/* "IP pública" del menú de usuario del administrador (controllers/IpPublica.php).
   Cambia la IP pública a la que apuntan los enlaces de descarga, cuando el admin lo decida. */
$(function () {
    $(document).on('click', '#menuIpPublica, #btnIpPublica', function (e) {
        e.preventDefault();
        $.post('ipPublica/estado', {}, null, 'json').done(function (r) {
            if (!r || !r.exito) { alert(r && r.mensaje ? r.mensaje : 'No se pudo consultar.'); return; }
            var esc = function (s) { return $('<div>').text(s == null ? '' : s).html(); };
            var yaNueva = r.actual === r.nueva;
            var otra = yaNueva ? (r.anterior && r.anterior !== r.actual ? r.anterior : '177.244.42.94') : r.nueva;
            var html =
                '<div class="modal fade" id="modalIpPublica" tabindex="-1"><div class="modal-dialog"><div class="modal-content">' +
                '<div class="modal-header"><h5 class="modal-title"><i class="fa fa-globe"></i> IP pública</h5>' +
                '<button type="button" class="close" data-dismiss="modal">&times;</button></div>' +
                '<div class="modal-body">' +
                '<p class="mb-1">Ahora los enlaces de descarga apuntan a:</p>' +
                '<h3 class="mb-1">' + esc(r.actual) + '</h3>' +
                (r.fecha ? '<small class="text-muted">Desde ' + esc(r.fecha) + (r.usuario ? ' (' + esc(r.usuario) + ')' : '') + '</small>' : '') +
                '<hr><p class="mb-2">' + (yaNueva ? 'Si hace falta, puedes regresar a la IP anterior:' :
                    'Cuando la IP nueva ya funcione (puertos 8080 y 8087 abiertos en el módem), cámbiala aquí:') + '</p>' +
                '<button type="button" class="btn ' + (yaNueva ? 'btn-outline-secondary' : 'btn-success') + '" id="btnCambiarIp" data-ip="' + esc(otra) + '">' +
                '<i class="fa fa-exchange"></i> ' + (yaNueva ? 'Regresar a ' : 'Cambiar a ') + esc(otra) + '</button>' +
                '</div></div></div></div>';
            $('#modalIpPublica').remove();
            $('body').append(html);
            $('#modalIpPublica').modal('show');
        });
    });

    $(document).on('click', '#btnCambiarIp', function () {
        var ip = $(this).data('ip');
        if (!confirm('¿Cambiar la IP pública a ' + ip + '?\nTodos los enlaces de descarga apuntarán a esa IP.')) return;
        var $b = $(this).prop('disabled', true);
        $.post('ipPublica/cambiar', { ip: ip }, null, 'json').done(function (r) {
            alert(r && r.mensaje ? r.mensaje : 'Sin respuesta.');
            if (r && r.exito) $('#modalIpPublica').modal('hide');
        }).fail(function () { alert('No se pudo cambiar.'); }).always(function () { $b.prop('disabled', false); });
    });
});
