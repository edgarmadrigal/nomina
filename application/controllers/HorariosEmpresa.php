<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HorariosEmpresa extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('HorarioEmpresa');
        header('Access-Control-Allow-Origin: *');
    }

    public function index() {
        header('Access-Control-Allow-Origin: *');
        $data = [];

        if (isset($this->session->userdata['login'])) {
            $data['user'] = $this->session->userdata['login']['user'];

            if ($this->session->userdata['login']['exito']) {
                $this->load->model('PermisoModel');
                $data['empresas'] = $this->PermisoModel->quitarOcultas($this->HorarioEmpresa->consultaPlanta());
                $this->load->view('header', $data);
                $this->load->view('Horarios_Empresa', $data);
                $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
        }
    }

    public function consultaPlanta() {
        header('Access-Control-Allow-Origin: *');
        $this->load->model('PermisoModel');
        echo json_encode($this->PermisoModel->quitarOcultas($this->HorarioEmpresa->consultaPlanta()));
    }

    public function consultaHorario() {
        header('Access-Control-Allow-Origin: *');
        $empresa_id = $this->input->post('empresa_id', true);
        $idDia = $this->input->post('dia', true);
        echo json_encode($this->HorarioEmpresa->consultaHorario($empresa_id, $idDia));
    }

    public function consultaHorarioEditar() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        echo json_encode($this->HorarioEmpresa->consultaHorarioEditar($id));
    }

    public function consultaHorarioID() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        echo json_encode($this->HorarioEmpresa->consultaHorarioID($id));
    }

    public function consultaHorarios() {
        header('Access-Control-Allow-Origin: *');
        echo json_encode($this->HorarioEmpresa->consultaHorarios());
    }

private function _horasONull($v){
    return ($v === null || $v === '') ? null : (int)$v;
}

public function ActualizarHorariosEmpresa() {
    header('Access-Control-Allow-Origin: *');
    $empresa_id = $this->input->post('empresa_id', true);
    $fechaAsignacion = $this->input->post('fechaAsignacion', true);
    $dia = $this->input->post('dia', true); // número de día (1–7)
    $horarioAnterior = $this->input->post('horarioAnterior', true); // id del horario actual
    $nuevoHorario = $this->input->post('nuevoHorario', true); // id del nuevo horario
    $descripcion = $this->input->post('descripcion', true);

    // Mapeo de día → columna
    $dayColumnMap = array(
        1 => 'idhorario_lunes',
        2 => 'idhorario_martes',
        3 => 'idhorario_miercoles',
        4 => 'idhorario_jueves',
        5 => 'idhorario_viernes',
        6 => 'idhorario_sabado',
        7 => 'idhorario_domingo'
    );

    $columna = isset($dayColumnMap[(int)$dia]) ? $dayColumnMap[(int)$dia] : null;

    if ($empresa_id && $columna) {
        $this->HorarioEmpresa->ActualizarHorariosPorHorario(
            $empresa_id,
            $columna,
            $horarioAnterior,
            $nuevoHorario,
            $descripcion,
            $fechaAsignacion
        );
        echo 1;
    } else {
        echo 0;
    }
}


    public function AgregarHorariosEmpresa() {
        header('Access-Control-Allow-Origin: *');
        // Si se envía empresa_id, aplicamos el horario a todos los empleados activos de la empresa
        $empresa_id = $this->input->post('empresa_id', true);
        $inserted = 0;
        $data_template = array(
            'idhorario_lunes'      => $this->input->post('idhorarioLunes', true),
            'idhorario_martes'     => $this->input->post('idhorarioMartes', true),
            'idhorario_miercoles'  => $this->input->post('idhorarioMiercoles', true),
            'idhorario_jueves'     => $this->input->post('idhorarioJueves', true),
            'idhorario_viernes'    => $this->input->post('idhorarioViernes', true),
            'idhorario_sabado'     => $this->input->post('idhorarioSabado', true),
            'idhorario_domingo'    => $this->input->post('idhorarioDomingo', true),
            'idEstatus'            => 1,
            'descripcion'          => $this->input->post('descripcion', true),
            'fechaAsignacion'      => $this->input->post('fechaAsignacion', true),
            'cantidad'             => $this->input->post('cantidad', true)
        );

        //if ($empresa_id) {
            // Use stored procedure to insert for all employees in the company
            $params = array(
                $empresa_id,
                $data_template['idhorario_lunes'],
                $data_template['idhorario_martes'],
                $data_template['idhorario_miercoles'],
                $data_template['idhorario_jueves'],
                $data_template['idhorario_viernes'],
                $data_template['idhorario_sabado'],
                $data_template['idhorario_domingo'],
                $data_template['idEstatus'],
                $data_template['descripcion'],
                $data_template['fechaAsignacion'],
                $data_template['cantidad']
            );

            // 1 (predeterminado) = no cambiar a quienes ya tienen horario nocturno; la página pregunta antes
            $params[] = $this->input->post('respetarNocturnos') === '0' ? 0 : 1;
            // "Solo a algunos": lista de empleados elegidos en la ventana (solo dígitos)
            $elegidos = $this->input->post('empleados');
            if (is_array($elegidos)) {
                $elegidos = array_values(array_unique(array_filter(array_map(function ($v) {
                    return preg_replace('/\D/', '', (string) $v);
                }, $elegidos), 'strlen')));
                if (count($elegidos) === 0) { echo json_encode(array('exito' => 0, 'mensaje' => 'No elegiste ningún empleado.')); return; }
                $params[] = implode(',', $elegidos);
            } else {
                $params[] = null;
            }

            $sp = "EXEC dbo.AgregarHorariosEmpresaSP ?,?,?,?,?,?,?,?,?,?,?,?,?,?";
            $res = $this->db->query($sp, $params);
            $fila = $res ? $res->row_array() : null;
            echo $fila ? json_encode(array('exito' => 1, 'asignados' => (int) $fila['asignados'], 'respetados' => (int) $fila['respetados'])) : 0;
            return;
        //}

        // Si viene empleado_id individual, lo insertamos normalmente
        /*$empleado_id = $this->input->post('empleado_id', true);
        if ($empleado_id) {
            $data = $data_template;
            $data['empleado_id'] = $empleado_id;
            echo $this->HorarioEmpresa->AgregarHorariosEmpresa($data);
            return;
        }
*/
        //echo 0;
    }

        

    /** Empleados activos de la empresa cuyo horario vigente tiene algún día nocturno (para preguntar antes de asignar). */
    public function nocturnosEmpresa() {
        header('Access-Control-Allow-Origin: *');
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $filas = $this->db->query(
            "SELECT e.empleado_id, e.NOMBRE_COMPLETO AS nombre
             FROM dbo.empleados e
             WHERE e.empresa_id = ? AND e.estatus = 'A' AND dbo.EmpleadoTieneNocturnoEmpresa(e.empleado_id, e.empresa_id) = 1
             ORDER BY e.NOMBRE_COMPLETO", array($empresa_id))->result_array();
        echo json_encode(array('total' => count($filas), 'empleados' => array_slice($filas, 0, 30)));
    }

    /**
     * Empleados activos de la empresa con su horario vigente, separados por turno
     * nocturno / normal (ventana "¿A quién se le asigna?").
     */
    public function empleadosAsignar() {
        header('Access-Control-Allow-Origin: *');
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $filas = $this->db->query(
            "SELECT e.empleado_id, e.NOMBRE_COMPLETO AS nombre, e.NOMBRE_DEPARTAMENTO AS departamento,
                    dbo.EmpleadoTieneNocturnoEmpresa(e.empleado_id, e.empresa_id) AS nocturno,
                    v.horario
             FROM dbo.empleados e
             OUTER APPLY (SELECT TOP 1 he.* FROM dbo.horarios_empleado he
                          WHERE he.empleado_id = e.empleado_id AND he.idEstatus = 1
                          ORDER BY CASE WHEN he.empresa_id = e.empresa_id THEN 0 WHEN he.empresa_id IS NULL THEN 1 ELSE 2 END,
                                   he.fechaAsignacion DESC, he.id DESC) he
             OUTER APPLY (SELECT STUFF((SELECT DISTINCT ', ' + h.nombre FROM dbo.horarios h
                                        WHERE h.id IN (he.idhorario_lunes, he.idhorario_martes, he.idhorario_miercoles, he.idhorario_jueves,
                                                       he.idhorario_viernes, he.idhorario_sabado, he.idhorario_domingo) AND h.id <> 1
                                        FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS horario) v
             WHERE e.empresa_id = ? AND e.estatus = 'A'
             ORDER BY e.NOMBRE_COMPLETO", array($empresa_id))->result_array();
        foreach ($filas as &$f) $f['nocturno'] = (int) $f['nocturno'];
        echo json_encode($filas);
    }

    /**
     * "Editar" de las tablas por día: el horario de ese día y todas las asignaciones de la empresa
     * que lo tienen ese día, vigentes y anteriores. Cada asignación vale desde su fecha hasta el
     * día anterior a la siguiente asignación del mismo empleado; la más reciente sigue vigente.
     */
    public function empleadosHorario() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $dia = (int) $this->input->post('dia', true);
        $idHorario = (int) $this->input->post('idHorario', true);
        $dias = array(1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo');
        if (!$empresa_id || !isset($dias[$dia]) || !$idHorario) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Faltan datos del horario.'));
            return;
        }
        $horario = $this->db->query(
            "SELECT id, nombre, tipo, CONVERT(VARCHAR(5), entrada_Inicio, 108) AS entrada, CONVERT(VARCHAR(5), salida_Inicio, 108) AS salida,
                    ISNULL(horas_diarias, 0) AS horas, ISNULL(minutos_diarios, 0) AS minutos
             FROM dbo.horarios WHERE id = ?", array($idHorario))->row_array();

        // columnas de los 7 días: nombre, entrada y salida de cada horario
        $sel = array(); $joins = array();
        foreach ($dias as $n => $d) {
            $sel[] = "h$n.nombre AS {$d}_horario, CONVERT(VARCHAR(5), h$n.entrada_Inicio, 108) AS {$d}_entrada, CONVERT(VARCHAR(5), h$n.salida_Inicio, 108) AS {$d}_salida";
            $joins[] = "LEFT JOIN dbo.horarios h$n ON h$n.id = u.idhorario_$d";
        }
        $empleados = $this->db->query(
            // cada asignación vale desde su fecha hasta el día anterior a la siguiente del mismo empleado
            "WITH u AS (
                 SELECT he.*,
                        LEAD(he.fechaAsignacion) OVER (PARTITION BY he.empleado_id ORDER BY he.fechaAsignacion, he.id) AS siguiente,
                        ROW_NUMBER() OVER (PARTITION BY he.empleado_id ORDER BY he.fechaAsignacion DESC, he.id DESC) AS rn
                 FROM dbo.horarios_empleado he
                 WHERE he.idEstatus = 1 AND he.empresa_id = ?)
             SELECT u.id AS idAsignacion, u.empleado_id, e.nombre, e.departamento, e.puesto, e.estatus,
                    u.cantidad, CONVERT(VARCHAR(10), u.fechaAsignacion, 23) AS fechaAsignacion, u.descripcion,
                    CONVERT(VARCHAR(10), DATEADD(DAY, -1, u.siguiente), 23) AS hasta,
                    CASE WHEN u.rn = 1 THEN 1 ELSE 0 END AS vigente,
                    CASE WHEN u.siguiente IS NOT NULL AND CAST(u.siguiente AS DATE) <= CAST(u.fechaAsignacion AS DATE) THEN 1 ELSE 0 END AS sinDias,
                    " . implode(",
                    ", $sel) . "
             FROM u
             OUTER APPLY (SELECT TOP 1 x.NOMBRE_COMPLETO AS nombre, x.NOMBRE_DEPARTAMENTO AS departamento, x.NOMBRE_PUESTO AS puesto, x.ESTATUS AS estatus
                          FROM dbo.empleados x WHERE x.empleado_id = u.empleado_id
                          ORDER BY CASE WHEN x.empresa_id = ? THEN 0 ELSE 1 END, CASE WHEN x.ESTATUS = 'A' THEN 0 ELSE 1 END, x.id DESC) e
             " . implode("
             ", $joins) . "
             WHERE u.idhorario_" . $dias[$dia] . " = ?
             ORDER BY vigente DESC, u.fechaAsignacion DESC, e.nombre",
            array($empresa_id, $empresa_id, $idHorario))->result_array();
        foreach ($empleados as &$e) {
            $e['vigente'] = (int) $e['vigente'];
            $e['sinDias'] = (int) $e['sinDias'];
        }
        unset($e);
        foreach ($empleados as &$e) $e['cantidad'] = $e['cantidad'] === null ? null : (float) $e['cantidad'];

        echo json_encode(array('exito' => true, 'dia' => $dia, 'horario' => $horario, 'empleados' => $empleados));
    }

    /**
     * Edición masiva desde "Editar / ver empleados": a cada empleado elegido se le crea una
     * asignación NUEVA desde fechaAsignacion, copiando la asignación elegida y cambiando solo
     * los días indicados (0 = sin cambio). La anterior queda en el historial y vence el día antes.
     */
    public function edicionMasiva() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        if (!isset($this->session->userdata['login']['user'])) {
            echo json_encode(array('exito' => false, 'mensaje' => 'La sesión terminó; vuelve a entrar.'));
            return;
        }
        $usuario = (int) $this->session->userdata['login']['user']->id;
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $fecha = trim((string) $this->input->post('fechaAsignacion', true));
        $ids = array_values(array_filter(array_map('intval', (array) $this->input->post('ids'))));
        $dias = (array) $this->input->post('dias');
        $cantidad = trim((string) $this->input->post('cantidad', true));
        $descripcion = trim((string) $this->input->post('descripcion', true));
        $columnas = array(1 => 'idhorario_lunes', 2 => 'idhorario_martes', 3 => 'idhorario_miercoles', 4 => 'idhorario_jueves',
                          5 => 'idhorario_viernes', 6 => 'idhorario_sabado', 7 => 'idhorario_domingo');

        if (!$empresa_id || !$ids) { echo json_encode(array('exito' => false, 'mensaje' => 'Elige al menos un empleado.')); return; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'La fecha de asignación no es válida.')); return;
        }
        if ($cantidad !== '' && (!is_numeric($cantidad) || $cantidad < 0 || $cantidad > 7)) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Los días por semana deben ser un número entre 0 y 7 (admite decimales).')); return;
        }
        $nuevos = array();
        foreach ($columnas as $n => $col) {
            $v = isset($dias[$n]) ? (int) $dias[$n] : 0;
            if ($v > 0) $nuevos[$col] = $v;
        }
        if (!$nuevos && $cantidad === '' && $descripcion === '') {
            echo json_encode(array('exito' => false, 'mensaje' => 'No elegiste ningún cambio.')); return;
        }
        if ($nuevos) {
            $existen = $this->db->query("SELECT COUNT(DISTINCT id) AS n FROM dbo.horarios WHERE id IN (" . implode(',', array_unique(array_values($nuevos))) . ")")->row_array();
            if ((int) $existen['n'] !== count(array_unique(array_values($nuevos)))) {
                echo json_encode(array('exito' => false, 'mensaje' => 'Alguno de los horarios elegidos no existe.')); return;
            }
        }

        // asignaciones elegidas de esta empresa; si de un empleado se eligieron varias, se toma la más reciente
        $filas = $this->db->query(
            "SELECT id, empleado_id FROM dbo.horarios_empleado
             WHERE id IN (" . implode(',', $ids) . ") AND empresa_id = ? AND idEstatus = 1
             ORDER BY fechaAsignacion DESC, id DESC", array($empresa_id))->result_array();
        $base = array();
        foreach ($filas as $f) if (!isset($base[$f['empleado_id']])) $base[$f['empleado_id']] = (int) $f['id'];
        if (!$base) { echo json_encode(array('exito' => false, 'mensaje' => 'No se encontraron esas asignaciones en la empresa.')); return; }

        $set = array(); $params = array();
        foreach ($columnas as $col) {
            if (isset($nuevos[$col])) { $set[] = '?'; $params[] = $nuevos[$col]; } else { $set[] = 'o.' . $col; }
        }
        $sql = "INSERT INTO dbo.horarios_empleado
                    (empleado_id, empresa_id, descripcion, idhorario_lunes, idhorario_martes, idhorario_miercoles,
                     idhorario_jueves, idhorario_viernes, idhorario_sabado, idhorario_domingo,
                     fechaAsignacion, idEstatus, rotativo, cantidad, create_dtm, update_iduser)
                SELECT o.empleado_id, o.empresa_id, " . ($descripcion !== '' ? '?' : 'o.descripcion') . ", " . implode(', ', $set) . ",
                       ?, 1, o.rotativo, " . ($cantidad !== '' ? '?' : 'o.cantidad') . ", GETDATE(), ?
                FROM dbo.horarios_empleado o WHERE o.id = ?";
        $this->db->trans_start();
        foreach ($base as $id) {
            $p = array();
            if ($descripcion !== '') $p[] = mb_substr($descripcion, 0, 200);
            $p = array_merge($p, $params, array($fecha));
            if ($cantidad !== '') $p[] = $cantidad;
            $p[] = $usuario;
            $p[] = $id;
            $this->db->query($sql, $p);
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) { echo json_encode(array('exito' => false, 'mensaje' => 'No se pudo guardar el cambio.')); return; }
        echo json_encode(array('exito' => true, 'total' => count($base),
            'mensaje' => 'Se creó la nueva asignación desde el ' . $fecha . ' para ' . count($base) . ' empleado(s). La anterior queda en el historial.'));
    }

    /**
     * "Eliminar" de las tablas por día (horariosEmpresa.js -> eliminar()): borra todas las asignaciones
     * de la empresa (vigentes y anteriores) que tienen ese horario ese día. El trigger de bitácora
     * guarda lo borrado. Con soloContar = 1 solo regresa la lista, para mostrarla en la confirmación.
     */
    public function delete() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        if (!isset($this->session->userdata['login']['user'])) {
            echo json_encode(array('exito' => false, 'mensaje' => 'La sesión terminó; vuelve a entrar.'));
            return;
        }
        $dias = array(1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo');
        $empresa_id = (int) $this->input->post('empresa_id', true);
        $dia = (int) $this->input->post('dia', true);
        $idHorario = (int) $this->input->post('idHorario', true);
        if (!$empresa_id || !isset($dias[$dia]) || !$idHorario) {
            echo json_encode(array('exito' => false, 'mensaje' => 'Faltan datos del horario.'));
            return;
        }
        $where = "WHERE empresa_id = ? AND idhorario_" . $dias[$dia] . " = ?";
        $params = array($empresa_id, $idHorario);
        if ($this->input->post('soloContar') === '1') {
            $filas = $this->db->query(
                "SELECT he.empleado_id, e.nombre, CONVERT(VARCHAR(10), he.fechaAsignacion, 23) AS fechaAsignacion,
                        CASE WHEN he.idEstatus = 1 THEN 1 ELSE 0 END AS activo
                 FROM dbo.horarios_empleado he
                 OUTER APPLY (SELECT TOP 1 x.NOMBRE_COMPLETO AS nombre FROM dbo.empleados x WHERE x.empleado_id = he.empleado_id
                              ORDER BY CASE WHEN x.empresa_id = he.empresa_id THEN 0 ELSE 1 END, x.id DESC) e
                 " . str_replace(array('empresa_id', 'idhorario_'), array('he.empresa_id', 'he.idhorario_'), $where) . "
                 ORDER BY e.nombre, he.fechaAsignacion DESC", $params)->result_array();
            foreach ($filas as &$f) $f['activo'] = (int) $f['activo'];
            unset($f);
            echo json_encode(array('exito' => true, 'asignaciones' => $filas));
            return;
        }
        $this->db->query("DELETE FROM dbo.horarios_empleado " . $where, $params);
        echo json_encode(array('exito' => true, 'total' => $this->db->affected_rows()));
    }

    public function EliminarHorariosEmpresa() {
    header('Access-Control-Allow-Origin: *');
    $empresa_id = $this->input->post('empresa_id', true);
    $dia = $this->input->post('dia', true);
    $horarioAEliminar = $this->input->post('horarioAEliminar', true);

    $dayColumnMap = array(
        1 => 'idhorario_lunes',
        2 => 'idhorario_martes',
        3 => 'idhorario_miercoles',
        4 => 'idhorario_jueves',
        5 => 'idhorario_viernes',
        6 => 'idhorario_sabado',
        7 => 'idhorario_domingo'
    );

    $columna = isset($dayColumnMap[(int)$dia]) ? $dayColumnMap[(int)$dia] : null;

    if ($empresa_id && $columna) {
        $this->HorarioEmpresa->EliminarHorariosPorHorario(
            $empresa_id,
            $columna,
            $horarioAEliminar
        );
        echo 1;
    } else {
        echo 0;
    }
}

}
