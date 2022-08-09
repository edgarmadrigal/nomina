<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmpleadosController_Eventuales extends CI_Controller {
	
	public function construct()
	{
		 header('Access-Control-Allow-Origin: *');
		 parent::__construct();
		 $this->load->database();
		 $this->load->model('import_model', 'import');
		 $this->load->model('empleadoModel_Eventuales');
    }

    public function index(){
		header('Access-Control-Allow-Origin: *');
		$data = [];
		//$this->load->model('Asistencia');
			if (isset($this->session->userdata['login'])) {
		  $data['user'] = $this->session->userdata['login']['user'];
				if ($this->session->userdata['login']['exito']) {
				  $this->load->view('header', $data);
				  $this->load->view('EmpleadosView_Eventuales', $data);
				  $this->load->view('footer', $data);
				} else {
					$this->load->view('login');
			  }
			} else {
				$this->load->view('login');
		}
	}

/**  */
	function consulta()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        echo json_encode($this->empleadoModel_Eventuales->get_empleado($id));
    }

    public function consulta_empleado()
    {
		$result = array('data' => array());
		$this->load->model('empleadoModel_Eventuales');
		$data =$this->empleadoModel_Eventuales->consulta();
		foreach ($data as $key => $value) {
			$buttons = '
			<div class="btn-group" >
			  <button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Acciones <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu">
			    <li><a type="button" onclick="editar('.$value['id'].')" data-toggle="modal" data-target="#myModal_agregar">Editar</a></li>
			    <li><a type="button" onclick="eliminar('.$value['id'].', '. "'".$value['NOMBRE_COMPLETO'] ."'".'  )">Eliminar</a></li>			    
			  </ul>
			</div>
			';
			$result['data'][$key] = array(
				$value['id'],		
				$value['NUMERO'],	
				$value['NOMBRE_COMPLETO'],
				$value['NOMBRE_PUESTO'],
				$value['NOMBRE_DEPARTAMENTO'],
				$value['ESTATUS'],
				$buttons
			);
		} // /foreach
		echo json_encode($result);
    }

    function edit()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        $data = [
			
	 	'empleado_id'           	=> $this->input->post('NUMERO'),  
		'NUMERO'           			=> $this->input->post('NUMERO'),
		'NOMBRE_COMPLETO'           => $this->input->post('NOMBRE_COMPLETO'),
		'NOMBRES'                	=> $this->input->post('NOMBRES'				),
		'APELLIDO_PATERNO'       	=> $this->input->post('APELLIDO_PATERNO'	),       
		'APELLIDO_MATERNO'       	=> $this->input->post('APELLIDO_MATERNO'	),       
		'NOMBRE_PUESTO'       		=> $this->input->post('NOMBRE_PUESTO'		),  
		'NOMBRE_DEPARTAMENTO'       => $this->input->post('NOMBRE_DEPARTAMENTO'	),
		'ESTATUS'       			=> 'A',
		];
        echo $this->empleadoModel_Eventuales->update_empleado($id, $data);
    }

    function delete()
    {

		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        $data = [
		'ESTATUS'                	=>  'B',  
        ];
        echo $this->empleadoModel_Eventuales->update_empleado($id, $data);

		/*
	   'NUMERO'                 =>  $data['NUMERO'],
       'empleado_id'            =>  $data['NUMERO'],
       'NOMBRES'                =>  $data['NOMBRES'],
       'APELLIDO_PATERNO'       =>  $data['APELLIDO_PATERNO'],       
       'APELLIDO_MATERNO'       =>  $data['APELLIDO_MATERNO'],       
       'APELLIDO_PATERNO'       =>  $data['NOMBRE_PUESTO'],  
       'APELLIDO_PATERNO'       =>  $data['NOMBRE_DEPARTAMENTO'],  
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        echo $this->empleadoModel_Eventuales->delete_usuario($id);
		*/
    }

    function insert()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $data = [
			'NUMERO'                 	=> $this->input->post('NUMERO'				),
			'NOMBRE_COMPLETO'           => $this->input->post('NOMBRE_COMPLETO'		),
			'NOMBRES'                	=> $this->input->post('NOMBRES'				),
			'APELLIDO_PATERNO'       	=> $this->input->post('APELLIDO_PATERNO'	),       
			'APELLIDO_MATERNO'       	=> $this->input->post('APELLIDO_MATERNO'	),       
			'NOMBRE_PUESTO'       		=> $this->input->post('NOMBRE_PUESTO'		),  
			'NOMBRE_DEPARTAMENTO'       => $this->input->post('NOMBRE_DEPARTAMENTO' ),
			'ESTATUS'                	=>  'A',  
        ];

        $dt['exito'] = $this->empleadoModel_Eventuales->insert_empleado($data);
        echo $dt['exito'];
    }
	

}