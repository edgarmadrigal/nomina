<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tutorial guiado que sale al entrar al sistema (assets/js/tutorial.js) hasta que el usuario
 * lo termina o lo salta; se guarda en dbo.UsuarioTutorial (ver application/sql/tutorial.sql).
 * Para volver a mostrarlo a todos (por ejemplo, con cambios nuevos) se sube VERSION.
 */
class Tutorial extends CI_Controller {

    const NOMBRE  = 'novedades';
    const VERSION = '2026-11-23';

    /** ¿Se le muestra al usuario? */
    public function estado(){
        $u = $this->usuario();
        if (!$u) { $this->json(array('mostrar' => false)); return; }
        $r = $this->db->query("SELECT version FROM dbo.UsuarioTutorial WHERE idUsuario = ? AND tutorial = ?",
                              array((int) $u->id, self::NOMBRE))->row_array();
        // 'vista' = versión que ya había visto: el JS le enseña solo las novedades posteriores a esa
        $this->json(array('mostrar' => !$r || $r['version'] !== self::VERSION, 'vista' => $r ? $r['version'] : null,
                          'version' => self::VERSION, 'esAdmin' => isset($u->idPerfil) && $u->idPerfil == 1));
    }

    /** Lo terminó o lo saltó: ya no se le vuelve a mostrar solo (puede verlo desde su menú). */
    public function terminar(){
        $u = $this->usuario();
        if (!$u) { $this->json(array('exito' => false)); return; }
        $resultado = $this->input->post('resultado') === 'omitido' ? 'omitido' : 'terminado';
        $this->db->query(
            "IF EXISTS (SELECT 1 FROM dbo.UsuarioTutorial WHERE idUsuario = ? AND tutorial = ?)
                 UPDATE dbo.UsuarioTutorial SET version = ?, resultado = ?, fecha = GETDATE() WHERE idUsuario = ? AND tutorial = ?
             ELSE
                 INSERT INTO dbo.UsuarioTutorial (idUsuario, tutorial, version, resultado) VALUES (?, ?, ?, ?)",
            array((int) $u->id, self::NOMBRE, self::VERSION, $resultado, (int) $u->id, self::NOMBRE,
                  (int) $u->id, self::NOMBRE, self::VERSION, $resultado));
        $this->json(array('exito' => true));
    }

    private function usuario(){
        $l = $this->session->userdata('login');
        return (!empty($l['exito']) && !empty($l['user'])) ? $l['user'] : null;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
