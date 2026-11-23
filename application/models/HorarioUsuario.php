<?php
//MODELO horarios
class HorarioUsuario extends CI_Model{

    function ___construct(){
        parent::___construct();
    }

    public function consultaPlanta(){
        $usuarios = $this->db->select('id ,nombre')->distinct()
        ->from('empresas')
        ->where('estatus','1')
        ->get()
        ->result();                 
        return $usuarios;
        /*
        4107
        1,279.6
        7 DE AGOSTO
        */

    }
    public function consultaEmpleado($search,$empresa_id){

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $usuarios = $this->db->select("empleado_id as id,empleado_id+'-'+CONCAT((NOMBRES),(' '),(APELLIDO_PATERNO),(' '),(APELLIDO_MATERNO)) as text")->distinct()
        ->from('empleados')
        ->where('estatus','A')
        ->where('empresa_id', $empresa_id)
        ->like("empleado_id+' '+CONCAT((NOMBRES),(' '),(APELLIDO_PATERNO),(' '),(APELLIDO_MATERNO))",$search)
       // ->order_by("NOMBRE_COMPLETO", "asc")
        ->get()
        ->result_array();
        return $usuarios;
    }    
    
    public function consultaHorario($empleado_id,$idDia,$empresa_id = null)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        // 2026-11-23: con la empresa, porque el mismo número puede existir en otra empresa
        $sp = "ConsultaHorariosEmpleado ?,?,? " ;
        $params = array(
            'empleado_id' => $empleado_id,
            'dia' => $idDia,
            'empresa_id' => ((int) $empresa_id > 0) ? (int) $empresa_id : null);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

    function ActualizarHorariosEmpleado($id,$data){
        // 2026-11-23: editar no mueve la asignación a otra empresa (antes, con el mismo número
        // en dos empresas, cada una se la "robaba" a la otra). Solo se completa si no tenía empresa.
        if (array_key_exists('empresa_id', $data)) {
            $actual = $this->db->query("SELECT empresa_id FROM dbo.horarios_empleado WHERE id = ?", array($id))->row_array();
            if (!$actual || $actual['empresa_id'] !== null || empty($data['empresa_id'])) unset($data['empresa_id']);
        }
        $this->db->where('id', $id)
              ->update('horarios_empleado', $data);
            return true;              
    }    

    public function AgregarHorariosEmpleado($data){   
        
        $d=true;        
        $this->db->insert('horarios_empleado',$data);
            
        return $d;
    }
    
    public function consultaHorarioEditar($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaHorariosEmpleadoEdit ? " ; 
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
    
    public function consultaHorarios()    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaHorarios";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }    
   


    // ---------------- Copiar horario de un empleado a otros ----------------

    /** Asignación vigente (activa más reciente) del empleado, con el nombre del horario de cada día. */
    public function horarioVigente($empleado_id, $empresa_id = null){
        $dias = array('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo');
        $cols = array();
        $joins = array();
        foreach ($dias as $i => $d) {
            $cols[]  = "h$i.nombre AS $d, CASE WHEN he.idhorario_$d = 1 THEN '' ELSE CONVERT(VARCHAR(5), h$i.entrada_Inicio, 108) + '-' + CONVERT(VARCHAR(5), h$i.salida_Inicio, 108) END AS {$d}Horas";
            $joins[] = "LEFT JOIN dbo.horarios h$i ON h$i.id = he.idhorario_$d";
        }
        $sql = "SELECT TOP 1 he.id, he.empleado_id, he.empresa_id, he.descripcion, he.cantidad,
                       CONVERT(VARCHAR(10), he.fechaAsignacion, 23) AS fechaAsignacion, " . implode(', ', $cols) . "
                FROM dbo.horarios_empleado he " . implode(' ', $joins) . "
                WHERE he.empleado_id = ? AND he.idEstatus = 1
                ORDER BY CASE WHEN he.empresa_id = ? THEN 0 WHEN he.empresa_id IS NULL THEN 1 ELSE 2 END, he.fechaAsignacion DESC, he.id DESC";
        return $this->db->query($sql, array($empleado_id, (int) $empresa_id))->row_array();
    }

    /**
     * Asigna a cada destino el mismo horario por día que la asignación vigente
     * del origen, con la fecha indicada (igual que "Asignar Horario": se agrega
     * una asignación nueva y la más reciente es la que vale).
     */
    public function copiarHorario($origen, array $destinos, $empresa_id, $fecha, $empresa_origen = null){
        $vig = $this->db->query(
            "SELECT TOP 1 id FROM dbo.horarios_empleado WHERE empleado_id = ? AND idEstatus = 1
             ORDER BY CASE WHEN empresa_id = ? THEN 0 WHEN empresa_id IS NULL THEN 1 ELSE 2 END, fechaAsignacion DESC, id DESC", array($origen, (int) $empresa_origen))->row_array();
        if (!$vig) return array('exito' => false, 'mensaje' => 'El empleado origen no tiene horario asignado.');

        $copiados = array();
        $omitidos = array();
        $this->db->trans_start();
        foreach ($destinos as $d) {
            if ((string) $d === (string) $origen) continue;
            // el destino debe ser empleado activo de la planta elegida
            $ok = $this->db->query("SELECT COUNT(*) AS n FROM dbo.empleados WHERE empleado_id = ? AND empresa_id = ? AND estatus = 'A'",
                                   array($d, $empresa_id))->row_array();
            if (!$ok || (int) $ok['n'] === 0) { $omitidos[] = $d; continue; }
            $this->db->query(
                "INSERT INTO dbo.horarios_empleado
                    (empleado_id, empresa_id, descripcion, idhorario_lunes, idhorario_martes, idhorario_miercoles,
                     idhorario_jueves, idhorario_viernes, idhorario_sabado, idhorario_domingo,
                     fechaAsignacion, idEstatus, rotativo, cantidad, create_dtm)
                 SELECT ?, ?, o.descripcion, o.idhorario_lunes, o.idhorario_martes, o.idhorario_miercoles,
                        o.idhorario_jueves, o.idhorario_viernes, o.idhorario_sabado, o.idhorario_domingo,
                        ?, 1, o.rotativo, o.cantidad, GETDATE()
                 FROM dbo.horarios_empleado o WHERE o.id = ?",
                array($d, $empresa_id, $fecha, $vig['id']));
            $copiados[] = $d;
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return array('exito' => false, 'mensaje' => 'No se pudo copiar el horario.');

        $msg = 'Horario copiado a ' . count($copiados) . ' empleado(s).';
        if ($omitidos) $msg .= ' Se omitieron (no están activos en la planta): ' . implode(', ', $omitidos) . '.';
        return array('exito' => count($copiados) > 0, 'mensaje' => $msg, 'copiados' => $copiados, 'omitidos' => $omitidos);
    }
}

?>
