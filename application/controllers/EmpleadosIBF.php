<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogos > Empleados IBF. IBF no está en Microsip: sus empleados se capturan aquí
 * (dbo.empleados con empresa_id de la empresa 'IBF'). La bitácora guarda cada cambio (trigger).
 */
class EmpleadosIBF extends CI_Controller {

    const EMPRESA = 'IBF';

    /** IBF es empresa restringida: solo entra quien tiene el permiso de IBF (aunque sea administrador). */
    public function __construct(){
        parent::__construct();
        if (is_cli() || !isset($this->session->userdata['login']['user'])) return;
        $this->load->model('PermisoModel');
        $emp = $this->empresaId();
        if ($emp && !$this->PermisoModel->puedeVerEmpresa($emp)) {
            if ($this->input->method() === 'get') show_error('No tienes permiso para ver IBF.', 403);
            $this->output->set_status_header(403)->set_content_type('application/json')
                 ->set_output(json_encode(array('exito' => false, 'mensaje' => 'No tienes permiso para ver IBF.')))->_display();
            exit;
        }
    }

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('EmpleadosIBFView', $data);
        $this->load->view('footer', $data);
    }

    /** Todos los empleados de IBF (activos y bajas) + puestos y departamentos ya usados (para sugerir). */
    public function lista(){
        $emp = $this->empresaId();
        if (!$emp) { $this->json(array('exito' => false, 'mensaje' => 'No existe la empresa IBF.')); return; }
        $filas = $this->db->query(
            "SELECT id, empleado_id AS numero, NOMBRE_COMPLETO AS nombreCompleto, APELLIDO_PATERNO AS paterno, APELLIDO_MATERNO AS materno,
                    NOMBRES AS nombres, NOMBRE_PUESTO AS puesto, NOMBRE_DEPARTAMENTO AS departamento, RFC AS rfc, REG_IMSS AS imss,
                    CONVERT(VARCHAR(10), FECHA_INGRESO, 23) AS fechaIngreso, ESTATUS AS estatus
             FROM dbo.empleados WHERE empresa_id = ? ORDER BY APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRES", array($emp))->result_array();
        $puestos = array_values(array_unique(array_filter(array_column($filas, 'puesto'))));
        $deptos  = array_values(array_unique(array_filter(array_column($filas, 'departamento'))));
        sort($puestos); sort($deptos);
        $this->json(array('exito' => true, 'filas' => $filas, 'puestos' => $puestos, 'departamentos' => $deptos));
    }

    /** Alta o edición (con id). El nombre completo se arma como en Microsip: PATERNO/MATERNO,NOMBRES. */
    public function guardar(){
        $emp = $this->empresaId();
        if (!$emp) { $this->json(array('exito' => false, 'mensaje' => 'No existe la empresa IBF.')); return; }
        $id = (int) $this->input->post('id');
        $t = function ($campo, $max) { return mb_substr(mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $this->input->post($campo))), 'UTF-8'), 0, $max, 'UTF-8'); };
        $numero  = preg_replace('/\D/', '', (string) $this->input->post('numero'));
        $paterno = $t('paterno', 60); $materno = $t('materno', 60); $nombres = $t('nombres', 80);
        $puesto  = $t('puesto', 100); $depto = $t('departamento', 100);
        $rfc     = $t('rfc', 13);     $imss = preg_replace('/\D/', '', (string) $this->input->post('imss'));
        $fecha   = trim((string) $this->input->post('fechaIngreso'));

        if ($numero === '' || strlen($numero) > 10) { $this->json(array('exito' => false, 'mensaje' => 'El número de empleado debe ser solo dígitos (máx. 10).')); return; }
        if ($paterno === '' || $nombres === '') { $this->json(array('exito' => false, 'mensaje' => 'Captura al menos el apellido paterno y el nombre.')); return; }
        if ($rfc !== '' && !preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{0,3}$/u', $rfc)) { $this->json(array('exito' => false, 'mensaje' => 'El RFC no tiene un formato válido.')); return; }
        if ($imss !== '' && strlen($imss) !== 11) { $this->json(array('exito' => false, 'mensaje' => 'El número de IMSS debe tener 11 dígitos.')); return; }
        if ($fecha !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$d || $d->format('Y-m-d') !== $fecha) { $this->json(array('exito' => false, 'mensaje' => 'La fecha de ingreso no es válida.')); return; }
        }
        $repetido = $this->db->query("SELECT TOP 1 NOMBRE_COMPLETO AS n FROM dbo.empleados WHERE empresa_id = ? AND empleado_id = ? AND id <> ?",
                                     array($emp, $numero, $id))->row_array();
        if ($repetido) { $this->json(array('exito' => false, 'mensaje' => 'El número ' . $numero . ' ya es de ' . $repetido['n'] . ' en IBF.')); return; }

        $datos = array(
            'empleado_id' => $numero, 'NUMERO' => $numero,
            'NOMBRE_COMPLETO' => $paterno . '/' . $materno . ',' . $nombres,
            'APELLIDO_PATERNO' => $paterno, 'APELLIDO_MATERNO' => $materno, 'NOMBRES' => $nombres,
            'NOMBRE_PUESTO' => $puesto !== '' ? $puesto : null, 'NOMBRE_DEPARTAMENTO' => $depto !== '' ? $depto : null,
            'RFC' => $rfc !== '' ? $rfc : null, 'REG_IMSS' => $imss !== '' ? $imss : null,
            'FECHA_INGRESO' => $fecha !== '' ? $fecha : null,
        );
        if ($id) {
            $existe = $this->db->query("SELECT COUNT(*) AS n FROM dbo.empleados WHERE id = ? AND empresa_id = ?", array($id, $emp))->row_array();
            if (!(int) $existe['n']) { $this->json(array('exito' => false, 'mensaje' => 'Ese empleado no es de IBF.')); return; }
            $this->db->where('id', $id)->where('empresa_id', $emp)->update('empleados', $datos);
        } else {
            $datos['empresa_id'] = $emp; $datos['NO_BYTE'] = 0; $datos['ESTATUS'] = 'A';
            $this->db->insert('empleados', $datos);
        }
        $movidas = $this->traerChecadas($numero, $fecha, $emp);
        $this->json(array('exito' => true, 'mensaje' => ($id ? 'Se guardaron los cambios de ' : 'Se dio de alta a ') . $datos['NOMBRE_COMPLETO'] . '.'
            . ($movidas ? ' Se pasaron a IBF ' . $movidas . ' checada(s) que ya tenía en el reloj; vuelve a Procesar la semana.' : '')));
    }

    /**
     * Las checadas toman la planta al bajarse del reloj: las que hizo antes de existir en IBF quedaron
     * en la planta del reloj. Se pasan a IBF desde su fecha de ingreso (o 14 días si no tiene), salvo
     * las de una planta donde ese número es empleado activo (otra persona con el mismo número).
     */
    private function traerChecadas($numero, $fecha, $emp){
        $desde = $fecha !== '' ? $fecha : date('Y-m-d', strtotime('-14 days'));
        $donde = "FROM dbo.checadas ch
              WHERE ch.usuarioid_norm = ? AND ch.fecha >= ? AND ch.idPlanta <> ?
                AND NOT EXISTS (SELECT 1 FROM dbo.empleados e
                                 WHERE e.empleado_id = ? AND e.empresa_id = ch.idPlanta AND e.ESTATUS = 'A')";
        $p = array($numero, $desde, $emp, $numero);
        $r = $this->db->query("SELECT COUNT(*) AS n " . $donde, $p)->row_array();
        $n = $r ? (int) $r['n'] : 0;
        if ($n) $this->db->query("UPDATE ch SET idPlanta = ? " . $donde, array_merge(array($emp), $p));
        return $n;
    }

    /** Baja (B) o reactivar (A). No se borra: queda el historial de checadas e incidencias. */
    public function estatus(){
        $emp = $this->empresaId();
        $id = (int) $this->input->post('id');
        $estatus = $this->input->post('estatus') === 'A' ? 'A' : 'B';
        if (!$emp || !$id) { $this->json(array('exito' => false, 'mensaje' => 'Faltan datos.')); return; }
        $this->db->query("UPDATE dbo.empleados SET ESTATUS = ? WHERE id = ? AND empresa_id = ?", array($estatus, $id, $emp));
        $this->json(array('exito' => true));
    }

    private function empresaId(){
        $r = $this->db->query("SELECT id FROM dbo.empresas WHERE nombre = ?", array(self::EMPRESA))->row_array();
        return $r ? (int) $r['id'] : 0;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
