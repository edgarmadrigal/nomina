<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bloquea la entrada por liga a pantallas del menú para las que el usuario no
 * tiene permiso (Catálogos > Usuarios > Permisos).
 *
 * Solo revisa la carga de la pantalla (método index). Las consultas internas
 * (AJAX) no se bloquean porque varias pantallas comparten endpoints, por ejemplo
 * checadasBiostar/consultaPlanta llena el combo de Planta de casi todo el sistema.
 *
 * Se registra en application/config/hooks.php (post_controller_constructor).
 */
class PermisoAcceso {

    // Lo único que puede usar el perfil Invitados (controladores): inicio/sesión, música, chat y el tutorial
    private static $soloInvitado = array('home', 'musica', 'chat', 'tutorial');

    public function revisar()
    {
        $CI =& get_instance();
        $this->revisarInvitado($CI);
        if ($CI->router->method !== 'index') return;

        // Sin sesión: cada controlador ya manda al login
        $login = $CI->session->userdata('login');
        if (empty($login['user']) || empty($login['exito'])) return;
        $user = $login['user'];

        $segmento = (string) $CI->uri->segment(1);
        if ($segmento === '') return;

        // ¿La dirección es una opción del menú?
        $opcion = $CI->db->query(
            "SELECT id, nombre, url FROM dbo.MenuOpcion WHERE activo = 1 AND LOWER(url) = LOWER(?)",
            array($segmento)
        )->row_array();
        if (!$opcion) return;   // pantallas que no están en el menú (home, login, etc.)

        // El administrador siempre puede entrar a Usuarios (para no quedarse sin acceso)
        if (strtolower($opcion['url']) === 'usuarios' && isset($user->idPerfil) && $user->idPerfil == 1) return;

        $tiene = $CI->db->query(
            "SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso WHERE idUsuario = ? AND idOpcion = ?",
            array(isset($user->id) ? $user->id : 0, $opcion['id'])
        )->row_array();
        if ($tiene && (int) $tiene['n'] > 0) return;

        // Sin permiso: aviso dentro del diseño normal del sistema
        $CI->load->view('header', array('user' => $user));
        $CI->output->append_output(
            '<div class="page-wrapper"><div class="container-fluid"><div class="row"><div class="col-12">'
          . '<div class="alert alert-danger mt-4" style="font-size:16px;">'
          . '<i class="fa fa-lock"></i> <b>Acceso denegado.</b> No tienes permiso para entrar a <b>'
          . htmlspecialchars($opcion['nombre']) . '</b>.<br>'
          . '<small>Si lo necesitas, pide al administrador que te lo asigne en Catálogos &gt; Usuarios &gt; Permisos.</small>'
          . '</div><a href="home" class="btn btn-info"><i class="fa fa-home"></i> Ir al inicio</a>'
          . '</div></div></div></div>'
        );
        $CI->load->view('footer', array('user' => $user));
        $CI->output->set_status_header(403);
        $CI->output->_display();
        exit;
    }

    /**
     * El perfil Invitados (música y chat) no ve checadas, asistencias ni nada más aunque tenga permisos marcados:
     * fuera de $soloInvitado se bloquea la pantalla y también las consultas internas (AJAX).
     */
    private function revisarInvitado($CI)
    {
        $login = $CI->session->userdata('login');
        if (empty($login['user']) || empty($login['exito'])) return;
        $CI->load->model('PermisoModel');
        if (!PermisoModel::esInvitado($login['user'])) return;
        if (in_array(strtolower($CI->router->class), self::$soloInvitado, true)) return;

        $CI->output->set_status_header(403);
        if ($CI->router->method !== 'index' || $CI->input->is_ajax_request()) {
            $CI->output->set_content_type('application/json')
                ->set_output(json_encode(array('exito' => false, 'mensaje' => 'Como invitado solo tienes acceso a la música y al chat.')));
            $CI->output->_display();
            exit;
        }
        $CI->load->view('header', array('user' => $login['user']));
        $CI->output->append_output(
            '<div class="page-wrapper"><div class="container-fluid"><div class="row"><div class="col-12">'
          . '<div class="alert alert-info mt-4" style="font-size:16px;">'
          . '<i class="fa fa-music"></i> Como invitado solo tienes acceso a la música (radio y YouTube) y al chat.'
          . '</div><a href="home" class="btn btn-info"><i class="fa fa-home"></i> Ir al inicio</a>'
          . '</div></div></div></div>'
        );
        $CI->load->view('footer', array('user' => $login['user']));
        $CI->output->_display();
        exit;
    }
}
