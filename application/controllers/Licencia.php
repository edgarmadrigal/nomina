<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Soporte > Licencia: número de serie, estado y vencimiento de la licencia.
 * El administrador solicita los códigos (llegan por correo al proveedor con una opción
 * por cada tiempo: 30/90/180/365 días o indefinida, más una para desactivar) y captura
 * el que le den. Config: application/config/licencia.php.
 */
class Licencia extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('LicenciaModel');
    }

    public function index(){
        $user = $this->session->userdata['login']['user'];
        $data = array('user' => $user, 'esAdmin' => $this->esAdmin(false));
        try { $data['estado'] = $this->LicenciaModel->estado(); }
        catch (Exception $e) { $data['estado'] = array('vigente' => false, 'serie' => '', 'mensaje' => 'Falta crear dbo.Licencia (application/sql/licencia.sql)'); }
        $data['avisoDias'] = (int) $this->LicenciaModel->cfg('aviso_dias');
        $data['movimientos'] = $data['esAdmin'] && empty($data['estado']['mensaje']) ? $this->LicenciaModel->movimientos() : array();
        $this->load->view('header', $data);
        $this->load->view('licenciaView', $data);
        $this->load->view('footer', $data);
    }

    /** Genera una solicitud nueva y manda el correo con los códigos. */
    public function solicitar(){
        if (!$this->esAdmin()) return;
        $smtp = $this->LicenciaModel->cfg('smtp');
        if (empty($smtp['smtp_user']) || empty($smtp['smtp_pass']) || !$this->LicenciaModel->cfg('secreto')) {
            return $this->json(false, 'Falta configurar la cuenta de correo (application/config/licencia.php).');
        }
        $user = $this->session->userdata['login']['user'];
        $s = $this->LicenciaModel->nuevaSolicitud($user->usuario);
        $est = $this->LicenciaModel->estado();

        $filas = '';
        foreach ($s['codigos'] as $op => $cod) {
            $txt = $op === 'X' ? '<b style="color:#b00">Desactivar el sistema</b>' : ($op === '0' ? '<b>Indefinida</b>' : $op . ' días');
            $filas .= '<tr><td style="padding:6px 12px;border:1px solid #ccc">' . $txt . '</td>'
                    . '<td style="padding:6px 12px;border:1px solid #ccc;font:bold 18px monospace">' . $cod . '</td></tr>';
        }
        $esc = function ($v) { return htmlspecialchars((string) $v); };
        $html = '<p>Se solicitó un código de licencia del sistema de Nóminas.</p>'
              . '<p><b>Número de serie:</b> ' . $esc($s['serie']) . '<br><b>Servidor:</b> ' . $esc($est['servidor'])
              . ' / ' . $esc($est['base']) . '<br><b>Solicitó:</b> ' . $esc($user->usuario) . ' (' . $esc($user->nombre) . ')'
              . '<br><b>Estado actual:</b> ' . ($est['vigente'] ? ($est['indefinida'] ? 'activa indefinida' : 'activa hasta ' . $esc($est['venceEl'])) : 'inactiva')
              . '</p><p><b>¿Por cuánto tiempo se activa?</b> Envía al cliente solo el código de la opción elegida:</p>'
              . '<table style="border-collapse:collapse">' . $filas . '</table>'
              . '<p style="color:#777">Solicitud ' . $esc($s['solicitud']) . '. Los códigos sirven una sola vez y dejan de valer si se pide otra solicitud.'
              . ' Si la licencia sigue vigente, los días se suman al vencimiento actual.</p>';

        $this->load->library('email');
        $this->email->initialize(array_merge($smtp, array('protocol' => 'smtp', 'mailtype' => 'html', 'charset' => 'utf-8',
                                                          'newline' => "\r\n", 'crlf' => "\r\n", 'smtp_timeout' => 20)));
        $this->email->from($smtp['smtp_user'], 'Nóminas - Licencia');
        $this->email->to($this->LicenciaModel->cfg('correo_destino'));
        $this->email->subject('Licencia Nóminas ' . $s['serie'] . ' - solicitud ' . $s['solicitud']);
        $this->email->message($html);
        if (!$this->email->send(FALSE)) {
            log_message('error', 'Licencia: no se pudo enviar el correo: ' . $this->email->print_debugger(array('headers')));
            return $this->json(false, 'No se pudo enviar el correo. Revisa la cuenta en application/config/licencia.php.');
        }
        $this->json(true, 'Solicitud enviada. Captura el código que te proporcione el proveedor.');
    }

    /** Crea las tablas (si faltan) y deja la licencia indefinida. Solo admin. */
    public function instalar(){
        if (!$this->esAdmin()) return;
        $user = $this->session->userdata['login']['user'];
        try {
            $this->LicenciaModel->instalar();
            $serie = $this->LicenciaModel->activarIndefinida($user->usuario);
        } catch (Exception $e) {
            log_message('error', 'Licencia instalar: ' . $e->getMessage());
            return $this->json(false, 'No se pudieron crear las tablas: ' . $e->getMessage());
        }
        $this->json(true, 'Listo. Serie ' . $serie . ', licencia activada (indefinida).');
    }

    public function aplicar(){
        if (!$this->esAdmin()) return;
        $user = $this->session->userdata['login']['user'];
        list($ok, $msg) = $this->LicenciaModel->aplicar($this->input->post('codigo'), $user->usuario);
        $this->json($ok, $msg);
    }

    private function esAdmin($responder = true){
        $u = $this->session->userdata['login']['user'];
        $es = isset($u->idPerfil) && $u->idPerfil == 1;
        if (!$es && $responder) $this->json(false, 'Solo el administrador puede hacer esto.');
        return $es;
    }

    private function json($exito, $mensaje){
        $this->output->set_content_type('application/json')
             ->set_output(json_encode(array('exito' => $exito, 'mensaje' => $mensaje)));
    }
}
