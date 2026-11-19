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

    public function actualizaPlanta($NoSemana, $anio, $planta, $tiempoExtra, $diasTiempoExtra = '1111111'){
        ini_set('max_execution_time', 0);
        $sp = "ActualizaChecadasBiostarTiempoExtra ?,?,?,?,?,?,?,?";
        $params = array(
            'NoSemana'        => intval($NoSemana),
            'anio'            => $anio,
            'planta'          => intval($planta),
            'Code'            => null,
            'puesto'          => null,
            'NoEmpleado'      => null,
            'tiempoExtra'     => intval($tiempoExtra),
            'diasTiempoExtra' => $diasTiempoExtra,
        );
        return $this->db->query($sp, $params);
    }

    public function ExportarArchivo($NoSemana, $anio, $planta, $Code, $puesto, $NoEmpleado){
        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);

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

        // ---------------------------------------------------------------
        // AJUSTE: antes este método escribía el reporte en un archivo
        // físico de nombre fijo (reporteAsistenciatxt.txt) con
        // file_put_contents()/unlink() y luego lo releía con readfile().
        // Como el nombre es fijo y compartido entre TODAS las peticiones
        // (dos usuarios exportando al mismo tiempo, o un doble clic en
        // "Descargar" que dispara dos peticiones casi simultáneas), en
        // Windows el segundo proceso encontraba el archivo bloqueado por
        // el primero -> "failed to open stream: Resource temporarily
        // unavailable". Ese warning se imprime al output ANTES de llegar
        // a los header() de abajo, y por eso salía también "Cannot
        // modify header information - headers already sent".
        //
        // El frontend (Modulo.fnObtenerDatos) ya arma la descarga él
        // mismo con Blob + a.download a partir del cuerpo de la
        // respuesta AJAX, así que el archivo en disco nunca fue
        // necesario: basta con construir el texto en memoria y mandarlo
        // directo como respuesta. Esto elimina la condición de carrera
        // de raíz (ya no hay disco ni nombre compartido de por medio).
        // ---------------------------------------------------------------
        $campos = array(
            'PremioporAsistencia',
            'PremioporPuntualidad',
            'DescansoTrabajado',
            'PrimaDominical',
             'DiaFestivo',
            'LunesFalta',
            'MartesFalta',
            'MiercolesFalta',
            'JuevesFalta',
            'ViernesFalta',
            'SabadoFalta',
            'DomingoFalta',
        );

        $contenido = '';
        foreach ($result as $fila) {
            // AJUSTE 2026-11-19: el formato de Microsip ahora lleva DIAS_VACACIONES y TIPO_MOVIMIENTO al
            // final del renglón |1|. Las semanas procesadas antes del cambio tienen guardado el renglón
            // viejo (7 campos): se completa con 0 vacaciones y "A" (solo agregar), que es exactamente como
            // se importaba antes, para que no truene con el formato nuevo. Al volver a procesar la
            // semana ya sale con las vacaciones y "R".
            $renglon = $fila['PrimerRenglon'];
            if (strpos($renglon, '|1|') === 0 && substr_count($renglon, ',') === 6) {
                $renglon .= ',0,A';
            }
            $contenido .= $renglon . PHP_EOL;
            foreach ($campos as $campo) {
                if (isset($fila[$campo]) && $fila[$campo] !== '' && $fila[$campo] != 'NULL') {
                    $contenido .= $fila[$campo] . PHP_EOL;
                }
            }
        }

        // Por si algún warning/notice ajeno a esto ya escribió algo al
        // buffer de salida antes de llegar aquí, lo limpiamos para que
        // los header() de abajo no truenen por "headers already sent".
        if (ob_get_length() !== false) {
            ob_clean();
        }

        $fileName = 'reporteAsistenciatxt.txt';

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$fileName);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($contenido));
        echo $contenido;

        return $result;    
    }
}
?>