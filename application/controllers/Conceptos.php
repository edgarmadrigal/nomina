<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conceptos extends CI_Controller {
	
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
		$this->load->model('Concepto');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
            $data['concepto'] =json_encode($this->Concepto->consulta(null));
            			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Conceptos', $data);
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
        $id=$this->input->post('id');
       if (empty($id)){
        $ids=null;
       }
        $this->load->model('concepto');
        $result = array('data' => array());
		$result =$this->concepto->consulta($id);

        echo json_encode($result);
    }


    
	public function consultaClasificacion()
	{ 
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Concepto');
        $id = $this->input->post('id');
		$data = $this->Concepto->consultaClasificacion($id);
        echo json_encode($data);
	}

    public function consulta_conceptos()
    {
		$result = array('data' => array());
		$this->load->model('Usuario');
		$this->load->model('Concepto');
		$data =$this->Concepto->consulta(null);
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
				$value['concepto'],			
				$value['descripcionCorta'],			
				$value['Clasificacion'],			
				$value['goceSueldo'],			
				$value['comentarios'],
				$buttons
			);
		}
		echo json_encode($result);
    }

    function edit()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Concepto');
            $id   =   $this->input->post('id');
            $concepto  =   $this->input->post('concepto');
            $descripcionCorta =   $this->input->post('descripcionCorta');
            $idClasificacion =   $this->input->post('idClasificacion');

            $goceSueldo =   $this->input->post('goceSueldo');
            $comentarios =   $this->input->post('comentarios');
               
           print_r(json_encode($this->Concepto->actualizaConceptos($id, $concepto , $descripcionCorta , $idClasificacion , $goceSueldo , $comentarios)));
    }

    function delete()
    {

        header('Access-Control-Allow-Origin: *');
		$this->load->model('Concepto');
		$login=$this->session->userdata['login'];
		$id_user=$login['user']->id ;
		$fechaBaja=date("Y-m-d h:i:s");
		$status=0;
		
        $id = $this->input->post('id');
        $data = [			
			'status' 			=> $status,	
			'fechaBaja' 		=> $fechaBaja,	
            'idusuarioBaja'     => $id_user
        ];
        echo $this->Concepto->update_concepto($id, $data);
    }

    function insert()
    {
		header('Access-Control-Allow-Origin: *');
        $this->load->model('Concepto');
        $concepto  =   $this->input->post('concepto');
        $descripcionCorta =   $this->input->post('descripcionCorta');
        $idClasificacion =   $this->input->post('idClasificacion');

        $goceSueldo =   $this->input->post('goceSueldo');
        $comentarios =   $this->input->post('comentarios');

        $dt['exito'] = $this->Concepto->insert_concepto($concepto , $descripcionCorta , $idClasificacion , $goceSueldo , $comentarios);
        echo $dt['exito'];
    }
	

}