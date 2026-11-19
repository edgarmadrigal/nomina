// Permisos por usuario: opciones del menú (por sección) y empresas que puede consultar.
var Permisos = {
    base: '',   // rutas relativas, igual que usuarios.js
    titulos: { 'General': 'General', 'Catalogos': 'Catálogos', 'Reportes': 'Reportes', 'Importar/Exportar': 'Importar / Exportar' },

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnCasilla: function (clase, valor, texto, extra) {
        return '<label class="custom-control custom-checkbox" style="margin:2px 0;">' +
               '<input type="checkbox" class="custom-control-input ' + clase + '" value="' + valor + '"' + (extra || '') + '>' +
               '<span class="custom-control-label">' + texto + '</span></label>';
    },

    fnAbrir: function (id, usuario) {
        $('#permisosIdUsuario').val(id);
        $('#permisosTitulo').text('Permisos de ' + usuario);
        $('#permisosOpciones').html('<i class="fa fa-spinner fa-spin"></i> Cargando...');
        $('#permisosEmpresas').html('');
        $('#modalPermisos').modal('show');

        $.ajax({
            url: Permisos.base + 'usuarios/permisos',
            type: 'post',
            data: { id: id },
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.exito) {
                $('#permisosOpciones').html('<div class="text-danger">' + Permisos.fnEsc(resp.mensaje) + '</div>');
                return;
            }
            Permisos.fnPintar(resp, id);
        }).fail(function () {
            $('#permisosOpciones').html('<div class="text-danger">Error al cargar los permisos.</div>');
        });
    },

    fnPintar: function (resp, id) {
        // opciones agrupadas por sección, con "marcar todo" por sección
        var grupos = {}, orden = [];
        $.each(resp.opciones, function (i, o) {
            if (!grupos[o.seccion]) { grupos[o.seccion] = []; orden.push(o.seccion); }
            grupos[o.seccion].push(o);
        });
        var html = '';
        $.each(orden, function (i, sec) {
            var claveSec = 'sec' + i;
            html += '<div class="mb-2" style="border:1px solid #ddd; border-radius:4px; padding:6px 10px;">' +
                    '<div style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; margin-bottom:4px;">' +
                    '<b>' + Permisos.fnEsc(Permisos.titulos[sec] || sec) + '</b>' +
                    Permisos.fnCasilla('chkSeccion', claveSec, '<small>Marcar todo</small>') + '</div>' +
                    '<div class="row">';
            $.each(grupos[sec], function (j, o) {
                html += '<div class="col-6">' + Permisos.fnCasilla('chkOpcion ' + claveSec, o.id, Permisos.fnEsc(o.nombre)) + '</div>';
            });
            html += '</div></div>';
        });
        $('#permisosOpciones').html(html);

        var emp = '';
        $.each(resp.empresas, function (i, e) {
            emp += Permisos.fnCasilla('chkEmpresa', e.id, Permisos.fnEsc(e.nombre));
        });
        $('#permisosEmpresas').html(emp);

        var copiar = '<option value="">— Selecciona un usuario —</option>';
        $.each(resp.usuarios, function (i, u) {
            if (String(u.id) === String(id)) return;
            copiar += '<option value="' + u.id + '">' + Permisos.fnEsc(u.usuario) + ' (' + Permisos.fnEsc(u.nombre) + ')</option>';
        });
        $('#permisosCopiarDe').html(copiar);

        Permisos.fnMarcar(resp.usuario);
    },

    fnMarcar: function (permisos) {
        var op = {}, em = {};
        $.each(permisos.opciones, function (i, x) { op[x] = true; });
        $.each(permisos.empresas, function (i, x) { em[x] = true; });
        $('.chkOpcion').each(function () { $(this).prop('checked', !!op[$(this).val()]); });
        $('.chkEmpresa').each(function () { $(this).prop('checked', !!em[$(this).val()]); });
        // sin empresas asignadas = todas
        $('#permisosTodasEmpresas').prop('checked', permisos.empresas.length === 0);
        Permisos.fnRefrescarEstado();
    },

    fnRefrescarEstado: function () {
        var todas = $('#permisosTodasEmpresas').is(':checked');
        $('.chkEmpresa').prop('disabled', todas);
        $('#permisosEmpresas').css('opacity', todas ? 0.5 : 1);
        $('.chkSeccion').each(function () {
            var $de = $('.chkOpcion.' + $(this).val());
            $(this).prop('checked', $de.length > 0 && $de.filter(':checked').length === $de.length);
        });
    },

    fnCopiar: function () {
        var de = $('#permisosCopiarDe').val();
        if (!de) return;
        $.ajax({
            url: Permisos.base + 'usuarios/permisosDe',
            type: 'post',
            data: { id: de },
            dataType: 'json'
        }).done(function (resp) {
            if (resp.exito) Permisos.fnMarcar(resp.usuario);
            else alert(resp.mensaje);
        });
    },

    fnGuardar: function () {
        var todas = $('#permisosTodasEmpresas').is(':checked');
        var empresas = todas ? [] : $('.chkEmpresa:checked').map(function () { return $(this).val(); }).get();
        if (!todas && empresas.length === 0) {
            alert('Marca al menos una empresa o "Todas las empresas".');
            return;
        }
        var $btn = $('#btnGuardarPermisos').prop('disabled', true);
        $.ajax({
            url: Permisos.base + 'usuarios/guardarPermisos',
            type: 'post',
            data: {
                id: $('#permisosIdUsuario').val(),
                opciones: $('.chkOpcion:checked').map(function () { return $(this).val(); }).get(),
                empresas: empresas
            },
            dataType: 'json'
        }).done(function (resp) {
            if (resp.exito) {
                $('#modalPermisos').modal('hide');
                if (typeof swal === 'function') swal('Listo', resp.mensaje, 'success');
                else alert(resp.mensaje);
            } else {
                alert(resp.mensaje);
            }
        }).fail(function () {
            alert('Error al guardar los permisos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }
};

// lo llama el menú "Acciones" de la tabla de usuarios
function permisos(id, usuario) {
    Permisos.fnAbrir(id, usuario);
}

$(document).ready(function () {
    $(document).on('change', '.chkSeccion', function () {
        $('.chkOpcion.' + $(this).val()).prop('checked', $(this).is(':checked'));
    });
    $(document).on('change', '.chkOpcion, #permisosTodasEmpresas', Permisos.fnRefrescarEstado);
    $('#permisosCopiarDe').on('change', Permisos.fnCopiar);
    $('#btnGuardarPermisos').on('click', Permisos.fnGuardar);
});
