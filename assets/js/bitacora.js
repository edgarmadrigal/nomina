/**
 * Catálogos > Historial de cambios (controllers/Bitacora.php).
 * Arriba los cambios en tiempo real (pide lo nuevo cada 5 s), abajo el historial con filtros.
 */
var Bitacora = {
    ultimoId: 0,
    pausado: false,
    reloj: null,
    actual: null,          // movimiento abierto en el detalle
    historial: [],
    colFiltros: {},        // filtros tipo Excel por columna: { clave: [valores permitidos] }
    acciones: {
        ALTA: ['Alta', 'badge-success'],
        MODIFICACION: ['Modificación', 'badge-info'],
        ELIMINACION: ['Eliminación', 'badge-danger']
    },
    // Etiquetas de los botones de revertir/reaplicar según el tipo de cambio
    etiquetas: {
        MODIFICACION: { revertir: 'Revertir (dejar como antes)', reaplicar: 'Reaplicar (dejar como después)' },
        ALTA:         { revertir: 'Deshacer el alta (eliminar)',  reaplicar: 'Volver a crearlo' },
        ELIMINACION:  { revertir: 'Restaurar el registro',        reaplicar: 'Volver a eliminarlo' }
    },

    fnEsc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },

    fnPost: function (url, datos) {
        return $.ajax({ url: 'bitacora/' + url, type: 'post', dataType: 'json', data: datos || {} });
    },

    fnCambios: function (m) {
        if (m.masivo) return '<span class="bit-mas">Cambio masivo (no se guardó registro por registro)</span>';
        if (m.accion !== 'MODIFICACION') return '';
        var html = '', max = 4;
        $.each(m.cambios.slice(0, max), function (i, c) {
            html += '<div class="bit-cambio"><b>' + Bitacora.fnEsc(c.campo) + ':</b> <span class="bit-antes">' + Bitacora.fnEsc(c.antes || '(vacío)') +
                    '</span> → <span class="bit-despues">' + Bitacora.fnEsc(c.despues || '(vacío)') + '</span></div>';
        });
        if (m.cambios.length > max) html += '<div class="bit-mas">y ' + (m.cambios.length - max) + ' campo(s) más</div>';
        return html;
    },

    fnFila: function (m, conOrigen, nuevo) {
        var a = Bitacora.acciones[m.accion] || [m.accion, 'badge-secondary'];
        var html = '<tr data-id="' + m.id + '"' + (nuevo ? ' class="bit-nuevo"' : '') + '>' +
            '<td>' + Bitacora.fnEsc(m.fecha) + '</td>' +
            '<td>' + Bitacora.fnEsc(m.usuario) + '</td>' +
            '<td><span class="badge ' + a[1] + '">' + a[0] + '</span></td>' +
            '<td>' + Bitacora.fnEsc(m.catalogo) + '</td>' +
            '<td>' + Bitacora.fnEsc(m.registro || m.llave) + '</td>' +
            '<td>' + Bitacora.fnCambios(m) + '</td>';
        if (conOrigen) html += '<td class="bit-origen">' + Bitacora.fnEsc(m.ip || '') + (m.pantalla ? '<br>' + Bitacora.fnEsc(m.pantalla) : '') + '</td>';
        html += '<td>' + (m.masivo ? '' : '<button type="button" class="btn btn-xs btn-sm btn-outline-info bit-ver" title="Ver detalle"><i class="fa fa-eye"></i></button>') + '</td></tr>';
        return html;
    },

    // ---------------------------------------------------------------- tiempo real
    fnVivo: function () {
        if (Bitacora.pausado) return;
        Bitacora.fnPost('recientes', { desde: Bitacora.ultimoId, soloUsuarios: Bitacora.fnSoloUsuarios() }).done(function (r) {
            if (!r.exito) { $('#bitVivo').html('<tr><td colspan="7" class="text-danger">' + Bitacora.fnEsc(r.mensaje) + '</td></tr>'); return; }
            Bitacora.fnPintarHoy(r.hoy);
            var primera = Bitacora.ultimoId === 0, html = '';
            $.each(r.movimientos, function (i, m) { html += Bitacora.fnFila(m, false, !primera); });
            if (primera) $('#bitVivo').html(html || '<tr class="bit-vacio"><td colspan="7" class="text-center text-muted">Todavía no hay cambios registrados.</td></tr>');
            else if (html) { $('#bitVivo .bit-vacio').remove(); $('#bitVivo').prepend(html); $('#bitVivo tr').slice(100).remove(); }
            if (r.movimientos.length) Bitacora.ultimoId = Math.max(Bitacora.ultimoId, r.movimientos[0].id);
            $('#bitEstadoVivo').text('Actualizado ' + new Date().toLocaleTimeString('es-MX'));
        }).fail(function () { $('#bitEstadoVivo').text('Sin conexión, reintentando...'); });
    },

    fnPintarHoy: function (hoy) {
        if (!hoy) return;
        $.each(hoy.totales, function (k, v) { $('#bitHoy b[data-k="' + k + '"]').text(v); });
        var html = '';
        $.each(hoy.usuarios, function (i, u) { html += Bitacora.fnEsc(u.usuario) + ': <b>' + u.n + '</b><br>'; });
        $('#bitTopUsuarios').html(html || 'Sin cambios hoy');
    },

    fnPausa: function () {
        Bitacora.pausado = !Bitacora.pausado;
        $('#bitacora').toggleClass('pausado', Bitacora.pausado);
        $('#bitPausa').html(Bitacora.pausado ? '<i class="fa fa-play"></i> Reanudar' : '<i class="fa fa-pause"></i> Pausar');
        if (!Bitacora.pausado) Bitacora.fnVivo();
    },

    fnSoloUsuarios: function () { return $('#bitSoloUsuarios').is(':checked') ? '1' : '0'; },

    // ---------------------------------------------------------------- historial
    fnBuscar: function () {
        $('#bitHistorial').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
        Bitacora.fnPost('historial', {
            desde: $('#bitDesde').val(), hasta: $('#bitHasta').val(), tabla: $('#bitTabla').val(),
            accion: $('#bitAccion').val(), usuario: $('#bitUsuario').val(), texto: $('#bitTexto').val(),
            soloUsuarios: Bitacora.fnSoloUsuarios()
        }).done(function (r) {
            if (!r.exito) { $('#bitHistorial').html('<tr><td colspan="8" class="text-danger">' + Bitacora.fnEsc(r.mensaje) + '</td></tr>'); return; }
            Bitacora.historial = r.movimientos;
            Bitacora.colFiltros = {};          // una búsqueda nueva limpia los filtros de columna
            Bitacora.limite = r.limite;
            Bitacora.fnRenderHistorial();
        });
    },

    // -------- filtros tipo Excel por columna (sobre lo que ya se bajó) --------
    // Valor de cada columna para filtrar/listar.
    colsFiltro: [
        { key: 'fecha',    valor: function (m) { return (m.fecha || '').substr(0, 10); } },   // por día
        { key: 'usuario',  valor: function (m) { return m.usuario || ''; } },
        { key: 'accion',   valor: function (m) { return (Bitacora.acciones[m.accion] || [m.accion])[0]; } },
        { key: 'catalogo', valor: function (m) { return m.catalogo || ''; } },
        { key: 'registro', valor: function (m) { return m.registro || m.llave || ''; } },
        { key: 'cambios',  valor: function (m) { return Bitacora.fnCambiosTexto(m); } },
        { key: 'origen',   valor: function (m) { return ((m.ip || '') + (m.pantalla ? ' ' + m.pantalla : '')).trim(); } }
    ],

    fnCambiosTexto: function (m) {
        if (m.masivo) return 'Cambio masivo';
        if (m.accion !== 'MODIFICACION' || !m.cambios) return '';
        return $.map(m.cambios, function (c) { return c.campo + ': ' + (c.antes || '') + '→' + (c.despues || ''); }).join(' | ');
    },

    fnCol: function (key) { return $.grep(Bitacora.colsFiltro, function (c) { return c.key === key; })[0]; },

    // Filas que pasan TODOS los filtros de columna menos (opcional) el que se está abriendo.
    fnFilasFiltradas: function (exceptoKey) {
        return $.grep(Bitacora.historial, function (m) {
            for (var i = 0; i < Bitacora.colsFiltro.length; i++) {
                var c = Bitacora.colsFiltro[i];
                if (c.key === exceptoKey) continue;
                var sel = Bitacora.colFiltros[c.key];
                if (sel && $.inArray(c.valor(m), sel) === -1) return false;
            }
            return true;
        });
    },

    fnRenderHistorial: function () {
        var filas = Bitacora.fnFilasFiltradas(null);
        var html = '';
        $.each(filas, function (i, m) { html += Bitacora.fnFila(m, true, false); });
        $('#bitHistorial').html(html || '<tr><td colspan="8" class="text-center text-muted">No hay cambios con esos filtros.</td></tr>');
        var hayCol = Object.keys(Bitacora.colFiltros).length > 0;
        $('#bitCuenta').text(filas.length + ' movimiento(s)'
            + (hayCol ? ' (de ' + Bitacora.historial.length + ', filtrados por columna)' : '')
            + (Bitacora.historial.length >= Bitacora.limite ? ' — se bajaron los ' + Bitacora.limite + ' más recientes; acota las fechas si falta algo' : ''));
        $('#bitacora .bit-flt').each(function () {
            $(this).toggleClass('activo', !!Bitacora.colFiltros[$(this).data('col')]);
        });
    },

    fnAbrirFiltroCol: function (key, $icono) {
        if ($('#bitFltPanel').data('col') === key && $('#bitFltPanel').is(':visible')) { Bitacora.fnCerrarFiltro(); return; }
        var col = Bitacora.fnCol(key);
        var base = Bitacora.fnFilasFiltradas(key);           // cascada: valores según los demás filtros
        var mapa = {};
        $.each(base, function (i, m) { var v = col.valor(m); mapa[v] = (mapa[v] || 0) + 1; });
        var valores = Object.keys(mapa).sort(function (a, b) { return a.localeCompare(b, undefined, { numeric: true }); });
        var sel = Bitacora.colFiltros[key];                  // null = todos

        var items = $.map(valores, function (v) {
            var marcado = !sel || $.inArray(v, sel) !== -1;
            var txt = v === '' ? '(vacío)' : v;
            return '<label title="' + Bitacora.fnEsc(txt) + '"><input type="checkbox" class="bit-flt-chk" value="' + Bitacora.fnEsc(v) + '"'
                 + (marcado ? ' checked' : '') + '> ' + Bitacora.fnEsc(txt) + ' <span class="text-muted">(' + mapa[v] + ')</span></label>';
        }).join('');

        var $p = $('#bitFltPanel').data('col', key).html(
            '<input type="search" class="bit-flt-search form-control form-control-sm" placeholder="Buscar valor...">' +
            '<label style="border-bottom:1px solid #e3e8ee;margin-bottom:4px;"><input type="checkbox" class="bit-flt-todos" checked> <b>(Seleccionar todo)</b></label>' +
            '<div class="bit-flt-lista">' + (items || '<span class="text-muted">Sin valores</span>') + '</div>' +
            '<div class="bit-flt-acc"><button type="button" class="btn btn-xs btn-sm btn-link bit-flt-limpiar">Quitar filtro</button>' +
            '<button type="button" class="btn btn-xs btn-sm btn-info bit-flt-aplicar">Aceptar</button></div>'
        );
        // posición debajo del icono
        var off = $icono.offset(), pw = 260, left = Math.min(off.left, $(window).scrollLeft() + $(window).width() - pw - 10);
        $p.css({ top: off.top + $icono.outerHeight() + 3, left: Math.max(5, left) }).show();
        $p.find('.bit-flt-search').focus();
    },

    fnCerrarFiltro: function () { $('#bitFltPanel').hide().removeData('col'); },

    fnExcel: function () {
        if (!Bitacora.historial.length) { alert('Primero busca en el historial.'); return; }
        var datos = Bitacora.fnFilasFiltradas(null);   // exporta lo que se ve (con filtros de columna)
        var lineas = [['Fecha y hora', 'Usuario', 'Acción', 'Catálogo', 'Registro', 'Qué cambió', 'IP', 'Pantalla']];
        $.each(datos, function (i, m) {
            var cambios = $.map(m.cambios, function (c) { return c.campo + ': ' + c.antes + ' -> ' + c.despues; }).join(' | ');
            lineas.push([m.fecha, m.usuario, (Bitacora.acciones[m.accion] || [m.accion])[0], m.catalogo, m.registro || m.llave, cambios, m.ip || '', m.pantalla || '']);
        });
        var csv = '﻿' + $.map(lineas, function (l) {
            return $.map(l, function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'historial_cambios_' + $('#bitDesde').val() + '_' + $('#bitHasta').val() + '.csv';
        document.body.appendChild(a); a.click(); a.remove();
    },

    // ---------------------------------------------------------------- detalle
    fnDetalle: function (id) {
        $('#bitModalTitulo').text('Detalle del movimiento');
        $('#bitModalCuerpo').html('<i class="fa fa-spinner fa-spin"></i>');
        $('#bitVerRegistro').show();
        $('#modalBitacora').modal('show');
        Bitacora.fnPost('detalle', { id: id }).done(function (r) {
            if (!r.exito) { $('#bitModalCuerpo').text(r.mensaje); return; }
            var m = r.movimiento, a = Bitacora.acciones[m.accion] || [m.accion, 'badge-secondary'];
            Bitacora.actual = m;
            var html = '<p><span class="badge ' + a[1] + '" style="color:#fff">' + a[0] + '</span> en <b>' + Bitacora.fnEsc(m.catalogo) + '</b>: ' +
                       Bitacora.fnEsc(m.registro || m.llave) + '<br><b>' + Bitacora.fnEsc(m.usuario) + '</b> el ' + Bitacora.fnEsc(m.fecha) +
                       (m.ip ? ' desde ' + Bitacora.fnEsc(m.ip) : '') + (m.pantalla ? ' (pantalla ' + Bitacora.fnEsc(m.pantalla) + ')' : '') +
                       '<br><small class="text-muted">' + Bitacora.fnEsc(m.origen || '') + '</small></p>';
            html += '<table class="table table-sm bit-campos"><thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead><tbody>';
            $.each(m.campos, function (i, c) {
                html += '<tr' + (c.cambio ? ' class="cambio"' : '') + '><td>' + Bitacora.fnEsc(c.campo) + '</td><td>' + Bitacora.fnEsc(c.antes) + '</td><td>' + Bitacora.fnEsc(c.despues) + '</td></tr>';
            });
            $('#bitModalCuerpo').html(html + '</tbody></table>' + (m.tabla === 'usuarios' ? '<small class="text-muted">La contraseña no se guarda; solo una huella para saber si cambió.</small>' : ''));
            // Botones de revertir / reaplicar (solo administrador y si no fue cambio masivo)
            var et = BIT_ADMIN && !m.masivo ? Bitacora.etiquetas[m.accion] : null;
            if (et) {
                $('#bitRevertir span').text(et.revertir);
                $('#bitReaplicar span').text(et.reaplicar);
                $('#bitRevertir, #bitReaplicar').show();
            } else {
                $('#bitRevertir, #bitReaplicar').hide();
            }
        });
    },

    /** Revierte (deja como 'antes') o reaplica (deja como 'después') el movimiento abierto. */
    fnRevertir: function (modo) {
        var m = Bitacora.actual;
        if (!m) return;
        var et = (Bitacora.etiquetas[m.accion] || {})[modo] || modo;
        swal({
            title: '¿Seguro?',
            text: et + ' — registro "' + (m.registro || m.llave) + '" en ' + m.catalogo + '. Esto cambia datos reales del sistema y quedará registrado a tu nombre.',
            icon: 'warning', buttons: ['Cancelar', 'Sí, ' + (modo === 'revertir' ? 'revertir' : 'reaplicar')], dangerMode: true
        }).then(function (ok) {
            if (!ok) return;
            $('#bitRevertir, #bitReaplicar').prop('disabled', true);
            Bitacora.fnPost('revertir', { id: m.id, modo: modo }).done(function (r) {
                swal(r.exito ? 'Listo' : 'No se pudo', r.mensaje, r.exito ? 'success' : 'warning');
                if (r.exito) { $('#modalBitacora').modal('hide'); Bitacora.fnVivo(); Bitacora.fnBuscar(); }
            }).fail(function () {
                swal('No se pudo', 'Error de conexión.', 'error');
            }).always(function () {
                $('#bitRevertir, #bitReaplicar').prop('disabled', false);
            });
        });
    },

    fnHistoriaRegistro: function () {
        var m = Bitacora.actual;
        if (!m) return;
        $('#bitModalTitulo').text('Historia de ' + m.catalogo + ': ' + (m.registro || m.llave));
        $('#bitVerRegistro, #bitRevertir, #bitReaplicar').hide();
        $('#bitModalCuerpo').html('<i class="fa fa-spinner fa-spin"></i>');
        Bitacora.fnPost('registro', { tabla: m.tabla, llave: m.llave }).done(function (r) {
            var html = '<table class="table table-sm bit-tabla"><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Qué cambió</th></tr></thead><tbody>';
            $.each(r.movimientos || [], function (i, x) {
                var a = Bitacora.acciones[x.accion] || [x.accion, 'badge-secondary'];
                html += '<tr><td>' + Bitacora.fnEsc(x.fecha) + '</td><td>' + Bitacora.fnEsc(x.usuario) + '</td><td><span class="badge ' + a[1] + '" style="color:#fff">' + a[0] + '</span></td><td>' + Bitacora.fnCambios(x) + '</td></tr>';
            });
            $('#bitModalCuerpo').html(html + '</tbody></table>');
        });
    },

    fnInicializar: function () {
        var hoy = new Date(), hace7 = new Date(Date.now() - 7 * 864e5);
        var f = function (d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
        $('#bitDesde').val(f(hace7)); $('#bitHasta').val(f(hoy));
        $('#bitPausa').on('click', Bitacora.fnPausa);
        // Al cambiar "Solo usuarios" se reinicia el panel en vivo y se vuelve a buscar el historial
        $('#bitSoloUsuarios').on('change', function () {
            Bitacora.ultimoId = 0;
            $('#bitVivo').html('<tr><td colspan="7" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
            Bitacora.fnVivo();
            Bitacora.fnBuscar();
        });
        $('#bitBuscar').on('click', Bitacora.fnBuscar);

        // Filtros tipo Excel por columna
        $('#bitacora').on('click', '.bit-flt', function (e) {
            e.stopPropagation();
            Bitacora.fnAbrirFiltroCol($(this).data('col'), $(this));
        });
        $('#bitFltPanel').on('click', function (e) { e.stopPropagation(); });
        $('#bitFltPanel').on('keyup', '.bit-flt-search', function () {
            var q = $(this).val().toLowerCase();
            $('#bitFltPanel .bit-flt-lista label').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
            });
        });
        $('#bitFltPanel').on('change', '.bit-flt-todos', function () {
            $('#bitFltPanel .bit-flt-lista label:visible .bit-flt-chk').prop('checked', this.checked);
        });
        $('#bitFltPanel').on('click', '.bit-flt-aplicar', function () {
            var key = $('#bitFltPanel').data('col');
            var todas = $('#bitFltPanel .bit-flt-chk');
            var marcadas = todas.filter(':checked');
            if (marcadas.length === todas.length) delete Bitacora.colFiltros[key];     // todo = sin filtro
            else Bitacora.colFiltros[key] = marcadas.map(function () { return this.value; }).get();
            Bitacora.fnCerrarFiltro();
            Bitacora.fnRenderHistorial();
        });
        $('#bitFltPanel').on('click', '.bit-flt-limpiar', function () {
            delete Bitacora.colFiltros[$('#bitFltPanel').data('col')];
            Bitacora.fnCerrarFiltro();
            Bitacora.fnRenderHistorial();
        });
        $(document).on('click', function () { Bitacora.fnCerrarFiltro(); });
        $(document).on('keydown', function (e) { if (e.key === 'Escape') Bitacora.fnCerrarFiltro(); });
        $('#bitExcel').on('click', Bitacora.fnExcel);
        $('#bitTexto, #bitUsuario').on('keydown', function (e) { if (e.key === 'Enter') Bitacora.fnBuscar(); });
        $('#bitacora').on('click', '.bit-ver', function () { Bitacora.fnDetalle($(this).closest('tr').data('id')); });
        $('#bitVerRegistro').on('click', Bitacora.fnHistoriaRegistro);
        $('#bitRevertir').on('click', function () { Bitacora.fnRevertir('revertir'); });
        $('#bitReaplicar').on('click', function () { Bitacora.fnRevertir('reaplicar'); });
        Bitacora.fnVivo();
        Bitacora.fnBuscar();
        Bitacora.reloj = setInterval(function () { if (!document.hidden) Bitacora.fnVivo(); }, 5000);
    }
};

$(function () { Bitacora.fnInicializar(); });
