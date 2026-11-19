<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sin sesión iniciada no responde nada del sistema, salvo el login.
 *
 * Antes cada controlador revisaba la sesión solo en su pantalla (index) y las consultas
 * internas (AJAX) respondían a cualquiera, por ejemplo usuarios/consulta regresaba la
 * contraseña de cualquier usuario sin haber entrado.
 *
 * - Llamadas AJAX / POST sin sesión: 401 con JSON {sesion:false}; header.php manda al login.
 * - Pantallas (GET) sin sesión: se redirige al login (home).
 *
 * Se registra en application/config/hooks.php (post_controller_constructor), antes de PermisoAcceso.
 */
class SesionRequerida {

    /** Lo único que se puede usar sin sesión: controlador => métodos. */
    private $libres = array(
        'home' => array('index', 'login', 'logout', 'captcha'),
    );

    public function revisar()
    {
        if (is_cli()) return;
        $CI =& get_instance();
        $clase  = strtolower($CI->router->class);
        $metodo = strtolower($CI->router->method);
        if (isset($this->libres[$clase]) && in_array($metodo, $this->libres[$clase], true)) return;

        $login = $CI->session->userdata('login');
        if (!empty($login['user']) && !empty($login['exito'])) return;

        if ($CI->input->is_ajax_request() || strtolower($CI->input->method()) !== 'get') {
            $CI->output->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('exito' => false, 'sesion' => false, 'mensaje' => 'Tu sesión terminó. Vuelve a iniciar sesión.')))
                ->_display();
            exit;
        }
        header('Location: ' . base_url('home'), true, 302);
        exit;
    }
}
