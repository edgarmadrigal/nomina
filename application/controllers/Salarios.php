<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogos > Salarios (dbo.salarioPorAnio, ver application/sql/salarios.sql).
 * Salarios por año con su importe con letra, que usa Reporte Contrato.
 * No se borran: se dan de baja (activo = 0) para no romper contratos que ya los usan.
 */
class Salarios extends CI_Controller {

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('salariosView', $data);
        $this->load->view('footer', $data);
    }

    public function lista(){
        $filas = $this->db->query(
            "SELECT id, salario, [año] AS anio, LTRIM(RTRIM(REPLACE(REPLACE(descripcion, CHAR(13), ''), CHAR(10), ''))) AS descripcion, activo
             FROM dbo.salarioPorAnio ORDER BY [año] DESC, salario DESC, id DESC")->result_array();
        foreach ($filas as &$f) { $f['id'] = (int) $f['id']; $f['anio'] = (int) $f['anio']; $f['salario'] = (float) $f['salario']; $f['activo'] = (int) $f['activo']; }
        $this->json(array('exito' => true, 'salarios' => $filas, 'puedeEditar' => $this->puedeEditar(false)));
    }

    public function guardar(){
        if (!$this->puedeEditar()) return;
        $id = (int) $this->input->post('id');
        $salario = round((float) str_replace(',', '', (string) $this->input->post('salario')), 2);
        $anio = (int) $this->input->post('anio');
        $desc = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $this->input->post('descripcion'))));
        if ($salario <= 0 || $salario >= 100000) { $this->json(array('exito' => false, 'mensaje' => 'Escribe un salario válido (mayor que 0).')); return; }
        if ($anio < 1990 || $anio > 2100) { $this->json(array('exito' => false, 'mensaje' => 'Escribe un año válido.')); return; }
        if ($desc === '' || strlen($desc) > 150) { $this->json(array('exito' => false, 'mensaje' => 'Falta el importe con letra (máximo 150 caracteres).')); return; }
        $dup = $this->db->query("SELECT COUNT(*) AS n FROM dbo.salarioPorAnio WHERE salario = ? AND [año] = ? AND id <> ? AND activo = 1",
                                array($salario, $anio, $id))->row_array();
        if ((int) $dup['n'] > 0) { $this->json(array('exito' => false, 'mensaje' => 'Ya existe ese salario en ' . $anio . '.')); return; }
        if ($id > 0) {
            $this->db->query("UPDATE dbo.salarioPorAnio SET salario = ?, [año] = ?, descripcion = ? WHERE id = ?", array($salario, $anio, $desc, $id));
            $this->json(array('exito' => true, 'mensaje' => 'Salario actualizado.'));
        } else {
            $this->db->query("INSERT INTO dbo.salarioPorAnio (salario, [año], descripcion, activo) VALUES (?, ?, ?, 1)", array($salario, $anio, $desc));
            $this->json(array('exito' => true, 'mensaje' => 'Salario agregado.'));
        }
    }

    /** Baja o reactivación (no se borra). */
    public function activar(){
        if (!$this->puedeEditar()) return;
        $activo = (int) $this->input->post('activo') === 1 ? 1 : 0;
        $this->db->query("UPDATE dbo.salarioPorAnio SET activo = ? WHERE id = ?", array($activo, (int) $this->input->post('id')));
        $this->json(array('exito' => true, 'mensaje' => $activo ? 'Salario reactivado.' : 'Salario dado de baja. Ya no sale para elegir en los contratos.'));
    }

    // ------------------------------------------------------------------

    /** Edita el administrador o quien tenga la opción "Salarios" en sus permisos. */
    private function puedeEditar($responder = true){
        $u = $this->session->userdata['login']['user'];
        $ok = isset($u->idPerfil) && $u->idPerfil == 1;
        if (!$ok) {
            $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                                   WHERE p.idUsuario = ? AND o.url = 'salarios' AND o.activo = 1", array((int) $u->id))->row_array();
            $ok = (int) $r['n'] > 0;
        }
        if (!$ok && $responder) $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso para cambiar salarios.'));
        return $ok;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
