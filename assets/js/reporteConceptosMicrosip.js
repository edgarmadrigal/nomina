/**
 * Reportes > Acumulado de Conceptos Microsip (controllers/reporteConceptosMicrosip.php).
 */
var RAcum = {
    tabla: null,
    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnMoneda: function (n) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    fnNum: function (n) { return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

    fnEmpresas: function () {
        $.post('reporteConceptosMicrosip/empresas', {}, null, 'json').done(function (lista) {
            var html = '<option value="">Todas las empresas</option>';
            $.each(lista || [], function (i, e) { html += '<option value="' + Number(e.id) + '">' + RAcum.esc(e.nombre) + '</option>'; });
            $('#acEmpresa').html(html);
        });
    },

    fnGenerar: function () {
        var btn = $('#acGenerar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generando…');
        $('#acResumen').hide();
        if (!$('#acEmpresa').val()) $('#acTabla tbody').html('<tr><td colspan="7" class="text-center text-muted">Consultando las empresas de Microsip… (puede tardar unos segundos)</td></tr>');
        var anio = $('#acAnio').val();
        $.post('reporteConceptosMicrosip/acumulado', {
            empresa_id: $('#acEmpresa').val(), fechaInicio: anio + '-01-01', fechaFin: anio + '-12-31'
        }, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
            RAcum.fnPintar(r.filas || [], r.esTodas);
        }).fail(function () {
            swal('No se pudo', 'No se pudo generar el reporte (revisa la conexión con Microsip).', 'error');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Generar');
        });
    },

    fnPintar: function (filas, esTodas) {
        $('#acTabla .ac-col-emp').toggle(!!esTodas);
        var totP = 0, totD = 0;
        var cuerpo = $.map(filas, function (f) {
            if (f.naturaleza === 'P') totP += Number(f.importe); else if (f.naturaleza === 'R') totD += Number(f.importe);
            var tipo = f.naturaleza === 'P' ? '<span class="badge badge-success">Percepción</span>'
                : f.naturaleza === 'R' ? '<span class="badge badge-danger">Deducción</span>' : RAcum.esc(f.naturaleza);
            return '<tr><td>' + RAcum.esc(f.clave) + '</td><td>' + RAcum.esc(f.nombre) + '</td><td>' + tipo +
                '</td><td class="text-right">' + RAcum.fnNum(f.unidades) + '</td><td class="text-right">' + Number(f.movimientos).toLocaleString('es-MX') +
                '</td><td class="text-right">' + RAcum.fnMoneda(f.importe) + '</td>' +
                '<td class="text-right ac-col-emp"' + (esTodas ? '' : ' style="display:none;"') + '>' + Number(f.empresas) + '</td></tr>';
        }).join('');
        if (RAcum.tabla) { RAcum.tabla.destroy(); }
        $('#acTabla tbody').html(cuerpo || '<tr><td colspan="7" class="text-center text-muted">No se encontraron conceptos en ese rango.</td></tr>');
        RAcum.tabla = $('#acTabla').DataTable({
            bDestroy: true, order: [], pageLength: 25, scrollX: false,
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"B>>rtip',
            buttons: [
                { extend: 'copyHtml5', text: 'Copiar' },
                { extend: 'excelHtml5', text: 'Excel', title: 'Acumulado conceptos Microsip' },
                { extend: 'csvHtml5', text: 'CSV', title: 'Acumulado conceptos Microsip' },
                { extend: 'pdfHtml5', text: 'PDF', title: 'Acumulado conceptos Microsip', orientation: 'landscape', pageSize: 'LETTER' }
            ],
            language: { sLengthMenu: '_MENU_ por página', sInfo: 'Del _START_ al _END_ de _TOTAL_', sInfoEmpty: 'Sin datos',
                sInfoFiltered: '(de _MAX_)', sSearch: 'Filtrar:', sZeroRecords: 'No se encontraron conceptos',
                oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' } }
        });
        $('#acResumen').html(
            RAcum.fnKpi('Percepciones', RAcum.fnMoneda(totP), 'success') +
            RAcum.fnKpi('Deducciones', RAcum.fnMoneda(totD), 'danger') +
            RAcum.fnKpi('Neto', RAcum.fnMoneda(totP - totD), 'info') +
            RAcum.fnKpi('Conceptos', filas.length, 'secondary')
        ).show();
    },
    fnKpi: function (titulo, valor, color) {
        return '<div class="col-md-3 col-6"><div class="card ac-kpi border-' + color + '"><div class="card-body">' +
            '<small>' + titulo + '</small><b class="text-' + color + '">' + valor + '</b></div></div></div>';
    }
};

$(function () {
    var y = new Date().getFullYear(), op = '';
    for (var a = y; a >= 2012; a--) op += '<option value="' + a + '">' + a + '</option>';   // la nómina en Microsip arranca en 2012
    $('#acAnio').html(op).val(y);
    $('.ac-col-emp').hide();
    RAcum.fnEmpresas();
    $('#acGenerar').on('click', RAcum.fnGenerar);

    // Excel con una hoja por empresa (detalle por empleado) del año elegido
    $('#acExcelDetalle').on('click', function () {
        var btn = $(this);
        if (btn.prop('disabled')) return;
        var anio = $('#acAnio').val();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generando Excel… (~1 min)');
        // descarga por iframe oculto para no perder la página; se reactiva el botón al terminar o a los 3 min
        $('#acIframeDescarga').remove();
        $('<iframe id="acIframeDescarga" style="display:none"></iframe>')
            .attr('src', 'reporteConceptosMicrosip/excelDetalle?anio=' + encodeURIComponent(anio))
            .appendTo('body');
        var fin = function () { btn.prop('disabled', false).html('<i class="fa fa-file-excel-o"></i> Excel detalle por empresa'); };
        $('#acIframeDescarga').on('load', fin);
        setTimeout(fin, 180000);
    });
});
