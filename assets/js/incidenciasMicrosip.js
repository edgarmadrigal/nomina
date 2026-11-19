// Incidencias: estado y botón "Traer de Microsip" (controllers/incidencia.php -> dbo.SincronizaIncidenciasMicrosip).
var IncMicrosip = {
    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnEstado: function () {
        $.ajax({ url: 'incidencia/microsipEstado', type: 'post', dataType: 'json' }).done(function (r) {
            if (!r || !r.fecha) { $('#microsipEstado').text('Todavía no se ha sincronizado.'); return; }
            var txt = 'Última actualización: ' + IncMicrosip.fnEsc(r.fecha) + ' (' + IncMicrosip.fnEsc(r.origen) + ')' +
                      ' · ' + r.nuevas + ' nuevas, ' + r.actualizadas + ' actualizadas, ' + r.borradas + ' quitadas' +
                      '<br><small>Se actualiza sola cada hora. Las que vienen de Microsip se marcan con la etiqueta <span class="badge badge-info" style="color:#fff !important;">Microsip</span>; si se cambian allá, se cambian aquí.</small>';
            if (+r.errores > 0) txt += '<br><span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + r.errores + ' empresa(s) no respondieron.</span>';
            $('#microsipEstado').html(txt);
        });
    },

    fnTraer: function () {
        var empresa = $('#empresa_id').val();
        var nombre = empresa && empresa !== '0' ? $('#empresa_id option:selected').text() : 'todas las empresas';
        if (!confirm('¿Traer de Microsip las vacaciones e incapacidades de ' + nombre + '?')) return;
        var $b = $('#btnTraerMicrosip').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Trayendo...');
        $.ajax({
            url: 'incidencia/traerMicrosip',
            type: 'post',
            data: { empresa_id: empresa && empresa !== '0' ? empresa : '' },
            dataType: 'json'
        }).done(function (r) {
            if (!r.exito) { alert(r.mensaje); return; }
            var nuevas = 0, act = 0, borr = 0, errores = [];
            $.each(r.empresas || [], function (i, e) {
                nuevas += +e.nuevas || 0; act += +e.actualizadas || 0; borr += +e.borradas || 0;
                if (e.error) errores.push(e.empresa + ': ' + e.error);
            });
            var msg = nuevas + ' nuevas, ' + act + ' actualizadas y ' + borr + ' quitadas (ya no están en Microsip).';
            if (errores.length) msg += '\n\nNo respondieron:\n' + errores.join('\n');
            if (typeof swal === 'function') swal('Listo', msg, errores.length ? 'warning' : 'success');
            else alert(msg);
            IncMicrosip.fnEstado();
            // refrescar las incidencias del empleado que se está viendo
            var emp = $('#selEmpleado').val();
            if (emp && emp !== '0') $('#selEmpleado').trigger('change');
        }).fail(function () {
            alert('No se pudo traer la información de Microsip.');
        }).always(function () {
            $b.prop('disabled', false).html('<i class="fa fa-download"></i> Traer de Microsip');
        });
    }
};

$(document).ready(function () {
    if (!$('#btnTraerMicrosip').length) return;
    IncMicrosip.fnEstado();
    $('#btnTraerMicrosip').on('click', IncMicrosip.fnTraer);
});
