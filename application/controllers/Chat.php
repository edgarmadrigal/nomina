<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chat entre usuarios de Nominas: canal general, mensajes directos, grupos y
 * reportes de fallas (SOPORTE) que atienden los administradores.
 * Tablas en application/sql/chat.sql; pantalla en views/chatView.php + assets/js/chat.js;
 * el aviso de mensajes nuevos en todas las páginas está en assets/js/chatAviso.js.
 */
class Chat extends CI_Controller {

    private $estados = array('ABIERTO', 'EN PROCESO', 'RESUELTO');

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('ChatModel');
    }

    public function index(){
        header('Access-Control-Allow-Origin: *');
        if (!$this->usuario(false)) { $this->load->view('login'); return; }
        $data['user'] = $this->session->userdata['login']['user'];
        $data['esAdmin'] = $this->esAdmin();
        $this->ChatModel->unirGeneral((int) $data['user']->id);
        $this->load->view('header', $data);
        $this->load->view('chatView', $data);
        $this->load->view('footer', $data);
    }

    public function conversaciones(){
        $u = $this->usuario();
        if (!$u) return;
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();   // se sondea seguido: no bloquear la sesión
        $this->ChatModel->unirGeneral((int) $u->id);
        echo json_encode(array('exito' => true, 'yo' => (int) $u->id, 'conversaciones' => $this->ChatModel->conversaciones((int) $u->id)));
    }

    public function mensajes(){
        $u = $this->usuario();
        if (!$u) return;
        $id = (int) $this->input->post('id');
        if (!$this->ChatModel->esParticipante($id, (int) $u->id)) { $this->sinAcceso(); return; }
        echo json_encode(array(
            'exito'        => true,
            'conversacion' => $this->ChatModel->conversacion($id),
            'mensajes'     => $this->ChatModel->mensajes($id, (int) $u->id, (int) $this->input->post('desde')),
        ));
    }

    public function enviar(){
        $u = $this->usuario();
        if (!$u) return;
        $id = (int) $this->input->post('id');
        if (!$this->ChatModel->esParticipante($id, (int) $u->id)) { $this->sinAcceso(); return; }
        $texto = trim((string) $this->input->post('texto'));
        $archivo = $this->imagen();
        if (is_string($archivo)) { echo json_encode(['exito' => false, 'mensaje' => $archivo]); return; }
        if ($texto === '' && !$archivo) { echo json_encode(['exito' => false, 'mensaje' => 'Escribe un mensaje.']); return; }
        $idMensaje = $this->ChatModel->enviar($id, (int) $u->id, mb_substr($texto, 0, 4000), $archivo);
        echo json_encode(['exito' => true, 'id' => $idMensaje]);
    }

    /** Nueva conversación: DIRECTO (usuario), GRUPO (titulo + usuarios) o SOPORTE (reporte de falla). */
    public function nueva(){
        $u = $this->usuario();
        if (!$u) return;
        $yo = (int) $u->id;
        $tipo = $this->input->post('tipo');
        $validos = array_map(function ($r) { return (int) $r['id']; }, $this->ChatModel->usuarios($yo));

        if ($tipo === 'DIRECTO') {
            $otro = (int) $this->input->post('usuario');
            if (!in_array($otro, $validos, true)) { echo json_encode(['exito' => false, 'mensaje' => 'Elige un usuario.']); return; }
            echo json_encode(['exito' => true, 'id' => $this->ChatModel->directo($yo, $otro)]);
            return;
        }

        if ($tipo === 'GRUPO') {
            $titulo = trim((string) $this->input->post('titulo'));
            $usuarios = array_values(array_intersect(array_map('intval', (array) $this->input->post('usuarios')), $validos));
            if ($titulo === '') { echo json_encode(['exito' => false, 'mensaje' => 'Escribe el nombre del grupo.']); return; }
            if (count($usuarios) < 1) { echo json_encode(['exito' => false, 'mensaje' => 'Agrega al menos un usuario al grupo.']); return; }
            $usuarios[] = $yo;
            $id = $this->ChatModel->crear('GRUPO', mb_substr($titulo, 0, 150), $yo, $usuarios);
            $this->ChatModel->enviar($id, null, $u->nombre . ' creó el grupo.');
            echo json_encode(['exito' => true, 'id' => $id]);
            return;
        }

        if ($tipo === 'SOPORTE') {
            $titulo = trim((string) $this->input->post('titulo'));
            $texto  = trim((string) $this->input->post('texto'));
            $pagina = mb_substr(trim((string) $this->input->post('pagina')), 0, 300);
            if ($titulo === '' || $texto === '') { echo json_encode(['exito' => false, 'mensaje' => 'Escribe qué pasó: un título y la descripción.']); return; }
            $archivo = $this->imagen();
            if (is_string($archivo)) { echo json_encode(['exito' => false, 'mensaje' => $archivo]); return; }
            // participan quien reporta y todos los administradores
            $usuarios = array_merge(array($yo), $this->ChatModel->admins());
            $id = $this->ChatModel->crear('SOPORTE', mb_substr($titulo, 0, 150), $yo, $usuarios, 'ABIERTO', $pagina === '' ? null : $pagina);
            $this->ChatModel->enviar($id, $yo, mb_substr($texto, 0, 4000), $archivo);
            echo json_encode(['exito' => true, 'id' => $id]);
            return;
        }

        echo json_encode(['exito' => false, 'mensaje' => 'Tipo de conversación no válido.']);
    }

    /** Solo el administrador cambia el estado de un reporte de falla. */
    public function estado(){
        $u = $this->usuario();
        if (!$u) return;
        if (!$this->esAdmin()) { echo json_encode(['exito' => false, 'mensaje' => 'Solo el administrador cambia el estado de un reporte.']); return; }
        $id = (int) $this->input->post('id');
        $estado = (string) $this->input->post('estado');
        $c = $this->ChatModel->conversacion($id);
        if (!$c || $c['tipo'] !== 'SOPORTE' || !in_array($estado, $this->estados, true)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Reporte o estado no válido.']);
            return;
        }
        if ($c['estado'] !== $estado) {
            $this->ChatModel->cambiarEstado($id, $estado);
            $this->ChatModel->enviar($id, null, $u->nombre . ' cambió el estado a ' . $estado . '.');
        }
        echo json_encode(['exito' => true]);
    }

    public function usuarios(){
        $u = $this->usuario();
        if (!$u) return;
        $lista = array();
        foreach ($this->ChatModel->usuarios((int) $u->id) as $r) {
            $lista[] = array('id' => (int) $r['id'], 'nombre' => $r['nombre'], 'usuario' => $r['usuario'], 'admin' => $r['idPerfil'] == 1);
        }
        echo json_encode(['exito' => true, 'usuarios' => $lista]);
    }

    /** Para el aviso del encabezado (se consulta cada pocos segundos desde todas las páginas). */
    public function pendientes(){
        $u = $this->usuario();
        if (!$u) return;
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();   // alta frecuencia: no bloquear la sesión
        $this->ChatModel->unirGeneral((int) $u->id);
        $this->ChatModel->tocarPresencia((int) $u->id, $this->input->post('activo') == '1');
        echo json_encode(array_merge(array('exito' => true), $this->ChatModel->pendientes((int) $u->id)));
    }

    /** Estado en línea de los usuarios (verde/naranja/rojo) para pintar los puntitos del chat. */
    public function presencia(){
        $u = $this->usuario();
        if (!$u) return;
        if (session_status() === PHP_SESSION_ACTIVE) @session_write_close();   // alta frecuencia: no bloquear la sesión
        $this->ChatModel->tocarPresencia((int) $u->id, $this->input->post('activo') == '1');
        echo json_encode(array('exito' => true, 'presencias' => $this->ChatModel->presencias()));
    }

    public function archivo($id = 0){
        $u = $this->usuario(false);
        if (!$u) { show_error('La sesión terminó.', 403); return; }
        $a = $this->ChatModel->archivo((int) $id, (int) $u->id);
        if (!$a) { show_error('No se encontró la imagen.', 404); return; }
        $datos = base64_decode($a['datos']);
        header('Content-Type: ' . $a['mime']);
        header('Content-Length: ' . strlen($datos));
        header('Cache-Control: private, max-age=86400');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $a['nombre']) . '"');
        echo $datos;
    }

    // ------------------------------------------------------------------ Asistente IA (models/ChatIAModel.php)

    /** Conversación del usuario con el asistente. */
    public function iaMensajes(){
        $u = $this->usuario();
        if (!$u) return;
        $this->load->model('ChatIAModel');
        echo json_encode(array(
            'exito'      => true,
            'configurada'=> $this->ChatIAModel->clave() !== '',
            'esAdmin'    => $this->esAdmin(),
            'mensajes'   => $this->ChatIAModel->mensajes((int) $u->id),
        ));
    }

    public function iaEnviar(){
        $u = $this->usuario();
        if (!$u) return;
        $this->load->model('ChatIAModel');
        if ($this->ChatIAModel->clave() === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'El asistente todavía no está configurado.']);
            return;
        }
        $texto = trim((string) $this->input->post('texto'));
        if ($texto === '') { echo json_encode(['exito' => false, 'mensaje' => 'Escribe tu pregunta.']); return; }
        if ($this->ChatIAModel->enviadosUltimaHora((int) $u->id) >= ChatIAModel::POR_HORA) {
            echo json_encode(['exito' => false, 'mensaje' => 'Llegaste al límite de ' . ChatIAModel::POR_HORA . ' preguntas por hora. Intenta más tarde.']);
            return;
        }
        $this->ChatIAModel->guardar((int) $u->id, 'user', mb_substr($texto, 0, 4000));
        $nombre = isset($u->nombre) ? $u->nombre : (isset($u->usuario) ? $u->usuario : '');
        $r = $this->ChatIAModel->preguntar((int) $u->id, $nombre);
        if (isset($r['error'])) {
            echo json_encode(['exito' => false, 'mensaje' => $r['error'], 'mensajes' => $this->ChatIAModel->mensajes((int) $u->id)]);
            return;
        }
        $this->ChatIAModel->guardar((int) $u->id, 'assistant', $r['texto']);
        echo json_encode(['exito' => true, 'mensajes' => $this->ChatIAModel->mensajes((int) $u->id)]);
    }

    /** "Nueva conversación": oculta lo anterior para que el asistente empiece de cero. */
    public function iaNueva(){
        $u = $this->usuario();
        if (!$u) return;
        $this->load->model('ChatIAModel');
        $this->ChatIAModel->nueva((int) $u->id);
        echo json_encode(['exito' => true]);
    }

    /** Solo el administrador captura la clave de Groq (se prueba antes de guardarla; vacía = quitarla). */
    public function iaClave(){
        $u = $this->usuario();
        if (!$u) return;
        if (!$this->esAdmin()) { echo json_encode(['exito' => false, 'mensaje' => 'Solo el administrador configura el asistente.']); return; }
        $this->load->model('ChatIAModel');
        $clave = trim((string) $this->input->post('clave'));
        if ($clave !== '') {
            if (!preg_match('/^[A-Za-z0-9_\-]{20,120}$/', $clave)) {
                echo json_encode(['exito' => false, 'mensaje' => 'Esa no parece una clave de Groq (empieza con "gsk_").']);
                return;
            }
            $r = $this->ChatIAModel->probar($clave);
            if (isset($r['error'])) { echo json_encode(['exito' => false, 'mensaje' => $r['error']]); return; }
        }
        $quien = substr(isset($u->usuario) ? $u->usuario : '', 0, 60);
        $this->ChatIAModel->guardarConfig(ChatIAModel::CLAVE, $clave, $quien);
        echo json_encode(['exito' => true, 'mensaje' => $clave === '' ? 'Se quitó la clave del asistente.' : 'Asistente configurado y probado.']);
    }

    // ------------------------------------------------------------------

    /** Imagen adjunta en "imagen" (data URL). null si no hay, string con el error si no es válida. */
    private function imagen(){
        $dataUrl = (string) $this->input->post('imagen');
        if ($dataUrl === '') return null;
        if (!preg_match('#^data:(image/(png|jpeg|gif|webp));base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return 'Solo se pueden adjuntar imágenes (PNG, JPG, GIF o WEBP).';
        }
        $tamano = (int) floor(strlen($m[3]) * 3 / 4);
        if ($tamano > 5 * 1024 * 1024) return 'La imagen pesa más de 5 MB.';
        $nombre = trim((string) $this->input->post('imagenNombre'));
        if ($nombre === '') $nombre = 'captura.' . ($m[2] === 'jpeg' ? 'jpg' : $m[2]);
        return array('nombre' => mb_substr($nombre, 0, 200), 'mime' => $m[1], 'tamano' => $tamano, 'datos' => $m[3]);
    }

    private function usuario($json = true){
        if ($json) header('Content-Type: application/json');
        if (isset($this->session->userdata['login']['user']) && $this->session->userdata['login']['exito']) {
            return $this->session->userdata['login']['user'];
        }
        if ($json) echo json_encode(['exito' => false, 'mensaje' => 'La sesión terminó; vuelve a entrar.']);
        return null;
    }

    private function sinAcceso(){
        echo json_encode(['exito' => false, 'mensaje' => 'No participas en esa conversación.']);
    }

    private function esAdmin(){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        return $u && isset($u->idPerfil) && $u->idPerfil == 1;
    }
}
