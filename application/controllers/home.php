<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	public function index()
	{
		$data = array();
		$login = $this->session->userdata('login');
		if (!empty($login['user']) && !empty($login['exito'])) {
			$data['user'] = $login['user'];
			$this->load->model('PermisoModel');
			$this->load->view('header',$data);
			// Invitados: sin tablero de checadas/asistencias, solo la música
			$this->load->view(PermisoModel::esInvitado($login['user']) ? 'invitadoView' : 'system',$data);
			$this->load->view('footer',$data);
			return;
		}
		// Hora en que se mostró el formulario: un robot manda el login sin abrir la página o al instante
		$this->load->model('SeguridadLogin');
		$this->session->set_userdata('loginMostrado', time());
		$this->load->view('login', array('captcha' => $this->SeguridadLogin->pideCaptcha()));
	}

	public function logout(){
		$this->session->sess_destroy();
		echo true;
	}

	/**
	 * Año y número de semana actuales, con la MISMA numeración que usan los SPs
	 * (SET DATEFIRST 1, la semana empieza en lunes). Lo usan los reportes para
	 * poner por defecto la semana actual (assets/js/semanaActual.js).
	 */
	public function semanaActual(){
		$this->db->simple_query("SET DATEFIRST 1");
		$row = $this->db->query("SELECT DATEPART(year, GETDATE()) AS anio, DATEPART(week, GETDATE()) AS semana")->row();
		$this->output->set_content_type('application/json')
			->set_output(json_encode(array('anio' => (int) $row->anio, 'semana' => (int) $row->semana)));
	}

	/** Imagen del captcha del login. */
	public function captcha()
	{
		$this->load->model('SeguridadLogin');
		$this->SeguridadLogin->imagenCaptcha();
	}

	/**
	 * Inicio de sesión. Responde JSON {exito, mensaje, captcha, recargar}.
	 * Orden: trampa de robots -> bloqueo por intentos -> captcha -> usuario y contraseña.
	 */
	public function login()
	{
		$this->load->model('Usuario');
		$this->load->model('SeguridadLogin', 'seg');
		$usuario  = substr(trim((string) $this->input->post('usuario')), 0, 50);
		$password = (string) $this->input->post('password');
		$this->session->unset_userdata('login');
		$generico = 'Usuario o contraseña incorrectos.';

		// 1) Robot: llenó el campo oculto, o no abrió la página / la mandó al instante
		$mostrado = (int) $this->session->userdata('loginMostrado');
		if ((string) $this->input->post('sitio') !== '') {
			$this->seg->registrar($usuario, false, 'ROBOT');
			return $this->responder(array('exito' => 0, 'mensaje' => $generico, 'captcha' => true));
		}
		if (!$mostrado) {   // la sesión venció con la página abierta, o nunca se abrió
			$this->seg->registrar($usuario, false, 'ROBOT');
			return $this->responder(array('exito' => 0, 'recargar' => true, 'mensaje' => 'La página caducó, se va a recargar.'));
		}
		if (time() - $mostrado < SeguridadLogin::MIN_SEGUNDOS) {
			$this->seg->registrar($usuario, false, 'ROBOT');
			return $this->responder(array('exito' => 0, 'mensaje' => $generico, 'captcha' => true));
		}

		// 2) Bloqueo por demasiados intentos (no se revisa la contraseña mientras dure)
		$bloqueo = $this->seg->bloqueo($usuario);
		if ($bloqueo) {
			$this->seg->registrar($usuario, false, $bloqueo['motivo']);
			return $this->responder(array('exito' => 0, 'mensaje' => $bloqueo['mensaje'], 'captcha' => $this->seg->pideCaptcha()));
		}

		// 3) Captcha cuando toca (siempre desde internet)
		if ($this->seg->pideCaptcha() && !$this->seg->captchaCorrecto($this->input->post('captcha'))) {
			$this->seg->registrar($usuario, false, 'CAPTCHA');
			return $this->responder(array('exito' => 0, 'mensaje' => 'Las letras de la imagen no coinciden. Intenta con la nueva imagen.', 'captcha' => true));
		}

		// 4) Usuario y contraseña
		$fila = ($usuario !== '' && $password !== '') ? $this->Usuario->login_usuario($usuario, $password) : false;
		if (!$fila) {
			$this->seg->registrar($usuario, false, 'CONTRASENA');
			$mensaje = $generico;
			if ($usuario !== '') {
				$quedan = $this->seg->quedan($usuario);
				if ($quedan <= 2) $mensaje .= $quedan > 0 ? ' Te queda(n) ' . $quedan . ' intento(s) antes de que se bloquee por ' . SeguridadLogin::VENTANA_MIN . ' minutos.'
				                                         : ' El usuario quedó bloqueado por ' . SeguridadLogin::VENTANA_MIN . ' minutos.';
			}
			return $this->responder(array('exito' => 0, 'mensaje' => $mensaje, 'captcha' => $this->seg->pideCaptcha()));
		}

		// Correcto: sesión nueva (otro identificador) para que no se pueda reutilizar una anterior
		$this->session->sess_regenerate(TRUE);
		$this->session->unset_userdata(array('loginMostrado', 'captcha'));
		$this->session->set_userdata('login', array('user' => $fila->login, 'exito' => true));
		$this->seg->registrar($fila->usuario, true, 'OK');
		return $this->responder(array('exito' => 1));
	}

	private function responder($datos)
	{
		$this->output->set_content_type('application/json')->set_output(json_encode($datos));
	}
}
