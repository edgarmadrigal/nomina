<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "IP pública" del menú de usuario del administrador (assets/js/ipPublica.js).
 * Cambia en dbo.ConfigSistema la IP pública que usan los enlaces de descarga
 * (helpers/ip_publica_helper.php). Solo el administrador (idPerfil = 1).
 */
class IpPublica extends CI_Controller {

    public function estado() {
        if (!$this->esAdmin()) { $this->json(array('exito' => false, 'mensaje' => 'Solo el administrador.')); return; }
        $filas = $this->db->query("SELECT clave, valor, CONVERT(VARCHAR(16), fechaModifico, 120) AS fecha, usuarioModifico AS usuario
                                   FROM dbo.ConfigSistema WHERE clave IN ('ip_publica', 'ip_publica_nueva', 'ip_publica_anterior')")->result_array();
        $d = array();
        foreach ($filas as $f) $d[$f['clave']] = $f;
        $this->json(array('exito' => true,
            'actual'   => isset($d['ip_publica']) ? $d['ip_publica']['valor'] : '177.244.42.94',
            'fecha'    => isset($d['ip_publica']) ? $d['ip_publica']['fecha'] : null,
            'usuario'  => isset($d['ip_publica']) ? $d['ip_publica']['usuario'] : null,
            'nueva'    => isset($d['ip_publica_nueva']) ? $d['ip_publica_nueva']['valor'] : '201.165.179.106',
            'anterior' => isset($d['ip_publica_anterior']) ? $d['ip_publica_anterior']['valor'] : null));
    }

    public function cambiar() {
        if (!$this->esAdmin() || $this->input->method() !== 'post') { $this->json(array('exito' => false, 'mensaje' => 'Solo el administrador.')); return; }
        $ip = trim((string) $this->input->post('ip', true));
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $this->json(array('exito' => false, 'mensaje' => 'IP no válida.')); return; }
        $u = $this->session->userdata('login')['user'];
        $usuario = isset($u->usuario) ? $u->usuario : '';

        $actual = $this->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = 'ip_publica'")->row_array();
        $actual = $actual ? $actual['valor'] : '177.244.42.94';
        if ($actual === $ip) { $this->json(array('exito' => true, 'mensaje' => 'Ya estaba en ' . $ip . '.')); return; }

        $this->db->trans_start();
        foreach (array('ip_publica_anterior' => $actual, 'ip_publica' => $ip) as $clave => $valor) {
            $this->db->query("IF EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = ?)
                                  UPDATE dbo.ConfigSistema SET valor = ?, fechaModifico = GETDATE(), usuarioModifico = ? WHERE clave = ?
                              ELSE
                                  INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES (?, ?, GETDATE(), ?)",
                             array($clave, $valor, $usuario, $clave, $clave, $valor, $usuario));
        }
        $this->db->trans_complete();
        $ok = $this->db->trans_status();
        $this->json(array('exito' => (bool) $ok, 'mensaje' => $ok ? 'Listo: ahora todo apunta a ' . $ip . ' (antes ' . $actual . ').' : 'No se pudo guardar.'));
    }

    private function esAdmin() {
        $l = $this->session->userdata('login');
        return !empty($l['exito']) && !empty($l['user']) && isset($l['user']->idPerfil) && $l['user']->idPerfil == 1;
    }

    private function json($d) {
        $this->output->set_content_type('application/json')->set_output(json_encode($d, JSON_UNESCAPED_UNICODE));
    }
}
