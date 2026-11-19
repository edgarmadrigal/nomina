// Gráficas de Inicio con datos de las semanas procesadas (controllers/Dashboard.php).
var Dash = {
    base: '',
    dias: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
    series: { asistencias: 'Asistencias', faltas: 'Faltas', retardos: 'Retardos', vacaciones: 'Vacaciones', incidencias: 'Incapacidades / Permisos' },
    colores: { Asistencias: '#20aee3', Faltas: '#ef5350', Retardos: '#f4b400', 'Vacaciones': '#26a69a', 'Incapacidades / Permisos': '#8e5ad8' },
    resumen: null,
    detalle: [],

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnFiltros: function () {
        var s = ($('#dashSemana').val() || '').split('|');
        return { anio: s[0] || 0, semana: s[1] || 0, planta: $('#dashPlanta').val() };
    },

    fnCargarFiltros: function () {
        $.ajax({ url: Dash.base + 'checadasBiostar/consultaPlanta', type: 'post', dataType: 'json' }).done(function (data) {
            $.each(data, function (i, p) {
                $('#dashPlanta').append('<option value="' + p.id + '">' + Dash.fnEsc(p.nombre) + '</option>');
            });
        });
        $.ajax({ url: Dash.base + 'dashboard/semanas', type: 'post', dataType: 'json' }).done(function (data) {
            if (!$.isArray(data) || data.length === 0) {
                $('#dashSemana').html('<option value="">Sin semanas procesadas</option>');
                return;
            }
            var html = '';
            $.each(data, function (i, s) {
                html += '<option value="' + s.anio + '|' + s.NoSemana + '">Semana ' + s.NoSemana + ' de ' + s.anio +
                        ' (' + Dash.fnEsc(s.inicio) + ' a ' + Dash.fnEsc(s.fin) + ')</option>';
            });
            $('#dashSemana').html(html);
            Dash.fnCargar();
        });
    },

    fnCargar: function () {
        var f = Dash.fnFiltros();
        if (!f.semana) return;
        $.ajax({ url: Dash.base + 'dashboard/resumen', type: 'post', data: f, dataType: 'json' }).done(function (r) {
            Dash.resumen = r;
            Dash.fnPintarKpis(r);
            Dash.fnGraficaDias(r.porDia || []);
            Dash.fnGraficaPlantas(r.porPlanta || []);
        });
    },

    fnPintarKpis: function (r) {
        var t = { asistencias: 0, faltas: 0, retardos: 0, vacaciones: 0, incidencias: 0 }, emp = 0;
        $.each(r.porDia || [], function (i, d) {
            t.asistencias += +d.asistencias; t.faltas += +d.faltas; t.retardos += +d.retardos;
            t.vacaciones += +d.vacaciones; t.incidencias += +d.incidencias;
        });
        $.each(r.porPlanta || [], function (i, p) { emp += +p.empleados; });
        $('#kpiEmpleados').text(emp.toLocaleString());
        $('#kpiAsistencias').text(t.asistencias.toLocaleString());
        $('#kpiFaltas').text(t.faltas.toLocaleString());
        $('#kpiRetardos').text(t.retardos.toLocaleString());
        $('#kpiVacaciones').text(t.vacaciones.toLocaleString());
        $('#kpiIncidencias').text(t.incidencias.toLocaleString());
    },

    fnGraficaDias: function (porDia) {
        var cols = [['Asistencias'], ['Faltas'], ['Retardos'], ['Vacaciones'], ['Incapacidades / Permisos']];
        for (var n = 1; n <= 7; n++) {
            var d = $.grep(porDia, function (x) { return +x.dia === n; })[0] || { asistencias: 0, faltas: 0, retardos: 0, vacaciones: 0, incidencias: 0 };
            cols[0].push(+d.asistencias); cols[1].push(+d.faltas); cols[2].push(+d.retardos);
            cols[3].push(+d.vacaciones); cols[4].push(+d.incidencias);
        }
        c3.generate({
            bindto: '#graficaDias',
            data: {
                columns: cols,
                type: 'bar',
                colors: Dash.colores,
                onclick: function (d) {
                    var tipo = { 'Asistencias': 'asistencias', 'Faltas': 'faltas', 'Retardos': 'retardos', 'Vacaciones': 'vacaciones', 'Incapacidades / Permisos': 'incidencias' }[d.id];
                    Dash.fnVerDetalle(tipo, d.index + 1, '', d.id + ' del ' + Dash.dias[d.index]);
                }
            },
            bar: { width: { ratio: 0.75 } },
            axis: { x: { type: 'category', categories: Dash.dias } },
            grid: { y: { show: true } },
            tooltip: { format: { title: function (i) { return Dash.dias[i] + ' — clic para ver empleados'; } } }
        });
    },

    fnGraficaPlantas: function (porPlanta) {
        var cols = [], ids = {};
        $.each(porPlanta, function (i, p) {
            if (+p.faltas > 0) { cols.push([p.planta, +p.faltas]); ids[p.planta] = p.empresa_id; }
        });
        if (cols.length === 0) {
            $('#graficaPlantas').html('<div class="text-center text-muted" style="padding-top:120px;"><i class="fa fa-check-circle fa-2x text-success"></i><br>Sin faltas esta semana</div>');
            return;
        }
        c3.generate({
            bindto: '#graficaPlantas',
            data: {
                columns: cols,
                type: 'donut',
                onclick: function (d) {
                    Dash.fnVerDetalle('faltas', 0, ids[d.id], 'Faltas de la semana — ' + d.id);
                }
            },
            donut: { title: 'Faltas', width: 28, label: { format: function (v) { return v; } } },
            legend: { position: 'bottom' }
        });
    },

    /** dia 0 = toda la semana; planta '' = la del filtro. */
    fnVerDetalle: function (tipo, dia, planta, titulo) {
        var f = Dash.fnFiltros();
        if (planta) f.planta = planta;
        f.tipo = tipo; f.dia = dia;
        $('#detalleDashTitulo').text(titulo + ' · Semana ' + f.semana + ' de ' + f.anio);
        $('#detalleDashBuscar').val('');
        $('#detalleDashResumen').text('');
        $('#detalleDashCabeza').html('');
        $('#detalleDashCuerpo').html('<tr><td class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $('#modalDetalleDash').modal('show');
        Dash.detalleTipo = tipo;
        $('#detalleDashSolo3Wrap').toggle(tipo === 'retardos');
        $('#detalleDashSolo3').prop('checked', false);
        $.ajax({ url: Dash.base + 'dashboard/detalle', type: 'post', data: f, dataType: 'json' }).done(function (data) {
            Dash.detalle = $.isArray(data) ? data : [];
            var grupos = Dash.fnAgrupar(Dash.detalle);
            var cols = Dash.fnColsDia();
            var ncols = 5 + Dash.DIAS.length * cols.length;
            $('#detalleDashCabeza').html(Dash.fnCabeceraDetalle());
            var esRet = (tipo === 'retardos'), html = '';
            $.each(grupos, function (i, g) {
                var mapa = {};
                $.each(g.dias, function (j, d) { mapa[Dash.fnNorm(d.nombreDia)] = d; });
                var n = g.dias.length, mas3 = esRet && n > 3;
                var nombre = Dash.fnEsc(g.nombre) +
                    (mas3 ? ' <span class="badge badge-danger">' + n + ' retardos</span>'
                          : (esRet ? ' <span class="text-muted">(' + n + ')</span>' : ''));
                html += '<tr class="' + (mas3 ? 'dd-mas3' : '') + '"><td>' + Dash.fnEsc(g.numero) + '</td><td>' + nombre + '</td><td>' + Dash.fnEsc(g.planta) + '</td>' +
                        '<td>' + Dash.fnEsc(g.departamento) + '</td><td>' + Dash.fnEsc(g.puesto) + '</td>';
                $.each(Dash.DIAS, function (k, dia) {
                    var d = mapa[dia[2]];
                    $.each(cols, function (j, c) {
                        var cell = c.tipo === 'inc' ? Dash.fnCeldaInc(d) : Dash.fnCeldaHora(d, c.campo);
                        html += '<td' + (j === 0 ? ' class="dd-dia-ini"' : '') + '>' + cell + '</td>';
                    });
                });
                html += '</tr>';
            });
            if (html === '') html = '<tr><td colspan="' + ncols + '" class="text-center">No hay registros.</td></tr>';
            $('#detalleDashCuerpo').html(html);
            $('#detalleDashResumen').text(grupos.length + ' empleados, ' + Dash.detalle.length + ' registros');
        }).fail(function () {
            $('#detalleDashCuerpo').html('<tr><td class="text-center text-danger">Error al cargar el detalle.</td></tr>');
        });
    },

    // [nombre completo, abreviatura, clave sin acento] en el orden de la semana
    DIAS: [['Lunes', 'Lun', 'lunes'], ['Martes', 'Mar', 'martes'], ['Miércoles', 'Mié', 'miercoles'], ['Jueves', 'Jue', 'jueves'], ['Viernes', 'Vie', 'viernes'], ['Sábado', 'Sáb', 'sabado'], ['Domingo', 'Dom', 'domingo']],
    detalleTipo: '',

    fnNorm: function (s) { return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim(); },

    // Columnas por día según el reporte: Retardos = solo entrada; Incap./Permisos = solo la incidencia;
    // los demás (Asistencias, Faltas, Vacaciones) = Entrada / Salida / Incidencia.
    fnColsDia: function () {
        if (Dash.detalleTipo === 'retardos') return [{ campo: 'entrada', tit: 'Entrada', tipo: 'hora' }];
        if (Dash.detalleTipo === 'incidencias') return [{ campo: 'concepto', tit: 'Incidencia', tipo: 'inc' }];
        if (Dash.detalleTipo === 'asistencias') return [{ campo: 'entrada', tit: 'Entrada', tipo: 'hora' }, { campo: 'salida', tit: 'Salida', tipo: 'hora' }];
        return [{ campo: 'entrada', tit: 'Entrada', tipo: 'hora' }, { campo: 'salida', tit: 'Salida', tipo: 'hora' }, { campo: 'concepto', tit: 'Incidencia', tipo: 'inc' }];
    },

    // Encabezado: columnas fijas con buscador propio + un grupo de columnas por día (según el reporte).
    fnCabeceraDetalle: function () {
        var ids = [['numero', 'Número'], ['nombre', 'Nombre'], ['planta', 'Planta'], ['departamento', 'Departamento'], ['puesto', 'Puesto']];
        var cols = Dash.fnColsDia();
        var r1 = '<tr>', r2 = '<tr>';
        $.each(ids, function (i, c) {
            r1 += '<th>' + c[1] + '</th>';
            r2 += '<th><input type="text" class="form-control form-control-sm dd-fcol" data-col="' + i + '" placeholder="Buscar"></th>';
        });
        $.each(Dash.DIAS, function (i, dia) {
            r1 += '<th class="dd-dia" colspan="' + cols.length + '">' + dia[0] + '</th>';
            $.each(cols, function (j, c) { r2 += '<th' + (j === 0 ? ' class="dd-dia-ini"' : '') + '>' + c.tit + '</th>'; });
        });
        return r1 + '</tr>' + r2 + '</tr>';
    },

    fnCeldaHora: function (d, campo) {
        if (!d) return '';
        if (Dash.detalleTipo === 'faltas' && !(d.entrada || d.salida)) return '<span class="badge badge-danger">FALTA</span>';
        return Dash.fnEsc(d[campo] || '');
    },

    fnCeldaInc: function (d) {
        if (!d) return '';
        var c = (d.concepto || '').trim();
        if (!c) return '';
        return c.toUpperCase() === 'FALTA' ? '<span class="badge badge-danger">FALTA</span>' : Dash.fnEsc(c);
    },

    // Filtro combinado: buscador global + un buscador por cada columna fija (Número..Puesto).
    fnFiltrarDetalle: function () {
        var filtros = [];
        $('#detalleDashCabeza .dd-fcol').each(function () {
            var q = (this.value || '').toLowerCase();
            if (q) filtros.push({ col: +this.getAttribute('data-col'), q: q });
        });
        var g = ($('#detalleDashBuscar').val() || '').toLowerCase();
        var solo3 = $('#detalleDashSolo3').is(':checked');
        $('#detalleDashCuerpo tr').each(function () {
            var tds = this.children, ok = (!g || this.textContent.toLowerCase().indexOf(g) >= 0);
            if (ok && solo3 && this.className.indexOf('dd-mas3') < 0) ok = false;
            for (var k = 0; ok && k < filtros.length; k++) {
                var cel = tds[filtros[k].col];
                if (!cel || cel.textContent.toLowerCase().indexOf(filtros[k].q) < 0) ok = false;
            }
            this.style.display = ok ? '' : 'none';
        });
    },

    /** Agrupa el detalle por empleado (número + planta, por si se repite el número entre plantas). */
    fnAgrupar: function (detalle) {
        var grupos = [], idx = {};
        $.each(detalle, function (i, r) {
            var key = r.numero + '|' + (r.planta || '');
            if (!(key in idx)) {
                idx[key] = grupos.length;
                grupos.push({ numero: r.numero, nombre: r.nombre, planta: r.planta, departamento: r.departamento, puesto: r.puesto, dias: [] });
            }
            grupos[idx[key]].dias.push({ nombreDia: r.nombreDia, entrada: r.entrada, salida: r.salida, concepto: r.concepto });
        });
        return grupos;
    },

    /** CSV del detalle que se está viendo (se abre en Excel). */
    fnExportar: function () {
        var q = function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; };
        var cols = Dash.fnColsDia();
        var cab = ['Numero', 'Nombre', 'Planta', 'Departamento', 'Puesto'];
        $.each(Dash.DIAS, function (i, dia) { $.each(cols, function (j, c) { cab.push(dia[1] + ' ' + c.tit); }); });
        var lineas = [cab.map(q).join(',')];
        $.each(Dash.fnAgrupar(Dash.detalle), function (i, g) {
            var mapa = {};
            $.each(g.dias, function (j, d) { mapa[Dash.fnNorm(d.nombreDia)] = d; });
            var fila = [g.numero, g.nombre, g.planta, g.departamento, g.puesto];
            $.each(Dash.DIAS, function (k, dia) {
                var d = mapa[dia[2]];
                $.each(cols, function (j, c) {
                    if (c.tipo === 'inc') { fila.push(d && d.concepto || ''); return; }
                    var falta = (Dash.detalleTipo === 'faltas' && d && !(d.entrada || d.salida));
                    fila.push(falta ? 'FALTA' : (d && d[c.campo] || ''));
                });
            });
            lineas.push(fila.map(q).join(','));
        });
        var blob = new Blob(['﻿' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = ($('#detalleDashTitulo').text() || 'detalle').replace(/[^\w\-]+/g, '_') + '.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    }
};

$(document).ready(function () {
    if ($('#graficaDias').length === 0) return;
    Dash.fnCargarFiltros();
    $('#dashSemana, #dashPlanta').on('change', Dash.fnCargar);
    $('.kpi[data-tipo]').on('click', function () {
        var tipo = $(this).data('tipo');
        Dash.fnVerDetalle(tipo, 0, '', Dash.series[tipo] + ' de la semana');
    });
    $('#detalleDashBuscar').on('keyup', Dash.fnFiltrarDetalle);
    $(document).on('keyup', '#detalleDashCabeza .dd-fcol', Dash.fnFiltrarDetalle);
    $('#detalleDashSolo3').on('change', Dash.fnFiltrarDetalle);
    $('#detalleDashExcel').on('click', Dash.fnExportar);
});
