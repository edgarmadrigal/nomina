<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Historial de cambios de los catálogos (dbo.Bitacora, ver application/sql/bitacora.sql).
 * Los triggers guardan el registro completo de antes y de después en JSON; aquí se resume
 * para la pantalla: qué registro es, qué campos cambiaron y de qué valor a qué valor.
 */
class BitacoraModel extends CI_Model {

    /** Nombre de cada catálogo en pantalla. */
    public static $catalogos = array(
        'empleados'         => 'Empleados',
        'usuarios'          => 'Usuarios',
        'perfiles'          => 'Perfiles',
        'UsuarioPermiso'    => 'Permisos de usuario',
        'UsuarioEmpresa'    => 'Empresas por usuario',
        'empresas'          => 'Empresas / plantas',
        'conceptos'         => 'Conceptos',
        'clasificacion'     => 'Clasificación de conceptos',
        'horarios'          => 'Horarios',
        'horarios_empleado' => 'Asignación de horarios',
        'incidencias'       => 'Incidencias',
        'DiasFestivos'      => 'Días festivos',
        'RelojDispositivo'  => 'Relojes checadores',
        'MenuOpcion'        => 'Opciones del menú',
        'RespaldoConfig'    => 'Respaldos (configuración)',
        'RespaldoDestino'   => 'Respaldos (ubicación)',
        'checadas'          => 'Checadas (cambios a mano)',
        'salarioPorAnio'    => 'Salarios',
    );

    /** Campos que no se muestran como "cambio" (los pone el sistema solo). */
    private $ignorar = array('update_dtm', 'update_iduser', 'create_dtm', 'fechaSubida', 'usuarioid_norm');

    const COLUMNAS = "b.id, CONVERT(VARCHAR(19), b.fecha, 120) AS fecha, b.tabla, b.accion, b.llave, b.idUsuario, b.usuario,
                      b.ip, b.pantalla, b.origen, b.antes, b.despues";

    /** Deja solo lo que hizo una persona: excluye SQL directo y procesos del sistema. */
    const SOLO_USUARIOS = "(b.usuario NOT LIKE 'Directo en SQL:%'
                            AND b.usuario NOT LIKE 'Proceso autom%'
                            AND b.usuario NOT LIKE 'Proceso programado%'
                            AND b.usuario <> 'Sistema web (sin sesión)')";

    /**
     * Lo más nuevo: posteriores a $desdeId (para el panel en tiempo real). Las checadas salen agrupadas por
     * evento; un evento que sigue creciendo vuelve a salir con el mismo evento.desde (la pantalla lo reemplaza).
     */
    public function recientes($desdeId, $limite = 40, $soloUsuarios = false)
    {
        $where = "b.id > ? AND b.tabla <> 'checadas'" . ($soloUsuarios ? " AND " . self::SOLO_USUARIOS : "");
        $filas = $this->db->query(
            "SELECT TOP (" . (int) $limite . ") " . self::COLUMNAS . " FROM dbo.Bitacora b WHERE " . $where . " ORDER BY b.id DESC",
            array((int) $desdeId))->result_array();
        // eventos de checadas: se arman con lo de las últimas 24 h (para conocer su inicio real) y salen los que tienen algo nuevo
        $wc = array("b.fecha >= DATEADD(HOUR, -24, GETDATE())");
        if ($soloUsuarios) $wc[] = self::SOLO_USUARIOS;
        $eventos = $this->eventosChecadas($wc, array(), (int) $desdeId, $limite);
        return $this->mezclar($this->resumir($filas), $eventos, $limite);
    }

    /** Historial con filtros (las checadas, agrupadas por evento). */
    public function historial($f, $limite = 500)
    {
        $w = array('b.fecha >= ?', 'b.fecha < DATEADD(DAY, 1, CAST(? AS DATE))');
        $p = array($f['desde'], $f['hasta']);
        if ($f['tabla'] !== '')   { $w[] = 'b.tabla = ?';   $p[] = $f['tabla']; }
        if ($f['accion'] !== '')  { $w[] = 'b.accion = ?';  $p[] = $f['accion']; }
        if ($f['usuario'] !== '') { $w[] = 'b.usuario LIKE ?'; $p[] = '%' . $f['usuario'] . '%'; }
        if (!empty($f['soloUsuarios'])) { $w[] = self::SOLO_USUARIOS; }
        if ($f['texto'] !== '') {
            $w[] = '(b.llave = ? OR b.antes LIKE ? OR b.despues LIKE ?)';
            $p[] = $f['texto']; $p[] = '%' . $f['texto'] . '%'; $p[] = '%' . $f['texto'] . '%';
        }
        $filas = array();
        if ($f['tabla'] !== 'checadas') {
            $filas = $this->db->query(
                "SELECT TOP (" . (int) $limite . ") " . self::COLUMNAS . " FROM dbo.Bitacora b WHERE " . implode(' AND ', $w) . " AND b.tabla <> 'checadas' ORDER BY b.id DESC",
                $p)->result_array();
        }
        $eventos = ($f['tabla'] === '' || $f['tabla'] === 'checadas') ? $this->eventosChecadas($w, $p, 0, $limite) : array();
        return $this->mezclar($this->resumir($filas), $eventos, $limite);
    }

    /** Une movimientos y eventos, del más nuevo al más viejo, hasta $limite. */
    private function mezclar(array $movs, array $eventos, $limite)
    {
        $todo = array_merge($movs, $eventos);
        usort($todo, function ($a, $b) { return $b['id'] - $a['id']; });
        return array_slice($todo, 0, $limite);
    }

    /**
     * Cambios a checadas agrupados por evento: mismo usuario, pantalla y acción, sin más de 60 s entre
     * un renglón y el siguiente (una importación de miles de checadas es UN evento). id = el último
     * renglón del evento (para el orden y el "lo nuevo"); evento.desde/hasta = rango de ids.
     */
    private function eventosChecadas(array $w, array $p, $nuevosDesde, $limite)
    {
        $sql = "WITH c AS (
                    SELECT b.id, b.fecha, b.accion, b.usuario, ISNULL(b.pantalla, '') AS pantalla, b.ip, b.origen,
                           JSON_VALUE(ISNULL(b.despues, b.antes), '$.usuarioid') AS emp,
                           JSON_VALUE(ISNULL(b.despues, b.antes), '$.fecha') AS dia,
                           CASE WHEN DATEDIFF(SECOND, LAG(b.fecha) OVER (PARTITION BY b.usuario, ISNULL(b.pantalla, ''), b.accion ORDER BY b.id), b.fecha) <= 60
                                THEN 0 ELSE 1 END AS nuevo
                    FROM dbo.Bitacora b
                    WHERE b.tabla = 'checadas' AND " . implode(' AND ', $w) . "
                ), g AS (
                    SELECT *, SUM(nuevo) OVER (PARTITION BY usuario, pantalla, accion ORDER BY id ROWS UNBOUNDED PRECEDING) AS grupo FROM c
                )
                SELECT TOP (" . (int) $limite . ")
                       MIN(id) AS desde, MAX(id) AS hasta, COUNT(*) AS n, COUNT(DISTINCT emp) AS empleados,
                       CONVERT(VARCHAR(19), MIN(fecha), 120) AS fecha, CONVERT(VARCHAR(19), MAX(fecha), 120) AS fechaFin,
                       MIN(dia) AS diaMin, MAX(dia) AS diaMax, accion, usuario, pantalla, MAX(ip) AS ip, MAX(origen) AS origen,
                       MIN(emp) AS unEmp
                FROM g
                GROUP BY usuario, pantalla, accion, grupo
                HAVING MAX(id) > ?
                ORDER BY MAX(id) DESC";
        $p[] = (int) $nuevosDesde;
        $grupos = $this->db->query($sql, $p)->result_array();
        $uno = array();
        foreach ($grupos as $g) if ((int) $g['empleados'] === 1) $uno[$g['unEmp']] = true;
        $nom = $this->nombres("SELECT empleado_id AS k, MAX(NOMBRE_COMPLETO) AS v FROM dbo.empleados WHERE empleado_id IN ? GROUP BY empleado_id", array_keys($uno));
        $out = array();
        foreach ($grupos as $g) {
            $n = (int) $g['n']; $e = (int) $g['empleados'];
            $dias = substr((string) $g['diaMin'], 0, 10) === substr((string) $g['diaMax'], 0, 10)
                  ? ' · ' . substr((string) $g['diaMin'], 0, 10)
                  : ' · del ' . substr((string) $g['diaMin'], 0, 10) . ' al ' . substr((string) $g['diaMax'], 0, 10);
            $quien = $e === 1 ? 'Empleado ' . $g['unEmp'] . (isset($nom[(string) $g['unEmp']]) ? ' · ' . $nom[(string) $g['unEmp']] : '')
                              : number_format($e) . ' empleados';
            $out[] = array(
                'id' => (int) $g['hasta'], 'fecha' => $g['fecha'], 'tabla' => 'checadas', 'catalogo' => self::$catalogos['checadas'],
                'accion' => $g['accion'], 'llave' => null, 'usuario' => $g['usuario'], 'ip' => $g['ip'],
                'pantalla' => $g['pantalla'], 'origen' => $g['origen'],
                'registro' => number_format($n) . ' checada' . ($n === 1 ? '' : 's') . ' · ' . $quien . ($g['diaMin'] ? $dias : ''),
                'cambios' => array(), 'masivo' => false,
                'evento' => array('desde' => (int) $g['desde'], 'hasta' => (int) $g['hasta'], 'n' => $n, 'empleados' => $e, 'fechaFin' => $g['fechaFin']),
            );
        }
        return $out;
    }

    /**
     * Todas las checadas de un evento, una por renglón: empleado, nombre, fecha, hora, reloj y, si fue
     * modificación, qué cambió. Se identifica por el rango de ids + usuario, pantalla y acción.
     */
    public function evento($desde, $hasta, $usuario, $pantalla, $accion)
    {
        $filas = $this->db->query(
            "SELECT TOP 50000 b.id, b.accion, b.antes, b.despues FROM dbo.Bitacora b
             WHERE b.tabla = 'checadas' AND b.id BETWEEN ? AND ? AND b.usuario = ? AND ISNULL(b.pantalla, '') = ? AND b.accion = ?
             ORDER BY b.id",
            array((int) $desde, (int) $hasta, $usuario, $pantalla, $accion))->result_array();
        $emps = array(); $regs = array();
        foreach ($filas as $f) {
            $a = $f['antes'] ? json_decode($f['antes'], true) : null;
            $d = $f['despues'] ? json_decode($f['despues'], true) : null;
            $r = $d ?: $a ?: array();
            if (isset($r['usuarioid'])) $emps[(string) $r['usuarioid']] = true;
            $regs[] = array($f, $a, $d, $r);
        }
        $nom = $this->nombres("SELECT empleado_id AS k, MAX(NOMBRE_COMPLETO) AS v FROM dbo.empleados WHERE empleado_id IN ? GROUP BY empleado_id", array_keys($emps));
        $out = array();
        foreach ($regs as $x) {
            list($f, $a, $d, $r) = $x;
            $cambio = '';
            if ($a && $d) {
                $partes = array();
                foreach ($d as $c => $v) {
                    if (in_array($c, $this->ignorar, true)) continue;
                    $ant = isset($a[$c]) ? $a[$c] : null;
                    if ($this->texto($ant) !== $this->texto($v)) $partes[] = $c . ': ' . $this->texto($ant) . ' → ' . $this->texto($v);
                }
                $cambio = implode(' | ', $partes);
            }
            $emp = isset($r['usuarioid']) ? (string) $r['usuarioid'] : '';
            $out[] = array(
                'id' => (int) $f['id'], 'accion' => $f['accion'], 'empleado' => $emp, 'nombre' => isset($nom[$emp]) ? $nom[$emp] : '',
                'fecha' => isset($r['fecha']) ? substr((string) $r['fecha'], 0, 10) : '',
                'hora' => isset($r['hora']) ? substr((string) $r['hora'], 0, 8) : (isset($r['fechahora']) ? substr((string) $r['fechahora'], 11, 8) : ''),
                'reloj' => isset($r['reloj']) ? (string) $r['reloj'] : '',
                'cambio' => $cambio,
            );
        }
        usort($out, function ($p, $q) { return strcmp($p['empleado'] . ' ' . $p['fecha'] . ' ' . $p['hora'], $q['empleado'] . ' ' . $q['fecha'] . ' ' . $q['hora']); });
        return $out;
    }

    /** Todo lo que le ha pasado a un registro. */
    public function delRegistro($tabla, $llave)
    {
        $filas = $this->db->query(
            "SELECT TOP 300 " . self::COLUMNAS . " FROM dbo.Bitacora b WHERE b.tabla = ? AND b.llave = ? ORDER BY b.id DESC",
            array($tabla, $llave))->result_array();
        return $this->resumir($filas);
    }

    /** Un movimiento con todos sus campos (antes y después). */
    public function detalle($id)
    {
        $fila = $this->db->query("SELECT " . self::COLUMNAS . " FROM dbo.Bitacora b WHERE b.id = ?", array((int) $id))->row_array();
        if (!$fila) return null;
        $r = $this->resumir(array($fila));
        $r = $r[0];
        $antes = $fila['antes'] ? json_decode($fila['antes'], true) : array();
        $despues = $fila['despues'] ? json_decode($fila['despues'], true) : array();
        $campos = array();
        foreach (array_unique(array_merge(array_keys((array) $antes), array_keys((array) $despues))) as $c) {
            $a = isset($antes[$c]) ? $antes[$c] : null;
            $d = isset($despues[$c]) ? $despues[$c] : null;
            $campos[] = array('campo' => $c, 'antes' => $this->texto($a), 'despues' => $this->texto($d),
                              'cambio' => $fila['antes'] && $fila['despues'] && $this->texto($a) !== $this->texto($d));
        }
        $r['campos'] = $campos;
        return $r;
    }

    /** Totales de hoy para el panel. */
    public function hoy()
    {
        $r = $this->db->query(
            "SELECT SUM(CASE WHEN accion = 'ALTA' THEN 1 ELSE 0 END) AS altas,
                    SUM(CASE WHEN accion = 'MODIFICACION' THEN 1 ELSE 0 END) AS modificaciones,
                    SUM(CASE WHEN accion = 'ELIMINACION' THEN 1 ELSE 0 END) AS eliminaciones,
                    COUNT(DISTINCT usuario) AS usuarios
             FROM dbo.Bitacora WHERE fecha >= CAST(GETDATE() AS DATE)")->row_array();
        $top = $this->db->query(
            "SELECT TOP 5 usuario, COUNT(*) AS n FROM dbo.Bitacora WHERE fecha >= CAST(GETDATE() AS DATE)
             GROUP BY usuario ORDER BY COUNT(*) DESC")->result_array();
        return array('totales' => array_map('intval', $r), 'usuarios' => $top);
    }

    // ------------------------------------------------------------------
    //  Revertir / reaplicar un movimiento
    // ------------------------------------------------------------------

    /**
     * Deja el registro como estaba en 'antes' (revertir) o en 'despues' (reaplicar).
     *   - Si el estado objetivo es NULL (p. ej. revertir un alta, o reaplicar una eliminación)
     *     el registro debe NO existir -> se elimina.
     *   - Si el estado objetivo tiene valores y el registro existe -> se actualiza.
     *   - Si tiene valores y el registro ya no existe -> se vuelve a insertar.
     * Todo dentro de una transacción. La contraseña y los campos de sistema nunca se tocan.
     * El propio cambio queda registrado en la bitácora a nombre de quien lo hace.
     *
     * @return array {exito, mensaje}
     */
    public function revertir($id, $modo)
    {
        $modo = ($modo === 'reaplicar') ? 'reaplicar' : 'revertir';
        $b = $this->db->query("SELECT tabla, accion, llave, antes, despues FROM dbo.Bitacora WHERE id = ?", array((int) $id))->row_array();
        if (!$b) return array('exito' => false, 'mensaje' => 'No se encontró el movimiento.');

        $tabla = $b['tabla'];
        if (!isset(self::$catalogos[$tabla])) return array('exito' => false, 'mensaje' => 'Ese catálogo no se puede revertir.');
        if ($b['antes'] === null && $b['despues'] === null)
            return array('exito' => false, 'mensaje' => 'Fue un cambio masivo: no se guardó registro por registro, no se puede revertir.');

        $antes   = $b['antes']   ? json_decode($b['antes'], true)   : null;
        $despues = $b['despues'] ? json_decode($b['despues'], true) : null;
        $target  = ($modo === 'revertir') ? $antes : $despues;         // estado al que queremos dejarlo
        $fuentePk = $antes !== null ? $antes : $despues;               // de aquí sacamos la llave primaria
        if (!is_array($fuentePk)) return array('exito' => false, 'mensaje' => 'No hay datos suficientes para revertir.');

        // Columnas reales de la tabla y su identidad; llave primaria
        $cols = $this->columnas($tabla);                              // name => is_identity
        if (!$cols) return array('exito' => false, 'mensaje' => 'No se pudo leer la estructura de la tabla.');
        $pk = $this->llavePrimaria($tabla);
        if (!$pk) return array('exito' => false, 'mensaje' => 'La tabla no tiene llave primaria: no se puede ubicar el registro.');

        // Valores de la llave (de la fuente que sí existe)
        $where = array(); $pWhere = array();
        foreach ($pk as $c) {
            if (!array_key_exists($c, $fuentePk)) return array('exito' => false, 'mensaje' => 'Falta la llave del registro en la bitácora.');
            $where[] = '[' . $c . '] = ?'; $pWhere[] = $this->valorSql($fuentePk[$c]);
        }
        $whereSql = implode(' AND ', $where);
        $tablaSql = 'dbo.[' . $tabla . ']';

        $existe = (int) $this->db->query("SELECT COUNT(*) AS n FROM $tablaSql WHERE $whereSql", $pWhere)->row_array()['n'] > 0;

        // Columnas que nunca se escriben: contraseña (solo se guarda huella) y campos de sistema
        $saltar = array_merge(array('password'), $this->ignorar);
        $saltar = array_map('strtolower', $saltar);

        $this->db->db_debug = FALSE;
        $this->db->trans_begin();
        $accionHecha = '';
        try {
            if ($target === null) {
                // El registro no debería existir
                if ($existe) {
                    if (!$this->db->query("DELETE FROM $tablaSql WHERE $whereSql", $pWhere)) throw new Exception('No se pudo eliminar (quizá tiene datos que dependen de él).');
                    $accionHecha = 'eliminado';
                } else {
                    $accionHecha = 'nada (el registro ya no existía)';
                }
            } else {
                // Queremos dejar el registro igual a $target
                $campos = array();
                foreach ($target as $c => $v) {
                    if (!isset($cols[$c])) continue;                       // columna que ya no existe
                    if (in_array(strtolower($c), $saltar, true)) continue; // password / campos de sistema
                    $campos[$c] = $this->valorSql($v);
                }
                if ($existe) {
                    $sets = array(); $pSet = array();
                    foreach ($campos as $c => $v) {
                        if (in_array($c, $pk, true)) continue;             // la llave no se cambia
                        if (!empty($cols[$c])) continue;                   // columna identidad: no se actualiza
                        $sets[] = '[' . $c . '] = ?'; $pSet[] = $v;
                    }
                    if (!$sets) throw new Exception('No hay nada que cambiar.');
                    if (!$this->db->query("UPDATE $tablaSql SET " . implode(', ', $sets) . " WHERE $whereSql", array_merge($pSet, $pWhere)))
                        throw new Exception('No se pudo actualizar el registro.');
                    $accionHecha = 'actualizado';
                } else {
                    // Reinsertar. Si hay columna identidad entre las que insertamos, hay que permitir identidad.
                    $nombres = array(); $marcas = array(); $pIns = array(); $hayIdentidad = false;
                    foreach ($campos as $c => $v) {
                        $nombres[] = '[' . $c . ']'; $marcas[] = '?'; $pIns[] = $v;
                        if (!empty($cols[$c])) $hayIdentidad = true;
                    }
                    if (!$nombres) throw new Exception('No hay datos para volver a crear el registro.');
                    $ins = "INSERT INTO $tablaSql (" . implode(', ', $nombres) . ") VALUES (" . implode(', ', $marcas) . ")";
                    if ($hayIdentidad) $ins = "SET IDENTITY_INSERT $tablaSql ON; " . $ins . "; SET IDENTITY_INSERT $tablaSql OFF;";
                    if (!$this->db->query($ins, $pIns)) throw new Exception('No se pudo volver a crear el registro (quizá choca con otro dato).');
                    $accionHecha = 'vuelto a crear';
                }
            }

            if ($this->db->trans_status() === FALSE) throw new Exception('La operación no se completó.');
            $this->db->trans_commit();
            $this->db->db_debug = TRUE;
            $reg = isset(self::$catalogos[$tabla]) ? self::$catalogos[$tabla] : $tabla;
            return array('exito' => true, 'mensaje' => 'Listo: el registro de "' . $reg . '" fue ' . $accionHecha . '.');
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->db->db_debug = TRUE;
            return array('exito' => false, 'mensaje' => 'No se pudo: ' . $e->getMessage());
        }
    }

    /** Columnas reales de la tabla: nombre (minúsculas para comparar) no; se devuelve name => is_identity. */
    private function columnas($tabla)
    {
        $r = $this->db->query(
            "SELECT c.name, c.is_identity FROM sys.columns c WHERE c.object_id = OBJECT_ID('dbo.' + ?)", array($tabla))->result_array();
        $m = array();
        foreach ($r as $x) $m[$x['name']] = (int) $x['is_identity'];
        return $m;
    }

    /** Columnas de la llave primaria, en orden. */
    private function llavePrimaria($tabla)
    {
        $r = $this->db->query(
            "SELECT c.name FROM sys.indexes i
               JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
               JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
             WHERE i.object_id = OBJECT_ID('dbo.' + ?) AND i.is_primary_key = 1
             ORDER BY ic.key_ordinal", array($tabla))->result_array();
        return array_map(function ($x) { return $x['name']; }, $r);
    }

    /** Convierte el valor del JSON al tipo que espera SQL Server. */
    private function valorSql($v)
    {
        if (is_bool($v)) return $v ? 1 : 0;
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return $v;   // null, número o texto (las fechas ISO las acepta sqlsrv tal cual)
    }

    /**
     * Para cada movimiento: nombre del catálogo, descripción del registro (nombre del empleado,
     * concepto, etc.) y lista de campos que cambiaron. No manda el JSON completo.
     */
    private function resumir(array $filas)
    {
        $empleados = array(); $conceptos = array(); $horarios = array(); $empresas = array();
        foreach ($filas as &$f) {
            $f['a'] = $f['antes'] ? json_decode($f['antes'], true) : null;
            $f['d'] = $f['despues'] ? json_decode($f['despues'], true) : null;
            $reg = $f['d'] ?: $f['a'] ?: array();
            if (isset($reg['empleado_id']) && $f['tabla'] !== 'empleados') $empleados[(string) $reg['empleado_id']] = true;
            if (isset($reg['usuarioid']) && $f['tabla'] === 'checadas') $empleados[(string) $reg['usuarioid']] = true;
            if (isset($reg['concepto_id'])) $conceptos[(int) $reg['concepto_id']] = true;
            if (isset($reg['empresa_id']) && $reg['empresa_id'] !== '' && $reg['empresa_id'] !== null) $empresas[(int) $reg['empresa_id']] = true;
            foreach (array('lunes','martes','miercoles','jueves','viernes','sabado','domingo') as $dia) {
                if (isset($reg['idhorario_' . $dia])) $horarios[(int) $reg['idhorario_' . $dia]] = true;
            }
        }
        unset($f);
        $nomEmp = $this->nombres("SELECT empleado_id AS k, MAX(NOMBRE_COMPLETO) AS v FROM dbo.empleados WHERE empleado_id IN ? GROUP BY empleado_id", array_keys($empleados));
        $nomCon = $this->nombres("SELECT id AS k, descripcion AS v FROM dbo.conceptos WHERE id IN ?", array_keys($conceptos));
        $nomHor = $this->nombres("SELECT id AS k, nombre AS v FROM dbo.horarios WHERE id IN ?", array_keys($horarios));
        $nomEmpresa = $this->nombres("SELECT id AS k, nombre AS v FROM dbo.empresas WHERE id IN ?", array_keys($empresas));

        $salida = array();
        foreach ($filas as $f) {
            $reg = $f['d'] ?: $f['a'] ?: array();
            $cambios = array();
            if ($f['a'] && $f['d']) {
                foreach ($f['d'] as $c => $v) {
                    if (in_array($c, $this->ignorar, true)) continue;
                    $antes = isset($f['a'][$c]) ? $f['a'][$c] : null;
                    if ($this->texto($antes) === $this->texto($v)) continue;
                    $cambios[] = array('campo' => $c, 'antes' => $this->legible($c, $antes, $nomCon, $nomHor), 'despues' => $this->legible($c, $v, $nomCon, $nomHor));
                }
            }
            $salida[] = array(
                'id' => (int) $f['id'], 'fecha' => $f['fecha'], 'tabla' => $f['tabla'],
                'catalogo' => isset(self::$catalogos[$f['tabla']]) ? self::$catalogos[$f['tabla']] : $f['tabla'],
                'accion' => $f['accion'], 'llave' => $f['llave'], 'usuario' => $f['usuario'], 'ip' => $f['ip'],
                'pantalla' => $f['pantalla'], 'origen' => $f['origen'],
                'registro' => $this->describir($f['tabla'], $reg, $nomEmp, $nomCon, $nomEmpresa),
                'cambios' => $cambios,
                'masivo' => $f['antes'] === null && $f['despues'] === null,
            );
        }
        return $salida;
    }

    private function nombres($sql, array $llaves)
    {
        if (!$llaves) return array();
        // como texto: PHP vuelve número las llaves numéricas y empleados.empleado_id es VARCHAR
        // (hay un registro ' 82300' que truena si SQL Server compara como entero)
        $llaves = array_map('strval', $llaves);
        $r = array();
        foreach (array_chunk($llaves, 500) as $parte) {
            $marcas = implode(',', array_fill(0, count($parte), '?'));
            foreach ($this->db->query(str_replace('IN ?', 'IN (' . $marcas . ')', $sql), $parte)->result_array() as $x) $r[(string) $x['k']] = $x['v'];
        }
        return $r;
    }

    /** Texto corto que identifica el registro. */
    private function describir($tabla, $r, $nomEmp, $nomCon, $nomEmpresa = array())
    {
        $emp = function ($id) use ($nomEmp) { return 'Empleado ' . $id . (isset($nomEmp[(string) $id]) ? ' · ' . $nomEmp[(string) $id] : ''); };
        // Empresa del registro (los números de empleado se repiten entre empresas, así que ayuda a ubicarlo)
        $empresaTxt = '';
        if (isset($r['empresa_id']) && $r['empresa_id'] !== '' && $r['empresa_id'] !== null) {
            $empresaTxt = ' · ' . (isset($nomEmpresa[(string) $r['empresa_id']]) ? $nomEmpresa[(string) $r['empresa_id']] : 'empresa ' . $r['empresa_id']);
        }
        switch ($tabla) {
            case 'empleados':
                return (isset($r['empleado_id']) ? $r['empleado_id'] . ' · ' : '') . (isset($r['NOMBRE_COMPLETO']) ? $r['NOMBRE_COMPLETO'] : '') . $empresaTxt;
            case 'incidencias':
                $t = isset($r['empleado_id']) ? $emp($r['empleado_id']) : 'Incidencia por empresa ' . (isset($r['empresa_id']) ? $r['empresa_id'] : '');
                if (isset($r['empleado_id'])) $t .= $empresaTxt;
                if (isset($r['concepto_id'])) $t .= ' · ' . (isset($nomCon[(string) $r['concepto_id']]) ? $nomCon[(string) $r['concepto_id']] : 'concepto ' . $r['concepto_id']);
                if (isset($r['fechaInicio'])) $t .= ' · ' . $r['fechaInicio'] . ($r['fechaFin'] && $r['fechaFin'] !== $r['fechaInicio'] ? ' al ' . $r['fechaFin'] : '');
                return $t;
            case 'horarios_empleado':
                return (isset($r['empleado_id']) ? $emp($r['empleado_id']) : '') . $empresaTxt . (isset($r['fechaAsignacion']) ? ' · desde ' . $r['fechaAsignacion'] : '');
            case 'checadas':
                return (isset($r['usuarioid']) ? $emp($r['usuarioid']) : '') . (isset($r['fechahora']) ? ' · ' . str_replace('T', ' ', $r['fechahora']) : '');
            case 'usuarios':
                return (isset($r['usuario']) ? $r['usuario'] : '') . (isset($r['nombre']) ? ' · ' . $r['nombre'] : '');
            case 'UsuarioPermiso':
                return 'Usuario ' . $r['idUsuario'] . ' · opción ' . $r['idOpcion'];
            case 'salarioPorAnio':
                $anio = null;
                foreach ($r as $c => $v) { if (stripos($c, 'a') === 0 && is_int($v) && $c !== 'activo') $anio = $v; }
                return '$' . (isset($r['salario']) ? number_format((float) $r['salario'], 2) : '') . ($anio ? ' · ' . $anio : '');
            case 'UsuarioEmpresa':
                return 'Usuario ' . $r['idUsuario'] . ' · empresa ' . $r['idEmpresa'];
        }
        foreach (array('nombre', 'descripcion', 'Descripcion', 'perfil', 'tipo', 'ruta') as $c) {
            if (!empty($r[$c])) return (string) $r[$c];
        }
        return '';
    }

    private function legible($campo, $v, $nomCon, $nomHor)
    {
        $t = $this->texto($v);
        if ($v !== null && $campo === 'concepto_id' && isset($nomCon[(string) $v])) return $t . ' (' . $nomCon[(string) $v] . ')';
        if ($v !== null && strpos($campo, 'idhorario_') === 0 && isset($nomHor[(string) $v])) return $t . ' (' . $nomHor[(string) $v] . ')';
        return $t;
    }

    private function texto($v)
    {
        if ($v === null) return '';
        if ($v === true) return 'Sí';
        if ($v === false) return 'No';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        return str_replace('T00:00:00', '', (string) $v);
    }
}
