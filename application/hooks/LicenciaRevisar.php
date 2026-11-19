<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sin licencia vigente bloquea las pantallas de application/config/licencia.php
 * (licencia_bloquear), incluidas sus consultas AJAX. Todo lo demás sigue igual:
 * entrar, consultar, la pantalla de Licencia y la descarga de relojes (RelojSync).
 * Si licencia_requerida = FALSE no hace nada.
 *
 * Se registra en application/config/hooks.php, después de SesionRequerida.
 */
class LicenciaRevisar {

    public function revisar()
    {
        if (is_cli()) return;
        $CI =& get_instance();
        $CI->config->load('licencia', TRUE);
        if (!$CI->config->item('licencia_requerida', 'licencia')) return;

        $clase = strtolower($CI->router->class);
        if (!in_array($clase, (array) $CI->config->item('licencia_bloquear', 'licencia'), true)) return;

        $CI->load->model('LicenciaModel');
        try { $est = $CI->LicenciaModel->estado(); } catch (Exception $e) { $est = array('vigente' => false); }
        if (!empty($est['vigente'])) return;

        $msg = 'La licencia del sistema no está vigente. Esta función está deshabilitada hasta que se active (Soporte > Licencia).';
        if ($CI->input->is_ajax_request() || strtolower($CI->input->method()) !== 'get') {
            $CI->output->set_status_header(402)->set_content_type('application/json')
                ->set_output(json_encode(array('exito' => false, 'licencia' => false, 'mensaje' => $msg)))->_display();
            exit;
        }
        $login = $CI->session->userdata('login');
        $CI->load->view('header', array('user' => $login['user']));
        $CI->output->append_output(
            '<div class="page-wrapper"><div class="container-fluid"><div class="row"><div class="col-12">'
          . '<div class="alert alert-warning mt-4" style="font-size:16px;"><i class="fa fa-key"></i> ' . $msg . '</div>'
          . '<a href="licencia" class="btn btn-info"><i class="fa fa-key"></i> Ver licencia</a> '
          . '<a href="home" class="btn btn-secondary"><i class="fa fa-home"></i> Ir al inicio</a>'
          . '</div></div></div></div>'
        );
        $CI->load->view('footer', array('user' => $login['user']));
        $CI->output->set_status_header(402);
        $CI->output->_display();
        exit;
    }
}
