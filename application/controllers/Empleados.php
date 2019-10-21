<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Empleados extends CI_Controller {
	
	public function construct()
	{
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

	public function index()
	{ 
		header('Access-Control-Allow-Origin: *');
		$data = [];
		$this->load->model('Usuario');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['usuarios'] = $this->Usuario->consulta_Usuarios();
			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Empleados', $data);
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
	}
	
	function actualizar()
    {
		
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Empleado');
        echo json_encode($this->Empleado->actualizar());
    }

    public function consultaEmpleado(){
		header('Access-Control-Allow-Origin: *');
		$empresa_id = $this->input->post('empresa_id');
		$result = array('data' => array());
		$this->load->model('Empleado');
		$data =$this->Empleado->consulta($empresa_id);
		echo json_encode($data);
	}

	

}