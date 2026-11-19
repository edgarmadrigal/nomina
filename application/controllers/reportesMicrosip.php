<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reportes > Reportes Microsip: los reportes del Reporteador de Microsip Nómina, leídos directo de
 * Microsip (OPENQUERY al servidor vinculado de la empresa, dbo.MicrosipEmpresa):
 *  - catalogo:  "vCatálogo de empleados 2022_GLORIA" (empleados con pago en el rango; activos o con baja)
 *  - fonacot:   "vFonacot 2019" (conceptos con FONACOT en el nombre, pagos aplicados)
 *  - infonavit: "vInfonavit x Empleado 2019_NUEVO" (claves 423, 424, 2cif, 2civ, pagos aplicados)
 */
class reportesMicrosip extends CI_Controller {

    /** Consultas Firebird; @FI / @FF = fechas del rango (YYYY-MM-DD ya validadas). */
    private $consultas = array(
        'catalogo' => "
SELECT EMD1.NUMERO AS EMD_NUM, EMD1.NOMBRE_COMPLETO AS EMD_NOMBRE,
       (SELECT MAX(INC3.SALARIO_DIARIO) FROM INCIDENCIAS INC3 WHERE INC3.EMPLEADO_ID = EMD1.EMPLEADO_ID
          AND INC3.FECHA = (SELECT MAX(INC4.FECHA) FROM INCIDENCIAS INC4 WHERE INC4.EMPLEADO_ID = INC3.EMPLEADO_ID AND INC4.FECHA <= '@FF' AND INC4.TIPO <> 'B')) AS SAL_DIA,
       (SELECT (MAX(INC3.PCTJE_INTEG) / 100) + 1 FROM INCIDENCIAS INC3 WHERE INC3.EMPLEADO_ID = EMD1.EMPLEADO_ID
          AND INC3.FECHA = (SELECT MAX(INC4.FECHA) FROM INCIDENCIAS INC4 WHERE INC4.EMPLEADO_ID = INC3.EMPLEADO_ID AND INC4.FECHA <= '@FF' AND INC4.TIPO <> 'B')) AS POR_INTEG,
       (SELECT MAX(INC3.SALARIO_INTEG) FROM INCIDENCIAS INC3 WHERE INC3.EMPLEADO_ID = EMD1.EMPLEADO_ID
          AND INC3.FECHA = (SELECT MAX(INC4.FECHA) FROM INCIDENCIAS INC4 WHERE INC4.EMPLEADO_ID = INC3.EMPLEADO_ID AND INC4.FECHA <= '@FF' AND INC4.TIPO <> 'B')) AS SAL_INTEG,
       EMD1.FECHA_INGRESO AS EMD_FING, EMD1.REG_IMSS, EMD1.RFC, EMD1.CURP,
       (SELECT MAX(I.FECHA) FROM INCIDENCIAS I WHERE I.EMPLEADO_ID = EMD1.EMPLEADO_ID AND I.TIPO = 'B' AND I.FECHA BETWEEN '@FI' AND '@FF') AS FBAJA,
       (SELECT MAX(I.FECHA) FROM INCIDENCIAS I WHERE I.EMPLEADO_ID = EMD1.EMPLEADO_ID AND I.TIPO = 'R') AS FREINGRESO,
       (SELECT OTRO_REG2 FROM DATOS_EMPRESA) AS GPO
FROM EMPLEADOS EMD1
WHERE EXISTS (SELECT 1 FROM PAGOS_NOMINA P WHERE P.EMPLEADO_ID = EMD1.EMPLEADO_ID AND P.FECHA BETWEEN '@FI' AND '@FF')
  AND (EMD1.ESTATUS = 'A' OR EXISTS (SELECT 1 FROM INCIDENCIAS I WHERE I.EMPLEADO_ID = EMD1.EMPLEADO_ID AND I.TIPO = 'B'))",

        'fonacot' => "
SELECT DISTINCT DEP.NOMBRE AS DEP_NOMBRE, EMD.NUMERO AS EMD_NUMERO, EMD.NOMBRE_COMPLETO AS EMD_NOM, CON.CLAVE AS CON_CLAVE,
       EMD.RFC, EMD.CURP, EMD.REG_IMSS, CON.NOMBRE AS CON_NOM, PGD.IMPORTE AS PAG_IMPORTE, PGD.FECHA AS PAG_FECHA,
       (SELECT FIRST 1 PE.DESCRIPCION FROM PRESTAMOS_EMP PE WHERE PE.CONCEPTO_NO_ID = CON.CONCEPTO_NO_ID AND PE.EMPLEADO_ID = EMD.EMPLEADO_ID
          AND PE.FECHA BETWEEN '@FI' AND '@FF') AS NUMERO_CREDITO
FROM EMPLEADOS EMD
JOIN PAGOS_NOMINA PGE ON PGE.EMPLEADO_ID = EMD.EMPLEADO_ID
JOIN PAGOS_NOMINA_DET PGD ON PGD.PAGO_NOMINA_ID = PGE.PAGO_NOMINA_ID
JOIN CONCEPTOS_NO CON ON CON.CONCEPTO_NO_ID = PGD.CONCEPTO_NO_ID
JOIN DEPTOS_NO DEP ON DEP.DEPTO_NO_ID = EMD.DEPTO_NO_ID
WHERE CON.NOMBRE LIKE '%FONACOT%' AND PGD.FECHA BETWEEN '@FI' AND '@FF' AND PGE.APLICADO = 'S'",

        'infonavit' => "
SELECT DEP.NOMBRE AS DEP_NOMBRE, EMD.NUMERO AS EMD_NUMERO, EMD.REG_IMSS AS EMD_IMSS, EMD.NOMBRE_COMPLETO AS EMD_NOM,
       (SELECT MAX(INC3.SALARIO_INTEG) FROM INCIDENCIAS INC3 WHERE INC3.EMPLEADO_ID = PGE.EMPLEADO_ID
          AND INC3.FECHA = (SELECT MAX(INC4.FECHA) FROM INCIDENCIAS INC4 WHERE INC4.EMPLEADO_ID = INC3.EMPLEADO_ID AND INC4.FECHA <= '@FF' AND INC4.TIPO <> 'B')) AS EMD_SDINT,
       PGE.DIAS_AUS_IMSS AS FALTAS, CON.NOMBRE AS CON_NOM, CON.CLAVE AS CON_CLAVE, PGD.IMPORTE AS PAG_IMPORTE, PGD.FECHA AS PAG_FECHA, NOM.DIAS_PERIODO
FROM EMPLEADOS EMD
JOIN PAGOS_NOMINA PGE ON PGE.EMPLEADO_ID = EMD.EMPLEADO_ID
JOIN PAGOS_NOMINA_DET PGD ON PGD.PAGO_NOMINA_ID = PGE.PAGO_NOMINA_ID
JOIN CONCEPTOS_NO CON ON CON.CONCEPTO_NO_ID = PGD.CONCEPTO_NO_ID
JOIN DEPTOS_NO DEP ON DEP.DEPTO_NO_ID = EMD.DEPTO_NO_ID
JOIN LIBRES_EMPLEADOS ELI ON ELI.EMPLEADO_ID = EMD.EMPLEADO_ID
JOIN NOMINAS NOM ON NOM.NOMINA_ID = PGE.NOMINA_ID
WHERE CON.CLAVE IN ('423','424','2cif','2civ') AND PGD.FECHA BETWEEN '@FI' AND '@FF' AND PGE.APLICADO = 'S'",
    );

    /** Columnas del Excel por reporte: campo => encabezado. */
    private $columnas = array(
        'catalogo' => array('EMD_NUM' => 'Número', 'EMD_NOMBRE' => 'Nombre', 'GPO' => 'Gpo', 'RFC' => 'RFC', 'REG_IMSS' => 'Num. IMSS',
                            'CURP' => 'CURP', 'SAL_DIA' => 'Sal. Diario', 'SAL_INTEG' => 'Sal. Integrado', 'POR_INTEG' => 'Factor',
                            'FBAJA' => 'Fecha Baja', 'FREINGRESO' => 'Fecha Reingreso', 'EMD_FING' => 'Fecha Antig.'),
        'fonacot' => array('CON_CLAVE' => 'Clave', 'CON_NOM' => 'Concepto', 'EMD_NUMERO' => 'Empleado', 'EMD_NOM' => 'Nombre',
                           'DEP_NOMBRE' => 'Departamento', 'NUMERO_CREDITO' => 'Número Crédito', 'RFC' => 'RFC', 'REG_IMSS' => 'Reg. IMSS',
                           'PAG_IMPORTE' => 'Importe', 'PAG_FECHA' => 'Fecha Pago'),
        'infonavit' => array('EMD_NUMERO' => 'Empleado', 'EMD_NOM' => 'Nombre', 'DEP_NOMBRE' => 'Departamento', 'EMD_IMSS' => 'NSS',
                             'EMD_SDINT' => 'Sal. Integrado', 'CON_CLAVE' => 'Clave', 'CON_NOM' => 'Concepto', 'PAG_IMPORTE' => 'Importe',
                             'FALTAS' => 'Faltas', 'DIAS_PERIODO' => 'Días Per.', 'PAG_FECHA' => 'Fecha Pago'),
    );

    private $titulos = array('catalogo' => 'Catálogo de empleados', 'fonacot' => 'Reporte de Fonacot', 'infonavit' => 'Infonavit x empleado');

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('reportesMicrosipView', $data);
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

    /** Filas del reporte en JSON (la página las agrupa como el reporte de Microsip). */
    public function datos(){
        ini_set('max_execution_time', 0);
        $r = $this->consultar($this->input->post('tipo'), (int) $this->input->post('empresa_id'),
                              $this->input->post('fechaInicio'), $this->input->post('fechaFin'));
        $this->json($r);
    }

    /** El mismo reporte en Excel (una fila por movimiento, con totales). */
    public function excel(){
        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');
        $tipo = $this->input->get('tipo');
        $r = $this->consultar($tipo, (int) $this->input->get('empresa_id'), $this->input->get('fechaInicio'), $this->input->get('fechaFin'));
        if (!$r['exito']) { show_error($r['mensaje'], 400); return; }

        require_once APPPATH . '/third_party/PHPExcel.php';
        $xls = new PHPExcel();
        $hoja = $xls->getActiveSheet();
        $hoja->setTitle(substr($this->titulos[$tipo], 0, 31));
        $hoja->setCellValue('A1', mb_strtoupper($this->titulos[$tipo], 'UTF-8'));
        $hoja->setCellValue('A2', $r['empresa']['nombre'] . '  ·  del ' . $r['fechaInicio'] . ' al ' . $r['fechaFin']);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $cols = $this->columnas[$tipo];
        $c = 0;
        foreach ($cols as $titulo) { $hoja->setCellValueByColumnAndRow($c++, 4, $titulo); }
        $hoja->getStyle('A4:' . PHPExcel_Cell::stringFromColumnIndex(count($cols) - 1) . '4')->getFont()->setBold(true);
        $fila = 5;
        $total = 0;
        foreach ($r['filas'] as $f) {
            $c = 0;
            foreach ($cols as $campo => $titulo) {
                $v = isset($f[$campo]) ? $f[$campo] : null;
                if (in_array($campo, array('EMD_NUM', 'EMD_NUMERO', 'REG_IMSS', 'EMD_IMSS'), true)) {
                    $hoja->setCellValueExplicitByColumnAndRow($c, $fila, (string) $v, PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $hoja->setCellValueByColumnAndRow($c, $fila, $v);
                }
                $c++;
            }
            if (isset($f['PAG_IMPORTE'])) $total += (float) $f['PAG_IMPORTE'];
            $fila++;
        }
        if ($tipo !== 'catalogo') {
            $ci = array_search('PAG_IMPORTE', array_keys($cols), true);
            $hoja->setCellValueByColumnAndRow($ci - 1, $fila, 'TOTAL');
            $hoja->setCellValueByColumnAndRow($ci, $fila, round($total, 2));
            $hoja->getStyle('A' . $fila . ':' . PHPExcel_Cell::stringFromColumnIndex(count($cols) - 1) . $fila)->getFont()->setBold(true);
        } else {
            $hoja->setCellValue('A' . $fila, 'Total de empleados: ' . count($r['filas']));
            $hoja->getStyle('A' . $fila)->getFont()->setBold(true);
        }
        for ($i = 0; $i < count($cols); $i++) $hoja->getColumnDimensionByColumn($i)->setAutoSize(true);
        $hoja->freezePane('A5');

        if (ob_get_length() !== false) @ob_clean();
        $nombre = preg_replace('/[^A-Za-z0-9_]+/', '_', $this->titulos[$tipo] . '_' . $r['empresa']['nombre']) . '_' . $r['fechaInicio'] . '_' . $r['fechaFin'];
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
        exit;
    }

    // ------------------------------------------------------------------

    /** Valida, corre el reporte en Microsip y regresa array(exito, mensaje | empresa, filas, fechas). */
    private function consultar($tipo, $empresa, $fi, $ff){
        if (!$this->puedeVer()) return array('exito' => false, 'mensaje' => 'No tienes permiso para este reporte.');
        if (!isset($this->consultas[$tipo])) return array('exito' => false, 'mensaje' => 'Elige un reporte.');
        if (!$empresa) return array('exito' => false, 'mensaje' => 'Elige una empresa.');
        if (!$this->puedePlanta($empresa)) return array('exito' => false, 'mensaje' => 'No tienes acceso a esa empresa.');
        $fi = $this->fecha($fi); $ff = $this->fecha($ff);
        if (!$fi || !$ff) return array('exito' => false, 'mensaje' => 'Elige un rango de fechas válido.');
        if ($fi > $ff) { $tmp = $fi; $fi = $ff; $ff = $tmp; }

        $srv = $this->db->query("SELECT servidor FROM dbo.MicrosipEmpresa WHERE empresa_id = ? AND activo = 1", array($empresa))->row_array();
        if (!$srv || !preg_match('/^[A-Za-z0-9_]+$/', $srv['servidor'])) return array('exito' => false, 'mensaje' => 'Esa empresa no tiene Microsip ligado.');
        $servidor = $srv['servidor'];

        $datosEmp = $this->openquery($servidor, 'SELECT NOMBRE, OTRO_REG2 FROM DATOS_EMPRESA');
        if ($datosEmp === false) return array('exito' => false, 'mensaje' => 'No se pudo conectar con Microsip de esa empresa.');
        $filas = $this->openquery($servidor, str_replace(array('@FI', '@FF'), array($fi, $ff), $this->consultas[$tipo]));
        if ($filas === false) return array('exito' => false, 'mensaje' => 'Microsip no respondió la consulta; intenta de nuevo.');

        foreach ($filas as &$f) {
            foreach ($f as $k => $v) {
                if ($v instanceof DateTime) $f[$k] = $v->format('Y-m-d');
                elseif (is_string($v)) $f[$k] = trim($v);
            }
            foreach (array('SAL_DIA', 'SAL_INTEG', 'POR_INTEG', 'PAG_IMPORTE', 'EMD_SDINT', 'DIAS_PERIODO') as $n)
                if (isset($f[$n])) $f[$n] = round((float) $f[$n], $n === 'POR_INTEG' ? 5 : 2);
            if (isset($f['FALTAS'])) $f['FALTAS'] = (int) $f['FALTAS'];
        }
        unset($f);
        // orden del reporte de Microsip: por número de empleado y luego por fecha de pago
        $num = isset($filas[0]['EMD_NUM']) ? 'EMD_NUM' : 'EMD_NUMERO';
        usort($filas, function ($a, $b) use ($num) {
            $c = (int) $a[$num] - (int) $b[$num];
            if ($c) return $c;
            $c = strcmp(isset($a['CON_CLAVE']) ? $a['CON_CLAVE'] : '', isset($b['CON_CLAVE']) ? $b['CON_CLAVE'] : '');
            return $c ?: strcmp(isset($a['PAG_FECHA']) ? $a['PAG_FECHA'] : '', isset($b['PAG_FECHA']) ? $b['PAG_FECHA'] : '');
        });

        $e = $datosEmp ? $datosEmp[0] : array();
        return array('exito' => true, 'tipo' => $tipo, 'titulo' => $this->titulos[$tipo], 'fechaInicio' => $fi, 'fechaFin' => $ff,
                     'empresa' => array('nombre' => isset($e['NOMBRE']) ? trim($e['NOMBRE']) : '', 'gpo' => isset($e['OTRO_REG2']) ? trim($e['OTRO_REG2']) : ''),
                     'filas' => $filas);
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

    /** Valida y normaliza una fecha YYYY-MM-DD; regresa la fecha o null. */
    private function fecha($v){
        $v = trim((string) $v);
        $d = DateTime::createFromFormat('Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : null;
    }

    /** La empresa está entre las que el usuario puede ver (sin empresas asignadas = todas). */
    private function puedePlanta($empresa){
        if (is_cli()) return true;
        $this->load->model('PermisoModel');
        $p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
        return empty($p['empresas']) || in_array((int) $empresa, $p['empresas'], true);
    }

    /** Ve el reporte el administrador o quien tenga la opción en sus permisos. */
    private function puedeVer(){
        if (is_cli()) return true;
        if (!isset($this->session->userdata['login']['user'])) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.activo = 1 AND LOWER(o.url) = 'reportesmicrosip'",
                              array((int) $u->id))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
