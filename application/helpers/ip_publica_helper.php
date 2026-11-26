<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * IP pública de la empresa para los enlaces que se abren desde fuera (dbo.ConfigSistema,
 * clave 'ip_publica'). La cambia el administrador desde "IP pública" en su menú de usuario
 * (controllers/IpPublica.php), sin tener que publicar código.
 */
if (!function_exists('ip_publica')) {
    function ip_publica() {
        static $ip = null;
        if ($ip !== null) return $ip;
        $ip = '177.244.42.94';   // si la tabla no existe o falla la consulta
        try {
            $fila = get_instance()->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = 'ip_publica'")->row_array();
            if ($fila && filter_var($fila['valor'], FILTER_VALIDATE_IP)) $ip = $fila['valor'];
        } catch (Exception $e) {}
        return $ip;
    }
}
