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
            var usuario = $("#usuario").valid();
            var password = $("#password").valid();
            var nombre = $("#nombre").valid();
            var perfil = $("#perfil").valid();

            if(usuario==true && password== true && nombre == true){
                return true;
            }else{
                return false;
            }
        }

        
Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    datosTabla: []
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
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "byte/consulta_empleados";
        /**Ajax */
        $.ajax({
            url: varURL,
            data: {
                empresa: parseInt(em),
            },
            type: 'post',
            //contentType: 'application/json; charset=utf-8',
        }).done(function (data) {
            Modulo.datosTabla=JSON.parse(data);
            Modulo.fnConstruitTabla();

        });
    }
    //Construye la tabla 
    , fnConstruitTabla: function () {
        
        table=$('#example').DataTable({
                data: Modulo.datosTabla,               
                "bDestroy": true,
                "scrollX": false,//"100%",
                "draw": true,
                "order": [[ 3, "asc" ]],
                "iDisplayLength": 10                
                , "oLanguage": {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "_MENU_ Registro por página",
                    "zeroRecords": "No se encontraron resultados",
                    "sEmptyTable": " " + TablaVacia,
                    //"sEmptyTable": "No se encontraron resultados",
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
                ,
                "aoColumns": [
                    {
                        "aTargets": [0],
                        "mData": function (source, type, val) {
                            return source.ID;
                             }
                    },    
                   {
                       "aTargets": [1],
                       "mData": function (source, type, val) {
                           return source.BYTE;
                            }
                   },    
                   {
                       "aTargets": [2],
                       "mData": function (source, type, val) {
                           return source.NUMERO;
                            }
                   },    
                   {
                       "aTargets": [3],
                       "mData": function (source, type, val) {
                           return source.NOMBRE_COMPLETO;
                            }
                   },    
                   {
                       "aTargets": [4],
                       "mData": function (source, type, val) {
                           return source.APELLIDO_PATERNO;
                            }
                   },    
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           return source.APELLIDO_MATERNO;
                            }
                   },    
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source.NOMBRES;
                            }
                   },    
                   {
                       "aTargets": [7],
                       "mData": function (source, type, val) {
                           return source.ESTATUS;
                            }
                   },
                ]
                ,"oAria": {
                    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
                //"aaData": Solicitud.datosCredito.Proyecciones,
                //,dom: 'Bfrtip',flBip
                ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
                //, "dom": "<'row'<'col-sm-12'<'pull-right'><'pull-left'B>>>    <'row-fluid'<'span8'l><'span4'<'pull-right'f><'pull-left'T>r<'clearfix'>>>t<'row'<'col-sm-12'<'pull-left'i><'pull-right'p>>>"
                ,buttons: [
                    $.extend( true, {}, buttonCommon, {
                        extend: 'copyHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ]
                        }
                    } ),
                ]
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
        } );
        table.columns().every( function () {
            var that = this;
     
            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        } );
        
        $("#tablaEmp").css("display", "block");
        $(".loadTable").css("display", "none");
    }
    //Load
    , Init: function () {
        Modulo.fnObtenerDatos(1);
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
        //Load
        table = $('#example').DataTable();
        Modulo.Init();
        $("#example").dataTable().fnDestroy();
        base_url="";
        //http://127.0.0.1:8080/nomina/
        TablaVacia = "No se encontraron resultados";
        empleadosTabla=[];
            $('#id').val(0);
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
        /*BOTON DE FORMULARIO... GUARDAR */
        $(document).on('click', '#btn_guardar', function (e) {
            e.preventDefault();
            var usuario = $('#usuario').val();       
            var nombre = $('#nombre').val();
            var password = $('#password').val();     
            var idPerfil = parseInt($('#perfil').val())+1;   
                       
            if(validar()){
                var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: base_url+'byte/edit',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id': id,
                            'usuario': usuario,
                            'nombre':nombre,
                            'password': password,
                            'idPerfil': idPerfil,
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
                                        document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });

                        } else {
                            swal({
                                    title: "Tus datos no han sido actualizados!",
                                    text: "No se actualizo correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( base_url+'byte');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });

                }else{        
                    ///GUARDAR NUEVO        
                    $.ajax({
                        url: base_url+'byte/insert',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'usuario': usuario,
                            'nombre':nombre,
                            'password': password,
                            'idPerfil': idPerfil,
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
                                        document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });

                        } else {
                            swal({
                                    title: "Tus datos no han sido registrados!",
                                    text: "No se agrego correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( base_url+'byte');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });
                }
            }
        });

        $('#empresa').on('change', function() {
            var empresa=parseInt($('#empresa').val());
            Modulo.fnObtenerDatos(empresa);
        });

        /* BOTON DE LA TABLA ... AGREGAR  */
        $(document).on('click', '#nuevo', function (e) {            
            $('#nombre').val('');
            $('#usuario').val(''); 
            $('#password').val(''); 
            $('#id').val(0);         
            $('#perfil').empty();  
            var perfil = $("#perfil").valid();
            document.getElementById("drop-remove").checked = false;
            var x = document.getElementById("password");            
             x.type = "password";
            $.ajax({
                url:  base_url+'byte/consultaPerfil',
                type: 'post',
                dataType: 'json'
            }).done(function (data) {                
                perfiles= JSON.parse(JSON.stringify(data));
                $('#perfil').append('<option value="">Selecciona</option>');
                $.each(perfiles, function( index, value ) { 
                    $('#perfil').append('<option value="'+index+'">'+value.perfil+'</option>');
                  });
            });
        });
    });