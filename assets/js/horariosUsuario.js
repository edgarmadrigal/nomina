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
    var horarioLunes = $("#horarioLunes").val()==0?false:true;
    var horarioMartes = $("#horarioMartes").val()==0?false:true;
    var horarioMiercoles = $("#horarioMiercoles").val()==0?false:true;
    var horarioJueves = $("#horarioJueves").val()==0?false:true;
    var horarioViernes = $("#horarioViernes").val()==0?false:true;
    var horarioSabado = $("#horarioSabado").val()==0?false:true;
    var horarioDomingo = $("#horarioDomingo").val()==0?false:true;
    var cantidad = $("#cantidad").val()==""?false:true;
    

    if(horarioLunes==true && horarioMartes== true && horarioMiercoles == true 
        && horarioJueves== true && horarioViernes == true && horarioSabado== true 
        && horarioDomingo == true  && cantidad == true 
        ){
            
        return true;
    }else{
        return false;
    }
}

function editar(id) {    
    $('#id').val(id);   
    Modulo.fnCargarSelectsModal(id);
}    
function eliminar(id_delete,horario,dia){ 
    var varURL = Modulo.baseurl + "horariosusuario/consultaHorario"; 
    $.ajax({
        url: varURL,
        data: {
            empleado_id: parseInt($('#selEmpleado').val()),
            dia:parseInt(dia)
        },
        type: 'post',
    }).done(function (data) {        
        Modulo.dtValidar=JSON.parse(data);
        if(Modulo.dtValidar.length>1){
            swal({
                title: "Estas seguro?",
                text: "Se eliminara el horario "+horario,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  Modulo.baseurl+'horariosusuario/delete',
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
        }else{
            alert('no puedes dejar a un empleado sin horario!');
        }
    }); 
}

Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    dtLunes: [],
    dtMartes: [],
    dtMiercoles: [],
    dtJueves: [],
    dtViernes: [],
    dtSabado: [],
    dtDomingo: [],
    dtValidar:[]
    //Válida campos Obligatorios
    , fnValidarObligatorios: function () {
    var estatusValidacion =$("#formSolicitud").valid({
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
        $("#tablaLlunes").css("display", "none");
        $("#tablaLMartes").css("display", "none");
        $("#tablaLMiercoles").css("display", "none");
        $("#tablaLJueves").css("display", "none");
        $("#tablaLViernes").css("display", "none");
        $("#tablaLSabado").css("display", "none");
        $("#tablaLDomingo").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "horariosusuario/consultaHorario"; 
        //Lunes
        $.ajax({
            url: varURL,
            data: {
                empleado_id: parseInt(em),
                dia:parseInt(1)
            },
            type: 'post',
        }).done(function (data) {
            Modulo.dtLunes=JSON.parse(data);
                //Martes
                $.ajax({
                    url: varURL,
                    data: {
                        empleado_id: parseInt(em),
                        dia:parseInt(2)
                    },
                    type: 'post',
                }).done(function (data) {                        
                    Modulo.dtMartes=JSON.parse(data);//Miercoles
                        $.ajax({
                            url: varURL,
                            data: {
                                empleado_id: parseInt(em),
                                dia:parseInt(3)
                            },
                            type: 'post',
                        }).done(function (data) {
                            Modulo.dtMiercoles=JSON.parse(data);
                                //Jueves
                                $.ajax({
                                    url: varURL,
                                    data: {
                                        empleado_id: parseInt(em),
                                        dia:parseInt(4)
                                    },
                                    type: 'post',
                                }).done(function (data) {                 
                                    Modulo.dtJueves=JSON.parse(data);
                                        //Viernes
                                        $.ajax({
                                            url: varURL,
                                            data: {
                                                empleado_id: parseInt(em),
                                                dia:parseInt(5)
                                            },
                                            type: 'post',
                                        }).done(function (data) {                 
                                            Modulo.dtViernes=JSON.parse(data);
                                                //Sabado
                                                $.ajax({
                                                    url: varURL,
                                                    data: {
                                                        empleado_id: parseInt(em),
                                                        dia:parseInt(6)
                                                    },
                                                    type: 'post',
                                                }).done(function (data) {                 
                                                    Modulo.dtSabado=JSON.parse(data);                                                
                                                    //Domingo
                                                    $.ajax({
                                                        url: varURL,
                                                        data: {
                                                            empleado_id: parseInt(em),
                                                            dia:parseInt(7)
                                                        },
                                                        type: 'post',
                                                    }).done(function (data) {                 
                                                        Modulo.dtDomingo=JSON.parse(data);
                                                        Modulo.fnConstruirTabla();
                                                    });
                                                });
                                        });
                                });
                        });
                });
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
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
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

        martes=$('#martes').DataTable({
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
            ,data: Modulo.dtMartes    
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                    ,title: 'HorarioMartes'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMartes'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMartes'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMartes'
                } ),
            ]
        });
        martes.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        martes.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        
        miercoles=$('#miercoles').DataTable({
            data: Modulo.dtMiercoles,    
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
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            //"aaData": Solicitud.datosCredito.Proyecciones,
            //,dom: 'Bfrtip',flBip
        // ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                    ,title: 'HorarioMiercoles'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMiercoles'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMiercoles'
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                    ,title: 'HorarioMiercoles'
                } ),
            ]
        });

        miercoles.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        miercoles.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        
        jueves=$('#jueves').DataTable({
            data: Modulo.dtJueves,    
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
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            //"aaData": Solicitud.datosCredito.Proyecciones,
            //,dom: 'Bfrtip',flBip
        // ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
            ]
        });

        jueves.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        jueves.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        
        viernes=$('#viernes').DataTable({
            data: Modulo.dtViernes,    
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
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            //"aaData": Solicitud.datosCredito.Proyecciones,
            //,dom: 'Bfrtip',flBip
        // ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
            ]
        });

        viernes.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        viernes.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );

        sabado=$('#sabado').DataTable({
            data: Modulo.dtSabado,    
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
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            //"aaData": Solicitud.datosCredito.Proyecciones,
            //,dom: 'Bfrtip',flBip
        // ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
            ]
        });

        sabado.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        sabado.columns().every( function () {
            var that = this;

            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        
        domingo=$('#domingo').DataTable({
            data: Modulo.dtDomingo,    
            colReorder: true,
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
            ,"aoColumns": [
                {
                    "aTargets": [0],
                    "mData": function (source, type, val) {
                        return source.horario;
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
                    return source.entrada;
                        }
            },    
            {
                "aTargets": [3],
                "mData": function (source, type, val) {
                    return source.salida;
                        }
            },   
            {
                "aTargets": [4],
                "mData": function (source, type, val) {
                    return source.fechaAsignacion;
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
                            <li><a type="button" onclick="eliminar(`+source.id+`,'`+String(source.horario)+`',`+source.dia+`)">Eliminar</a></li>
                            </ul>
                            </div>`;
                    }
                },
            ]
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            //"aaData": Solicitud.datosCredito.Proyecciones,
            //,dom: 'Bfrtip',flBip
        // ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3,4]
                    }
                } ),
            ]
        });

        domingo.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        domingo.columns().every( function () {
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
        $("#tablaLMartes").css("display", "block");
        $("#tablaLMiercoles").css("display", "block");
        $("#tablaLJueves").css("display", "block");
        $("#tablaLViernes").css("display", "block");
        $("#tablaLSabado").css("display", "block");
        $("#tablaLDomingo").css("display", "block");
        $(".loadTable").css("display", "none");
        
    }
    //Carga los selects
    , fnCargarSelectsModal(id){
        $('#horarioLunes').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioLunes').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioLunes').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioMartes').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioMartes').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioMartes').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioMiercoles').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioMiercoles').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioMiercoles').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioJueves').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioJueves').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioJueves').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioViernes').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioViernes').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioViernes').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioSabado').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioSabado').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioSabado').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
        });
        
        $('#horarioDomingo').empty();  
        $.ajax({
            url:  Modulo.baseurl+'horariosusuario/consultaHorarios',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horarioDomingo').append('<option value="0">Selecciona</option>');
            $.each(perfiles, function( index, value ){
                $('#horarioDomingo').append('<option value="'+value.id+'">'+value.nombre+'---------------- Entrada:'+value.entrada+', Salida:'+value.salida+', Comida:'+value.tiempoComida+'</option>');
            });
            if(id){                
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioEditar',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id
                }
            }).done(function (data) {
                if (data) {            
                    if(data[0].horarioLunes=='DESCANSO'){
                        $('#horarioLunes').val(data[0].idhorarioLunes);   
                        $("#checkLunes").attr('checked', false);
                        $( "#checkLunes" ).prop( "checked", false );
                        $('#tipoLunes').val();
                        $('#entradaLunes').val();
                        $('#salidaLunes').val();
                        $('#horarioLunes').prop("disabled", true);
                    }else{
                        $('#horarioLunes').val(data[0].idhorarioLunes);  
                        $("#checkLunes").attr('checked', true);
                        $( "#checkLunes" ).prop( "checked", true ); 
                        $('#tipoLunes').val(data[0].tipoLunes);   
                        $('#entradaLunes').val(data[0].entradaLunes);   
                        $('#salidaLunes').val(data[0].salidaLunes);                 
                        $('#horarioLunes').prop("disabled", false);
                    }
                    if(data[0].horarioMartes=='DESCANSO'){
                        $('#horarioMartes').val(data[0].idhorarioMartes);  
                        $("#checkMartes").attr('checked', false);
                        $( "#checkMartes" ).prop( "checked", false ); 
                        $('#tipoMartes').val();
                        $('#entradaMartes').val();
                        $('#salidaMartes').val();
                        $('#horarioMartes').prop("disabled", true);
                    }else{
                        $('#horarioMartes').val(data[0].idhorarioMartes); 
                        $("#checkMartes").attr('checked', true);
                        $( "#checkMartes" ).prop( "checked", true );  
                        $('#tipoMartes').val(data[0].tipoMartes);   
                        $('#entradaMartes').val(data[0].entradaMartes);   
                        $('#salidaMartes').val(data[0].salidaMartes);                 
                        $('#horarioMartes').prop("disabled", false);
                    }
        
                    if(data[0].horarioMiercoles=='DESCANSO'){
                        $('#horarioMiercoles').val(data[0].idhorarioMiercoles);
                        $("#checkMiercoles").attr('checked', false);
                        $( "#checkMiercoles" ).prop( "checked", false );   
                        $('#tipoMiercoles').val();
                        $('#entradaMiercoles').val();
                        $('#salidaMiercoles').val();
                        $('#horarioMiercoles').prop("disabled", true);
                    }else{
                        $('#horarioMiercoles').val(data[0].idhorarioMiercoles);   
                        $("#checkMiercoles").attr('checked', true);
                        $( "#checkMiercoles" ).prop( "checked", true );
                        $('#tipoMiercoles').val(data[0].tipoMiercoles);   
                        $('#entradaMiercoles').val(data[0].entradaMiercoles);   
                        $('#salidaMiercoles').val(data[0].salidaMiercoles);                 
                        $('#horarioMiercoles').prop("disabled", false);
                    }
        
                    if(data[0].horarioJueves=='DESCANSO'){
                        $('#horarioJueves').val(data[0].idhorarioJueves);   
                        $("#checkJueves").attr('checked', false);
                        $( "#checkJueves" ).prop( "checked", false );
                        $('#tipoJueves').val();
                        $('#entradaJueves').val();
                        $('#salidaJueves').val();
                        $('#horarioJueves').prop("disabled", true);
                    }else{
                        $("#checkJueves").attr('checked', true);
                        $( "#checkJueves" ).prop( "checked", true );
                        $('#horarioJueves').val(data[0].idhorarioJueves);   
                        $('#tipoJueves').val(data[0].tipoJueves);   
                        $('#entradaJueves').val(data[0].entradaJueves);   
                        $('#salidaJueves').val(data[0].salidaJueves);                 
                        $('#horarioJueves').prop("disabled", false);
                    }
        
                    if(data[0].horarioViernes=='DESCANSO'){
                        $('#horarioViernes').val(data[0].idhorarioViernes);  
                        $("#checkViernes").attr('checked', false);
                        $( "#checkViernes" ).prop( "checked", false ); 
                        $('#tipoViernes').val();
                        $('#entradaViernes').val();
                        $('#salidaViernes').val();
                        $('#horarioViernes').prop("disabled", true);
                    }else{
                        $('#horarioViernes').val(data[0].idhorarioViernes);   
                        $("#checkViernes").attr('checked', true);
                        $( "#checkViernes" ).prop( "checked", true );
                        $('#tipoViernes').val(data[0].tipoViernes);   
                        $('#entradaViernes').val(data[0].entradaViernes);   
                        $('#salidaViernes').val(data[0].salidaViernes);                 
                        $('#horarioViernes').prop("disabled", false);
                    }
        
                    if(data[0].horarioSabado=='DESCANSO'){
                        $('#horarioSabado').val(data[0].idhorarioSabado);   
                        $("#checkSabado").attr('checked', false);
                        $( "#checkSabado" ).prop( "checked", false );
                        $('#tipoSabado').val();
                        $('#entradaSabado').val();
                        $('#salidaSabado').val();
                        $('#horarioSabado').prop("disabled", true);
                    }else{
                        $('#horarioSabado').val(data[0].idhorarioSabado);   
                        $("#checkSabado").attr('checked', true);
                        $( "#checkSabado" ).prop( "checked", true );
                        $('#tipoSabado').val(data[0].tipoSabado);   
                        $('#entradaSabado').val(data[0].entradaSabado);   
                        $('#salidaSabado').val(data[0].salidaSabado);                 
                        $('#horarioSabado').prop("disabled", false);
                    }
        
                    if(data[0].horarioDomingo=='DESCANSO'){
                        $('#horarioDomingo').val(data[0].idhorarioDomingo);  
                        $("#checkDomingo").attr('checked', false);
                        $( "#checkDomingo" ).prop( "checked", false ); 
                        $('#tipoDomingo').val();
                        $('#entradaDomingo').val();
                        $('#salidaDomingo').val();
                        $('#horarioDomingo').prop("disabled", true);
                    }else{
                        $('#horarioDomingo').val(data[0].idhorarioDomingo); 
                        $("#checkDomingo").attr('checked', true);
                        $( "#checkDomingo" ).prop( "checked", true );  
                        $('#tipoDomingo').val(data[0].tipoDomingo);   
                        $('#entradaDomingo').val(data[0].entradaDomingo);   
                        $('#salidaDomingo').val(data[0].salidaDomingo);                 
                        $('#horarioDomingo').prop("disabled", false);
                    }                     
                    $('#descripcion').val(data[0].descripcion);
                    $('#fechaAsignacion').val(data[0].fechaAsignacion);
                    $('#cantidad').val(data[0].cantidad);
                    
                    ///Debuggear a ver como se puede acomodar en la vista
        
                } else {
                    $('#id').val('0');    
                }
            });
            }
        });
    }    
    , Init: function () {
       // Modulo.fnCargarSelects();
    }
}

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

$(document).ready(function () {

    $('#checkLunes').change(function() {
        if($(this).is(":checked")) {
            $('#horarioLunes').prop("disabled", false);
            $('#horarioLunes').val(0);
        }else{
            $('#horarioLunes').val(1);
            $('#horarioLunes').prop("disabled", true);
            $('#tipoLunes').val('');
            $('#entradaLunes').val('');
            $('#salidaLunes').val('');
        }
    });
    $('#horarioLunes').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkLunes").attr('checked',true);
            $( "#checkLunes" ).prop( "checked",true);
            $('#horarioLunes').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoLunes').val(data[0].tipo);
                    $('#entradaLunes').val(data[0].entrada);
                    $('#salidaLunes').val(data[0].salida);
                    $("#horarioMartes").val(valueSelected).change();
                    $("#horarioMiercoles").val(valueSelected).change();
                    $("#horarioJueves").val(valueSelected).change();
                    $("#horarioViernes").val(valueSelected).change();
                    $("#horarioSabado").val(valueSelected).change();
                    $("#horarioDomingo").val(valueSelected).change();

                }
            });

        }else{
            $("#checkLunes").attr('checked',false);
            $( "#checkLunes" ).prop( "checked",false);
            $('#horarioLunes').prop("disabled",true);
            $('#tipoLunes').val('');
            $('#entradaLunes').val('');
            $('#salidaLunes').val('');
        }
    });

    $('#checkMartes').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioMartes').prop("disabled", false);
            $('#horarioMartes').val(0);
        }else{
            $('#horarioMartes').val(1);
            $('#horarioMartes').prop("disabled", true);
            $('#tipoMartes').val('');
            $('#entradaMartes').val('');
            $('#salidaMartes').val('');
        }
    });
    $('#horarioMartes').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkMartes").attr('checked',true);
            $( "#checkMartes" ).prop( "checked",true);
            $('#horarioMartes').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoMartes').val(data[0].tipo);
                    $('#entradaMartes').val(data[0].entrada);
                    $('#salidaMartes').val(data[0].salida);
                }
            });
        }else{
            $("#checkMartes").attr('checked',false);
            $( "#checkMartes" ).prop( "checked",false);
            $('#horarioMartes').prop("disabled",true);
            $('#tipoMartes').val('');
            $('#entradaMartes').val('');
            $('#salidaMartes').val('');
        }
    });

    $('#checkMiercoles').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioMiercoles').prop("disabled", false);
            $('#horarioMiercoles').val(0);
        }else{
            $('#horarioMiercoles').val(1);
            $('#horarioMiercoles').prop("disabled", true);
            $('#tipoMiercoles').val('');
            $('#entradaMiercoles').val('');
            $('#salidaMiercoles').val('');
        }
    });
    $('#horarioMiercoles').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkMiercoles").attr('checked',true);
            $( "#checkMiercoles" ).prop( "checked",true);
            $('#horarioMiercoles').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoMiercoles').val(data[0].tipo);
                    $('#entradaMiercoles').val(data[0].entrada);
                    $('#salidaMiercoles').val(data[0].salida);
                }
            });
        }else{
            $("#checkMiercoles").attr('checked',false);
            $( "#checkMiercoles" ).prop( "checked",false);
            $('#horarioMiercoles').prop("disabled",true);
            $('#tipoMiercoles').val('');
            $('#entradaMiercoles').val('');
            $('#salidaMiercoles').val('');
        }
    });

    $('#checkJueves').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioJueves').prop("disabled", false);
            $('#horarioJueves').val(0);
        }else{
            $('#horarioJueves').val(1);
            $('#horarioJueves').prop("disabled", true);
            $('#tipoJueves').val('');
            $('#entradaJueves').val('');
            $('#salidaJueves').val('');
        }
    });
    $('#horarioJueves').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkJueves").attr('checked',true);
            $( "#checkJueves" ).prop( "checked",true);
            $('#horarioJueves').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoJueves').val(data[0].tipo);
                    $('#entradaJueves').val(data[0].entrada);
                    $('#salidaJueves').val(data[0].salida);
                }
            });

        }else{
            $("#checkJueves").attr('checked',false);
            $( "#checkJueves" ).prop( "checked",false);
            $('#horarioJueves').prop("disabled",true);
            $('#tipoJueves').val('');
            $('#entradaJueves').val('');
            $('#salidaJueves').val('');
        }
    });

    $('#checkViernes').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioViernes').prop("disabled", false);
            $('#horarioViernes').val(0);
        }else{
            $('#horarioViernes').val(1);
            $('#horarioViernes').prop("disabled", true);
            $('#tipoViernes').val('');
            $('#entradaViernes').val('');
            $('#salidaViernes').val('');
        }
    });
    $('#horarioViernes').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkViernes").attr('checked',true);
            $( "#checkViernes" ).prop( "checked",true);
            $('#horarioViernes').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoViernes').val(data[0].tipo);
                    $('#entradaViernes').val(data[0].entrada);
                    $('#salidaViernes').val(data[0].salida);
                }
            });
        }else{
            $("#checkViernes").attr('checked',false);
            $( "#checkViernes" ).prop( "checked",false);
            $('#horarioViernes').prop("disabled",true);
            $('#tipoViernes').val('');
            $('#entradaViernes').val('');
            $('#salidaViernes').val('');
        }
    });

    $('#checkSabado').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioSabado').prop("disabled", false);
            $('#horarioSabado').val(0);
        }else{
            $('#horarioSabado').val(1);
            $('#horarioSabado').prop("disabled", true);
            $('#tipoSabado').val('');
            $('#entradaSabado').val('');
            $('#salidaSabado').val('');
        }
    });
    $('#horarioSabado').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkSabado").attr('checked',true);
            $( "#checkSabado" ).prop( "checked",true);
            $('#horarioSabado').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoSabado').val(data[0].tipo);
                    $('#entradaSabado').val(data[0].entrada);
                    $('#salidaSabado').val(data[0].salida);
                }
            });
        }else{
            $("#checkSabado").attr('checked',false);
            $( "#checkSabado" ).prop( "checked",false);
            $('#horarioSabado').prop("disabled",true);
            $('#tipoSabado').val('');
            $('#entradaSabado').val('');
            $('#salidaSabado').val('');
        }
    });

    $('#checkDomingo').change(function() {
        if($(this).is(":checked")) {    
            $('#horarioDomingo').prop("disabled", false);
            $('#horarioDomingo').val(0);
        }else{
            $('#horarioDomingo').val(1);
            $('#horarioDomingo').prop("disabled", true);
            $('#tipoDomingo').val('');
            $('#entradaDomingo').val('');
            $('#salidaDomingo').val('');
        }
    });
    $('#horarioDomingo').change(function() {
        var optionSelected = $("option:selected", this);
         var valueSelected = this.value;
        if(valueSelected!=1) {
            $("#checkDomingo").attr('checked',true);
            $( "#checkDomingo" ).prop( "checked",true);
            $('#horarioDomingo').prop("disabled",false);
            // consulta ajax el horario!!!        
            $.ajax({
                url: Modulo.baseurl+'horariosusuario/consultaHorarioID',
                data: {
                    id: valueSelected
                },
                type: 'post',
                dataType: 'json',
            }).done(function (data){
                if (data) { 
                    $('#tipoDomingo').val(data[0].tipo);
                    $('#entradaDomingo').val(data[0].entrada);
                    $('#salidaDomingo').val(data[0].salida);
                }
            });
        }else{
            $("#checkDomingo").attr('checked',false);
            $( "#checkDomingo" ).prop( "checked",false);
            $('#horarioDomingo').prop("disabled",true);
            $('#tipoDomingo').val('');
            $('#entradaDomingo').val('');
            $('#salidaDomingo').val('');
        }
    });

    
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
    $('#lunes thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  
    var martes = $('#martes').DataTable();
    $('#martes tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#martes thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  
    var miercoles = $('#miercoles').DataTable();
    $('#miercoles tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#miercoles thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  
    var jueves = $('#jueves').DataTable();
    $('#jueves tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#jueves thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  
    var viernes = $('#viernes').DataTable();
    $('#viernes tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#viernes thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });   
    var sabado = $('#sabado').DataTable();
    $('#sabado tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#sabado thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    var domingo = $('#domingo').DataTable();
    $('#domingo tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    $('#domingo thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  

    $('#selEmpleado').on('change', function() {
        Modulo.fnObtenerDatos($('#selEmpleado').val());   
        
        $("#asignar").css("display", "block");

    });

    /* BOTON DE LA TABLA ... AGREGAR  */
    $(document).on('click', '#nuevo', function (e) {        
        $('#id').val(0); 
        $("#checkLunes").attr('checked',true);
        $( "#checkLunes" ).prop( "checked",true);
        $('#horarioLunes').prop("disabled", false);
        $('#horarioLunes').val(0);
        $('#tipoLunes').val(''); 
        $('#entradaLunes').val(''); 
        $('#salidaLunes').val('');  
        $('#descripcion').val(''),

        $("#checkMartes").attr('checked',true);
        $( "#checkMartes" ).prop( "checked",true);
        $('#horarioMartes').prop("disabled", false);
        $('#horarioMartes').val(0);
        $('#tipoMartes').val(''); 
        $('#entradaMartes').val(''); 
        $('#salidaMartes').val('');  

        $("#checkMiercoles").attr('checked',true);
        $( "#checkMiercoles" ).prop( "checked",true);
        $('#horarioMiercoles').prop("disabled", false);
        $('#horarioMiercoles').val(0);
        $('#tipoMiercoles').val(''); 
        $('#entradaMiercoles').val(''); 
        $('#salidaMiercoles').val('');          

        $("#checkJueves").attr('checked',true);
        $( "#checkJueves" ).prop( "checked",true);
        $('#horarioJueves').prop("disabled", false);
        $('#horarioJueves').val(0);
        $('#tipoJueves').val(''); 
        $('#entradaJueves').val(''); 
        $('#salidaJueves').val('');  

        $("#checkViernes").attr('checked',true);
        $( "#checkViernes" ).prop( "checked",true);
        $('#horarioViernes').prop("disabled", false);
        $('#horarioViernes').val(0);
        $('#tipoViernes').val(''); 
        $('#entradaViernes').val(''); 
        $('#salidaViernes').val('');  

        $("#checkSabado").attr('checked',true);
        $( "#checkSabado" ).prop( "checked",true);
        $('#horarioSabado').prop("disabled", false);
        $('#horarioSabado').val(0);
        $('#tipoSabado').val(''); 
        $('#entradaSabado').val(''); 
        $('#salidaSabado').val('');    

        $("#checkDomingo").attr('checked',true);
        $( "#checkDomingo" ).prop( "checked",true);
        $('#horarioDomingo').prop("disabled", false);
        $('#horarioDomingo').val(0);
        $('#tipoDomingo').val(''); 
        $('#entradaDomingo').val(''); 
        $('#salidaDomingo').val('');  

        $('#fechaAsignacion').val('');  


        Modulo.fnCargarSelectsModal();
    });


    /*BOTON DE FORMULARIO... GUARDAR */
    $(document).on('click', '#btn_guardar', function (e) {
        e.preventDefault();                       
        if(validar()){
            var id= $('#id').val().trim();
            if($('#fechaAsignacion').val()!=''){
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: Modulo.baseurl+'horariosusuario/ActualizarHorariosEmpleado',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id'                    : $('#id').val().trim(),
                            'idhorarioLunes'        : $('#horarioLunes').val(),
                            'idhorarioMartes'       : $('#horarioMartes').val(),
                            'idhorarioMiercoles'    : $('#horarioMiercoles').val(),
                            'idhorarioJueves'       : $('#horarioJueves').val(),
                            'idhorarioViernes'      : $('#horarioViernes').val(),
                            'idhorarioSabado'       : $('#horarioSabado').val(),
                            'idhorarioDomingo'      : $('#horarioDomingo').val(),
                            'descripcion'           : $('#descripcion').val(),
                            'fechaAsignacion'       : $('#fechaAsignacion').val(),
                            'cantidad'              : $("#cantidad").val()
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
                        url: Modulo.baseurl+'horariosusuario/AgregarHorariosEmpleado',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'empleado_id'           : $('#selEmpleado').val().trim(),
                            'idhorarioLunes'        : $('#horarioLunes').val(),
                            'idhorarioMartes'       : $('#horarioMartes').val(),
                            'idhorarioMiercoles'    : $('#horarioMiercoles').val(),
                            'idhorarioJueves'       : $('#horarioJueves').val(),
                            'idhorarioViernes'      : $('#horarioViernes').val(),
                            'idhorarioSabado'       : $('#horarioSabado').val(),
                            'idhorarioDomingo'      : $('#horarioDomingo').val(),
                            'descripcion'           : $('#descripcion').val(),
                            'fechaAsignacion'       : $('#fechaAsignacion').val(),
                            'cantidad'              : $("#cantidad").val()
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
                alert('Favor de seleccionar una fecha de Asignación');
            }            
        }else{
            
            alert('Favor de completar el horario');
        }
    });



});