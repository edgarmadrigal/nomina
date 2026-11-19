<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class incidenciaEmpresa extends CI_Controller {

	public function construct(){
		$this->load->model('Incidencias');
		header('Access-Control-Allow-Origin: *');
		parent::__construct();
		if(isset($this->session->userdata['login'])){
			$logged = true;
		}
		else{
			$logged = false;
		}
	}
	
	public function index(){
		header('Access-Control-Allow-Origin: *');
				$data = []; 
				
		$this->load->model('Incidencias');
				if (isset($this->session->userdata['login'])) {
						$data['user'] = $this->session->userdata['login']['user'];
						
						if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Incidencias_EmpresaView', $data);
				$this->load->view('footer', $data);
						} else {
								$this->load->view('login');
						}
				} else {
						$this->load->view('login');
		}		
	}


	
	public function consultaIncidenciasEmpresa(){
		header('Access-Control-Allow-Origin: *');
		$result = array('data' => array());
		$id = $this->input->post('id',true);
		$this->load->model('IncidenciasEmpresa');
		$data = $this->IncidenciasEmpresa->consultaIncidencias($id);
		echo json_encode($data);
	}


	public function ActualizarIncidencia(){
		header('Access-Control-Allow-Origin: *');
				$id= $this->input->post('id',true);
				
		$fechaInicio = new DateTime($this->input->post('fechaInicio',true));
		$fechaFin = new DateTime($this->input->post('fechaFin',true));
		$interval = $fechaInicio->diff($fechaFin);
		$cantidadDias = $interval->days+1;
		//DIE();
				$data = array(
				'concepto_id' 	=> $this->input->post('concepto_id',true),
				'cantidadDias' 	=> $cantidadDias,
				'fechaInicio' 	=> $this->input->post('fechaInicio',true),
				'fechaFin'		=> $this->input->post('fechaFin',true)
			) + $this->cubre();
				$this->load->model('IncidenciasEmpresa');
				header('Content-Type: application/json');
				$m = $this->IncidenciasEmpresa->maestro($id);
				if (!$m || !$this->puedePlanta($m['empresa_id'])) { echo json_encode(['exito' => false, 'mensaje' => 'No se encontró la incidencia.']); return; }
				$r = $this->IncidenciasEmpresa->ActualizarIncidencia($id, $data, $this->empleadosElegidos(), $this->usuarioId());
				echo json_encode($r === false ? ['exito' => false, 'mensaje' => 'No se pudo guardar.']
					: ['exito' => true, 'mensaje' => 'Se actualizó la incidencia' . ($r['agregados'] ? '; se agregó a ' . $r['agregados'] . ' empleado(s)' : '') . ($r['quitados'] ? '; se quitó a ' . $r['quitados'] . ' empleado(s)' : '') . '.']);
	}
	public function GuardarIncidencia(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('IncidenciasEmpresa');

		//calcular dias de INCIDENCIA
		$fechaInicio = new DateTime($this->input->post('fechaInicio',true));
		$fechaFin = new DateTime($this->input->post('fechaFin',true));
		$interval = $fechaInicio->diff($fechaFin);
		$cantidadDias = $interval->days+1;
		//ECHO $interval->days;
		//DIE();
		$data =  array(
			'concepto_id' 	=> $this->input->post('concepto_id',true),
			'fechaInicio' 	=> $this->input->post('fechaInicio',true),			
			'cantidadDias' 	=> $cantidadDias,
			'fechaFin'		=> $this->input->post('fechaFin',true),
			'empresa_id'	=> $this->input->post('empresa_id',true),
			'estatus'		=> $this->input->post('estatus',true)
		) + $this->cubre();

		header('Content-Type: application/json');
		if (!$this->puedePlanta($data['empresa_id'])) { echo json_encode(['exito' => false, 'mensaje' => 'No tienes acceso a esa planta.']); return; }
		$empleados = $this->empleadosElegidos();
		if ($empleados !== null && !$empleados) { echo json_encode(['exito' => false, 'mensaje' => 'Elige al menos un empleado.']); return; }
		$copias = $this->IncidenciasEmpresa->GuardarIncidencia($data, $empleados);
		echo json_encode($copias === false ? ['exito' => false, 'mensaje' => 'No se pudo guardar.']
			: ['exito' => true, 'mensaje' => 'Se agregó la incidencia a ' . number_format($copias) . ' empleado(s).']);
	}	
	public function ActualizarHorariosEmpleado(){
		header('Access-Control-Allow-Origin: *');
		$id= $this->input->post('id',true);
		$data = array(
		'idhorario_lunes' 		=> $this->input->post('idhorarioLunes',true),
		'idhorario_martes' 		=> $this->input->post('idhorarioMartes',true),
		'idhorario_miercoles' => $this->input->post('idhorarioMiercoles',true),
		'idhorario_jueves'		=> $this->input->post('idhorarioJueves',true),
		'idhorario_viernes'		=> $this->input->post('idhorarioViernes',true),
		'idhorario_sabado'		=> $this->input->post('idhorarioSabado',true),
		'idhorario_domingo'		=> $this->input->post('idhorarioDomingo',true),
		'descripcion'					=> $this->input->post('descripcion',true),				
		'fechaAsignacion'			=> $this->input->post('fechaAsignacion',true),
		);
		$this->load->model('HorarioUsuario');
		//print_r($params);die();
		echo  $this->HorarioUsuario->ActualizarHorariosEmpleado($id,$data);
	}
	
	
    /** Empleados activos de la planta, para elegir a quién se aplica. */
    public function empleadosPlanta(){
		header('Content-Type: application/json');
		$empresa = (int) $this->input->post('empresa_id');
		if (!$this->puedePlanta($empresa)) { echo '[]'; return; }
		$this->load->model('IncidenciasEmpresa');
		echo json_encode($this->IncidenciasEmpresa->empleadosPlanta($empresa));
    }

    /** Quiénes tienen la incidencia de empresa. */
    public function empleadosIncidencia(){
		header('Content-Type: application/json');
		$this->load->model('IncidenciasEmpresa');
		$m = $this->IncidenciasEmpresa->maestro((int) $this->input->post('id'));
		if (!$m || !$this->puedePlanta($m['empresa_id'])) { echo '[]'; return; }
		echo json_encode($this->IncidenciasEmpresa->empleadosIncidencia($m['id']));
    }

    /** Quita la incidencia de empresa a un solo empleado. */
    public function quitarEmpleado(){
		header('Content-Type: application/json');
		$this->load->model('IncidenciasEmpresa');
		$m = $this->IncidenciasEmpresa->maestro((int) $this->input->post('id'));
		if (!$m || !$this->puedePlanta($m['empresa_id'])) { echo json_encode(['exito' => false]); return; }
		echo json_encode(['exito' => $this->IncidenciasEmpresa->QuitarEmpleado($m['id'], (int) $this->input->post('copia_id'), $this->usuarioId())]);
    }

    /** modo=elegir + empleados="1,2,3" => esos números; si no, null = todos los activos de la planta. */
    private function empleadosElegidos(){
		if ($this->input->post('modo') !== 'elegir') return null;
		return array_values(array_filter(array_map('intval', explode(',', (string) $this->input->post('empleados')))));
    }

    private function usuarioId(){
		return isset($this->session->userdata['login']['user']->id) ? $this->session->userdata['login']['user']->id : null;
    }

    /** La planta está entre las empresas permitidas del usuario (sin empresas asignadas = todas). */
    private function puedePlanta($empresa){
		$u = $this->usuarioId();
		if (!$u || !(int) $empresa) return false;
		$this->load->model('PermisoModel');
		$p = $this->PermisoModel->permisosUsuario($u);
		return empty($p['empresas']) || in_array((int) $empresa, $p['empresas'], true);
    }

    /** Qué checadas cubre (ver incidencia.php); sin datos o sin ninguna marcada = todo el día. */
    private function cubre(){
		$r = array();
		foreach (array('cubreEntrada', 'cubreEntradaComedor', 'cubreSalidaComedor', 'cubreSalida') as $c) {
			$v = $this->input->post($c, true);
			$r[$c] = ($v === null || $v === '') ? 1 : ((int) $v === 1 ? 1 : 0);
		}
		if (array_sum($r) === 0) $r = array_fill_keys(array_keys($r), 1);
		return $r;
    }

    function delete() {
		header('Access-Control-Allow-Origin: *');

		header('Content-Type: application/json');
		if (!isset($this->session->userdata['login']['user'])) {
			echo json_encode(['exito' => false, 'mensaje' => 'La sesión terminó; vuelve a entrar.']);
			return;
		}
		$id_user = $this->session->userdata['login']['user']->id;
		$id = (int) $this->input->post('id');
		// baja de la incidencia de la empresa y de sus copias por empleado
		$this->load->model('IncidenciasEmpresa');
		$copias = $this->IncidenciasEmpresa->EliminarIncidenciaEmpresa($id, $id_user);
		if ($copias === false) {
			echo json_encode(['exito' => false, 'mensaje' => 'No se encontró la incidencia o ya estaba eliminada.']);
			return;
		}
		echo json_encode(['exito' => true, 'copias' => $copias,
			'mensaje' => 'Se eliminó la incidencia de la empresa y de ' . number_format($copias) . ' empleado(s).']);
	}
}