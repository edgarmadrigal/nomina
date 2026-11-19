/**
 * Importar/Exportar > Modifica excepciones Microsip (controllers/modificaExcepciones.php).
 * Vista previa del Excel contra las excepciones de la nómina; cada cuota que cambia o se agrega trae una
 * casilla (marcada) para escoger qué se aplica (sustituye a "preguntar cada que haya una coincidencia").
 */
var MExc = {
    previa: null,   // última vista previa (token, columnas, filas, resumen)

    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnNum: function (n) {
        if (n === null || n === undefined || n === '') return '';
        return Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 6 });
    },
    fnFecha: function (f) { if (!f) return ''; var p = String(f).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f; },

    fnEmpresas: function () {
        $.post('modificaExcepciones/empresas', {}, null, 'json').done(function (lista) {
            var html = '<option value="">Elige la empresa</option>';
            $.each(lista || [], function (i, e) { html += '<option value="' + Number(e.id) + '">' + MExc.esc(e.nombre) + '</option>'; });
            $('#mxEmpresa').html(html);
            try {
                var prev = window.localStorage.getItem('mexc.empresa');
                if (prev && $('#mxEmpresa option[value="' + Number(prev) + '"]').length) $('#mxEmpresa').val(Number(prev)).trigger('change');
            } catch (e) { }
        });
    },

    fnNominas: function () {
        var emp = $('#mxEmpresa').val();
        MExc.fnLimpiar();
        var sel = $('#mxNomina').prop('disabled', true);
        if (!emp) { sel.html('<option value="">Elige la empresa</option>'); return; }
        try { window.localStorage.setItem('mexc.empresa', emp); } catch (e) { }
        sel.html('<option value="">Consultando Microsip…</option>');
        $.post('modificaExcepciones/nominas', { empresa_id: emp }, null, 'json').done(function (r) {
            if (!r.exito) { sel.html('<option value="">' + MExc.esc(r.mensaje) + '</option>'); return; }
            var html = '<option value="">Elige la nómina</option>', primera = null;
            $.each(r.nominas, function (i, n) {
                var txt = MExc.fnFecha(n.fecha) + ' · ' + n.tipoNombre + ' · ' + n.excepciones + ' excepciones' + (n.aplicado ? ' · APLICADA' : '');
                html += '<option value="' + Number(n.id) + '"' + (n.aplicado ? ' data-aplicada="1"' : '') + '>' + MExc.esc(txt) + '</option>';
                if (primera === null && !n.aplicado && n.excepciones > 0) primera = n.id;
            });
            sel.html(html).prop('disabled', false);
            if (primera !== null) sel.val(primera);
        }).fail(function () { sel.html('<option value="">No respondió Microsip</option>'); });
    },

    fnLimpiar: function () {
        MExc.previa = null;
        $('#mxAplicar, #mxExcel').prop('disabled', true);
        $('#mxHoja').html('<div class="text-center text-muted">Elige la empresa, la nómina y el Excel, y da <b>Vista previa</b>.</div>');
    },

    fnPrevia: function () {
        var emp = $('#mxEmpresa').val(), nom = $('#mxNomina').val(), arch = $('#mxArchivo')[0].files[0];
        if (!emp) { swal('Aviso', 'Elige la empresa.', 'warning'); return; }
        if (!nom) { swal('Aviso', 'Elige la nómina.', 'warning'); return; }
        if (!arch) { swal('Aviso', 'Elige el archivo de Excel.', 'warning'); return; }
        var fd = new FormData();
        fd.append('empresa_id', emp); fd.append('nomina_id', nom); fd.append('archivo', arch);
        var btn = $('#mxPrevia').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Comparando con Microsip…');
        $('#mxAplicar, #mxExcel').prop('disabled', true);
        $.ajax({ url: 'modificaExcepciones/previsualizar', type: 'post', data: fd, processData: false, contentType: false, dataType: 'json' }).done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); MExc.fnLimpiar(); return; }
            MExc.previa = r;
            MExc.fnPintar();
            $('#mxExcel').prop('disabled', false);
        }).fail(function () {
            swal('No se pudo', 'No se pudo leer el Excel o no respondió Microsip.', 'error');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-eye"></i> Vista previa');
        });
    },

    fnPintar: function () {
        var r = MExc.previa, s = r.resumen, n = r.nomina, html = '';
        if (n.aplicado) html += '<div class="mx-aviso"><b>La nómina ya está aplicada en Microsip:</b> se puede ver la comparación pero no se le pueden modificar las excepciones.</div>';
        html += '<div class="mx-res">'
             + '<span>Nómina <b>' + MExc.esc(MExc.fnFecha(n.fecha)) + '</b> · ' + MExc.esc(n.tipoNombre) + '</span>'
             + '<span><b>' + s.actualizar + '</b> cuotas cambian</span>'
             + '<span><b>' + s.agregar + '</b> conceptos se agregan</span>'
             + '<span><b>' + s.igual + '</b> ya tenían ese valor</span>'
             + '<span><b>' + s.sinExcepcion + '</b> empleados sin excepción (se ignoran)</span>'
             + (s.noExiste ? '<span class="text-danger"><b>' + s.noExiste + '</b> números no existen en Microsip</span>' : '')
             + '</div>';
        if (r.avisos && r.avisos.length) html += '<div class="mx-aviso">' + $.map(r.avisos, MExc.esc).join('<br>') + '</div>';
        html += '<div class="mx-leyenda mb-2" style="font-size:12px;"><span class="mx-U" style="background:#c8ffc8">cambia</span><span style="background:#c8e1ff">se agrega</span><span style="background:#e6e6e6">igual</span> Quita la palomita a lo que no quieras aplicar.</div>';

        html += '<table><thead><tr><th>Empleado</th><th style="text-align:left">Nombre</th>';
        $.each(r.columnas, function (j, c) {
            html += '<th><label class="mb-0" style="font-weight:bold;cursor:pointer;"><input type="checkbox" class="mx-col" data-c="' + Number(c.id) + '" checked> ' + MExc.esc(c.nombre) + '</label><span class="mx-clv">' + MExc.esc(c.clave) + (c.nat === 'R' ? ' · deducción' : c.nat === 'P' ? ' · percepción' : '') + '</span></th>';
        });
        html += '</tr></thead><tbody>';
        $.each(r.filas, function (i, f) {
            var est = f.estado === 'I' ? ' <small>(sin excepción)</small>' : f.estado === 'X' ? ' <small>(no existe)</small>' : '';
            html += '<tr class="mx-est-' + f.estado + '" data-n="' + Number(f.numero) + '"><td>' + Number(f.numero) + '</td><td class="mx-nom">' + MExc.esc(f.nombre) + est + '</td>';
            $.each(r.columnas, function (j, c) {
                var x = f.celdas[c.id];
                if (!x || (!x.nuevo && x.actual === null)) { html += '<td></td>'; return; }
                if (x.accion === 'U' || x.accion === 'I') {
                    var ant = x.accion === 'U' ? '<span class="mx-ant">antes ' + MExc.fnNum(x.actual) + (x.inactivo ? ' (inactivo)' : '') + '</span>' : '<span class="mx-ant">nuevo concepto</span>';
                    html += '<td class="num mx-' + x.accion + '" data-k="' + MExc.esc(x.k) + '" data-c="' + Number(c.id) + '"><label class="mb-0" style="cursor:pointer;font-weight:normal;"><input type="checkbox" class="mx-k" value="' + MExc.esc(x.k) + '" checked' + (n.aplicado ? ' disabled' : '') + '>' + MExc.fnNum(x.nuevo) + '</label>' + ant + '</td>';
                } else if (x.accion === '=') {
                    html += '<td class="num mx-eq">' + MExc.fnNum(x.nuevo) + '</td>';
                } else {
                    html += '<td class="num">' + (x.nuevo ? MExc.fnNum(x.nuevo) : '') + '</td>';
                }
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        $('#mxHoja').html(html);
        MExc.fnContar();
    },

    fnMarcados: function () { return $('#mxHoja .mx-k:checked:not(:disabled)').map(function () { return this.value; }).get(); },

    fnContar: function () {
        $('#mxHoja td[data-k]').each(function () { $(this).toggleClass('mx-off', !$(this).find('.mx-k').is(':checked')); });
        var n = MExc.fnMarcados().length;
        $('#mxAplicar').prop('disabled', !n || !MExc.previa || MExc.previa.nomina.aplicado)
            .html('<i class="fa fa-check"></i> Aplicar en Microsip' + (n ? ' (' + n + ')' : ''));
    },

    fnAplicar: function () {
        var claves = MExc.fnMarcados(), r = MExc.previa;
        if (!r || !claves.length) return;
        var emp = $('#mxEmpresa option:selected').text();
        swal({
            title: '¿Aplicar en Microsip?',
            text: 'Se modificarán ' + claves.length + ' cuota(s) en las excepciones de la nómina del ' + MExc.fnFecha(r.nomina.fecha) + ' (' + r.nomina.tipoNombre + ') de ' + emp + '. Esto escribe directo en Microsip y queda registrado a tu nombre.',
            icon: 'warning', buttons: ['Cancelar', 'Sí, aplicar'], dangerMode: true
        }).then(function (ok) {
            if (!ok) return;
            var btn = $('#mxAplicar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando en Microsip…');
            $('#mxPrevia').prop('disabled', true);
            $.post('modificaExcepciones/aplicar', { token: r.token, claves: claves.join(',') }, null, 'json').done(function (x) {
                $.each(x.aplicados || [], function (i, k) {
                    var td = $('#mxHoja td[data-k="' + k + '"]');
                    td.find('.mx-k').prop('disabled', true);
                    td.closest('tr').addClass('mx-ok');
                    td.find('.mx-ant').append(' <i class="fa fa-check text-success"></i>');
                });
                if (x.exito) {
                    swal('Listo', 'Se guardaron en Microsip ' + x.actualizados + ' cuota(s) cambiadas y ' + x.agregados + ' concepto(s) agregados.', 'success');
                    MExc.previa.nomina.aplicado = true;   // el token ya no sirve: para otra corrida hay que volver a cargar
                    $('#mxHoja .mx-k').prop('disabled', true);
                } else {
                    swal('No se pudo', x.mensaje, 'warning');
                }
            }).fail(function () {
                swal('No se pudo', 'Error de conexión. Vuelve a dar Vista previa para ver qué quedó guardado.', 'error');
            }).always(function () {
                $('#mxPrevia').prop('disabled', false);
                MExc.fnContar();
            });
        });
    },

    fnExcel: function () {
        if (MExc.previa) window.location = 'modificaExcepciones/excel?token=' + encodeURIComponent(MExc.previa.token);
    }
};

$(function () {
    MExc.fnEmpresas();
    $('#mxEmpresa').on('change', MExc.fnNominas);
    $('#mxNomina, #mxArchivo').on('change', MExc.fnLimpiar);
    $('#mxPrevia').on('click', MExc.fnPrevia);
    $('#mxAplicar').on('click', MExc.fnAplicar);
    $('#mxExcel').on('click', MExc.fnExcel);
    $('#mxHoja').on('change', '.mx-k', MExc.fnContar);
    $('#mxHoja').on('change', '.mx-col', function () {
        $('#mxHoja td[data-c="' + $(this).data('c') + '"] .mx-k:not(:disabled)').prop('checked', this.checked);
        MExc.fnContar();
    });
});
