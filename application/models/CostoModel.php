<?php
//MODELO usuario
class CostoModel extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**-------------------------------ConsultaReporteDiario---------------------------------------------  */
    public function consulta($fechaInicio,$fechaFin)
    {        
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $sp = "ConsultaReporteCosto ?,? "; 
        $params = array('FechaInicio' => $fechaInicio,'FechaFin' => $fechaFin);
        
        $result = $this->db->query($sp,$params);
        
        return  $result->result_array();        
    }

    // Convierte el nombre de la empresa en algo seguro para usar como nombre
    // de archivo: sin espacios, acentos, ni caracteres raros.
    private function sanearNombreArchivo($texto)
    {
        $texto = trim($texto);
        // Quita acentos
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        // Solo deja letras, numeros, guiones y guion bajo
        $texto = preg_replace('/[^A-Za-z0-9_-]+/', '_', $texto);
        $texto = trim($texto, '_');
        return $texto === '' ? 'empresa' : $texto;
    }

    
// Deja solo digitos, punto y signo; redondea a 2 y quita ceros sobrantes:
// "1,093,599.93" -> 1093599.93 | "0.00" -> 0 | "4,160.00" -> 4160 | "32,819.10" -> 32819.1
private function formatearNumero($valor)
{
    if ($valor === null) return '0';
    $s = preg_replace('/[^0-9.\-]/', '', (string)$valor);
    if ($s === '' || $s === '-' || $s === '.') return '0';

    $out = number_format(round((float)$s, 2), 2, '.', ',');   // <-- antes era '' al final
    $out = rtrim(rtrim($out, '0'), '.');
    return ($out === '-0' || $out === '') ? '0' : $out;
}

// Para columnas de texto: sin ';', sin saltos de linea ni caracteres de control
private function limpiarTexto($valor)
{
    if ($valor === null) return '';
    $valor = preg_replace('/[\x00-\x1F\x7F]/', '', (string)$valor);
    $valor = str_replace(';', '', $valor);
    return trim($valor);
}
    
   
    public function ExportarArchivo($fechaInicio,$fechaFin){

        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);

        $sp = "ConsultaReporteCosto  ?,? ";
        $params = array(
            'FechaInicio' => $fechaInicio,
            'FechaFin' => $fechaFin);  
        
        $result = $this->db->query($sp,$params);
        $result = $result->result_array();

        // --- DIAGNOSTICO TEMPORAL: quitar despues de revisar ---
       /* file_put_contents(
            'debug_result.json',
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents('debug_conteo.txt', 'Total de filas recibidas: ' . count($result));
        */
        // --- FIN DIAGNOSTICO ---

        // Nombre de archivo UNICO en disco: evita que dos exportaciones
        // simultaneas (dos usuarios, dos plantas, doble clic) se pisen entre si.
        $carpeta = 'reportescosto/'; // crea esta carpeta con permisos de escritura
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }
        $fileDisco = $carpeta . 'costo_' . uniqid() . '.txt';

        // Nombre "bonito" que vera el usuario al descargar, con la empresa
        // seleccionada y el rango de fechas.
        //$nombreEmpresa = $this->sanearNombreArchivo($this->nombreEmpresa($empresa_id));
        $fechaFinLimpia = $this->sanearNombreArchivo($fechaFin);
        $nombreDescarga = "costo_{$fechaFinLimpia}.txt";

        $mapaColumnas = array(
        'CENTRO_COSTO' => 'CENTRO_COSTO',
        'DEPARTAMENTO' => 'DEPARTAMENTO',
        'PLANILLA'     => 'PLANILLA',
        'PERCEP'       => 'PERCEP',
        'HOREXT'       => 'HOREXT',
        'GRATIF'       => 'GRATIF',
        'INCENT'       => 'INCENT',
        'PPYADE'       => 'PPYADE',
        'CROM'         => 'CROM',
        'TOTPER'       => 'TOTPER',
        'SUBEMP'       => 'SUBEMP',
        'ISPT'         => 'ISPT',
        'IMSS'         => 'IMSS',
        'CAJAAHO'      => 'CAJAAHO',
        'INFONAVIT'    => 'INFONAVIT',
        'PREAHO'       => 'PREAHO',
        'PREEMP'       => 'PREEMP',   // <-- antes apuntaba a 'DEDUCCIONES', revisa cual regresa el SP
        'INTERGAS'     => 'INTERGAS',
        'OTRASRET'     => 'OTRASRET',
        'TOTAL'        => 'TOTAL',
            );
            $columnasTexto = array('CENTRO_COSTO', 'DEPARTAMENTO');

            $lineas = array();
            $lineas[] = implode(';', array_keys($mapaColumnas)); // CENTRO_COSTO;DEPARTAMENTO;...

            foreach ($result as $fila) {
                $valores = array();
                foreach ($mapaColumnas as $encabezado => $llaveReal) {
                    $valor = array_key_exists($llaveReal, $fila) ? $fila[$llaveReal] : null;

                    if (in_array($encabezado, $columnasTexto)) {
                        $valores[] = $this->limpiarTexto($valor);
                    } else {
                        $valores[] = $this->formatearNumero($valor); // PLANILLA tambien: 15 -> 15
                    }
                }
                $lineas[] = implode(';', $valores);
            }
            $contenido = implode("\r\n", $lineas) . "\r\n";
        file_put_contents($fileDisco, $contenido, LOCK_EX);

        if (file_exists($fileDisco)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            // Aqui usamos el nombre "bonito" (con la empresa), no el nombre
            // interno en disco.
            header('Content-Disposition: attachment; filename=' . $nombreDescarga);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileDisco));
            ob_clean();
            flush();
            readfile($fileDisco);
            // Borramos el archivo temporal despues de servirlo (limpieza)
            unlink($fileDisco);
            exit;
        } else {
            echo "Hubo un error al momento de crear el archivo, verifique los permisos de las carpetas del servidor.";
        }

        return $result;
    }
/*
    public function consultacosto(){

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $this->db->select('TIPO
        ,CLAVE
        ,GPO
        ,EMPRESA
        ,NOMBRECONCEPTO
        ,IMPORTE_GRAVABLE
        ,IMPORTE_EXENTO
        ,TOTAL');       
        $this->db->from('costoMicro');
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }
*/

}

?>