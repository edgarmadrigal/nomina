/**
 * Reportes > Nómina consolidada Microsip (controllers/nominaConsolidada.php).
 * Dos vistas de los mismos datos: concentrada por empleado y costo total por empresa/departamento.
 */
var NCon = {
    datos: null,         // última respuesta del servidor
    ultimo: null,        // parámetros del último reporte (para el Excel)
    vista: 'concentrada',

    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnNum: function (n) {
        if (!n) return '';
        return Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    fnFecha: function (f) { if (!f) return ''; var p = String(f).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f; },
    fnIso: function (d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); },

    fnEmpresas: function () {
        $.post('nominaConsolidada/empresas', {}, null, 'json').done(function (lista) {
            var html = '';
            $.each(lista || [], function (i, e) {
                html += '<label><input type="checkbox" class="ncEmp" value="' + Number(e.id) + '"> ' + NCon.esc(e.nombre) + '</label>';
            });
            $('#ncEmpresas').html(html || '<span class="text-muted">No tienes empresas con Microsip.</span>');
            // recordar las empresas elegidas la última vez (solo en este navegador)
            try {
                var prev = JSON.parse(window.localStorage.getItem('ncon.empresas') || '[]');
                $.each(prev, function (i, id) { $('.ncEmp[value="' + Number(id) + '"]').prop('checked', true); });
            } catch (e) { }
        });
    },

    fnParametros: function () {
        var emp = $('.ncEmp:checked').map(function () { return this.value; }).get();
        return { empresas: emp, fechaInicio: $('#ncFi').val(), fechaFin: $('#ncFf').val(),
                 tipoNomina: $('#ncTipo').val(), soloAplicadas: $('#ncAplicadas').is(':checked') ? 1 : 0 };
    },

    fnGenerar: function () {
        if (NCon.vista === 'imss') { NCon.fnGenerarMov(); return; }
        var p = NCon.fnParametros();
        if (!p.empresas.length) { swal('Aviso', 'Elige al menos una empresa.', 'warning'); return; }
        if (!p.fechaInicio || !p.fechaFin) { swal('Aviso', 'Elige las fechas de la nómina.', 'warning'); return; }
        try { window.localStorage.setItem('ncon.empresas', JSON.stringify(p.empresas)); } catch (e) { }
        var btn = $('#ncGenerar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Consultando Microsip…');
        $('#ncImprimir, #ncExcel, #ncBuscar').prop('disabled', true);
        $('#ncHoja').html('<div class="text-center text-muted">Consultando Microsip de ' + p.empresas.length + ' empresa(s)… puede tardar unos segundos.</div>');
        $.ajax({ url: 'nominaConsolidada/datos', type: 'post', data: p, dataType: 'json', traditional: false }).done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); $('#ncHoja').html(''); return; }
            NCon.datos = r;
            NCon.ultimo = p;
            NCon.fnPintar();
            $('#ncImprimir, #ncExcel, #ncBuscar').prop('disabled', false);
        }).fail(function () {
            swal('No se pudo', 'No se pudo generar el reporte (revisa la conexión con Microsip).', 'error');
            $('#ncHoja').html('');
        }).always(function () {
            btn.prop('disabled', false).html('<i class="fa fa-search"></i> Generar');
        });
    },

    /** Conceptos con algún importe (se ocultan las columnas en ceros). */
    fnConceptos: function (empleados) {
        var usados = {};
        $.each(empleados, function (i, e) { $.each(e.imp, function (k, v) { if (v) usados[k] = true; }); });
        var lista = [];
        $.each(NCon.datos.conceptos, function (k, c) { if (usados[k]) lista.push($.extend({ k: k }, c)); });
        return lista;
    },

    fnFiltrados: function () {
        var txt = $.trim($('#ncBuscar').val() || '').toLowerCase();
        var emps = NCon.datos.empleados || [];
        if (!txt) return emps;
        return $.grep(emps, function (e) {
            return (e.numero + ' ' + e.nombre + ' ' + e.depto + ' ' + e.empresa).toLowerCase().indexOf(txt) >= 0;
        });
    },

    fnEncabezado: function (titulo) {
        var r = NCon.datos, ahora = new Date();
        var html = '<div class="nc-enc"><div><h3>' + NCon.esc(titulo) + '</h3>' +
            '<div class="nc-sub">Nóminas del ' + NCon.fnFecha(r.fechaInicio) + ' al ' + NCon.fnFecha(r.fechaFin) + ' · ' + NCon.esc(r.tipoNombre) +
            (r.soloAplicadas ? ' · solo aplicadas' : '') + '</div>' +
            '<div class="nc-sub">Empresas: ' + NCon.esc((r.consultadas || []).join(', ')) + '</div></div>' +
            '<div class="nc-imp">Impreso: ' + ahora.toLocaleDateString('es-MX') + ' ' + ahora.toLocaleTimeString('es-MX') + '</div></div>';
        if (r.errores && r.errores.length) {
            html += '<div class="nc-aviso"><i class="fa fa-exclamation-triangle"></i> Microsip no respondió en: <b>' + NCon.esc(r.errores.join(', ')) +
                    '</b>. Esas empresas no están en el reporte; vuelve a generar en unos minutos.</div>';
        }
        return html;
    },

    fnCabeceraConceptos: function (conceptos) {
        var html = '';
        $.each(conceptos, function (i, c) {
            html += '<th class="' + (c.nat === 'R' ? 'nc-ded' : '') + '" title="' + NCon.esc(c.nombre) + '"><span class="nc-clv">' + NCon.esc(c.clave) + '</span>' + NCon.esc(c.nombre) + '</th>';
        });
        return html + '<th class="nc-tot">Total percepciones</th><th class="nc-tot">Total deducciones</th><th class="nc-tot">Neto</th>';
    },

    /** Suma un empleado a un acumulador. */
    fnSumar: function (acc, e) {
        acc.n = (acc.n || 0) + 1;
        acc.imp = acc.imp || {};
        $.each(e.imp, function (k, v) { acc.imp[k] = (acc.imp[k] || 0) + v; });
        acc.per = (acc.per || 0) + e.per;
        acc.ded = (acc.ded || 0) + e.ded;
    },

    fnCeldas: function (conceptos, t) {
        var html = '';
        $.each(conceptos, function (i, c) { html += '<td class="num">' + NCon.fnNum(t.imp ? t.imp[c.k] : 0) + '</td>'; });
        return html + '<td class="num">' + NCon.fnNum(t.per) + '</td><td class="num">' + NCon.fnNum(t.ded) + '</td><td class="num">' + NCon.fnNum((t.per || 0) - (t.ded || 0)) + '</td>';
    },

    titulos: { concentrada: 'NÓMINA CONCENTRADA', costo: 'COSTO TOTAL DE NÓMINA', dia: 'REPORTE DIRECTOS, INDIRECTOS Y ADMINISTRATIVOS',
               ret: 'RELACIÓN DE PAGOS DE RETENCIONES', pen: 'RELACIÓN DE PENSIONES ALIMENTICIAS' },

    fnPintar: function () {
        if (NCon.vista === 'imss') { NCon.fnPintarMov(); return; }
        if (!NCon.datos) {
            $('#ncHoja').html('<div class="text-center text-muted">Elige las fechas de la nómina y las empresas, y da <b>Generar</b>.</div>');
            return;
        }
        var emps = NCon.fnFiltrados();
        if (!emps.length) {
            $('#ncHoja').html(NCon.fnEncabezado(NCon.titulos[NCon.vista]) + '<div class="text-center text-muted">No hay pagos de nómina con esos filtros.</div>');
            return;
        }
        var f = { costo: NCon.fnCosto, dia: NCon.fnDIA, ret: NCon.fnRet, pen: NCon.fnPen }[NCon.vista] || NCon.fnConcentrada;
        $('#ncHoja').html(f(emps));
    },

    // ---------------- Retenciones, pensiones y solicitud de cheque ----------------
    cheques: [],   // datos de cada botón "Solicitud de cheque" de la vista actual

    fnBotonCheque: function (datos) {
        NCon.cheques.push(datos);
        return '<button type="button" class="btn btn-outline-secondary btn-sm nc-cheque" data-i="' + (NCon.cheques.length - 1) + '" title="Imprimir solicitud de cheque">' +
               '<i class="fa fa-file-text-o"></i> Solicitud</button>';
    },

    fnRet: function (emps) {
        NCon.cheques = [];
        var conc = NCon.datos.conceptos, por = [], idx = {}, gen = {};
        $.each(emps, function (i, e) {
            $.each(e.imp, function (k, v) {
                if (!v || !conc[k] || conc[k].nat !== 'R') return;
                if (!idx[e.empresa]) { idx[e.empresa] = { nombre: e.empresa, c: {} }; por.push(idx[e.empresa]); }
                var t = idx[e.empresa].c[k] = idx[e.empresa].c[k] || { n: 0, imp: 0 };
                t.n++; t.imp += v;
                var g = gen[k] = gen[k] || { n: 0, imp: 0 };
                g.n++; g.imp += v;
            });
        });
        var claves = function (o) { return Object.keys(o).sort(function (a, b) { return conc[a].clave.localeCompare(conc[b].clave, 'es', { numeric: true }); }); };
        var html = NCon.fnEncabezado(NCon.titulos.ret) +
                   '<div class="nc-ayuda-hoja"><i class="fa fa-info-circle"></i> Con <b>Solicitud</b> imprimes la solicitud de cheque de ese importe (por empresa o del total).</div>' +
                   '<table><thead><tr><th class="nc-txt">Clave</th><th class="nc-txt">Concepto</th><th class="nc-txt">Tipo SAT</th><th>Empleados</th><th>Importe</th><th></th></tr></thead><tbody>';
        var r = NCon.datos, periodo = NCon.fnFecha(r.fechaInicio) + (r.fechaInicio !== r.fechaFin ? ' al ' + NCon.fnFecha(r.fechaFin) : '');
        var bloque = function (titulo, lista, cls) {
            var tot = 0;
            html += '<tr class="nc-g1"><td colspan="6">' + NCon.esc(titulo) + '</td></tr>';
            $.each(claves(lista), function (j, k) {
                var t = lista[k]; tot += t.imp;
                html += '<tr><td>' + NCon.esc(conc[k].clave) + '</td><td>' + NCon.esc(conc[k].nombre) + '</td><td>' + NCon.esc(conc[k].satNombre || '') +
                        '</td><td class="num">' + t.n + '</td><td class="num">' + NCon.fnNum(t.imp) + '</td><td>' +
                        NCon.fnBotonCheque({ empresa: titulo, concepto: conc[k].clave + ' ' + conc[k].nombre, importe: t.imp, periodo: periodo }) + '</td></tr>';
            });
            html += '<tr class="' + cls + '"><td colspan="4">TOTAL ' + NCon.esc(titulo) + '</td><td class="num">' + NCon.fnNum(tot) + '</td><td></td></tr>';
        };
        $.each(por, function (i, g) { bloque(g.nombre, g.c, 'nc-subemp'); });
        if (por.length > 1) bloque('TODAS LAS EMPRESAS', gen, 'nc-totg');
        return html + '</tbody></table>';
    },

    fnPen: function (emps) {
        NCon.cheques = [];
        var conc = NCon.datos.conceptos, filas = [], tot = 0;
        $.each(emps, function (i, e) {
            $.each(e.imp, function (k, v) {
                if (v && conc[k] && conc[k].nat === 'R' && Number(conc[k].sat) === 7) {
                    filas.push({ e: e, k: k, imp: v }); tot += v;
                }
            });
        });
        var html = NCon.fnEncabezado(NCon.titulos.pen);
        if (!filas.length) return html + '<div class="text-center text-muted">No hay descuentos de pensión alimenticia (deducciones con tipo SAT 007) en esas nóminas.</div>';
        var r = NCon.datos, periodo = NCon.fnFecha(r.fechaInicio) + (r.fechaInicio !== r.fechaFin ? ' al ' + NCon.fnFecha(r.fechaFin) : '');
        html += '<div class="nc-ayuda-hoja"><i class="fa fa-info-circle"></i> El beneficiario sale del campo libre "Pensión alimenticia" del empleado en Microsip. Con <b>Solicitud</b> imprimes la solicitud de cheque para el beneficiario.</div>' +
                '<table><thead><tr><th class="nc-txt">Empresa</th><th class="nc-txt">Número</th><th class="nc-txt">Empleado</th><th class="nc-txt">Beneficiario</th>' +
                '<th class="nc-txt">Nómina</th><th class="nc-txt">Concepto</th><th>Importe</th><th></th></tr></thead><tbody>';
        $.each(filas, function (i, f) {
            html += '<tr><td>' + NCon.esc(f.e.empresa) + '</td><td>' + NCon.esc(f.e.numero) + '</td><td>' + NCon.esc(f.e.nombre) + '</td><td>' +
                    (f.e.pension ? NCon.esc(f.e.pension) : '<span class="nc-pend">sin capturar</span>') + '</td><td>' + NCon.fnFecha(f.e.fecha) + '</td><td>' +
                    NCon.esc(conc[f.k].clave + ' ' + conc[f.k].nombre) + '</td><td class="num">' + NCon.fnNum(f.imp) + '</td><td>' +
                    NCon.fnBotonCheque({ empresa: f.e.empresa, concepto: 'Pensión alimenticia de ' + f.e.nombre + ' (' + f.e.numero + ')',
                                         beneficiario: f.e.pension, importe: f.imp, periodo: periodo }) + '</td></tr>';
        });
        html += '<tr class="nc-totg"><td colspan="6">TOTAL (' + filas.length + ')</td><td class="num">' + NCon.fnNum(tot) + '</td><td></td></tr>';
        return html + '</tbody></table>';
    },

    /** Importe con letra (pesos M.N.) para la solicitud de cheque. */
    fnLetra: function (n) {
        var U = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE',
                 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO',
                 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
        var D = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        var C = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
        var cientos = function (x) {
            if (x === 100) return 'CIEN';
            var c = Math.floor(x / 100), r = x % 100, s = C[c];
            if (r) s += (s ? ' ' : '') + (r < 30 ? U[r] : D[Math.floor(r / 10)] + (r % 10 ? ' Y ' + U[r % 10] : ''));
            return s;
        };
        var entero = Math.floor(n), cent = Math.round((n - entero) * 100);
        if (cent === 100) { entero++; cent = 0; }
        var mill = Math.floor(entero / 1000000), miles = Math.floor((entero % 1000000) / 1000), resto = entero % 1000, txt = '';
        if (mill) txt += (mill === 1 ? 'UN MILLÓN' : cientos(mill) + ' MILLONES') + ' ';
        if (miles) txt += (miles === 1 ? 'MIL' : cientos(miles) + ' MIL') + ' ';
        if (resto) txt += cientos(resto);
        txt = $.trim(txt) || 'CERO';
        if (mill && !miles && !resto) txt += ' DE';   // "un millón de pesos"
        return txt + (entero === 1 ? ' PESO ' : ' PESOS ') + ('0' + cent).slice(-2) + '/100 M.N.';
    },

    /** Abre la solicitud de cheque (formato del programa viejo) en una ventana para imprimir; los datos se editan ahí. */
    fnCheque: function (d) {
        var hoy = new Date(), usuario = $.trim($('.user-name, #nombreUsuario').first().text()) || '';
        var w = window.open('', '_blank', 'width=900,height=1000');
        if (!w) { swal('Aviso', 'El navegador bloqueó la ventana; permite ventanas emergentes para este sitio.', 'warning'); return; }
        var campo = function (id, valor, ancho) {
            return '<span contenteditable="true" id="' + id + '" class="ed" style="min-width:' + (ancho || 220) + 'px">' + NCon.esc(valor || '') + '</span>';
        };
        var html = '<!doctype html><html><head><meta charset="utf-8"><title>Solicitud de cheque</title><style>' +
            'body{font-family:Arial,sans-serif;font-size:13px;color:#000;margin:24px}h2{text-align:center;letter-spacing:6px;margin:4px 0 18px}' +
            '.emp{text-align:center;font-weight:bold;font-size:15px}.fila{display:flex;gap:10px;margin:9px 0;align-items:baseline}.fila b{min-width:170px}' +
            '.ed{display:inline-block;border-bottom:1px solid #000;padding:0 4px;outline:none}.ed:focus{background:#fff8d6}' +
            '.imp{font-size:18px;font-weight:bold}.firmas{display:flex;justify-content:space-between;margin-top:70px;text-align:center}' +
            '.firmas div{width:30%;border-top:1px solid #000;padding-top:4px;font-size:12px;letter-spacing:3px}' +
            '.barra{background:#eef;padding:8px;margin-bottom:16px;font-size:12px}@media print{.barra{display:none}.ed{border-bottom:1px solid #000}}</style></head><body>' +
            '<div class="barra">Haz clic en los campos subrayados para escribir o corregir los datos y luego imprime. ' +
            '<button onclick="window.print()">Imprimir</button></div>' +
            '<div class="emp">' + NCon.esc(d.empresa) + '</div><h2>SOLICITUD DE CHEQUE</h2>' +
            '<div class="fila"><b>PROVEEDOR / BENEFICIARIO:</b>' + campo('prov', d.beneficiario || '', 420) + '</div>' +
            '<div class="fila"><b>CONCEPTO:</b>' + campo('conc', d.concepto + ' · nómina ' + d.periodo, 420) + '</div>' +
            '<div class="fila"><b>IMPORTE:</b><span class="imp">$ ' + NCon.fnNum(d.importe) + '</span></div>' +
            '<div class="fila"><b>IMPORTE CON LETRA:</b><span>' + NCon.esc(NCon.fnLetra(d.importe)) + '</span></div>' +
            '<div class="fila"><b>BANCO:</b>' + campo('banco', '', 260) + '<b style="min-width:80px">CUENTA:</b>' + campo('cta', '', 200) + '</div>' +
            '<div class="fila"><b>FECHA CONTABLE:</b>' + campo('fcont', hoy.toLocaleDateString('es-MX'), 140) + '</div>' +
            '<div class="fila"><b>FECHA DE VENCIMIENTO:</b>' + campo('fven', '', 140) + '<b style="min-width:150px">FECHA DE ENVÍO:</b>' + campo('fenv', '', 140) + '</div>' +
            '<div class="fila"><b>USUARIO SOLICITANTE:</b>' + campo('usr', usuario, 300) + '</div>' +
            '<div class="firmas"><div>S O L I C I T A N T E<br>NOMBRE Y FIRMA</div><div>A U T O R I Z A</div><div>C A P T U R A</div></div>' +
            '</body></html>';
        w.document.open(); w.document.write(html); w.document.close();
    },

    clasifNombre: { D: 'Directo', I: 'Indirecto', A: 'Administrativo', '': 'Sin clasificar' },

    /** Selector D/I/A de un departamento (en impresión sale el texto). */
    fnSelClasif: function (empresaId, depto, tipo) {
        var html = '<select class="nc-clasif" data-empresa="' + Number(empresaId) + '" data-depto="' + NCon.esc(depto) + '">';
        $.each(['', 'D', 'I', 'A'], function (i, t) {
            html += '<option value="' + t + '"' + (t === (tipo || '') ? ' selected' : '') + '>' + (t ? NCon.clasifNombre[t] : '— Sin clasificar —') + '</option>';
        });
        return html + '</select><span class="nc-clasif-txt">' + NCon.esc(NCon.clasifNombre[tipo || '']) + '</span>';
    },

    /** Guarda la clasificación y la aplica a los empleados de ese departamento ya cargados. */
    fnGuardarClasif: function (sel) {
        var $s = $(sel), emp = Number($s.data('empresa')), depto = String($s.data('depto')), tipo = $s.val();
        $s.prop('disabled', true);
        $.post('nominaConsolidada/clasificar', { empresa_id: emp, depto: depto, tipo: tipo }, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
            $.each(NCon.datos.empleados, function (i, e) {
                if (e.empresa_id === emp && String(e.depto).toUpperCase() === depto.toUpperCase()) e.clasif = tipo;
            });
            $s.next('.nc-clasif-txt').text(NCon.clasifNombre[tipo || '']);
            if (typeof toastr !== 'undefined') toastr.success(depto + ': ' + NCon.clasifNombre[tipo || '']);
        }).fail(function () {
            swal('No se pudo', 'No se pudo guardar la clasificación.', 'error');
        }).always(function () { $s.prop('disabled', false); });
    },

    fnDIA: function (emps) {
        var conceptos = NCon.fnConceptos(emps), orden = ['D', 'I', 'A', ''];
        var porEmp = [], idx = {}, gen = {}, sinClasif = {};
        $.each(emps, function (i, e) {
            if (!idx[e.empresa]) { idx[e.empresa] = { nombre: e.empresa, tipos: {}, tot: {} }; porEmp.push(idx[e.empresa]); }
            var t = e.clasif || '';
            idx[e.empresa].tipos[t] = idx[e.empresa].tipos[t] || {};
            NCon.fnSumar(idx[e.empresa].tipos[t], e); NCon.fnSumar(idx[e.empresa].tot, e);
            gen[t] = gen[t] || {}; NCon.fnSumar(gen[t], e);
            if (!t) sinClasif[e.empresa + ' · ' + (e.depto || '(sin departamento)')] = true;
        });
        var ncol = conceptos.length + 5;
        var html = NCon.fnEncabezado(NCon.titulos.dia);
        var pend = Object.keys(sinClasif);
        if (pend.length) {
            html += '<div class="nc-aviso"><i class="fa fa-exclamation-triangle"></i> Hay <b>' + pend.length + ' departamento(s) sin clasificar</b>. ' +
                    'Clasifícalos en la vista <b>Costo total</b> (columna Tipo); se guarda y ya queda para siempre.</div>';
        }
        html += '<table><thead><tr><th class="nc-txt">Empresa / Tipo</th><th>Empleados</th>' + NCon.fnCabeceraConceptos(conceptos) + '</tr></thead><tbody>';
        var filaTipo = function (nombre, t, cls) {
            return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td' + (nombre === 'Sin clasificar' ? ' class="nc-pend"' : '') + '>' + NCon.esc(nombre) + '</td><td class="num">' + (t.n || 0) + '</td>' + NCon.fnCeldas(conceptos, t) + '</tr>';
        };
        $.each(porEmp, function (i, g) {
            html += '<tr class="nc-g1"><td colspan="' + ncol + '">' + NCon.esc(g.nombre) + '</td></tr>';
            $.each(orden, function (j, t) { if (g.tipos[t]) html += filaTipo(NCon.clasifNombre[t], g.tipos[t]); });
            html += filaTipo('TOTAL ' + g.nombre, g.tot, 'nc-subemp');
        });
        html += '<tr class="nc-g1"><td colspan="' + ncol + '">TODAS LAS EMPRESAS</td></tr>';
        var totG = {};
        $.each(orden, function (j, t) {
            if (!gen[t]) return;
            html += filaTipo(NCon.clasifNombre[t], gen[t]);
            totG.n = (totG.n || 0) + gen[t].n; totG.per = (totG.per || 0) + gen[t].per; totG.ded = (totG.ded || 0) + gen[t].ded;
            totG.imp = totG.imp || {};
            $.each(gen[t].imp, function (k, v) { totG.imp[k] = (totG.imp[k] || 0) + v; });
        });
        html += filaTipo('TOTAL GENERAL', totG, 'nc-totg');
        return html + '</tbody></table>';
    },

    // ---------------- Movimientos IMSS ----------------
    mov: null, ultimoMov: null,
    movNombre: { I: 'Alta', R: 'Reingreso', B: 'Baja', S: 'Modificación de salario' },

    fnGenerarMov: function () {
        var p = NCon.fnParametros();
        p.tiposMov = $('.ncMov:checked').map(function () { return this.value; }).get().join(',');
        if (!p.empresas.length) { swal('Aviso', 'Elige al menos una empresa.', 'warning'); return; }
        if (!p.fechaInicio || !p.fechaFin) { swal('Aviso', 'Elige el rango de fechas de los movimientos.', 'warning'); return; }
        if (!p.tiposMov) { swal('Aviso', 'Elige al menos un tipo de movimiento.', 'warning'); return; }
        try { window.localStorage.setItem('ncon.empresas', JSON.stringify(p.empresas)); } catch (e) { }
        var btn = $('#ncGenerar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Consultando Microsip…');
        $('#ncImprimir, #ncExcel, #ncBuscar').prop('disabled', true);
        $('#ncHoja').html('<div class="text-center text-muted">Consultando movimientos en Microsip…</div>');
        $.post('nominaConsolidada/movimientos', p, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); $('#ncHoja').html(''); return; }
            NCon.mov = r; NCon.ultimoMov = p;
            NCon.fnPintarMov();
            $('#ncImprimir, #ncExcel, #ncBuscar').prop('disabled', false);
        }).fail(function () {
            swal('No se pudo', 'No se pudo consultar Microsip.', 'error');
            $('#ncHoja').html('');
        }).always(function () { btn.prop('disabled', false).html('<i class="fa fa-search"></i> Generar'); });
    },

    fnPintarMov: function () {
        var r = NCon.mov;
        if (!r) { $('#ncHoja').html('<div class="text-center text-muted">Elige fechas, empresas y tipos de movimiento, y da <b>Generar</b>.</div>'); return; }
        var txt = $.trim($('#ncBuscar').val() || '').toLowerCase();
        var filas = !txt ? r.filas : $.grep(r.filas, function (f) {
            return (f.numero + ' ' + f.nombre + ' ' + f.nss + ' ' + f.curp + ' ' + f.empresa).toLowerCase().indexOf(txt) >= 0;
        });
        var campo = $('#ncAgrupar').val() || 'regPatronal';
        var ahora = new Date();
        var html = '<div class="nc-enc"><div><h3>MOVIMIENTOS AFILIATORIOS IMSS</h3>' +
            '<div class="nc-sub">Del ' + NCon.fnFecha(r.fechaInicio) + ' al ' + NCon.fnFecha(r.fechaFin) + '</div>' +
            '<div class="nc-sub">Empresas: ' + NCon.esc((r.consultadas || []).join(', ')) + '</div></div>' +
            '<div class="nc-imp">Impreso: ' + ahora.toLocaleDateString('es-MX') + ' ' + ahora.toLocaleTimeString('es-MX') + '</div></div>';
        if (r.errores && r.errores.length) html += '<div class="nc-aviso">Microsip no respondió en: <b>' + NCon.esc(r.errores.join(', ')) + '</b>.</div>';
        if (!filas.length) return $('#ncHoja').html(html + '<div class="text-center text-muted">No hay movimientos con esos filtros.</div>');

        var grupos = [], idx = {};
        $.each(filas, function (i, f) {
            var g = campo === 'regPatronal' ? (f.regPatronal + (f.regDesc ? ' · ' + f.regDesc : '')) : f[campo];
            g = g || '(sin dato)';
            if (!idx[g]) { idx[g] = { nombre: g, filas: [] }; grupos.push(idx[g]); }
            idx[g].filas.push(f);
        });
        grupos.sort(function (a, b) { return a.nombre < b.nombre ? -1 : a.nombre > b.nombre ? 1 : 0; });
        var etiqueta = { regPatronal: 'Registro patronal', grupo: 'Grupo de pago', empresa: 'Empresa' }[campo];
        html += '<table><thead><tr><th class="nc-txt">Empresa</th><th class="nc-txt">Tipo</th><th class="nc-txt">Fecha</th><th class="nc-txt">Número</th>' +
                '<th class="nc-txt">Nombre</th><th class="nc-txt">NSS</th><th class="nc-txt">CURP</th><th>Sal. diario</th><th>Sal. hora</th><th>Sal. integrado</th><th>Causa baja</th></tr></thead><tbody>';
        $.each(grupos, function (i, g) {
            var cuenta = {};
            html += '<tr class="nc-g1"><td colspan="11">' + NCon.esc(etiqueta + ': ' + g.nombre) + '</td></tr>';
            $.each(g.filas, function (j, f) {
                cuenta[f.tipo] = (cuenta[f.tipo] || 0) + 1;
                html += '<tr class="nc-mov-' + NCon.esc(f.tipo) + '"><td>' + NCon.esc(f.empresa) + '</td><td>' + NCon.esc(f.tipoNombre) + '</td><td>' + NCon.fnFecha(f.fecha) +
                        '</td><td>' + NCon.esc(f.numero) + '</td><td>' + NCon.esc(f.nombre) + '</td><td>' + NCon.esc(f.nss) + '</td><td>' + NCon.esc(f.curp) +
                        '</td><td class="num">' + NCon.fnNum(f.salDiario) + '</td><td class="num">' + NCon.fnNum(f.salHora) + '</td><td class="num">' + NCon.fnNum(f.salInteg) +
                        '</td><td>' + NCon.esc(f.causaBaja) + '</td></tr>';
            });
            var res = $.map(['I', 'R', 'B', 'S'], function (t) { return cuenta[t] ? NCon.movNombre[t] + ': ' + cuenta[t] : null; }).join(' · ');
            html += '<tr class="nc-sub"><td colspan="11">Total ' + g.filas.length + ' movimiento(s) — ' + NCon.esc(res) + '</td></tr>';
        });
        html += '<tr class="nc-totg"><td colspan="11">TOTAL GENERAL: ' + filas.length + ' movimiento(s)</td></tr>';
        $('#ncHoja').html(html + '</tbody></table>');
    },

    fnConcentrada: function (emps) {
        var conceptos = NCon.fnConceptos(emps);
        var variasFechas = false, f0 = emps[0].fecha + emps[0].tipo;
        $.each(emps, function (i, e) { if (e.fecha + e.tipo !== f0) { variasFechas = true; return false; } });
        var nFijas = variasFechas ? 3 : 2, ncol = nFijas + conceptos.length + 3;

        var html = NCon.fnEncabezado('NÓMINA CONCENTRADA') + '<table><thead><tr><th class="nc-txt">Número</th><th class="nc-txt">Nombre</th>' +
                   (variasFechas ? '<th class="nc-txt">Nómina</th>' : '') + NCon.fnCabeceraConceptos(conceptos) + '</tr></thead><tbody>';
        var totG = {}, totE = null, totD = null, empActual = null, depActual = null;
        var cerrarDepto = function () {
            if (totD) html += '<tr class="nc-sub"><td colspan="' + nFijas + '">Total ' + NCon.esc(depActual || '(sin departamento)') + ' (' + totD.n + ')</td>' + NCon.fnCeldas(conceptos, totD) + '</tr>';
        };
        var cerrarEmpresa = function () {
            if (totE) html += '<tr class="nc-subemp"><td colspan="' + nFijas + '">TOTAL ' + NCon.esc(empActual) + ' (' + totE.n + ' empleados)</td>' + NCon.fnCeldas(conceptos, totE) + '</tr>';
        };
        $.each(emps, function (i, e) {
            if (e.empresa !== empActual) {
                cerrarDepto(); cerrarEmpresa();
                empActual = e.empresa; depActual = null; totE = {}; totD = null;
                html += '<tr class="nc-g1"><td colspan="' + ncol + '">' + NCon.esc(e.empresa) + '</td></tr>';
            }
            if (totD === null || e.depto !== depActual) {
                cerrarDepto();
                depActual = e.depto; totD = {};
                html += '<tr class="nc-g2"><td colspan="' + ncol + '">' + NCon.esc(e.depto || '(sin departamento)') + '</td></tr>';
            }
            html += '<tr' + (e.aplicado ? '' : ' class="nc-noapl" title="Nómina sin aplicar"') + '><td>' + NCon.esc(e.numero) + '</td><td>' + NCon.esc(e.nombre) + '</td>' +
                    (variasFechas ? '<td>' + NCon.fnFecha(e.fecha) + ' ' + NCon.esc(e.tipo) + '</td>' : '') + NCon.fnCeldas(conceptos, e) + '</tr>';
            NCon.fnSumar(totD, e); NCon.fnSumar(totE, e); NCon.fnSumar(totG, e);
        });
        cerrarDepto(); cerrarEmpresa();
        html += '<tr class="nc-totg"><td colspan="' + nFijas + '">TOTAL GENERAL (' + totG.n + ' empleados)</td>' + NCon.fnCeldas(conceptos, totG) + '</tr>';
        return html + '</tbody></table>';
    },

    fnCosto: function (emps) {
        var conceptos = NCon.fnConceptos(emps);
        var html = NCon.fnEncabezado('COSTO TOTAL DE NÓMINA') +
                   '<div class="nc-ayuda-hoja"><i class="fa fa-info-circle"></i> En <b>Tipo</b> clasifica cada departamento como Directo, Indirecto o Administrativo; se guarda al momento y lo usa el reporte D, I y A.</div>' +
                   '<table><thead><tr><th class="nc-txt">Empresa / Departamento</th><th class="nc-txt">Centro de costo</th><th class="nc-txt">Tipo</th><th>Empleados</th>' +
                   NCon.fnCabeceraConceptos(conceptos) + '</tr></thead><tbody>';
        // empresa > depto
        var grupos = [], idx = {};
        $.each(emps, function (i, e) {
            if (!idx[e.empresa]) { idx[e.empresa] = { nombre: e.empresa, grupo: e.grupo, empresa_id: e.empresa_id, deptos: {}, info: {}, orden: [], tot: {} }; grupos.push(idx[e.empresa]); }
            var g = idx[e.empresa], d = e.depto || '(sin departamento)';
            if (!g.deptos[d]) { g.deptos[d] = {}; g.info[d] = { cc: e.cc, clasif: e.clasif, depto: e.depto }; g.orden.push(d); }
            NCon.fnSumar(g.deptos[d], e); NCon.fnSumar(g.tot, e);
        });
        var totG = { n: 0, imp: {}, per: 0, ded: 0 };
        $.each(grupos, function (i, g) {
            html += '<tr class="nc-g1"><td colspan="' + (conceptos.length + 7) + '">' + NCon.esc(g.nombre) + (g.grupo ? ' · grupo de pago ' + NCon.esc(g.grupo) : '') + '</td></tr>';
            g.orden.sort();
            $.each(g.orden, function (j, d) {
                var t = g.deptos[d], inf = g.info[d];
                html += '<tr><td>' + NCon.esc(d) + '</td><td>' + NCon.esc(inf.cc) + '</td><td>' +
                        (inf.depto ? NCon.fnSelClasif(g.empresa_id, inf.depto, inf.clasif) : '') + '</td><td class="num">' + t.n + '</td>' + NCon.fnCeldas(conceptos, t) + '</tr>';
            });
            html += '<tr class="nc-subemp"><td colspan="3">TOTAL ' + NCon.esc(g.nombre) + '</td><td class="num">' + g.tot.n + '</td>' + NCon.fnCeldas(conceptos, g.tot) + '</tr>';
            totG.n += g.tot.n; totG.per += g.tot.per; totG.ded += g.tot.ded;
            $.each(g.tot.imp, function (k, v) { totG.imp[k] = (totG.imp[k] || 0) + v; });
        });
        html += '<tr class="nc-totg"><td colspan="3">TOTAL GENERAL</td><td class="num">' + totG.n + '</td>' + NCon.fnCeldas(conceptos, totG) + '</tr>';
        return html + '</tbody></table>';
    },

    fnExcel: function () {
        if (NCon.vista === 'imss') {
            if (!NCon.ultimoMov) return;
            var m = NCon.ultimoMov;
            window.location = 'nominaConsolidada/movimientosExcel?' + $.param({ empresas: m.empresas.join(','), fechaInicio: m.fechaInicio, fechaFin: m.fechaFin, tiposMov: m.tiposMov });
            return;
        }
        if (!NCon.ultimo) return;
        var p = NCon.ultimo;
        window.location = 'nominaConsolidada/excel?' + $.param({ empresas: p.empresas.join(','), fechaInicio: p.fechaInicio, fechaFin: p.fechaFin,
                                                                  tipoNomina: p.tipoNomina, soloAplicadas: p.soloAplicadas });
    },

    fnInicializar: function () {
        // por omisión: la nómina semanal que terminó el domingo pasado
        var d = new Date(); d.setDate(d.getDate() - ((d.getDay() + 7) % 7 || 7));
        $('#ncFi, #ncFf').val(NCon.fnIso(d));
        NCon.fnEmpresas();
        $('#ncTodas').on('click', function (e) { e.preventDefault(); $('.ncEmp').prop('checked', true); });
        $('#ncNinguna').on('click', function (e) { e.preventDefault(); $('.ncEmp').prop('checked', false); });
        $('#ncGenerar').on('click', NCon.fnGenerar);
        $('#ncExcel').on('click', NCon.fnExcel);
        $('#ncImprimir').on('click', function () { window.print(); });
        $('#ncVista button').on('click', function () {
            $('#ncVista button').removeClass('active');
            $(this).addClass('active');
            NCon.vista = $(this).data('vista');
            var imss = NCon.vista === 'imss';
            $('.nc-solo-imss').toggle(imss);
            $('.nc-solo-nomina').toggle(!imss);
            var hay = imss ? !!NCon.mov : !!NCon.datos;
            $('#ncImprimir, #ncExcel, #ncBuscar').prop('disabled', !hay);
            NCon.fnPintar();
        });
        $('#ncAgrupar').on('change', function () { if (NCon.vista === 'imss') NCon.fnPintarMov(); });
        $('#ncHoja').on('change', 'select.nc-clasif', function () { NCon.fnGuardarClasif(this); });
        $('#ncHoja').on('click', '.nc-cheque', function () { NCon.fnCheque(NCon.cheques[Number($(this).data('i'))]); });
        var t = null;
        $('#ncBuscar').on('input', function () { clearTimeout(t); t = setTimeout(NCon.fnPintar, 250); });
    }
};

$(function () { NCon.fnInicializar(); });
