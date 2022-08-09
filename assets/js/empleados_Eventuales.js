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
    var NUMERO              = $("#NUMERO").valid();
    var NOMBRES             = $("#NOMBRES").valid();
    var APELLIDO_PATERNO    = $("#APELLIDO_PATERNO").valid();
    var APELLIDO_MATERNO    = $("#APELLIDO_MATERNO").valid();
    var NOMBRE_PUESTO       = $("#NOMBRE_PUESTO").valid();
    var NOMBRE_DEPARTAMENTO = $("#NOMBRE_DEPARTAMENTO").valid();
    
    if(NOMBRES == true && APELLIDO_PATERNO== true  && APELLIDO_MATERNO== true && NOMBRE_PUESTO== true && NOMBRE_DEPARTAMENTO== true && NUMERO== true){
        return true;
    }else{
        return false;
    }
}
function editar(id_edit) {
    $('#NUMERO').val('');
    $('#NOMBRES').val('');
    $('#APELLIDO_PATERNO').val(''); 
    $('#APELLIDO_MATERNO').val(''); 
    $('#NOMBRE_PUESTO').val(''); 
    $('#NOMBRE_DEPARTAMENTO').val(''); 
    $('#id').val(id_edit);   
     
    $.ajax({
        url: base_url+'EmpleadosController_Eventuales/consulta',
        type: 'post',
        dataType: 'json',
        data: {
            id: id_edit
        }
    }).done(function (data) {
        if (data) {    
            $('#NUMERO').val(data.NUMERO);
            $('#NOMBRES').val(data.NOMBRES);
            $('#APELLIDO_PATERNO').val(data.APELLIDO_PATERNO); 
            $('#APELLIDO_MATERNO').val(data.APELLIDO_MATERNO); 
            $('#NOMBRE_PUESTO').val(data.NOMBRE_PUESTO); 
            $('#NOMBRE_DEPARTAMENTO').val(data.NOMBRE_DEPARTAMENTO); 
            $('#id').val(data.id);
            /** */            
            $('#NUMERO').removeClass("error");
            $('#NOMBRES').removeClass("error");
            $('#APELLIDO_PATERNO').removeClass("error");
            $('#APELLIDO_MATERNO').removeClass("error"); 
            $('#NOMBRE_PUESTO').removeClass("error"); 
            $('#NOMBRE_DEPARTAMENTO').removeClass("error"); 
   
            $('#NUMERO-error').removeClass("error");
            $('#NOMBRES-error').removeClass("error");
            $('#APELLIDO_PATERNO-error').removeClass("error");
            $('#APELLIDO_MATERNO-error').removeClass("error"); 
            $('#NOMBRE_PUESTO-error').removeClass("error"); 
            $('#NOMBRE_DEPARTAMENTO-error').removeClass("error"); 

        } else {
            $('#NUMERO').val('');
            $('#NOMBRES').val('');
            $('#APELLIDO_PATERNO').val(''); 
            $('#APELLIDO_MATERNO').val(''); 
            $('#NOMBRE_PUESTO').val(''); 
            $('#NOMBRE_DEPARTAMENTO').val('');
            $('#id').val('0');    
        }
    });
}    
function eliminar(id_delete,empleado){            
    swal({
        title: "Estas seguro?",
        text: "Se eliminara el empleado "+empleado,
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willDelete) => {
        if (willDelete) {
            $.ajax({
                url:  base_url+'EmpleadosController_Eventuales/delete',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_delete
                }
            }).done(function (data) {
                if (data) {
                    // update the manageMemberTable
                    table.ajax.reload(null, false); 
                } else {                            
                    // update the manageMemberTable
                    table.ajax.reload(null, false); 
                }
            });
        } else {
            // update the manageMemberTable
            table.ajax.reload(null, false); 
        }
    });
}
var table;

(function ($) {
var id_edit;

$(document).ready(function () {
base_url="";
//http://192.168.128.24:8080/nomina/
TablaVacia = "No se encontraron resultados";
usuariosTabla=[];
$('#id').val(0);
var buttonCommon = {
    exportOptions: {
        format: {
            body: function ( data, row, column, node ) {
                // Strip $ from salary column to make it numeric
                return data;/*column === 5 ?
                    data.replace( /[$,]/g, '' ) :
                    data;/**/
            }
        }
    }
};         
table=$('#example').DataTable( {
    colReorder: true,
    responsive: true,
    'orders': [],
    "columnDefs": [
        { "orderable": false, "targets":6 } ///no se puede ordenar por la Operacion que es la columna 4
        ],
    ajax: {
        type: "POST",
        url: base_url+'EmpleadosController_Eventuales/consulta_empleado',
        contentType: 'application/json; charset=utf-8',
        data: function (data) {
            usuariosTabla=data; 
            return JSON.stringify(data); 
        }
    },
    "iDisplayLength": 10
    , "oLanguage": {
        "sProcessing": "Procesando...",
        "sLengthMenu": "_MENU_ Registro por página",
        "zeroRecords": "No se encontraron resultados",
        "sEmptyTable": " " + TablaVacia,
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
    ,"oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    }
    ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
    ,buttons: [
        $.extend( true, {}, buttonCommon, {
            extend: 'copyHtml5',
            exportOptions: {
                columns: [ 0, 1, 2, 3 ,4,5,6]
            }
        } ),
        $.extend( true, {}, buttonCommon, {
            extend: 'excelHtml5',
            exportOptions: {
                columns: [ 0, 1, 2, 3 ,4,5,6]
            }
        } ),
        $.extend( true, {}, buttonCommon, {
            extend: 'csvHtml5',
            exportOptions: {
                columns: [ 0, 1, 2, 3 ,4,5,6]
            }
        } ),
        $.extend( true, {}, buttonCommon, {
            extend: 'pdfHtml5',
            exportOptions: {
                columns: [ 0, 1, 2, 3 ,4,5,6]
            }
        } ),
    ]
});
$('#example tfoot th').each( function () {
    var title = $(this).text();
    if(title!="Opciones"){
        $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
    }else{
        $(this).html(title);
    }
});
$('#example thead th').each( function () {
    var title = $(this).text();
    if(title!="Opciones"){
        $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
    }else{
        $(this).html(title);
    }
});  
table.columns().every( function () {
    var that = this;         
    $( 'input', this.header() ).on( 'keyup change', function () {
        if ( that.search() !== this.value ) {
            that
                .search( this.value )
                .draw();
        }
    } );
});
table.columns().every( function () {
    var that = this;

    $( 'input', this.footer() ).on( 'keyup change', function () {
        if ( that.search() !== this.value ) {
            that
                .search( this.value )
                .draw();
        }
    } );
});

/*BOTON DE FORMULARIO... GUARDAR */
$(document).on('click', '#btn_guardar', function (e) {
    e.preventDefault(); 
    var NUMERO              = $("#NUMERO").val();
    var NOMBRES             = $("#NOMBRES").val();
    var APELLIDO_PATERNO    = $("#APELLIDO_PATERNO").val();
    var APELLIDO_MATERNO    = $("#APELLIDO_MATERNO").val();
    var NOMBRE_PUESTO       = $("#NOMBRE_PUESTO").val();
    var NOMBRE_DEPARTAMENTO = $("#NOMBRE_DEPARTAMENTO").val();
    var NOMBRE_COMPLETO     = NOMBRES+' '+APELLIDO_PATERNO+' '+APELLIDO_MATERNO;  
    
    if(validar()){
        var id= $('#id').val();
        if(id>0){
            ///GUARDAR EDICION
            $.ajax({
                url: base_url+'EmpleadosController_Eventuales/edit',
                type: 'post',
                dataType: 'json',
                data: {
                    'id': id
                    ,'NUMERO'               : NUMERO
                    ,'NOMBRE_COMPLETO'      : NOMBRE_COMPLETO
                    ,'NOMBRES'              : NOMBRES
                    ,'APELLIDO_PATERNO'     : APELLIDO_PATERNO    
                    ,'APELLIDO_MATERNO'     : APELLIDO_MATERNO    
                    ,'NOMBRE_PUESTO'        : NOMBRE_PUESTO       
                    ,'NOMBRE_DEPARTAMENTO'  : NOMBRE_DEPARTAMENTO 
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
                                $("#myModal_agregar").modal('hide');
                                // update the manageMemberTable
                                table.ajax.reload(null, false); 
                        });

                } else {
                    swal({
                            title: "Tus datos no han sido actualizados!",
                            text: "No se actualizo correctamente",
                            icon: "warning",
                        })
                        .then((value) => {
                            window.location.assign( base_url+'EmpleadosController_Eventuales');
                        });
                }
            });

        }else{        
            ///GUARDAR NUEVO        
            $.ajax({
                url: base_url+'EmpleadosController_Eventuales/insert',
                type: 'post',
                dataType: 'json',
                data: {
                     'NUMERO'               : NUMERO
                    ,'NOMBRE_COMPLETO'      : NOMBRE_COMPLETO
                    ,'NOMBRES'              : NOMBRES
                    ,'APELLIDO_PATERNO'     : APELLIDO_PATERNO    
                    ,'APELLIDO_MATERNO'     : APELLIDO_MATERNO    
                    ,'NOMBRE_PUESTO'        : NOMBRE_PUESTO       
                    ,'NOMBRE_DEPARTAMENTO'  : NOMBRE_DEPARTAMENTO 
                }
            }).done(function (data) {
                if (data) {
                    swal({
                            title: "Tus datos han sido registrados",
                            text: "Se agrego correctamente",
                            icon: "success",
                        })
                        .then((value) => {
                                // hide the modal
                                $("#myModal_agregar").modal('hide');
                                // update the manageMemberTable
                                table.ajax.reload(null, false); 
                        });

                } else {
                    swal({
                            title: "Tus datos no han sido registrados!",
                            text: "No se agrego correctamente",
                            icon: "warning",
                        })
                        .then((value) => {
                            window.location.assign( base_url+'EmpleadosController_Eventuales');
                        });
                }
            });
        }
    }
});

/* BOTON DE LA TABLA ... AGREGAR  */
$(document).on('click', '#nuevo', function (e) {    
    $('#NUMERO').val('');        
    $('#NOMBRES').val('');
    $('#APELLIDO_PATERNO').val(''); 
    $('#APELLIDO_MATERNO').val(''); 
    $('#NOMBRE_PUESTO').val(''); 
    $('#NOMBRE_DEPARTAMENTO').val('');
    $('#id').val(0);         
});


});
})(jQuery);