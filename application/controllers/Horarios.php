<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Horarios extends CI_Controller {
	
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
		$this->load->model('Usuario');
		$this->load->model('Horario');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['horarios'] = $this->Horario->consulta_Horarios();
			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Horarios', $data);
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
				}		
  }
	
	public function consultaPerfil(){ 
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Perfil');
		$data = $this->Perfil->consulta_Perfiles();
        echo json_encode($data);
	}

	function consulta(){
			header('Access-Control-Allow-Origin: *');
			$this->load->model('Horario');
			$id = $this->input->post('id');
			echo json_encode($this->Horario->get_horario($id));
	}

	public function consulta_horarios(){
		$result = array('data' => array());
		$this->load->model('Usuario');
		$this->load->model('Horario');
		$data =$this->Horario->consulta();
		foreach ($data as $key => $value) {
			
			$buttons = '
			<div class="btn-group" >
				<button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" 
				data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Acciones <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a type="button" onclick="editar('.$value['id'].')" data-toggle="modal" data-target="#myModal_agregar">Editar</a></li>
					<li><a type="button" onclick="eliminar('.$value['id'].'  )">Eliminar</a></li>			    
				</ul>
			</div>
			';
			$result['data'][$key] = array(			
				$value['id'],			
				$value['nombre'],	
				$value['entrada_Desde'],			
				$value['entrada_Inicio'],			
				$value['entrada_Hasta'],			
				$value['salida_Desde'],			
				$value['salida_Inicio'],			
				$value['salida_Hasta'],
				$value['horas_diarias'],
				$value['comedor'],
				$value['tiempoComida'],
				$value['tipo'],
				$value['fechaAsignacion'],
				$buttons
			);
		}
		echo json_encode($result);
	}

	function edit(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Horario');
			$id = $this->input->post('id');
			$data = [			
		'nombre'			=> $this->input->post('nombre'),	
		'entrada_Inicio' 	=> $this->input->post('entradaInicio'),	
		'salida_Desde' 		=> $this->input->post('salidaDesde')  ,	
		'salida_Hasta' 		=> $this->input->post('salidaHasta')  ,	
		'tipo' 				=> $this->input->post('tipo') ,	
		'entrada_Desde' 	=> $this->input->post('entradaDesde') ,	
		'entrada_Hasta' 	=> $this->input->post('entradaHasta') ,	
		'salida_Inicio' 	=> $this->input->post('salidaInicio') ,	
		'horas_diarias' 	=> $this->input->post('horasdiarias') ,
		'comedor'			=> $this->input->post('comedor') ,
		'tiempoComida'		=> $this->input->post('tiempoComida'),
		'fechaAsignacion'	=> $this->input->post('fechaAsignacion') ,
			];
			echo $this->Horario->update_horario($id, $data);
	}

	function delete(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Horario');
		//$this->load->model('Usuario');
		$login=$this->session->userdata['login'];
		$id_user=$login['user']->id ;
		$fechaBaja=date('Y-m-d');
		$status=0;
		
				$id = $this->input->post('id');
				$data = [			
			'status' 			=> $status,	
			'fechaBaja' 		=> $fechaBaja,	
				'idusuarioBaja' 		=> $id_user
				];
		echo $this->Horario->update_horario($id, $data);
	}
	function insert(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Horario');
			$data = [
		'nombre' 			=> $this->input->post('nombre') ,	
		'entrada_Desde' 	=> $this->input->post('entradaDesde') ,	
		'entrada_Inicio' 	=> $this->input->post('entradaInicio'),	
		'entrada_Hasta' 	=> $this->input->post('entradaHasta') ,	
		'salida_Desde' 		=> $this->input->post('salidaDesde')  ,	
		'salida_Inicio' 	=> $this->input->post('salidaInicio') ,	
		'salida_Hasta' 		=> $this->input->post('salidaHasta')  ,	
		'tipo' 				=> $this->input->post('tipo') ,	
		'horas_diarias' 	=> $this->input->post('horasdiarias') ,
		'comedor'			=> $this->input->post('comedor'),
		'tiempoComida'		=> $this->input->post('tiempoComida'),
		'fechaAsignacion'	=> $this->input->post('fechaAsignacion')];
			$dt['exito'] = $this->Horario->insert_horario($data);
			echo $dt['exito'];
	}
}