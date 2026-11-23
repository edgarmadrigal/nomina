<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HorarioEmpresa extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function consultaPlanta() {
        return $this->db->select('id, nombre')
            ->distinct()
            ->from('empresas')
            ->where('estatus', '1')
            ->get()
            ->result();
    }
public function consultaHorario($empresa_id, $idDia) {
    ini_set('max_execution_time', 0);

    $dayColumnMap = array(
        1 => 'idhorario_lunes',
        2 => 'idhorario_martes',
        3 => 'idhorario_miercoles',
        4 => 'idhorario_jueves',
        5 => 'idhorario_viernes',
        6 => 'idhorario_sabado',
        7 => 'idhorario_domingo'
    );

    $col = isset($dayColumnMap[(int)$idDia]) ? $dayColumnMap[(int)$idDia] : null;

    if (!$col) {
        return array();
    }

    $this->db->select("
        h.id AS idHorario,
        h.nombre AS horario,
        he.descripcion,
        ISNULL(CONVERT(varchar(8), h.entrada_Inicio, 108), '') AS entrada,
        ISNULL(CONVERT(varchar(8), h.salida_Inicio, 108), '') AS salida,
        CAST(ISNULL(h.horas_diarias,0) AS VARCHAR) + 'h ' + CAST(ISNULL(h.minutos_diarios,0) AS VARCHAR) + 'm' AS horas,
        MAX(CONVERT(varchar(10), he.fechaAsignacion, 23)) AS fechaAsignacion,
        $idDia AS dia
    ", false);

    $this->db->from('horarios_empleado he');
    $this->db->join('horarios h', "h.id = he.$col", 'left');
    $this->db->where('he.empresa_id', $empresa_id);
    $this->db->where('he.idEstatus', 1);
    $this->db->where("he.$col IS NOT NULL", null, false);
    $this->db->where("he.$col <> 0", null, false);

    // Agrupamos por las columnas que definen un horario único
    $this->db->group_by('h.id, h.nombre, he.descripcion, h.entrada_Inicio, h.salida_Inicio, h.horas_diarias, h.minutos_diarios');

    $this->db->order_by('fechaAsignacion', 'DESC');

    $query = $this->db->get();
    return $query->result_array();
}


    public function consultaHorarioEditar($id) {
        ini_set('max_execution_time', 0);
        $sp = "ConsultaHorariosEmpleadoEdit ?";
        $params = array('id' => $id);
        $result = $this->db->query($sp, $params);
        return $result->result_array();
    }

    public function consultaHorarioID($id) {
        ini_set('max_execution_time', 0);
        $sp = "ConsultaHorariosID ?";
        $params = array('id' => $id);
        $result = $this->db->query($sp, $params);
        return $result->result_array();
    }

    public function consultaHorarios() {
        ini_set('max_execution_time', 0);
        $sp = "ConsultaHorarios";
        $result = $this->db->query($sp);
        return $result->result_array();
    }

    public function AgregarHorariosEmpresa($data) {
        return $this->db->insert('horarios_empleado', $data);
    }

    public function ActualizarHorariosEmpresa($id, $data) {
        $this->db->where('id', $id)
            ->update('horarios_empleado', $data);
        return true;
    }
public function ActualizarHorariosEmpresaPorEmpresa($empresa_id, $data, $fechaAsignacion) {
    $sql = "UPDATE he SET
        idhorario_lunes = ?,
        idhorario_martes = ?,
        idhorario_miercoles = ?,
        idhorario_jueves = ?,
        idhorario_viernes = ?,
        idhorario_sabado = ?,
        idhorario_domingo = ?,
        descripcion = ?,
        fechaAsignacion = ?,
        cantidad = ?
        FROM horarios_empleado he
        JOIN empleados e ON e.empleado_id = he.empleado_id AND (he.empresa_id = e.empresa_id OR he.empresa_id IS NULL)  -- 2026-11-23: mismo numero en otra empresa
        WHERE e.empresa_id = ? AND he.fechaAsignacion = ? ";

    $params = array(
        $data['idhorario_lunes'],
        $data['idhorario_martes'],
        $data['idhorario_miercoles'],
        $data['idhorario_jueves'],
        $data['idhorario_viernes'],
        $data['idhorario_sabado'],
        $data['idhorario_domingo'],
        $data['descripcion'],
        $data['fechaAsignacion'],
        $data['cantidad'],
        $empresa_id,
        $fechaAsignacion
    );

    $this->db->query($sql, $params);
    return true;
}

    public function delete($id) {
        $this->db->where('id', $id)
            ->delete('horarios_empleado');
        return true;
    }

    public function ActualizarHorariosPorHorario($empresa_id, $columna, $horarioAnterior, $nuevoHorario, $descripcion, $fechaAsignacion) {
    $sql = "UPDATE he
            SET $columna = ?, descripcion = ?, fechaAsignacion = ?
            FROM horarios_empleado he
            JOIN empleados e ON e.empleado_id = he.empleado_id AND (he.empresa_id = e.empresa_id OR he.empresa_id IS NULL)  -- 2026-11-23: mismo numero en otra empresa
            WHERE e.empresa_id = ? AND he.$columna = ?";
    
    $params = array($nuevoHorario, $descripcion, $fechaAsignacion, $empresa_id, $horarioAnterior);
    $this->db->query($sql, $params);
    return true;
}

public function EliminarHorariosPorHorario($empresa_id, $columna, $horarioAEliminar) {
    $sql = "UPDATE he
            SET $columna = NULL
            FROM horarios_empleado he
            JOIN empleados e ON e.empleado_id = he.empleado_id AND (he.empresa_id = e.empresa_id OR he.empresa_id IS NULL)  -- 2026-11-23: mismo numero en otra empresa
            WHERE e.empresa_id = ? AND he.$columna = ?";
    
    $params = array($empresa_id, $horarioAEliminar);
    $this->db->query($sql, $params);
    return true;
}


}
