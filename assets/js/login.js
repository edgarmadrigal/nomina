/**
 * Inicio y cierre de sesión.
 * El login responde JSON: {exito, mensaje, captcha, recargar}. Cuando el servidor pide captcha
 * (siempre desde internet, y dentro de la red después de intentos fallidos) se muestra la imagen.
 * Este archivo también se carga en todas las pantallas por el botón "Cerrar Sesión".
 */
var Login = {
    enviando: false,

    fnCaptcha: function (mostrar) {
        if (!mostrar) { $('#bloqueCaptcha').hide(); return; }
        $('#bloqueCaptcha').show();
        $('#imgCaptcha').attr('src', 'home/captcha?t=' + Date.now());
        $('#captcha').val('');
    },

    fnError: function (texto) {
        $('#error').text(texto || '').toggle(!!texto);
    },

    fnEnviar: function (e) {
        e.preventDefault();
        if (Login.enviando) return;
        var usuario = $.trim($('#usuario').val()), password = $('#password').val();
        if (!usuario || !password) { Login.fnError('Escribe tu usuario y contraseña.'); return; }
        if ($('#bloqueCaptcha').is(':visible') && !$.trim($('#captcha').val())) {
            Login.fnError('Escribe las letras de la imagen.'); $('#captcha').focus(); return;
        }
        Login.enviando = true;
        $('#admin_login').prop('disabled', true).val('Entrando...');
        $.ajax({
            url: 'home/login',
            type: 'post',
            dataType: 'json',
            data: { usuario: usuario, password: password, captcha: $.trim($('#captcha').val()), sitio: $('#sitio').val() }
        }).done(function (data) {
            if (data && data.exito == 1) { window.location = 'home'; return; }
            if (data && data.recargar) { window.location.reload(); return; }
            Login.fnError(data && data.mensaje ? data.mensaje : 'No se pudo iniciar sesión.');
            Login.fnCaptcha(data && data.captcha);
            $('#password').val('').focus();
        }).fail(function () {
            Login.fnError('No hay conexión con el servidor. Intenta de nuevo.');
        }).always(function () {
            Login.enviando = false;
            $('#admin_login').prop('disabled', false).val('Entrar');
        });
    }
};

$(document).ready(function () {
    $(document).off('submit.login').on('submit.login', '#edgar', Login.fnEnviar);
    $(document).off('click.captcha').on('click.captcha', '#otraCaptcha', function (e) { e.preventDefault(); Login.fnCaptcha(true); });

    $(document).off('click.logout').on('click.logout', '#logout_admin', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'home/logout',
            type: 'POST',
            dataType: 'json',
            async: true
        }).always(function () {
            window.location = 'home';
        });
    });
});
