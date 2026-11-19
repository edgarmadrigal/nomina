<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reportes > Nómina consolidada Microsip.
 * Reemplaza al programa "Reportes Consolidados de Nóminas" (TECNOMART\REPORTES NOMINA\NOMINAS.exe):
 * junta los pagos de nómina ya calculados en Microsip de VARIAS empresas, sin "levantar" nada, leyendo
 * directo con OPENQUERY al servidor vinculado de cada empresa (dbo.MicrosipEmpresa).
 *  - concentrada: una fila por empleado (empresa > departamento) con una columna por concepto
 *  - costo:       totales por empresa y departamento (costo total)
 * Las columnas salen de los conceptos que de verdad se pagaron; Microsip marca cada concepto como
 * percepción (NATURALEZA = 'P') o deducción ('R'); neto = percepciones - deducciones.
 * Solo lectura: no modifica nada en Microsip.
 */
class nominaConsolidada extends CI_Controller {

    private $tipos = array('N' => 'Ordinaria', 'E' => 'Extraordinaria', 'A' => 'Aguinaldo', 'L' => 'Liquidación', 'R' => 'Reparto de utilidades');

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('nominaConsolidadaView', $data);
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

    /** Datos del reporte en JSON. */
    public function datos(){
        ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');
        $this->json($this->consultar($this->parametros($this->input->post())));
    }

    /** Excel: hoja "Concentrada" (por empleado) y hoja "Costo total" (por empresa y departamento). */
    public function excel(){
        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '1024M');
        $r = $this->consultar($this->parametros($this->input->get()));
        if (!$r['exito']) { show_error($r['mensaje'], 400); return; }

        require_once APPPATH . '/third_party/PHPExcel.php';
        $xls = new PHPExcel();
        $subtitulo = 'Nóminas del ' . $r['fechaInicio'] . ' al ' . $r['fechaFin'] . '  ·  ' . $r['tipoNombre'] . ($r['soloAplicadas'] ? '  ·  solo aplicadas' : '');
        $conceptos = $r['conceptos'];

        // ---- Hoja 1: concentrada por empleado
        $h = $xls->getActiveSheet();
        $h->setTitle('Concentrada');
        $h->setCellValue('A1', 'NÓMINA CONCENTRADA');
        $h->setCellValue('A2', $subtitulo);
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $enc = array('Empresa', 'Grupo de pago', 'Departamento', 'Centro de costo', 'Tipo', 'Número', 'Nombre', 'Fecha nómina', 'Aplicada');
        $fijas = count($enc);
        foreach ($conceptos as $c) $enc[] = $c['clave'] . ' ' . $c['nombre'];
        $enc[] = 'Total percepciones'; $enc[] = 'Total deducciones'; $enc[] = 'Neto';
        foreach ($enc as $i => $t) $h->setCellValueByColumnAndRow($i, 4, $t);
        $ultima = PHPExcel_Cell::stringFromColumnIndex(count($enc) - 1);
        $h->getStyle('A4:' . $ultima . '4')->getFont()->setBold(true);
        $h->getStyle('A4:' . $ultima . '4')->getAlignment()->setWrapText(true);
        $fila = 5;
        foreach ($r['empleados'] as $e) {
            $c = 0;
            $h->setCellValueByColumnAndRow($c++, $fila, $e['empresa']);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['grupo']);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['depto']);
            $h->setCellValueExplicitByColumnAndRow($c++, $fila, (string) $e['cc'], PHPExcel_Cell_DataType::TYPE_STRING);
            $h->setCellValueByColumnAndRow($c++, $fila, $this->nombreClasif($e['clasif']));
            $h->setCellValueExplicitByColumnAndRow($c++, $fila, (string) $e['numero'], PHPExcel_Cell_DataType::TYPE_STRING);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['nombre']);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['fecha']);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['aplicado'] ? 'Sí' : 'No');
            foreach ($conceptos as $k => $cc) {
                $v = isset($e['imp'][$k]) ? $e['imp'][$k] : null;
                if ($v !== null) $h->setCellValueByColumnAndRow($c, $fila, $v);
                $c++;
            }
            $h->setCellValueByColumnAndRow($c++, $fila, $e['per']);
            $h->setCellValueByColumnAndRow($c++, $fila, $e['ded']);
            $h->setCellValueByColumnAndRow($c++, $fila, round($e['per'] - $e['ded'], 2));
            $fila++;
        }
        if ($fila > 5) {
            $h->setCellValueByColumnAndRow($fijas - 3, $fila, 'TOTAL (' . count($r['empleados']) . ' empleados)');
            for ($i = $fijas; $i < count($enc); $i++) {
                $col = PHPExcel_Cell::stringFromColumnIndex($i);
                $h->setCellValueByColumnAndRow($i, $fila, '=SUM(' . $col . '5:' . $col . ($fila - 1) . ')');
            }
            $h->getStyle('A' . $fila . ':' . $ultima . $fila)->getFont()->setBold(true);
            $h->getStyle(PHPExcel_Cell::stringFromColumnIndex($fijas) . '5:' . $ultima . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        for ($i = 0; $i < $fijas; $i++) $h->getColumnDimensionByColumn($i)->setAutoSize(true);
        for ($i = $fijas; $i < count($enc); $i++) $h->getColumnDimensionByColumn($i)->setWidth(13);
        $h->getRowDimension(4)->setRowHeight(45);
        $h->freezePane('H5');

        // ---- Hoja 2: costo total por empresa y departamento
        $h2 = $xls->createSheet();
        $h2->setTitle('Costo total');
        $h2->setCellValue('A1', 'COSTO TOTAL DE NÓMINA');
        $h2->setCellValue('A2', $subtitulo);
        $h2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $enc2 = array('Empresa', 'Departamento', 'Empleados');
        foreach ($conceptos as $c) $enc2[] = $c['clave'] . ' ' . $c['nombre'];
        $enc2[] = 'Total percepciones'; $enc2[] = 'Total deducciones'; $enc2[] = 'Neto';
        foreach ($enc2 as $i => $t) $h2->setCellValueByColumnAndRow($i, 4, $t);
        $ultima2 = PHPExcel_Cell::stringFromColumnIndex(count($enc2) - 1);
        $h2->getStyle('A4:' . $ultima2 . '4')->getFont()->setBold(true);
        $h2->getStyle('A4:' . $ultima2 . '4')->getAlignment()->setWrapText(true);
        $fila = 5;
        $filasEmpresa = array();
        foreach ($this->resumen($r['empleados'], $conceptos) as $emp => $deptos) {
            $ini = $fila;
            foreach ($deptos as $depto => $t) {
                $this->filaCosto($h2, $fila++, $emp, $depto, $t, $conceptos, false);
            }
            // subtotal de la empresa
            $h2->setCellValueByColumnAndRow(0, $fila, $emp);
            $h2->setCellValueByColumnAndRow(1, $fila, 'TOTAL ' . $emp);
            for ($i = 2; $i < count($enc2); $i++) {
                $col = PHPExcel_Cell::stringFromColumnIndex($i);
                $h2->setCellValueByColumnAndRow($i, $fila, '=SUM(' . $col . $ini . ':' . $col . ($fila - 1) . ')');
            }
            $h2->getStyle('A' . $fila . ':' . $ultima2 . $fila)->getFont()->setBold(true);
            $h2->getStyle('A' . $fila . ':' . $ultima2 . $fila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('EEEEEE');
            $filasEmpresa[] = $fila;
            $fila += 2;
        }
        if ($filasEmpresa) {
            $h2->setCellValueByColumnAndRow(1, $fila, 'TOTAL GENERAL');
            for ($i = 2; $i < count($enc2); $i++) {
                $col = PHPExcel_Cell::stringFromColumnIndex($i);
                $h2->setCellValueByColumnAndRow($i, $fila, '=' . implode('+', array_map(function ($f) use ($col) { return $col . $f; }, $filasEmpresa)));
            }
            $h2->getStyle('A' . $fila . ':' . $ultima2 . $fila)->getFont()->setBold(true)->setSize(12);
            $h2->getStyle('D5:' . $ultima2 . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        for ($i = 0; $i < 3; $i++) $h2->getColumnDimensionByColumn($i)->setAutoSize(true);
        for ($i = 3; $i < count($enc2); $i++) $h2->getColumnDimensionByColumn($i)->setWidth(13);
        $h2->getRowDimension(4)->setRowHeight(45);
        $h2->freezePane('C5');

        // ---- Hoja 3: Directos, Indirectos y Administrativos (por empresa y en total)
        $h3 = $xls->createSheet();
        $h3->setTitle('D, I y A');
        $h3->setCellValue('A1', 'REPORTE DIRECTOS, INDIRECTOS Y ADMINISTRATIVOS');
        $h3->setCellValue('A2', $subtitulo);
        $h3->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $enc3 = array('Empresa', 'Tipo', 'Empleados');
        foreach ($conceptos as $c) $enc3[] = $c['clave'] . ' ' . $c['nombre'];
        $enc3[] = 'Total percepciones'; $enc3[] = 'Total deducciones'; $enc3[] = 'Neto';
        foreach ($enc3 as $i => $t) $h3->setCellValueByColumnAndRow($i, 4, $t);
        $ultima3 = PHPExcel_Cell::stringFromColumnIndex(count($enc3) - 1);
        $h3->getStyle('A4:' . $ultima3 . '4')->getFont()->setBold(true);
        $h3->getStyle('A4:' . $ultima3 . '4')->getAlignment()->setWrapText(true);
        $porTipo = array(); $general = array();
        foreach ($r['empleados'] as $e) {
            $t = $this->nombreClasif($e['clasif']);
            if (!isset($porTipo[$e['empresa']][$t])) $porTipo[$e['empresa']][$t] = null;
            if (!isset($general[$t])) $general[$t] = null;
            $this->sumar($porTipo[$e['empresa']][$t], $e);
            $this->sumar($general[$t], $e);
        }
        $orden = array('Directo', 'Indirecto', 'Administrativo', 'Sin clasificar');
        $fila = 5;
        ksort($porTipo);
        foreach ($porTipo + array('TOTAL GENERAL' => $general) as $emp => $tipos) {
            foreach ($orden as $t) {
                if (empty($tipos[$t])) continue;
                $this->filaCosto($h3, $fila, $emp, $t, $tipos[$t], $conceptos, false);
                if ($emp === 'TOTAL GENERAL') $h3->getStyle('A' . $fila . ':' . $ultima3 . $fila)->getFont()->setBold(true);
                $fila++;
            }
            $fila++;
        }
        $h3->getStyle('D5:' . $ultima3 . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        for ($i = 0; $i < 3; $i++) $h3->getColumnDimensionByColumn($i)->setAutoSize(true);
        for ($i = 3; $i < count($enc3); $i++) $h3->getColumnDimensionByColumn($i)->setWidth(13);
        $h3->getRowDimension(4)->setRowHeight(45);
        $h3->freezePane('C5');

        // ---- Hoja 4: relación de retenciones (deducciones por empresa y concepto)
        $h4 = $xls->createSheet();
        $h4->setTitle('Retenciones');
        $h4->setCellValue('A1', 'RELACIÓN DE PAGOS DE RETENCIONES');
        $h4->setCellValue('A2', $subtitulo);
        $h4->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        foreach (array('Empresa', 'Clave', 'Concepto', 'Tipo SAT', 'Empleados', 'Importe') as $i => $t) $h4->setCellValueByColumnAndRow($i, 4, $t);
        $h4->getStyle('A4:F4')->getFont()->setBold(true);
        $fila = 5;
        $ret = $this->retenciones($r['empleados'], $conceptos);
        foreach ($ret['porEmpresa'] + array('TODAS LAS EMPRESAS' => $ret['general']) as $emp => $lista) {
            $ini = $fila;
            foreach ($lista as $k => $t) {
                $c = $conceptos[$k];
                $h4->setCellValueByColumnAndRow(0, $fila, $emp);
                $h4->setCellValueExplicitByColumnAndRow(1, $fila, $c['clave'], PHPExcel_Cell_DataType::TYPE_STRING);
                $h4->setCellValueByColumnAndRow(2, $fila, $c['nombre']);
                $h4->setCellValueByColumnAndRow(3, $fila, $this->nombreSat($c['sat']));
                $h4->setCellValueByColumnAndRow(4, $fila, $t['n']);
                $h4->setCellValueByColumnAndRow(5, $fila, round($t['importe'], 2));
                $fila++;
            }
            $h4->setCellValueByColumnAndRow(2, $fila, 'TOTAL ' . $emp);
            $h4->setCellValueByColumnAndRow(5, $fila, '=SUM(F' . $ini . ':F' . ($fila - 1) . ')');
            $h4->getStyle('A' . $fila . ':F' . $fila)->getFont()->setBold(true);
            $fila += 2;
        }
        $h4->getStyle('F5:F' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'F') as $col) $h4->getColumnDimension($col)->setAutoSize(true);
        $h4->freezePane('A5');

        // ---- Hoja 5: pensiones alimenticias
        $h5 = $xls->createSheet();
        $h5->setTitle('Pensiones');
        $h5->setCellValue('A1', 'RELACIÓN DE PENSIONES ALIMENTICIAS');
        $h5->setCellValue('A2', $subtitulo);
        $h5->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        foreach (array('Empresa', 'Número', 'Empleado', 'Beneficiario', 'Fecha nómina', 'Concepto', 'Importe') as $i => $t) $h5->setCellValueByColumnAndRow($i, 4, $t);
        $h5->getStyle('A4:G4')->getFont()->setBold(true);
        $fila = 5;
        foreach ($this->pensiones($r['empleados'], $conceptos) as $pp) {
            $h5->setCellValueByColumnAndRow(0, $fila, $pp['empresa']);
            $h5->setCellValueExplicitByColumnAndRow(1, $fila, $pp['numero'], PHPExcel_Cell_DataType::TYPE_STRING);
            $h5->setCellValueByColumnAndRow(2, $fila, $pp['nombre']);
            $h5->setCellValueByColumnAndRow(3, $fila, $pp['beneficiario']);
            $h5->setCellValueByColumnAndRow(4, $fila, $pp['fecha']);
            $h5->setCellValueByColumnAndRow(5, $fila, $pp['concepto']);
            $h5->setCellValueByColumnAndRow(6, $fila, $pp['importe']);
            $fila++;
        }
        $h5->setCellValueByColumnAndRow(5, $fila, 'TOTAL');
        $h5->setCellValueByColumnAndRow(6, $fila, $fila > 5 ? '=SUM(G5:G' . ($fila - 1) . ')' : 0);
        $h5->getStyle('A' . $fila . ':G' . $fila)->getFont()->setBold(true);
        $h5->getStyle('G5:G' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'G') as $col) $h5->getColumnDimension($col)->setAutoSize(true);

        $xls->setActiveSheetIndex(0);
        if (ob_get_length() !== false) @ob_clean();
        $nombre = 'Nomina_consolidada_' . $r['fechaInicio'] . '_' . $r['fechaFin'];
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombre . '.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
        exit;
    }

    /**
     * Guarda la clasificación (D/I/A) de un departamento de Microsip. tipo vacío = quitarla.
     * La usa la vista "Costo total" (un selector por departamento).
     */
    public function clasificar(){
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso.')); return; }
        $empresa = (int) $this->input->post('empresa_id');
        $depto = trim((string) $this->input->post('depto'));
        $tipo = strtoupper(trim((string) $this->input->post('tipo')));
        if (!$empresa || $depto === '' || mb_strlen($depto, 'UTF-8') > 100) { $this->json(array('exito' => false, 'mensaje' => 'Departamento no válido.')); return; }
        if ($tipo !== '' && !in_array($tipo, array('D', 'I', 'A'), true)) { $this->json(array('exito' => false, 'mensaje' => 'Tipo no válido.')); return; }
        $u = $this->session->userdata['login']['user'];
        if ($tipo === '') {
            $this->db->query("DELETE FROM dbo.NominaDeptoClasificacion WHERE empresa_id = ? AND depto = ?", array($empresa, $depto));
        } else {
            $this->db->query(
                "MERGE dbo.NominaDeptoClasificacion AS t
                 USING (SELECT ? AS empresa_id, ? AS depto) AS s ON t.empresa_id = s.empresa_id AND t.depto = s.depto
                 WHEN MATCHED THEN UPDATE SET tipo = ?, idUsuario = ?, fecha = GETDATE()
                 WHEN NOT MATCHED THEN INSERT (empresa_id, depto, tipo, idUsuario) VALUES (?, ?, ?, ?);",
                array($empresa, $depto, $tipo, (int) $u->id, $empresa, $depto, $tipo, (int) $u->id));
        }
        $this->json(array('exito' => true));
    }

    /**
     * Movimientos afiliatorios del IMSS registrados en Microsip (tabla INCIDENCIAS): altas (I),
     * reingresos (R), bajas (B) y modificaciones de salario (S), de varias empresas, por fecha.
     * Sustituye a los reportes de incidencias del programa viejo (por registro patronal / grupo / tipo).
     */
    public function movimientos(){
        ini_set('max_execution_time', 0);
        $this->json($this->consultarMovimientos($this->parametros($this->input->post()), $this->input->post('tiposMov')));
    }

    public function movimientosExcel(){
        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');
        $r = $this->consultarMovimientos($this->parametros($this->input->get()), $this->input->get('tiposMov'));
        if (!$r['exito']) { show_error($r['mensaje'], 400); return; }
        require_once APPPATH . '/third_party/PHPExcel.php';
        $xls = new PHPExcel();
        $h = $xls->getActiveSheet();
        $h->setTitle('Movimientos IMSS');
        $h->setCellValue('A1', 'MOVIMIENTOS AFILIATORIOS IMSS');
        $h->setCellValue('A2', 'Del ' . $r['fechaInicio'] . ' al ' . $r['fechaFin']);
        $h->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $cols = array('empresa' => 'Empresa', 'grupo' => 'Grupo de pago', 'regPatronal' => 'Registro patronal', 'regDesc' => 'Reg. patronal (lugar)',
                      'tipoNombre' => 'Tipo', 'fecha' => 'Fecha', 'numero' => 'Número', 'nombre' => 'Nombre', 'nss' => 'NSS', 'curp' => 'CURP',
                      'salDiario' => 'Sal. diario', 'salHora' => 'Sal. hora', 'salInteg' => 'Sal. integrado', 'causaBaja' => 'Causa baja');
        $c = 0;
        foreach ($cols as $t) $h->setCellValueByColumnAndRow($c++, 4, $t);
        $h->getStyle('A4:N4')->getFont()->setBold(true);
        $fila = 5;
        foreach ($r['filas'] as $f) {
            $c = 0;
            foreach ($cols as $k => $t) {
                if (in_array($k, array('numero', 'nss', 'regPatronal'), true)) $h->setCellValueExplicitByColumnAndRow($c, $fila, (string) $f[$k], PHPExcel_Cell_DataType::TYPE_STRING);
                else $h->setCellValueByColumnAndRow($c, $fila, $f[$k]);
                $c++;
            }
            $fila++;
        }
        $h->setCellValue('A' . $fila, 'Total de movimientos: ' . count($r['filas']));
        $h->getStyle('A' . $fila)->getFont()->setBold(true);
        $h->getStyle('K5:M' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
        for ($i = 0; $i < count($cols); $i++) $h->getColumnDimensionByColumn($i)->setAutoSize(true);
        $h->freezePane('A5');
        if (ob_get_length() !== false) @ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Movimientos_IMSS_' . $r['fechaInicio'] . '_' . $r['fechaFin'] . '.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
        exit;
    }

    // ------------------------------------------------------------------

    private $tiposMov = array('I' => 'Alta', 'R' => 'Reingreso', 'B' => 'Baja', 'S' => 'Modificación de salario');

    private function consultarMovimientos($p, $tipos){
        if (!$this->puedeVer()) return array('exito' => false, 'mensaje' => 'No tienes permiso para este reporte.');
        if (!$p['empresas']) return array('exito' => false, 'mensaje' => 'Elige al menos una empresa.');
        if (!$p['fi'] || !$p['ff']) return array('exito' => false, 'mensaje' => 'Elige el rango de fechas.');
        if ($p['fi'] > $p['ff']) { $tmp = $p['fi']; $p['fi'] = $p['ff']; $p['ff'] = $tmp; }
        if (!is_array($tipos)) $tipos = explode(',', (string) $tipos);
        $tipos = array_values(array_intersect(array_map('strtoupper', array_map('trim', $tipos)), array_keys($this->tiposMov)));
        if (!$tipos) $tipos = array_keys($this->tiposMov);

        $this->load->model('PermisoModel');
        $permitidas = $this->empresasPermitidas();
        $lista = $this->db->query(
            "SELECT e.id, e.nombre, me.servidor FROM dbo.MicrosipEmpresa me JOIN dbo.empresas e ON e.id = me.empresa_id
             WHERE me.activo = 1 AND e.id IN (" . implode(',', $p['empresas']) . ") ORDER BY e.nombre")->result_array();
        $q = "SELECT i.TIPO, i.FECHA, e.NUMERO, e.NOMBRE_COMPLETO, e.REG_IMSS, e.CURP, i.SALARIO_DIARIO, i.SALARIO_HORA, i.SALARIO_INTEG,
                     i.CAUSA_BAJA, r.NUM_REG_PATRONAL, r.DESCRIPCION AS REG_DESC
              FROM INCIDENCIAS i
              JOIN EMPLEADOS e ON e.EMPLEADO_ID = i.EMPLEADO_ID
              LEFT JOIN REG_PATRONALES r ON r.REG_PATRONAL_ID = i.REG_PATRONAL_ID
              WHERE i.FECHA BETWEEN '" . $p['fi'] . "' AND '" . $p['ff'] . "'
                AND i.TIPO IN ('" . implode("','", $tipos) . "')";
        $filas = array(); $errores = array(); $consultadas = array();
        foreach ($lista as $emp) {
            if ($permitidas !== null && !in_array((int) $emp['id'], $permitidas, true)) continue;
            if (!preg_match('/^[A-Za-z0-9_]+$/', $emp['servidor'])) continue;
            $res = $this->openquery($emp['servidor'], $q);
            if ($res === false) { $errores[] = $emp['nombre']; continue; }
            $consultadas[] = $emp['nombre'];
            $grupo = $this->grupoPago($emp['servidor']);
            foreach ($res as $f) {
                $tipo = trim((string) $f['TIPO']);
                $filas[] = array(
                    'empresa' => $emp['nombre'], 'grupo' => $grupo,
                    'regPatronal' => trim((string) $f['NUM_REG_PATRONAL']), 'regDesc' => trim((string) $f['REG_DESC']),
                    'tipo' => $tipo, 'tipoNombre' => isset($this->tiposMov[$tipo]) ? $this->tiposMov[$tipo] : $tipo,
                    'fecha' => $f['FECHA'] instanceof DateTime ? $f['FECHA']->format('Y-m-d') : substr((string) $f['FECHA'], 0, 10),
                    'numero' => trim((string) $f['NUMERO']), 'nombre' => trim((string) $f['NOMBRE_COMPLETO']),
                    'nss' => trim((string) $f['REG_IMSS']), 'curp' => trim((string) $f['CURP']),
                    'salDiario' => round((float) $f['SALARIO_DIARIO'], 2), 'salHora' => round((float) $f['SALARIO_HORA'], 2),
                    'salInteg' => round((float) $f['SALARIO_INTEG'], 2), 'causaBaja' => trim((string) $f['CAUSA_BAJA']),
                );
            }
        }
        if (!$consultadas && $errores) return array('exito' => false, 'mensaje' => 'Microsip no respondió en: ' . implode(', ', $errores) . '.');
        usort($filas, function ($a, $b) {
            return strcmp($a['regPatronal'], $b['regPatronal']) ?: strcmp($a['empresa'], $b['empresa']) ?: strcmp($a['tipo'], $b['tipo'])
                ?: strcmp($a['fecha'], $b['fecha']) ?: ((int) $a['numero'] - (int) $b['numero']);
        });
        return array('exito' => true, 'fechaInicio' => $p['fi'], 'fechaFin' => $p['ff'], 'filas' => $filas,
                     'consultadas' => $consultadas, 'errores' => $errores);
    }

    /** Clasificaciones guardadas: 'empresa_id|DEPTO' => D/I/A. */
    private function clasificaciones(){
        $m = array();
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $q = $this->db->query("SELECT empresa_id, depto, tipo FROM dbo.NominaDeptoClasificacion");
        $this->db->db_debug = $debug;
        if ($q) foreach ($q->result_array() as $r) $m[(int) $r['empresa_id'] . '|' . mb_strtoupper(trim($r['depto']), 'UTF-8')] = $r['tipo'];
        return $m;
    }

    private function nombreClasif($t){
        $n = array('D' => 'Directo', 'I' => 'Indirecto', 'A' => 'Administrativo');
        return isset($n[$t]) ? $n[$t] : 'Sin clasificar';
    }

    /** Grupo de pago de la empresa en Microsip (DATOS_EMPRESA.OTRO_REG2, p. ej. "AE1"). */
    private $gruposCache = array();
    private function grupoPago($servidor){
        if (!isset($this->gruposCache[$servidor])) {
            $r = $this->openquery($servidor, 'SELECT OTRO_REG2 FROM DATOS_EMPRESA');
            $this->gruposCache[$servidor] = ($r && isset($r[0]['OTRO_REG2'])) ? trim((string) $r[0]['OTRO_REG2']) : '';
        }
        return $this->gruposCache[$servidor];
    }

    /** Deducciones por empresa y concepto: ['porEmpresa' => [emp => [k => n, importe]], 'general' => [k => ...]]. */
    private function retenciones($empleados, $conceptos){
        $por = array(); $gen = array();
        foreach ($empleados as $e) {
            foreach ($e['imp'] as $k => $v) {
                if (!$v || !isset($conceptos[$k]) || $conceptos[$k]['nat'] !== 'R') continue;
                foreach (array('por', 'gen') as $dest) {
                    if ($dest === 'por') { $t =& $por[$e['empresa']][$k]; } else { $t =& $gen[$k]; }
                    if (!$t) $t = array('n' => 0, 'importe' => 0);
                    $t['n']++; $t['importe'] += $v;
                    unset($t);
                }
            }
        }
        $orden = function ($a, $b) use ($conceptos) { return strnatcasecmp($conceptos[$a]['clave'], $conceptos[$b]['clave']); };
        ksort($por);
        foreach ($por as &$l) uksort($l, $orden);
        unset($l);
        uksort($gen, $orden);
        return array('porEmpresa' => $por, 'general' => $gen);
    }

    /** Descuentos de pensión alimenticia (deducción con tipo SAT 007) por empleado. */
    private function pensiones($empleados, $conceptos){
        $res = array();
        foreach ($empleados as $e) {
            foreach ($e['imp'] as $k => $v) {
                if (!$v || !isset($conceptos[$k]) || $conceptos[$k]['nat'] !== 'R' || (int) $conceptos[$k]['sat'] !== 7) continue;
                $res[] = array('empresa' => $e['empresa'], 'numero' => $e['numero'], 'nombre' => $e['nombre'], 'beneficiario' => $e['pension'],
                               'fecha' => $e['fecha'], 'concepto' => $conceptos[$k]['clave'] . ' ' . $conceptos[$k]['nombre'], 'importe' => round($v, 2));
            }
        }
        return $res;
    }

    /** Nombre del tipo de deducción del catálogo del SAT (c_TipoDeduccion). */
    private function nombreSat($n){
        $cat = array(1 => '001 Seguridad social', 2 => '002 ISR', 3 => '003 Aportaciones a retiro', 4 => '004 Otros', 5 => '005 Aportaciones a Fondo de vivienda',
                     6 => '006 Descuento por incapacidad', 7 => '007 Pensión alimenticia', 8 => '008 Renta', 9 => '009 Préstamos Fondo Nacional de Vivienda',
                     10 => '010 Pago por crédito de vivienda', 11 => '011 Pago de abonos INFONACOT', 12 => '012 Anticipo de salarios',
                     13 => '013 Pagos hechos con exceso al trabajador', 14 => '014 Errores', 15 => '015 Pérdidas', 16 => '016 Averías',
                     17 => '017 Adquisición de artículos de la empresa', 18 => '018 Cuotas para cajas de ahorro', 19 => '019 Cuotas sindicales',
                     20 => '020 Ausencia (Ausentismo)', 21 => '021 Cuotas obrero patronales', 22 => '022 Impuestos locales', 23 => '023 Aportaciones voluntarias');
        return isset($cat[(int) $n]) ? $cat[(int) $n] : ($n ? sprintf('%03d', $n) : '');
    }

    private function sumar(&$acc, $e){
        if (!$acc) $acc = array('n' => 0, 'imp' => array(), 'per' => 0, 'ded' => 0);
        $acc['n']++;
        foreach ($e['imp'] as $k => $v) $acc['imp'][$k] = (isset($acc['imp'][$k]) ? $acc['imp'][$k] : 0) + $v;
        $acc['per'] += $e['per'];
        $acc['ded'] += $e['ded'];
    }

    private function filaCosto($h, $fila, $emp, $depto, $t, $conceptos, $negrita){
        $c = 0;
        $h->setCellValueByColumnAndRow($c++, $fila, $emp);
        $h->setCellValueByColumnAndRow($c++, $fila, $depto);
        $h->setCellValueByColumnAndRow($c++, $fila, $t['n']);
        foreach ($conceptos as $k => $cc) {
            if (!empty($t['imp'][$k])) $h->setCellValueByColumnAndRow($c, $fila, round($t['imp'][$k], 2));
            $c++;
        }
        $h->setCellValueByColumnAndRow($c++, $fila, round($t['per'], 2));
        $h->setCellValueByColumnAndRow($c++, $fila, round($t['ded'], 2));
        $h->setCellValueByColumnAndRow($c++, $fila, round($t['per'] - $t['ded'], 2));
    }

    /** Totales por empresa > departamento. */
    private function resumen($empleados, $conceptos){
        $res = array();
        foreach ($empleados as $e) {
            $emp = $e['empresa']; $dep = $e['depto'] !== '' ? $e['depto'] : '(sin departamento)';
            if (!isset($res[$emp][$dep])) $res[$emp][$dep] = array('n' => 0, 'imp' => array(), 'per' => 0, 'ded' => 0);
            $t =& $res[$emp][$dep];
            $t['n']++;
            foreach ($e['imp'] as $k => $v) $t['imp'][$k] = (isset($t['imp'][$k]) ? $t['imp'][$k] : 0) + $v;
            $t['per'] += $e['per'];
            $t['ded'] += $e['ded'];
            unset($t);
        }
        foreach ($res as &$d) ksort($d);
        unset($d);
        return $res;
    }

    /** Normaliza los parámetros (POST o GET). */
    private function parametros($in){
        $empresas = isset($in['empresas']) ? $in['empresas'] : array();
        if (!is_array($empresas)) $empresas = explode(',', (string) $empresas);
        $empresas = array_values(array_unique(array_filter(array_map('intval', $empresas))));
        $tipo = isset($in['tipoNomina']) ? strtoupper(trim((string) $in['tipoNomina'])) : 'N';
        return array(
            'empresas' => $empresas,
            'fi' => $this->fecha(isset($in['fechaInicio']) ? $in['fechaInicio'] : ''),
            'ff' => $this->fecha(isset($in['fechaFin']) ? $in['fechaFin'] : ''),
            'tipo' => ($tipo === 'T' || isset($this->tipos[$tipo])) ? $tipo : 'N',
            'soloAplicadas' => !empty($in['soloAplicadas']) && $in['soloAplicadas'] !== '0' && $in['soloAplicadas'] !== 'false',
        );
    }

    /**
     * Corre la consulta en el Microsip de cada empresa y arma:
     * conceptos (clave => nombre, naturaleza; percepciones primero), empleados (con importes por concepto),
     * errores por empresa (si alguna no respondió, las demás sí salen).
     */
    private function consultar($p){
        if (!$this->puedeVer()) return array('exito' => false, 'mensaje' => 'No tienes permiso para este reporte.');
        if (!$p['empresas']) return array('exito' => false, 'mensaje' => 'Elige al menos una empresa.');
        if (!$p['fi'] || !$p['ff']) return array('exito' => false, 'mensaje' => 'Elige el rango de fechas de la nómina.');
        if ($p['fi'] > $p['ff']) { $tmp = $p['fi']; $p['fi'] = $p['ff']; $p['ff'] = $tmp; }

        $this->load->model('PermisoModel');
        $permitidas = $this->empresasPermitidas();
        $lista = $this->db->query(
            "SELECT e.id, e.nombre, me.servidor FROM dbo.MicrosipEmpresa me JOIN dbo.empresas e ON e.id = me.empresa_id
             WHERE me.activo = 1 AND e.id IN (" . implode(',', $p['empresas']) . ") ORDER BY e.nombre")->result_array();

        // CLAVES_CAT_SEC del departamento = centro de costo (como en el programa viejo)
        $q = "SELECT n.FECHA, n.TIPO_NOM, e.NUMERO, e.NOMBRE_COMPLETO, d.NOMBRE AS DEPTO, k.CLAVE AS CC, p.APLICADO,
                     TRIM(c.CLAVE) AS CLAVE, c.NOMBRE AS CONCEPTO, c.NATURALEZA, c.TIPO_SAT, SUM(pd.IMPORTE) AS IMPORTE
              FROM PAGOS_NOMINA p
              JOIN NOMINAS n ON n.NOMINA_ID = p.NOMINA_ID
              JOIN PAGOS_NOMINA_DET pd ON pd.PAGO_NOMINA_ID = p.PAGO_NOMINA_ID
              JOIN CONCEPTOS_NO c ON c.CONCEPTO_NO_ID = pd.CONCEPTO_NO_ID
              JOIN EMPLEADOS e ON e.EMPLEADO_ID = p.EMPLEADO_ID
              LEFT JOIN DEPTOS_NO d ON d.DEPTO_NO_ID = p.DEPTO_NO_ID
              LEFT JOIN CLAVES_CAT_SEC k ON k.ELEM_ID = p.DEPTO_NO_ID AND k.NOMBRE_TABLA = 'DEPTOS_NO'
              WHERE n.FECHA BETWEEN '" . $p['fi'] . "' AND '" . $p['ff'] . "'"
           . ($p['tipo'] !== 'T' ? " AND n.TIPO_NOM = '" . $p['tipo'] . "'" : "")
           . ($p['soloAplicadas'] ? " AND p.APLICADO = 'S'" : "") . "
              GROUP BY n.FECHA, n.TIPO_NOM, e.NUMERO, e.NOMBRE_COMPLETO, d.NOMBRE, k.CLAVE, p.APLICADO, c.CLAVE, c.NOMBRE, c.NATURALEZA, c.TIPO_SAT";
        // Beneficiario de la pensión alimenticia (campo libre del empleado en Microsip)
        $qPension = "SELECT e.NUMERO, l.PENSION_ALIMENTICIA FROM LIBRES_EMPLEADOS l JOIN EMPLEADOS e ON e.EMPLEADO_ID = l.EMPLEADO_ID
                     WHERE l.PENSION_ALIMENTICIA IS NOT NULL AND l.PENSION_ALIMENTICIA <> ''";
        $clasif = $this->clasificaciones();

        $conceptos = array();   // 'P|008' => [clave, nombre, nat]
        $empleados = array();   // empresa|numero|fecha => fila
        $errores = array();
        $consultadas = array();
        foreach ($lista as $emp) {
            if ($permitidas !== null && !in_array((int) $emp['id'], $permitidas, true)) continue;
            if (!preg_match('/^[A-Za-z0-9_]+$/', $emp['servidor'])) continue;
            $filas = $this->openquery($emp['servidor'], $q);
            if ($filas === false) { $errores[] = $emp['nombre']; continue; }
            $consultadas[] = $emp['nombre'];
            $grupo = $this->grupoPago($emp['servidor']);
            $pensiones = array();
            $resP = $this->openquery($emp['servidor'], $qPension);
            if ($resP) foreach ($resP as $pp) $pensiones[trim((string) $pp['NUMERO'])] = trim((string) $pp['PENSION_ALIMENTICIA']);
            foreach ($filas as $f) {
                $nat = trim((string) $f['NATURALEZA']) === 'R' ? 'R' : 'P';
                $clave = trim((string) $f['CLAVE']);
                $k = $nat . '|' . $clave;
                if (!isset($conceptos[$k])) $conceptos[$k] = array('clave' => $clave, 'nombre' => trim((string) $f['CONCEPTO']), 'nat' => $nat,
                                                                   'sat' => (int) $f['TIPO_SAT'],
                                                                   'satNombre' => $nat === 'R' ? $this->nombreSat($f['TIPO_SAT']) : '');
                $fecha = $f['FECHA'] instanceof DateTime ? $f['FECHA']->format('Y-m-d') : substr((string) $f['FECHA'], 0, 10);
                $ek = $emp['nombre'] . '|' . trim((string) $f['NUMERO']) . '|' . $fecha . '|' . $f['TIPO_NOM'];
                if (!isset($empleados[$ek])) {
                    $depto = trim((string) $f['DEPTO']);
                    $ck = (int) $emp['id'] . '|' . mb_strtoupper($depto, 'UTF-8');
                    $empleados[$ek] = array('empresa' => $emp['nombre'], 'empresa_id' => (int) $emp['id'], 'grupo' => $grupo,
                                            'depto' => $depto, 'cc' => trim((string) $f['CC']), 'clasif' => isset($clasif[$ck]) ? $clasif[$ck] : '',
                                            'pension' => isset($pensiones[trim((string) $f['NUMERO'])]) ? $pensiones[trim((string) $f['NUMERO'])] : '',
                                            'numero' => trim((string) $f['NUMERO']),
                                            'nombre' => trim((string) $f['NOMBRE_COMPLETO']), 'fecha' => $fecha, 'tipo' => trim((string) $f['TIPO_NOM']),
                                            'aplicado' => trim((string) $f['APLICADO']) === 'S', 'imp' => array(), 'per' => 0.0, 'ded' => 0.0);
                }
                $imp = round((float) $f['IMPORTE'], 2);
                $empleados[$ek]['imp'][$k] = (isset($empleados[$ek]['imp'][$k]) ? $empleados[$ek]['imp'][$k] : 0) + $imp;
                if ($nat === 'P') $empleados[$ek]['per'] += $imp; else $empleados[$ek]['ded'] += $imp;
            }
        }
        if (!$consultadas && $errores) return array('exito' => false, 'mensaje' => 'Microsip no respondió en: ' . implode(', ', $errores) . '.');

        // percepciones primero, luego deducciones; dentro, por clave (numérica si se puede)
        uksort($conceptos, function ($a, $b) use ($conceptos) {
            $x = $conceptos[$a]; $y = $conceptos[$b];
            if ($x['nat'] !== $y['nat']) return $x['nat'] === 'P' ? -1 : 1;
            return strnatcasecmp($x['clave'], $y['clave']);
        });
        $empleados = array_values($empleados);
        foreach ($empleados as &$e) { $e['per'] = round($e['per'], 2); $e['ded'] = round($e['ded'], 2); }
        unset($e);
        usort($empleados, function ($a, $b) {
            return strcmp($a['empresa'], $b['empresa']) ?: strcmp($a['depto'], $b['depto']) ?: ((int) $a['numero'] - (int) $b['numero']) ?: strcmp($a['fecha'], $b['fecha']);
        });

        return array('exito' => true, 'fechaInicio' => $p['fi'], 'fechaFin' => $p['ff'], 'tipo' => $p['tipo'],
                     'tipoNombre' => $p['tipo'] === 'T' ? 'Todos los tipos' : $this->tipos[$p['tipo']], 'soloAplicadas' => $p['soloAplicadas'],
                     'conceptos' => $conceptos, 'empleados' => $empleados, 'consultadas' => $consultadas, 'errores' => $errores);
    }

    /** null = todas; si no, ids de empresas que el usuario puede ver. */
    private function empresasPermitidas(){
        if (is_cli()) return null;
        $p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
        return empty($p['empresas']) ? null : $p['empresas'];
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

    /** Ve el reporte el administrador o quien tenga la opción en sus permisos. */
    private function puedeVer(){
        if (is_cli()) return true;
        if (!isset($this->session->userdata['login']['user'])) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.activo = 1 AND LOWER(o.url) = 'nominaconsolidada'",
                              array((int) $u->id))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
