/**
 * Catálogos > Conceptos Microsip (controllers/ConceptosMicrosip.php, tablas dbo.MicrosipConcepto y dbo.TxtConceptoClave).
 */
var CMicro = {
    conceptos: [],
    puedeEditar: false,

    fnEsc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },

    /** "08/10/2026 14:10 (hace 5 min)" */
    fnUltima: function (u) {
        if (!u) return '<span class="cm-error">todavía no se ha actualizado</span>';
        var f = u.fecha.substr(8, 2) + '/' + u.fecha.substr(5, 2) + '/' + u.fecha.substr(0, 4) + ' ' + u.fecha.substr(11, 5);
        var m = Number(u.minutos), hace = m < 1 ? 'hace un momento' : m < 60 ? 'hace ' + m + ' min' : 'hace ' + Math.floor(m / 60) + ' h ' + (m % 60) + ' min';
        return CMicro.fnEsc(f) + ' (' + hace + (u.origen === 'boton' ? ', manual' : '') + ')' +
            (u.error ? ' <span class="cm-error" title="' + CMicro.fnEsc(u.error) + '"><i class="fa fa-exclamation-triangle"></i> no se pudo leer Microsip</span>' : '');
    },

    fnEmpresas: function () {
        $.post('ConceptosMicrosip/empresas', {}, null, 'json').done(function (r) {
            CMicro.puedeEditar = !!r.puedeEditar;
            $('.cm-actualizar').toggle(CMicro.puedeEditar);
            $('.cm-ultima').html(CMicro.fnUltima(r.ultima));
            var html = '<option value="">Selecciona una empresa</option>';
            $.each(r.empresas || [], function (i, e) { html += '<option value="' + Number(e.id) + '">' + CMicro.fnEsc(e.nombre) + '</option>'; });
            $('#cmEmpresa').html(html);
            var guardada = null;
            try { guardada = localStorage.getItem('cmEmpresa'); } catch (e) { }
            if (guardada && $('#cmEmpresa option[value="' + guardada + '"]').length) $('#cmEmpresa').val(guardada).trigger('change');
        });
    },

    fnCargar: function () {
        var emp = $('#cmEmpresa').val();
        $('.cm-requiere-empresa').toggle(!!emp);
        if (!emp) return;
        $('#cmTabla').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
        $.post('ConceptosMicrosip/lista', { empresa_id: emp }, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
            CMicro.conceptos = r.conceptos || [];
            CMicro.txt = r.txt || [];
            $('.cm-ultima').html(CMicro.fnUltima(r.ultima));
            CMicro.fnTxt();
            CMicro.fnTabla();
        });
    },

    /** Renglones del TXT con su clave; con permiso se elige de las percepciones de la empresa. */
    fnTxt: function () {
        var porClave = {};
        $.each(CMicro.conceptos, function (i, c) { if (Number(c.activo) === 1) porClave[c.clave] = c; });
        var percepciones = $.grep(CMicro.conceptos, function (c) { return Number(c.activo) === 1 && c.naturaleza === 'P'; });
        $('#cmTxt').html($.map(CMicro.txt, function (t) {
            var c = porClave[t.clave], estado = c
                ? '<span class="cm-ok"><i class="fa fa-check"></i> ' + CMicro.fnEsc(c.nombre) + '</span>'
                : '<span class="cm-mal"><i class="fa fa-exclamation-triangle"></i> La clave ' + CMicro.fnEsc(t.clave) + ' no existe en el Microsip de esta empresa</span>';
            var origen = t.origen === 'empresa' ? '' : ' <span class="badge badge-secondary" title="No tiene clave propia: usa la general">general</span>';
            var celda;
            if (CMicro.puedeEditar) {
                var opciones = $.map(percepciones, function (p) {
                    return '<option value="' + CMicro.fnEsc(p.clave) + '"' + (p.clave === t.clave ? ' selected' : '') + '>' + CMicro.fnEsc(p.clave + ' · ' + p.nombre) + '</option>';
                }).join('');
                if (!c && t.clave) opciones = '<option value="' + CMicro.fnEsc(t.clave) + '" selected>' + CMicro.fnEsc(t.clave) + ' · (no existe)</option>' + opciones;
                celda = '<select class="form-control form-control-sm cm-clave" data-concepto="' + CMicro.fnEsc(t.concepto) + '">' + opciones + '</select>' +
                    (t.origen === 'empresa' ? ' <a href="#" class="cm-general" data-concepto="' + CMicro.fnEsc(t.concepto) + '" style="font-size:12px;">usar la general</a>' : origen);
            } else {
                celda = '<b>' + CMicro.fnEsc(t.clave) + '</b>' + origen;
            }
            return '<tr><td>' + CMicro.fnEsc(t.nombre) + '</td><td>' + celda + '</td><td>' + estado + '</td></tr>';
        }).join(''));
    },

    fnTabla: function () {
        var q = $.trim($('#cmBuscar').val()).toLowerCase(), nat = $('#cmNaturaleza').val(), bajas = $('#cmVerBajas').is(':checked');
        var usados = {};
        $.each(CMicro.txt || [], function (i, t) { (usados[t.clave] = usados[t.clave] || []).push(t.nombre); });
        var activos = 0, filas = $.grep(CMicro.conceptos, function (c) {
            if (Number(c.activo) === 1) activos++;
            if (!bajas && Number(c.activo) !== 1) return false;
            if (nat && c.naturaleza !== nat) return false;
            return !q || (c.clave + ' ' + c.nombre + ' ' + (c.nombreAbrev || '')).toLowerCase().indexOf(q) >= 0;
        });
        $('.cm-cuenta').text('(' + activos + ' conceptos' + (filas.length !== activos ? ', se ven ' + filas.length : '') + ')');
        $('#cmTabla').html($.map(filas, function (c) {
            var uso = Number(c.activo) === 1 && usados[c.clave] ? '<span class="badge badge-info">' + CMicro.fnEsc(usados[c.clave].join(', ')) + '</span>' : '';
            return '<tr class="' + (Number(c.activo) === 1 ? '' : 'cm-baja') + '"><td><b>' + CMicro.fnEsc(c.clave) + '</b></td><td>' + CMicro.fnEsc(c.nombre) +
                (Number(c.activo) === 1 ? '' : ' <small>(ya no existe en Microsip)</small>') + '</td><td>' +
                (c.naturaleza === 'P' ? 'Percepción' : c.naturaleza === 'R' ? 'Deducción' : CMicro.fnEsc(c.naturaleza)) + '</td><td>' + uso + '</td><td>' +
                CMicro.fnEsc(c.fechaActualiza) + '</td></tr>';
        }).join('') || '<tr><td colspan="5" class="text-center text-muted">Sin conceptos</td></tr>');
    },

    fnGuardarClave: function (concepto, clave) {
        $.post('ConceptosMicrosip/guardarClave', { empresa_id: $('#cmEmpresa').val(), concepto: concepto, clave: clave }, null, 'json').done(function (r) {
            swal(r.exito ? 'Listo' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
            CMicro.fnCargar();
        });
    }
};

$(function () {
    CMicro.fnEmpresas();
    $('#cmEmpresa').on('change', function () {
        try { localStorage.setItem('cmEmpresa', this.value); } catch (e) { }
        CMicro.fnCargar();
    });
    $('#cmBuscar').on('input', CMicro.fnTabla);
    $('#cmNaturaleza, #cmVerBajas').on('change', CMicro.fnTabla);
    $(document).on('change', '#cmicro .cm-clave', function () { CMicro.fnGuardarClave($(this).data('concepto'), this.value); });
    $(document).on('click', '#cmicro .cm-general', function (e) { e.preventDefault(); CMicro.fnGuardarClave($(this).data('concepto'), ''); });
    $('.cm-actualizar').on('click', function () {
        var btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Actualizando…');
        $.post('ConceptosMicrosip/actualizar', { empresa_id: $('#cmEmpresa').val() }, null, 'json').done(function (r) {
            swal(r.exito ? 'Conceptos actualizados' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
        }).fail(function () {
            swal('No se pudo', 'No se pudo conectar con Microsip.', 'error');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-cloud-download"></i> Actualizar ahora');
            if ($('#cmEmpresa').val()) CMicro.fnCargar(); else CMicro.fnEmpresas();
        });
    });
});
