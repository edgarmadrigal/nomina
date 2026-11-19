<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DiasFestivosModel extends CI_Model {

    function __construct(){
        parent::__construct();
    }

    /**
     * Lista los días festivos, opcionalmente filtrados por año.
     * La fecha se convierte explícitamente a VARCHAR (formato ISO
     * YYYY-MM-DD) porque el driver sqlsrv puede regresar las columnas
     * DATE como objetos DateTime en vez de texto, y eso no se
     * serializa bien a JSON para el front-end.
     */
    public function listar($anio = null){
        $sql = "SELECT id, anio, CONVERT(VARCHAR(10), fecha, 23) AS fecha, descripcion
                FROM DiasFestivos";
        $params = array();
        if ($anio !== null && $anio !== '') {
            $sql .= " WHERE anio = ?";
            $params[] = $anio;
        }
        $sql .= " ORDER BY fecha";
        $result = $this->db->query($sql, $params);
        return $result->result_array();
    }

    public function obtener($id){
        $sql = "SELECT id, anio, CONVERT(VARCHAR(10), fecha, 23) AS fecha, descripcion
                FROM DiasFestivos
                WHERE id = ?";
        $result = $this->db->query($sql, array($id));
        return $result->row_array();
    }

    /**
     * anio se deriva de la fecha (YEAR(?)) para que nunca queden
     * desincronizados -- no se captura por separado en el formulario.
     */
    public function agregar($fecha, $descripcion){
        $sql = "INSERT INTO DiasFestivos (anio, fecha, descripcion)
                VALUES (YEAR(?), ?, ?)";
        $params = array($fecha, $fecha, $descripcion);
        return $this->db->query($sql, $params);
    }

    public function actualizar($id, $fecha, $descripcion){
        $sql = "UPDATE DiasFestivos
                SET anio = YEAR(?), fecha = ?, descripcion = ?
                WHERE id = ?";
        $params = array($fecha, $fecha, $descripcion, $id);
        return $this->db->query($sql, $params);
    }

    public function eliminar($id){
        $sql = "DELETE FROM DiasFestivos WHERE id = ?";
        return $this->db->query($sql, array($id));
    }

    /**
     * Evita duplicar un festivo en la misma fecha. $excluirId se usa
     * al editar, para no chocar contra el propio registro que se está
     * actualizando.
     */
    public function existeFecha($fecha, $excluirId = null){
        $sql = "SELECT COUNT(*) AS total FROM DiasFestivos WHERE fecha = ?";
        $params = array($fecha);
        if ($excluirId !== null && $excluirId !== '') {
            $sql .= " AND id <> ?";
            $params[] = $excluirId;
        }
        $result = $this->db->query($sql, $params);
        $row = $result->row_array();
        return $row['total'] > 0;
    }

    /**
     * Llama al proc dbo.GeneraDiasFestivos (ya creado en la base) para
     * poblar los 7 festivos oficiales del año dado. El proc mismo evita
     * duplicados con IF NOT EXISTS por fecha, así que es seguro volver
     * a llamarlo para un año que ya tenía datos.
     */
    public function generarAnio($anio){
        $sql = "EXEC dbo.GeneraDiasFestivos ?";
        $result = $this->db->query($sql, array($anio));
        return $result->result_array();
    }

    public function aniosDisponibles(){
        $sql = "SELECT DISTINCT anio FROM DiasFestivos ORDER BY anio";
        $result = $this->db->query($sql);
        return $result->result_array();
    }
}
