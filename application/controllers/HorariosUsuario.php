<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HorariosUsuario extends CI_Controller {

		public function construct(){
			$this->load->model('Usuario');
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
			$this->load->model('HorarioUsuario');
					if (isset($this->session->userdata['login'])) {
							$data['user'] = $this->session->userdata['login']['user'];
							
							if ($this->session->userdata['login']['exito']) {
					$this->load->view('header', $data);
					$this->load->view('Horarios_Usuario', $data);
					$this->load->view('footer', $data);
							} else {
									$this->load->view('login');
							}
					} else {
							$this->load->view('login');
			}		
		}
		
    public function consultaEmpleado(){
			header('Access-Control-Allow-Origin: *');
			$this->load->model('HorarioUsuario');
			
				$empresa_id= $this->input->post('empresa_id',true);
			$search=null;
			$data = array();
			if(!isset($_POST['searchTerm'])){
				$data = $this->HorarioUsuario->consultaEmpleado($search,$empresa_id);     
			}else{
				$search = $_POST['searchTerm'];   
				$data = $this->HorarioUsuario->consultaEmpleado($search,$empresa_id);    
			} 
			echo json_encode($data);
		}		
			private function _horasONull($v){
    return ($v === null || $v === '') ? null : (int)$v;
}

public function ActualizarHorariosEmpleado()
{
    header('Access-Control-Allow-Origin: *');
    $id= $this->input->post('id',true);
    $data = array(
        'idhorario_lunes' 		=> $this->input->post('idhorarioLunes',true),
        'empresa_id'  				=> $this->input->post('empresa_id', true),  // <-- agregar
        'idhorario_martes' 		=> $this->input->post('idhorarioMartes',true),
        'idhorario_miercoles'   => $this->input->post('idhorarioMiercoles',true),
        'idhorario_jueves'		=> $this->input->post('idhorarioJueves',true),
        'idhorario_viernes'		=> $this->input->post('idhorarioViernes',true),
        'idhorario_sabado'		=> $this->input->post('idhorarioSabado',true),
        'idhorario_domingo'		=> $this->input->post('idhorarioDomingo',true),
        'descripcion'			=> $this->input->post('descripcion',true),				
        'fechaAsignacion'		=> $this->input->post('fechaAsignacion',true),				
        'cantidad'				=> $this->input->post('cantidad',true),
    );
    $this->load->model('HorarioUsuario');
    echo  $this->HorarioUsuario->ActualizarHorariosEmpleado($id,$data);
}

function AgregarHorariosEmpleado()
{
    header('Access-Control-Allow-Origin: *');
    $this->load->model('HorarioUsuario');
    $data =  array(
        'empleado_id' 				=> $this->input->post('empleado_id',true),
        'empresa_id'  				=> $this->input->post('empresa_id', true),  // <-- agregar
        'idhorario_lunes' 		=> $this->input->post('idhorarioLunes',true),
        'idhorario_martes' 		=> $this->input->post('idhorarioMartes',true),
        'idhorario_miercoles'	=> $this->input->post('idhorarioMiercoles',true),
        'idhorario_jueves'		=> $this->input->post('idhorarioJueves',true),
        'idhorario_viernes'		=> $this->input->post('idhorarioViernes',true),
        'idhorario_sabado'		=> $this->input->post('idhorarioSabado',true),
        'idhorario_domingo'		=> $this->input->post('idhorarioDomingo',true),
        'idEstatus'				=> 1,			
        'descripcion'			=> $this->input->post('descripcion',true),				
        'fechaAsignacion'		=> $this->input->post('fechaAsignacion',true),				
        'cantidad'				=> $this->input->post('cantidad',true),
    );

    $dt['exito'] = $this->HorarioUsuario->AgregarHorariosEmpleado($data);
    echo $dt['exito'];
}
    public function consultaHorario(){
			header('Access-Control-Allow-Origin: *');
			$empleado_id = $this->input->post('empleado_id',true);
			$idDia = $this->input->post('dia',true);
			$result = array('data' => array());
			$this->load->model('HorarioUsuario');
			$data = $this->HorarioUsuario->consultaHorario($empleado_id,$idDia);
			//DESDE JS se hacen los botones de editar y eliminar horariosUsuario.js
			echo json_encode($data);
		}

    public function consultaHorarioEditar(){
			header('Access-Control-Allow-Origin: *');
			$id = $this->input->post('id',true);
			$result = array('data' => array());
			$this->load->model('HorarioUsuario');
			$data = $this->HorarioUsuario->consultaHorarioEditar($id);
			//DESDE JS se hacen los botones de editar y eliminar horariosUsuario.js
			echo json_encode($data);
		}
		
    public function consultaHorarioID(){
			header('Access-Control-Allow-Origin: *');
			$id = $this->input->post('id',true);
			$result = array('data' => array());
			$this->load->model('HorarioUsuario');
			$data = $this->HorarioUsuario->consultaHorarioID($id);
			echo json_encode($data);
		}
		

    public function consultaHorarios(){
			header('Access-Control-Allow-Origin: *');
			$result = array('data' => array());
			$this->load->model('HorarioUsuario');
			$data = $this->HorarioUsuario->consultaHorarios();
			//DESDE JS se hacen los botones de editar y eliminar horariosUsuario.js
			echo json_encode($data);
		}
		
    function delete() {
			header('Access-Control-Allow-Origin: *');
			$this->load->model('HorarioUsuario');
			//$this->load->model('Usuario');
			$login=$this->session->userdata['login'];
			$id_user=$login['user']->id ;
			$fechaBaja=date('Y-m-d');
			$status=0;
			
					$id = $this->input->post('id');
					$data = [			
				'idEstatus' 				=> $status,	
				'fechaBaja' 				=> $fechaBaja,	
				'idusuarioBaja' 		=> $id_user
					];
			echo $this->HorarioUsuario->ActualizarHorariosEmpleado($id, $data);
    }
	


    /** Horario vigente de un empleado (para "Copiar horario de..."). */
    public function horarioVigente(){
        header('Access-Control-Allow-Origin: *');
        $this->load->model('HorarioUsuario');
        $h = $this->HorarioUsuario->horarioVigente($this->input->post('empleado_id', true));
        echo json_encode($h ? array('exito' => true, 'horario' => $h)
                            : array('exito' => false, 'mensaje' => 'Ese empleado no tiene horario asignado.'));
    }

    /** Copia el horario vigente de un empleado a uno o varios empleados. */
    public function copiarHorario(){
        header('Access-Control-Allow-Origin: *');
        if (!isset($this->session->userdata['login']['user'])) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Sesión inválida, vuelva a iniciar sesión.'));
            return;
        }
        $origen     = trim((string) $this->input->post('origen', true));
        $destinos   = array_values(array_unique(array_filter(array_map('trim', (array) $this->input->post('destinos', true)))));
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $fecha      = (string) $this->input->post('fechaAsignacion', true);
        if ($origen === '' || empty($destinos) || $empresa_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Selecciona empleado origen, empleados destino, planta y fecha.'));
            return;
        }
        $this->load->model('HorarioUsuario');
        echo json_encode($this->HorarioUsuario->copiarHorario($origen, $destinos, $empresa_id, $fecha));
    }
}
