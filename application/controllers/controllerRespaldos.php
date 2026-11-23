<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogo de respaldos (solo administrador). Los respaldos los hace el job
 * "Nominas - Respaldos" de SQL Server Agent; aquí se configuran y se consultan.
 */
class controllerRespaldos extends CI_Controller {

    private $tipos = array('BASE', 'LOG', 'CHECADAS', 'CODIGO', 'ROSTROS');

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('RespaldoModel');
    }

    public function index(){
        header('Access-Control-Allow-Origin: *');
        $data = [];
        if (isset($this->session->userdata['login']) && $this->session->userdata['login']['exito']) {
            $data['user'] = $this->session->userdata['login']['user'];
            $this->load->view('header', $data);
            if ($this->esAdmin(false)) {
                $this->load->view('respaldosView', $data);
            } else {
                echo '<div class="page-wrapper"><div class="container-fluid"><div class="alert alert-warning mt-4">'
                   . '<i class="fa fa-lock"></i> Los respaldos solo los puede ver el administrador.</div></div></div>';
            }
            $this->load->view('footer', $data);
        } else {
            $this->load->view('login');
        }
    }

    public function datos(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        echo json_encode(array(
            'exito'  => true,
            'config' => $this->RespaldoModel->config(),
            'estado' => $this->RespaldoModel->estado(),
            'copiaLocal' => $this->RespaldoModel->copiaLocalEstado(),
        ));
    }

    public function historial(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $tipo = $this->input->post('tipo');
        if (!in_array($tipo, $this->tipos, true)) $tipo = '';
        $dias = (int) $this->input->post('dias');
        if ($dias <= 0 || $dias > 400) $dias = 7;
        echo json_encode($this->RespaldoModel->historial($tipo, $dias));
    }

    public function guardarConfig(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $tipo = $this->input->post('tipo');
        if (!in_array($tipo, $this->tipos, true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Tipo de respaldo no válido.']);
            return;
        }
        $d = array(
            'activo'        => $this->input->post('activo') ? 1 : 0,
            'hora'          => null,
            'cadaMinutos'   => null,
            'retencionDias' => (int) $this->input->post('retencionDias'),
            'diasExportar'  => null,
        );
        if ($d['retencionDias'] < 1 || $d['retencionDias'] > 3650) {
            echo json_encode(['exito' => false, 'mensaje' => 'Los días a guardar deben ser entre 1 y 3650.']);
            return;
        }
        if ($tipo === 'LOG') {
            $d['cadaMinutos'] = (int) $this->input->post('cadaMinutos');
            if ($d['cadaMinutos'] < 15 || $d['cadaMinutos'] > 1440) {
                echo json_encode(['exito' => false, 'mensaje' => 'El log se respalda cada 15 a 1440 minutos (el job revisa cada 15).']);
                return;
            }
        } else {
            $d['hora'] = (string) $this->input->post('hora');
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['hora'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'La hora debe tener formato HH:mm.']);
                return;
            }
        }
        if ($tipo === 'CHECADAS') {
            $d['diasExportar'] = (int) $this->input->post('diasExportar');
            if ($d['diasExportar'] < 1 || $d['diasExportar'] > 400) {
                echo json_encode(['exito' => false, 'mensaje' => 'Los días a exportar deben ser entre 1 y 400.']);
                return;
            }
        }
        $this->RespaldoModel->guardarConfig($tipo, $d);
        echo json_encode(['exito' => true, 'mensaje' => 'Configuración guardada.']);
    }

    /**
     * Carpeta principal y hasta 3 copias: otra carpeta / red, la carpeta que sincroniza
     * MEGA, Google Drive, OneDrive o Dropbox en el servidor, o Azure (directo).
     * Antes de guardar se prueba que SQL Server pueda escribir en cada una.
     */
    public function guardarDestinos(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        ini_set('max_execution_time', 0);
        $nombres = array('CARPETA' => 'Carpeta', 'MEGA' => 'MEGA', 'GDRIVE' => 'Google Drive', 'ONEDRIVE' => 'OneDrive',
                         'DROPBOX' => 'Dropbox', 'AZURE' => 'Azure');
        $ruta1 = $this->limpiarRuta($this->input->post('ruta1'));
        if ($ruta1 !== '' && !$this->rutaValida($ruta1)) {
            echo json_encode(['exito' => false, 'mensaje' => 'La carpeta principal no es válida. Usa una ruta como D:\\Respaldos o \\\\servidor\\carpeta.']);
            return;
        }
        $d = $this->RespaldoModel->destinos();
        $principal = $ruta1 === '' ? $d['predeterminada'] : $ruta1;

        $recibidas = $this->input->post('copias');
        if (!is_array($recibidas)) $recibidas = array();
        if (count($recibidas) > 3) {
            echo json_encode(['exito' => false, 'mensaje' => 'Puedes tener hasta 3 copias.']);
            return;
        }
        $copias = array();
        $vistas = array(strtolower($principal) => true);
        foreach (array_values($recibidas) as $i => $c) {
            $num = 'La copia ' . ($i + 1);
            $servicio = isset($c['servicio']) && isset($nombres[$c['servicio']]) ? $c['servicio'] : 'CARPETA';
            if ($servicio === 'AZURE') {
                $ruta = rtrim(trim(isset($c['ruta']) ? (string) $c['ruta'] : ''), '/');
                $sas  = ltrim(trim(isset($c['sas']) ? (string) $c['sas'] : ''), '?');
                if ($sas === '') $sas = $this->RespaldoModel->sasGuardado($ruta);   // vacío = conservar el guardado
                if (!preg_match('#^https://[a-z0-9]{3,24}\.blob\.core\.windows\.net/[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])?$#', $ruta)) {
                    echo json_encode(['exito' => false, 'mensaje' => $num . ' (Azure): la URL del contenedor debe ser como https://micuenta.blob.core.windows.net/respaldos']);
                    return;
                }
                if (!preg_match('/(^|&)sig=/', $sas)) {
                    echo json_encode(['exito' => false, 'mensaje' => $num . ' (Azure): pega el token SAS del contenedor (empieza con sv= y contiene sig=).']);
                    return;
                }
            } else {
                $ruta = $this->limpiarRuta(isset($c['ruta']) ? $c['ruta'] : '');
                $sas  = '';
                if ($ruta === '' || !$this->rutaValida($ruta)) {
                    echo json_encode(['exito' => false, 'mensaje' => $num . ' (' . $nombres[$servicio] . '): escribe una carpeta válida, como D:\\Respaldos o \\\\servidor\\carpeta.']);
                    return;
                }
            }
            if (isset($vistas[strtolower($ruta)])) {
                echo json_encode(['exito' => false, 'mensaje' => $num . ' repite una carpeta que ya se usa.']);
                return;
            }
            $vistas[strtolower($ruta)] = true;
            $copias[] = array('servicio' => $servicio, 'ruta' => $ruta, 'sas' => $sas);
        }

        // se prueba cada destino en el servidor de SQL
        $error = $this->RespaldoModel->probarCarpeta($principal);
        foreach ($copias as $i => $c) {
            if ($error) break;
            $error = $c['servicio'] === 'AZURE' ? $this->RespaldoModel->probarNube($c['ruta'], $c['sas'])
                                                : $this->RespaldoModel->probarCarpeta($c['ruta']);
            if ($error) $error = 'Copia ' . ($i + 1) . ' (' . $nombres[$c['servicio']] . '): ' . $error;
        }
        if ($error) { echo json_encode(['exito' => false, 'mensaje' => $error]); return; }

        $this->RespaldoModel->guardarDestinos($ruta1, $copias);
        $texto = array();
        foreach ($copias as $c) $texto[] = $nombres[$c['servicio']] . ' (' . $c['ruta'] . ')';
        echo json_encode(['exito' => true, 'mensaje' => 'Ubicación guardada. Los siguientes respaldos se guardan en ' . $principal
            . ($texto ? ' y en: ' . implode(', ', $texto) : '') . '.']);
    }

    public function respaldarAhora(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        ini_set('max_execution_time', 0);
        $tipo = $this->input->post('tipo');
        if (!in_array($tipo, $this->tipos, true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Tipo de respaldo no válido.']);
            return;
        }
        $u = $this->session->userdata['login']['user'];
        echo json_encode($this->RespaldoModel->respaldarAhora($tipo, isset($u->usuario) ? $u->usuario : ''));
    }

    /**
     * Tarea programada de Windows "Nominas - Respaldo codigo" (cada hora):
     *   C:\xampp\php\php.exe C:\xampp\htdocs\nomina\index.php controllerRespaldos codigoProgramado
     * Solo desde la línea de comandos; hace el respaldo del código si ya toca hoy
     * (con el argumento "ahora" lo hace en ese momento).
     */
    public function codigoProgramado($cuando = ''){
        if (!is_cli()) { show_404(); return; }
        if ($cuando !== 'ahora' && !$this->RespaldoModel->tocaCodigo()) { echo "No toca todavía.\n"; return; }
        $r = $this->RespaldoModel->respaldoCodigo($cuando === 'ahora' ? 'Manual (línea de comandos)' : 'Programado');
        echo $r['mensaje'] . "\n";
    }

    /**
     * Tarea programada de Windows "Nominas - Respaldo rostros" (cada hora, en EDGAR):
     *   C:\xampp\php\php.exe C:\xampp\htdocs\nomina\index.php controllerRespaldos rostrosProgramado
     * Respalda los rostros de los relojes si ya toca (domingo a la hora configurada; si se perdió, de noche).
     * "ahora" lo hace en ese momento; un número es el RespaldoLog que ya creó "Respaldar ahora" en la web.
     */
    public function rostrosProgramado($cuando = ''){
        if (!is_cli()) { show_404(); return; }
        if (ctype_digit((string) $cuando)) { $r = $this->RespaldoModel->respaldoRostros('', (int) $cuando); echo $r['mensaje'] . "\n"; return; }
        if ($cuando !== 'ahora' && !$this->RespaldoModel->tocaRostros()) { echo "No toca todavía.\n"; return; }
        $r = $this->RespaldoModel->respaldoRostros($cuando === 'ahora' ? 'Manual (línea de comandos)' : 'Programado');
        echo $r['mensaje'] . "\n";
    }

    /**
     * Tarea programada de Windows "Nominas - Copia respaldos MEGA" (en EDGAR, cada hora):
     *   C:\xampp\php\php.exe C:\xampp\htdocs\nomina\index.php controllerRespaldos copiaLocal
     * Baja a la carpeta de esta computadora (ConfigSistema respaldo_copia_local, p. ej. la de MEGA)
     * los respaldos que aún no tiene; la aplicación de la nube los sube.
     */
    public function copiaLocal(){
        if (!is_cli()) { show_404(); return; }
        $r = $this->RespaldoModel->copiaLocal();
        echo $r['error'] ? 'ERROR: ' . $r['error'] . "\n" : 'Copiados ' . $r['copiados'] . ' (' . $r['mb'] . ' MB), borrados ' . $r['borrados'] . ".\n";
    }

    /** Descarga el ZIP de checadas, del código o de los rostros (están en el servidor de SQL). */
    public function descargar($id = null){
        if (!$this->esAdmin(false)) { show_error('Solo el administrador puede descargar respaldos.', 403); return; }
        ini_set('max_execution_time', 0);
        $archivo = $this->RespaldoModel->archivoChecadas((int) $id);
        if (!$archivo && $this->RespaldoModel->enviarArchivoCodigo((int) $id)) exit;
        if (!$archivo && $this->RespaldoModel->enviarArchivoRostros((int) $id)) exit;
        if (!$archivo) { show_error('No se encontró el archivo del respaldo.', 404); return; }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $archivo['nombre'] . '"');
        header('Content-Length: ' . strlen($archivo['datos']));
        echo $archivo['datos'];
    }

    // ------------------------------------------------------------------

    /** D:\carpeta  o  \\servidor\recurso[\carpeta], sin caracteres raros. */
    private function rutaValida($ruta){
        $bs = chr(92);
        if (strpbrk($ruta, '"\'<>|*?;') !== false || strlen($ruta) > 250) return false;
        if (preg_match('/^[A-Za-z]:$/', substr($ruta, 0, 2)) && substr($ruta, 2, 1) === $bs) return true;
        if (substr($ruta, 0, 2) === $bs . $bs) {
            $partes = explode($bs, substr($ruta, 2));
            return count($partes) >= 2 && $partes[0] !== '' && $partes[1] !== '';
        }
        return false;
    }

    private function limpiarRuta($ruta){
        $ruta = trim(str_replace('/', '\\', (string) $ruta));
        return strlen($ruta) > 3 ? rtrim($ruta, '\\') : $ruta;
    }

    private function esAdmin($responder = true){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        if ($u && isset($u->idPerfil) && $u->idPerfil == 1) return true;
        if ($responder) echo json_encode(['exito' => false, 'mensaje' => 'Solo el administrador puede ver los respaldos.']);
        return false;
    }
}
