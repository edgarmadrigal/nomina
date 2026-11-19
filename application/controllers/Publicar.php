<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Botón "Publicar" de la barra MODO DESARROLLO (views/header.php).
 *
 * Copia a producción (la VM SISAI, carpeta compartida \\SISAI\nomina) los archivos de este
 * equipo que son más nuevos o no existen allá. Usa robocopy SIN borrar nada en el destino
 * (sin /MIR ni /PURGE) y sin pisar archivos que allá sean más recientes (/XO).
 *
 * - Solo existe en el equipo de desarrollo (config 'es_desarrollo', ver config.php) y solo
 *   para el administrador (idPerfil = 1).
 * - Destino, cuenta y exclusiones en application/config/publicar.php (fuera de git y nunca se
 *   publica: robocopy excluye publicar.php, así que este controlador tampoco llega a producción).
 * - revisar  -> lista lo que se copiaría (robocopy /L, no copia nada)
 * - ejecutar -> copia de verdad y deja bitácora en application/logs/publicar.log
 */
class Publicar extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->config->load('publicar', TRUE, TRUE);
    }

    public function revisar() {
        $this->correr(true);
    }

    public function ejecutar() {
        $this->correr(false);
    }

    private function correr($soloRevisar) {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->config->item('es_desarrollo')) {
            $this->responder(false, 'Publicar solo está disponible en el equipo de desarrollo.');
        }
        $login = $this->session->userdata('login');
        if (empty($login['exito']) || empty($login['user']) || !isset($login['user']->idPerfil) || $login['user']->idPerfil != 1) {
            $this->responder(false, 'Solo el administrador puede publicar.');
        }
        if ($this->input->method() !== 'post') {
            $this->responder(false, 'Petición no válida.');
        }

        $cfg     = $this->config->item('publicar');
        $destino = rtrim(isset($cfg['publicar_destino']) ? $cfg['publicar_destino'] : '', '\\/');
        $usuario = isset($cfg['publicar_usuario']) ? $cfg['publicar_usuario'] : '';
        $clave   = isset($cfg['publicar_clave']) ? $cfg['publicar_clave'] : '';
        if ($destino === '' || $usuario === '' || $clave === '') {
            $this->responder(false, 'Falta configurar destino, usuario o clave en application/config/publicar.php.');
        }

        // Una publicación a la vez
        $lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nomina_publicar.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->responder(false, 'Ya hay una publicación en curso, espera a que termine.');
        }
        set_time_limit(0);
        ignore_user_abort(true);

        // Conectar a la carpeta compartida de la VM
        if (!$this->conectar($destino, $usuario, $clave, $error)) {
            $this->responder(false, 'No se pudo conectar a ' . $destino . ': ' . $error);
        }

        $origen = rtrim(str_replace('/', '\\', FCPATH), '\\');

        // Publicación parcial: solo los archivos marcados en el panel (rutas relativas al proyecto)
        $seleccion = $soloRevisar ? null : $this->input->post('archivos');
        if (!$soloRevisar && $seleccion !== null) {
            $seleccion = array_values(array_filter((array) $seleccion, 'is_string'));
            if (!$seleccion) {
                flock($lock, LOCK_UN);
                $this->responder(false, 'No marcaste ningún archivo para publicar.');
            }
        }

        if ($seleccion === null) {
            list($archivos, $errores, $codigo) = $this->robocopy($this->comandoTodo($origen, $destino, $cfg, $soloRevisar), $origen);
        } else {
            // Solo se permite publicar lo que la revisión marca como pendiente (nada fuera del
            // proyecto ni excluido); se copia con robocopy por carpeta, nombrando cada archivo.
            list($pendientes, $errores, $codigo) = $this->robocopy($this->comandoTodo($origen, $destino, $cfg, true), $origen);
            $permitidos = array();
            foreach ($pendientes as $a) $permitidos[strtolower($a['ruta'])] = $a['ruta'];

            $porCarpeta = array();
            foreach ($seleccion as $r) {
                $r = trim(str_replace('/', '\\', $r), '\\');
                if (!isset($permitidos[strtolower($r)])) continue;
                $r = $permitidos[strtolower($r)];
                $dir = dirname($r) === '.' ? '' : dirname($r);
                $porCarpeta[$dir][] = basename($r);
            }
            if (!$porCarpeta) {
                flock($lock, LOCK_UN);
                $this->responder(false, 'Los archivos marcados ya no están pendientes; vuelve a abrir Publicar.');
            }

            $archivos = array();
            $codigo = 0;
            foreach ($porCarpeta as $dir => $nombres) {
                $sub = $dir === '' ? '' : '\\' . $dir;
                $cmd = 'robocopy ' . $this->q($origen . $sub) . ' ' . $this->q($destino . $sub) . ' '
                     . implode(' ', array_map(array($this, 'q'), $nombres))
                     . ' /R:1 /W:2 /NP /NDL /NJH /FP /BYTES';
                list($a, $e, $c) = $this->robocopy($cmd, $origen);
                $archivos = array_merge($archivos, $a);
                $errores  = array_merge($errores, $e);
                $codigo   = max($codigo, $c);
            }
        }
        flock($lock, LOCK_UN);

        // Fecha de modificación de cada archivo (el panel ordena y pre-marca lo más reciente)
        foreach ($archivos as $i => $a) {
            $t = @filemtime($origen . '\\' . $a['ruta']);
            $archivos[$i]['fecha'] = $t ? date('Y-m-d H:i', $t) : '';
        }
        if ($soloRevisar) {
            usort($archivos, function ($x, $y) { return strcmp($y['fecha'], $x['fecha']); });
        }

        // Códigos de robocopy: 0 = nada que copiar, 1-7 = bien, 8 o más = falló algo
        $ok = $codigo < 8;
        $total = count($archivos);
        if ($soloRevisar) {
            $msg = $total ? "$total archivo(s) por publicar." : 'Producción ya está al día, no hay nada que publicar.';
        } else {
            $msg = $ok ? ($total ? "Publicados $total archivo(s) a producción." : 'Producción ya estaba al día.')
                       : 'La publicación terminó con errores (código ' . $codigo . ').';
            $this->bitacora($login['user'], $total, $codigo, $archivos, $errores);
        }

        echo json_encode(array(
            'exito'    => $ok,
            'mensaje'  => $msg,
            'total'    => $total,
            'archivos' => array_slice($archivos, 0, 2000),
            'errores'  => array_slice($errores, 0, 50),
            'codigo'   => $codigo,
            'destino'  => $destino,
        ));
        exit;
    }

    /** robocopy de todo el proyecto (sin borrar en destino, sin pisar lo más nuevo allá). */
    private function comandoTodo($origen, $destino, $cfg, $soloRevisar) {
        $cmd = 'robocopy ' . $this->q($origen) . ' ' . $this->q($destino)
             . ' /E /XO /R:1 /W:2 /NP /NDL /NJH /FP /BYTES';
        if ($soloRevisar) $cmd .= ' /L';

        $xd = array();
        foreach ((array) $cfg['publicar_excluir_carpetas'] as $c) {
            // Con "\" es una ruta dentro del proyecto; sin "\" es un nombre de carpeta en cualquier nivel
            $xd[] = $this->q(strpos($c, '\\') !== false ? $origen . '\\' . $c : $c);
        }
        if ($xd) $cmd .= ' /XD ' . implode(' ', $xd);
        $xf = array();
        foreach ((array) $cfg['publicar_excluir_archivos'] as $f) $xf[] = $this->q($f);
        if ($xf) $cmd .= ' /XF ' . implode(' ', $xf);
        return $cmd;
    }

    /** Corre robocopy y regresa [archivos, errores, código]. Las rutas quedan relativas al proyecto. */
    private function robocopy($cmd, $origen) {
        $salida = array();
        exec($cmd . ' 2>&1', $salida, $codigo);

        $archivos = array();
        $errores  = array();
        $prefijo  = strtolower($origen . '\\');
        foreach ($salida as $l) {
            // robocopy escribe en la página de códigos de la consola
            $l = function_exists('iconv') ? (string) @iconv('CP850', 'UTF-8//IGNORE', $l) : utf8_encode($l);
            if (preg_match('/^\s*(.+?)\s+(\d+)\s+([A-Za-z]:\\\\.+)$/', $l, $m)) {
                $ruta = $m[3];
                if (strpos(strtolower($ruta), $prefijo) === 0) $ruta = substr($ruta, strlen($prefijo));
                $archivos[] = array('tipo' => trim($m[1]), 'bytes' => (int) $m[2], 'ruta' => $ruta);
            } elseif (preg_match('/\bERROR\s+\d+\s+\(0x[0-9A-F]+\)/i', $l)) {
                // Errores reales de robocopy: "... ERROR 5 (0x00000005) Copiando archivo ..."
                // (el encabezado del resumen "Total Copiado Omitido ... ERROR Extras" no cuenta)
                $errores[] = trim($l);
            }
        }
        return array($archivos, $errores, $codigo);
    }

    private function conectar($destino, $usuario, $clave, &$error) {
        if (is_dir($destino)) return true;   // ya hay sesión con la VM
        $out = array();
        exec('net use ' . $this->q($destino) . ' ' . $this->q($clave) . ' /user:' . $this->q($usuario) . ' /persistent:no 2>&1', $out, $c);
        if ($c !== 0) {
            // 1219: ya hay otra sesión con la VM con otra cuenta -> se cierra y se reintenta
            exec('net use ' . $this->q($destino) . ' /delete /y 2>&1');
            $out = array();
            exec('net use ' . $this->q($destino) . ' ' . $this->q($clave) . ' /user:' . $this->q($usuario) . ' /persistent:no 2>&1', $out, $c);
        }
        if ($c !== 0) {
            $error = trim(implode(' ', array_map(function ($l) {
                return function_exists('iconv') ? (string) @iconv('CP850', 'UTF-8//IGNORE', $l) : $l;
            }, $out)));
            return false;
        }
        return true;
    }

    private function bitacora($user, $total, $codigo, $archivos, $errores) {
        $quien = isset($user->usuario) ? $user->usuario : (isset($user->id) ? 'id ' . $user->id : '?');
        $txt = date('Y-m-d H:i:s') . " | $quien | $total archivo(s) | codigo $codigo\n";
        foreach ($archivos as $a) $txt .= '    ' . $a['tipo'] . '  ' . $a['ruta'] . "\n";
        foreach ($errores as $e) $txt .= '    !! ' . $e . "\n";
        @file_put_contents(APPPATH . 'logs' . DIRECTORY_SEPARATOR . 'publicar.log', $txt, FILE_APPEND);
    }

    private function q($s) {
        return '"' . str_replace('"', '', $s) . '"';
    }

    private function responder($exito, $mensaje) {
        echo json_encode(array('exito' => $exito, 'mensaje' => $mensaje));
        exit;
    }
}
