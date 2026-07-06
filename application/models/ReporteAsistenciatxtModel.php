<?php
class ReporteAsistenciatxtModel extends CI_Model{

    function __construct(){
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function consulta($NoSemana, $anio, $planta, $Code, $puesto, $NoEmpleado){        
        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);

        $sp = "ConsultaAsistenciaExportar ?,?,?,?,?,?"; 
        $params = array(
            'NoSemana' => $NoSemana,
            'anio' => $anio,
            'planta' => $planta,
            'Code' => $Code,
            'puesto' => $puesto,
            'NoEmpleado' => $NoEmpleado,
        );
        $result = $this->db->query($sp, $params);        
        return $result->result_array();        
    }

    public function ExportarArchivo($NoSemana, $anio, $planta, $Code, $puesto, $NoEmpleado){
        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);
        
        if(file_exists('reporteAsistenciatxt.txt')){
            unlink('reporteAsistenciatxt.txt');
        }
        
        $sp = "ConsultaAsistenciaExportar ?,?,?,?,?,?";
        $params = array(
            'NoSemana' => $NoSemana,
            'anio' => $anio,
            'planta' => $planta,
            'Code' => $Code,
            'puesto' => $puesto,
            'NoEmpleado' => $NoEmpleado);        
        
        $result = $this->db->query($sp, $params);
        $result = $result->result_array();   
      
        $file = "reporteAsistenciatxt.txt";
        
        // Crear archivo con datos formateados
        if (count($result) > 0) {
            // Agrupar datos por empleado
            $empleados = array();
            
            foreach ($result as $row) {
                $numEmpleado = $row['PrimerRenglon']; // Número de empleado con formato
                
                if (!isset($empleados[$numEmpleado])) {
                    $empleados[$numEmpleado] = array();
                }
                
                $empleados[$numEmpleado][] = $row;
            }
            
            // Escribir datos al archivo
            foreach ($empleados as $numEmpleado => $datos) {
                // Escribir línea principal del empleado
                file_put_contents($file, "|" . $numEmpleado . PHP_EOL, FILE_APPEND);
                
                // Escribir líneas adicionales
                foreach ($datos as $fila) {
                    // Formato: |1.1|CODIGO,VALOR
                    if (isset($fila['PremioPorAsistencia'])) {
                        file_put_contents($file, "|1.1|" . $fila['PremioPorAsistencia'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['PremioPorPuntualidad'])) {
                        file_put_contents($file, "|1.1|" . $fila['PremioPorPuntualidad'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['LunesFalta']) && $fila['LunesFalta'] != 'NULL') {
                        file_put_contents($file, "|1.2|" . $fila['LunesFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['MartesFalta']) && $fila['MartesFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['MartesFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['MiercolesFalta']) && $fila['MiercolesFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['MiercolesFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['JuevesFalta']) && $fila['JuevesFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['JuevesFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['ViernesFalta']) && $fila['ViernesFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['ViernesFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['SabadoFalta']) && $fila['SabadoFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['SabadoFalta'] . PHP_EOL, FILE_APPEND);
                    }
                    if (isset($fila['DomingoFalta']) && $fila['DomingoFalta'] != 'NULL') {
                        file_put_contents($file, "|1.1|" . $fila['DomingoFalta'] . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
        
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
        } else {
            echo "Hubo un error al momento de crear el archivo, verifique los permisos de las carpetas del servidor.";
        }
        
        return $result;    
    }
}
?>
