/**
 * Asignación de horarios por empresa: "Editar / ver empleados" de las tablas por día.
 * Muestra el horario de ese día (entrada, salida, turno) y todas las asignaciones que lo tienen
 * ese día, vigentes y anteriores, con su vigencia (desde la fecha de asignación hasta el día antes
 * de la siguiente asignación del empleado) y sus 7 días con horas (horariosEmpresa/empleadosHorario).
 * Edición masiva: se eligen empleados y a cada uno se le crea una asignación nueva desde la fecha
 * indicada, cambiando solo los días elegidos (horariosEmpresa/edicionMasiva).
 */
var VerHorario = {
    dias: ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
    claves: ['', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'],
    datos: null,
    filtro: '',   // '' todas, 'vig' vigentes, 'ant' anteriores
    abierto: null,   // { dia, idHorario } para recargar después de guardar
    horarios: null,  // catálogo de horarios para la edición masiva

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnHoy: function () {
        var d = new Date();
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    },

    fnModal: function () {
        if ($('#modalVerHorario').length) return;
        var filasDias = '';
        for (var n = 1; n <= 7; n++) {
            filasDias += '<div class="ver-masivo-dia"><label>' + VerHorario.dias[n] + '</label>' +
                         '<select class="form-control form-control-sm ver-masivo-horario" data-dia="' + n + '"><option value="0">— sin cambio —</option></select></div>';
        }
        $('body').append(
            '<div class="modal fade" id="modalVerHorario" tabindex="-1" role="dialog">' +
            '  <div class="modal-dialog modal-xl" role="document"><div class="modal-content">' +
            '    <div class="modal-header"><div><h4 class="modal-title" id="verHorarioTitulo"></h4><small id="verHorarioSub"></small></div>' +
            '      <button type="button" class="close" data-dismiss="modal">&times;</button></div>' +
            '    <div class="modal-body">' +
            '      <div class="ver-horario-barra">' +
            '        <input type="text" class="form-control form-control-sm" id="verHorarioBuscar" placeholder="Buscar por número, nombre o departamento">' +
            '        <div class="btn-group btn-group-sm" id="verHorarioFiltros">' +
            '          <button type="button" class="btn ver-filtro activo" data-f="">Todas</button>' +
            '          <button type="button" class="btn ver-filtro" data-f="vig">Vigentes</button>' +
            '          <button type="button" class="btn ver-filtro" data-f="ant">Anteriores</button>' +
            '        </div>' +
            '        <span id="verHorarioCuenta"></span>' +
            '      </div>' +
            '      <div class="ver-horario-tabla"><table class="table table-sm table-bordered mb-0"><thead></thead><tbody></tbody></table></div>' +
            '      <div class="ver-masivo-barra">' +
            '        <span id="verMasivoCuenta">Marca empleados para cambiarles el horario a todos juntos.</span>' +
            '        <button type="button" class="btn btn-sm btn-warning" id="verMasivoAbrir" style="color:#fff !important;" disabled><i class="fa fa-pencil"></i> Cambiar horario a los seleccionados</button>' +
            '      </div>' +
            '      <div id="verMasivo" style="display:none;">' +
            '        <h6><i class="fa fa-pencil"></i> Nuevo horario para <span id="verMasivoTotal"></span> empleado(s)</h6>' +
            '        <p class="ver-masivo-ayuda">Elige solo los días que cambian; los demás se quedan como están en la asignación de cada empleado. ' +
            'Se crea una <b>asignación nueva desde la fecha</b> que pongas; la anterior queda en el historial y vence un día antes.</p>' +
            '        <div class="ver-masivo-dias">' + filasDias + '</div>' +
            '        <div class="ver-masivo-campos">' +
            '          <div><label>Desde (fecha de asignación)</label><input type="date" class="form-control form-control-sm" id="verMasivoFecha"></div>' +
            '          <div><label>Días por semana</label><input type="number" step="0.01" min="0" max="7" class="form-control form-control-sm" id="verMasivoCantidad" placeholder="sin cambio"></div>' +
            '          <div class="ver-masivo-desc"><label>Descripción</label><input type="text" class="form-control form-control-sm" id="verMasivoDescripcion" maxlength="200" placeholder="sin cambio"></div>' +
            '        </div>' +
            '        <div class="ver-masivo-botones">' +
            '          <button type="button" class="btn btn-sm btn-secondary" id="verMasivoCancelar">Cancelar</button>' +
            '          <button type="button" class="btn btn-sm btn-success" id="verMasivoGuardar" style="color:#fff !important;"><i class="fa fa-save"></i> Guardar cambio</button>' +
            '        </div>' +
            '      </div>' +
            '      <div class="ver-horario-nota"><i class="fa fa-info-circle"></i> Cada asignación vale desde su fecha hasta un día antes de la siguiente asignación del empleado; ' +
            'la más reciente sigue <b>vigente</b>. Para un solo empleado también está <a href="horariosusuario">Asignación de Horarios</a>.</div>' +
            '    </div>' +
            '    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>' +
            '  </div></div>' +
            '</div>');
        $('#verHorarioBuscar').on('input', VerHorario.fnFiltrar);
        $('#verHorarioFiltros').on('click', '.ver-filtro', function () {
            VerHorario.filtro = $(this).attr('data-f');
            $('#verHorarioFiltros .ver-filtro').removeClass('activo');
            $(this).addClass('activo');
            VerHorario.fnFiltrar();
        });
        // selección con íconos (las casillas del tema se encimaban en otras ventanas)
        $('#modalVerHorario').on('click', 'tbody tr[data-id] td.ver-sel', function () {
            VerHorario.fnMarcar($(this).closest('tr'), !$(this).closest('tr').hasClass('sel'));
            VerHorario.fnContar();
        });
        $('#modalVerHorario').on('click', 'thead th.ver-sel', function () {
            var $vis = $('#modalVerHorario tbody tr[data-id]:visible');
            var marcar = $vis.filter('.sel').length < $vis.length;
            $vis.each(function () { VerHorario.fnMarcar($(this), marcar); });
            VerHorario.fnContar();
        });
        $('#verMasivoAbrir').on('click', VerHorario.fnAbrirMasivo);
        $('#verMasivoCancelar').on('click', function () { $('#verMasivo').hide(); });
        $('#verMasivoGuardar').on('click', VerHorario.fnGuardarMasivo);
    },

    fnAbrir: function (dia, idHorario) {
        VerHorario.fnModal();
        VerHorario.abierto = { dia: dia, idHorario: idHorario };
        $('#verHorarioTitulo').text('Cargando...');
        $('#verHorarioSub, #verHorarioCuenta').text('');
        $('#verHorarioBuscar').val('');
        $('#verMasivo').hide();
        VerHorario.filtro = '';
        $('#verHorarioFiltros .ver-filtro').removeClass('activo').filter('[data-f=""]').addClass('activo');
        $('#modalVerHorario').modal('show');
        VerHorario.fnCargar();
    },

    fnCargar: function () {
        var a = VerHorario.abierto;
        $('#modalVerHorario thead').empty();
        $('#modalVerHorario tbody').html('<tr><td class="text-center text-muted p-4"><i class="fa fa-spinner fa-spin"></i></td></tr>');
        $.ajax({
            url: 'horariosEmpresa/empleadosHorario',
            type: 'post',
            dataType: 'json',
            data: { empresa_id: $('#id_empresa').val(), dia: a.dia, idHorario: a.idHorario }
        }).done(function (r) {
            if (!r || !r.exito) { $('#verHorarioTitulo').text(r && r.mensaje || 'No se pudo cargar.'); $('#modalVerHorario tbody').empty(); return; }
            VerHorario.datos = r;
            VerHorario.fnPintar();
        }).fail(function () {
            $('#verHorarioTitulo').text('No se pudo cargar el horario.');
            $('#modalVerHorario tbody').empty();
        });
    },

    fnCelda: function (e, n, resaltado) {
        var k = VerHorario.claves[n], nombre = e[k + '_horario'];
        var clase = resaltado ? ' class="ver-hoy"' : '';
        if (!nombre) return '<td' + clase + '><span class="text-muted">—</span></td>';
        if (nombre === 'DESCANSO') return '<td' + clase + '><span class="ver-descanso">Descanso</span></td>';
        return '<td' + clase + '><div class="ver-nombre">' + VerHorario.fnEsc(nombre) + '</div>' +
               '<div class="ver-horas">' + VerHorario.fnEsc(e[k + '_entrada'] || '') + ' – ' + VerHorario.fnEsc(e[k + '_salida'] || '') + '</div></td>';
    },

    fnPintar: function () {
        var r = VerHorario.datos, h = r.horario || {}, dia = Number(r.dia);
        var turno = h.tipo == 1 ? 'Nocturno' : 'Matutino / normal';
        $('#verHorarioTitulo').html('<i class="fa fa-clock-o"></i> ' + VerHorario.fnEsc(h.nombre || 'Horario') + ' · ' + VerHorario.dias[dia]);
        $('#verHorarioSub').text(h.nombre === 'DESCANSO' ? 'Día de descanso'
            : 'Entrada ' + (h.entrada || '--') + ' · Salida ' + (h.salida || '--') + ' · Turno ' + turno);

        var cab = '<tr><th class="ver-sel" title="Marcar / desmarcar los que se ven"><i class="fa fa-square-o ver-caja"></i></th><th>No.</th><th>Empleado</th>';
        for (var n = 1; n <= 7; n++) cab += '<th' + (n === dia ? ' class="ver-hoy"' : '') + '>' + VerHorario.dias[n] + '</th>';
        cab += '<th title="Cantidad de días por semana">Días/sem.</th><th>Vigencia</th></tr>';
        $('#modalVerHorario thead').html(cab);

        var html = '';
        $.each(r.empleados, function (i, e) {
            var texto = (e.empleado_id + ' ' + (e.nombre || '') + ' ' + (e.departamento || '') + ' ' + (e.puesto || '')).toLowerCase();
            var baja = e.estatus && e.estatus !== 'A';
            var vigencia = e.vigente
                ? '<span class="badge badge-success">Vigente</span><br>desde ' + VerHorario.fnEsc(e.fechaAsignacion)
                : (e.sinDias ? '<span class="badge badge-secondary">Reemplazada</span><br>el mismo día ' + VerHorario.fnEsc(e.fechaAsignacion)
                             : '<span class="badge badge-secondary">Anterior</span><br>' + VerHorario.fnEsc(e.fechaAsignacion) + ' al ' + VerHorario.fnEsc(e.hasta));
            html += '<tr data-id="' + Number(e.idAsignacion) + '" data-emp="' + VerHorario.fnEsc(e.empleado_id) + '" data-texto="' + VerHorario.fnEsc(texto) + '" data-vig="' + (e.vigente ? 'vig' : 'ant') + '"' +
                    (e.vigente ? '' : ' class="ver-anterior"') + '>' +
                    '<td class="ver-sel"><i class="fa fa-square-o ver-caja"></i></td>' +
                    '<td>' + VerHorario.fnEsc(e.empleado_id) + '</td>' +
                    '<td><b>' + VerHorario.fnEsc(e.nombre || '(sin datos del empleado)') + '</b>' + (baja ? ' <span class="badge badge-danger">Baja</span>' : '') +
                    '<br><small class="text-muted">' + VerHorario.fnEsc(e.departamento || '') + (e.puesto ? ' · ' + VerHorario.fnEsc(e.puesto) : '') + '</small></td>';
            for (var n = 1; n <= 7; n++) html += VerHorario.fnCelda(e, n, n === dia);
            html += '<td class="text-center">' + (e.cantidad == null ? '' : Number(e.cantidad)) + '</td><td class="ver-vigencia">' + vigencia + '</td></tr>';
        });
        $('#modalVerHorario tbody').html(html || '<tr><td colspan="12" class="text-center text-muted p-4">Ninguna asignación tiene este horario ese día.</td></tr>');
        VerHorario.fnFiltrar();
        VerHorario.fnContar();
    },

    fnFiltrar: function () {
        var t = $.trim($('#verHorarioBuscar').val()).toLowerCase(), visibles = 0;
        $('#modalVerHorario tbody tr[data-texto]').each(function () {
            var ok = (!t || $(this).attr('data-texto').indexOf(t) >= 0) && (!VerHorario.filtro || $(this).attr('data-vig') === VerHorario.filtro);
            $(this).toggle(ok);
            if (ok) visibles++;
        });
        var lista = VerHorario.datos ? VerHorario.datos.empleados : [];
        var vig = $.grep(lista, function (e) { return e.vigente; }).length;
        $('#verHorarioCuenta').html(lista.length ? '<b>' + visibles + '</b> de ' + lista.length + ' asignación(es) · ' + vig + ' vigente(s), ' + (lista.length - vig) + ' anterior(es)' : '');
    },

    // ---------------------------------------------------------------- edición masiva
    fnMarcar: function ($tr, marcar) {
        $tr.toggleClass('sel', marcar).find('.ver-caja').attr('class', 'fa ' + (marcar ? 'fa-check-square' : 'fa-square-o') + ' ver-caja');
    },

    /** Empleados elegidos (uno por empleado aunque se marquen varias de sus asignaciones). */
    fnElegidos: function () {
        var ids = [], emps = {};
        $('#modalVerHorario tbody tr.sel[data-id]').each(function () {
            ids.push($(this).attr('data-id'));
            emps[$(this).attr('data-emp')] = true;
        });
        return { ids: ids, empleados: Object.keys(emps).length };
    },

    fnContar: function () {
        var el = VerHorario.fnElegidos();
        var $vis = $('#modalVerHorario tbody tr[data-id]:visible');
        var todos = $vis.length > 0 && $vis.filter('.sel').length === $vis.length;
        $('#modalVerHorario thead .ver-caja').attr('class', 'fa ' + (todos ? 'fa-check-square' : 'fa-square-o') + ' ver-caja');
        $('#verMasivoAbrir').prop('disabled', el.empleados === 0);
        $('#verMasivoCuenta').html(el.empleados ? '<b>' + el.empleados + '</b> empleado(s) seleccionado(s).' : 'Marca empleados para cambiarles el horario a todos juntos.');
        $('#verMasivoTotal').text(el.empleados);
        if (!el.empleados) $('#verMasivo').hide();
    },

    fnAbrirMasivo: function () {
        var listo = VerHorario.horarios ? $.Deferred().resolve() : $.ajax({ url: 'horariosEmpresa/consultaHorarios', type: 'post', dataType: 'json' })
            .done(function (r) { VerHorario.horarios = $.isArray(r) ? r : []; });
        listo.done(function () {
            $('.ver-masivo-horario').each(function () {
                var $s = $(this);
                if ($s.find('option').length <= 1) {
                    $.each(VerHorario.horarios, function (i, h) {
                        var txt = h.nombre === 'DESCANSO' ? 'DESCANSO' : h.nombre + ' (' + String(h.entrada || '').substring(0, 5) + '–' + String(h.salida || '').substring(0, 5) + ')';
                        $s.append($('<option>').val(h.id).text(txt));
                    });
                }
                $s.val('0');
            });
            $('#verMasivoFecha').val(VerHorario.fnHoy());
            $('#verMasivoCantidad, #verMasivoDescripcion').val('');
            $('#verMasivoTotal').text(VerHorario.fnElegidos().empleados);
            $('#verMasivo').show();
            $('#verMasivo')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }).fail(function () { alert('No se pudo cargar el catálogo de horarios.'); });
    },

    fnGuardarMasivo: function () {
        var el = VerHorario.fnElegidos();
        var dias = {}, cambios = [];
        $('.ver-masivo-horario').each(function () {
            var v = Number($(this).val()), n = Number($(this).attr('data-dia'));
            dias[n] = v;
            if (v > 0) cambios.push(VerHorario.dias[n] + ': ' + $(this).find('option:selected').text());
        });
        var fecha = $('#verMasivoFecha').val(), cantidad = $.trim($('#verMasivoCantidad').val()), desc = $.trim($('#verMasivoDescripcion').val());
        if (!fecha) { alert('Pon la fecha desde la que aplica el nuevo horario.'); return; }
        if (!cambios.length && cantidad === '' && desc === '') { alert('Elige al menos un día que cambie (o los días por semana / descripción).'); return; }
        if (cantidad !== '') cambios.push('Días por semana: ' + cantidad);
        if (desc !== '') cambios.push('Descripción: ' + desc);
        if (!confirm('Se creará una asignación nueva desde el ' + fecha + ' para ' + el.empleados + ' empleado(s):\n\n' + cambios.join('\n') + '\n\n¿Continuar?')) return;

        var $b = $('#verMasivoGuardar').prop('disabled', true);
        $.ajax({
            url: 'horariosEmpresa/edicionMasiva',
            type: 'post',
            dataType: 'json',
            data: { empresa_id: $('#id_empresa').val(), ids: el.ids, dias: dias, fechaAsignacion: fecha, cantidad: cantidad, descripcion: desc }
        }).done(function (r) {
            if (typeof swal === 'function') swal(r.exito ? 'Listo' : 'Revisa', r.mensaje, r.exito ? 'success' : 'warning');
            else alert(r.mensaje);
            if (!r.exito) return;
            $('#verMasivo').hide();
            VerHorario.fnCargar();
            if (window.Modulo && Modulo.fnObtenerDatos) Modulo.fnObtenerDatos($('#id_empresa').val());   // tablas por día
        }).fail(function () {
            alert('No se pudo guardar el cambio.');
        }).always(function () { $b.prop('disabled', false); });
    }
};
