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
        
(function ($) {
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
                { "orderable": false, "targets": 4 } ///no se puede ordenar por la Operacion que es la columna 4
                ],
            ajax: {
                type: "POST",
                url: base_url+'usuarios/consulta_usuario',
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
                        columns: [ 0, 1, 2, 3 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3 ]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3 ]
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
            var usuario = $('#usuario').val();       
            var nombre = $('#nombre').val();
            var password = $('#password').val();     
            var idPerfil = parseInt($('#perfil').val())+1;   
                       
            if(validar()){
                var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: base_url+'usuarios/edit',
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
                                    window.location.assign( base_url+'usuarios');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });

                }else{        
                    ///GUARDAR NUEVO        
                    $.ajax({
                        url: base_url+'usuarios/insert',
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
                                    window.location.assign( base_url+'usuarios');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });
                }
            }
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
                url:  base_url+'usuarios/consultaPerfil',
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
})(jQuery);