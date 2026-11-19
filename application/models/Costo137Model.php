<?php
//MODELO usuario
class Costo137Model extends CI_Model{

    function ___construct(){
        parent::___construct();
        $this->load->library('form_validation');
    }

    public function consulta($fechaInicio,$fechaFin,$empresa_id)
    {
        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);

        $sp = "ConsultaReporte137 ?,?,? ";
        $params = array(
            'FechaInicio' => $fechaInicio,
            'FechaFin' => $fechaFin,
            'empresa_id' => $empresa_id,
        );
        $result = $this->db->query($sp,$params);
        return $result->result_array();
    }

    // Obtiene el nombre de la empresa directo de la BD, igual que lo hace la
    // propia SP (SELECT nombre FROM empresas WHERE id=@EmpresaID). Asi no
    // dependemos de que el texto del combobox llegue bien por POST.
    private function nombreEmpresa($empresa_id)
    {
        $row = $this->db->query(
            "SELECT nombre FROM empresas WHERE id = ?",
            array((int)$empresa_id)
        )->row_array();

        return $row ? $row['nombre'] : 'empresa';
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

    // "Torreón unificado": grupos de pago que van juntos en un solo 137, en este orden
    // (empresas.code entre paréntesis). El CROM de cada grupo sale en la columna CROM
    // de su empresa (la SP lo trae del servidor <empresa>_CROM), no como grupo aparte.
    const TORREON = -1;
    private $gruposTorreon = array(
        10, // TT1  TERMINADO
        6,  // LAV  LAVANDERIA
        3,  // USC  CORTE
        4,  // SPM  DESARROLLO (+ SPMCROM en CROM)
        7,  // PLC  PLANCHAS
        8,  // SB1  PROCESOS_SECOS
        1,  // AE1  ACABADOS_ESPECIALES
        40, // DELTA2
    );

    // Columnas numéricas del 137 (las que se suman en el total)
    private $columnasImporte = array('SUELDO','PUNT','ASIS','DESP','INCENT','PREMDES','CROM','HEXTRA',
        'OTRAPER','TOTALPER','IMPUESTOS','INFONAVIT','FALTINC','DEDUCCIONES','TOTALPAGAR');

    private function reporte137($fechaInicio, $fechaFin, $empresa_id)
    {
        $sp = "ConsultaReporte137  ?,?,? ";
        $params = array(
            'FechaInicio' => $fechaInicio,
            'FechaFin' => $fechaFin,
            'empresa_id' => $empresa_id);
        return $this->db->query($sp, $params)->result_array();
    }

    // Un 137 por grupo, uno tras otro: renglón de título por grupo y al final el total
    // de Torreón (suma de los renglones de empleado, no de los subtotales por depto).
    // Se arma por grupo porque los números de empleado se repiten entre empresas.
    private function reporte137Torreon($fechaInicio, $fechaFin)
    {
        $grupos = $this->db->query(
            "SELECT id, nombre, ISNULL(code, nombre) AS code FROM empresas WHERE id IN ?",
            array($this->gruposTorreon)
        )->result_array();
        $porId = array();
        foreach ($grupos as $g) $porId[(int)$g['id']] = $g;

        $total = array_fill_keys($this->columnasImporte, 0.0);
        $filas = array();
        foreach ($this->gruposTorreon as $id) {
            if (!isset($porId[$id])) continue;
            $g = $porId[$id];
            $titulo = $g['code'] === $g['nombre'] ? $g['nombre'] : $g['code'] . ' - ' . $g['nombre'];
            $filas[] = array('DEPARTAMENTO' => '', 'NOMBRE' => '=== ' . $titulo . ' ===');
            foreach ($this->reporte137($fechaInicio, $fechaFin, $id) as $fila) {
                $filas[] = $fila;
                if (trim((string)$fila['DEPARTAMENTO']) === '') continue;   // subtotal de depto
                foreach ($this->columnasImporte as $c) $total[$c] += (float)$fila[$c];
            }
        }
        $fila = array('DEPARTAMENTO' => '', 'NOMBRE' => 'TOTAL TORREON');
        foreach ($total as $c => $v) $fila[$c] = number_format($v, 2, '.', '');
        $filas[] = $fila;
        return $filas;
    }

    public function ExportarArchivo($fechaInicio,$fechaFin,$empresa_id){
        ini_set('memory_limit','512M');
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');
        ini_set('max_execution_time', 0);

        $torreon = (int)$empresa_id === self::TORREON;
        $result = $torreon ? $this->reporte137Torreon($fechaInicio, $fechaFin)
                           : $this->reporte137($fechaInicio, $fechaFin, $empresa_id);

        // Nombre de archivo UNICO en disco: evita que dos exportaciones
        // simultaneas (dos usuarios, dos plantas, doble clic) se pisen entre si.
        $carpeta = 'reportes137/'; // crea esta carpeta con permisos de escritura
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }
        $fileDisco = $carpeta . 'costo137_' . uniqid() . '.txt';

        // Nombre "bonito" que vera el usuario al descargar, con la empresa
        // seleccionada y el rango de fechas.
        $nombreEmpresa = $torreon ? 'TORREON' : $this->sanearNombreArchivo($this->nombreEmpresa($empresa_id));
        $fechaFinLimpia = $this->sanearNombreArchivo($fechaFin);
        $nombreDescarga = "137_{$nombreEmpresa}_{$fechaFinLimpia}.txt";

        $mapaColumnas = array(
            'DEPARTAMENTO' => 'DEPARTAMENTO',
            'NOMBRE'       => 'NOMBRE',
            'SUELDO'       => 'SUELDO',
            'PUNT'         => 'PUNT',
            'ASIS'         => 'ASIS',
            'DESP'         => 'DESP',
            'INCENT'       => 'INCENT',
            'PREMDES'      => 'PREMDES',
            'CROM'         => 'CROM',
            'HEXTRA'       => 'HEXTRA',
            'OTRAPER'      => 'OTRAPER',
            'TOTALPER'     => 'TOTALPER',
            'IMPUESTOS'    => 'IMPUESTOS',
            'INFONAVIT'    => 'INFONAVIT',
            'FALTINC'      => 'FALTINC',
            'DEDUCCIONES'  => 'DEDUCCIONES',
            'TOTALPAGAR'   => 'TOTALPAGAR',
        );

        // Construimos TODO el contenido en memoria primero...
        $lineas = array();
        $lineas[] = "DEPARTAMENTO;NOMBRE;SUELDO;PUNT;ASIS;DESP;INCENT;PREMDES;CROM;HEXTRA;OTRAPER;TOTALPER;IMPUESTOS;INFONAVIT;FALT/INC;DEDUCCIONES;TOTAL PAGAR;";

        foreach ($result as $fila) {
            $valores = array();
            foreach ($mapaColumnas as $llaveReal) {
                $valores[] = isset($fila[$llaveReal]) ? $fila[$llaveReal] : '';
            }
            $lineas[] = implode(';', $valores);
        }

        // ...y escribimos UNA sola vez (una llamada, no cientos)
        $contenido = implode(PHP_EOL, $lineas) . PHP_EOL;
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
        } else {
            echo "Hubo un error al momento de crear el archivo, verifique los permisos de las carpetas del servidor.";
        }

        return $result;
    }
}
?>