<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Byte extends CI_Controller {
	
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
		$this->load->model('ByteEmpleado');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['empleados'] = $this->ByteEmpleado->consulta_Empleados_Nazareno(1);			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('EmpleadosByte', $data);
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
    }
    
	public function consultaPerfil()
	{ 
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Perfil');
		$data = $this->Perfil->consulta_Perfiles();
        echo json_encode($data);

	}

	function consulta()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
        $id = $this->input->post('id');
        echo json_encode($this->Usuario->get_usuario($id));
    }

    public function consulta_empleados()
    {
		header('Access-Control-Allow-Origin: *');
		$empresa=$this->input->post('empresa');
		$this->load->model('ByteEmpleado');
		$data =$this->ByteEmpleado->consulta(intval($empresa));
		echo json_encode($data);
    }

    function edit()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
        $id = $this->input->post('id');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'usuario' => $this->input->post('usuario'),
            'password' => $this->input->post('password'),
            'idPerfil' => $this->input->post('idPerfil')
        ];
        echo $this->Usuario->update_usuario($id, $data);
    }

    function delete()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
        $id = $this->input->post('id');
        echo $this->Usuario->delete_usuario($id);
    }

    function insert()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'usuario' => $this->input->post('usuario'),
            'password' => $this->input->post('password'),
            'idPerfil' => $this->input->post('idPerfil')
        ];

        $dt['exito'] = $this->Usuario->insert_usuario($data);
        echo $dt['exito'];
    }
	

}