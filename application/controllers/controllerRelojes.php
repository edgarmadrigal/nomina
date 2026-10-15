<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogo de relojes checadores. Las descargas las hace
 * tools/RelojSync/publish/RelojSync.exe (ver tools/RelojSync).
 */
class controllerRelojes extends CI_Controller {

    // Minutos que dura la autorización con clave de administrador
    const MINUTOS_AUTORIZACION = 10;

    // Tipos de reloj que entiende RelojSync.exe y su puerto por defecto
    private $tipos = array('BS2' => 51211, 'BS1' => 1470, 'HV' => 9922);

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('RelojDispositivoModel');
    }

    public function index(){
        header('Access-Control-Allow-Origin: *');
        $data = [];
        if (isset($this->session->userdata['login'])) {
            $data['user'] = $this->session->userdata['login']['user'];

            if ($this->session->userdata['login']['exito']) {
                $this->load->view('header', $data);
                if ($this->usuarioEsTorreon()) {
                    $data['esAdmin'] = isset($data['user']->idPerfil) && $data['user']->idPerfil == 1;
                    $this->load->view('relojesView', $data);
                } else {
                    echo '<div class="page-wrapper"><div class="container-fluid"><div class="alert alert-warning mt-4">'
                       . '<i class="fa fa-lock"></i> El catálogo de relojes checadores solo está disponible para usuarios de Torreón.'
                       . '</div></div></div>';
                }
                $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
        }
    }

    public function listar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        echo json_encode($this->RelojDispositivoModel->listar());
    }

    public function plantas(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        echo json_encode($this->RelojDispositivoModel->plantas());
    }

    public function guardar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;

        $id = $this->input->post('id');
        $d  = $this->leerFormulario();
        if (is_string($d)) {
            echo json_encode(['exito' => false, 'mensaje' => $d]);
            return;
        }

        if (empty($id)) {
            if (!preg_match('/^\d{1,18}$/', $d['deviceId'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Captura el ID del reloj (usa "Detectar ID").']);
                return;
            }
            if ($this->RelojDispositivoModel->existeDeviceId($d['deviceId'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Ese reloj ya está dado de alta.']);
                return;
            }
            $this->RelojDispositivoModel->agregar($d);
            echo json_encode(['exito' => true, 'mensaje' => 'Reloj agregado.']);
        } else {
            $this->RelojDispositivoModel->actualizar($id, $d);
            echo json_encode(['exito' => true, 'mensaje' => 'Reloj actualizado.']);
        }
    }

    public function cambiarActivo(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $id = $this->input->post('id');
        $activo = $this->input->post('activo') ? 1 : 0;
        if (empty($id)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta el id.']);
            return;
        }
        $this->RelojDispositivoModel->cambiarActivo($id, $activo);
        echo json_encode(['exito' => true, 'mensaje' => $activo ? 'Reloj activado.' : 'Reloj desactivado.']);
    }

    /**
     * Un usuario que no es administrador captura usuario y clave de un
     * administrador para poder modificar el catálogo por unos minutos.
     */
    public function autorizar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $usuario  = trim((string) $this->input->post('usuario'));
        $password = (string) $this->input->post('password');

        $this->load->model('Usuario');
        $admin = ($usuario !== '' && $password !== '') ? $this->Usuario->login_usuario($usuario, $password) : false;
        if (!$admin || !isset($admin->idPerfil) || $admin->idPerfil != 1) {
            sleep(1);   // frena intentos repetidos
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario o clave incorrectos, o el usuario no es administrador.']);
            return;
        }

        $this->session->set_userdata('relojesAutorizadoHasta', time() + self::MINUTOS_AUTORIZACION * 60);
        $this->session->set_userdata('relojesAutorizadoPor', $admin->usuario);
        echo json_encode(['exito' => true, 'minutos' => self::MINUTOS_AUTORIZACION,
                          'mensaje' => 'Autorizado por ' . $admin->usuario . ' durante ' . self::MINUTOS_AUTORIZACION . ' minutos.']);
    }

    /** Se conecta al reloj y regresa su ID (para dar de alta uno nuevo). */
    public function detectar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $sdk    = $this->input->post('sdk');
        $ip     = trim((string) $this->input->post('ip'));
        $puerto = (int) $this->input->post('puerto');

        if (!isset($this->tipos[$sdk]) || !filter_var($ip, FILTER_VALIDATE_IP) || $puerto <= 0 || $puerto > 65535) {
            echo json_encode(['exito' => false, 'mensaje' => 'Revisa tipo, IP y puerto.']);
            return;
        }

        $r = $this->ejecutar(array('detectar', $sdk, $ip, $puerto));
        if ($r['exito'] && preg_match('/^ID=(\d+)/m', $r['salida'], $m)) {
            echo json_encode(['exito' => true, 'deviceId' => $m[1], 'mensaje' => 'Reloj encontrado: ' . $m[1]]);
        } else {
            $msg = preg_match('/^ERROR=(.*)$/m', $r['salida'], $m) ? $m[1] : $r['salida'];
            echo json_encode(['exito' => false, 'mensaje' => 'No se pudo conectar: ' . $msg]);
        }
    }

    /**
     * Revisa si el reloj contesta: ping y que su puerto acepte conexión.
     * No usa el SDK, solo red, así que es rápido (unos 2 s si no responde).
     */
    public function revisar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $r = $this->RelojDispositivoModel->obtener($this->input->post('id'));
        if (!$r || !filter_var($r['ip'], FILTER_VALIDATE_IP)) {
            echo json_encode(['exito' => false, 'ping' => false, 'puerto' => false, 'mensaje' => 'IP no válida']);
            return;
        }

        $salida = array();
        $codigo = 1;
        exec('ping -n 1 -w 1500 ' . escapeshellarg($r['ip']), $salida, $codigo);
        // en Windows ping regresa 0 aun con "host inaccesible"; se valida el TTL
        $ping = $codigo === 0 && stripos(implode("
", $salida), 'TTL=') !== false;

        $t0 = microtime(true);
        $sock = @fsockopen($r['ip'], (int) $r['puerto'], $errno, $errstr, 2);
        $puerto = $sock !== false;
        $ms = (int) round((microtime(true) - $t0) * 1000);
        if ($sock) fclose($sock);

        if ($puerto)      $mensaje = 'En línea (' . $ms . ' ms)';
        elseif ($ping)    $mensaje = 'Responde ping, pero no el puerto ' . $r['puerto'];
        else              $mensaje = 'No responde el ping';

        echo json_encode(['exito' => $puerto, 'ping' => $ping, 'puerto' => $puerto, 'mensaje' => $mensaje]);
    }

    /** Checadas que trajo la última descarga de un reloj. */
    public function bajadas(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $id = $this->input->post('id');
        echo json_encode(empty($id) ? [] : $this->RelojDispositivoModel->bajadas($id));
    }

    /** Empleados que checaron en un reloj en la fecha dada. */
    public function empleados(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $id    = $this->input->post('id');
        $fecha = $this->input->post('fecha');
        if (empty($id) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $fecha)) {
            echo json_encode([]);
            return;
        }
        echo json_encode($this->RelojDispositivoModel->empleados($id, $fecha));
    }

    /** Descarga y procesa las checadas de un reloj, o de todos los activos si no llega id. */
    public function descargar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $id = $this->input->post('id');
        $args = array('sync');
        if (!empty($id)) {
            $deviceId = $this->RelojDispositivoModel->deviceIdDe($id);
            if ($deviceId === null) {
                echo json_encode(['exito' => false, 'mensaje' => 'No existe el reloj.']);
                return;
            }
            $args[] = $deviceId;
        }
        $r = $this->ejecutar($args);
        echo json_encode(['exito' => $r['exito'], 'mensaje' => $r['salida']]);
    }

    // ------------------------------------------------------------ comunicación con relojes

    /** Minutos que dura la autorización para borrar checadas (clave de administrador + "BORRAR"). */
    const MINUTOS_BORRADO = 5;

    /**
     * Autoriza borrar las checadas de los relojes: SIEMPRE pide usuario y clave de un
     * administrador (aunque quien esté en sesión ya sea administrador) y que escriba BORRAR.
     */
    public function autorizarBorrado(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $usuario  = trim((string) $this->input->post('usuario'));
        $password = (string) $this->input->post('password');
        $confirma = strtoupper(trim((string) $this->input->post('confirmacion')));
        // tipo: borrar = checadas del reloj; quitar = usuarios (y su rostro) del reloj;
        //       copiaborrar = borrar el reloj destino antes de copiarle el principal (muy peligroso)
        $tipoPost = $this->input->post('tipo');
        $tipo     = in_array($tipoPost, array('quitar', 'copiaborrar'), true) ? $tipoPost : 'borrar';
        $palabra  = $tipo === 'copiaborrar' ? 'BORRAR' : strtoupper($tipo);

        if ($confirma !== $palabra) {
            echo json_encode(['exito' => false, 'mensaje' => 'Para confirmar escribe la palabra ' . $palabra . '.']);
            return;
        }
        $this->load->model('Usuario');
        $admin = ($usuario !== '' && $password !== '') ? $this->Usuario->login_usuario($usuario, $password) : false;
        if (!$admin || !isset($admin->idPerfil) || $admin->idPerfil != 1) {
            sleep(1);   // frena intentos repetidos
            echo json_encode(['exito' => false, 'mensaje' => 'Usuario o clave incorrectos, o el usuario no es administrador.']);
            return;
        }
        $claves = array('quitar' => 'relojesQuitar', 'copiaborrar' => 'relojesBorrarCopia', 'borrar' => 'relojesBorrar');
        $clave  = $claves[$tipo];
        $this->session->set_userdata($clave . 'Hasta', time() + self::MINUTOS_BORRADO * 60);
        $this->session->set_userdata($clave . 'Autorizo', $admin->usuario);
        // Un administrador válido acaba de autenticarse: también autoriza las operaciones del
        // catálogo por la ventana normal, para no pedir la clave dos veces en la misma acción.
        $this->session->set_userdata('relojesAutorizadoHasta', time() + self::MINUTOS_AUTORIZACION * 60);
        $this->session->set_userdata('relojesAutorizadoPor', $admin->usuario);
        echo json_encode(['exito' => true, 'minutos' => self::MINUTOS_BORRADO]);
    }

    // ------------------------------------------------------------ usuarios de los relojes

    /**
     * Usuarios del reloj comparados contra los empleados:
     *   falta      activo en una planta del reloj y no está en el reloj  -> dar de alta
     *   sinRostro  está en el reloj pero no tiene rostro                 -> registrar o copiar rostro
     *   baja       está en el reloj y no está activo en ninguna empresa  -> se puede quitar
     *   desconocido está en el reloj y no existe en empleados
     *   ok         activo y con rostro
     */
    private function dirCacheUsuarios(){
        $dir = FCPATH . 'application/cache/relojusuarios/';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        return $dir;
    }

    /**
     * Arma las filas (número, nombre, planta, rostro y situación) a partir de la lista de
     * usuarios del reloj. La situación se recalcula cada vez contra dbo.empleados, así que
     * aunque la lista venga del caché, altas/bajas recientes se reflejan al instante.
     */
    private function construirFilasUsuarios($reloj, $datos){
        $estado = array();
        foreach ($this->RelojDispositivoModel->estadoEmpleados($this->plantasDe($reloj)) as $e) $estado[$e['num']] = $e;

        $filas = array();
        $enReloj = array();
        foreach ((array) $datos as $u) {
            $num = ltrim((string) $u['id'], '0') === '' ? (string) $u['id'] : ltrim((string) $u['id'], '0');
            $enReloj[$num] = true;
            $e = isset($estado[$num]) ? $estado[$num] : null;
            if (!$e)                      $situacion = 'desconocido';
            elseif (!(int) $e['activo'])  $situacion = 'baja';
            elseif ((int) $u['rostros'] === 0) $situacion = 'sinRostro';
            else                          $situacion = 'ok';
            $filas[] = array('num' => (string) $u['id'], 'nombre' => $e ? $e['nombre'] : '', 'nombreReloj' => $u['nombre'],
                             'planta' => $e ? $e['empresa_id'] . ' - ' . $e['planta'] : '', 'rostros' => (int) $u['rostros'],
                             'estado' => $situacion);
        }
        foreach ($estado as $num => $e) {
            if ((int) $e['activoAqui'] && !isset($enReloj[$num])) {
                // $num viene de una clave de array: PHP convierte las claves numéricas a entero,
                // así que se fuerza a string (el JS ordena con localeCompare, que es de strings).
                $filas[] = array('num' => (string) $num, 'nombre' => $e['nombre'], 'nombreReloj' => '', 'planta' => $e['empresa_id'] . ' - ' . $e['planta'],
                                 'rostros' => 0, 'estado' => 'falta');
            }
        }
        return $filas;
    }

    /**
     * Regresa los usuarios del reloj desde el CACHÉ (un archivo por reloj); NO toca el reloj,
     * así que responde al instante. La lista se refresca con usuariosRefrescar (segundo plano).
     */
    public function usuariosReloj(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $this->liberarSesion();   // solo lectura: no bloquear a otras peticiones del mismo usuario
        $reloj = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        if (!$reloj) { echo json_encode(['exito' => false, 'mensaje' => 'No existe el reloj.']); return; }

        $dev = $reloj['deviceId'];
        $dir = $this->dirCacheUsuarios();
        $cache = $dir . 'cache_' . $dev . '.json';
        $leyendo = file_exists($dir . 'read_' . $dev . '.part') || file_exists($dir . 'read_' . $dev . '.done');

        if (!file_exists($cache)) {
            echo json_encode(['exito' => true, 'sdk' => $reloj['sdk'], 'filas' => array(), 'fecha' => null,
                              'sinCache' => true, 'leyendo' => $leyendo]);
            return;
        }
        $c = json_decode(file_get_contents($cache), true);
        echo json_encode(['exito' => true, 'sdk' => $reloj['sdk'],
                          'filas' => $this->construirFilasUsuarios($reloj, isset($c['usuarios']) ? $c['usuarios'] : array()),
                          'fecha' => isset($c['fecha']) ? $c['fecha'] : null, 'leyendo' => $leyendo]);
    }

    /** Lanza en SEGUNDO PLANO la lectura de usuarios del reloj (no bloquea la pantalla). */
    public function usuariosRefrescar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $reloj = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        if (!$reloj) { echo json_encode(['exito' => false, 'mensaje' => 'No existe el reloj.']); return; }

        $dev = $reloj['deviceId'];
        $dir = $this->dirCacheUsuarios();
        $base = $dir . 'read_' . $dev;
        if (file_exists($base . '.part')) { echo json_encode(['exito' => true, 'yaCorriendo' => true]); return; }
        @unlink($base . '.done');

        $exe = FCPATH . 'tools/RelojSync/publish/RelojSync.exe';
        if (!file_exists($exe)) { echo json_encode(['exito' => false, 'mensaje' => 'No está instalado RelojSync.exe.']); return; }

        $cmd = $base . '.cmd';
        // Rutas Windows con backslashes: el 'ren' fallaba con diagonales mezcladas, así que el .part
        // nunca pasaba a .done y el contador "Actualizando... N s" subía sin fin aunque la lectura ya
        // había terminado. Usamos 'move' (más tolerante) con la ruta destino completa, y entramos a la
        // carpeta del .exe para que BS_SDK_V2.dll encuentre sus dependencias.
        $exeW  = str_replace('/', '\\', $exe);
        $dirW  = str_replace('/', '\\', dirname($exe));
        $partW = str_replace('/', '\\', $base . '.part');
        $doneW = str_replace('/', '\\', $base . '.done');
        file_put_contents($cmd,
            "@echo off\r\n"
          . 'cd /d "' . $dirW . '"' . "\r\n"
          . '"' . $exeW . '" usuarios ' . $dev . ' > "' . $partW . '" 2>&1' . "\r\n"
          . 'move /Y "' . $partW . '" "' . $doneW . '" >nul' . "\r\n");
        file_put_contents($base . '.start', (string) time());
        // start "" /B cmd /c: se desprende y regresa de inmediato; el proceso sigue solo.
        $h = popen('start "" /B cmd /c "' . $cmd . '"', 'r');
        if ($h !== false) pclose($h);
        echo json_encode(['exito' => true]);
    }

    /** ¿Terminó la lectura en segundo plano? Si terminó, guarda el caché y regresa las filas. */
    public function usuariosEstado(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $this->liberarSesion();   // se consulta cada 3 s: no bloquear a otras peticiones del mismo usuario
        $reloj = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        if (!$reloj) { echo json_encode(['exito' => false, 'mensaje' => 'No existe el reloj.']); return; }

        $dev = $reloj['deviceId'];
        $dir = $this->dirCacheUsuarios();
        $base = $dir . 'read_' . $dev;

        if (file_exists($base . '.done')) {
            $salida = file_get_contents($base . '.done');
            @unlink($base . '.done'); @unlink($base . '.cmd'); @unlink($base . '.start');
            if (preg_match('/^JSON=(.*)$/m', $salida, $m) && ($datos = json_decode($m[1], true)) !== null) {
                $fecha = date('Y-m-d H:i:s');
                file_put_contents($dir . 'cache_' . $dev . '.json',
                    json_encode(array('fecha' => $fecha, 'sdk' => $reloj['sdk'], 'usuarios' => $datos), JSON_UNESCAPED_UNICODE));
                echo json_encode(['exito' => true, 'listo' => true, 'sdk' => $reloj['sdk'],
                                  'filas' => $this->construirFilasUsuarios($reloj, $datos), 'fecha' => $fecha]);
                return;
            }
            $msg = preg_match('/^ERROR=(.*)$/m', $salida, $m) ? $m[1] : $this->ultimaLinea($salida);
            echo json_encode(['exito' => false, 'listo' => true, 'mensaje' => $msg]);
            return;
        }
        if (file_exists($base . '.part')) {
            $ini = file_exists($base . '.start') ? (int) file_get_contents($base . '.start') : filemtime($base . '.part');
            echo json_encode(['exito' => true, 'listo' => false, 'segundos' => max(0, time() - $ini)]);
            return;
        }
        echo json_encode(['exito' => true, 'listo' => false, 'idle' => true]);
    }

    /** Da de alta en el reloj (número y nombre, sin rostro) a empleados activos. */
    public function altaUsuarios(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $reloj = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        $nums  = $this->numerosPost();
        if (!$reloj || !$nums) { echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos.']); return; }

        $estado = array();
        foreach ($this->RelojDispositivoModel->estadoEmpleados($this->plantasDe($reloj)) as $e) $estado[$e['num']] = $e;
        $lista = array();
        $noActivos = array();
        foreach ($nums as $n) {
            if (isset($estado[$n]) && (int) $estado[$n]['activo']) $lista[] = array('id' => $n, 'nombre' => $this->nombreReloj($estado[$n]['nombre']));
            else $noActivos[] = $n;
        }
        if (!$lista) { echo json_encode(['exito' => false, 'mensaje' => 'Ninguno de los seleccionados es un empleado activo.']); return; }

        $r = $this->ejecutarJson(array('alta', $reloj['deviceId']), $lista);
        $this->registrarUsuarios($reloj['id'], 'alta', $r, null);
        if ($r['exito']) $r['noActivos'] = $noActivos;
        echo json_encode($r);
    }

    /** Quita usuarios (y su rostro) del reloj. Requiere clave de administrador + QUITAR. Nunca quita activos. */
    public function bajaUsuarios(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $hasta = $this->session->userdata('relojesQuitarHasta');
        if (!$hasta || $hasta < time()) {
            echo json_encode(['exito' => false, 'requiereQuitar' => true, 'mensaje' => 'Se requiere la clave del administrador para quitar usuarios.']);
            return;
        }
        // Renueva la ventana en cada bloque: una operación larga (miles de usuarios) no caduca a
        // la mitad, pero si dejas de quitar por más de MINUTOS_BORRADO vuelve a pedir la clave.
        $this->session->set_userdata('relojesQuitarHasta', time() + self::MINUTOS_BORRADO * 60);
        $reloj = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        $nums  = $this->numerosPost();
        if (!$reloj || !$nums) { echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos.']); return; }

        $estado = array();
        foreach ($this->RelojDispositivoModel->estadoEmpleados($this->plantasDe($reloj)) as $e) $estado[$e['num']] = $e;
        $quitar = array();
        $activos = array();
        foreach ($nums as $n) {
            if (isset($estado[$n]) && (int) $estado[$n]['activo']) $activos[] = $n;   // protección: un activo no se quita
            else $quitar[] = $n;
        }
        if (!$quitar) { echo json_encode(['exito' => false, 'mensaje' => 'Los seleccionados son empleados activos; no se quitan del reloj.']); return; }

        $r = $this->ejecutarJson(array('baja', $reloj['deviceId']), $quitar);
        $this->registrarUsuarios($reloj['id'], 'baja', $r, $this->session->userdata('relojesQuitarAutorizo'));
        if ($r['exito']) $r['activosProtegidos'] = $activos;
        echo json_encode($r);
    }

    /** Copia usuarios con su rostro desde otro reloj BioStar 2 hacia este. */
    public function copiarRostros(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $destino = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('id'));
        $origen  = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('origen'));
        $nums    = $this->numerosPost();
        if (!$destino || !$origen || !$nums) { echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos.']); return; }

        $extra = array();
        if ($this->input->post('incluir'))    $extra[] = 'consinrostro';
        if ($this->input->post('reemplazar')) $extra[] = 'reemplazar';
        $r = $this->ejecutarJson(array('copiarrostros', $origen['deviceId'], $destino['deviceId']), $nums, $extra);
        $nota = 'desde ' . $origen['descripcion']
              . (in_array('consinrostro', $extra) ? ' (con sin rostro)' : '')
              . (in_array('reemplazar', $extra) ? ' (reemplazando)' : '');
        $this->registrarUsuarios($destino['id'], 'copiar', $r, null, $nota);
        echo json_encode($r);
    }

    /**
     * Copia TODOS los usuarios del reloj principal (origen) hacia VARIOS relojes, en segundo plano.
     * Escribe el avance en copia_progreso.json (la misma barra de la copia individual).
     */
    public function copiarVarios(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->esAdmin()) return;
        $origen  = $this->RelojDispositivoModel->obtenerCompleto((int) $this->input->post('origen'));
        $destIds = $this->input->post('destinos');
        if (!$origen || $origen['sdk'] !== 'BS2' || !is_array($destIds) || !$destIds) {
            echo json_encode(['exito' => false, 'mensaje' => 'Elige el reloj principal y al menos un reloj destino.']); return;
        }
        $destDevs = array();
        foreach ($destIds as $did) {
            $d = $this->RelojDispositivoModel->obtenerCompleto((int) $did);
            if ($d && $d['sdk'] === 'BS2' && $d['deviceId'] != $origen['deviceId']) $destDevs[] = (string) $d['deviceId'];
        }
        if (!$destDevs) { echo json_encode(['exito' => false, 'mensaje' => 'No hay relojes destino válidos (deben ser BioStar 2 y distintos del principal).']); return; }

        $exe = FCPATH . 'tools/RelojSync/publish/RelojSync.exe';
        if (!file_exists($exe)) { echo json_encode(['exito' => false, 'mensaje' => 'No está instalado RelojSync.exe.']); return; }

        // "Borrar todo primero" deja los relojes destino idénticos al principal borrándolos antes de
        // copiar: es peligroso (un destino mal elegido se vacía), así que SIEMPRE exige que un
        // administrador haya capturado su clave (autorizarBorrado tipo 'copiaborrar') hace poco.
        $borrar = $this->input->post('borrar') ? 1 : 0;
        if ($borrar) {
            $hasta = $this->session->userdata('relojesBorrarCopiaHasta');
            if (!$hasta || $hasta < time()) {
                echo json_encode(['exito' => false, 'requiereBorrarCopia' => true,
                    'mensaje' => 'Se requiere la clave del administrador para borrar y reemplazar.']);
                return;
            }
            // consumir la autorización: un solo uso para que no quede abierta tras lanzar la copia
            $this->session->unset_userdata('relojesBorrarCopiaHasta');
        }

        $dir       = $this->dirCacheUsuarios();
        $destsFile = $dir . 'copiavarios_destinos.json';
        file_put_contents($destsFile, json_encode($destDevs));
        $prog = $dir . 'copia_progreso.json';

        $flags = '';
        if ($this->input->post('reemplazar')) $flags .= ' reemplazar';
        if ($this->input->post('incluir'))    $flags .= ' consinrostro';
        if ($borrar)                          $flags .= ' borrarprimero';

        $exeW   = str_replace('/', '\\', $exe);
        $dirW   = str_replace('/', '\\', dirname($exe));
        $destsW = str_replace('/', '\\', $destsFile);
        $progW  = str_replace('/', '\\', $prog);
        $cmd = $dir . 'copiavarios.cmd';
        file_put_contents($cmd,
            "@echo off\r\n"
          . 'cd /d "' . $dirW . '"' . "\r\n"
          . '"' . $exeW . '" copiarvarios ' . $origen['deviceId'] . ' "' . $destsW . '"' . $flags . ' "' . $progW . '" >nul 2>&1' . "\r\n");
        $h = popen('start "" /B cmd /c "' . $cmd . '"', 'r');
        if ($h !== false) pclose($h);

        echo json_encode(['exito' => true, 'destinos' => count($destDevs)]);
    }

    /** Números de empleado del POST (solo dígitos, sin repetidos). */
    private function numerosPost(){
        $nums = $this->input->post('nums');
        if (!is_array($nums)) return array();
        $limpios = array();
        foreach ($nums as $n) {
            $n = trim((string) $n);
            // El valor guarda el string; si usáramos la CLAVE, PHP la convertiría a entero y el
            // JSON saldría con números -> RelojSync (GetString) truena "requires String, got Number".
            if (preg_match('/^\d{1,18}$/', $n)) $limpios[$n] = $n;
        }
        return array_values($limpios);
    }

    private function plantasDe($reloj){
        $g = trim((string) $reloj['grupoPlantas']);
        return $g !== '' ? array_map('intval', explode(',', $g)) : array((int) $reloj['idPlanta']);
    }

    /** "LARA/BECERRA,ESTEFANY NALLELY" -> "ESTEFANY NALLELY LARA BECERRA" (máximo 48 caracteres, como en el reloj). */
    private function nombreReloj($completo){
        $p = explode(',', (string) $completo, 2);
        $nombre = count($p) === 2 ? trim($p[1]) . ' ' . str_replace('/', ' ', $p[0]) : str_replace('/', ' ', $p[0]);
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        return function_exists('mb_substr') ? mb_substr($nombre, 0, 48, 'UTF-8') : substr($nombre, 0, 48);
    }

    /**
     * Ejecuta RelojSync con una lista en un archivo temporal (si viene) y lee su línea JSON=.
     * Regresa ['exito' => bool, 'datos' => ..., 'mensaje' => ...].
     */
    private function ejecutarJson(array $args, $lista = null, array $extraArgs = array()){
        $archivo = null;
        if ($lista !== null) {
            $archivo = tempnam(sys_get_temp_dir(), 'rsu');
            file_put_contents($archivo, json_encode($lista, JSON_UNESCAPED_UNICODE));
            $args[] = $archivo;
        }
        foreach ($extraArgs as $a) $args[] = $a;   // p.ej. 'consinrostro' (debe ir DESPUÉS del archivo de ids)
        $r = $this->ejecutar($args);
        if ($archivo) @unlink($archivo);
        if (preg_match('/^JSON=(.*)$/m', $r['salida'], $m)) {
            $datos = json_decode($m[1], true);
            if ($datos !== null) return array('exito' => true, 'datos' => $datos);
        }
        $msg = preg_match('/^ERROR=(.*)$/m', $r['salida'], $m) ? $m[1] : $this->ultimaLinea($r['salida']);
        return array('exito' => false, 'mensaje' => $msg);
    }

    private function registrarUsuarios($idReloj, $accion, $r, $autorizo, $extra = ''){
        if ($r['exito']) {
            $d = $r['datos'];
            $msg = count($d['hechos']) . ' hechos, ' . count($d['yaEstaban']) . ' sin cambio, ' . count($d['errores']) . ' con error'
                 . ($d['hechos'] ? ' (' . implode(', ', array_slice($d['hechos'], 0, 30)) . (count($d['hechos']) > 30 ? '...' : '') . ')' : '');
        } else {
            $msg = $r['mensaje'];
        }
        if ($extra !== '') $msg = $extra . ': ' . $msg;
        $u = $this->session->userdata['login']['user'];
        $this->db->query("INSERT INTO dbo.RelojComunicacion (idReloj, accion, usuario, autorizo, ip, exito, resultado) VALUES (?,?,?,?,?,?,?)",
                         array($idReloj, $accion, isset($u->usuario) ? $u->usuario : null, $autorizo,
                               $this->input->ip_address(), $r['exito'] ? 1 : 0, $msg));
    }

    /**
     * Ejecuta una acción en un reloj:
     *   descargar  baja y procesa las checadas (cualquier usuario de relojes)
     *   hora       lee la hora del reloj (cualquier usuario de relojes)
     *   ponerhora  pone el reloj a la hora del servidor (administrador o autorizado)
     *   borrar     descarga TODO y luego borra las checadas del reloj (requiere autorizarBorrado)
     * Cada acción queda en dbo.RelojComunicacion.
     */
    public function comunicar(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $id     = (int) $this->input->post('id');
        $accion = (string) $this->input->post('accion');
        if (!in_array($accion, array('descargar', 'hora', 'ponerhora', 'borrar'), true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida.']);
            return;
        }
        $deviceId = $this->RelojDispositivoModel->deviceIdDe($id);
        if ($deviceId === null) {
            echo json_encode(['exito' => false, 'mensaje' => 'No existe el reloj.']);
            return;
        }

        $autorizo = null;
        if ($accion === 'ponerhora' && !$this->esAdmin()) return;   // esAdmin ya respondió requiereClave
        if ($accion === 'borrar') {
            $hasta = $this->session->userdata('relojesBorrarHasta');
            if (!$hasta || $hasta < time()) {
                echo json_encode(['exito' => false, 'requiereBorrado' => true,
                                  'mensaje' => 'La autorización para borrar venció. Vuelve a capturar la clave del administrador.']);
                return;
            }
            $autorizo = $this->session->userdata('relojesBorrarAutorizo');
        }

        $r = $this->ejecutar($accion === 'descargar' ? array('sync', $deviceId) : array($accion, $deviceId));
        $d = array();
        foreach (explode("\n", $r['salida']) as $linea) {
            if (preg_match('/^([A-Z]+)=(.*)$/', trim($linea), $m)) $d[$m[1]] = $m[2];
        }

        if ($accion === 'descargar') {
            $mensaje = $this->resumenDescarga($r['salida']);   // trae "ERROR ..." si falló
        } elseif (!$r['exito']) {
            $mensaje = isset($d['ERROR']) ? $d['ERROR'] : $this->ultimaLinea($r['salida']);
        } elseif ($accion === 'hora') {
            $mensaje = 'Hora del reloj: ' . $d['HORA'] . ' (' . $this->textoDiferencia((int) $d['DIF']) . ').';
        } elseif ($accion === 'ponerhora') {
            $mensaje = 'Puesto a tiempo: ' . ($d['ANTES'] !== '' ? 'tenía ' . $d['ANTES'] . ', ' : '') . 'ahora ' . $d['HORA']
                     . ' (' . $this->textoDiferencia((int) $d['DIF']) . ').';
        } else {
            $mensaje = 'Se descargaron primero ' . (int) $d['GUARDADOS'] . ' eventos pendientes y se borraron las checadas del reloj.';
        }

        $u = $this->session->userdata['login']['user'];
        $this->db->query("INSERT INTO dbo.RelojComunicacion (idReloj, accion, usuario, autorizo, ip, exito, resultado) VALUES (?,?,?,?,?,?,?)",
                         array($id, $accion, isset($u->usuario) ? $u->usuario : null, $autorizo,
                               $this->input->ip_address(), $r['exito'] ? 1 : 0, $mensaje));

        echo json_encode(['exito' => $r['exito'], 'mensaje' => $mensaje, 'detalle' => $r['salida']]);
    }

    /** Últimas acciones enviadas a los relojes. */
    public function historialComunicacion(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $sql = "SELECT TOP 200 CONVERT(VARCHAR(19), c.fecha, 120) AS fecha, ISNULL(d.descripcion, '') AS reloj,
                       c.accion, c.usuario, c.autorizo, c.exito, c.resultado
                FROM dbo.RelojComunicacion c
                LEFT JOIN dbo.RelojDispositivo d ON d.id = c.idReloj
                ORDER BY c.id DESC";
        echo json_encode($this->db->query($sql)->result_array());
    }

    private function textoDiferencia($s){
        if (abs($s) <= 2) return 'en tiempo';
        $a = abs($s);
        $t = $a >= 60 ? floor($a / 60) . ' min ' . ($a % 60) . ' s' : $a . ' s';
        return $t . ($s > 0 ? ' adelantado' : ' atrasado');
    }

    /** "205 Fundacion: 19 eventos nuevos, 11 checadas. Checadas insertadas: 11, ..." sin la fecha de cada línea. */
    private function resumenDescarga($salida){
        $partes = array();
        foreach (explode("\n", $salida) as $l) {
            $l = trim(preg_replace('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\s*/', '', trim($l)));
            if ($l !== '') $partes[] = $l;
        }
        return implode(' ', $partes);
    }

    private function ultimaLinea($salida){
        $lineas = array_values(array_filter(array_map('trim', explode("\n", $salida))));
        return $lineas ? end($lineas) : 'Error desconocido.';
    }

    // ------------------------------------------------------------------

    /**
     * Modificar el catálogo: el usuario es administrador (idPerfil = 1), o un
     * administrador capturó su clave en esta sesión hace menos de MINUTOS_AUTORIZACION.
     */
    private function esAdmin(){
        if (!$this->sesionValida()) return false;
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $hasta = $this->session->userdata('relojesAutorizadoHasta');
        if ($hasta && $hasta > time()) return true;
        echo json_encode(['exito' => false, 'requiereClave' => true, 'mensaje' => 'Se requiere la clave del administrador.']);
        return false;
    }

    /** Sesión iniciada y usuario de una planta de Torreón (los relojes son de Torreón). */
    private function sesionValida(){
        if (!isset($this->session->userdata['login']['user'])) {
            echo json_encode(['exito' => false, 'mensaje' => 'Sesión inválida, vuelva a iniciar sesión.']);
            return false;
        }
        if (!$this->usuarioEsTorreon()) {
            echo json_encode(['exito' => false, 'mensaje' => 'Los relojes checadores solo están disponibles para usuarios de Torreón.']);
            return false;
        }
        return true;
    }

    /**
     * Cierra la escritura de la sesión (suelta el flock del archivo de sesión) para que las
     * peticiones de solo lectura o muy frecuentes no bloqueen a las demás del mismo usuario.
     * Después de esto la sesión sigue legible pero ya no se debe escribir en ella.
     */
    private function liberarSesion(){
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();
    }

    /**
     * Progreso de la copia de rostros en segundo plano (lo escribe tools/RelojSync/publish/run_copia.sh
     * en application/cache/relojusuarios/copia_progreso.json). Solo lectura; se consulta cada pocos segundos.
     */
    public function copiaProgreso(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->sesionValida()) return;
        $this->liberarSesion();
        $f = $this->dirCacheUsuarios() . 'copia_progreso.json';
        if (!file_exists($f)) { echo json_encode(['exito' => true, 'hay' => false]); return; }
        $p = json_decode(file_get_contents($f), true);
        if (!is_array($p)) { echo json_encode(['exito' => true, 'hay' => false]); return; }
        // Si ya terminó hace más de 10 min, dejamos de mostrarlo.
        if (empty($p['activo']) && !empty($p['fin']) && (time() - strtotime($p['fin'])) > 600) {
            echo json_encode(['exito' => true, 'hay' => false]); return;
        }
        $p['exito'] = true;
        $p['hay']   = true;
        echo json_encode($p);
    }

    private function usuarioEsTorreon(){
        $u = $this->session->userdata['login']['user'];
        if (!isset($u->idPlanta)) return false;
        $row = $this->db->query("SELECT esTorreon FROM dbo.empresas WHERE id = ?", array($u->idPlanta))->row_array();
        return $row && (int) $row['esTorreon'] === 1;
    }

    /** Valida el formulario. Regresa el arreglo de datos o un mensaje de error. */
    private function leerFormulario(){
        $d = array(
            'deviceId'     => trim((string) $this->input->post('deviceId')),
            'descripcion'  => trim((string) $this->input->post('descripcion')),
            'ip'           => trim((string) $this->input->post('ip')),
            'puerto'       => (int) $this->input->post('puerto'),
            'sdk'          => $this->input->post('sdk'),
            'idPlanta'     => (int) $this->input->post('idPlanta'),
            'grupoPlantas' => trim((string) $this->input->post('grupoPlantas')),
            'zonaHoraria'  => $this->input->post('zonaHoraria'),
            'fechaInicio'  => $this->input->post('fechaInicio'),
            'activo'       => $this->input->post('activo') ? 1 : 0,
        );

        if ($d['descripcion'] === '')                    return 'Captura la descripción.';
        if (!filter_var($d['ip'], FILTER_VALIDATE_IP))   return 'La IP no es válida.';
        if ($d['puerto'] <= 0 || $d['puerto'] > 65535)   return 'El puerto no es válido.';
        if (!isset($this->tipos[$d['sdk']]))             return 'Selecciona el tipo de reloj.';
        if ($d['idPlanta'] <= 0)                         return 'Selecciona la planta de respaldo.';
        if ($d['grupoPlantas'] !== '' && !preg_match('/^\d+(,\d+)*$/', $d['grupoPlantas']))
                                                         return 'El grupo de plantas debe ser una lista de números separados por coma.';
        if ($d['grupoPlantas'] === '')                   $d['grupoPlantas'] = null;
        if (empty($d['zonaHoraria']))                    $d['zonaHoraria'] = 'Central Standard Time (Mexico)';
        if (empty($d['fechaInicio']))                    $d['fechaInicio'] = null;
        return $d;
    }

    private function ejecutar(array $args){
        ini_set('max_execution_time', 0);
        $exe = FCPATH . 'tools/RelojSync/publish/RelojSync.exe';
        if (!file_exists($exe)) {
            return array('exito' => false, 'salida' => 'No está instalado RelojSync.exe en tools/RelojSync/publish.');
        }
        $cmd = escapeshellarg($exe);
        foreach ($args as $a) $cmd .= ' ' . escapeshellarg((string) $a);
        $salida = array();
        $codigo = 0;
        exec($cmd . ' 2>&1', $salida, $codigo);
        return array('exito' => $codigo === 0, 'salida' => implode("\n", $salida));
    }
}
