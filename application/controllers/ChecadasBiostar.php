<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use CodeIgniter\Database\Exceptions\DataException;


class ChecadasBiostar extends CI_Controller {
	
	public function construct()
	{
		header('Access-Control-Allow-Origin: *');
		$this->load->model('Usuario');
	parent::__construct();
		if(isset($this->session->userdata['login'])){
			$logged = true;
		}
		else{
			$logged = false;
		}
    }

	public function index()
	{
		header('Access-Control-Allow-Origin: *');
		$data = [];
		$this->load->model('Usuario');
		$this->load->model('ChecadaBiostar');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['esTorreon'] = $this->usuarioEsTorreon();
           /// $data['checadas'] =json_encode( $this->ChecadaBiostar->consulta(NULL,NULL,NULL,NULL,NULL,NULL));
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
				$this->load->view('ChecadasBiostar', $data);
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
    }
	function procesar()
{
    header('Access-Control-Allow-Origin: *');

		$this->load->model('Usuario');
    $NoSemana     = $this->input->post('NoSemana');
    $anio         = $this->input->post('anio');
    $planta       = $this->input->post('planta');
    $departamento = $this->input->post('departamento');
    $puesto       = $this->input->post('puesto');
    $noempleado   = $this->input->post('noempleado');
    $forzar       = $this->input->post('forzar'); // 0 o 1, viene del botón de confirmación

    if (empty($NoSemana))     $NoSemana = null;
    if (empty($anio))         $anio = null;
    if (empty($planta))       $planta = null;
    if (empty($departamento)) $departamento = null;
    if (empty($puesto))       $puesto = null;
    if (empty($noempleado))   $noempleado = null;
    $forzar = (empty($forzar)) ? 0 : 1;

    // El usuario se toma de la sesión, no del cliente.
    $usuario = null;
    if (isset($this->session->userdata['login']['user'])) {
       $data['user'] =  $this->session->userdata['login']['user'];
		$usuario=$data['user']->usuario;
		//
    }

    if (empty($usuario)) {
        echo json_encode([['Exito' => false, 'YaProcesado' => false, 'Mensaje' => 'Sesión inválida, vuelva a iniciar sesión.']]);
        return;
    }

    $error = $this->semanaInvalida($NoSemana, $anio);
    if ($error) {
        echo json_encode([['Exito' => false, 'YaProcesado' => false, 'Mensaje' => $error]]);
        return;
    }

    $this->load->model('ChecadaBiostar');
    if ($planta === self::TORREON) {
        echo json_encode($this->procesarTorreon($NoSemana, $anio, $departamento, $puesto, $noempleado, $usuario, $forzar));
        return;
    }
    $result = $this->ChecadaBiostar->procesar($NoSemana, $anio, $planta, $departamento, $puesto, $noempleado, $usuario, $forzar);
    echo json_encode($result);
}

/**
 * Torreón unificado: se procesa cada planta de Torreón del usuario. Sin forzar, las que ya estaban
 * procesadas se saltan y se pregunta una sola vez si se reprocesan (con forzar se reprocesan todas).
 */
private function procesarTorreon($NoSemana, $anio, $departamento, $puesto, $noempleado, $usuario, $forzar)
{
    $plantas = $this->plantasTorreon();
    if (!$plantas) return [['Exito' => false, 'YaProcesado' => false, 'Mensaje' => 'No tienes plantas de Torreón asignadas.']];
    $nombres = $this->nombresPlantas($plantas);
    $ok = []; $ya = []; $errores = [];
    foreach ($plantas as $id) {
        $r = $this->ChecadaBiostar->procesar($NoSemana, $anio, (string) $id, $departamento, $puesto, $noempleado, $usuario, $forzar);
        $info = isset($r[0]) ? $r[0] : [];
        $nombre = isset($nombres[$id]) ? $nombres[$id] : $id;
        if (!empty($info['Exito']))           $ok[] = $nombre;
        elseif (!empty($info['YaProcesado'])) $ya[] = $nombre;
        else                                  $errores[] = $nombre . ': ' . (isset($info['Mensaje']) ? $info['Mensaje'] : 'sin respuesta');
    }
    $msg = ($ok ? 'Procesadas ' . count($ok) . ' planta(s) de Torreón: ' . implode(', ', $ok) . '.' : '')
         . ($errores ? "\nCon error:\n" . implode("\n", $errores) : '');
    if ($ya) {
        return [['Exito' => false, 'YaProcesado' => true,
                 'Mensaje' => trim($msg . "\n\n" . count($ya) . ' planta(s) ya estaban procesadas esta semana: ' . implode(', ', $ya)
                                  . ".\n¿Reprocesar TODAS las plantas de Torreón?")]];
    }
    return [['Exito' => (bool) $ok, 'YaProcesado' => false, 'Mensaje' => trim($msg) ?: 'No se procesó ninguna planta.']];
}
/**
 * La semana a procesar debe existir en ese año (1 a 52 o 53, semanas ISO como el SP) y no puede
 * ser posterior a la semana actual. Antes se procesó por error la "semana 111063" (un número de
 * empleado escrito en No Semana) y salía en el Inicio como semana del año 4154.
 */
private function semanaInvalida($NoSemana, $anio)
{
    if ($NoSemana === null || !ctype_digit((string) $NoSemana)) return 'Escribe el número de semana (1 a 53).';
    if ($anio === null || !ctype_digit((string) $anio) || (int) $anio < 2000 || (int) $anio > 2100) return 'Elige un año válido.';
    $mx = new DateTimeZone('America/Mexico_City');   // php.ini trae Europe/Berlin
    $semanas = (int) (new DateTime($anio . '-12-28', $mx))->format('W');
    if ((int) $NoSemana < 1 || (int) $NoSemana > $semanas) return 'El año ' . $anio . ' tiene ' . $semanas . ' semanas; la semana ' . $NoSemana . ' no existe.';
    $lunes = (new DateTime('now', $mx))->setISODate((int) $anio, (int) $NoSemana)->setTime(0, 0);
    $hoy = (new DateTime('now', $mx))->setTime(0, 0);
    if ($lunes > $hoy) return 'La semana ' . $NoSemana . ' de ' . $anio . ' empieza el ' . $lunes->format('d/m/Y') . ': todavía no se puede procesar una semana posterior a la actual.';
    return null;
}

public function ultimoProceso()
{
    header('Access-Control-Allow-Origin: *');
    $NoSemana = $this->input->post('NoSemana');
    $anio     = $this->input->post('anio');
    $planta   = $this->input->post('planta');

    if (empty($NoSemana)) $NoSemana = null;
    if (empty($anio))     $anio = null;
    if (empty($planta))   $planta = null;

    $this->load->model('ChecadaBiostar');
    if ($planta === self::TORREON) {
        // Torreón unificado: cuántas plantas ya se procesaron y el proceso más reciente
        $plantas = $this->plantasTorreon();
        $nombres = $this->nombresPlantas($plantas);
        $hechas = 0; $ultimo = null; $faltan = [];
        foreach ($plantas as $id) {
            $r = $this->ChecadaBiostar->ultimoProceso($NoSemana, $anio, (string) $id);
            $info = isset($r[0]) ? $r[0] : [];
            if (empty($info['YaProcesado'])) { $faltan[] = isset($nombres[$id]) ? $nombres[$id] : $id; continue; }
            $hechas++;
            if (!$ultimo || (string) $info['FechaUltimoProceso'] > (string) $ultimo['FechaUltimoProceso']) $ultimo = $info;
        }
        $texto = $hechas . ' de ' . count($plantas) . ' plantas de Torreón procesadas'
               . ($ultimo ? '; la última por <b>' . htmlspecialchars($ultimo['UsuarioUltimoProceso']) . '</b> el <b>' . htmlspecialchars($ultimo['FechaUltimoProceso']) . '</b>' : '')
               . ($faltan ? '. Falta: ' . htmlspecialchars(implode(', ', $faltan)) : '') . '.';
        echo json_encode([['YaProcesado' => $hechas > 0, 'Texto' => $texto]]);
        return;
    }
    $result = $this->ChecadaBiostar->ultimoProceso($NoSemana, $anio, $planta);
    echo json_encode($result);
}
	function consulta()
    {       
		header('Access-Control-Allow-Origin: *'); 
        $NoSemana=$this->input->post('NoSemana');
        $anio=$this->input->post('anio');
        $planta=$this->input->post('planta');
        $departamento=$this->input->post('departamento');
        $puesto=$this->input->post('puesto');
        $noempleado=$this->input->post('noempleado');
       // echo $NoSemana.$anio.$departamento.$noempleado;
       if (empty($NoSemana)){
        $NoSemana=null;
       }if (empty($anio)){
        $anio=null;
       }if (empty($noempleado)){
        $noempleado=null;
       }if (empty($planta)){
        $planta=null;
       }if (empty($departamento)){
        $departamento=null;
       }
       if (empty($planta)){
        $planta=null;
       }
       if (empty($puesto)){
        $puesto=null;
       }
       // Torreón unificado: todas las plantas de Torreón del usuario juntas (el SP acepta '1,2,3')
       if ($planta === self::TORREON) {
        $plantas = $this->plantasTorreon();
        if (!$plantas) { echo '[]'; return; }
        $planta = implode(',', $plantas);
       }
       // print_r($NoSemana.' '.$anio.' '.$planta);
        //die();
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
		$result =$this->ChecadaBiostar->consulta($NoSemana,$anio,$planta,$departamento,$puesto,$noempleado);
        echo json_encode($result);
    }
    



    function consultaPlanta(){

		header('Access-Control-Allow-Origin: *');
        $result = $this->plantasUsuario();
        // Solo la pantalla de checadas pide la opción "Torreón unificado" (si tiene 2 o más plantas de Torreón)
        if ($this->input->post('unificado') && count($this->plantasTorreon($result)) > 1) {
            array_unshift($result, array('id' => self::TORREON, 'nombre' => 'TORREÓN UNIFICADO (todas)'));
        }
        echo json_encode($result);
    }

    const TORREON = 'TORREON';

    /** Empresas que el usuario puede consultar (Catálogos > Usuarios > Permisos). */
    private function plantasUsuario(){
        $this->load->model('ChecadaBiostar');
        $result = $this->ChecadaBiostar->consultaPlanta();
        if (isset($this->session->userdata['login']['user']->id)) {
            $this->load->model('PermisoModel');
            $result = $this->PermisoModel->filtrarPlantas($this->session->userdata['login']['user']->id, $result);
            $result = $this->PermisoModel->agregarEventuales($this->session->userdata['login']['user']->id, $result);
        }
        return $result;
    }

    /** Ids de las plantas de Torreón (empresas.esTorreon = 1, más EVENTUALES que checa en Torreón) que puede ver el usuario. */
    private function plantasTorreon($plantas = null){
        $this->load->model('PermisoModel');
        $permitidas = array_map('intval', array_column($plantas === null ? $this->plantasUsuario() : $plantas, 'id'));
        $torreon = array_map('intval', array_column($this->db->query(
            "SELECT id FROM dbo.empresas WHERE esTorreon = 1 OR id = ?", array(PermisoModel::EMPRESA_EVENTUALES))->result_array(), 'id'));
        return array_values(array_intersect($permitidas, $torreon));
    }

    private function nombresPlantas(array $ids){
        if (!$ids) return array();
        $r = $this->db->query("SELECT id, nombre FROM dbo.empresas WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")")->result_array();
        return array_column($r, 'nombre', 'id');
    }

    // Descarga las checadas directo de los relojes Suprema (tools/RelojSync)
    function descargarReloj(){
        header('Access-Control-Allow-Origin: *');
        if (!isset($this->session->userdata['login']['user'])) {
            echo json_encode(['Exito' => false, 'Mensaje' => 'Sesión inválida, vuelva a iniciar sesión.']);
            return;
        }
        ini_set('max_execution_time', 0);

        if (!$this->usuarioEsTorreon()) {
            echo json_encode(['Exito' => false, 'Mensaje' => 'La descarga de relojes solo está disponible para usuarios de Torreón.']);
            return;
        }

        $exe = FCPATH . 'tools/RelojSync/publish/RelojSync.exe';
        if (!file_exists($exe)) {
            echo json_encode(['Exito' => false, 'Mensaje' => 'No está instalado RelojSync.exe en tools/RelojSync/publish.']);
            return;
        }

        $salida = [];
        $codigo = 0;
        exec(escapeshellarg($exe) . ' sync 2>&1', $salida, $codigo);
        echo json_encode([
            'Exito'   => $codigo === 0,
            'Mensaje' => implode("\n", $salida),
        ]);
    }

    function ActualizarChecadasNomiplus(){
        
		header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->ActualizarChecadasNomiplus();
        
        echo json_encode($result);
    }
    function consultaDepartamento(){

        $planta=$this->input->post('planta');
        header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->consultaDepartamento($planta);
        
        echo json_encode($result);
    }

    function consultaPuesto(){

        $planta=$this->input->post('planta');
		header('Access-Control-Allow-Origin: *');
        $this->load->model('ChecadaBiostar');
        $result = array('data' => array());
        $result =$this->ChecadaBiostar->consultaPuesto($planta);
        
        echo json_encode($result);
    }
    
    public function consulta_checadas()
    {
		header('Access-Control-Allow-Origin: *');
		//$empresa=$this->input->post('empresa');
		//echo json_encode($empresa);
		$this->load->model('ChecadaBiostar');
		$data['data']=$this->ChecadaBiostar->consulta();


		echo json_encode($data);
    }
	


    // Los relojes que descarga RelojSync son de Torreón: solo usuarios cuya planta tiene empresas.esTorreon = 1
    private function usuarioEsTorreon(){
        if (!isset($this->session->userdata['login']['user']->idPlanta)) return false;
        $row = $this->db->query("SELECT esTorreon FROM dbo.empresas WHERE id = ?",
                                array($this->session->userdata['login']['user']->idPlanta))->row_array();
        return $row && (int) $row['esTorreon'] === 1;
    }

}
