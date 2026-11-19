<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reportes > Acumulado de Conceptos Microsip.
 * Suma de importes y unidades de la nómina de Microsip (PAGOS_NOMINA_DET) por concepto,
 * en un rango de fechas, por empresa o de todas combinadas por clave.
 * El acumulado lo calcula dbo.ReporteAcumuladoConceptosMicrosip (application/sql/reporte_acumulado_conceptos.sql).
 */
class reporteConceptosMicrosip extends CI_Controller {

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('reporteConceptosMicrosipView', $data);
        $this->load->view('footer', $data);
    }

    /** Empresas con Microsip que el usuario puede ver. */
    public function empresas(){
        header('Content-Type: application/json');
        if (!$this->puedeVer()) { echo '[]'; return; }
        $filas = $this->db->query(
            "SELECT e.id, e.nombre FROM dbo.MicrosipEmpresa me JOIN dbo.empresas e ON e.id = me.empresa_id
             WHERE me.activo = 1 ORDER BY e.nombre")->result_array();
        $this->load->model('PermisoModel');
        $filas = $this->PermisoModel->filtrarPlantas((int) $this->session->userdata['login']['user']->id, $filas);
        $this->json($filas);
    }

    /**
     * Acumulado de conceptos en un rango de fechas.
     * empresa_id vacío = todas las empresas que el usuario puede ver (combinadas por clave).
     */
    public function acumulado(){
        header('Content-Type: application/json');
        ini_set('max_execution_time', 0);
        if (!$this->puedeVer()) { $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso para este reporte.')); return; }
        $empresa = (int) $this->input->post('empresa_id');
        $fi = $this->fecha($this->input->post('fechaInicio'));
        $ff = $this->fecha($this->input->post('fechaFin'));
        if (!$fi || !$ff) { $this->json(array('exito' => false, 'mensaje' => 'Elige un rango de fechas válido.')); return; }
        if ($fi > $ff) { $tmp = $fi; $fi = $ff; $ff = $tmp; }

        if ($empresa) {
            if (!$this->puedePlanta($empresa)) { $this->json(array('exito' => false, 'mensaje' => 'No tienes acceso a esa empresa.')); return; }
            $filas = $this->acumuladoEmpresa($empresa, $fi, $ff);
        } else {
            $this->load->model('PermisoModel');
            $permitidas = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
            if (empty($permitidas['empresas'])) {
                $filas = $this->acumuladoEmpresa(null, $fi, $ff);     // sin restricción: todas de un jalón
            } else {
                $combinado = array();                                 // restringido: solo sus empresas, por clave
                foreach ($permitidas['empresas'] as $e) {
                    foreach ($this->acumuladoEmpresa((int) $e, $fi, $ff) as $r) {
                        $k = $r['clave'];
                        if (!isset($combinado[$k])) { $combinado[$k] = $r; $combinado[$k]['empresas'] = 1; }
                        else {
                            $combinado[$k]['unidades']    += $r['unidades'];
                            $combinado[$k]['movimientos'] += $r['movimientos'];
                            $combinado[$k]['importe']     += $r['importe'];
                            $combinado[$k]['empresas']    += 1;
                        }
                    }
                }
                $filas = array_values($combinado);
                usort($filas, function ($a, $b) {
                    $na = $a['naturaleza'] === 'P' ? 0 : ($a['naturaleza'] === 'R' ? 1 : 2);
                    $nb = $b['naturaleza'] === 'P' ? 0 : ($b['naturaleza'] === 'R' ? 1 : 2);
                    return $na !== $nb ? $na - $nb : ($b['importe'] <=> $a['importe']);
                });
            }
        }
        $this->json(array('exito' => true, 'filas' => $filas, 'fechaInicio' => $fi, 'fechaFin' => $ff, 'esTodas' => !$empresa));
    }

    /**
     * Descarga un Excel con UNA HOJA POR EMPRESA (matriz por empleado: conceptos en columnas),
     * del año elegido. Usa el SP dbo.ConsultaConceptosMICROSIP (un result set por empresa).
     * Si el usuario tiene empresas restringidas, solo se incluyen sus empresas.
     */
    public function excelDetalle(){
		if (!$this->puedeVer()) { show_error('No tienes permiso para este reporte.', 403); return; }
		@ini_set('memory_limit', '1024M');
		@ini_set('max_execution_time', 0);
		$anio = (int) ($this->input->get('anio') ?: $this->input->post('anio'));
		if ($anio < 2012 || $anio > 2100) $anio = (int) date('Y');

		// empresas (servidores) permitidas: null = todas
		$this->load->model('PermisoModel');
		$p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
		$permitidos = null;
		if (!empty($p['empresas'])) {
			$ids = implode(',', array_map('intval', $p['empresas']));
			$rows = $this->db->query("SELECT servidor FROM dbo.MicrosipEmpresa WHERE empresa_id IN ($ids)")->result_array();
			$permitidos = array_map('strtoupper', array_column($rows, 'servidor'));
		}

		$stmt = sqlsrv_query($this->db->conn_id, "SET NOCOUNT ON; EXEC dbo.ConsultaConceptosMICROSIP @anio = ?", array($anio));
		if ($stmt === false) { show_error('No se pudo generar el reporte (revisa la conexión con Microsip).', 500); return; }

		require_once APPPATH . "/third_party/PHPExcel.php";
		$xls = new PHPExcel();
		$xls->removeSheetByIndex(0);
		$usadas = array();
		$hojas = 0;
		do {
			$meta = sqlsrv_field_metadata($stmt);
			if (!$meta) continue;
			$filas = array();
			while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) $filas[] = $row;
			if (!$filas) continue;
			$label = isset($filas[0][9]) ? (string) $filas[0][9] : ('Empresa ' . ($hojas + 1));   // col 9 = empresa
			if ($permitidos !== null && !in_array(strtoupper($label), $permitidos, true)) continue;

			$titulo = substr(preg_replace('/[\\/\?\*\[\]:]/', '_', $label), 0, 31);
			$base = $titulo; $n = 1;
			while (isset($usadas[strtolower($titulo)])) { $titulo = substr($base, 0, 28) . '_' . (++$n); }
			$usadas[strtolower($titulo)] = true;

			$hoja = $xls->createSheet();
			$hoja->setTitle($titulo);
			foreach ($meta as $i => $m) $hoja->setCellValueByColumnAndRow($i, 1, $m['Name']);
			$r = 2;
			foreach ($filas as $fila) {
				foreach ($fila as $i => $val) {
					if ($val instanceof DateTime) $val = $val->format('Y-m-d');
					$hoja->setCellValueByColumnAndRow($i, $r, $val);
				}
				$r++;
			}
			$hoja->freezePane('A2');
			$hojas++;
		} while (sqlsrv_next_result($stmt));
		sqlsrv_free_stmt($stmt);

		if ($hojas === 0) { $h = $xls->createSheet(); $h->setTitle('Sin datos'); $h->setCellValue('A1', 'No hay nómina en ' . $anio . ' para tus empresas.'); }
		$xls->setActiveSheetIndex(0);

		if (ob_get_length() !== false) @ob_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="ConceptosMicrosip_' . $anio . '.xlsx"');
		header('Cache-Control: max-age=0');
		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
		$writer->save('php://output');
		exit;
    }

    // ------------------------------------------------------------------

    private function acumuladoEmpresa($empresa, $fi, $ff){
        $r = $this->db->query("EXEC dbo.ReporteAcumuladoConceptosMicrosip @empresa_id = ?, @fechaInicio = ?, @fechaFin = ?",
                              array($empresa, $fi, $ff))->result_array();
        foreach ($r as &$f) {
            $f['unidades'] = (float) $f['unidades']; $f['movimientos'] = (int) $f['movimientos'];
            $f['importe'] = (float) $f['importe']; $f['empresas'] = (int) $f['empresas'];
            $f['clave'] = trim((string) $f['clave']);
        }
        return $r;
    }

    /** Valida y normaliza una fecha YYYY-MM-DD; regresa la fecha o null. */
    private function fecha($v){
        $v = trim((string) $v);
        $d = DateTime::createFromFormat('Y-m-d', $v);
        return ($d && $d->format('Y-m-d') === $v) ? $v : null;
    }

    /** La empresa está entre las que el usuario puede ver (sin empresas asignadas = todas). */
    private function puedePlanta($empresa){
        if (!$empresa) return false;
        $this->load->model('PermisoModel');
        $p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
        return empty($p['empresas']) || in_array((int) $empresa, $p['empresas'], true);
    }

    /** Ve el reporte el administrador o quien tenga la opción en sus permisos (igual que el hook de acceso). */
    private function puedeVer(){
        if (!isset($this->session->userdata['login']['user'])) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.activo = 1 AND LOWER(o.url) = 'reporteconceptosmicrosip'",
                              array((int) $u->id))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
