/**
 * En los reportes que piden "No Semana": pone por defecto la semana actual y convierte
 * el campo en una lista para elegir semanas pasadas. Si el año elegido es uno anterior,
 * muestra TODAS las semanas (1..53); si es el año actual, hasta la semana de hoy.
 *
 * Funciona con el patrón común de los reportes: #NoSemana (texto) + #anio (select).
 * También pone la semana actual en #pnSemana (prenómina) si está vacío.
 * Se incluye en el footer, así que aplica en todas las pantallas que tengan esos campos.
 */
(function () {
    function llenarSemanas($sel, maxSemana, seleccionada) {
        var html = '';
        for (var w = 1; w <= maxSemana; w++) {
            html += '<option value="' + w + '"' + (w === seleccionada ? ' selected' : '') + '>Semana ' + w + '</option>';
        }
        $sel.html(html);
    }

    function dd(n) { return (n < 10 ? '0' : '') + n; }
    var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    function fmt(d) { return dd(d.getDate()) + ' ' + MESES[d.getMonth()] + ' ' + d.getFullYear(); }
    // Rango (lunes a domingo) de la semana w del año y, con la MISMA numeración que los SPs
    // (semana 1 = la que contiene el 1 de enero, inicia en lunes).
    function rangoSemana(y, w) {
        var jan1 = new Date(y, 0, 1);
        var dow = (jan1.getDay() + 6) % 7;                 // 0 = lunes
        var ini = new Date(jan1); ini.setDate(jan1.getDate() - dow + (w - 1) * 7);
        var fin = new Date(ini); fin.setDate(ini.getDate() + 6);
        return fmt(ini) + ' – ' + fmt(fin);
    }

    // Cambia el <input id="NoSemana"> por un <select> con el mismo id/clase (conserva el valor por .val()).
    function aSelect(id) {
        var $el = $('#' + id);
        if (!$el.length) return null;
        if ($el.is('select')) return $el;
        var $sel = $('<select>')
            .attr('id', id)
            .attr('class', $el.attr('class') || 'form-control')
            .attr('title', 'Elige la semana');
        $el.replaceWith($sel);
        return $sel;
    }

    $(function () {
        var $sem = $('#NoSemana');
        var $pn = $('#pnSemana');
        if (!$sem.length && !$pn.length) return;

        var base = (typeof base_url !== 'undefined' && base_url) ? base_url
                 : ((typeof Modulo !== 'undefined' && Modulo.baseurl) ? Modulo.baseurl : '');

        $.getJSON(base + 'home/semanaActual').done(function (d) {
            var anioActual = parseInt(d.anio), semActual = parseInt(d.semana);
            if (!anioActual || !semActual) return;

            // Reportes con #NoSemana + #anio
            var $s = aSelect('NoSemana');
            if ($s) {
                var $anio = $('#anio');
                // Etiqueta de referencia con el rango de fechas de la semana elegida
                if (!$('#NoSemanaRango').length) {
                    $s.after('<small id="NoSemanaRango" class="text-muted d-block" style="margin-top:3px;font-size:11px;"></small>');
                }
                var pintarRango = function () {
                    var y = $anio.length ? (parseInt($anio.val()) || anioActual) : anioActual;
                    var w = parseInt($s.val()) || semActual;
                    $('#NoSemanaRango').text('Semana ' + w + ': ' + rangoSemana(y, w));
                };
                var sincronizar = function () {
                    var y = $anio.length ? (parseInt($anio.val()) || anioActual) : anioActual;
                    var max = (y === anioActual) ? semActual : 53;   // año anterior: todas las semanas
                    var sel = Math.min(semActual, max);
                    llenarSemanas($s, max, sel);
                    pintarRango();
                };
                if ($anio.length && $anio.find('option[value="' + anioActual + '"]').length) {
                    $anio.val(anioActual);
                }
                sincronizar();
                $s.on('change', pintarRango);
                if ($anio.length) $anio.on('change', sincronizar);
            }

            // Prenómina (#pnSemana es numérico): poner la semana actual si está vacío y mostrar el rango
            if ($pn.length) {
                if (!$pn.val()) $pn.val(semActual);
                if (!$('#pnSemanaRango').length) {
                    $pn.after('<small id="pnSemanaRango" class="text-muted d-block" style="margin-top:3px;font-size:11px;"></small>');
                }
                var pintarPn = function () {
                    var w = parseInt($pn.val()) || semActual;
                    $('#pnSemanaRango').text('Semana ' + w + ': ' + rangoSemana(anioActual, w));
                };
                pintarPn();
                $pn.on('change keyup', pintarPn);
            }
        });
    });
})();
