/**
 * Catálogos > Salarios (controllers/Salarios.php, tabla dbo.salarioPorAnio).
 */
var Salarios = {
    lista: [],
    puedeEditar: false,
    anioSel: null,      // año elegido en la gráfica

    fnEsc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnMoneda: function (n) { return '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

    // ---------------------------------------------------------------- importe con letra
    fnLetra: function (importe) {
        var unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE',
                        'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUN', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO',
                        'VEINTICINCO', 'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
        var decenas = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        var centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
        var menorMil = function (n) {
            if (n === 0) return '';
            if (n === 100) return 'CIEN';
            var c = Math.floor(n / 100), r = n % 100, t = centenas[c];
            if (r) t += (t ? ' ' : '') + (r < 30 ? unidades[r] : decenas[Math.floor(r / 10)] + (r % 10 ? ' Y ' + unidades[r % 10] : ''));
            return t;
        };
        var entero = Math.floor(Math.round(importe * 100) / 100), cent = Math.round(importe * 100) % 100;
        var millones = Math.floor(entero / 1000000), miles = Math.floor((entero % 1000000) / 1000), resto = entero % 1000, partes = [];
        if (millones) partes.push(millones === 1 ? 'UN MILLON' : menorMil(millones) + ' MILLONES');
        if (miles) partes.push(miles === 1 ? 'MIL' : menorMil(miles) + ' MIL');
        if (resto) partes.push(menorMil(resto));
        var texto = partes.length ? partes.join(' ') : 'CERO';
        if (millones && !miles && !resto) texto += ' DE';
        return '(' + texto + (entero === 1 ? ' PESO ' : ' PESOS ') + ('0' + cent).slice(-2) + '/100 M.N.)';
    },

    // ---------------------------------------------------------------- datos
    fnCargar: function () {
        $.post('salarios/lista', {}, null, 'json').done(function (r) {
            Salarios.lista = r.salarios || [];
            Salarios.puedeEditar = !!r.puedeEditar;
            $('.sal-editar-btn').toggle(Salarios.puedeEditar);
            Salarios.fnResumen();
            Salarios.fnGrafica();
            Salarios.fnTabla();
        });
    },

    /** Activos agrupados por año: {anio: [salarios...]} */
    fnPorAnio: function () {
        var g = {};
        $.each(Salarios.lista, function (i, s) { if (s.activo) (g[s.anio] = g[s.anio] || []).push(s); });
        return g;
    },

    /** Aumento del salario máximo del año contra el máximo del año anterior que tenga datos. */
    fnAumento: function (anio, g) {
        var anios = $.map(Object.keys(g), Number).sort(function (a, b) { return a - b; }), i = anios.indexOf(anio);
        if (i <= 0) return null;
        var max = function (a) { return Math.max.apply(null, $.map(g[a], function (s) { return s.salario; })); };
        return { pct: (max(anio) / max(anios[i - 1]) - 1) * 100, anterior: anios[i - 1] };
    },

    fnResumen: function () {
        var g = Salarios.fnPorAnio(), anios = $.map(Object.keys(g), Number).sort(function (a, b) { return b - a; });
        if (!anios.length) { $('#salResumen').empty(); return; }
        var ult = anios[0], salarios = $.map(g[ult], function (s) { return s.salario; }), aum = Salarios.fnAumento(ult, g);
        var kpi = function (titulo, valor, extra) {
            return '<div class="col-6 col-md-3"><div class="card sal-kpi"><div class="card-body"><small>' + titulo + '</small><b>' + valor + '</b>' + (extra || '') + '</div></div></div>';
        };
        $('#salResumen').html(
            kpi('Año más reciente', ult) +
            kpi('Salarios ' + ult, salarios.length, '<div style="font-size:12px">' + $.map(salarios, Salarios.fnMoneda).join(' · ') + '</div>') +
            kpi('Salario más alto ' + ult, Salarios.fnMoneda(Math.max.apply(null, salarios))) +
            kpi('Aumento vs ' + (aum ? aum.anterior : 'año anterior'), aum ? aum.pct.toFixed(1) + '%' : '–'));
    },

    fnGrafica: function () {
        var g = Salarios.fnPorAnio(), anios = $.map(Object.keys(g), Number).sort(function (a, b) { return a - b; });
        var tope = 0;
        $.each(anios, function (i, a) { $.each(g[a], function (j, s) { tope = Math.max(tope, s.salario); }); });
        var html = '';
        $.each(anios, function (i, a) {
            var max = Math.max.apply(null, $.map(g[a], function (s) { return s.salario; }));
            html += '<div class="sal-barra' + (Salarios.anioSel === a ? ' sel' : '') + '" data-anio="' + a + '" title="' + a + ': ' + $.map(g[a], function (s) { return Salarios.fnMoneda(s.salario); }).join(', ') + '">' +
                    '<em>' + Math.round(max) + '</em><i style="height:' + Math.max(1, max / tope * 85) + '%"></i><span>' + String(a).slice(-2) + '</span></div>';
        });
        $('#salGrafica').html(html || '<span class="text-muted">Sin salarios.</span>');
    },

    fnTabla: function () {
        var t = $.trim($('#salBuscar').val()).toLowerCase(), bajas = $('#salVerBajas').is(':checked');
        var g = Salarios.fnPorAnio(), html = '', anioActual = null;
        $.each(Salarios.lista, function (i, s) {
            if (!s.activo && !bajas) return;
            if (Salarios.anioSel && s.anio !== Salarios.anioSel) return;
            if (t && (s.anio + ' ' + s.salario.toFixed(2) + ' ' + s.descripcion).toLowerCase().indexOf(t) < 0) return;
            if (s.anio !== anioActual) {
                anioActual = s.anio;
                var aum = Salarios.fnAumento(s.anio, g);
                html += '<tr class="sal-anio"><td colspan="6">' + s.anio + (g[s.anio] ? ' · ' + g[s.anio].length + ' salario(s)' : '') +
                        (aum ? ' · <span class="' + (aum.pct >= 0 ? 'sal-sube' : 'sal-baja-txt') + '">' + (aum.pct >= 0 ? '+' : '') + aum.pct.toFixed(1) + '% vs ' + aum.anterior + '</span>' : '') + '</td></tr>';
            }
            var previo = Salarios.fnAumento(s.anio, g), extra = '';
            if (previo && s.activo) {
                var mismos = $.grep(g[previo.anterior], function () { return true; }).sort(function (a, b) { return a.salario - b.salario; });
                var propios = g[s.anio].slice().sort(function (a, b) { return a.salario - b.salario; });
                var pos = propios.indexOf(s), par = mismos[Math.min(pos, mismos.length - 1)];
                if (par) extra = '<span class="' + (s.salario >= par.salario ? 'sal-sube' : 'sal-baja-txt') + '">' + ((s.salario / par.salario - 1) * 100).toFixed(1) + '%</span> <small class="text-muted">vs ' + Salarios.fnMoneda(par.salario) + '</small>';
            }
            html += '<tr data-id="' + s.id + '"' + (s.activo ? '' : ' class="sal-baja"') + '>' +
                    '<td>' + s.anio + '</td><td class="text-right"><b>' + Salarios.fnMoneda(s.salario) + '</b></td>' +
                    '<td>' + Salarios.fnEsc(s.descripcion) + (String(s.descripcion).replace(/\s+/g, ' ') !== Salarios.fnLetra(s.salario) ? ' <i class="fa fa-exclamation-triangle text-warning" title="No coincide con el importe con letra correcto: ' + Salarios.fnEsc(Salarios.fnLetra(s.salario)) + '"></i>' : '') + '</td>' +
                    '<td>' + extra + '</td>' +
                    '<td>' + (s.activo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Baja</span>') + '</td>' +
                    '<td class="text-nowrap">' + (Salarios.puedeEditar
                        ? '<button class="btn btn-sm btn-info sal-edit" title="Editar"><i class="fa fa-pencil"></i></button> ' +
                          (s.activo ? '<button class="btn btn-sm btn-danger sal-baja-btn" title="Dar de baja"><i class="fa fa-ban"></i></button>'
                                    : '<button class="btn btn-sm btn-success sal-alta-btn" title="Reactivar"><i class="fa fa-undo"></i></button>')
                        : '') + '</td></tr>';
        });
        $('#salTabla').html(html || '<tr><td colspan="6" class="text-center text-muted">No hay salarios con ese filtro.</td></tr>');
    },

    // ---------------------------------------------------------------- captura
    fnAbrir: function (s) {
        $('#salModalTitulo').text(s ? 'Editar salario' : 'Agregar salario');
        $('#salId').val(s ? s.id : 0);
        $('#salAnio').val(s ? s.anio : new Date().getFullYear());
        $('#salImporte').val(s ? s.salario.toFixed(2) : '');
        $('#salLetra').val(s ? s.descripcion : '').data('auto', !s);
        $('#salAviso').text(s ? 'Si este salario ya se usó en contratos, los contratos nuevos saldrán con el cambio.' : '');
        $('#modalSalario').modal('show');
        setTimeout(function () { $(s ? '#salImporte' : '#salImporte').focus(); }, 400);
    },

    fnGuardar: function () {
        var datos = { id: $('#salId').val(), anio: $('#salAnio').val(), salario: $('#salImporte').val(), descripcion: $('#salLetra').val() };
        var $b = $('#salGuardar').prop('disabled', true);
        $.post('salarios/guardar', datos, null, 'json').done(function (r) {
            if (!r.exito) { if (typeof swal === 'function') swal('No se guardó', r.mensaje, 'warning'); else alert(r.mensaje); return; }
            $('#modalSalario').modal('hide');
            if (typeof swal === 'function') swal('Listo', r.mensaje, 'success');
            Salarios.fnCargar();
        }).always(function () { $b.prop('disabled', false); });
    },

    fnActivar: function (id, activo) {
        var s = $.grep(Salarios.lista, function (x) { return x.id === id; })[0];
        if (!activo && !confirm('¿Dar de baja el salario ' + Salarios.fnMoneda(s.salario) + ' de ' + s.anio + '?\nYa no saldrá para elegir en los contratos (no se borra).')) return;
        $.post('salarios/activar', { id: id, activo: activo ? 1 : 0 }, null, 'json').done(function (r) {
            if (typeof swal === 'function') swal(r.exito ? 'Listo' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
            Salarios.fnCargar();
        });
    },

    fnInicializar: function () {
        $('#salNuevo').on('click', function () { Salarios.fnAbrir(null); });
        $('#salBuscar').on('input', Salarios.fnTabla);
        $('#salVerBajas').on('change', Salarios.fnTabla);
        $('#salGuardar').on('click', Salarios.fnGuardar);
        $('#salImporte').on('input', function () {
            // el importe con letra se escribe solo mientras no lo hayan corregido a mano
            if ($('#salLetra').data('auto') || !$.trim($('#salLetra').val())) {
                var v = parseFloat(this.value);
                $('#salLetra').val(v > 0 ? Salarios.fnLetra(v) : '').data('auto', true);
            }
        });
        $('#salLetra').on('input', function () { $(this).data('auto', false); });
        $('#salGrafica').on('click', '.sal-barra', function () {
            var a = Number($(this).data('anio'));
            Salarios.anioSel = Salarios.anioSel === a ? null : a;
            Salarios.fnGrafica(); Salarios.fnTabla();
        });
        $('#salTabla').on('click', '.sal-edit', function () {
            var id = $(this).closest('tr').data('id');
            Salarios.fnAbrir($.grep(Salarios.lista, function (x) { return x.id === id; })[0]);
        }).on('click', '.sal-baja-btn', function () { Salarios.fnActivar($(this).closest('tr').data('id'), false); })
          .on('click', '.sal-alta-btn', function () { Salarios.fnActivar($(this).closest('tr').data('id'), true); });
        Salarios.fnCargar();
    }
};

$(function () { Salarios.fnInicializar(); });
