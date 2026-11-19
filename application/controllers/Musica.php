<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Radio de la página (símbolo de música junto al nombre del usuario).
 * Las estaciones las busca el navegador en radio-browser.info (radio por internet,
 * solo audio); aquí se maneja la contraseña de la música (application/sql/musica.sql) y la
 * búsqueda en YouTube para el mini reproductor (visible, como piden las reglas de YouTube).
 */
class Musica extends CI_Controller {

    const CLAVE = 'musica_clave';
    const YOUTUBE = 'youtube_api_key';

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /** Catálogos > Música: cambiar la contraseña de la música (solo administrador). */
    public function index(){
        header('Access-Control-Allow-Origin: *');
        if (!isset($this->session->userdata['login']) || !$this->session->userdata['login']['exito']) {
            $this->load->view('login');
            return;
        }
        $data['user'] = $this->session->userdata['login']['user'];
        $this->load->view('header', $data);
        if ($this->esAdmin()) {
            $data['info'] = $this->db->query(
                "SELECT CONVERT(VARCHAR(16), fechaModifico, 120) AS fecha, usuarioModifico
                 FROM dbo.ConfigSistema WHERE clave = ? AND valor IS NOT NULL", array(self::CLAVE))->row_array();
            $data['youtube'] = $this->db->query(
                "SELECT CONVERT(VARCHAR(16), fechaModifico, 120) AS fecha, usuarioModifico, RIGHT(valor, 4) AS final
                 FROM dbo.ConfigSistema WHERE clave = ? AND valor IS NOT NULL AND valor <> ''", array(self::YOUTUBE))->row_array();
            $this->load->view('musicaView', $data);
        } else {
            echo '<div class="page-wrapper"><div class="container-fluid"><div class="alert alert-warning mt-4">'
               . '<i class="fa fa-lock"></i> La contraseña de la música solo la puede ver el administrador.</div></div></div>';
        }
        $this->load->view('footer', $data);
    }

    /** ¿Ya desbloqueó la música en esta sesión? ¿Hay contraseña definida? */
    public function estado(){
        if (!$this->usuario()) return;
        echo json_encode(array(
            'exito'         => true,
            'desbloqueada'  => $this->desbloqueada(),
            'hayClave'      => $this->hashClave() !== '',
            'esAdmin'       => $this->esAdmin(),
            'hayYoutube'    => $this->claveYoutube() !== '',
        ));
    }

    public function desbloquear(){
        if (!$this->usuario()) return;
        $hash = $this->hashClave();
        if ($hash === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'El administrador todavía no define la contraseña de la música.']);
            return;
        }
        // pequeña pausa contra intentos repetidos
        usleep(400000);
        if (!password_verify((string) $this->input->post('clave'), $hash)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Contraseña incorrecta.']);
            return;
        }
        $this->session->set_userdata('musicaDesbloqueada', true);
        echo json_encode(['exito' => true]);
    }

    public function bloquear(){
        if (!$this->usuario()) return;
        $this->session->unset_userdata('musicaDesbloqueada');
        echo json_encode(['exito' => true]);
    }

    /** Solo el administrador define o cambia la contraseña. */
    public function cambiarClave(){
        $u = $this->usuario();
        if (!$u) return;
        if (!$this->esAdmin()) {
            echo json_encode(['exito' => false, 'mensaje' => 'Solo el administrador puede cambiar la contraseña de la música.']);
            return;
        }
        $nueva = (string) $this->input->post('nueva');
        if (strlen($nueva) < 4) {
            echo json_encode(['exito' => false, 'mensaje' => 'La contraseña debe tener al menos 4 caracteres.']);
            return;
        }
        if ($nueva !== (string) $this->input->post('confirmar')) {
            echo json_encode(['exito' => false, 'mensaje' => 'La confirmación no coincide con la nueva contraseña.']);
            return;
        }
        // solo cambia la contraseña de la música (dbo.ConfigSistema); la del administrador (dbo.usuarios) no se toca
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $quien = substr(isset($u->usuario) ? $u->usuario : '', 0, 60);
        $this->db->query(
            "MERGE dbo.ConfigSistema AS t
             USING (SELECT ? AS clave) AS s ON t.clave = s.clave
             WHEN MATCHED THEN UPDATE SET valor = ?, fechaModifico = GETDATE(), usuarioModifico = ?
             WHEN NOT MATCHED THEN INSERT (clave, valor, usuarioModifico) VALUES (?, ?, ?);",
            array(self::CLAVE, $hash, $quien, self::CLAVE, $hash, $quien));
        $this->session->set_userdata('musicaDesbloqueada', true);
        echo json_encode(['exito' => true, 'mensaje' => 'Contraseña de la música guardada.']);
    }

    /** Solo el administrador guarda la clave de la API de YouTube (vacía = quitarla). */
    public function guardarYoutube(){
        $u = $this->usuario();
        if (!$u) return;
        if (!$this->esAdmin()) {
            echo json_encode(['exito' => false, 'mensaje' => 'Solo el administrador puede cambiar la clave de YouTube.']);
            return;
        }
        $clave = trim((string) $this->input->post('clave'));
        if ($clave !== '') {
            if (!preg_match('/^[A-Za-z0-9_\-]{30,60}$/', $clave)) {
                echo json_encode(['exito' => false, 'mensaje' => 'La clave no tiene el formato de una clave de API de Google (empieza con "AIza...").']);
                return;
            }
            // se prueba antes de guardarla
            $r = $this->llamarYoutube($clave, 'musica');
            if (isset($r['error'])) {
                echo json_encode(['exito' => false, 'mensaje' => $r['error']]);
                return;
            }
        }
        $quien = substr(isset($u->usuario) ? $u->usuario : '', 0, 60);
        $this->db->query(
            "MERGE dbo.ConfigSistema AS t
             USING (SELECT ? AS clave) AS s ON t.clave = s.clave
             WHEN MATCHED THEN UPDATE SET valor = ?, fechaModifico = GETDATE(), usuarioModifico = ?
             WHEN NOT MATCHED THEN INSERT (clave, valor, usuarioModifico) VALUES (?, ?, ?);",
            array(self::YOUTUBE, $clave, $quien, self::YOUTUBE, $clave, $quien));
        echo json_encode(['exito' => true, 'mensaje' => $clave === '' ? 'Se quitó la clave de YouTube.' : 'Clave de YouTube guardada y probada.']);
    }

    /** Busca canciones en YouTube (primero las versiones "Artista - Topic", que solo muestran la portada). */
    public function youtube(){
        if (!$this->usuario()) return;
        if (!$this->desbloqueada()) {
            echo json_encode(['exito' => false, 'mensaje' => 'Primero escribe la contraseña de la música.']);
            return;
        }
        $clave = $this->claveYoutube();
        if ($clave === '') {
            echo json_encode(['exito' => false, 'mensaje' => $this->esAdmin()
                ? 'Falta la clave de YouTube: ponla en Catálogos › Música.'
                : 'El administrador todavía no configura YouTube.']);
            return;
        }
        $q = trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('q')));
        if ($q === '') { echo json_encode(['exito' => true, 'videos' => []]); return; }
        $q = mb_substr($q, 0, 150, 'UTF-8');
        $llave = mb_strtolower($q, 'UTF-8');

        // cada búsqueda gasta 100 unidades de la cuota diaria gratis: se reutilizan 7 días
        $c = $this->db->query("SELECT resultado FROM dbo.MusicaYoutubeCache WHERE consulta = ? AND fecha > DATEADD(DAY, -7, GETDATE())", array($llave))->row_array();
        if ($c) {
            echo json_encode(['exito' => true, 'videos' => json_decode($c['resultado'], true)]);
            return;
        }
        $r = $this->llamarYoutube($clave, $q);
        if (isset($r['error'])) {
            echo json_encode(['exito' => false, 'mensaje' => $r['error']]);
            return;
        }
        $json = json_encode($r['videos']);
        $this->db->query(
            "MERGE dbo.MusicaYoutubeCache AS t
             USING (SELECT ? AS consulta) AS s ON t.consulta = s.consulta
             WHEN MATCHED THEN UPDATE SET resultado = ?, fecha = GETDATE()
             WHEN NOT MATCHED THEN INSERT (consulta, resultado) VALUES (?, ?);",
            array($llave, $json, $llave, $json));
        echo json_encode(['exito' => true, 'videos' => $r['videos']]);
    }

    // ------------------------------------------------------------------

    /** YouTube Data API v3 search.list: solo videos de música que se pueden insertar en otra página. */
    private function llamarYoutube($clave, $q){
        $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query(array(
            'part' => 'snippet', 'type' => 'video', 'videoEmbeddable' => 'true', 'videoSyndicated' => 'true',
            'videoCategoryId' => '10', 'regionCode' => 'MX', 'relevanceLanguage' => 'es',
            'maxResults' => 25, 'q' => $q, 'key' => $clave,
        ));
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6));
        $resp = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errCurl = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return array('error' => 'No hay conexión con YouTube (' . $errCurl . ').');
        $d = json_decode($resp, true);
        if ($codigo != 200 || !is_array($d)) {
            $razon = isset($d['error']['errors'][0]['reason']) ? $d['error']['errors'][0]['reason'] : '';
            if ($razon === 'quotaExceeded' || $razon === 'dailyLimitExceeded')
                return array('error' => 'Se acabaron las búsquedas de YouTube de hoy (cuota gratis de Google). Mañana se reinicia; las búsquedas ya hechas siguen funcionando.');
            if ($razon === 'keyInvalid' || $codigo == 400)
                return array('error' => 'La clave de YouTube no es válida.');
            if ($codigo == 403)
                return array('error' => 'Google rechazó la clave: revisa que tenga activada la "YouTube Data API v3" y que sus restricciones no bloqueen este servidor.');
            return array('error' => 'YouTube respondió con error ' . $codigo . '.');
        }
        $videos = array();
        foreach ((isset($d['items']) ? $d['items'] : array()) as $it) {
            if (empty($it['id']['videoId'])) continue;
            $sn = $it['snippet'];
            $canal = html_entity_decode(isset($sn['channelTitle']) ? $sn['channelTitle'] : '', ENT_QUOTES, 'UTF-8');
            $titulo = html_entity_decode(isset($sn['title']) ? $sn['title'] : '', ENT_QUOTES, 'UTF-8');
            $videos[] = array(
                'id'     => $it['id']['videoId'],
                'titulo' => $titulo,
                'canal'  => preg_replace('/\s*-\s*Topic$/i', '', $canal),
                'imagen' => isset($sn['thumbnails']['default']['url']) ? $sn['thumbnails']['default']['url'] : '',
                // "Artista - Topic" = canción oficial que solo muestra la portada; luego audio / letra
                'orden'  => preg_match('/-\s*Topic$/i', $canal) ? 0 : (preg_match('/\b(audio|lyrics?|letra)\b/iu', $titulo) ? 1 : 2),
                'pos'    => count($videos),
            );
        }
        usort($videos, function ($a, $b) { return $a['orden'] != $b['orden'] ? $a['orden'] - $b['orden'] : $a['pos'] - $b['pos']; });
        return array('videos' => $videos);
    }

    private function claveYoutube(){
        $f = $this->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = ?", array(self::YOUTUBE))->row_array();
        return $f && $f['valor'] !== null ? trim((string) $f['valor']) : '';
    }

    private function hashClave(){
        $f = $this->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = ?", array(self::CLAVE))->row_array();
        return $f && $f['valor'] !== null ? (string) $f['valor'] : '';
    }

    /** Los invitados entran solo a escuchar música: no se les pide la contraseña. */
    private function desbloqueada(){
        $this->load->model('PermisoModel');
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        return PermisoModel::esInvitado($u) || (bool) $this->session->userdata('musicaDesbloqueada');
    }

    private function usuario(){
        header('Content-Type: application/json');
        if (isset($this->session->userdata['login']['user']) && $this->session->userdata['login']['exito']) {
            return $this->session->userdata['login']['user'];
        }
        echo json_encode(['exito' => false, 'mensaje' => 'La sesión terminó; vuelve a entrar.']);
        return null;
    }

    private function esAdmin(){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        return $u && isset($u->idPerfil) && $u->idPerfil == 1;
    }
}
