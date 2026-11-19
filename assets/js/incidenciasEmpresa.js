/**
 * ¿Qué cubre la incidencia? (incidencias.cubreEntrada / cubreEntradaComedor / cubreSalidaComedor / cubreSalida)
 * Todo el día = como siempre. Parcial: el código sale solo en esas checadas y las demás se respetan;
 * si cubre la entrada no hay retardo. "Retardo ruta transporte" (tran) propone solo la entrada.
 */
var Cubre = {
    campos: ['cubreEntrada', 'cubreEntradaComedor', 'cubreSalidaComedor', 'cubreSalida'],
    fnPoner: function (valores) {
        $.each(Cubre.campos, function (i, c) {
            var on = !valores || String(valores[c]) === '1' || valores[c] === true;
            $('#ModificarDia .cubre-op[data-campo="' + c + '"]').toggleClass('activo', on)
                .find('.fa').attr('class', 'fa ' + (on ? 'fa-check-square' : 'fa-square-o'));
        });
        Cubre.fnAyuda();
    },
    fnLeer: function () {
        var r = {};
        $.each(Cubre.campos, function (i, c) { r[c] = $('#ModificarDia .cubre-op[data-campo="' + c + '"]').hasClass('activo') ? 1 : 0; });
        return r;
    },
    fnAyuda: function () {
        var v = Cubre.fnLeer(), n = v.cubreEntrada + v.cubreEntradaComedor + v.cubreSalidaComedor + v.cubreSalida;
        $('#ModificarDia .cubre-ayuda').text(n === 4 ? 'Todo el día: cubre las 4 checadas (como siempre).'
            : n === 0 ? 'Marca al menos una checada.'
            : 'Solo esas checadas llevan la incidencia; las demás se toman del checador.' + (v.cubreEntrada ? ' Si llega tarde no cuenta retardo.' : ' El retardo se cuenta normal.'));
    },
    fnPorConcepto: function () {
        var corto = $.trim($('#descripcionCorta').val()).toLowerCase();
        Cubre.fnPoner(corto === 'tran' ? { cubreEntrada: 1, cubreEntradaComedor: 0, cubreSalidaComedor: 0, cubreSalida: 0 } : null);
    }
};
$(document).on('click', '#ModificarDia .cubre-op', function () {
    var on = !$(this).hasClass('activo');
    $(this).toggleClass('activo', on).find('.fa').attr('class', 'fa ' + (on ? 'fa-check-square' : 'fa-square-o'));
    Cubre.fnAyuda();
});
$(document).on('click', '#ModificarDia .cubre-todo', function (e) { e.preventDefault(); Cubre.fnPoner(null); });

/**
 * ¿A quién se aplica? Todos los activos de la planta o solo los elegidos.
 * Al editar se marca a quienes ya la tienen; al guardar se agrega/quita la diferencia.
 */
var Aplica = {
    empleados: [],
    esc: function (t) { return $('<div>').text(t == null ? '' : t).html(); },
    fnCargar: function (idIncidencia) {
        var empresa = $('#empresa_id').val();
        Aplica.empleados = [];
        Aplica.fnPonerModo('todos');
        $('#ModificarDia .aplica-buscar').val('');
        $('#ModificarDia .aplica-items').html('<em>Cargando empleados…</em>');
        Aplica.fnModo();
        var turno = Aplica.turno = (Aplica.turno || 0) + 1;   // si se abre otra incidencia antes de que conteste, se ignora la vieja
        var pPlanta = $.post(Modulo.baseurl + 'incidenciaEmpresa/empleadosPlanta', { empresa_id: empresa }, null, 'json');
        var pTiene = idIncidencia ? $.post(Modulo.baseurl + 'incidenciaEmpresa/empleadosIncidencia', { id: idIncidencia }, null, 'json') : $.Deferred().resolve([]).promise();
        $.when(pPlanta, pTiene).done(function (r1, r2) {
            if (turno !== Aplica.turno) return;
            // $.when con dos ajax entrega [datos, estado, xhr] por cada uno
            var planta = r1[0], tiene = idIncidencia ? r2[0] : [];
            var marcados = {};
            $.each(tiene || [], function (i, t) { marcados[String(t.empleado_id)] = true; });
            Aplica.empleados = planta || [];
            $('#ModificarDia .aplica-total').text(Aplica.empleados.length);
            var html = $.map(Aplica.empleados, function (e) {
                var texto = (e.empleado_id + ' ' + e.nombre + ' ' + (e.puesto || '') + ' ' + (e.departamento || '')).toLowerCase();
                return '<label class="aplica-item" data-texto="' + Aplica.esc(texto) + '"><input type="checkbox" value="' + Number(e.empleado_id) + '"' +
                    (!idIncidencia || marcados[String(e.empleado_id)] ? ' checked' : '') + '> <b>' + Number(e.empleado_id) + '</b> ' + Aplica.esc(e.nombre) +
                    ' <small>' + Aplica.esc([e.puesto, e.departamento].filter(Boolean).join(' · ')) + '</small></label>';
            }).join('');
            $('#ModificarDia .aplica-items').html(html || '<em>La planta no tiene empleados activos.</em>');
            // si no la tienen todos, la edición abre en "Elegir empleados"
            if (idIncidencia && Object.keys(marcados).length < Aplica.empleados.length) {
                Aplica.fnPonerModo('elegir');
            }
            Aplica.fnFiltrar();
            Aplica.fnModo();
        }).fail(function () { $('#ModificarDia .aplica-items').html('<em>No se pudieron cargar los empleados.</em>'); });
    },
    fnModo: function () {
        $('#ModificarDia .aplica-lista').toggle(Aplica.fnLeerModo() === 'elegir');
        Aplica.fnCuenta();
    },
    fnPonerModo: function (modo) { $('#ModificarDia .aplica-op').each(function () { $(this).toggleClass('activo', $(this).data('modo') === modo); }); },
    fnLeerModo: function () { return $('#ModificarDia .aplica-op.activo').data('modo') || 'todos'; },
    fnElegidos: function () { return $('#ModificarDia .aplica-items input:checked').map(function () { return this.value; }).get(); },
    fnFiltrar: function () {
        var q = $.trim($('#ModificarDia .aplica-buscar').val()).toLowerCase();
        $('#ModificarDia .aplica-item').each(function () { $(this).toggleClass('aplica-oculto', !!q && $(this).attr('data-texto').indexOf(q) < 0); });
    },
    fnCuenta: function () { $('#ModificarDia .aplica-cuenta').text(Aplica.fnElegidos().length + ' de ' + Aplica.empleados.length + ' elegidos'); },
    fnDatos: function () { return { modo: Aplica.fnLeerModo(), empleados: Aplica.fnElegidos().join(',') }; }
};
$(document).on('click', '#ModificarDia .aplica-op', function () { Aplica.fnPonerModo($(this).data('modo')); Aplica.fnModo(); });
$(document).on('change', '#ModificarDia .aplica-items input', Aplica.fnCuenta);
$(document).on('input', '#ModificarDia .aplica-buscar', Aplica.fnFiltrar);
$(document).on('click', '#ModificarDia .aplica-marcar, #ModificarDia .aplica-desmarcar', function (e) {
    e.preventDefault();
    $('#ModificarDia .aplica-item:not(.aplica-oculto) input').prop('checked', $(this).hasClass('aplica-marcar'));
    Aplica.fnCuenta();
});

/** Ver quiénes tienen la incidencia; se puede quitar a uno o ir a editar la lista. */
function verEmpleados(id, titulo) {
    $('#VerEmpleados').data('id', id).modal('show');
    $('#VerEmpleados .ver-titulo').text(titulo || '');
    $('#VerEmpleados .ver-buscar').val('');
    $('#VerEmpleados tbody').html('<tr><td colspan="5"><em>Cargando…</em></td></tr>');
    $.post(Modulo.baseurl + 'incidenciaEmpresa/empleadosIncidencia', { id: id }, null, 'json').done(function (d) {
        $('#VerEmpleados .ver-cuenta').text((d || []).length + ' empleado(s) activos la tienen');
        $('#VerEmpleados tbody').html($.map(d || [], function (e) {
            var texto = (e.empleado_id + ' ' + e.nombre + ' ' + (e.puesto || '') + ' ' + (e.departamento || '')).toLowerCase();
            return '<tr data-texto="' + Aplica.esc(texto) + '"><td>' + Number(e.empleado_id) + '</td><td>' + Aplica.esc(e.nombre) + '</td><td>' + Aplica.esc(e.puesto) +
                '</td><td>' + Aplica.esc(e.departamento) + '</td><td><button type="button" class="btn btn-sm btn-outline-danger ver-quitar" data-copia="' + Number(e.copia_id) +
                '" title="Quitarle la incidencia solo a este empleado"><i class="fa fa-times"></i> Quitar</button></td></tr>';
        }).join('') || '<tr><td colspan="5"><em>Nadie tiene esta incidencia.</em></td></tr>');
    }).fail(function () { $('#VerEmpleados tbody').html('<tr><td colspan="5">No se pudo consultar.</td></tr>'); });
}
$(document).on('input', '#VerEmpleados .ver-buscar', function () {
    var q = $.trim(this.value).toLowerCase();
    $('#VerEmpleados tbody tr[data-texto]').each(function () { $(this).toggle(!q || $(this).attr('data-texto').indexOf(q) >= 0); });
});
$(document).on('click', '#VerEmpleados .ver-quitar', function () {
    var btn = $(this), id = $('#VerEmpleados').data('id');
    btn.prop('disabled', true);
    $.post(Modulo.baseurl + 'incidenciaEmpresa/quitarEmpleado', { id: id, copia_id: btn.data('copia') }, null, 'json').done(function (r) {
        if (r && r.exito) { btn.closest('tr').remove(); $('#VerEmpleados .ver-cuenta').text($('#VerEmpleados tbody tr[data-texto]').length + ' empleado(s) activos la tienen'); Modulo.fnObtenerDatos($('#empresa_id').val()); }
        else { btn.prop('disabled', false); swal('No se pudo', 'No se pudo quitar la incidencia.', 'warning'); }
    }).fail(function () { btn.prop('disabled', false); });
});
$(document).on('click', '#VerEmpleados .ver-editar', function () {
    var id = $('#VerEmpleados').data('id');
    $('#VerEmpleados').modal('hide');
    editar(id);
    $('#ModificarDia').modal('show');
});

jQuery.extend(jQuery.validator.messages, {
    required: "Campo obligatorio.",
    remote: "Please fix this field.",
    email: "Please enter a valid email address.",
    url: "Please enter a valid URL.",
    date: "Please enter a valid date.",
    dateISO: "Please enter a valid date (ISO).",
    number: "Please enter a valid number.",
    digits: "Please enter only digits.",
    creditcard: "Please enter a valid credit card number.",
    equalTo: "Please enter the same value again.",
    accept: "Please enter a value with a valid extension.",
    maxlength: jQuery.validator.format("Please enter no more than {0} characters."),
    minlength: jQuery.validator.format("Please enter at least {0} characters."),
    rangelength: jQuery.validator.format("Please enter a value between {0} and {1} characters long."),
    range: jQuery.validator.format("Please enter a value between {0} and {1}."),
    max: jQuery.validator.format("Please enter a value less than or equal to {0}."),
    min: jQuery.validator.format("Please enter a value greater than or equal to {0}.")
});
// $("#formulario").validate(); -- #formulario es un fieldset, no un form: jquery.validate tronaba en cada focus del modal

function validar() {
    var descripcion = $("#concepto_id").val() == "" ? false : true;
    var fechaInicio = $("#fechaInicio").val() == "" ? false : true;
    var fechaFin = $("#fechaFin").val() == "" ? false : true;

    if (descripcion == true && fechaInicio == true && fechaFin == true) {
        return true;
    } else {
        return false;
    }
}

function editar(id) {
    $('#id').val(id);
    Modulo.fnCargarSelectsModal(id);
    Aplica.fnCargar(id);
}
function eliminar(id_delete, descripcion) {
    swal({
        title: "Estas seguro?",
        text: "Se eliminará la incidencia " + descripcion + " de la empresa y de todos sus empleados.",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
        .then((willDelete) => {
            if (!willDelete) return;
            $.ajax({
                url: Modulo.baseurl + 'incidenciaEmpresa/delete',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_delete
                }
            }).done(function (data) {
                swal(data.exito ? 'Listo' : 'No se pudo', data.mensaje, data.exito ? 'success' : 'warning');
            }).fail(function () {
                swal('No se pudo', 'No se pudo eliminar la incidencia.', 'error');
            }).always(function () {
                Modulo.fnObtenerDatos($('#empresa_id').val());
            });
        });
}

Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    dtLunes: [],
    dtValidar: []
    //Válida campos Obligatorios
    , fnValidarObligatorios: function () {
        var estatusValidacion =

            $("#formSolicitud").valid({
                highlight: function (element) {
                    $(element).closest('.control-group').removeClass('valid').addClass('error');
                },
                success: function (element) {
                    $(element).closest('.control-group').removeClass('error');
                    $(element).remove();
                },
                errorPlacement: function (error, element) {
                    element.parent().append(error);
                }
            });
        return estatusValidacion;
    }
    //Obtiene los datos
    , fnObtenerDatos: function (em) {
        $('#lunes').empty();
        $('#lunes').DataTable();
        Modulo.dtLunes = null;
        lunes = $('#lunes').DataTable();
        $("#tablaLlunes").css("display", "none");
        $(".loadTable").css("display", "block");

        var varURL = Modulo.baseurl + "incidenciaEmpresa/consultaIncidenciasEmpresa";
        //
        $.ajax({
            url: varURL,
            data: {
                id: parseInt(em)
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            Modulo.dtLunes = data;
            Modulo.fnConstruirTabla();
        });


    }
    //Construye la tabla 
    , fnConstruirTabla: function () {
        lunes = $('#lunes').DataTable({
            colReorder: false,
            responsive: true,
            "order": [3, 'desc'],
            "bDestroy": true,
            "scrollX": false,//"100%",
            "draw": true,
            "oLanguage": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "_MENU_ Registro por página",
                "zeroRecords": "No se encontraron resultados",
                //"sEmptyTable": " " + TablaVacia,
                "sEmptyTable": "No se encontraron resultados en la Base de Datos",
                "sInfo": "Listado del _START_ al _END_ de _TOTAL_",
                "sInfoEmpty": "No se encontraron resultados",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix": "",
                "sSearch": "Filtrar:",
                "sUrl": "",
                "sInfoThousands": ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            }
            , data: Modulo.dtLunes
            , "aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.id;
                    }
                },
                {
                    "aTargets": [1],
                    "mData": function (source, type, val) {
                        var partes = [], nombres = { cubreEntrada: 'Entrada', cubreEntradaComedor: 'Entrada comida', cubreSalidaComedor: 'Salida comida', cubreSalida: 'Salida' };
                        $.each(nombres, function (c, n) { if (String(source[c]) === '1') partes.push(n); });
                        return $('<div>').text(source.descripcion).html() + (partes.length && partes.length < 4
                            ? ' <span class="badge badge-warning" style="color:#fff !important;" title="La incidencia cubre solo estas checadas">Solo: ' + partes.join(', ') + '</span>' : '');
                    }
                },
                {
                    "aTargets": [2],
                    "mData": function (source, type, val) {
                        return source.descripcionCorta.toUpperCase();
                    }
                },
                {
                    "aTargets": [3],
                    "mData": function (source, type, val) {
                        return source.fechaInicio;
                    }
                },
                {
                    "aTargets": [4],
                    "mData": function (source, type, val) {
                        return source.fechaFin;
                    }
                },

                {
                    "aTargets": [5],
                    "mData": function (source, type, val) {
                        var n = Number(source.aplicados) || 0, total = Number(source.activosPlanta) || 0;
                        if (type !== 'display') return n;
                        var desc = $('<div>').text(source.descripcion || '').html().replace(/'/g, '&#39;');
                        return '<span class="inc-empleados">' + (n >= total && total > 0 ? 'Todos (' + n + ')' : n + ' de ' + total) +
                               ' <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="verEmpleados(' + Number(source.id) + ', this.getAttribute(\'data-desc\'))" data-desc="' + desc +
                               ' ' + $('<div>').text(source.fechaInicio).html() + '" title="Ver quiénes la tienen"><i class="fa fa-users"></i> Ver</button></span>';
                    }
                },
                {
                    "aTargets": [6],
                    "aName": "Opciones",
                    "mData": function (source, type, val) {
                        // botones directos (antes era un menú desplegable que a veces no dejaba dar clic en Eliminar)
                        var desc = $('<div>').text(source.descripcion || '').html().replace(/'/g, '&#39;');
                        return '<button type="button" class="btn btn-sm btn-info mr-1" style="color:#fff !important;" onclick="editar(' + Number(source.id) + ')" ' +
                               'data-toggle="modal" data-target="#ModificarDia" title="Editar"><i class="fa fa-pencil"></i> Editar</button>' +
                               '<button type="button" class="btn btn-sm btn-danger" style="color:#fff !important;" onclick="eliminar(' + Number(source.id) + ', this.getAttribute(\'data-desc\'))" ' +
                               'data-desc="' + desc + '" title="Eliminar para toda la empresa"><i class="fa fa-trash"></i> Eliminar</button>';
                    }
                },
            ]
            , "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            , buttons: [
                $.extend(true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                    , title: 'HorarioLunes'  // titulo de excel
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                    , title: 'HorarioLunes'
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                    , title: 'HorarioLunes'
                }),
                $.extend(true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                    , title: 'HorarioLunes'
                }),
            ]
        });

        lunes.columns().every(function () {
            var that = this;
            $('input', this.header()).on('keyup change', function () {
                if (that.search() !== this.value) {
                    that
                        .search(this.value)
                        .draw();
                }
            });
        });
        lunes.columns().every(function () {
            var that = this;

            $('input', this.footer()).on('keyup change', function () {
                if (that.search() !== this.value) {
                    that
                        .search(this.value)
                        .draw();
                }
            });
        });
        $("#tablaLlunes").css("display", "block");
        $(".loadTable").css("display", "none");

    }
    , fnCargarDescripcionCorta(id) {
        $('#descripcionCorta').empty();
        $.ajax({
            url: Modulo.baseurl + 'incidencia/descripcionCorta',
            type: 'post',
            data: {
                id: parseInt(id)
            },
            dataType: 'json'
        }).done(function (data) {
            $('#descripcionCorta').val(data[0].descripcionCorta.toUpperCase());
            if (!(parseInt($('#id').val()) > 0)) Cubre.fnPorConcepto();
        });
    }
    //Carga los selects
    , fnCargarSelectsModal(id) {
        $('#concepto_id').empty();
        $.ajax({
            url: Modulo.baseurl + 'incidencia/concepto',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles = JSON.parse(JSON.stringify(data));
            $('#concepto_id').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function (index, value) {
                $('#concepto_id').append('<option value="' + value.id + '">' + value.descripcion + '</option>');
            });
            if (id) {
                $.ajax({
                    url: Modulo.baseurl + 'incidencia/consultaIncidenciaEditar',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        id: parseInt(id)
                    }
                }).done(function (d) {
                    if (d) {
                        $('#concepto_id').val(d[0].concepto_id);
                        $('#fechaInicio').val(d[0].fechaInicio);
                        $('#fechaFin').val(d[0].fechaFin);
                        Cubre.fnPoner(d[0]);
                        Modulo.fnCargarDescripcionCorta(parseInt(d[0].concepto_id));
                    }
                });
            }
        });
    }
    //Carga los selects
    , fnCargarSelects() {
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPlanta";
        $.ajax({
            url: varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles = JSON.parse(JSON.stringify(data));
            $('#empresa_id').append('<option value="">Selecciona una planta</option>');
            $.each(perfiles, function (index, value) {
                $('#empresa_id').append('<option value="' + value.id + '">' + value.nombre + '</option>');
            });
        });
    }
    //Load
    , Init: function () {
        Modulo.fnCargarSelects();
    }
}


$(document).ready(function () {

    //Load
    Modulo.Init();
    base_url = "";
    TablaVacia = "No se encontraron resultados";
    empleadosTabla = [];
    $('#id').val(0);
    var lunes = $('#lunes').DataTable();
    $('#lunes tfoot th').each(function () {
        var title = $(this).text();
        if (title != "Opciones") {
            $(this).html(title + '<br><input type="text" placeholder="Buscar ' + title + '" />');
        } else {
            $(this).html(title);
        }
    });

    $('#empresa_id').on('change', function () {

        Modulo.fnObtenerDatos($('#empresa_id').val());

        $("#asignar").css("display", "block");
    });

    $('#concepto_id').on('change', function () {
        Modulo.fnCargarDescripcionCorta($('#concepto_id').val());
    });

    /* BOTON DE LA TABLA ... AGREGAR  */
    $(document).on('click', '#nuevo', function (e) {
        $('#id').val(0);
        $('#concepto_id').val('');
        $('#descripcionCorta').val('');
        $('#fechaInicio').val('');
        $('#fechaFin').val('');
        Cubre.fnPoner(null);
        Modulo.fnCargarSelectsModal();
        Aplica.fnCargar(0);
    });
    /*BOTON DE FORMULARIO... GUARDAR */
    $(document).on('click', '#btn_guardar', function (e) {
        e.preventDefault();
        var cubre = Cubre.fnLeer();
        if (!(cubre.cubreEntrada || cubre.cubreEntradaComedor || cubre.cubreSalidaComedor || cubre.cubreSalida)) {
            swal({ title: "Falta qué cubre", text: "Marca al menos una checada (entrada, comida o salida).", icon: "warning" });
            return;
        }
        var aplica = Aplica.fnDatos();
        if (aplica.modo === 'elegir' && !aplica.empleados) {
            swal({ title: "Faltan empleados", text: "Elige al menos un empleado o marca \"Todos\".", icon: "warning" });
            return;
        }
        if (validar()) {
            var id = $('#id').val();
            if (id > 0) {  ///GUARDAR EDICION
                $.ajax({
                    url: Modulo.baseurl + 'incidenciaEmpresa/ActualizarIncidencia',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        'id': $('#id').val(),
                        'concepto_id': $('#concepto_id').val(),
                        'fechaInicio': $('#fechaInicio').val(),
                        'fechaFin': $('#fechaFin').val(), 'cubreEntrada': cubre.cubreEntrada, 'cubreEntradaComedor': cubre.cubreEntradaComedor, 'cubreSalidaComedor': cubre.cubreSalidaComedor, 'cubreSalida': cubre.cubreSalida,
                        'modo': aplica.modo, 'empleados': aplica.empleados
                    }
                }).done(function (data) {
                    if (data && data.exito) {
                        swal({
                            title: "Tus datos han sido actualizados",
                            text: data.mensaje,
                            icon: "success",
                        })
                            .then((value) => {
                                // hide the modal
                                $("#ModificarDia").modal('hide');
                                // update the manageMemberTable
                                Modulo.fnObtenerDatos($('#empresa_id').val());
                                $("#fechaInicio").datepicker({
                                    // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
                                    // daysOfWeekDisabled: [0,2,3,4,5, 6], 
                                    dateFormat: "yyyy-mm-dd",
                                    autoclose: true,
                                    todayHighlight: true,
                                    language: 'es',
                                }).datepicker('update', new Date());

                                $("#fechaFin").datepicker({
                                    // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
                                    // daysOfWeekDisabled: [0,2,3,4,5, 6], 
                                    dateFormat: "yyyy-mm-dd",
                                    autoclose: true,
                                    todayHighlight: true,
                                    language: 'es',
                                }).datepicker('update', new Date());
                            });
                    } else {
                        swal({
                            title: "Tus datos no han sido actualizados!",
                            text: (data && data.mensaje) || "No se actualizo correctamente",
                            icon: "warning",
                        })
;
                    }
                });
            } else {
                ///GUARDAR NUEVO  
                $.ajax({
                    url: Modulo.baseurl + 'incidenciaEmpresa/GuardarIncidencia',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        'concepto_id': $('#concepto_id').val(),
                        'fechaInicio': $('#fechaInicio').val(),
                        'fechaFin': $('#fechaFin').val(), 'cubreEntrada': cubre.cubreEntrada, 'cubreEntradaComedor': cubre.cubreEntradaComedor, 'cubreSalidaComedor': cubre.cubreSalidaComedor, 'cubreSalida': cubre.cubreSalida,
                        'empresa_id': $('#empresa_id').val(),
                        'estatus': parseInt('1'),
                        'modo': aplica.modo, 'empleados': aplica.empleados
                    }
                }).done(function (data) {
                    if (data && data.exito) {

                        Modulo.fnObtenerDatos($('#empresa_id').val());
                        swal({
                            title: "Tus datos han sido agregados",
                            text: data.mensaje,
                            icon: "success",
                        })
                            .then((value) => {
                                // hide the modal
                                $("#ModificarDia").modal('hide');
                                
                        // update the manageMemberTable
                        Modulo.fnObtenerDatos($('#empresa_id').val());
                                // update the manageMemberTable
                                $("#fechaInicio").datepicker({
                                    // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
                                    // daysOfWeekDisabled: [0,2,3,4,5, 6], 
                                    dateFormat: "yyyy-mm-dd",
                                    autoclose: true,
                                    todayHighlight: true,
                                    language: 'es',
                                }).datepicker('update', new Date());

                                $("#fechaFin").datepicker({
                                    // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
                                    // daysOfWeekDisabled: [0,2,3,4,5, 6], 
                                    dateFormat: "yyyy-mm-dd",
                                    autoclose: true,
                                    todayHighlight: true,
                                    language: 'es',
                                }).datepicker('update', new Date());
                            });
                    } else {
                        swal({
                            title: "Tus datos no han sido actualizados!",
                            text: (data && data.mensaje) || "No se agrego correctamente",
                            icon: "warning",
                        })
;
                    }
                });
            }
        } else {
            alert('Favor de completar el formulario');
        }
    });
});


var buttonCommon = {
    exportOptions: {
        format: {
            body: function (data, row, column, node) {
                return data;
            }
        }
    }
};
var table;
var id_edit;
$.extend(jQuery.fn.dataTableExt.oSort, {
    "date-uk-pre": function (a) {
        var x;
        try {
            var dateA = a.replace(/ /g, '').split("/");
            var day = parseInt(dateA[0], 10);
            var month = parseInt(dateA[1], 10);
            var year = parseInt(dateA[2], 10);
            var date = new Date(year, month - 1, day)
            x = date.getTime();
        }
        catch (err) {
            x = new Date().getTime();
        }

        return x;
    },

    "date-uk-asc": function (a, b) {
        return a - b;
    },

    "date-uk-desc": function (a, b) {
        return b - a;
    }
});
