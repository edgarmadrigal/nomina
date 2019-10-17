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

        var varURL = Modulo.baseurl + "incidencia/empleado"; 
        //
        $.ajax({
            url: varURL,
            data: {
                id: parseInt(em)
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            $('#regimss').val(data[0].REG_IMSS);
        });    
        
        var varURL = Modulo.baseurl + "incidencia/consultaIncidencias"; 
        //
        $.ajax({
            url: varURL,
            data: {
                id: parseInt(em)
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
                        return source.descripcion;
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
                            Modulo.fnCargarDescripcionCorta(parseInt(d[0].concepto_id));
                    }
                });
            }
        }); 
    }
    //Load
    , Init: function () {
       // Modulo.fnCargarSelects();
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
        Modulo.fnCargarSelectsModal();
    });
    /*BOTON DE FORMULARIO... GUARDAR */
    $(document).on('click', '#btn_guardar', function (e) {
        e.preventDefault();                       
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
                            'estatus'           : parseInt('1')
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