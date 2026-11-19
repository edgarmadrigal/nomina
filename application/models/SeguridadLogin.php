<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Protección del inicio de sesión:
 *  - Bitácora de intentos en dbo.LoginIntento (usuario, IP, si vino de internet, navegador).
 *  - Bloqueo temporal por IP y por usuario después de varios intentos fallidos.
 *  - Captcha (prueba de que es una persona): siempre desde internet; dentro de la red
 *    solo después de intentos fallidos.
 *  - Trampa para robots: campo oculto que una persona no llena y tiempo mínimo para llenar.
 * Ver application/sql/seguridad_login.sql.
 */
class SeguridadLogin extends CI_Model {

    const VENTANA_MIN     = 15;   // minutos que se cuentan los intentos fallidos (y que dura el bloqueo)
    const MAX_IP          = 10;   // fallidos desde una misma IP
    const MAX_USUARIO     = 5;    // fallidos de un mismo usuario (desde cualquier IP)
    const CAPTCHA_INTERNO = 2;    // dentro de la red se pide captcha después de estos fallidos
    const MIN_SEGUNDOS    = 1;    // menos que esto entre abrir la página y entrar = robot
    const CAPTCHA_VIGENCIA = 600; // segundos que sirve una imagen de captcha
    const CAPTCHA_LETRAS  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // sin 0/O, 1/I

    public function ip()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '0.0.0.0';
    }

    /** true si la conexión viene de internet (no de 192.168.x, 10.x, 172.16-31.x ni del mismo equipo). */
    public function esExterna($ip = null)
    {
        $ip = $ip ?: $this->ip();
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) return true;
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /** Fallidos recientes (desde el último acierto) de una IP o de un usuario, y segundos que faltan para que venzan. */
    private function fallidos($campo, $valor)
    {
        // Por IP cuenta todo (robots, captcha y contraseña); por usuario solo contraseña equivocada,
        // para que un error al escribir las letras del captcha no bloquee a la persona.
        $campo = $campo === 'ip' ? 'ip' : 'usuario';
        $motivos = $campo === 'ip' ? "motivo NOT LIKE 'BLOQUEO%'" : "motivo = 'CONTRASENA'";
        $r = $this->db->query(
            "SELECT COUNT(*) AS n, DATEDIFF(SECOND, GETDATE(), DATEADD(MINUTE, ?, MAX(fecha))) AS faltan
               FROM dbo.LoginIntento
              WHERE $campo = ? AND exito = 0 AND $motivos
                AND fecha >= DATEADD(MINUTE, -?, GETDATE())
                AND fecha > ISNULL((SELECT MAX(fecha) FROM dbo.LoginIntento WHERE $campo = ? AND exito = 1), '19000101')",
            array(self::VENTANA_MIN, $valor, self::VENTANA_MIN, $valor)
        )->row_array();
        return array('n' => (int) $r['n'], 'faltan' => max(60, (int) $r['faltan']));
    }

    /** Si la IP o el usuario están bloqueados regresa el motivo y el mensaje; si no, null. */
    public function bloqueo($usuario)
    {
        $ip = $this->fallidos('ip', $this->ip());
        if ($ip['n'] >= self::MAX_IP) {
            return array('motivo' => 'BLOQUEO_IP', 'mensaje' => 'Demasiados intentos fallidos desde esta conexión. Espera ' . ceil($ip['faltan'] / 60) . ' minuto(s).');
        }
        if ($usuario !== '') {
            $u = $this->fallidos('usuario', $usuario);
            if ($u['n'] >= self::MAX_USUARIO) {
                return array('motivo' => 'BLOQUEO_USUARIO', 'mensaje' => 'Ese usuario quedó bloqueado por intentos fallidos. Espera ' . ceil($u['faltan'] / 60) . ' minuto(s) o pide ayuda al administrador.');
            }
        }
        return null;
    }

    /** Intentos que le quedan al usuario antes del bloqueo (para avisar). */
    public function quedan($usuario)
    {
        $u = $this->fallidos('usuario', $usuario);
        return max(0, self::MAX_USUARIO - $u['n']);
    }

    public function pideCaptcha()
    {
        if ($this->esExterna()) return true;
        $ip = $this->fallidos('ip', $this->ip());
        return $ip['n'] >= self::CAPTCHA_INTERNO;
    }

    public function registrar($usuario, $exito, $motivo)
    {
        $nav = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 300) : null;
        $this->db->query(
            "INSERT INTO dbo.LoginIntento (usuario, ip, exito, motivo, externo, navegador) VALUES (?, ?, ?, ?, ?, ?)",
            array($usuario === '' ? null : substr($usuario, 0, 50), $this->ip(), $exito ? 1 : 0, $motivo, $this->esExterna() ? 1 : 0, $nav)
        );
    }

    // ---------------- Captcha ----------------

    /** Genera un texto nuevo, lo guarda en la sesión y manda la imagen PNG. */
    public function imagenCaptcha()
    {
        $texto = '';
        for ($i = 0; $i < 5; $i++) $texto .= substr(self::CAPTCHA_LETRAS, random_int(0, strlen(self::CAPTCHA_LETRAS) - 1), 1);
        $this->session->set_userdata('captcha', array('texto' => $texto, 'hora' => time()));

        $an = 170; $al = 56;
        $img = imagecreatetruecolor($an, $al);
        imagefilledrectangle($img, 0, 0, $an, $al, imagecolorallocate($img, 244, 246, 250));
        for ($i = 0; $i < 7; $i++) {   // líneas de ruido
            $c = imagecolorallocate($img, random_int(120, 200), random_int(120, 200), random_int(120, 200));
            imagesetthickness($img, random_int(1, 2));
            imageline($img, random_int(0, $an), random_int(0, $al), random_int(0, $an), random_int(0, $al), $c);
        }
        for ($i = 0; $i < 350; $i++) {  // puntos
            imagesetpixel($img, random_int(0, $an - 1), random_int(0, $al - 1), imagecolorallocate($img, random_int(90, 220), random_int(90, 220), random_int(90, 220)));
        }
        $fuente = 'C:/Windows/Fonts/arialbd.ttf';
        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($img, random_int(10, 90), random_int(20, 90), random_int(60, 140));
            if (is_file($fuente) && function_exists('imagettftext')) {
                imagettftext($img, random_int(21, 26), random_int(-25, 25), 12 + $i * 31, random_int(36, 44), $c, $fuente, $texto[$i]);
            } else {
                imagestring($img, 5, 20 + $i * 28, random_int(12, 28), $texto[$i], $c);
            }
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        imagepng($img);
        imagedestroy($img);
    }

    /** Revisa lo que escribió la persona; cada imagen sirve una sola vez. */
    public function captchaCorrecto($respuesta)
    {
        $c = $this->session->userdata('captcha');
        $this->session->unset_userdata('captcha');
        if (!is_array($c) || time() - $c['hora'] > self::CAPTCHA_VIGENCIA) return false;
        return hash_equals($c['texto'], strtoupper(preg_replace('/\s+/', '', (string) $respuesta)));
    }
}
