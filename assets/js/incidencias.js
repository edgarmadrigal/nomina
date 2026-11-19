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
$("#formulario").validate();

function validar(){    
    var descripcion = $("#concepto_id").val()==""?false:true;
    var fechaInicio = $("#fechaInicio").val()==""?false:true;
    var fechaFin = $("#fechaFin").val()==""?false:true;

    if(descripcion==true && fechaInicio== true && fechaFin == true){
        return true;
    }else{
        return false;
    }
}

function editar(id) {    
    $('#id').val(id);   
    Modulo.fnCargarSelectsModal(id);
}    
function eliminar(id_delete,descripcion){ 
           swal({
                title: "Estas seguro?",
                text: "Se eliminara la incapacidad "+descripcion,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  Modulo.baseurl+'incidencia/delete',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            id: id_delete
                        }
                    }).done(function (data) {
                        if (data) {
                            // update the manageMemberTable
                            Modulo.fnObtenerDatos($('#selEmpleado').val());   
                        } else {                            
                            // update the manageMemberTable
                            Modulo.fnObtenerDatos($('#selEmpleado').val());   
                        }
                    });
                } else {
                    // update the manageMemberTable
                            Modulo.fnObtenerDatos($('#selEmpleado').val());   
                }
            });
}

Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    dtLunes: [],
    dtValidar:[]
    //Válida campos Obligatorios
    ,fnMandaCorreo(){
        $.ajax({
            url:  Modulo.baseurl+'incidencia/MandaCorreo',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
           //-------- $('#mensaje').val(data[0]);
        }); 
    }
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
        Modulo.dtLunes=null;
        lunes = $('#lunes').DataTable();
        $("#tablaLlunes").css("display", "none");
        $(".loadTable").css("display", "block");

        var empSel = parseInt($('#empresa_id').val()) || 0;

        var varURL = Modulo.baseurl + "incidencia/empleado";
        //
        $.ajax({
            url: varURL,
            data: {
                id: parseInt(em),
                empresa_id: empSel
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            if (data && data[0]) {
                $('#regimss').val(data[0].REG_IMSS);
                $('#fechaIngreso').text(data[0].FECHA_INGRESO ? ('Fecha de ingreso: ' + data[0].FECHA_INGRESO) : '');
            } else {
                $('#fechaIngreso').text('');
            }
        });

        var varURL = Modulo.baseurl + "incidencia/consultaIncidencias";
        //
        $.ajax({
            url: varURL,
            data: {
                id: parseInt(em),
                empresa_id: empSel
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            Modulo.dtLunes=data;
            Modulo.fnConstruirTabla();
        });
        
        
    }
    //Construye la tabla 
    , fnConstruirTabla: function () {

        lunes=$('#lunes').DataTable({
            colReorder: false,
            responsive: true,
            "order": [ 5, 'asc' ],     
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
            ,data: Modulo.dtLunes    
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.id;
                        }
                },    
                {
                    "aTargets": [1],
                    "mData": function (source, type, val) {
                        // las traídas de Microsip (vacaciones / incapacidades) se marcan
                        var partes = [], nombres = { cubreEntrada: 'Entrada', cubreEntradaComedor: 'Entrada comida', cubreSalidaComedor: 'Salida comida', cubreSalida: 'Salida' };
                        $.each(nombres, function (c, n) { if (String(source[c]) === '1') partes.push(n); });
                        var parcial = partes.length && partes.length < 4 ? ' <span class="badge badge-warning" style="color:#fff !important;" title="La incidencia cubre solo estas checadas">Solo: ' + partes.join(', ') + '</span>' : '';
                        return $('<div>').text(source.descripcion).html() + parcial +
                            (source.origenClave ? ' <span class="badge badge-info" style="color:#fff !important;" title="Traída de Microsip; si cambia allá, se actualiza aquí">Microsip</span>' : '');
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
                "aName": "Opciones", 
                "mData": function (source, type, val) {
                    return `<div class="btn-group"> 
                            <button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" 
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> Acciones <span class="caret"></span></button>
                            <ul class="dropdown-menu">
                            <li><a type="button" onclick="editar(`+source.id+`)" data-toggle="modal" data-target="#ModificarDia">Editar</a></li>
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+source.descripcion+`')">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }                    
                    ,title: 'HorarioLunes'  // titulo de excel
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioLunes'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioLunes'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioLunes'
                } ),
            ]
        });

        lunes.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        lunes.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        $("#tablaLlunes").css("display", "block");
        $(".loadTable").css("display", "none");
        
    }
    ,fnCargarDescripcionCorta(id){
        $('#descripcionCorta').empty();  
        $.ajax({
            url:  Modulo.baseurl+'incidencia/descripcionCorta',
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
    , fnCargarSelectsModal(id){
        $('#concepto_id').empty();  
        $.ajax({
            url:  Modulo.baseurl+'incidencia/concepto',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#concepto_id').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#concepto_id').append('<option value="'+value.id+'">'+value.descripcion+'</option>');
            });            
            if(id){
                $.ajax({
                    url: Modulo.baseurl+'incidencia/consultaIncidenciaEditar',
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
    , fnCargarSelects(){               
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPlanta";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#empresa_id').append('<option value="">Selecciona una planta</option>');
            $.each(perfiles, function( index, value ) {
                $('#empresa_id').append('<option value="'+value.id+'">'+value.nombre+'</option>');
                });
            Modulo.fnPreseleccionarURL();
        });
    }
    // Si se llegó con ?empresa=..&empleado=..&nombre=.. (p.ej. desde "VAC / hora"), preselecciona y carga.
    , fnPreseleccionarURL: function(){
        var p = new URLSearchParams(window.location.search);
        var empresa = p.get('empresa'), empleado = p.get('empleado'), nombre = p.get('nombre');
        if (!empleado) return;
        if (empresa && $('#empresa_id option[value="'+empresa+'"]').length) $('#empresa_id').val(empresa);
        var txt = (nombre ? nombre + ' - ' : '') + empleado;
        $('#selEmpleado').append(new Option(txt, empleado, true, true)).trigger('change.select2');
        Modulo.fnObtenerDatos(empleado);
        $("#asignar").css("display", "block");
    }
    //Load
    , Init: function () {
        Modulo.fnCargarSelects();
    }
}


$(document).ready(function () {

    //Load
    Modulo.Init();
    base_url="http://127.0.0.1:8080/nomina/";
    TablaVacia = "No se encontraron resultados";
    empleadosTabla=[];
    $('#id').val(0);
    var lunes = $('#lunes').DataTable();
    $('#lunes tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });

 $('#empresa_id').on('change', function() {

		$('#selEmpleado').on('change', function() {
			Modulo.fnObtenerDatos($('#selEmpleado').val());   
			
			$("#asignar").css("display", "block");
		});
    });
    
    $(document).on('click', '#correo', function (e) {
       Modulo.fnMandaCorreo();
    }); 

    $('#selEmpleado').on('change', function() {
        lunes = $('#lunes').DataTable();
        Modulo.fnObtenerDatos($('#selEmpleado').val()); 
        $("#asignar").css("display", "block");
    });

    $('#concepto_id').on('change', function() {
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
    });
    /*BOTON DE FORMULARIO... GUARDAR */
    $(document).on('click', '#btn_guardar', function (e) {
        e.preventDefault();
        var cubre = Cubre.fnLeer();
        if (!(cubre.cubreEntrada || cubre.cubreEntradaComedor || cubre.cubreSalidaComedor || cubre.cubreSalida)) {
            swal({ title: "Falta qué cubre", text: "Marca al menos una checada (entrada, comida o salida).", icon: "warning" });
            return;
        }
        if(validar()){
            var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: Modulo.baseurl+'incidencia/ActualizarIncidencia',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id'                : $('#id').val(),
                            'concepto_id'       : $('#concepto_id').val(),
                            'fechaInicio'       : $('#fechaInicio').val(),
                            'fechaFin'          : $('#fechaFin').val(),
                            'cubreEntrada'        : cubre.cubreEntrada,
                            'cubreEntradaComedor' : cubre.cubreEntradaComedor,
                            'cubreSalidaComedor'  : cubre.cubreSalidaComedor,
                            'cubreSalida'         : cubre.cubreSalida
                        }
                    }).done(function (data) {
                        if (data) {
                            swal({
                                    title: "Tus datos han sido actualizados",
                                    text: "Se actualizo correctamente",
                                    icon: "success",
                                })
                                .then((value) => {
                                        // hide the modal
                                        $("#ModificarDia").modal('hide');
                                        // update the manageMemberTable
                                        Modulo.fnObtenerDatos($('#selEmpleado').val());   
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
                                    text: "No se actualizo correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( Modulo.baseurl+'horariosusuario');
                                });
                        }
                    });
                }else{
                    ///GUARDAR NUEVO  
                    $.ajax({
                        url: Modulo.baseurl+'incidencia/GuardarIncidencia',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'empleado_id'       : $('#selEmpleado').val(),
                            'concepto_id'       : $('#concepto_id').val(),
                            'fechaInicio'       : $('#fechaInicio').val(),
                            'fechaFin'          : $('#fechaFin').val(),
                            'estatus'           : parseInt('1'),
                            'cubreEntrada'        : cubre.cubreEntrada,
                            'cubreEntradaComedor' : cubre.cubreEntradaComedor,
                            'cubreSalidaComedor'  : cubre.cubreSalidaComedor,
                            'cubreSalida'         : cubre.cubreSalida
                        }
                    }).done(function (data) {
                        if (data) {
                            swal({
                                    title: "Tus datos han sido agregados",
                                    text: "Se agrego correctamente",
                                    icon: "success",
                                })
                                .then((value) => {
                                    // hide the modal
                                    $("#ModificarDia").modal('hide');
                                    // update the manageMemberTable
                                    Modulo.fnObtenerDatos($('#selEmpleado').val());   
                                    Modulo.fnObtenerDatos($('#selEmpleado').val());   
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
                                    text: "No se agrego correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( Modulo.baseurl+'horariosusuario');
                                });
                        }
                    });
                }   
        }else{
            alert('Favor de completar el formulario');
        }
    });
});


var buttonCommon = {
    exportOptions: {
        format: {
            body: function ( data, row, column, node ) {
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

/**
 * "Incidencia a varios empleados" (incidencia/GuardarVarios): el mismo concepto, días y checadas que
 * cubre, para los empleados marcados de una o varias empresas, en un solo día o en varios.
 */
var Varios = {
    empleados: [],
    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    hoy: function () { var d = new Date(); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); },

    fnAbrir: function () {
        // Las empresas son las del combo de la pantalla (ya filtradas por los permisos del usuario)
        var actual = $('#empresa_id').val();
        $('#varEmpresas').html($.map($('#empresa_id option').get(), function (o) {
            if (!o.value) return null;
            return '<label class="var-chk"><input type="checkbox" class="varEmpresa" value="' + Varios.esc(o.value) + '"' +
                (o.value === actual ? ' checked' : '') + '> ' + Varios.esc($(o).text()) + '</label>';
        }).join('') || '<span class="text-muted">No tienes empresas asignadas.</span>');
        $('input[name="varModo"][value="uno"]').prop('checked', true);
        $('#varFi, #varFf').val(Varios.hoy());
        Varios.fnModo();
        $('.varCubre').prop('checked', true);
        $('#varBuscar').val('');
        $('#varTodos').prop('checked', false);
        Varios.fnAyuda();
        $('#varConcepto').html('<option value="0">Cargando…</option>');
        $.post(Modulo.baseurl + 'incidencia/concepto', {}, null, 'json').done(function (lista) {
            $('#varConcepto').html('<option value="0">Selecciona la incidencia</option>' +
                $.map(lista || [], function (c) { return '<option value="' + Number(c.id) + '" data-corto="' + Varios.esc(c.descripcionCorta) + '">' + Varios.esc(c.descripcion) + '</option>'; }).join(''));
        });
        Varios.empleados = [];
        Varios.fnCargar();
        $('#ModalVarios').modal('show');
    },

    /** Empleados activos de las empresas marcadas; conserva lo que ya estaba marcado. */
    fnCargar: function () {
        var empresas = $('.varEmpresa:checked').map(function () { return this.value; }).get();
        var marcados = $('#varLista .varEmp:checked').map(function () { return this.value; }).get();
        if (!empresas.length) { Varios.empleados = []; Varios.fnPintar(marcados); return; }
        $('#varLista').html('<div class="text-muted p-2">Cargando…</div>');
        var pedido = Varios.pedido = $.post(Modulo.baseurl + 'incidencia/empleadosEmpresa', { empresas: empresas }, null, 'json').done(function (lista) {
            if (pedido !== Varios.pedido) return;   // llegó tarde, ya se pidió otra combinación
            Varios.empleados = lista || [];
            Varios.fnPintar(marcados);
        });
    },

    fnPintar: function (marcados) {
        var varias = $('.varEmpresa:checked').length > 1, ok = {};
        $.each(marcados || [], function (i, v) { ok[v] = true; });
        var html = $.map(Varios.empleados, function (e) {
            var clave = e.empresa_id + '|' + e.numero;
            return '<label class="var-emp" data-texto="' + Varios.esc((e.numero + ' ' + e.nombre + ' ' + (e.puesto || '') + ' ' + (e.departamento || '') + ' ' + (e.empresa || '')).toUpperCase()) + '">' +
                '<input type="checkbox" class="varEmp" value="' + Varios.esc(clave) + '"' + (ok[clave] ? ' checked' : '') + '><span class="var-num">' + Varios.esc(e.numero) + '</span>' +
                '<span>' + Varios.esc(e.nombre) + '</span>' + (varias ? ' <span class="var-empr">' + Varios.esc(e.empresa) + '</span>' : '') +
                '<span class="var-det">' + Varios.esc(e.puesto || '') + (e.departamento ? ' · ' + Varios.esc(e.departamento) : '') + '</span></label>';
        }).join('');
        $('#varLista').html(html || '<div class="text-muted p-2">' + ($('.varEmpresa:checked').length ? 'No hay empleados activos en las empresas marcadas.' : 'Marca una o varias empresas.') + '</div>');
        Varios.fnFiltrar();
    },

    /** Un solo día: "Al" = "Del" y se oculta. */
    fnModo: function () {
        var uno = $('input[name="varModo"]:checked').val() === 'uno';
        $('#ModalVarios .var-al').toggle(!uno);
        $('#varFiTit').text(uno ? 'Día' : 'Del');
        if (uno || !$('#varFf').val() || $('#varFf').val() < $('#varFi').val()) $('#varFf').val($('#varFi').val());
        Varios.fnDias();
    },

    fnDias: function () {
        var fi = $('#varFi').val(), ff = $('#varFf').val();
        var n = fi && ff ? Math.round((new Date(ff) - new Date(fi)) / 86400000) + 1 : 0;
        $('#varDias').text(n > 1 ? n + ' días' : n === 1 ? '1 día' : '');
    },

    fnFiltrar: function () {
        var q = $.trim($('#varBuscar').val()).toUpperCase();
        $('#varLista .var-emp').each(function () { $(this).toggle(!q || String($(this).data('texto')).indexOf(q) >= 0); });
        Varios.fnCuenta();
    },

    fnCuenta: function () {
        var marcados = $('#varLista .varEmp:checked').length;
        var visibles = $('#varLista .var-emp:visible .varEmp');
        $('#varTodos').prop('checked', visibles.length > 0 && visibles.filter(':checked').length === visibles.length);
        $('#varCuenta').text(marcados + ' de ' + Varios.empleados.length + ' marcados');
    },

    fnAyuda: function () {
        var n = $('.varCubre:checked').length;
        $('#varCubreAyuda').text(n === 4 ? 'Todo el día: cubre las 4 checadas.' : n === 0 ? 'Marca al menos una checada.'
            : 'Solo las checadas marcadas llevan la incidencia; las demás se toman del checador.' +
              ($('.varCubre[value="cubreEntrada"]').is(':checked') ? ' Si llega tarde no cuenta retardo.' : ' El retardo se cuenta normal.'));
    },

    fnGuardar: function () {
        var cubre = {};
        $('.varCubre').each(function () { cubre[this.value] = this.checked ? 1 : 0; });
        var empleados = $('#varLista .varEmp:checked').map(function () { return this.value; }).get();
        if ($('input[name="varModo"]:checked').val() === 'uno') $('#varFf').val($('#varFi').val());
        if ($('#varFf').val() < $('#varFi').val()) { swal({ title: 'Revisa las fechas', text: 'El día final es antes del inicial.', icon: 'warning' }); return; }
        if ($('#varConcepto').val() === '0') { swal({ title: 'Falta la incidencia', text: 'Elige qué incidencia es.', icon: 'warning' }); return; }
        if (!$('#varFi').val() || !$('#varFf').val()) { swal({ title: 'Faltan las fechas', text: 'Elige del día y al día.', icon: 'warning' }); return; }
        if (!$('.varCubre:checked').length) { swal({ title: 'Falta qué cubre', text: 'Marca al menos una checada.', icon: 'warning' }); return; }
        if (!empleados.length) { swal({ title: 'Faltan empleados', text: 'Marca al menos un empleado.', icon: 'warning' }); return; }
        var btn = $('#varGuardar').prop('disabled', true);
        $.post(Modulo.baseurl + 'incidencia/GuardarVarios', $.extend({
            concepto_id: $('#varConcepto').val(),
            fechaInicio: $('#varFi').val(), fechaFin: $('#varFf').val(), empleados: empleados
        }, cubre), null, 'json').done(function (r) {
            if (!r.exito) { swal({ title: 'No se guardó', text: r.mensaje, icon: 'warning' }); return; }
            $('#ModalVarios').modal('hide');
            swal({ title: 'Listo', icon: 'success',
                   text: 'Se capturó la incidencia a ' + r.guardados + ' empleado(s).' +
                         (r.yaTenian ? '\n' + r.yaTenian + ' ya la tenían en esas fechas (no se duplicó).' : '') +
                         (r.noValidos ? '\n' + r.noValidos + ' no están activos en su empresa.' : '') });
            if ($('#selEmpleado').val() && $('#selEmpleado').val() !== '0') Modulo.fnObtenerDatos($('#selEmpleado').val());
        }).fail(function () { swal({ title: 'No se guardó', text: 'Error al guardar.', icon: 'error' }); })
          .always(function () { btn.prop('disabled', false); });
    }
};

$(function () {
    $('#btnVarios').on('click', Varios.fnAbrir);
    $('#varBuscar').on('input', Varios.fnFiltrar);
    $('#varTodos').on('change', function () {
        $('#varLista .var-emp:visible .varEmp').prop('checked', this.checked);
        Varios.fnCuenta();
    });
    $('#varLista').on('change', '.varEmp', Varios.fnCuenta);
    $('#ModalVarios').on('change', '.varCubre', Varios.fnAyuda);
    $('#varTodoDia').on('click', function (e) { e.preventDefault(); $('.varCubre').prop('checked', true); Varios.fnAyuda(); });
    // "Retardo ruta transporte" (tran) propone solo la entrada, igual que la captura de uno
    $('#varConcepto').on('change', function () {
        if ($(this).find('option:selected').data('corto') === 'tran') {
            $('.varCubre').prop('checked', false);
            $('.varCubre[value="cubreEntrada"]').prop('checked', true);
            Varios.fnAyuda();
        }
    });
    $('#varEmpresas').on('change', '.varEmpresa', Varios.fnCargar);
    $('#varEmpTodas, #varEmpNinguna').on('click', function (e) {
        e.preventDefault();
        $('.varEmpresa').prop('checked', this.id === 'varEmpTodas');
        Varios.fnCargar();
    });
    $('input[name="varModo"]').on('change', Varios.fnModo);
    $('#varFi').on('change', function () {
        if ($('input[name="varModo"]:checked').val() === 'uno' || !$('#varFf').val() || $('#varFf').val() < this.value) $('#varFf').val(this.value);
        Varios.fnDias();
    });
    $('#varFf').on('change', Varios.fnDias);
    $('#varGuardar').on('click', Varios.fnGuardar);
});
