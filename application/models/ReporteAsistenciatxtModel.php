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
        $Code = ($Code === '' || $Code === null) ? null : $Code;
        $puesto = ($puesto === '' || $puesto === null) ? null : $puesto;
        $NoEmpleado = ($NoEmpleado === '' || $NoEmpleado === null) ? null : $NoEmpleado;


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

        file_put_contents($file, ""); //creamos el archivo vacio para que siempre exista

        // Escribir datos al archivo (los valores ya vienen con sus prefijos |1|, |1.1|, |1.2| desde el SP)
        $campos = array(
    'PremioporAsistencia',
    'PremioporPuntualidad',
    'LunesFalta',
    'MartesFalta',
    'MiercolesFalta',
    'JuevesFalta',
    'ViernesFalta',
    'SabadoFalta',
    'DomingoFalta',
);

foreach ($result as $fila) {
    file_put_contents($file, $fila['PrimerRenglon'] . PHP_EOL, FILE_APPEND);
    foreach ($campos as $campo) {
        if (isset($fila[$campo]) && $fila[$campo] !== '' && $fila[$campo] != 'NULL') {
            file_put_contents($file, $fila[$campo] . PHP_EOL, FILE_APPEND);
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
