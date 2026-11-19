/* =====================================================================
   MODO NOCTURNO - Sistema Nominas
   Guardar en: assets/js/modo-nocturno.js
   Cargar al final de footer.php (antes de </body>).

   La clase se aplica en <html> desde un script en linea en el <head>
   (ver instrucciones) para que no "parpadee" en blanco al cargar.
   Este archivo solo maneja el boton y guarda la preferencia.
   ===================================================================== */
(function () {
    var CLAVE = 'modoNocturno';
    var raiz = document.documentElement;

    function leerPreferencia() {
        try { return localStorage.getItem(CLAVE) === '1'; } catch (e) { return false; }
    }

    function guardarPreferencia(activo) {
        try { localStorage.setItem(CLAVE, activo ? '1' : '0'); } catch (e) { /* sin almacenamiento */ }
    }

    function actualizarBoton(activo) {
        var boton = document.getElementById('toggleNocturno');
        if (!boton) return;
        var icono = boton.querySelector('i');
        if (icono) {
            // Font Awesome 4 (el sistema usa "fa fa-refresh", "fa fa-download")
            icono.className = activo ? 'fa fa-sun-o' : 'fa fa-moon-o';
        }
        boton.setAttribute('title', activo ? 'Modo claro' : 'Modo nocturno');
    }

    function aplicar(activo) {
        if (activo) {
            raiz.classList.add('modo-nocturno');
        } else {
            raiz.classList.remove('modo-nocturno');
        }
        actualizarBoton(activo);
    }

    function iniciar() {
        aplicar(leerPreferencia());

        var boton = document.getElementById('toggleNocturno');
        if (!boton) return;

        boton.addEventListener('click', function (e) {
            e.preventDefault();
            var activo = !raiz.classList.contains('modo-nocturno');
            aplicar(activo);
            guardarPreferencia(activo);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
