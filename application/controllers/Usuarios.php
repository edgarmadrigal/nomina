<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuarios extends CI_Controller {
	
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
		$this->load->model('Usuario');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['usuarios'] = $this->Usuario->consulta_Usuarios();
			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('Usuarios', $data);
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
	
	public function consultaPlanta()
	{ 
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Planta');
		$data = $this->Planta->consulta_Planta();
        echo json_encode($data);

	}

	function consulta()
    {
		$this->load->model('Usuario');
		if (!$this->puedeAdministrar()) { echo json_encode(false); return; }
        $id = $this->input->post('id');
        echo json_encode($this->Usuario->get_usuario($id));
    }

    public function consulta_usuario()
    {
		if (!$this->puedeAdministrar()) { echo json_encode(array('data' => array())); return; }

		$result = array('data' => array());
		$this->load->model('Usuario');
		$data =$this->Usuario->consulta();
		// "Ver como" solo lo ofrece un administrador real y cuando no está ya suplantando a alguien
		$yo = $this->usuarioActual();
		$ofreceVerComo = $yo && isset($yo->idPerfil) && $yo->idPerfil == 1 && !$this->estaSuplantando();
		$miId = $yo && isset($yo->id) ? (int) $yo->id : 0;
		foreach ($data as $key => $value) {
			$usuEsc = htmlspecialchars($value['usuario'], ENT_QUOTES);
			$verComo = ($ofreceVerComo && (int) $value['id'] !== $miId)
				? '<li><a type="button" onclick="verComo('.$value['id'].', \''.$usuEsc.'\')"><i class="fa fa-eye"></i> Ver el sistema como este usuario</a></li>'
				: '';
			// button
			$buttons = '
			<div class="btn-group" >
			  <button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Acciones <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu">
			    <li><a type="button" onclick="editar('.$value['id'].')" data-toggle="modal" data-target="#myModal_agregar">Editar</a></li>
			    <li><a type="button" onclick="cambiarPassword('.$value['id'].', '. "'".$usuEsc ."'".')">Cambiar contraseña</a></li>
			    <li><a type="button" onclick="permisos('.$value['id'].', '. "'".$usuEsc ."'".')">Permisos</a></li>
			    '.$verComo.'
			    <li><a type="button" onclick="eliminar('.$value['id'].', '. "'".$value['usuario'] ."'".'  )">Eliminar</a></li>
			  </ul>
			</div>
			';
			$result['data'][$key] = array(			
				$value['id'],			
				$value['nombre'],
				$value['usuario'],
				$value['perfil'],
				htmlspecialchars($value['planta'], ENT_QUOTES) . ((int) $value['nPlantas'] === 0
					? ' <span class="badge badge-info" style="color:#fff !important;" title="Puede ver todas las plantas">+ todas</span>'
					: ((int) $value['otrasPlantas'] > 0 ? ' <span class="badge badge-info" style="color:#fff !important;">+' . (int) $value['otrasPlantas'] . ' más</span>' : '')),
				$buttons
			);
		} // /foreach

		echo json_encode($result);
    }

    function edit()
    {
		$this->load->model('Usuario');
        $id = (int) $this->input->post('id');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'usuario' => $this->input->post('usuario'),
            'password' => (string) $this->input->post('password'),
            'idPerfil' => $this->input->post('idPerfil'),
            'idPlanta' => $this->input->post('idPlanta')
        ];
        $error = $this->revisarCambio($id, $data, false);
        if ($error) { echo json_encode(array('exito' => false, 'mensaje' => $error)); return; }
        $ok = $this->Usuario->update_usuario($id, $data);
        if ($ok) $this->guardarPlantas($id, (int) $data['idPlanta']);
        echo $ok;
    }

    function delete()
    {
		$this->load->model('Usuario');
        $id = (int) $this->input->post('id');
        $error = $this->revisarCambio($id, null, false);
        if ($error) { echo json_encode(array('exito' => false, 'mensaje' => $error)); return; }
        if ($id === (int) $this->usuarioActual()->id) { echo json_encode(array('exito' => false, 'mensaje' => 'No puedes eliminar tu propio usuario.')); return; }
        $this->load->model('PermisoModel');
        $this->PermisoModel->eliminarUsuario($id);
        echo $this->Usuario->delete_usuario($id);
    }

    function insert()
    {
		$this->load->model('Usuario');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'usuario' => $this->input->post('usuario'),
            'password' => (string) $this->input->post('password'),
            'idPerfil' => $this->input->post('idPerfil'),
            'idPlanta' => $this->input->post('idPlanta')
        ];
        $error = $this->revisarCambio(0, $data, true);
        if ($error) { echo json_encode(array('exito' => false, 'mensaje' => $error)); return; }

        $dt['exito'] = $this->Usuario->insert_usuario($data);
        if ($dt['exito']) {
            $nuevo = $this->db->query("SELECT id FROM dbo.usuarios WHERE usuario = ?", array(trim((string) $data['usuario'])))->row_array();
            if ($nuevo) $this->guardarPlantas((int) $nuevo['id'], (int) $data['idPlanta']);
        }
        echo $dt['exito'];
    }
	


    /** Plantas que puede ver el usuario (dbo.UsuarioEmpresa; vacío = todas). */
    public function plantasUsuario()
    {
		header('Content-Type: application/json');
		if (!$this->puedeAdministrar()) { echo json_encode(array('empresas' => array())); return; }
		$this->load->model('PermisoModel');
		$p = $this->PermisoModel->permisosUsuario((int) $this->input->post('id'), true);
		echo json_encode(array('empresas' => $p['empresas']));
    }

    /**
     * Guarda las plantas que puede ver (post 'plantas': '' = todas, o ids separados por coma).
     * La principal siempre va incluida. Si no llega el campo (pantalla vieja en caché) no se toca nada.
     */
    private function guardarPlantas($id, $principal)
    {
		$txt = $this->input->post('plantas');
		if ($txt === null) return;
		$ids = array_values(array_filter(array_map('intval', explode(',', (string) $txt))));
		if ($ids && $principal > 0 && !in_array($principal, $ids, true)) $ids[] = $principal;
		$this->load->model('PermisoModel');
		$this->PermisoModel->guardarEmpresas($id, $ids);
    }

    /**
     * Solo cambia la contraseña del usuario (la anterior no se puede ver: está cifrada).
     * Mismas reglas que al editar: hay que poder administrar, y solo un administrador cambia
     * la contraseña de otro administrador.
     */
    function cambiarPassword()
    {
		header('Content-Type: application/json');
		$this->load->model('Usuario');
		$id = (int) $this->input->post('id');
		$password = (string) $this->input->post('password');
		if ($id <= 0) { echo json_encode(array('exito' => false, 'mensaje' => 'Falta el usuario.')); return; }
		if (!$this->puedeAdministrar()) { echo json_encode(array('exito' => false, 'mensaje' => 'No tienes permiso para administrar usuarios.')); return; }
		$yo = $this->usuarioActual();
		$soyAdmin = isset($yo->idPerfil) && $yo->idPerfil == 1;
		if (!$soyAdmin) {
			$actual = $this->db->query("SELECT idPerfil FROM dbo.usuarios WHERE id = ?", array($id))->row_array();
			if ($actual && (int) $actual['idPerfil'] === 1) { echo json_encode(array('exito' => false, 'mensaje' => 'Solo un administrador puede cambiar la contraseña de otro administrador.')); return; }
		}
		if (strlen($password) < 8) { echo json_encode(array('exito' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.')); return; }
		$this->Usuario->cambiarPassword($id, $password);
		echo json_encode(array('exito' => true, 'mensaje' => 'Contraseña cambiada. Pásasela al usuario para que entre.'));
    }

    // ---------------- Ver el sistema como otro usuario (suplantar) ----------------

    /**
     * Entra temporalmente como otro usuario para ver el sistema con SUS permisos, menú y plantas,
     * sin pedir su contraseña. Guarda al administrador real en la sesión (clave 'suplantacion')
     * para poder regresar con volver(). Toda la sesión 'login' pasa a ser el usuario destino, así
     * que el menú y los hooks de permiso lo tratan igual que si él hubiera entrado.
     * Solo lo puede iniciar un administrador real y nunca encadenado (no se suplanta estando suplantando).
     */
    public function suplantar()
    {
		header('Content-Type: application/json');
		$this->load->model('Usuario');
		$yo = $this->usuarioActual();
		if (!$yo || !isset($yo->idPerfil) || $yo->idPerfil != 1) { echo json_encode(array('exito' => false, 'mensaje' => 'Solo un administrador puede ver el sistema como otro usuario.')); return; }
		if ($this->estaSuplantando()) { echo json_encode(array('exito' => false, 'mensaje' => 'Ya estás viendo como otro usuario. Primero vuelve a tu cuenta.')); return; }
		$id = (int) $this->input->post('id');
		if ($id <= 0) { echo json_encode(array('exito' => false, 'mensaje' => 'Falta el usuario.')); return; }
		if ($id === (int) $yo->id) { echo json_encode(array('exito' => false, 'mensaje' => 'Ya estás usando tu propia cuenta.')); return; }
		$destino = $this->Usuario->get_usuario($id);
		if (!$destino || !isset($destino->id)) { echo json_encode(array('exito' => false, 'mensaje' => 'Ese usuario ya no existe.')); return; }

		// Objeto usuario con la misma forma que arma el login normal
		$destino->loged = true;
		$destino->login = $destino;

		$this->session->set_userdata('suplantacion', array(
			'admin'   => $yo,                                  // el administrador real, para regresar
			'adminNombre'  => isset($yo->nombre) ? $yo->nombre : $yo->usuario,
			'adminUsuario' => isset($yo->usuario) ? $yo->usuario : '',
			'destinoNombre'  => isset($destino->nombre) ? $destino->nombre : $destino->usuario,
			'destinoUsuario' => isset($destino->usuario) ? $destino->usuario : '',
			'desde' => time(),
		));
		$login = (array) $this->session->userdata('login');
		$login['user'] = $destino;
		$login['exito'] = true;
		$this->session->set_userdata('login', $login);
		echo json_encode(array('exito' => true));
    }

    /**
     * Regresa el administrador real a su propia cuenta. Lo puede llamar cualquiera que esté
     * suplantando (el usuario destino quizá no sea administrador), por eso solo exige que exista
     * la clave 'suplantacion' en la sesión.
     */
    public function volver()
    {
		header('Content-Type: application/json');
		$sup = $this->session->userdata('suplantacion');
		if (empty($sup) || empty($sup['admin'])) { echo json_encode(array('exito' => false, 'mensaje' => 'No estás viendo como otro usuario.')); return; }
		$login = (array) $this->session->userdata('login');
		$login['user'] = $sup['admin'];
		$login['exito'] = true;
		$this->session->set_userdata('login', $login);
		$this->session->unset_userdata('suplantacion');
		echo json_encode(array('exito' => true));
    }

    /** ¿El administrador está ahora mismo viendo como otro usuario? */
    private function estaSuplantando()
    {
		$sup = $this->session->userdata('suplantacion');
		return !empty($sup) && !empty($sup['admin']);
    }

    // ---------------- Permisos por usuario ----------------

    /** Opciones del menú, empresas y lo que tiene asignado el usuario. */
    public function permisos()
    {
		header('Access-Control-Allow-Origin: *');
		if (!$this->esAdmin()) return;
		$this->load->model('PermisoModel');
		$id = (int) $this->input->post('id');
		echo json_encode(array(
			'exito'    => true,
			'opciones' => $this->PermisoModel->opciones(),
			'empresas' => $this->PermisoModel->empresas(),
			'usuario'  => $this->PermisoModel->permisosUsuario($id, true),
			'usuarios' => $this->PermisoModel->usuarios(),
		));
    }

    /** Permisos de otro usuario, para copiarlos. */
    public function permisosDe()
    {
		header('Access-Control-Allow-Origin: *');
		if (!$this->esAdmin()) return;
		$this->load->model('PermisoModel');
		echo json_encode(array('exito' => true, 'usuario' => $this->PermisoModel->permisosUsuario((int) $this->input->post('id'), true)));
    }

    /** Guarda opciones[] y empresas[] del usuario (empresas vacío = todas). */
    public function guardarPermisos()
    {
		header('Access-Control-Allow-Origin: *');
		if (!$this->esAdmin()) return;
		$this->load->model('PermisoModel');
		$id = (int) $this->input->post('id');
		if ($id <= 0) {
			echo json_encode(array('exito' => false, 'mensaje' => 'Falta el usuario.'));
			return;
		}
		$opciones = array_map('intval', (array) $this->input->post('opciones'));
		$empresas = array_map('intval', (array) $this->input->post('empresas'));
		$ok = $this->PermisoModel->guardar($id, $opciones, $empresas);
		echo json_encode(array('exito' => (bool) $ok, 'mensaje' => $ok ? 'Permisos guardados.' : 'No se pudieron guardar los permisos.'));
    }

    private function usuarioActual()
    {
		return isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
    }

    /** Administra usuarios el administrador o quien tenga la opción Usuarios en sus permisos. */
    private function puedeAdministrar()
    {
		$u = $this->usuarioActual();
		if (!$u) return false;
		if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
		$r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion m ON m.id = p.idOpcion
		                       WHERE p.idUsuario = ? AND m.activo = 1 AND LOWER(m.url) = 'usuarios'", array((int) $u->id))->row_array();
		return $r && (int) $r['n'] > 0;
    }

    /**
     * Reglas al guardar o eliminar: hay que poder administrar usuarios, solo un administrador
     * crea/edita/elimina administradores, y la contraseña nueva debe tener al menos 8 caracteres.
     * Regresa el mensaje de error o null.
     */
    private function revisarCambio($id, $data, $nuevo)
    {
		if (!$this->puedeAdministrar()) return 'No tienes permiso para administrar usuarios.';
		$yo = $this->usuarioActual();
		$soyAdmin = isset($yo->idPerfil) && $yo->idPerfil == 1;
		if (!$soyAdmin) {
			if ($data !== null && (int) $data['idPerfil'] === 1) return 'Solo un administrador puede dar el perfil de Administrador.';
			if ($id > 0) {
				$actual = $this->db->query("SELECT idPerfil FROM dbo.usuarios WHERE id = ?", array($id))->row_array();
				if ($actual && (int) $actual['idPerfil'] === 1) return 'Solo un administrador puede cambiar a otro administrador.';
			}
		}
		if ($data === null) return null;
		if (trim((string) $data['usuario']) === '') return 'Falta el usuario.';
		if ($nuevo || $data['password'] !== '') {
			if (strlen($data['password']) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
		}
		$dup = $this->db->query("SELECT COUNT(*) AS n FROM dbo.usuarios WHERE usuario = ? AND id <> ?", array(trim((string) $data['usuario']), (int) $id))->row_array();
		if ($dup && (int) $dup['n'] > 0) return 'Ya existe otro usuario con ese nombre de usuario.';
		return null;
    }

    /** Solo el administrador (idPerfil = 1) administra permisos. */
    private function esAdmin()
    {
		$u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
		if ($u && isset($u->idPerfil) && $u->idPerfil == 1) return true;
		echo json_encode(array('exito' => false, 'mensaje' => 'Solo el administrador puede cambiar permisos.'));
		return false;
    }

}