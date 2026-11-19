<?php
//MODELO horarios
class IncidenciasEmpresa extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }

    
    public function consultaIncidencias($id){
        // id = id de la incidencia de la empresa (antes regresaba empresa_id y no se podía eliminar)
        $this->db->select('i.id
        ,i.empresa_id
        ,i.concepto_id
        ,i.fechaInicio
        ,i.fechaFin
        ,i.estatus
        ,i.usuarioBaja_id
        ,i.fechaBaja,c.descripcion,c.descripcionCorta
        ,i.cubreEntrada,i.cubreEntradaComedor,i.cubreSalidaComedor,i.cubreSalida
        ,(SELECT COUNT(DISTINCT c.empleado_id) FROM dbo.incidencias c WHERE c.incidenciaEmpresa_id = i.id AND c.estatus = 1
            AND EXISTS (SELECT 1 FROM dbo.empleados em WHERE em.empresa_id = i.empresa_id AND em.ESTATUS = \'A\'
                        AND TRY_CAST(em.empleado_id AS INT) = c.empleado_id)) AS aplicados
        ,(SELECT COUNT(DISTINCT em.empleado_id) FROM dbo.empleados em WHERE em.empresa_id = i.empresa_id AND em.ESTATUS = \'A\') AS activosPlanta', false);
        $this->db->from('incidencias i');
        $this->db->join('conceptos c', 'i.concepto_id = c.id')
        ->where('i.empresa_id',$id);
        $this->db->where('i.estatus','1');
        $this->db->where('i.empleado_id',NULL);
        $aResult = $this->db->get();

        if(!$aResult->num_rows() == 1)
        {
            return false;
        }

        return $aResult->result();
    }


    public function consultaConceptos(){        
        $conceptos = $this->db->select('id
        ,descripcion
        ,descripcionCorta
        ,idClasificacion
        ,goceSueldo
        ,comentarios
        ,status
        ,fechaBaja
        ,idUsuarioBaja')->distinct()
        ->from('conceptos')
        ->where('status','1')
        ->get()
        ->result();                 
        return $conceptos;
    }
    
    public function consultaDescripcionCorta($id){
        $conceptos = $this->db->select('
        descripcionCorta')->distinct()
        ->from('conceptos')
        ->where('id',$id)
        ->get()
        ->result();                 
        return $conceptos;

    }

    public function consultaEmpleado($id){
        $empleado = $this->db->select('REG_IMSS')
        ->from('empleados')
        ->where('empleado_id',$id)
        ->get()
        ->result();                 
        return $empleado;
    }

    
    public function ConsultaIncidenciaEditar($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaIncidenciaEditar ? " ; 
        $params = array(
            'id' => $id);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    
    public function consultaHorarioID($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaHorariosID ? " ; 
        $params = array(
            'id' => $id);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    
    /** Empleados activos de la planta (los que pueden llevar la incidencia). */
    public function empleadosPlanta($empresa){
        return $this->db->query(
            "SELECT TRY_CAST(empleado_id AS INT) AS empleado_id, MAX(NOMBRE_COMPLETO) AS nombre,
                    MAX(NOMBRE_PUESTO) AS puesto, MAX(NOMBRE_DEPARTAMENTO) AS departamento
             FROM dbo.empleados
             WHERE empresa_id = ? AND ESTATUS = 'A' AND TRY_CAST(empleado_id AS INT) IS NOT NULL
             GROUP BY TRY_CAST(empleado_id AS INT)
             ORDER BY nombre", array((int) $empresa))->result_array();
    }

    /** Quiénes tienen la incidencia de empresa $id (copias vigentes); solo empleados activos de la planta. */
    public function empleadosIncidencia($id){
        return $this->db->query(
            "SELECT c.id AS copia_id, c.empleado_id, e.nombre, e.puesto, e.departamento
             FROM dbo.incidencias AS m
             JOIN dbo.incidencias AS c ON c.incidenciaEmpresa_id = m.id AND c.estatus = 1
             JOIN (SELECT TRY_CAST(empleado_id AS INT) AS empleado_id, empresa_id, MAX(NOMBRE_COMPLETO) AS nombre,
                          MAX(NOMBRE_PUESTO) AS puesto, MAX(NOMBRE_DEPARTAMENTO) AS departamento
                   FROM dbo.empleados WHERE ESTATUS = 'A'
                   GROUP BY TRY_CAST(empleado_id AS INT), empresa_id) AS e
               ON e.empleado_id = c.empleado_id AND e.empresa_id = m.empresa_id
             WHERE m.id = ? AND m.empleado_id IS NULL
             ORDER BY e.nombre", array((int) $id))->result_array();
    }

    /** Renglón de la empresa (empleado_id NULL) vigente, o null. */
    public function maestro($id){
        return $this->db->query("SELECT id, empresa_id FROM dbo.incidencias
                                 WHERE id = ? AND empleado_id IS NULL AND empresa_id IS NOT NULL AND estatus = 1",
                                array((int) $id))->row_array();
    }

    /** Filtro SQL de empleados (alias em); $empleados null = todos, si no solo esos números. */
    private function filtroEmpleados($empleados){
        if ($empleados === null) return '';
        $ids = array_values(array_unique(array_filter(array_map('intval', $empleados))));
        return $ids ? ' AND TRY_CAST(em.empleado_id AS INT) IN (' . implode(',', $ids) . ')' : ' AND 1 = 0';
    }

    /** Copias vigentes de la incidencia de empresa (affected_rows() da -1 por el trigger de la bitácora). */
    private function vigentes($idMaestro){
        return (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.incidencias WHERE incidenciaEmpresa_id = ? AND estatus = 1",
                                      array((int) $idMaestro))->row()->n;
    }

    /** Copia la incidencia de empresa $idMaestro a los empleados activos elegidos que todavía no la tienen. */
    private function agregarCopias($idMaestro, $empleados){
        $antes = $this->vigentes($idMaestro);
        $this->db->query(
            "INSERT INTO dbo.incidencias (empleado_id, concepto_id, fechaInicio, fechaFin, estatus, cantidadDias, empresa_id,
                                          cubreEntrada, cubreEntradaComedor, cubreSalidaComedor, cubreSalida, incidenciaEmpresa_id)
             SELECT e.empleado_id, m.concepto_id, m.fechaInicio, m.fechaFin, 1, m.cantidadDias, m.empresa_id,
                    m.cubreEntrada, m.cubreEntradaComedor, m.cubreSalidaComedor, m.cubreSalida, m.id
             FROM dbo.incidencias AS m
             CROSS APPLY (SELECT DISTINCT TRY_CAST(em.empleado_id AS INT) AS empleado_id FROM dbo.empleados AS em
                          WHERE em.empresa_id = m.empresa_id AND em.ESTATUS = 'A'
                            AND TRY_CAST(em.empleado_id AS INT) IS NOT NULL" . $this->filtroEmpleados($empleados) . ") AS e
             WHERE m.id = ?
               AND NOT EXISTS (SELECT 1 FROM dbo.incidencias AS c
                               WHERE c.incidenciaEmpresa_id = m.id AND c.empleado_id = e.empleado_id AND c.estatus = 1)",
            array((int) $idMaestro));
        return $this->vigentes($idMaestro) - $antes;
    }

    /**
     * Nueva incidencia de empresa. $empleados null = todos los activos de la planta; si no, solo esos.
     * El trigger trg_InsertEmpresa_Incidencias no copia (SESSION_CONTEXT); las copias se hacen aquí.
     * Regresa cuántos empleados la llevan, o false.
     */
    public function GuardarIncidencia($data, $empleados = null){
        $this->db->trans_start();
        $this->db->query("EXEC sp_set_session_context N'incEmpresaSinCopias', 1");
        // insert_id() corre SCOPE_IDENTITY() en otro lote y regresa NULL: el id sale con OUTPUT en el mismo lote
        // (empieza con DECLARE: si empieza con SET, CI lo toma como escritura y no regresa filas)
        $sql = str_replace(') VALUES (', ') OUTPUT INSERTED.id INTO @t VALUES (', $this->db->insert_string('incidencias', $data));
        $id = (int) $this->db->query("DECLARE @t TABLE (id INT); SET NOCOUNT ON; " . $sql . "; SELECT id FROM @t;")->row()->id;
        $this->db->query("EXEC sp_set_session_context N'incEmpresaSinCopias', NULL");
        $copias = $id ? $this->agregarCopias($id, $empleados) : 0;
        $this->db->trans_complete();
        return ($id && $this->db->trans_status()) ? $copias : false;
    }

    /**
     * Edita la incidencia de la empresa ($id = renglón maestro) y sus copias. Con $empleados
     * (null = todos los activos) quita a los que ya no van y agrega a los nuevos.
     * Regresa ['agregados' => n, 'quitados' => n] o false.
     */
    public function ActualizarIncidencia($id, $data, $empleados = null, $usuarioBaja = null){
        $m = $this->maestro($id);
        if (!$m) return false;
        $this->db->trans_start();
        $this->db->where('id', (int) $id)->update('incidencias', $data);
        $this->db->where('incidenciaEmpresa_id', (int) $id)->where('estatus', 1)->update('incidencias', $data);
        // quita a los activos de la planta que ya no están en la lista (las copias de bajas no se tocan)
        $antes = $this->vigentes($id);
        $this->db->query(
            "UPDATE c SET c.estatus = 0, c.fechaBaja = CAST(GETDATE() AS DATE), c.usuarioBaja_id = ?
             FROM dbo.incidencias AS c
             WHERE c.incidenciaEmpresa_id = ? AND c.estatus = 1
               AND EXISTS (SELECT 1 FROM dbo.empleados AS em WHERE em.empresa_id = ? AND em.ESTATUS = 'A'
                             AND TRY_CAST(em.empleado_id AS INT) = c.empleado_id)
               AND NOT EXISTS (SELECT 1 FROM dbo.empleados AS em WHERE em.empresa_id = ? AND em.ESTATUS = 'A'
                                 AND TRY_CAST(em.empleado_id AS INT) = c.empleado_id" . $this->filtroEmpleados($empleados) . ")",
            array($usuarioBaja, (int) $id, (int) $m['empresa_id'], (int) $m['empresa_id']));
        $quitados = $antes - $this->vigentes($id);
        $agregados = $this->agregarCopias($id, $empleados);
        $this->db->trans_complete();
        return $this->db->trans_status() ? array('agregados' => $agregados, 'quitados' => $quitados) : false;
    }

    /** Quita la incidencia a un solo empleado (copia $copiaId de la incidencia de empresa $id). */
    public function QuitarEmpleado($id, $copiaId, $usuarioBaja){
        $this->db->query(
            "UPDATE dbo.incidencias SET estatus = 0, fechaBaja = CAST(GETDATE() AS DATE), usuarioBaja_id = ?
             WHERE id = ? AND incidenciaEmpresa_id = ? AND empleado_id IS NOT NULL AND estatus = 1",
            array($usuarioBaja, (int) $copiaId, (int) $id));
        return (int) $this->db->query("SELECT COUNT(*) AS n FROM dbo.incidencias WHERE id = ? AND incidenciaEmpresa_id = ? AND estatus = 0",
                                      array((int) $copiaId, (int) $id))->row()->n === 1;
    }

    /**
     * Da de baja la incidencia de la empresa y sus copias por empleado (incidenciaEmpresa_id).
     * Regresa cuántas copias de empleados se dieron de baja, o false si no existe.
     */
    public function EliminarIncidenciaEmpresa($id, $usuarioBaja){
        $m = $this->maestro($id);
        if (!$m) return false;
        $copias = $this->vigentes($id);
        $this->db->trans_start();
        $this->db->query(
            "UPDATE dbo.incidencias SET estatus = 0, fechaBaja = CAST(GETDATE() AS DATE), usuarioBaja_id = ?
             WHERE incidenciaEmpresa_id = ? AND estatus = 1",
            array($usuarioBaja, (int) $id));
        $this->db->query(
            "UPDATE dbo.incidencias SET estatus = 0, fechaBaja = CAST(GETDATE() AS DATE), usuarioBaja_id = ? WHERE id = ?",
            array($usuarioBaja, (int) $id));
        $this->db->trans_complete();
        return $this->db->trans_status() ? $copias : false;
    }

    public function consultaHorarios()    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaHorarios";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }    
   
}

?>