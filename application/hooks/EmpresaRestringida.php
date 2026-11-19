<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Empresas restringidas (empresas.restringida = 1, p. ej. IBF): nadie las consulta en ningún reporte o
 * pantalla a menos que tenga un permiso de menú que las otorgue (MenuOpcion.empresaRestringida),
 * aunque sea administrador. Los combos ya no las muestran (PermisoModel), y este hook rechaza
 * cualquier petición que de todos modos mande una de esas empresas en sus parámetros de empresa/planta.
 *
 * Se registra en application/config/hooks.php (post_controller_constructor), después de SesionRequerida.
 */
class EmpresaRestringida {

    /** Parámetros (POST o GET) que traen una empresa/planta en las pantallas del sistema. */
    private $parametros = array('empresa_id', 'empresa', 'planta', 'idPlanta', 'idEmpresa', 'id_empresa', 'plantas', 'empresas');

    /** Pantallas donde se ADMINISTRA a quién se le da la empresa (no consultan sus datos). */
    private $libres = array('usuarios', 'home');

    public function revisar()
    {
        if (is_cli()) return;
        $CI =& get_instance();
        if (in_array(strtolower($CI->router->class), $this->libres, true)) return;
        $login = $CI->session->userdata('login');
        if (empty($login['user'])) return;   // sin sesión ya lo resuelve SesionRequerida

        $pedidas = array();
        foreach ($this->parametros as $p) {
            foreach (array($CI->input->post($p), $CI->input->get($p)) as $v) {
                if ($v === null || $v === '') continue;
                foreach ((array) $v as $x) {
                    foreach (preg_split('/[,\s]+/', (string) $x) as $id) if (ctype_digit($id)) $pedidas[] = (int) $id;
                }
            }
        }
        if (!$pedidas) return;

        $CI->load->model('PermisoModel');
        if (!array_intersect($pedidas, $CI->PermisoModel->empresasOcultasSesion())) return;

        $mensaje = 'No tienes permiso para consultar esa empresa.';
        if ($CI->input->is_ajax_request() || strtolower($CI->input->method()) !== 'get') {
            $CI->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('exito' => false, 'Exito' => false, 'mensaje' => $mensaje, 'Mensaje' => $mensaje)))
                ->_display();
            exit;
        }
        show_error($mensaje, 403);
    }
}
