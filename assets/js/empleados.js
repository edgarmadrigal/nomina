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
        function editar(id_edit) {
            $('#nombre').val('');
            $('#usuario').val(''); 
            $('#password').val(''); 
            $('#id').val(id_edit);   
            document.getElementById("drop-remove").checked = false;
            var x = document.getElementById("password");
            x.type = "password";   
             
            $.ajax({
                url: base_url+'Usuarios/consulta',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_edit
                }
            }).done(function (data) {
                if (data) {    
                    $('#perfil').empty();  
                    $('#nombre').val(data.nombre);
                    $('#usuario').val(data.usuario); 
                    $('#password').val(data.password); 
                    $('#id').val(data.id);
                    var perfil=data.idPerfil;
                    $.ajax({
                        url:  base_url+'usuarios/consultaPerfil',
                        type: 'post',
                        dataType: 'json'
                    }).done(function (data){                             
                        perfiles= JSON.parse(JSON.stringify(data));

                        $('#perfil').append('<option value="">Selecciona</option>');
                        $.each(perfiles, function( index, value ) { 
                            $('#perfil').append('<option value="'+index+'">'+value.perfil+'</option>');
                        });                        
                        $("#perfil").val(perfil-1);                        
                        $('#id').val(id_edit);
                        $('#nombre').removeClass("error");
                        $('#usuario').removeClass("error");
                        $('#password').removeClass("error");
                        $('#perfil').removeClass("error");
                        $('#nombre-error').hide();
                        $('#usuario-error').hide();
                        $('#password-error').hide();
                        $('#perfil-error').hide();
                    });
                } else {
                    $('#nombre').val('');
                    $('#usuario').val(''); 
                    $('#password').val(''); 
                    $('#id').val('0');    
                }
            });
        }    
        function eliminar(id_delete,usuario){            
            swal({
                title: "Estas seguro?",
                text: "Se eliminara el usuario "+usuario,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  base_url+'usuarios/delete',
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
        

        Modulo = {
            baseurl: "",
            //http://192.168.128.24:8080/nomina/
            validator: false,
            dtEmpleados: [],
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
            , fnObtenerDatos: function (empresa_id) {
                $("#tablaEmpleados").css("display", "none");
                $(".loadTable").css("display", "block");
                var varURL = Modulo.baseurl + "empleados/consultaEmpleado"; 
                //Lunes
                $.ajax({
                    url: varURL,
                    data: {
                        empresa_id:parseInt(empresa_id)
                    },
                    type: 'post',
                }).done(function (data) {
                    Modulo.dtEmpleados=JSON.parse(data);                    
                    Modulo.fnConstruirTabla();                  
                });
            }
            //Construye la tabla
            , fnConstruirTabla: function () {
        
                empleados=$('#empleados').DataTable({
                    colReorder: false,
                    responsive: true,
                    "order": [ 0, 'asc' ],     
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
                    ,data: Modulo.dtEmpleados    
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
                            return source.empleado_id;
                                }
                    },    
                    {
                        "aTargets": [2],
                        "mData": function (source, type, val) {
                            return source.NOMBRE_COMPLETO;
                                }
                    },    
                    {
                        "aTargets": [3],
                        "mData": function (source, type, val) {
                            return source.NOMBRE_PUESTO;
                                }
                    },   
                    {
                        "aTargets": [4],
                        "mData": function (source, type, val) {
                            return source.NOMBRE_DEPARTAMENTO;
                                }
                    },     
                    
                    {
                        "aTargets": [5],
                        "mData": function (source, type, val) {
                            return source.ESTATUS;
                                }
                    },  
                    ]
                    ,"oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                    ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
                    ,buttons: [
                        $.extend( true, {}, buttonCommon, {
                            extend: 'copyHtml5',
                            exportOptions: {
                                columns: [ 0, 1, 2, 3,4,5 ]
                            }                    
                            ,title: 'Empleados'  // titulo de excel
                        } ),
                        $.extend( true, {}, buttonCommon, {
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [ 0, 1, 2, 3,4,5 ]
                            }
                            ,title: 'Empleados'
                        } ),
                        $.extend( true, {}, buttonCommon, {
                            extend: 'csvHtml5',
                            exportOptions: {
                                columns: [  0, 1, 2, 3,4,5 ]
                            }
                            ,title: 'Empleados'
                        } ),
                        $.extend( true, {}, buttonCommon, {
                            extend: 'pdfHtml5',
                            exportOptions: {
                                columns: [  0, 1, 2, 3,4,5 ]
                            }
                            ,title: 'Empleados'
                        } ),
                    ]
                });
        
                empleados.columns().every( function () {
                    var that = this;         
                    $( 'input', this.header() ).on( 'keyup change', function () {
                        if ( that.search() !== this.value ) {
                            that
                                .search( this.value )
                                .draw();
                        }
                    } );
                } );
                empleados.columns().every( function () {
                    var that = this;
        
                    $( 'input', this.footer() ).on( 'keyup change', function () {
                        if ( that.search() !== this.value ) {
                            that
                                .search( this.value )
                                .draw();
                        }
                    } );
                } );
                $("#tablaEmpleados").css("display", "block");
                $(".loadTable").css("display", "none");
                
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
                    $('#planta').append('<option value="">Selecciona una planta</option>');
                    $.each(perfiles, function( index, value ) { 
                        $('#planta').append('<option value="'+value.id+'">'+value.nombre+'</option>');
                      });
                });
            }    
            , Init: function () {
               Modulo.fnCargarSelects();
            }
            ,ActualizarEmpleados(){
                
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
        Modulo.Init();

        $('#planta').on('change', function() {
            Modulo.fnObtenerDatos($('#planta').val());       
        });

        
    var empleados = $('#empleados').DataTable();
    $('#empleados tfoot th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });
    

    $(document).on('click', '#actualizar', function (e) {
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");  
        var varURL = Modulo.baseurl + "empleados/actualizar";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {  
            alert("Se actualizo con exito "+data[0].TOTAL);
            $.unblockUI();
            $("#tablaEmp").css("display", "block");
            $(".loadTable").css("display", "none");
        });
    });

    $('#empleados thead th').each( function () {
        var title = $(this).text();
        if(title!="Opciones"){
            $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
        }else{
            $(this).html(title);
        }
    });  
    });