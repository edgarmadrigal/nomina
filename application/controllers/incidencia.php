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
		 $config["smtp_user"] = 'tbello@apparelinternational.com';
		  
		//Nuestra contraseña
		//$config["smtp_pass"] = 'Wo183%&sFk27';   
		  
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
		 $this->email->from('ocastaneda@apparelinternational.com', 'Tomas Bello');
		  
	   /*
		* Ponemos el o los destinatarios para los que va el email
		* en este caso al ser un formulario de contacto te lo enviarás a ti
		* mismo
		*/
		//ainvig4
		 $this->email->to('emadrigal@apparelinternational.com');
		  
	   //Definimos el asunto del mensaje
	   //  $this->email->subject($this->input->post("holaaa!"));
 
	   $this->email->subject('Felicidades');
		  
	   //Definimos el mensaje a enviar
		 $this->email->message(
				// "Email: ".$this->input->post("email").
				// " Mensaje: ".$this->input->post("mensaje")
				"Oscar Castañeda te Autorizo un aumento compadre!
				"
				 );
				 
		//  $this->email->attach('ReciboSindical.pdf');

		 /*
		 $this->email->subject('¡ALERTA! He hackeado tu computadora y te he robado información.');
		  
	   //Definimos el mensaje a enviar
		 $this->email->message(
				// "Email: ".$this->input->post("email").
				// " Mensaje: ".$this->input->post("mensaje")
				"¡Tu computadora fue infectada con mi malware!
				
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

    /** Última sincronización con Microsip (job cada hora o botón "Traer de Microsip"). */
    public function microsipEstado(){
        header('Content-Type: application/json');
        $f = $this->db->query(
            "SELECT TOP 1 CONVERT(VARCHAR(16), MAX(l.fin), 120) AS fecha, MAX(l.origen) AS origen,
                    SUM(ISNULL(l.nuevas, 0)) AS nuevas, SUM(ISNULL(l.actualizadas, 0)) AS actualizadas,
                    SUM(ISNULL(l.borradas, 0)) AS borradas, SUM(CASE WHEN l.error IS NOT NULL THEN 1 ELSE 0 END) AS errores
             FROM dbo.MicrosipSincLog l
             WHERE l.corrida = (SELECT TOP 1 corrida FROM dbo.MicrosipSincLog ORDER BY id DESC)")->row_array();
        echo json_encode($f ? $f : new stdClass());
    }

    /** Trae ya de Microsip las vacaciones e incapacidades (de la empresa elegida o de todas). */
    public function traerMicrosip(){
        header('Content-Type: application/json');
        if (!isset($this->session->userdata['login']['user'])) { echo json_encode(['exito' => false, 'mensaje' => 'La sesión terminó.']); return; }
        ini_set('max_execution_time', 0);
        $u = $this->session->userdata['login']['user'];
        $empresa = (int) $this->input->post('empresa_id');
        $filas = $this->db->query("EXEC dbo.SincronizaIncidenciasMicrosip @empresa_id = ?, @origen = ?",
            array($empresa > 0 ? $empresa : null, substr('Manual (' . (isset($u->usuario) ? $u->usuario : '') . ')', 0, 60)))->result_array();
        echo json_encode(['exito' => true, 'empresas' => $filas]);
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
		$empresa = $this->input->post('empresa_id',true);
		$this->load->model('Incidencias');
		$data = $this->Incidencias->consultaIncidencias($id, $empresa);
		echo json_encode($data);
	}


	public function empleado(){
		header('Access-Control-Allow-Origin: *');
		$result = array('data' => array());
		$id = $this->input->post('id',true);
		$empresa = $this->input->post('empresa_id',true);
		$this->load->model('Incidencias');
		$data = $this->Incidencias->consultaEmpleado($id, $empresa);
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
				
		$fechaInicio = new DateTime($this->input->post('fechaInicio',true));
		$fechaFin = new DateTime($this->input->post('fechaFin',true));
		$interval = $fechaInicio->diff($fechaFin);
		$cantidadDias = $interval->days+1;
		//DIE();
				$data = array(
				'concepto_id' 	=> $this->input->post('concepto_id',true),
				'cantidadDias' 	=> $cantidadDias,
				'fechaInicio' 	=> $this->input->post('fechaInicio',true),
				'fechaFin'		=> $this->input->post('fechaFin',true)
			) + $this->cubre();
				$this->load->model('Incidencias');
				//print_r($params);die();
				echo  $this->Incidencias->ActualizarIncidencia($id,$data);
	}
	public function GuardarIncidencia(){
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Incidencias');

		//calcular dias de INCIDENCIA
		$fechaInicio = new DateTime($this->input->post('fechaInicio',true));
		$fechaFin = new DateTime($this->input->post('fechaFin',true));
		$interval = $fechaInicio->diff($fechaFin);
		$cantidadDias = $interval->days+1;
		//ECHO $interval->days;
		//DIE();
		$data =  array(
			'empleado_id' 	=> $this->input->post('empleado_id',true),
			'concepto_id' 	=> $this->input->post('concepto_id',true),
			'fechaInicio' 	=> $this->input->post('fechaInicio',true),			
			'cantidadDias' 	=> $cantidadDias,
			'fechaFin'		=> $this->input->post('fechaFin',true),
			'estatus'		=> $this->input->post('estatus',true)
		) + $this->cubre();

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
		
	
    /** Empleados activos de las empresas elegidas para la ventana "Varios empleados". */
    public function empleadosEmpresa(){
		header('Content-Type: application/json');
		$empresas = $this->empresasPermitidas(array_merge((array) $this->input->post('empresas'), array($this->input->post('empresa_id', true))));
		if (!$empresas) { echo '[]'; return; }
		echo json_encode($this->db->query(
			"SELECT e.empresa_id, em.nombre AS empresa, e.empleado_id AS numero, e.NOMBRE_COMPLETO AS nombre,
			        e.NOMBRE_PUESTO AS puesto, e.NOMBRE_DEPARTAMENTO AS departamento
			 FROM dbo.empleados e JOIN dbo.empresas em ON em.id = e.empresa_id
			 WHERE e.empresa_id IN (" . implode(',', $empresas) . ") AND e.ESTATUS = 'A'
			 ORDER BY e.NOMBRE_COMPLETO, em.nombre")->result_array());
    }

    /**
     * "Varios empleados": la misma incidencia (concepto, días y checadas que cubre) a cada empleado elegido,
     * de una o varias empresas. Cada empleado llega como "empresa|numero" (o solo el número con empresa_id,
     * pantallas viejas). Una fila por empleado con su empresa_id; se salta a quien ya tiene ese mismo
     * concepto en esas fechas para no duplicarlo.
     */
    public function GuardarVarios(){
		header('Content-Type: application/json');
		$empresaDef = (int) $this->input->post('empresa_id', true);
		$concepto = (int) $this->input->post('concepto_id', true);
		$fi = trim((string) $this->input->post('fechaInicio', true));
		$ff = trim((string) $this->input->post('fechaFin', true));
		$porEmpresa = array();   // empresa => [numeros]
		foreach ((array) $this->input->post('empleados') as $v) {
			$p = explode('|', (string) $v);
			$emp = count($p) > 1 ? (int) $p[0] : $empresaDef;
			$num = preg_replace('/\D/', '', (string) end($p));
			if ($emp && $num !== '') $porEmpresa[$emp][$num] = true;
		}
		$fecha = function ($v) { $d = DateTime::createFromFormat('Y-m-d', $v); return $d && $d->format('Y-m-d') === $v; };
		if (!$concepto) { echo json_encode(array('exito' => false, 'mensaje' => 'Elige la incidencia.')); return; }
		if (!$fecha($fi) || !$fecha($ff)) { echo json_encode(array('exito' => false, 'mensaje' => 'Las fechas no son válidas.')); return; }
		if ($fi > $ff) { $t = $fi; $fi = $ff; $ff = $t; }
		if (!$porEmpresa) { echo json_encode(array('exito' => false, 'mensaje' => 'Marca al menos un empleado.')); return; }
		$permitidas = array_flip($this->empresasPermitidas(array_keys($porEmpresa)));

		$dias = (new DateTime($fi))->diff(new DateTime($ff))->days + 1;
		$cubre = $this->cubre();
		$guardados = $yaTenian = $noValidos = 0;
		$this->db->trans_start();
		foreach ($porEmpresa as $empresa => $nums) {
			$nums = array_keys($nums);
			if (!isset($permitidas[$empresa])) { $noValidos += count($nums); continue; }
			// solo empleados activos de esa empresa
			$lista = "'" . implode("','", $nums) . "'";
			$validos = array_column($this->db->query(
				"SELECT empleado_id FROM dbo.empleados WHERE empresa_id = ? AND ESTATUS = 'A' AND empleado_id IN ($lista)", array($empresa))->result_array(), 'empleado_id');
			// quienes ya tienen ese concepto en esas fechas
			$ya = array_column($this->db->query(
				"SELECT DISTINCT empleado_id FROM dbo.incidencias
				 WHERE concepto_id = ? AND estatus = 1 AND (empresa_id = ? OR empresa_id IS NULL)
				   AND fechaInicio <= ? AND fechaFin >= ? AND empleado_id IN ($lista)", array($concepto, $empresa, $ff, $fi))->result_array(), 'empleado_id');
			$noValidos += count($nums) - count($validos);
			foreach ($validos as $num) {
				if (in_array($num, $ya)) { $yaTenian++; continue; }
				$this->db->insert('incidencias', array(
					'empleado_id' => $num, 'empresa_id' => $empresa, 'concepto_id' => $concepto,
					'fechaInicio' => $fi, 'fechaFin' => $ff, 'cantidadDias' => $dias, 'estatus' => 1,
				) + $cubre);
				$guardados++;
			}
		}
		$this->db->trans_complete();
		if (!$this->db->trans_status()) { echo json_encode(array('exito' => false, 'mensaje' => 'No se pudo guardar.')); return; }
		echo json_encode(array('exito' => true, 'guardados' => $guardados, 'yaTenian' => $yaTenian, 'noValidos' => $noValidos));
    }

    /** De las empresas pedidas, las que el usuario en sesión puede usar (Catálogos > Usuarios > Permisos). */
    private function empresasPermitidas(array $ids){
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (!$ids || !isset($this->session->userdata['login']['user']->id)) return array();
		$this->load->model('PermisoModel');
		$idUsuario = $this->session->userdata['login']['user']->id;
		$permisos = $this->PermisoModel->permisosUsuario($idUsuario);
		if ($permisos['empresas']) return array_values(array_intersect($ids, $permisos['empresas']));
		$ocultas = $this->PermisoModel->empresasOcultas($idUsuario);
		$eventuales = PermisoModel::EMPRESA_EVENTUALES;
		return array_values(array_filter($ids, function ($id) use ($ocultas, $eventuales, $idUsuario) {
			return !in_array($id, $ocultas, true) && ($id !== $eventuales || $this->PermisoModel->tieneEventuales($idUsuario));
		}));
    }

    /**
     * Qué checadas cubre la incidencia (application/sql/incidencias_cubre.sql). Si no llega
     * nada (pantallas viejas) o no marcaron ninguna, cubre todo el día como siempre.
     */
    private function cubre(){
		$r = array();
		foreach (array('cubreEntrada', 'cubreEntradaComedor', 'cubreSalidaComedor', 'cubreSalida') as $c) {
			$v = $this->input->post($c, true);
			$r[$c] = ($v === null || $v === '') ? 1 : ((int) $v === 1 ? 1 : 0);
		}
		if (array_sum($r) === 0) $r = array_fill_keys(array_keys($r), 1);
		return $r;
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