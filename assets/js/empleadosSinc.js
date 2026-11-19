/**
 * Empleados: leyenda "Se actualiza automáticamente cada hora desde Microsip · Última actualización".
 * El job "Nominas - Empleados Microsip" corre a los :05; el botón "Actualizar Empleados Microsip" también cuenta.
 */
var EmpleadosSinc = {
    fnCargar: function () {
        $.post('empleados/ultimaActualizacion', {}, null, 'json').done(function (u) {
            if (!u || !u.fecha) { $('.emp-sinc-ultima').text('sin registro'); return; }
            var f = u.fecha.substr(8, 2) + '/' + u.fecha.substr(5, 2) + '/' + u.fecha.substr(0, 4) + ' ' + u.fecha.substr(11, 5);
            var m = Number(u.minutos), hace = m < 1 ? 'hace un momento' : m < 60 ? 'hace ' + m + ' min' : 'hace ' + Math.floor(m / 60) + ' h ' + (m % 60) + ' min';
            $('.emp-sinc-ultima').text(f + ' (' + hace + (u.origen === 'boton' ? ', manual' : '') + ')');
        });
    }
};
$(function () {
    EmpleadosSinc.fnCargar();
    // después del botón manual
    $(document).ajaxComplete(function (e, xhr, opt) { if (opt.url && opt.url.indexOf('empleados/actualizar') >= 0) EmpleadosSinc.fnCargar(); });
});
$('<style>.emp-sinc{font-size:12px;margin-top:6px;opacity:.85}.emp-sinc .fa{color:#1e88e5}</style>').appendTo('head');
