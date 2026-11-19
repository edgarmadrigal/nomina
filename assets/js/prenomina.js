/**
 * Prenómina (controllers/Prenomina.php). Calcula por planta y semana el pago de cada empleado
 * activo a partir de lo que el sistema ya procesó. Fase 1.
 */
var Prenom = {
    tabla: null,
    cols: null,
    editables: {},
    enteros: ['Retardos', 'Faltas'],
    horas: ['TE_Total'],
    texto: ['Numero', 'Nombre', 'Puesto'],

    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    money: function (n) { return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

    fnPlantas: function () {
        $.post('prenomina/plantas', {}, null, 'json').done(function (lista) {
            var html = '<option value="">Selecciona</option>';
            $.each(lista || [], function (i, e) { html += '<option value="' + Number(e.id) + '">' + Prenom.esc(e.nombre) + '</option>'; });
            $('#pnPlanta').html(html);
            try { var g = localStorage.getItem('pnPlanta'); if (g && $('#pnPlanta option[value="' + g + '"]').length) $('#pnPlanta').val(g); } catch (e) {}
        });
    },

    fnCalcular: function () {
        if (!$('#pnPlanta').val()) { swal('Aviso', 'Elige una planta.', 'warning'); return; }
        var btn = $('#pnCalcular').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Calculando…');
        $('#pnResumen').hide(); $('#pnAviso').hide();
        $.post('prenomina/calcular', {
            planta: $('#pnPlanta').val(), semana: $('#pnSemana').val(), anio: $('#pnAnio').val()
        }, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
            Prenom.cols = r.columnas;
            Prenom.editables = r.editables || {};
            Prenom.fnPintar(r.filas || []);
            var avisos = [];
            if (r.sinProcesar > 0) avisos.push('<i class="fa fa-exclamation-triangle"></i> ' + r.sinProcesar + ' empleado(s) activos no tienen la semana procesada (sin checadas). Procesa la semana en Checadas para incluir sus faltas.');
            if (r.microsip === 0) avisos.push('<i class="fa fa-info-circle"></i> La nómina de esta semana aún no está en Microsip. La prima vacacional, fondo de ahorro, prima dominical, festivo y defunción se llenarán solos cuando esa nómina se corra en Microsip (vuelve a dar Calcular).');
            else if (r.microsip > 0) avisos.push('<i class="fa fa-check-circle text-success"></i> Se trajeron de Microsip los conceptos (prima vac., fondo ahorro, prima dom., festivo, defunción) de ' + r.microsip + ' empleado(s).');
            if (avisos.length) $('#pnAviso').html(avisos.join('<br>')).show();
        }).fail(function () {
            swal('No se pudo', 'No se pudo calcular la prenómina.', 'error');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Calcular');
        });
    },

    fnPintar: function (filas) {
        var keys = [];
        var head = '';
        $.each(Prenom.cols, function (k, titulo) { keys.push(k); head += '<th>' + Prenom.esc(titulo) + '</th>'; });
        $('#pnHead').html(head);

        var totPagar = 0, totFaltas = 0, totRet = 0;
        var cuerpo = $.map(filas, function (f) {
            totPagar += Number(f.TotalAPagar || 0); totFaltas += Number(f.Faltas || 0); totRet += Number(f.Retardos || 0);
            var emp = $.trim(String(f.Numero));
            var tds = $.map(keys, function (k) {
                var v = f[k], clase = '';
                if (Prenom.texto.indexOf(k) >= 0) return '<td class="pn-txt" title="' + Prenom.esc(v) + '">' + Prenom.esc(v) + '</td>';
                if (Prenom.enteros.indexOf(k) >= 0) return '<td class="text-right">' + Number(v || 0) + '</td>';
                if (Prenom.editables[k]) {   // concepto capturable: input chico
                    return '<td class="pn-edcell"><input type="number" step="0.01" class="pn-edit" data-emp="' + Prenom.esc(emp) +
                        '" data-campo="' + Prenom.editables[k] + '" value="' + (Number(v) ? Number(v) : '') + '"></td>';
                }
                if (Prenom.horas.indexOf(k) >= 0) return '<td class="text-right">' + Number(v || 0).toFixed(2) + '</td>';
                if (Number(v) < 0) clase = ' pn-neg';
                if (k === 'TotalAPagar') clase += ' pn-total';
                return '<td class="text-right' + clase + ' pn-' + k + '">' + Prenom.money(v) + '</td>';
            }).join('');
            return '<tr data-emp="' + Prenom.esc(emp) + '" data-total="' + Number(f.TotalAPagar || 0) + '">' + tds + '</tr>';
        }).join('');

        if (Prenom.tabla) { Prenom.tabla.destroy(); }
        $('#pnTabla tbody').html(cuerpo || '<tr><td colspan="' + keys.length + '" class="text-center text-muted">No hay empleados activos para esa planta.</td></tr>');
        Prenom.tabla = $('#pnTabla').DataTable({
            bDestroy: true, order: [], paging: false, scrollX: false,
            dom: '<"row"<"col-sm-12 text-right"f>>rti',
            language: { sInfo: '_TOTAL_ empleados', sInfoEmpty: 'Sin datos', sInfoFiltered: '(de _MAX_)',
                sSearch: 'Filtrar:', sZeroRecords: 'Sin resultados' }
        });
        $('#pnResumen').html(
            Prenom.fnKpi('Empleados', filas.length, 'secondary') +
            Prenom.fnKpi('Total a pagar', '$' + Prenom.money(totPagar), 'success', 'pn-kpi-total') +
            Prenom.fnKpi('Faltas', totFaltas, 'danger') +
            Prenom.fnKpi('Retardos', totRet, 'warning')
        ).show();
    },
    fnKpi: function (t, v, c, extra) {
        return '<div class="col-md-3 col-6"><div class="card pn-kpi ' + (extra || '') + ' border-' + c + '"><div class="card-body"><small>' + t + '</small><b class="text-' + c + '">' + v + '</b></div></div></div>';
    },

    /** Guarda un concepto editado y actualiza el total de la fila y el KPI. */
    fnGuardarCaptura: function (inp) {
        var $i = $(inp), $tr = $i.closest('tr');
        $i.prop('disabled', true);
        $.post('prenomina/guardarCaptura', {
            planta: $('#pnPlanta').val(), semana: $('#pnSemana').val(), anio: $('#pnAnio').val(),
            empleado: $i.data('emp'), campo: $i.data('campo'), monto: $i.val()
        }, null, 'json').done(function (r) {
            if (r && r.exito) {
                $tr.attr('data-total', Number(r.total || 0));
                $tr.find('.pn-TotalAPagar').text(Prenom.money(r.total));
                var mr = $tr.find('.pn-MontoRetardos');
                if (mr.length) mr.text(Prenom.money(r.montoRetardos)).toggleClass('pn-neg', Number(r.montoRetardos) < 0);
                var df = $tr.find('.pn-Diferencia');
                if (df.length && r.diferencia != null) df.text(Prenom.money(r.diferencia)).toggleClass('pn-neg', Number(r.diferencia) < 0);
                Prenom.fnKpiTotal();
                $i.addClass('pn-ok'); setTimeout(function () { $i.removeClass('pn-ok'); }, 800);
            } else { swal('No se pudo', (r && r.mensaje) || 'No se guardó.', 'warning'); }
        }).fail(function () { swal('No se pudo', 'Error de conexión.', 'error'); })
          .always(function () { $i.prop('disabled', false); });
    },
    fnKpiTotal: function () {
        var t = 0; $('#pnTabla tbody tr').each(function () { t += Number($(this).attr('data-total') || 0); });
        $('#pnResumen .pn-kpi-total b').text(Prenom.money(t));
    },

    fnExcel: function () {
        if (!$('#pnPlanta').val()) { swal('Aviso', 'Elige una planta.', 'warning'); return; }
        var url = 'prenomina/excel?planta=' + encodeURIComponent($('#pnPlanta').val()) +
                  '&semana=' + encodeURIComponent($('#pnSemana').val()) + '&anio=' + encodeURIComponent($('#pnAnio').val());
        $('#pnIframe').remove();
        $('<iframe id="pnIframe" style="display:none"></iframe>').attr('src', url).appendTo('body');
    },

    fnAbrirConfig: function () {
        if (!$('#pnPlanta').val()) { swal('Aviso', 'Elige una planta primero.', 'warning'); return; }
        $.post('prenomina/config', { planta: $('#pnPlanta').val() }, null, 'json').done(function (r) {
            var c = (r && r.config) || {};
            $('#cfgPremio').val(c.premio); $('#cfgFactorFalta').val(c.factorFalta);
            $('#cfgTeLV').val(c.tarifaTE_LV); $('#cfgTeSab').val(c.tarifaTE_Sab); $('#cfgTeDom').val(c.tarifaTE_Dom);
            $('#cfgDias').val(c.diasSemana); $('#cfgTope').val(c.topeTEDoble);
            $('#pnModalConfig').modal('show');
        });
    },
    fnGuardarConfig: function () {
        var btn = $('#pnGuardarConfig').prop('disabled', true);
        $.post('prenomina/guardarConfig', {
            planta: $('#pnPlanta').val(), premio: $('#cfgPremio').val(), factorFalta: $('#cfgFactorFalta').val(),
            tarifaTE_LV: $('#cfgTeLV').val(), tarifaTE_Sab: $('#cfgTeSab').val(), tarifaTE_Dom: $('#cfgTeDom').val(),
            diasSemana: $('#cfgDias').val(), topeTEDoble: $('#cfgTope').val()
        }, null, 'json').done(function (r) {
            swal(r.exito ? 'Listo' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
            if (r.exito) { $('#pnModalConfig').modal('hide'); }
        }).always(function () { btn.prop('disabled', false); });
    }
};

$(function () {
    // semana ISO actual aproximada y años
    var hoy = new Date(), y = hoy.getFullYear();
    var op = ''; for (var a = y; a >= 2012; a--) op += '<option value="' + a + '">' + a + '</option>';
    $('#pnAnio').html(op).val(y);
    var onejan = new Date(y, 0, 1);
    var sem = Math.ceil((((hoy - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    $('#pnSemana').val(sem);

    Prenom.fnPlantas();
    $('#pnPlanta').on('change', function () { try { localStorage.setItem('pnPlanta', this.value); } catch (e) {} });
    $('#pnCalcular').on('click', Prenom.fnCalcular);
    $('#pnExcel').on('click', Prenom.fnExcel);
    $('#pnConfig').on('click', Prenom.fnAbrirConfig);
    $('#pnGuardarConfig').on('click', Prenom.fnGuardarConfig);
    // Importar producción desde el Excel de la planta
    $('#pnImportarProd').on('click', function () {
        if (!$('#pnPlanta').val()) { swal('Aviso', 'Elige planta, semana y año primero.', 'warning'); return; }
        $('#pnArchivoProd').val('').click();
    });
    $('#pnArchivoProd').on('change', function () {
        if (!this.files || !this.files.length) return;
        var fd = new FormData();
        fd.append('archivo', this.files[0]);
        fd.append('planta', $('#pnPlanta').val()); fd.append('semana', $('#pnSemana').val()); fd.append('anio', $('#pnAnio').val());
        var btn = $('#pnImportarProd').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Importando…');
        $.ajax({ url: 'prenomina/importarProduccion', type: 'post', data: fd, processData: false, contentType: false, dataType: 'json' })
            .done(function (r) {
                swal(r.exito ? 'Listo' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
                if (r.exito) Prenom.fnCalcular();
            })
            .fail(function () { swal('No se pudo', 'No se pudo importar el archivo.', 'error'); })
            .always(function () { btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Importar producción'); });
    });
    // Guardar un concepto al salir del campo o al presionar Enter
    $(document).on('change', '#pnTabla .pn-edit', function () { Prenom.fnGuardarCaptura(this); });
    $(document).on('keydown', '#pnTabla .pn-edit', function (e) { if (e.key === 'Enter') { e.preventDefault(); this.blur(); } });
});
