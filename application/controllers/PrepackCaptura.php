<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PrepackCaptura extends CI_Controller {
	
	public function construct()
	{
		$this->load->model('PrepackCapturaModel');
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
				$this->load->view('PrepackCaptura', $data);
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

    public function consulta_usuario()
    {
        
		$result = array('data' => array());
		$this->load->model('Usuario');
		$data =$this->Usuario->consulta();
		foreach ($data as $key => $value) {  
			// button
			$buttons = '
			<div class="btn-group" >
			  <button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Acciones <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu">
			    <li><a type="button" onclick="editar('.$value['id'].')" data-toggle="modal" data-target="#myModal_agregar">Editar</a></li>
			    <li><a type="button" onclick="eliminar('.$value['id'].', '. "'".$value['usuario'] ."'".'  )">Eliminar</a></li>			    
			  </ul>
			</div>
			';
			$result['data'][$key] = array(			
				$value['id'],			
				$value['nombre'],
				$value['usuario'],
				$value['perfil'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
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