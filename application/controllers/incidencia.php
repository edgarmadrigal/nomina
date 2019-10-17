<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class incidencia extends CI_Controller {

	public function construct(){
		$this->load->model('Incidencias');
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
				
		$this->load->model('Incidencias');
				if (isset($this->session->userdata['login'])) {
						$data['user'] = $this->session->userdata['login']['user'];
						
						if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('IncidenciasView', $data);
				$this->load->view('footer', $data);
						} else {
								$this->load->view('login');
						}
				} else {
						$this->load->view('login');
		}		
	}

	public function MandaCorreo(){

		  //Cargamos la librería email
		  $this->load->library('email');
        
		  /*
		   * Configuramos los parámetros para enviar el email,
		   * las siguientes configuraciones es recomendable
		   * hacerlas en el fichero email.php dentro del directorio config,
		   * en este caso para hacer un ejemplo rápido lo hacemos
		   * en el propio controlador
		   */
		   
		  //Indicamos el protocolo a utilizar
		   $config['protocol'] = 'smtp';         
		  
		   //El servidor de correo que utilizaremos
		   $config["smtp_host"] = 'mail.apparelinternational.com';
   
		  //Nuestro usuario
		   $config["smtp_user"] = 'emadrigal@apparelinternational.com';
			
		  //Nuestra contraseña
		   //$config["smtp_pass"] = '0110EM18';   
			
		  //El puerto que utilizará el servidor smtp
		   $config["smtp_port"] = '25';
		   
		  //El juego de caracteres a utilizar
		   $config['charset'] = 'utf-8';
	
		  //Permitimos que se puedan cortar palabras
		   $config['wordwrap'] = TRUE;
			
		  //El email debe ser valido 
		  $config['validate'] = true;
		  
		   
		 //Establecemos esta configuración
		   $this->email->initialize($config);
	
		 //Ponemos la dirección de correo que enviará el email y un nombre
		   $this->email->from('ocastaneda@apparelinternational.com', 'Oscar Castañeda');
			
		 /*
		  * Ponemos el o los destinatarios para los que va el email
		  * en este caso al ser un formulario de contacto te lo enviarás a ti
		  * mismo
		  */
		  //ainvig4
		   $this->email->to('emadrigal@apparelinternational.com');
			
		 //Definimos el asunto del mensaje
		 //  $this->email->subject($this->input->post("holaaa!"));
   
		   
		   $this->email->subject('holaaa!');
			
		 //Definimos el mensaje a enviar
		   $this->email->message(
				  // "Email: ".$this->input->post("email").
				  // " Mensaje: ".$this->input->post("mensaje")
				  "Buen dia Oscar,
				  Esto es una prueba de edgar que se puede mandar cualquier correo de cualquier persona sin necesidad de la contraseña,
				  no se por que solo para los programas si se blo
				  Saludos "
				   );
			
		   //Enviamos el email y si se produce bien o mal que avise con una flasdata
		   if($this->email->send()){
			   echo 'Email enviado correctamente';
		   }else{
			   echo 'No se a enviado el email';
		   }
	}
        
    public function concepto(){
        header('Access-Control-Allow-Origin: *');
          $this->load->model('Incidencias');
          $data = $this->Incidencias->consultaConceptos();
          echo json_encode($data);
	}

	public function consultaIncidencias(){
		header('Access-Control-Allow-Origin: *');
		$result = array('data' => array());
		$id = $this->input->post('id',true);
		$this->load->model('Incidencias');
		$data = $this->Incidencias->consultaIncidencias($id);
		echo json_encode($data);
	}


	public function empleado(){
		header('Access-Control-Allow-Origin: *');
		$result = array('data' => array());
		$id = $this->input->post('id',true);
		$this->load->model('Incidencias');
		$data = $this->Incidencias->consultaEmpleado($id);
		echo json_encode($data);
	}
      
	public function descripcionCorta(){
		header('Access-Control-Allow-Origin: *');
		$result = array('data' => array());
		$id = $this->input->post('id',true);
		$this->load->model('Incidencias');
		$data = $this->Incidencias->consultaDescripcionCorta($id);
		echo json_encode($data);
	}
	  

	public function ActualizarIncidencia(){
		header('Access-Control-Allow-Origin: *');
				$id= $this->input->post('id',true);
				$data = array(
				'concepto_id' 	=> $this->input->post('concepto_id',true),
				'fechaInicio' 	=> $this->input->post('fechaInicio',true),
				'fechaFin'		=> $this->input->post('fechaFin',true)
			);
				$this->load->model('Incidencias');
				//print_r($params);die();
				echo  $this->Incidencias->ActualizarIncidencia($id,$data);
	}
	public function GuardarIncidencia(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Incidencias');
		$data =  array(
			'empleado_id' 	=> $this->input->post('empleado_id',true),
			'concepto_id' 	=> $this->input->post('concepto_id',true),
			'fechaInicio' 	=> $this->input->post('fechaInicio',true),
			'fechaFin'		=> $this->input->post('fechaFin',true),
			'estatus'		=> $this->input->post('estatus',true)
		);

		$dt['exito'] = $this->Incidencias->GuardarIncidencia($data);
		echo $dt['exito'];
	}	
	public function ActualizarHorariosEmpleado(){
		header('Access-Control-Allow-Origin: *');
		$id= $this->input->post('id',true);
		$data = array(
		'idhorario_lunes' 		=> $this->input->post('idhorarioLunes',true),
		'idhorario_martes' 		=> $this->input->post('idhorarioMartes',true),
		'idhorario_miercoles' => $this->input->post('idhorarioMiercoles',true),
		'idhorario_jueves'		=> $this->input->post('idhorarioJueves',true),
		'idhorario_viernes'		=> $this->input->post('idhorarioViernes',true),
		'idhorario_sabado'		=> $this->input->post('idhorarioSabado',true),
		'idhorario_domingo'		=> $this->input->post('idhorarioDomingo',true),
		'descripcion'					=> $this->input->post('descripcion',true),				
		'fechaAsignacion'			=> $this->input->post('fechaAsignacion',true),
		);
		$this->load->model('HorarioUsuario');
		//print_r($params);die();
		echo  $this->HorarioUsuario->ActualizarHorariosEmpleado($id,$data);
	}

	function AgregarHorariosEmpleado(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('HorarioUsuario');
			$data =  array(
				'empleado_id' 				=> $this->input->post('empleado_id',true),
				'idhorario_lunes' 		=> $this->input->post('idhorarioLunes',true),
				'idhorario_martes' 		=> $this->input->post('idhorarioMartes',true),
				'idhorario_miercoles'	=> $this->input->post('idhorarioMiercoles',true),
				'idhorario_jueves'		=> $this->input->post('idhorarioJueves',true),
				'idhorario_viernes'		=> $this->input->post('idhorarioViernes',true),
				'idhorario_sabado'		=> $this->input->post('idhorarioSabado',true),
				'idhorario_domingo'		=> $this->input->post('idhorarioDomingo',true),
				'idEstatus'						=> 1,			
				'descripcion'					=> $this->input->post('descripcion',true),				
				'fechaAsignacion'			=> $this->input->post('fechaAsignacion',true),
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

    public function consultaIncidenciaEditar(){
			header('Access-Control-Allow-Origin: *');
			$id = $this->input->post('id',true);
			$result = array('data' => array());
			$this->load->model('Incidencias');
			$data = $this->Incidencias->ConsultaIncidenciaEditar($id);
			//DESDE JS se hacen los botones de editar y eliminar horariosUsuario.js
			echo json_encode($data);
	}
		
	
    function delete() {
		header('Access-Control-Allow-Origin: *');

		$login=$this->session->userdata['login'];
		$id_user=$login['user']->id ;
		$fechaBaja=date('Y-m-d');
		$status=0;
		$id = $this->input->post('id');
		$data = [
		'estatus' 				=> $status,	
		'fechaBaja' 				=> $fechaBaja,	
		'usuarioBaja_id' 		    => $id_user
		];
		$this->load->model('Incidencias');
		echo $this->Incidencias->ActualizarIncidencia($id, $data);
	}
}