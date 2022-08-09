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
		   $config["smtp_user"] = 'josuederker2022@gmail.com';
			
		  //Nuestra contraseña
		   //$config["smtp_pass"] = '0110EM18';   
			
		  //El puerto que utilizará el servidor smtp
		   $config["smtp_port"] = '2525';
		   
		  //El juego de caracteres a utilizar
		   $config['charset'] = 'utf-8';
	
		  //Permitimos que se puedan cortar palabras
		   $config['wordwrap'] = TRUE;
			
		  //El email debe ser valido 
		  $config['validate'] = true;
		  
		   
		 //Establecemos esta configuración
		   $this->email->initialize($config);
	
		 //Ponemos la dirección de correo que enviará el email y un nombre
		   $this->email->from('josuederker2022@gmail.com', 'SINDICATO');
			
		 /*
		  * Ponemos el o los destinatarios para los que va el email
		  * en este caso al ser un formulario de contacto te lo enviarás a ti
		  * mismo
		  */
		  //ainvig4
		   $this->email->to('josuederker2022@gmail.com');
			
		 //Definimos el asunto del mensaje
		 //  $this->email->subject($this->input->post("holaaa!"));
   
		 $this->email->subject('RECIBO DE NOMINA SINDICAL');
			
		 //Definimos el mensaje a enviar
		   $this->email->message(
				  // "Email: ".$this->input->post("email").
				  // " Mensaje: ".$this->input->post("mensaje")
				  " TU RECIBO DE NOMINA"
				   );
				   
			//$this->email->attach('ReciboSindical.pdf');
		   /*
		   $this->email->subject('¡ALERTA! He hackeado tu computadora y te he robado información.');
			
		 //Definimos el mensaje a enviar
		   $this->email->message(
				  // "Email: ".$this->input->post("email").
				  // " Mensaje: ".$this->input->post("mensaje")
				  "¡Tu computadora fue infectada con mi malware!

				  Su contraseña para este correo: oswaldo1
				  
				  Soy programador y hackeé tu computadora hace 3 meses. Seguí guardando información todo el tiempo, como: historial de navegación, grabaciones de pantalla, contactos, mensajes y mucho más.
				  
				  Ya quería olvidarte, pero hace poco vi algo interesante en tu escritorio. Estoy hablando del día que visitaste un sitio porno. Decidí grabar video desde la cámara web y el escritorio. Ahora tengo un video tuyo masturbándote. Sabes a lo que me refiero 😉
				  
				  Me conecté a la cámara web de forma remota y apagué el indicador para que no notara nada.
				  
				  Ya he anotado todos tus contactos de la libreta de direcciones. Todos los contactos de amigos, conocidos, familiares. Todo esto estará conmigo.
				  
				  Estoy listo para olvidarme de todo esto y dejar de acceder por completo a su computadora y correo electrónico. Garantizo que no enviaré estos videos y eliminaré todos los archivos con ellos.
				  Después de eso me iré y ya no te molestaré, pero para eso quiero tener $5,000 pesos en bitcoins en mi billetera.
				  Tienes 48 horas después de leer este correo electrónico. Todavía controlo tu correo electrónico y tu computadora, y sé cuándo los abres y los lees.
				  
				  No intentes cambiar la contraseña de tu correo electrónico, todo está bajo control. No intentes contactarme y contestar esta carta. Te lo envié desde tu dirección de correo electrónico. Echa un vistazo al remitente, verás que tengo control total sobre tu correo electrónico y tu computadora.
				  
				  Dirección de la billetera Bitcoin:
				  3K13Sb8u9Rcxqufa3vpwSHQ8HyLC1jwio5
				  
				  Si no sabe cómo comprar bitcoins, puede encontrar información sobre cómo comprar bitcoins en línea. Si necesitas ayuda, puedes leer varios artículos al respecto.
				  
				  https://localbitcoins.com/guides/how-to-buy-bitcoins
				  https://www.coinbase.com/comprar-bitcoin?locale=en
				  https://paxful.com/como-comprar-bitcoin
				  
				  Espero sus acciones. Si no necesita estos datos en línea y con todos sus amigos, envíe $ 500 a mi billetera lo antes posible. Después de eso, borraré todos los datos y desapareceré de tu vida.
				  
				  No te ofendas por mí. Si pagas, no pasa nada.
				  
				  ¡La próxima vez actualiza tu navegador antes de navegar por la web!
				   "
				   );
				   */
			
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