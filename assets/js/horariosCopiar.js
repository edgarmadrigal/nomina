// "Copiar horario de...": asigna el horario vigente de un empleado a uno o varios empleados.
var CopiaHorario = {
    base: '',
    dias: [['lunes', 'Lunes'], ['martes', 'Martes'], ['miercoles', 'Miércoles'], ['jueves', 'Jueves'],
           ['viernes', 'Viernes'], ['sabado', 'Sábado'], ['domingo', 'Domingo']],
    plantasCargadas: false,

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    /** select2 de empleados de la planta elegida (mismo endpoint que la pantalla). */
    fnSelectEmpleados: function ($sel, $planta, multiple) {
        $sel.select2({
            dropdownParent: $('#modalCopiarHorario'),
            multiple: multiple,
            placeholder: multiple ? 'Busca y agrega empleados' : 'Busca un empleado',
            ajax: {
                url: CopiaHorario.base + 'horariosusuario/consultaEmpleado',
                type: 'post',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { searchTerm: params.term, empresa_id: $planta.val() }; },
                processResults: function (response) { return { results: response }; },
                cache: true
            }
        });
    },

    fnAbrir: function () {
        if (!CopiaHorario.plantasCargadas) {
            $.ajax({ url: CopiaHorario.base + 'checadasBiostar/consultaPlanta', type: 'post', dataType: 'json' }).done(function (data) {
                var html = '<option value="">Selecciona una planta</option>';
                $.each(data, function (i, p) { html += '<option value="' + p.id + '">' + CopiaHorario.fnEsc(p.nombre) + '</option>'; });
                $('#copiaPlantaOrigen, #copiaPlantaDestino').html(html);
                CopiaHorario.plantasCargadas = true;
                CopiaHorario.fnPrellenar();
            });
            CopiaHorario.fnSelectEmpleados($('#copiaOrigen'), $('#copiaPlantaOrigen'), false);
            CopiaHorario.fnSelectEmpleados($('#copiaDestinos'), $('#copiaPlantaDestino'), true);
        } else {
            CopiaHorario.fnPrellenar();
        }
        $('#copiaOrigen').val(null).trigger('change');
        $('#copiaDestinos').val(null).trigger('change');
        $('#copiaHorarioOrigen').html('');
        var hoy = new Date();
        $('#copiaFecha').val(hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2));
        $('#modalCopiarHorario').modal('show');
    },

    /** Si en la pantalla ya hay planta / empleado elegidos, se usan como origen. */
    fnPrellenar: function () {
        var planta = $('#empresa_id').val();
        if (planta) $('#copiaPlantaOrigen, #copiaPlantaDestino').val(planta);
        var emp = $('#selEmpleado').val();
        if (emp && emp !== '0') {
            var texto = $('#selEmpleado option:selected').text() || emp;
            $('#copiaOrigen').append(new Option(texto, emp, true, true)).trigger('change');
        }
    },

    fnMostrarOrigen: function () {
        var id = $('#copiaOrigen').val();
        if (!id) { $('#copiaHorarioOrigen').html(''); return; }
        $('#copiaHorarioOrigen').html('<i class="fa fa-spinner fa-spin"></i> Consultando horario...');
        $.ajax({
            url: CopiaHorario.base + 'horariosusuario/horarioVigente',
            type: 'post',
            data: { empleado_id: id },
            dataType: 'json'
        }).done(function (r) {
            if (!r.exito) {
                $('#copiaHorarioOrigen').html('<div class="alert alert-warning mb-0">' + CopiaHorario.fnEsc(r.mensaje) + '</div>');
                return;
            }
            var h = r.horario, cab = '', fila = '';
            $.each(CopiaHorario.dias, function (i, d) {
                cab += '<th>' + d[1] + '</th>';
                fila += '<td><small>' + CopiaHorario.fnEsc(h[d[0]] || '—') + (h[d[0] + 'Horas'] ? '<br><b>' + CopiaHorario.fnEsc(h[d[0] + 'Horas']) + '</b>' : '') + '</small></td>';
            });
            $('#copiaHorarioOrigen').html('<small class="text-muted">Horario vigente desde ' + CopiaHorario.fnEsc(h.fechaAsignacion) + ':</small>' +
                '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 text-center"><thead><tr>' + cab +
                '</tr></thead><tbody><tr>' + fila + '</tr></tbody></table></div>');
        });
    },

    fnCopiar: function () {
        var datos = {
            origen: $('#copiaOrigen').val(),
            destinos: $('#copiaDestinos').val() || [],
            empresa_id: $('#copiaPlantaDestino').val(),
            fechaAsignacion: $('#copiaFecha').val()
        };
        if (!datos.origen) { alert('Selecciona el empleado origen.'); return; }
        if (!datos.empresa_id || datos.destinos.length === 0) { alert('Selecciona la planta y al menos un empleado destino.'); return; }
        if (!datos.fechaAsignacion) { alert('Indica la fecha de asignación.'); return; }
        if (!confirm('¿Asignar este horario a ' + datos.destinos.length + ' empleado(s) a partir del ' + datos.fechaAsignacion + '?')) return;

        var $btn = $('#btnConfirmarCopia').prop('disabled', true);
        $.ajax({
            url: CopiaHorario.base + 'horariosusuario/copiarHorario',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (r) {
            if (typeof swal === 'function') swal(r.exito ? 'Listo' : 'Revisa', r.mensaje, r.exito ? 'success' : 'warning');
            else alert(r.mensaje);
            if (r.exito) {
                $('#modalCopiarHorario').modal('hide');
                // si el empleado que se ve en pantalla recibió el horario, refrescarlo
                var emp = $('#selEmpleado').val();
                if (emp && r.copiados && r.copiados.indexOf(String(emp)) >= 0 && typeof Modulo !== 'undefined') {
                    Modulo.fnObtenerDatos(emp);
                }
            }
        }).fail(function () {
            alert('Error al copiar el horario.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }
};

$(document).ready(function () {
    $('#btnCopiarHorario').on('click', CopiaHorario.fnAbrir);
    $('#copiaOrigen').on('change', CopiaHorario.fnMostrarOrigen);
    $('#copiaPlantaOrigen').on('change', function () { $('#copiaOrigen').val(null).trigger('change'); });
    $('#copiaPlantaDestino').on('change', function () { $('#copiaDestinos').val(null).trigger('change'); });
    $('#btnConfirmarCopia').on('click', CopiaHorario.fnCopiar);
});
