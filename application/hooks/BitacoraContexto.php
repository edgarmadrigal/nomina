<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Le dice a SQL Server quién está haciendo cada petición (SESSION_CONTEXT), para que los
 * triggers de la bitácora (application/sql/bitacora.sql) guarden el usuario, la IP y la
 * pantalla en dbo.Bitacora en cada alta, modificación o eliminación.
 *
 * Se registra en application/config/hooks.php (post_controller_constructor), después de SesionRequerida.
 */
class BitacoraContexto {

    public function poner()
    {
        $CI =& get_instance();
        if (!isset($CI->db) || !$CI->db->conn_id) return;

        $login = $CI->session->userdata('login');
        $user  = (!empty($login['user']) && !empty($login['exito'])) ? $login['user'] : null;
        if (is_cli()) {
            $usuario = 'Proceso programado (' . $CI->router->class . '/' . $CI->router->method . ')';
        } elseif ($user) {
            $usuario = isset($user->usuario) ? $user->usuario : (isset($user->nombre) ? $user->nombre : 'desconocido');
        } else {
            $usuario = 'Sistema web (sin sesión)';
        }
        self::poner_valores($CI->db, $usuario, $user && isset($user->id) ? (int) $user->id : null,
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            substr($CI->router->class . '/' . $CI->router->method, 0, 200));
    }

    /** También lo usa el login para registrar a nombre de la persona el cifrado de su contraseña. */
    public static function poner_valores($db, $usuario, $idUsuario, $ip, $pantalla)
    {
        $db->query(
            "EXEC sys.sp_set_session_context @key = N'usuario', @value = ?;
             EXEC sys.sp_set_session_context @key = N'idUsuario', @value = ?;
             EXEC sys.sp_set_session_context @key = N'ip', @value = ?;
             EXEC sys.sp_set_session_context @key = N'pantalla', @value = ?;",
            array(substr((string) $usuario, 0, 150), $idUsuario, $ip, $pantalla)
        );
    }
}
