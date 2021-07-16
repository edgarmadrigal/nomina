<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChecadasBiostar extends CI_Controller {
	
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
		$this->load->model('ChecadaBiostar');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
           /// $data['checadas'] =json_encode( $this->ChecadaBiostar->consulta(NULL,NULL,NULL,NULL,NULL,NULL));
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('ChecadasBiostar', $data);
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
    }

	function consulta()
    {       
		header('Access-Control-Allow-Origin: *'); 
        $NoSemana=$this->input->post('NoSemana');
        $anio=$this->input->post('anio');
        $planta=$this->input->post('planta');
        $departamento=$this->input->post('departamento');
        $puesto=$this->input->post('puesto');
        $noempleado=$this->input->post('noempleado');
       // echo $NoSemana.$anio.$departamento.$noempleado;
       if (empty($NoSemana)){
        $NoSemana=null;
       }if (empty($anio)){
        $anio=null;
       }if (empty($noempleado)){
        $noempleado=null;
       }if (empty($planta)){
        $planta=null;
       }if (empty($departamento)){
        $departamento=null;
       }
       if (empty($planta)){
        $planta=null;
       }
       if (empty($puesto)){
        $puesto=null;
       }
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
		$result =$this->ChecadaBiostar->consulta($NoSemana,$anio,$planta,$departamento,$puesto,$noempleado);

        echo json_encode($result);
    }
    



    function consultaPlanta(){

		header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->consultaPlanta();
        
        echo json_encode($result);
    }

    function ActualizarChecadasNomiplus(){
        
		header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->ActualizarChecadasNomiplus();
        
        echo json_encode($result);
    }
    function consultaDepartamento(){

        $planta=$this->input->post('planta');
        header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->consultaDepartamento($planta);
        
        echo json_encode($result);
    }

    function consultaPuesto(){

        $planta=$this->input->post('planta');
		header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->consultaPuesto($planta);
        
        echo json_encode($result);
    }
    
    public function consulta_checadas()
    {
		header('Access-Control-Allow-Origin: *');
		//$empresa=$this->input->post('empresa');
		//echo json_encode($empresa);
		$this->load->model('ChecadaBiostar');
		$data['data']=$this->ChecadaBiostar->consulta();


		echo json_encode($data);
    }
	

}