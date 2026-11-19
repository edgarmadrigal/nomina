/**
 * Sube la sesión a HTTPS SOLO si el equipo confía en el certificado (nominas.crt).
 *
 * El servidor no puede saber si un navegador tiene instalado el certificado, así que
 * la decisión se toma aquí: al cargar por HTTP (puerto interno 8080) se intenta leer
 * un archivo por HTTPS (8443). Si carga, el certificado es de confianza y se redirige
 * a HTTPS; si falla (certificado no instalado), se queda en HTTP y todo sigue
 * funcionando con normalidad. Así nadie se queda sin entrar por culpa del certificado.
 *
 * - Solo actúa en el acceso interno por el puerto 8080 (internet entra por 8087 y ese
 *   no debe ir al 8443, que no está publicado hacia afuera).
 * - La decisión se recuerda durante la sesión del navegador (sessionStorage) para no
 *   volver a probar en cada página.
 * - Debe ir lo más arriba posible del <head> (login.php y header.php) y NO necesita jQuery.
 */
(function () {
    try {
        if (location.protocol !== 'http:') return;   // ya vamos por https: nada que hacer
        if (location.port !== '8080') return;         // solo el acceso interno (8080); internet usa 8087

        var host = location.hostname;
        var destino = 'https://' + host + ':8443' + location.pathname + location.search;

        var decision = null;
        try { decision = sessionStorage.getItem('httpsCert'); } catch (e) {}

        if (decision === 'no') return;                 // ya probamos: este equipo no confía en el certificado
        if (decision === 'si') { location.replace(destino); return; }

        // Primera vez en la sesión: probamos si el HTTPS (8443) es de confianza cargando el favicon.
        var img = new Image();
        var listo = false;
        var fin = function (ok) {
            if (listo) return; listo = true;
            try { sessionStorage.setItem('httpsCert', ok ? 'si' : 'no'); } catch (e) {}
            if (ok) location.replace(destino);
        };
        var t = setTimeout(function () { fin(false); }, 3000);   // si tarda, seguimos en HTTP
        img.onload  = function () { clearTimeout(t); fin(true); };
        img.onerror = function () { clearTimeout(t); fin(false); };
        img.src = 'https://' + host + ':8443/nomina/assets/images/favicon.png?cb=' + Date.now();
    } catch (e) { /* ante cualquier duda, nos quedamos en HTTP */ }
})();
