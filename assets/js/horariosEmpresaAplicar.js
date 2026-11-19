/**
 * Asignación de horarios por empresa: ventana "¿A quién se le asigna este horario?".
 * Pregunta si es a todos o solo a algunos y muestra los empleados separados en
 * turno normal y turno nocturno (horarios.tipo = 1). Por omisión, si hay nocturnos,
 * se marcan solo los de turno normal para no quitarles su horario nocturno.
 */
var AplicarA = {
    empleados: [],
    alGuardar: null,

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    modo: 'todos',

    fnAbrir: function (empresa, nombreEmpresa, alGuardar) {
        AplicarA.alGuardar = alGuardar;
        $('#aplicarEmpresa').text(nombreEmpresa || '');
        $('#aplicarNormales, #aplicarNocturnos').html('<div class="text-muted p-2"><i class="fa fa-spinner fa-spin"></i> Cargando empleados...</div>');
        $('#aplicarBuscar').val('');
        $('#modalAplicarA').modal('show');
        $.ajax({
            url: 'horariosEmpresa/empleadosAsignar',
            type: 'post',
            data: { empresa_id: empresa },
            dataType: 'json'
        }).done(function (lista) {
            AplicarA.empleados = $.isArray(lista) ? lista : [];
            var hayNocturnos = $.grep(AplicarA.empleados, function (e) { return e.nocturno == 1; }).length > 0;
            // con nocturnos: "Solo a algunos" con los de turno normal marcados; sin nocturnos: "A todos"
            AplicarA.fnModo(hayNocturnos ? 'algunos' : 'todos', hayNocturnos ? 'normales' : 'todos');
        }).fail(function () {
            $('#aplicarNormales, #aplicarNocturnos').html('<div class="text-danger p-2">No se pudieron cargar los empleados.</div>');
        });
    },

    fnModo: function (modo, marcar) {
        AplicarA.modo = modo;
        $('.aplicar-modo').removeClass('activo').filter('[data-modo="' + modo + '"]').addClass('activo');
        AplicarA.fnPintar(modo === 'todos' ? 'todos' : marcar);
    },

    /** marcar: 'todos' | 'normales' | null (conservar lo marcado) */
    fnPintar: function (marcar) {
        var antes = {};
        $('#modalAplicarA .aplicar-fila.sel').each(function () { antes[$(this).attr('data-id')] = true; });
        var grupos = { normales: '', nocturnos: '' }, cuenta = { normales: 0, nocturnos: 0 };
        $.each(AplicarA.empleados, function (i, e) {
            var g = e.nocturno == 1 ? 'nocturnos' : 'normales';
            cuenta[g]++;
            var marcado = marcar === 'todos' || (marcar === 'normales' && g === 'normales') || (!marcar && antes[e.empleado_id]);
            grupos[g] += '<div class="aplicar-fila' + (marcado ? ' sel' : '') + '" data-id="' + AplicarA.fnEsc(e.empleado_id) + '" data-texto="' +
                AplicarA.fnEsc((e.empleado_id + ' ' + e.nombre + ' ' + (e.departamento || '') + ' ' + (e.horario || '')).toLowerCase()) + '">' +
                '<i class="fa ' + (marcado ? 'fa-check-square' : 'fa-square-o') + ' aplicar-caja"></i>' +
                '<div><b>' + AplicarA.fnEsc(e.empleado_id) + '</b> ' + AplicarA.fnEsc(e.nombre) +
                '<br><small class="text-muted">' + AplicarA.fnEsc(e.departamento || '') + ' · ' + (e.horario ? AplicarA.fnEsc(e.horario) : 'sin horario') + '</small></div></div>';
        });
        $('#aplicarNormales').html(grupos.normales || '<div class="text-muted p-2">No hay empleados de turno normal.</div>');
        $('#aplicarNocturnos').html(grupos.nocturnos || '<div class="text-muted p-2">No hay empleados de turno nocturno.</div>');
        $('#aplicarCuentaNormales').text(cuenta.normales);
        $('#aplicarCuentaNocturnos').text(cuenta.nocturnos);
        AplicarA.fnFiltrar();
        AplicarA.fnResumen();
    },

    fnMarcar: function ($fila, marcado) {
        $fila.toggleClass('sel', marcado).find('.aplicar-caja').attr('class', 'fa ' + (marcado ? 'fa-check-square' : 'fa-square-o') + ' aplicar-caja');
    },

    fnFiltrar: function () {
        var t = $.trim($('#aplicarBuscar').val()).toLowerCase();
        $('#modalAplicarA .aplicar-fila').each(function () {
            $(this).toggle(!t || $(this).attr('data-texto').indexOf(t) >= 0);
        });
    },

    fnResumen: function () {
        $.each({ Normales: '#aplicarTodosNormales', Nocturnos: '#aplicarTodosNocturnos' }, function (g, cab) {
            var $l = $('#aplicar' + g + ' .aplicar-fila');
            var todosSel = $l.length > 0 && $l.filter('.sel').length === $l.length;
            $(cab).toggleClass('sel', todosSel).find('.aplicar-caja').attr('class', 'fa ' + (todosSel ? 'fa-check-square' : 'fa-square-o') + ' aplicar-caja');
        });
        var normales = $('#aplicarNormales .aplicar-fila.sel').length;
        var nocturnos = $('#aplicarNocturnos .aplicar-fila.sel').length;
        // si quedan todos marcados se considera "A todos"
        var total = $('#modalAplicarA .aplicar-fila').length;
        if (AplicarA.modo === 'algunos' && total > 0 && normales + nocturnos === total) {
            AplicarA.modo = 'todos';
            $('.aplicar-modo').removeClass('activo').filter('[data-modo="todos"]').addClass('activo');
        }
        $('#aplicarResumen').html('Se asignará a <b>' + (normales + nocturnos) + '</b> empleado(s)' +
            (nocturnos > 0 ? ', <span class="text-warning"><b>' + nocturnos + '</b> de turno nocturno</span>' : '') + '.');
    },

    fnGuardar: function () {
        if (!AplicarA.alGuardar) return;
        if (AplicarA.modo === 'todos') {
            var noc = $.grep(AplicarA.empleados, function (e) { return e.nocturno == 1; }).length;
            if (noc > 0 && !confirm('También se les cambiará el horario a ' + noc + ' empleado(s) de turno nocturno. ¿Continuar?')) return;
            AplicarA.alGuardar(null);
            return;
        }
        var elegidos = $('#modalAplicarA .aplicar-fila.sel').map(function () { return $(this).attr('data-id'); }).get();
        if (elegidos.length === 0) { alert('Elige al menos un empleado.'); return; }
        AplicarA.alGuardar(elegidos);
    }
};

$(document).ready(function () {
    if (!$('#modalAplicarA').length) return;
    $('.aplicar-modo').on('click', function () {
        var modo = $(this).attr('data-modo');
        // "Solo a algunos" desde "A todos": se propone quitar a los nocturnos
        AplicarA.fnModo(modo, modo === 'algunos' && AplicarA.modo === 'todos' ? 'normales' : null);
    });
    $('#modalAplicarA').on('click', '.aplicar-fila', function () {
        if (AplicarA.modo === 'todos') { AplicarA.modo = 'algunos'; $('.aplicar-modo').removeClass('activo').filter('[data-modo="algunos"]').addClass('activo'); }
        AplicarA.fnMarcar($(this), !$(this).hasClass('sel'));
        AplicarA.fnResumen();
    });
    $('.aplicar-cab').on('click', function () {
        if (AplicarA.modo === 'todos') { AplicarA.modo = 'algunos'; $('.aplicar-modo').removeClass('activo').filter('[data-modo="algunos"]').addClass('activo'); }
        var lista = $(this).attr('data-grupo') === 'normales' ? '#aplicarNormales' : '#aplicarNocturnos';
        var marcar = !$(this).hasClass('sel');
        $(lista + ' .aplicar-fila:visible').each(function () { AplicarA.fnMarcar($(this), marcar); });
        AplicarA.fnResumen();
    });
    $('#aplicarBuscar').on('input', AplicarA.fnFiltrar);
    $('#aplicarGuardar').on('click', AplicarA.fnGuardar);
});
