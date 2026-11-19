<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chat entre usuarios (application/sql/chat.sql).
 * Toda consulta valida que el usuario participe en la conversación; la GENERAL es de todos.
 */
class ChatModel extends CI_Model {

    function __construct(){
        parent::__construct();
    }

    /** Agrega al usuario a la conversación GENERAL si todavía no está. */
    public function unirGeneral($idUsuario){
        $this->db->query(
            "INSERT INTO dbo.ChatParticipante (idConversacion, idUsuario, ultimoLeido)
             SELECT c.id, ?, ISNULL((SELECT MAX(m.id) FROM dbo.ChatMensaje m WHERE m.idConversacion = c.id), 0)
             FROM dbo.ChatConversacion c
             WHERE c.tipo = 'GENERAL'
               AND NOT EXISTS (SELECT 1 FROM dbo.ChatParticipante p WHERE p.idConversacion = c.id AND p.idUsuario = ?)",
            array($idUsuario, $idUsuario));
    }

    public function esParticipante($idConversacion, $idUsuario){
        return $this->db->query("SELECT 1 AS x FROM dbo.ChatParticipante WHERE idConversacion = ? AND idUsuario = ?",
                                array($idConversacion, $idUsuario))->num_rows() > 0;
    }

    /** Conversaciones del usuario con no leídos, último mensaje y nombre a mostrar. */
    public function conversaciones($idUsuario){
        $sql = "SELECT c.id, c.tipo, c.estado, c.pagina, c.creadoPor,
                       CONVERT(VARCHAR(16), c.ultimoMensaje, 120) AS ultimoMensaje,
                       CASE WHEN c.tipo = 'DIRECTO'
                            THEN (SELECT TOP 1 p2.idUsuario FROM dbo.ChatParticipante p2
                                  WHERE p2.idConversacion = c.id AND p2.idUsuario <> ?)
                            ELSE NULL END AS otroId,
                       CASE WHEN c.tipo = 'DIRECTO'
                            THEN (SELECT TOP 1 u.nombre FROM dbo.ChatParticipante p2 JOIN dbo.usuarios u ON u.id = p2.idUsuario
                                  WHERE p2.idConversacion = c.id AND p2.idUsuario <> ?)
                            ELSE c.titulo END AS titulo,
                       (SELECT COUNT(*) FROM dbo.ChatMensaje m
                        WHERE m.idConversacion = c.id AND m.id > p.ultimoLeido AND ISNULL(m.idUsuario, 0) <> ?) AS noLeidos,
                       ult.texto AS ultimoTexto, ult.idArchivo AS ultimoArchivo, uu.nombre AS ultimoDe,
                       STUFF((SELECT ', ' + u3.nombre FROM dbo.ChatParticipante p3 JOIN dbo.usuarios u3 ON u3.id = p3.idUsuario
                              WHERE p3.idConversacion = c.id AND c.tipo IN ('GRUPO', 'SOPORTE')
                              FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS participantes
                FROM dbo.ChatParticipante p
                JOIN dbo.ChatConversacion c ON c.id = p.idConversacion
                OUTER APPLY (SELECT TOP 1 m.texto, m.idArchivo, m.idUsuario FROM dbo.ChatMensaje m
                             WHERE m.idConversacion = c.id ORDER BY m.id DESC) ult
                LEFT JOIN dbo.usuarios uu ON uu.id = ult.idUsuario
                WHERE p.idUsuario = ?
                ORDER BY CASE WHEN c.tipo = 'GENERAL' THEN 0 ELSE 1 END, c.ultimoMensaje DESC";
        return $this->db->query($sql, array($idUsuario, $idUsuario, $idUsuario, $idUsuario))->result_array();
    }

    /**
     * Registra que el usuario sigue conectado (visto) y, si se movió hace poco, activo.
     * $conInteraccion = hubo mouse/teclado en los últimos minutos.
     */
    public function tocarPresencia($idUsuario, $conInteraccion){
        $idUsuario = (int) $idUsuario;
        if ($idUsuario <= 0) return;
        $setActivo = $conInteraccion ? 'GETDATE()' : 't.activo';
        $insActivo = $conInteraccion ? 'GETDATE()' : 'NULL';
        $this->db->query(
            "MERGE dbo.ChatPresencia AS t
             USING (SELECT ? AS idUsuario) AS s ON t.idUsuario = s.idUsuario
             WHEN MATCHED THEN UPDATE SET visto = GETDATE(), activo = $setActivo
             WHEN NOT MATCHED THEN INSERT (idUsuario, visto, activo) VALUES (?, GETDATE(), $insActivo);",
            array($idUsuario, $idUsuario));
    }

    /**
     * Estado de cada usuario: 'activo' (verde), 'ausente' (naranja) u 'offline' (rojo).
     * Conectado = latido en los últimos 90 s; ausente = conectado pero sin moverse 10 min.
     */
    public function presencias(){
        $rows = $this->db->query(
            "SELECT idUsuario,
                    DATEDIFF(SECOND, visto, GETDATE()) AS segVisto,
                    CASE WHEN activo IS NULL THEN NULL ELSE DATEDIFF(SECOND, activo, GETDATE()) END AS segActivo
             FROM dbo.ChatPresencia")->result_array();
        $out = array();
        foreach ($rows as $r) {
            $sv = (int) $r['segVisto'];
            $sa = $r['segActivo'] === null ? null : (int) $r['segActivo'];
            if ($sv > 90)                      $estado = 'offline';
            elseif ($sa === null || $sa > 600) $estado = 'ausente';
            else                               $estado = 'activo';
            $out[(string) $r['idUsuario']] = $estado;
        }
        return $out;
    }

    /** Mensajes después de $desde (o los últimos 150) y marca como leído. */
    public function mensajes($idConversacion, $idUsuario, $desde){
        if ($desde > 0) {
            $filas = $this->db->query(
                "SELECT m.id, m.idUsuario, u.nombre, m.texto, m.idArchivo, a.nombre AS archivoNombre,
                        CONVERT(VARCHAR(16), m.fecha, 120) AS fecha
                 FROM dbo.ChatMensaje m
                 LEFT JOIN dbo.usuarios u ON u.id = m.idUsuario
                 LEFT JOIN dbo.ChatArchivo a ON a.id = m.idArchivo
                 WHERE m.idConversacion = ? AND m.id > ?
                 ORDER BY m.id", array($idConversacion, $desde))->result_array();
        } else {
            $filas = $this->db->query(
                "SELECT * FROM (
                    SELECT TOP 150 m.id, m.idUsuario, u.nombre, m.texto, m.idArchivo, a.nombre AS archivoNombre,
                           CONVERT(VARCHAR(16), m.fecha, 120) AS fecha
                    FROM dbo.ChatMensaje m
                    LEFT JOIN dbo.usuarios u ON u.id = m.idUsuario
                    LEFT JOIN dbo.ChatArchivo a ON a.id = m.idArchivo
                    WHERE m.idConversacion = ?
                    ORDER BY m.id DESC) x
                 ORDER BY id", array($idConversacion))->result_array();
        }
        if ($filas) {
            $ultimo = end($filas);
            $this->db->query("UPDATE dbo.ChatParticipante SET ultimoLeido = ? WHERE idConversacion = ? AND idUsuario = ? AND ultimoLeido < ?",
                             array($ultimo['id'], $idConversacion, $idUsuario, $ultimo['id']));
        }
        return $filas;
    }

    public function conversacion($idConversacion){
        return $this->db->query("SELECT id, tipo, titulo, estado, pagina, creadoPor FROM dbo.ChatConversacion WHERE id = ?",
                                array($idConversacion))->row_array();
    }

    public function enviar($idConversacion, $idUsuario, $texto, $archivo = null){
        $idArchivo = null;
        if ($archivo) {
            $idArchivo = $this->insertarId("INSERT INTO dbo.ChatArchivo (nombre, mime, tamano, datos) OUTPUT INSERTED.id INTO @t VALUES (?, ?, ?, ?)",
                             array($archivo['nombre'], $archivo['mime'], $archivo['tamano'], $archivo['datos']));
        }
        $idMensaje = $this->insertarId(
            "INSERT INTO dbo.ChatMensaje (idConversacion, idUsuario, texto, idArchivo) OUTPUT INSERTED.id INTO @t VALUES (?, ?, ?, ?)",
            array($idConversacion, $idUsuario, $texto === '' ? null : $texto, $idArchivo));
        $this->db->query("UPDATE dbo.ChatConversacion SET ultimoMensaje = GETDATE() WHERE id = ?", array($idConversacion));
        if ($idUsuario) {
            $this->db->query("UPDATE dbo.ChatParticipante SET ultimoLeido = ? WHERE idConversacion = ? AND idUsuario = ?",
                             array($idMensaje, $idConversacion, $idUsuario));
        }
        return $idMensaje;
    }

    /** Conversación directa entre dos usuarios (la crea si no existe). */
    public function directo($yo, $otro){
        $f = $this->db->query(
            "SELECT TOP 1 c.id FROM dbo.ChatConversacion c
             WHERE c.tipo = 'DIRECTO'
               AND EXISTS (SELECT 1 FROM dbo.ChatParticipante p WHERE p.idConversacion = c.id AND p.idUsuario = ?)
               AND EXISTS (SELECT 1 FROM dbo.ChatParticipante p WHERE p.idConversacion = c.id AND p.idUsuario = ?)",
            array($yo, $otro))->row_array();
        if ($f) return (int) $f['id'];
        return $this->crear('DIRECTO', null, $yo, array($yo, $otro));
    }

    public function crear($tipo, $titulo, $creadoPor, $usuarios, $estado = null, $pagina = null){
        $id = $this->insertarId(
            "INSERT INTO dbo.ChatConversacion (tipo, titulo, estado, pagina, creadoPor) OUTPUT INSERTED.id INTO @t VALUES (?, ?, ?, ?, ?)",
            array($tipo, $titulo, $estado, $pagina, $creadoPor));
        foreach (array_unique(array_map('intval', $usuarios)) as $u) {
            $this->db->query("INSERT INTO dbo.ChatParticipante (idConversacion, idUsuario) VALUES (?, ?)", array($id, $u));
        }
        return $id;
    }

    /** Usuarios que existen (para validar los que manda la página). */
    public function usuarios($excepto){
        return $this->db->query("SELECT id, nombre, usuario, idPerfil FROM dbo.usuarios WHERE id <> ? ORDER BY nombre",
                                array($excepto))->result_array();
    }

    public function admins(){
        return array_map(function ($r) { return (int) $r['id']; },
            $this->db->query("SELECT id FROM dbo.usuarios WHERE idPerfil = 1")->result_array());
    }

    public function cambiarEstado($idConversacion, $estado){
        $this->db->query("UPDATE dbo.ChatConversacion SET estado = ? WHERE id = ? AND tipo = 'SOPORTE'", array($estado, $idConversacion));
    }

    /** No leídos del usuario y el último mensaje recibido (para el aviso en todas las páginas). */
    public function pendientes($idUsuario){
        $f = $this->db->query(
            "SELECT ISNULL(SUM(x.n), 0) AS total, MAX(x.ultimoId) AS ultimoId
             FROM (SELECT COUNT(*) AS n, MAX(m.id) AS ultimoId
                   FROM dbo.ChatParticipante p
                   JOIN dbo.ChatMensaje m ON m.idConversacion = p.idConversacion AND m.id > p.ultimoLeido
                   WHERE p.idUsuario = ? AND ISNULL(m.idUsuario, 0) <> ?
                   GROUP BY p.idConversacion) x", array($idUsuario, $idUsuario))->row_array();
        $res = array('total' => (int) $f['total'], 'ultimo' => null);
        if ($f['ultimoId']) {
            $res['ultimo'] = $this->db->query(
                "SELECT m.id, m.idConversacion, ISNULL(u.nombre, 'Sistema') AS de, m.texto, m.idArchivo
                 FROM dbo.ChatMensaje m LEFT JOIN dbo.usuarios u ON u.id = m.idUsuario WHERE m.id = ?",
                array($f['ultimoId']))->row_array();
        }
        return $res;
    }

    /**
     * INSERT que devuelve el id nuevo. Empieza con DECLARE porque CodeIgniter trata
     * las consultas que empiezan con INSERT como escritura y no regresa renglones.
     */
    private function insertarId($insert, $params){
        $f = $this->db->query("DECLARE @t TABLE (id INT); SET NOCOUNT ON; " . $insert . "; SELECT id FROM @t;", $params)->row_array();
        return (int) $f['id'];
    }

    /** Imagen adjunta, solo si el usuario participa en la conversación del mensaje. */
    public function archivo($idArchivo, $idUsuario){
        return $this->db->query(
            "SELECT TOP 1 a.nombre, a.mime, a.datos
             FROM dbo.ChatArchivo a
             JOIN dbo.ChatMensaje m ON m.idArchivo = a.id
             JOIN dbo.ChatParticipante p ON p.idConversacion = m.idConversacion AND p.idUsuario = ?
             WHERE a.id = ?", array($idUsuario, $idArchivo))->row_array();
    }
}
