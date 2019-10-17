<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	
	public function construct()
	{
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
		$data = array();
		$this->load->model('Usuario');
		if(isset($this->session->userdata['login'])){
			if(isset($this->session->userdata['login']['user'])){
				$data['user'] = $this->session->userdata['login']['user'];
				if($this->session->userdata['login']['exito']){
					$this->load->view('header',$data);
					$this->load->view('system',$data);
					$this->load->view('footer',$data);
				} else {
					$this->load->view('login');
				}
			}
			else{
				$this->load->view('login');
			}
		} else {
			$this->load->view('login');
		}   
	}


	public function logout(){
		$this->session->sess_destroy();
		echo true;
	}

	public function login()
	{
		try{
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
		$usuario = $this->input->post('usuario');
		$password = $this->input->post('password');
		$login = array();
		if((isset($usuario))&&(isset($password))){
			$existe = $this->Usuario->existe($usuario);
			if($existe){
				$login = array();
				$login_admin = $this->Usuario->login_usuario($usuario,$password);
				if($login_admin){
					$login['user'] = $login_admin->login;
					$login['exito'] = true;
				} else {
					$this->session->sess_destroy();
					$login['exito'] = 0;
				}
			} else {
				$this->session->sess_destroy();
				$login['exito'] = 0;
			}
		}else{
			$this->session->sess_destroy();
			$login['exito'] = 0;
		}		
		$this->session->login = $login;
		} catch (Exception $e) {
		echo 'Excepción capturada: ',  $e->getMessage(), "\n";			
			$this->session->sess_destroy();
			$login['exito'] = 0;
		}
		
		echo $login['exito'];
	}
}
?>