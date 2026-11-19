<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Prenómina (Fase 1): calcula por planta y semana el sueldo, premios, pago de tiempo extra
 * y descuentos por falta/retardo de cada empleado ACTIVO, a partir de lo que el sistema ya
 * procesó (dbo.asistenciaBioStar) + el salario diario (dbo.empleados.salarioDiario).
 * El cálculo lo hace dbo.ConsultaPrenomina (application/sql/prenomina.sql); las constantes
 * por planta están en dbo.PrenominaConfig.
 */
class Prenomina extends CI_Controller {

    /** Columnas que devuelve el SP, en orden, con su título (corto) para la pantalla y el Excel. */
    private $columnas = array(
        'Numero' => 'Núm.', 'Nombre' => 'Nombre', 'Puesto' => 'Puesto',
        'SD' => 'SD', 'SueldoFalta' => 'Sueldo', 'Premio' => 'Premio',
        'SueldoAutorizado' => 'Sueldo aut.', 'Retardos' => 'Ret', 'Faltas' => 'Fal',
        'TE_Total' => 'T.E. h', 'PagoTE' => 'Pago T.E.',
        'PrimaVacacional' => 'P.Vac', 'FondoAhorro' => 'F.Ahorro', 'PrimaDominical' => 'P.Dom',
        'Festivo' => 'Festivo', 'Defuncion' => 'Defunc', 'Produccion' => 'Producción', 'BonoCalidad' => 'B.Calidad',
        'HorasRebajadas' => 'Hrs reb', 'MontoRetardos' => 'M.Ret',
        'DescPuntualidad' => 'D.Punt', 'DescAsistencia' => 'D.Asist', 'DescFalta' => 'D.Falta',
        'TotalAPagar' => 'TOTAL', 'PagoSistema' => 'Pago sist.', 'Diferencia' => 'Dif.',
    );

    /**
     * Conceptos que el usuario puede capturar/editar en la pantalla (se guardan en PrenominaCaptura).
     * Prima vacacional, fondo de ahorro, prima dominical, festivo, defunción y pago sistema se traen
     * de Microsip (dbo.PrenominaTraeMicrosip) al Calcular, PERO quedan editables: el usuario puede
     * sobreescribirlos y su edición se respeta (el traído de Microsip solo llena lo que está en 0).
     */
    private $editables = array(
        'TE_Total' => 'teHoras',   // horas de T.E. a mano (vacío = usar las de los relojes)
        'PrimaVacacional' => 'primaVacacional', 'FondoAhorro' => 'fondoAhorro',
        'PrimaDominical' => 'primaDominical', 'Festivo' => 'festivo', 'Defuncion' => 'defuncion',
        'BonoCalidad' => 'bonoCalidad', 'HorasRebajadas' => 'horasRebajadas',
        'PagoSistema' => 'pagoSistema');

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('PrenominaView', $data);
        $this->load->view('footer', $data);
    }

    /** Plantas que el usuario puede ver (con config de prenómina). */
    public function plantas(){
        if (!$this->puedeVer()) { $this->json(array()); return; }
        $filas = $this->db->query(
            "SELECT e.id, e.nombre FROM dbo.empresas e WHERE e.id IN (SELECT empresa_id FROM dbo.PrenominaConfig WHERE activo = 1) ORDER BY e.nombre")->result_array();
        $this->load->model('PermisoModel');
        $filas = $this->PermisoModel->filtrarPlantas((int) $this->session->userdata['login']['user']->id, $filas);
        $this->json($filas);
    }

    /** Calcula la prenómina (JSON) de una planta/semana. */
    public function calcular(){
        ini_set('max_execution_time', 0);
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso para este reporte.')); return; }
        $planta = (int) $this->input->post('planta');
        $semana = (int) $this->input->post('semana');
        $anio   = (int) $this->input->post('anio');
        if (!$planta || !$this->puedePlanta($planta)) { $this->json(array('exito' => false, 'mensaje' => 'Elige una planta válida.')); return; }
        if ($semana < 1 || $semana > 53) { $this->json(array('exito' => false, 'mensaje' => 'Semana inválida.')); return; }
        if ($anio < 2012 || $anio > 2100) $anio = (int) date('Y');

        // Trae de Microsip prima vacacional / fondo ahorro / prima dominical / festivo / defunción
        // de esa semana (si la nómina ya se corrió en Microsip). Si falla o aún no está, se sigue igual.
        $microsip = null;
        try {
            $r = $this->db->query("EXEC dbo.PrenominaTraeMicrosip @planta = ?, @NoSemana = ?, @anio = ?",
                                  array($planta, $semana, $anio))->row();
            $microsip = $r ? (int) $r->actualizados : null;
        } catch (Exception $e) { $microsip = -1; }

        $filas = $this->db->query("EXEC dbo.ConsultaPrenomina @planta = ?, @NoSemana = ?, @anio = ?",
                                  array($planta, $semana, $anio))->result_array();
        foreach ($filas as &$f) {   // Pago T.E. = suma del pago L-V + sábado + domingo
            $f['PagoTE'] = number_format((float) $f['PagoTE_LV'] + (float) $f['PagoTE_Sab'] + (float) $f['PagoTE_Dom'], 2, '.', '');
        }
        unset($f);
        $this->json(array('exito' => true, 'columnas' => $this->columnas, 'editables' => $this->editables, 'filas' => $filas,
                          'microsip' => $microsip,   // -1 sin Microsip, 0 nómina pendiente, >0 empleados traídos
                          'sinProcesar' => count(array_filter($filas, function ($f) { return (int) $f['Procesado'] === 0; }))));
    }

    /** Guarda un concepto capturado de un empleado en una semana (upsert en PrenominaCaptura). */
    public function guardarCaptura(){
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'Sin permiso.')); return; }
        $planta = (int) $this->input->post('planta');
        $semana = (int) $this->input->post('semana');
        $anio   = (int) $this->input->post('anio');
        $emp    = trim((string) $this->input->post('empleado'));
        $campo  = (string) $this->input->post('campo');
        $raw    = $this->input->post('monto');
        // teHoras vacío = quitar el override (NULL -> vuelve a las horas de los relojes)
        $monto  = ($campo === 'teHoras' && ($raw === '' || $raw === null)) ? null : (float) $raw;
        if (!$planta || !$this->puedePlanta($planta)) { $this->json(array('exito' => false, 'mensaje' => 'Planta inválida.')); return; }
        if ($emp === '' || !in_array($campo, $this->editables, true)) { $this->json(array('exito' => false, 'mensaje' => 'Dato inválido.')); return; }
        // upsert
        $existe = $this->db->query("SELECT COUNT(*) n FROM dbo.PrenominaCaptura WHERE empresa_id=? AND anio=? AND NoSemana=? AND empleado_id=?",
                                  array($planta, $anio, $semana, $emp))->row()->n;
        if ($existe) {
            $this->db->query("UPDATE dbo.PrenominaCaptura SET [$campo]=? WHERE empresa_id=? AND anio=? AND NoSemana=? AND empleado_id=?",
                             array($monto, $planta, $anio, $semana, $emp));
        } else {
            $this->db->query("INSERT INTO dbo.PrenominaCaptura (empresa_id,anio,NoSemana,empleado_id,[$campo]) VALUES (?,?,?,?,?)",
                             array($planta, $anio, $semana, $emp, $monto));
        }
        // devuelve el total recalculado de ese empleado
        $filas = $this->db->query("EXEC dbo.ConsultaPrenomina @planta = ?, @NoSemana = ?, @anio = ?", array($planta, $semana, $anio))->result_array();
        $total = null; $mret = null; $dif = null;
        foreach ($filas as $f) if (trim($f['Numero']) === $emp) { $total = $f['TotalAPagar']; $mret = $f['MontoRetardos']; $dif = $f['Diferencia']; break; }
        $this->json(array('exito' => true, 'total' => $total, 'montoRetardos' => $mret, 'diferencia' => $dif));
    }

    /** Constantes de la planta (para el modal de configuración). */
    public function config(){
        if (!$this->puedeVer()) { $this->json(array('exito' => false)); return; }
        $planta = (int) $this->input->post('planta');
        $r = $this->db->query("SELECT * FROM dbo.PrenominaConfig WHERE empresa_id = ?", array($planta))->row_array();
        $this->json(array('exito' => true, 'config' => $r));
    }

    /** Guarda las constantes de la planta (solo administrador). */
    public function guardarConfig(){
        if (!$this->esAdmin()) { $this->json(array('exito' => false, 'mensaje' => 'Solo el administrador cambia las constantes.')); return; }
        $planta = (int) $this->input->post('planta');
        if (!$planta) { $this->json(array('exito' => false, 'mensaje' => 'Falta la planta.')); return; }
        $d = array(
            'premio'       => (float) $this->input->post('premio'),
            'factorFalta'  => (float) $this->input->post('factorFalta'),
            'tarifaTE_LV'  => (float) $this->input->post('tarifaTE_LV'),
            'tarifaTE_Sab' => (float) $this->input->post('tarifaTE_Sab'),
            'tarifaTE_Dom' => (float) $this->input->post('tarifaTE_Dom'),
            'diasSemana'   => (int)   $this->input->post('diasSemana'),
            'topeTEDoble'  => (float) $this->input->post('topeTEDoble'),
        );
        $existe = $this->db->query("SELECT COUNT(*) n FROM dbo.PrenominaConfig WHERE empresa_id = ?", array($planta))->row()->n;
        if ($existe) $this->db->where('empresa_id', $planta)->update('PrenominaConfig', $d);
        else { $d['empresa_id'] = $planta; $this->db->insert('PrenominaConfig', $d); }
        $this->json(array('exito' => true, 'mensaje' => 'Constantes guardadas.'));
    }

    /**
     * Importa el PAGO TOTAL GENERAL de producción desde el Excel de la planta (PRODUCCION.xlsx,
     * hoja REPORTES): número de empleado en la columna A y el monto = "PAGO TOTAL GENERAL" (col EA,
     * = día + T.E. + bono; se autodetecta por el encabezado que contiene 'GENERAL'). Se guarda en
     * PrenominaCaptura.produccionGeneral; la producción que se paga = produccionGeneral - total base
     * (lo calcula ConsultaPrenomina). Se puede forzar la columna con el parámetro 'columna'.
     */
    public function importarProduccion(){
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'Sin permiso.')); return; }
        @ini_set('memory_limit', '512M'); @ini_set('max_execution_time', 0);
        $planta = (int) $this->input->post('planta');
        $semana = (int) $this->input->post('semana');
        $anio   = (int) $this->input->post('anio');
        if (!$planta || !$this->puedePlanta($planta) || $semana < 1 || $semana > 53) { $this->json(array('exito' => false, 'mensaje' => 'Parámetros inválidos.')); return; }
        if (empty($_FILES['archivo']['tmp_name']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) { $this->json(array('exito' => false, 'mensaje' => 'No llegó el archivo.')); return; }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('xlsx', 'xls'), true)) { $this->json(array('exito' => false, 'mensaje' => 'Debe ser un Excel (.xlsx).')); return; }

        require_once APPPATH . "/third_party/PHPExcel.php";
        try {
            $libro = PHPExcel_IOFactory::load($_FILES['archivo']['tmp_name']);
        } catch (Exception $e) { $this->json(array('exito' => false, 'mensaje' => 'No se pudo leer el Excel.')); return; }
        $hoja = $libro->sheetNameExists('REPORTES') ? $libro->getSheetByName('REPORTES') : $libro->getSheet(0);
        $maxCol = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn()) - 1;   // 0-based
        $maxRow = $hoja->getHighestRow();

        // Columna del monto: la forzada, o "PAGO TOTAL GENERAL" (col EA, encabezado con 'GENERAL'
        // en las primeras filas). Es el pago total de producción = día + T.E. + bono.
        $forzada = strtoupper(trim((string) $this->input->post('columna')));
        $colGen = $forzada !== '' ? PHPExcel_Cell::columnIndexFromString($forzada) - 1 : -1;
        if ($colGen < 0) {
            for ($c = 0; $c <= $maxCol && $colGen < 0; $c++) {
                for ($r = 1; $r <= 5; $r++) {
                    if (stripos((string) $hoja->getCellByColumnAndRow($c, $r)->getValue(), 'GENERAL') !== false) { $colGen = $c; break; }
                }
            }
        }
        if ($colGen < 0) { $this->json(array('exito' => false, 'mensaje' => 'No encontré la columna "PAGO TOTAL GENERAL". Indícame la letra de la columna.')); return; }

        // Recorrer filas: col A numérica (> 1000) = empleado; su pago total general -> produccionGeneral
        $datos = array();
        for ($r = 1; $r <= $maxRow; $r++) {
            $celdaA = $hoja->getCellByColumnAndRow(0, $r);
            $a = $celdaA->getValue();
            if (!is_numeric($a) || $a < 1000) continue;
            if (PHPExcel_Shared_Date::isDateTime($celdaA)) continue;   // una fecha (serial ~46000), no un empleado
            $emp = (string) (int) $a;
            $gen = $hoja->getCellByColumnAndRow($colGen, $r)->getValue();
            $datos[$emp] = is_numeric($gen) ? round((float) $gen, 2) : 0;
        }
        if (!$datos) { $this->json(array('exito' => false, 'mensaje' => 'No encontré empleados en el Excel.')); return; }

        // Upsert de cada empleado
        $n = 0;
        foreach ($datos as $emp => $monto) {
            $existe = $this->db->query("SELECT COUNT(*) c FROM dbo.PrenominaCaptura WHERE empresa_id=? AND anio=? AND NoSemana=? AND empleado_id=?",
                                      array($planta, $anio, $semana, $emp))->row()->c;
            if ($existe) $this->db->query("UPDATE dbo.PrenominaCaptura SET produccionGeneral=? WHERE empresa_id=? AND anio=? AND NoSemana=? AND empleado_id=?",
                                          array($monto, $planta, $anio, $semana, $emp));
            else $this->db->query("INSERT INTO dbo.PrenominaCaptura (empresa_id,anio,NoSemana,empleado_id,produccionGeneral) VALUES (?,?,?,?,?)",
                                  array($planta, $anio, $semana, $emp, $monto));
            $n++;
        }
        $this->json(array('exito' => true, 'mensaje' => 'Se importó el pago total general de producción de ' . $n . ' empleado(s) (columna ' . PHPExcel_Cell::stringFromColumnIndex($colGen) . ').', 'importados' => $n));
    }

    /** Descarga la prenómina en Excel (una hoja, todas las columnas). */
    public function excel(){
        if (!$this->puedeVer()) { show_error('No tienes permiso.', 403); return; }
        ini_set('max_execution_time', 0);
        $planta = (int) $this->input->get('planta');
        $semana = (int) $this->input->get('semana');
        $anio   = (int) $this->input->get('anio');
        if (!$planta || !$this->puedePlanta($planta) || $semana < 1 || $semana > 53) { show_error('Parámetros inválidos.', 400); return; }
        if ($anio < 2012 || $anio > 2100) $anio = (int) date('Y');
        $filas = $this->db->query("EXEC dbo.ConsultaPrenomina @planta = ?, @NoSemana = ?, @anio = ?",
                                  array($planta, $semana, $anio))->result_array();
        foreach ($filas as &$f) { $f['PagoTE'] = (float) $f['PagoTE_LV'] + (float) $f['PagoTE_Sab'] + (float) $f['PagoTE_Dom']; }
        unset($f);
        $nom = $this->db->query("SELECT nombre FROM dbo.empresas WHERE id = ?", array($planta))->row();

        require_once APPPATH . "/third_party/PHPExcel.php";
        $xls = new PHPExcel();
        $hoja = $xls->getActiveSheet();
        $hoja->setTitle('PRENOMINA');
        $col = 0;
        foreach ($this->columnas as $k => $titulo) { $hoja->setCellValueByColumnAndRow($col++, 1, $titulo); }
        $r = 2;
        foreach ($filas as $fila) {
            $col = 0;
            foreach ($this->columnas as $k => $titulo) {
                $v = isset($fila[$k]) ? $fila[$k] : '';
                if (!in_array($k, array('Numero', 'Nombre', 'Puesto'), true) && $v !== '' && is_numeric($v)) $v = (float) $v;
                $hoja->setCellValueByColumnAndRow($col++, $r, $v);
            }
            $r++;
        }
        $hoja->freezePaneByColumnAndRow(0, 2);
        if (ob_get_length() !== false) @ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Prenomina_' . preg_replace('/[^A-Za-z0-9_]/', '_', ($nom ? $nom->nombre : $planta)) . '_S' . $semana . '_' . $anio . '.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
        exit;
    }

    // ------------------------------------------------------------------

    private function puedePlanta($planta){
        if (!$planta) return false;
        $this->load->model('PermisoModel');
        $p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
        return empty($p['empresas']) || in_array((int) $planta, $p['empresas'], true);
    }

    private function puedeVer(){
        if (!isset($this->session->userdata['login']['user'])) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.activo = 1 AND LOWER(o.url) = 'prenomina'",
                              array((int) $u->id))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    private function esAdmin(){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        return $u && isset($u->idPerfil) && $u->idPerfil == 1;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
