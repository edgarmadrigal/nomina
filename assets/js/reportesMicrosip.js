/**
 * Reportes > Reportes Microsip (controllers/reportesMicrosip.php).
 * Pinta cada reporte agrupado como en el Reporteador de Microsip.
 */
var RMic = {
    ultimo: null,   // parámetros del último reporte generado (para el Excel)
    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnNum: function (n, dec) { return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: dec == null ? 2 : dec, maximumFractionDigits: dec == null ? 2 : dec }); },
    fnFecha: function (f) { if (!f) return ''; var p = String(f).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f; },

    fnEmpresas: function () {
        $.post('reportesMicrosip/empresas', {}, null, 'json').done(function (lista) {
            var html = '<option value="">Elige…</option>';
            $.each(lista || [], function (i, e) { html += '<option value="' + Number(e.id) + '">' + RMic.esc(e.nombre) + '</option>'; });
            $('#rmEmpresa').html(html);
        });
    },

    fnParametros: function () {
        return { tipo: $('#rmTipo').val(), empresa_id: $('#rmEmpresa').val(), fechaInicio: $('#rmFi').val(), fechaFin: $('#rmFf').val() };
    },

    fnGenerar: function () {
        var p = RMic.fnParametros();
        if (!p.empresa_id) { swal('Aviso', 'Elige una empresa.', 'warning'); return; }
        if (!p.fechaInicio || !p.fechaFin) { swal('Aviso', 'Elige el rango de fechas.', 'warning'); return; }
        var btn = $('#rmGenerar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Consultando Microsip…');
        $('#rmImprimir, #rmExcel').prop('disabled', true);
        $('#rmHoja').html('<div class="text-center text-muted">Consultando Microsip… (puede tardar unos segundos)</div>');
        $.post('reportesMicrosip/datos', p, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); $('#rmHoja').html(''); return; }
            RMic.ultimo = p;
            $('#rmHoja').html(RMic.fnEncabezado(r) + RMic['fnPintar_' + r.tipo](r.filas || []));
            $('#rmImprimir, #rmExcel').prop('disabled', false);
        }).fail(function () {
            swal('No se pudo', 'No se pudo generar el reporte (revisa la conexión con Microsip).', 'error');
            $('#rmHoja').html('');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Generar');
        });
    },

    fnEncabezado: function (r) {
        var ahora = new Date();
        return '<div class="rm-enc"><div><h3>' + RMic.esc(r.titulo.toUpperCase()) + '</h3>' +
            '<div class="rm-emp">' + RMic.esc(r.empresa.nombre) + '</div>' +
            '<div class="rm-emp">Pagos del ' + RMic.fnFecha(r.fechaInicio) + ' al ' + RMic.fnFecha(r.fechaFin) + '</div></div>' +
            '<div class="rm-imp">Fecha de impresión: ' + ahora.toLocaleDateString('es-MX') + '<br>Hora de impresión: ' + ahora.toLocaleTimeString('es-MX') + '</div></div>';
    },

    /** Agrupa filas consecutivas por número de empleado. */
    fnGrupos: function (filas, campo) {
        var grupos = [], actual = null;
        $.each(filas, function (i, f) {
            if (!actual || String(actual.num) !== String(f[campo])) { actual = { num: f[campo], filas: [] }; grupos.push(actual); }
            actual.filas.push(f);
        });
        return grupos;
    },

    fnPintar_catalogo: function (filas) {
        if (!filas.length) return '<div class="text-center text-muted">No hay empleados con pago en ese rango.</div>';
        var h = '<table><thead><tr><th>Número</th><th>Nombre</th><th>Gpo</th><th>RFC</th><th>Num. IMSS</th><th>CURP</th>' +
            '<th class="num">Sal. Diario</th><th class="num">Sal. Integrado</th><th class="num">Factor</th><th>Fecha Baja</th><th>Fecha Ing.</th><th>Fecha Antig.</th></tr></thead><tbody>';
        $.each(filas, function (i, f) {
            h += '<tr' + (f.FBAJA ? ' class="rm-baja"' : '') + '><td class="nw">' + RMic.esc(f.EMD_NUM) + '</td><td class="nw">' + RMic.esc(f.EMD_NOMBRE) +
                '</td><td>' + RMic.esc(f.GPO) + '</td><td class="nw">' + RMic.esc(f.RFC) + '</td><td class="nw">' + RMic.esc(f.REG_IMSS) +
                '</td><td class="nw">' + RMic.esc(f.CURP) + '</td><td class="num">' + RMic.fnNum(f.SAL_DIA) + '</td><td class="num">' + RMic.fnNum(f.SAL_INTEG) +
                '</td><td class="num">' + RMic.fnNum(f.POR_INTEG, 5) + '</td><td class="nw">' + RMic.fnFecha(f.FBAJA) + '</td><td class="nw">' + RMic.fnFecha(f.FREINGRESO) +
                '</td><td class="nw">' + RMic.fnFecha(f.EMD_FING) + '</td></tr>';
        });
        return h + '<tr class="rm-tot"><td colspan="12">Total de empleados: ' + filas.length + '</td></tr></tbody></table>';
    },

    fnPintar_fonacot: function (filas) {
        if (!filas.length) return '<div class="text-center text-muted">No hay descuentos de Fonacot en ese rango.</div>';
        var total = 0, empleados = 0;
        var h = '<table><thead><tr><th>Clave</th><th>Concepto</th><th>Empleado</th><th>Nombre</th><th>Número Crédito</th><th>RFC</th><th>Reg. IMSS</th>' +
            '<th class="num">Importe</th><th>Fecha Pago</th></tr></thead><tbody>';
        $.each(RMic.fnGrupos(filas, 'EMD_NUMERO'), function (i, g) {
            var sub = 0;
            empleados++;
            $.each(g.filas, function (j, f) {
                sub += Number(f.PAG_IMPORTE);
                h += '<tr><td>' + RMic.esc(f.CON_CLAVE) + '</td><td class="nw">' + RMic.esc(f.CON_NOM) + '</td><td>' + RMic.esc(f.EMD_NUMERO) +
                    '</td><td class="nw">' + RMic.esc(f.EMD_NOM) + '</td><td>' + RMic.esc(f.NUMERO_CREDITO) + '</td><td class="nw">' + RMic.esc(f.RFC) +
                    '</td><td class="nw">' + RMic.esc(f.REG_IMSS) + '</td><td class="num">' + RMic.fnNum(f.PAG_IMPORTE) + '</td><td class="nw">' + RMic.fnFecha(f.PAG_FECHA) + '</td></tr>';
            });
            total += sub;
            var f0 = g.filas[0];
            h += '<tr class="rm-sub"><td colspan="2"></td><td>' + RMic.esc(f0.EMD_NUMERO) + '</td><td class="nw">' + RMic.esc(f0.EMD_NOM) +
                '</td><td colspan="3">Total FONACOT</td><td class="num">' + RMic.fnNum(sub) + '</td><td class="nw">Nº de Pagos: ' + g.filas.length + '</td></tr>';
        });
        return h + '<tr class="rm-tot"><td colspan="7">Total FONACOT (' + empleados + ' empleados)</td><td class="num">' + RMic.fnNum(total) +
            '</td><td>Nº de Pagos: ' + filas.length + '</td></tr></tbody></table>';
    },

    fnPintar_infonavit: function (filas) {
        if (!filas.length) return '<div class="text-center text-muted">No hay descuentos de Infonavit en ese rango.</div>';
        var total = 0, empleados = 0;
        var h = '<table><thead><tr><th>Empleado</th><th>Nombre</th><th>NSS</th><th>Clave</th><th>Concepto</th><th class="num">Sal. Integ.</th>' +
            '<th class="num">Importe</th><th class="num">Faltas</th><th class="num">Días Per.</th><th>Fecha Pago</th></tr></thead><tbody>';
        $.each(RMic.fnGrupos(filas, 'EMD_NUMERO'), function (i, g) {
            var sub = 0, f0 = g.filas[0];
            empleados++;
            h += '<tr class="rm-emp"><td>' + RMic.esc(f0.EMD_NUMERO) + '</td><td class="nw">' + RMic.esc(f0.EMD_NOM) + '</td><td class="nw">' + RMic.esc(f0.EMD_IMSS) +
                '</td><td colspan="2"></td><td class="num">' + RMic.fnNum(f0.EMD_SDINT) + '</td><td colspan="4"></td></tr>';
            $.each(g.filas, function (j, f) {
                sub += Number(f.PAG_IMPORTE);
                h += '<tr><td colspan="3"></td><td>' + RMic.esc(f.CON_CLAVE) + '</td><td class="nw">' + RMic.esc(f.CON_NOM) + '</td><td></td>' +
                    '<td class="num">' + RMic.fnNum(f.PAG_IMPORTE) + '</td><td class="num">' + Number(f.FALTAS || 0) + '</td><td class="num">' + RMic.fnNum(f.DIAS_PERIODO, 0) +
                    '</td><td class="nw">' + RMic.fnFecha(f.PAG_FECHA) + '</td></tr>';
            });
            total += sub;
            h += '<tr class="rm-sub"><td colspan="6"></td><td class="num">' + RMic.fnNum(sub) + '</td><td colspan="3">NumPagos: ' + g.filas.length + '</td></tr>';
        });
        return h + '<tr class="rm-tot"><td colspan="6">Total Infonavit (' + empleados + ' empleados)</td><td class="num">' + RMic.fnNum(total) +
            '</td><td colspan="3">NumPagos: ' + filas.length + '</td></tr></tbody></table>';
    }
};

$(function () {
    // por omisión: el mes anterior completo
    var hoy = new Date(), ini = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1), fin = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
    var iso = function (d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
    $('#rmFi').val(iso(ini));
    $('#rmFf').val(iso(fin));
    RMic.fnEmpresas();
    $('#rmGenerar').on('click', RMic.fnGenerar);
    $('#rmTipo, #rmEmpresa, #rmFi, #rmFf').on('change', function () { $('#rmImprimir, #rmExcel').prop('disabled', true); });
    $('#rmImprimir').on('click', function () { window.print(); });
    $('#rmExcel').on('click', function () {
        if (!RMic.ultimo) return;
        window.location = 'reportesMicrosip/excel?' + $.param(RMic.ultimo);
    });
});
