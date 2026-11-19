<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Importar/Exportar > Modifica excepciones Microsip.
 * Reemplaza al programa "Modifica Excepciones.exe" (SOTI\Programa Nóminas): toma un Excel
 * (columna A = número de empleado, fila 1 = nombre o clave del concepto) y pone esas cuotas en las
 * excepciones de una nómina de Microsip.
 *  - Solo toca empleados que YA tienen excepción en esa nómina; los demás se ignoran (igual que el programa).
 *  - Si el empleado ya tiene el concepto, cambia la cuota; si no lo tiene, lo agrega (no periódico).
 *  - Celdas vacías o en 0 no se tocan.
 * A diferencia del programa viejo, primero enseña una vista previa (qué cambia, qué se agrega, qué queda
 * igual); el usuario quita lo que no quiera y luego aplica. Todo se escribe en el Microsip de la empresa
 * por su servidor vinculado (EXEC ... AT) en bloques EXECUTE BLOCK (cada bloque entra completo o no entra),
 * y cada cambio queda en dbo.ModificaExcepcionesLog con el valor anterior.
 */
class modificaExcepciones extends CI_Controller {

    private $tipos = array('N' => 'Ordinaria', 'E' => 'Extraordinaria', 'A' => 'Aguinaldo', 'L' => 'Liquidación', 'R' => 'Reparto de utilidades');

    // Encabezados que no son conceptos (por si el Excel trae el nombre del empleado)
    private $columnasIgnoradas = array('NOMBRE', 'NOMBRE COMPLETO', 'EMPLEADO', 'NOMBRE EMPLEADO', 'NOMBRE DEL EMPLEADO');

    // Cambios por bloque EXECUTE BLOCK (texto del bloque por debajo de 64 KB)
    const POR_BLOQUE = 250;

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('modificaExcepcionesView', $data);
        $this->load->view('footer', $data);
    }

    /** Empresas con Microsip que el usuario puede ver. */
    public function empresas(){
        if (!$this->puedeVer()) { $this->json(array()); return; }
        $filas = $this->db->query(
            "SELECT e.id, e.nombre FROM dbo.MicrosipEmpresa me JOIN dbo.empresas e ON e.id = me.empresa_id
             WHERE me.activo = 1 ORDER BY e.nombre")->result_array();
        $this->load->model('PermisoModel');
        $this->json($this->PermisoModel->filtrarPlantas((int) $this->session->userdata['login']['user']->id, $filas));
    }

    /** Últimas nóminas de la empresa en Microsip (para elegir a cuál se le modifican las excepciones). */
    public function nominas(){
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso.')); return; }
        $srv = $this->servidor((int) $this->input->post('empresa_id'));
        if (!$srv) { $this->json(array('exito' => false, 'mensaje' => 'Empresa no válida.')); return; }
        $filas = $this->openquery($srv,
            "SELECT FIRST 40 n.NOMINA_ID, n.FECHA, n.TIPO_NOM, n.APLICADO,
                    (SELECT COUNT(*) FROM EXCEP_EMPLEADOS x WHERE x.NOMINA_ID = n.NOMINA_ID) AS NEXC
             FROM NOMINAS n ORDER BY n.FECHA DESC, n.NOMINA_ID DESC");
        if ($filas === false) { $this->json(array('exito' => false, 'mensaje' => 'No respondió el Microsip de la empresa (' . $srv . ').')); return; }
        $out = array();
        foreach ($filas as $f) {
            $out[] = array(
                'id' => (int) $f['NOMINA_ID'],
                'fecha' => $this->ymd($f['FECHA']),
                'tipo' => trim($f['TIPO_NOM']),
                'tipoNombre' => isset($this->tipos[trim($f['TIPO_NOM'])]) ? $this->tipos[trim($f['TIPO_NOM'])] : trim($f['TIPO_NOM']),
                'aplicado' => trim($f['APLICADO']) === 'S',
                'excepciones' => (int) $f['NEXC'],
            );
        }
        $this->json(array('exito' => true, 'nominas' => $out));
    }

    /**
     * Lee el Excel y compara contra las excepciones actuales de la nómina. No escribe nada en Microsip:
     * guarda el plan en un archivo temporal y regresa la vista previa con un token para aplicar.
     */
    public function previsualizar(){
        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso.')); return; }
        $empresa = (int) $this->input->post('empresa_id');
        $nominaId = (int) $this->input->post('nomina_id');
        $srv = $this->servidor($empresa);
        if (!$srv) { $this->json(array('exito' => false, 'mensaje' => 'Empresa no válida.')); return; }
        if ($nominaId <= 0) { $this->json(array('exito' => false, 'mensaje' => 'Elige la nómina.')); return; }
        if (empty($_FILES['archivo']['tmp_name']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) { $this->json(array('exito' => false, 'mensaje' => 'No llegó el archivo.')); return; }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('xlsx', 'xls'), true)) { $this->json(array('exito' => false, 'mensaje' => 'Debe ser un Excel (.xlsx o .xls).')); return; }

        $this->json($this->comparar($empresa, $nominaId, $srv, $_FILES['archivo']['tmp_name'], $_FILES['archivo']['name']));
    }

    /** Compara el Excel ($ruta) contra las excepciones de la nómina y guarda el plan; no escribe en Microsip. */
    protected function comparar($empresa, $nominaId, $srv, $ruta, $nombre){
        $nomina = $this->nomina($srv, $nominaId);
        if ($nomina === false) { return array('exito' => false, 'mensaje' => 'No respondió el Microsip de la empresa (' . $srv . ').'); }
        if (!$nomina) { return array('exito' => false, 'mensaje' => 'No se encontró la nómina en Microsip.'); }

        require_once APPPATH . '/third_party/PHPExcel.php';
        try {
            $filas = PHPExcel_IOFactory::load($ruta)->getSheet(0)->toArray(null, true, false, false);
        } catch (Exception $e) { return array('exito' => false, 'mensaje' => 'No se pudo leer el Excel.'); }
        if (count($filas) < 2) { return array('exito' => false, 'mensaje' => 'El Excel no trae renglones: la fila 1 lleva los conceptos y la columna A el número de empleado.'); }

        // ---- Conceptos de la fila 1 (por nombre, por clave o "clave nombre")
        $catalogo = $this->openquery($srv, "SELECT CONCEPTO_NO_ID, CLAVE, NOMBRE, NATURALEZA FROM CONCEPTOS_NO");
        if ($catalogo === false) { return array('exito' => false, 'mensaje' => 'No se pudo leer el catálogo de conceptos de Microsip.'); }
        $porNombre = array(); $porClave = array(); $info = array();
        foreach ($catalogo as $c) {
            $id = (int) $c['CONCEPTO_NO_ID'];
            $nom = $this->may($c['NOMBRE']); $clv = $this->may($c['CLAVE']);
            if (!isset($porNombre[$nom])) $porNombre[$nom] = $id;
            if ($clv !== '' && !isset($porClave[$clv])) $porClave[$clv] = $id;
            $info[$id] = array('id' => $id, 'clave' => trim($c['CLAVE']), 'nombre' => trim($c['NOMBRE']), 'nat' => trim($c['NATURALEZA']));
        }
        $columnas = array();     // índice de columna del Excel => concepto_no_id
        $noEncontrados = array();
        foreach ($filas[0] as $i => $titulo) {
            if ($i === 0) continue;
            $t = $this->may($titulo);
            if ($t === '' || in_array($t, $this->columnasIgnoradas, true)) continue;
            $id = isset($porNombre[$t]) ? $porNombre[$t] : (isset($porClave[$t]) ? $porClave[$t] : 0);
            if (!$id && preg_match('/^(\S+)\s+(.+)$/u', $t, $m) && isset($porClave[$m[1]]) && $this->may($info[$porClave[$m[1]]]['nombre']) === $m[2]) $id = $porClave[$m[1]];
            if ($id) $columnas[$i] = $id; else $noEncontrados[] = trim((string) $titulo);
        }
        if ($noEncontrados) { return array('exito' => false, 'mensaje' => 'No se encontraron en Microsip estos conceptos (fila 1): ' . implode(', ', $noEncontrados) . '. Escríbelos con el nombre o la clave tal como están en Microsip.'); }
        if (!$columnas) { return array('exito' => false, 'mensaje' => 'La fila 1 no trae conceptos (de la columna B en adelante).'); }
        if (count(array_unique($columnas)) !== count($columnas)) { return array('exito' => false, 'mensaje' => 'Hay un concepto repetido en la fila 1.'); }

        // ---- Excepciones actuales de la nómina
        $exc = $this->excepciones($srv, $nominaId);
        if ($exc === false) { return array('exito' => false, 'mensaje' => 'No se pudieron leer las excepciones de la nómina.'); }

        // ---- Renglones del Excel
        $renglones = array(); $errores = array(); $numeros = array();
        for ($r = 1; $r < count($filas); $r++) {
            $num = trim((string) $filas[$r][0]);
            if ($num === '') continue;
            if (!preg_match('/^\d+(\.0+)?$/', $num)) { $errores[] = 'Fila ' . ($r + 1) . ': "' . $num . '" no es un número de empleado.'; continue; }
            $num = (int) $num;
            $valores = array();
            foreach ($columnas as $i => $cid) {
                $v = isset($filas[$r][$i]) ? trim(str_replace(array(',', '$', ' '), '', (string) $filas[$r][$i])) : '';
                if ($v === '') { $valores[$cid] = 0.0; continue; }
                if (!is_numeric($v)) { $errores[] = 'Fila ' . ($r + 1) . ', ' . $info[$cid]['nombre'] . ': "' . $filas[$r][$i] . '" no es un número.'; $v = 0; }
                $valores[$cid] = round((float) $v, 6);
            }
            if (isset($renglones[$num])) $errores[] = 'El empleado ' . $num . ' viene repetido (fila ' . ($r + 1) . '); se toma el último renglón.';
            $renglones[$num] = $valores;
            $numeros[$num] = true;
        }
        if (!$renglones) { return array('exito' => false, 'mensaje' => 'No hay números de empleado en la columna A.'); }

        // Nombres de los que no tienen excepción (para enseñarlos; esos no se tocan)
        $faltan = array();
        foreach (array_keys($numeros) as $n) if (!isset($exc[$n])) $faltan[] = $n;
        $nombres = $this->nombres($srv, $faltan);

        // ---- Comparación
        $cambios = array(); $vista = array();
        $res = array('actualizar' => 0, 'agregar' => 0, 'igual' => 0, 'sinExcepcion' => 0, 'noExiste' => 0);
        foreach ($renglones as $num => $valores) {
            $fila = array('numero' => $num, 'celdas' => array());
            if (isset($exc[$num])) {
                $e = $exc[$num];
                $fila['nombre'] = $e['nombre']; $fila['estado'] = 'M';
                foreach ($valores as $cid => $nuevo) {
                    $det = isset($e['det'][$cid]) ? $e['det'][$cid] : null;
                    $actual = $det ? $det['cuota'] : null;
                    if (!($nuevo > 0)) { $accion = ''; }
                    elseif (!$det) { $accion = 'I'; }
                    elseif (abs($det['cuota'] - $nuevo) < 0.0000005) { $accion = '='; }
                    else { $accion = 'U'; }
                    $celda = array('nuevo' => $nuevo, 'actual' => $actual, 'accion' => $accion);
                    if ($det && $det['activo'] !== 'S') $celda['inactivo'] = true;
                    if ($accion === 'U' || $accion === 'I') {
                        $k = $num . '|' . $cid;
                        $celda['k'] = $k;
                        $cambios[$k] = array('numero' => $num, 'nombre' => $e['nombre'], 'excep_emp_id' => $e['excep_emp_id'],
                                             'det_id' => $det ? $det['det_id'] : null, 'concepto_no_id' => $cid,
                                             'concepto' => $info[$cid]['clave'] . ' ' . $info[$cid]['nombre'],
                                             'actual' => $actual, 'nuevo' => $nuevo, 'accion' => $accion);
                        $res[$accion === 'U' ? 'actualizar' : 'agregar']++;
                    } elseif ($accion === '=') $res['igual']++;
                    $fila['celdas'][$cid] = $celda;
                }
            } else {
                $existe = isset($nombres[$num]);
                $fila['nombre'] = $existe ? $nombres[$num] : '';
                $fila['estado'] = $existe ? 'I' : 'X';
                $res[$existe ? 'sinExcepcion' : 'noExiste']++;
                foreach ($valores as $cid => $nuevo) $fila['celdas'][$cid] = array('nuevo' => $nuevo, 'actual' => null, 'accion' => '');
            }
            $vista[] = $fila;
        }

        foreach ((array) glob($this->rutaPlan('*')) as $viejo) if (@filemtime($viejo) < time() - 86400) @unlink($viejo);
        $token = bin2hex(openssl_random_pseudo_bytes(12));
        $plan = array('usuario' => $this->idUsuario(), 'creado' => time(), 'empresa_id' => $empresa, 'servidor' => $srv,
                      'nomina' => $nomina, 'archivo' => mb_substr($nombre, 0, 200), 'cambios' => $cambios,
                      'columnas' => array_values(array_map(function ($cid) use ($info) { return $info[$cid]; }, $columnas)),
                      'filas' => $vista);
        if (@file_put_contents($this->rutaPlan($token), json_encode($plan)) === false) { return array('exito' => false, 'mensaje' => 'No se pudo guardar la vista previa en el servidor.'); }

        return array('exito' => true, 'token' => $token, 'nomina' => $nomina, 'columnas' => $plan['columnas'],
                          'filas' => $vista, 'resumen' => $res, 'avisos' => $errores);
    }

    /**
     * Escribe en Microsip los cambios marcados de la vista previa (claves "numero|concepto_no_id").
     * Antes de escribir vuelve a leer la nómina: si ya se aplicó o alguien movió esas excepciones
     * desde la vista previa, no escribe nada y pide volver a cargar.
     */
    public function aplicar(){
        @ini_set('max_execution_time', 0);
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso.')); return; }
        $token = preg_replace('/[^a-f0-9]/', '', (string) $this->input->post('token'));
        $plan = $token ? json_decode((string) @file_get_contents($this->rutaPlan($token)), true) : null;
        if (!$plan || $plan['usuario'] !== $this->idUsuario() || time() - $plan['creado'] > 4 * 3600 || !empty($plan['usado'])) { $this->json(array('exito' => false, 'mensaje' => 'La vista previa ya no es válida; vuelve a cargar el Excel.')); return; }
        $claves = $this->input->post('claves');
        if (!is_array($claves)) $claves = array_filter(explode(',', (string) $claves));
        $sel = array();
        foreach ($claves as $k) if (isset($plan['cambios'][$k])) $sel[$k] = $plan['cambios'][$k];
        if (!$sel) { $this->json(array('exito' => false, 'mensaje' => 'No hay cambios marcados para aplicar.')); return; }

        $srv = $this->servidor((int) $plan['empresa_id']);
        if (!$srv || $srv !== $plan['servidor']) { $this->json(array('exito' => false, 'mensaje' => 'Empresa no válida.')); return; }
        $nominaId = (int) $plan['nomina']['id'];
        $nomina = $this->nomina($srv, $nominaId);
        if (!$nomina) { $this->json(array('exito' => false, 'mensaje' => 'No se pudo volver a leer la nómina en Microsip.')); return; }
        if ($nomina['aplicado']) { $this->json(array('exito' => false, 'mensaje' => 'La nómina ya está aplicada en Microsip; no se modifican sus excepciones.')); return; }

        // Revisión contra lo que hay ahorita
        $exc = $this->excepciones($srv, $nominaId);
        if ($exc === false) { $this->json(array('exito' => false, 'mensaje' => 'No se pudieron leer las excepciones de la nómina.')); return; }
        foreach ($sel as $c) {
            $e = isset($exc[$c['numero']]) ? $exc[$c['numero']] : null;
            $det = ($e && isset($e['det'][$c['concepto_no_id']])) ? $e['det'][$c['concepto_no_id']] : null;
            $igual = $e && (int) $e['excep_emp_id'] === (int) $c['excep_emp_id'] && (
                     $c['accion'] === 'I' ? !$det
                                          : ($det && (int) $det['det_id'] === (int) $c['det_id'] && abs($det['cuota'] - $c['actual']) < 0.0000005));
            if (!$igual) { $this->json(array('exito' => false, 'mensaje' => 'Las excepciones del empleado ' . $c['numero'] . ' cambiaron en Microsip desde la vista previa. No se guardó nada; vuelve a cargar el Excel.')); return; }
        }

        // Escritura por bloques; cada bloque es una sola sentencia (entra completo o no entra)
        $sentencias = array();
        foreach ($sel as $k => $c) {
            $cuota = sprintf('%.6F', $c['nuevo']);
            $sentencias[$k] = $c['accion'] === 'U'
                ? 'UPDATE EXCEP_EMPLEADOS_DET SET CUOTA = ' . $cuota . ' WHERE EXCEP_EMP_DET_ID = ' . (int) $c['det_id'] . ';'
                : 'INSERT INTO EXCEP_EMPLEADOS_DET (EXCEP_EMP_DET_ID, EXCEP_EMP_ID, CONCEPTO_NO_ID, CUOTA, AHORRO_EMPRESA, ACTIVO, ES_PERIODICO)'
                  . ' VALUES (GEN_ID(ID_DOCTOS, 1), ' . (int) $c['excep_emp_id'] . ', ' . (int) $c['concepto_no_id'] . ', ' . $cuota . ", 0, 'S', 'N');";
        }
        $hechos = array(); $error = null;
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        foreach (array_chunk($sentencias, self::POR_BLOQUE, true) as $bloque) {
            $fb = 'EXECUTE BLOCK AS BEGIN ' . implode(' ', $bloque) . ' END';
            $ok = $this->db->query('EXEC (\'' . str_replace("'", "''", $fb) . '\') AT [' . $srv . ']');
            if (!$ok) { $err = $this->db->error(); $error = $err['message']; break; }
            foreach (array_keys($bloque) as $k) $hechos[$k] = $sel[$k];
        }

        // Bitácora
        $usr = $this->idUsuario();
        foreach ($hechos as $c) {
            $this->db->query("INSERT INTO dbo.ModificaExcepcionesLog (empresa_id, nomina_id, fecha_nomina, tipo_nomina, numero, concepto_no_id, concepto, accion, cuota_anterior, cuota_nueva, archivo, idUsuario)
                              VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                array((int) $plan['empresa_id'], $nominaId, $plan['nomina']['fecha'], $plan['nomina']['tipo'], (int) $c['numero'],
                      (int) $c['concepto_no_id'], $c['concepto'], $c['accion'], $c['actual'], $c['nuevo'], $plan['archivo'], $usr));
        }
        $this->db->db_debug = $debug;
        if ($hechos) { $plan['usado'] = true; @file_put_contents($this->rutaPlan($token), json_encode($plan)); }   // se queda para el Excel, pero ya no se vuelve a aplicar

        $n = array('actualizados' => 0, 'agregados' => 0);
        foreach ($hechos as $c) $n[$c['accion'] === 'U' ? 'actualizados' : 'agregados']++;
        $this->json(array('exito' => !$error, 'aplicados' => array_keys($hechos), 'actualizados' => $n['actualizados'], 'agregados' => $n['agregados'],
                          'mensaje' => $error ? ('Microsip rechazó los cambios' . ($hechos ? ' a partir del cambio ' . (count($hechos) + 1) . ' (los anteriores sí se guardaron)' : '; no se guardó nada') . ': ' . $error) : 'Listo.'));
    }

    /** Excel de la vista previa (lo que trae el archivo contra lo que hay en Microsip). */
    public function excel(){
        if (!$this->puedeVer()) { show_error('No tienes permiso.', 403); return; }
        $token = preg_replace('/[^a-f0-9]/', '', (string) $this->input->get('token'));
        $plan = $token ? json_decode((string) @file_get_contents($this->rutaPlan($token)), true) : null;
        if (!$plan || $plan['usuario'] !== $this->idUsuario()) { show_error('La vista previa ya no es válida; vuelve a cargar el Excel.', 400); return; }

        require_once APPPATH . '/third_party/PHPExcel.php';
        $xls = new PHPExcel();
        $h = $xls->getActiveSheet();
        $h->setTitle('Excepciones');
        $h->setCellValue('A1', 'MODIFICA EXCEPCIONES');
        $h->setCellValue('A2', 'Nómina del ' . $plan['nomina']['fecha'] . ' · ' . $plan['nomina']['tipoNombre'] . ' · ' . $plan['servidor'] . ' · archivo ' . $plan['archivo']);
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $enc = array('Estatus', 'Empleado', 'Nombre');
        foreach ($plan['columnas'] as $c) $enc[] = $c['clave'] . ' ' . $c['nombre'];
        foreach ($enc as $i => $t) $h->setCellValueByColumnAndRow($i, 4, $t);
        $h->getStyle('A4:' . PHPExcel_Cell::stringFromColumnIndex(count($enc) - 1) . '4')->getFont()->setBold(true);
        $colores = array('U' => 'C8FFC8', 'I' => 'C8E1FF', '=' => 'DDDDDD');
        $estados = array('M' => 'Con excepción', 'I' => 'Sin excepción (ignorado)', 'X' => 'No existe en Microsip');
        $fila = 5;
        foreach ($plan['filas'] as $f) {
            $h->setCellValueByColumnAndRow(0, $fila, $estados[$f['estado']]);
            $h->setCellValueByColumnAndRow(1, $fila, $f['numero']);
            $h->setCellValueByColumnAndRow(2, $fila, $f['nombre']);
            foreach ($plan['columnas'] as $j => $c) {
                $celda = isset($f['celdas'][$c['id']]) ? $f['celdas'][$c['id']] : null;
                if (!$celda || !$celda['nuevo']) continue;
                $h->setCellValueByColumnAndRow(3 + $j, $fila, $celda['nuevo']);
                if (isset($colores[$celda['accion']])) {
                    $h->getStyleByColumnAndRow(3 + $j, $fila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($colores[$celda['accion']]);
                }
                if ($celda['accion'] === 'U') $h->getCommentByColumnAndRow(3 + $j, $fila)->getText()->createTextRun('Antes: ' . $celda['actual']);
            }
            $fila++;
        }
        $h->getStyle('D5:' . PHPExcel_Cell::stringFromColumnIndex(count($enc) - 1) . max(5, $fila - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $h->setCellValue('A' . ($fila + 1), 'Verde = cambia la cuota · Azul = se agrega el concepto · Gris = ya tenía ese valor');
        for ($i = 0; $i < count($enc); $i++) $h->getColumnDimensionByColumn($i)->setAutoSize(true);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="excepciones_' . $plan['servidor'] . '_' . $plan['nomina']['fecha'] . '.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
    }

    // ------------------------------------------------------------------ privados

    /** Nómina por id: id, fecha, tipo, tipoNombre, aplicado. array() si no existe, false si no respondió. */
    private function nomina($srv, $id){
        $f = $this->openquery($srv, 'SELECT NOMINA_ID, FECHA, TIPO_NOM, APLICADO FROM NOMINAS WHERE NOMINA_ID = ' . (int) $id);
        if ($f === false) return false;
        if (!$f) return array();
        $t = trim($f[0]['TIPO_NOM']);
        return array('id' => (int) $f[0]['NOMINA_ID'], 'fecha' => $this->ymd($f[0]['FECHA']), 'tipo' => $t,
                     'tipoNombre' => isset($this->tipos[$t]) ? $this->tipos[$t] : $t, 'aplicado' => trim($f[0]['APLICADO']) === 'S');
    }

    /** Excepciones de la nómina: numero => {excep_emp_id, nombre, det: concepto_no_id => {det_id, cuota, activo}}. */
    private function excepciones($srv, $nominaId){
        $filas = $this->openquery($srv,
            'SELECT t.NUMERO, t.NOMBRE_COMPLETO, e.EXCEP_EMP_ID, d.EXCEP_EMP_DET_ID, d.CONCEPTO_NO_ID, d.CUOTA, d.ACTIVO
             FROM EXCEP_EMPLEADOS e
             JOIN EMPLEADOS t ON t.EMPLEADO_ID = e.EMPLEADO_ID
             LEFT JOIN EXCEP_EMPLEADOS_DET d ON d.EXCEP_EMP_ID = e.EXCEP_EMP_ID
             WHERE e.NOMINA_ID = ' . (int) $nominaId . '
             ORDER BY d.EXCEP_EMP_DET_ID');
        if ($filas === false) return false;
        $exc = array();
        foreach ($filas as $f) {
            $n = (int) $f['NUMERO'];
            if (!isset($exc[$n])) $exc[$n] = array('excep_emp_id' => (int) $f['EXCEP_EMP_ID'], 'nombre' => trim($f['NOMBRE_COMPLETO']), 'det' => array());
            // como el programa viejo: si el concepto se repite, manda el primero
            if ($f['EXCEP_EMP_DET_ID'] !== null && !isset($exc[$n]['det'][(int) $f['CONCEPTO_NO_ID']])) {
                $exc[$n]['det'][(int) $f['CONCEPTO_NO_ID']] = array('det_id' => (int) $f['EXCEP_EMP_DET_ID'], 'cuota' => (float) $f['CUOTA'], 'activo' => trim($f['ACTIVO']));
            }
        }
        return $exc;
    }

    /** Nombres de empleados de Microsip por número (de 400 en 400 para no pasar el límite de OPENQUERY). */
    private function nombres($srv, $numeros){
        $out = array();
        foreach (array_chunk(array_map('intval', $numeros), 400) as $grupo) {
            $f = $this->openquery($srv, 'SELECT NUMERO, NOMBRE_COMPLETO FROM EMPLEADOS WHERE NUMERO IN (' . implode(',', $grupo) . ')');
            if ($f) foreach ($f as $r) $out[(int) $r['NUMERO']] = trim($r['NOMBRE_COMPLETO']);
        }
        return $out;
    }

    /** Servidor vinculado de la empresa si está activa y el usuario la puede ver; null si no. */
    private function servidor($empresa){
        if ($empresa <= 0) return null;
        $r = $this->db->query("SELECT servidor FROM dbo.MicrosipEmpresa WHERE empresa_id = ? AND activo = 1", array($empresa))->row_array();
        if (!$r) return null;
        if (!is_cli()) {
            $this->load->model('PermisoModel');
            $ok = $this->PermisoModel->filtrarPlantas($this->idUsuario(), array(array('id' => $empresa)));
            if (!$ok) return null;
        }
        return preg_match('/^[A-Za-z0-9_]+$/', $r['servidor']) ? $r['servidor'] : null;
    }

    /** SELECT * FROM OPENQUERY(servidor, consulta Firebird). false si falla. */
    private function openquery($servidor, $consulta){
        $sql = "SELECT * FROM OPENQUERY([" . $servidor . "], '" . str_replace("'", "''", $consulta) . "')";
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $q = $this->db->query($sql);
        $this->db->db_debug = $debug;
        return $q ? $q->result_array() : false;
    }

    private function rutaPlan($token){
        return rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'modexc_' . $token . '.json';
    }

    private function idUsuario(){
        return is_cli() ? 0 : (int) $this->session->userdata['login']['user']->id;
    }

    private function may($s){
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $s)), 'UTF-8');
    }

    private function ymd($v){
        if ($v instanceof DateTime) return $v->format('Y-m-d');
        return substr((string) $v, 0, 10);
    }

    /** Lo usa el administrador o quien tenga la opción en sus permisos. */
    private function puedeVer(){
        if (is_cli()) return true;
        if (!isset($this->session->userdata['login']['user'])) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.activo = 1 AND LOWER(o.url) = 'modificaexcepciones'",
                              array((int) $u->id))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
