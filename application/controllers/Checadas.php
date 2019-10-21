<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Checadas extends CI_Controller {
	
	public function construct()
	{
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
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
		$this->load->model('Checada');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
            $data['checadas'] =json_encode( $this->Checada->consulta(NULL,NULL,NULL,NULL));
            			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Checadas', $data);
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
        $NoSemana=$this->input->post('NoSemana');
        $anio=$this->input->post('anio');
        $departamento=$this->input->post('departamento');
        $noempleado=$this->input->post('noempleado');
       // echo $NoSemana.$anio.$departamento.$noempleado;
       if (empty($NoSemana)){
        $NoSemana=null;
       }if (empty($anio)){
        $anio=null;
       }if (empty($noempleado)){
        $noempleado=null;
       }if (empty($departamento)){
        $departamento=null;
       }
        $this->load->model('Checada');
        $result = array('data' => array());
		$result =$this->Checada->consulta($NoSemana,$anio,$departamento,$noempleado);
        echo json_encode($result);
    }

    function consultaChecadasMYSQL(){
        $this->load->model('Checada');
        $result =$this->Checada->consultaChecadasMYSQL();

        echo json_encode($result);
    }

    function consultaDepartamento(){

		header('Access-Control-Allow-Origin: *');
        $this->load->model('Checada');
        $result = array('data' => array());
        $result =$this->Checada->consultaDepartamento();
        
        echo json_encode($result);
    }

    public function consulta_checadas()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Checada');
		$data['data']=$this->Checada->consulta();
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