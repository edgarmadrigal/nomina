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

            var concepto    =$('#descripcion').valid();  
            var descripcionCorta   =$('#descripcionCorta').valid();     
            var idClasificacion = $('#clasificacion').valid();             
            var goceSueldo = $('#goceSueldo').valid();  
            var comentarios = $('#comentarios').valid();

            if(concepto==true 
                && descripcionCorta== true 
                && idClasificacion == true
                && goceSueldo== true 
                && comentarios == true){
                return true;
            }else{
                return false;
            }
        }
        function editar(id_edit) {

            $("#goceSueldo").attr('checked',false);  
            $("#goceSueldo").prop('checked',false);              
            $( "#comentarios" ).attr( "checked", false);    
            $( "#comentarios" ).prop( "checked", false);   
            $('#descripcion').val('');
            $('#descripcionCorta').val('');
            $('#clasificacion').empty(); 

            $('#id').val(id_edit);   
             
            $.ajax({
                url: base_url+'conceptos/consulta',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_edit
                }
            }).done(function (data) {
                if (data) {    
                    var goceSueldo      = data[0].goceSueldo == 1 ? true : false;
                    var comentarios    = data[0].comentarios == 1 ? true : false;
                    $("#goceSueldo").attr('checked',goceSueldo);  
                    $("#goceSueldo").prop('checked',goceSueldo);  

                    $( "#comentarios" ).attr( "checked", comentarios);    
                    $( "#comentarios" ).prop( "checked", comentarios);   

                    $('#descripcion').val(data[0].concepto);
                    $('#descripcionCorta').val(data[0].descripcionCorta);
                    var idClasificacion=data[0].idClasificacion;
                    $.ajax({
                        url:  base_url+'conceptos/consultaClasificacion',
                        type: 'post',
                        dataType: 'json'
                    }).done(function (data) {                
                        clasificacion= JSON.parse(JSON.stringify(data));
                        $('#clasificacion').append('<option value="">Selecciona</option>');
                        $.each(clasificacion, function( index, value ) {
                            $('#clasificacion').append('<option value="'+value.id+'">'+value.descripcion+'</option>');
                          });
                        $("#clasificacion").val(idClasificacion);   
                    });

                    $('#id').val(data[0].id);
                } else {

                    $("#goceSueldo").attr('checked',false);  
                    $("#goceSueldo").prop('checked',false);  

                    
                    $( "#comentarios" ).attr( "checked", false);    
                    $( "#comentarios" ).prop( "checked", false);   

                    $('#descripcion').val('');
                    $('#descripcionCorta').val('');

                    $('#id').val(id_edit);   
                }
            });
        }    
        function eliminar(id_delete){            
            swal({
                title: "Estas seguro?",
                text: "Se eliminara el concepto ?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  base_url+'conceptos/delete',
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
        $("#descripcionCorta").val().toUpperCase();
        base_url="http://192.168.128.24:8080/nomina/";
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
                ajax: {
                    type: "POST",
                    url: base_url+'conceptos/consulta_conceptos',
                    contentType: 'application/json; charset=utf-8',
                    data: function (data) { 
                        usuariosTabla=data; 
                        return JSON.stringify(data); 
                    }
                },            
                "aoColumns": [
                    {
                        "aTargets": [0],
                        "mData": function (source, type, val) {
                            return source[0];
                             }
                
                        ,"defaultContent": ""
                    },  
                   {
                       "aTargets": [1],
                       "mData": function (source, type, val) {
                           return source[1];
                            }             
                        ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [2],
                       "mData": function (source, type, val) {
                           return source[2];
                            }             
                        ,"defaultContent": ""
                   },   
                    
                   {
                       "aTargets": [3],
                       "mData": function (source, type, val) {
                           return source[3];
                            }             
                        ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [4],
                       "mData": function (source, type, val) {
                            return source[4]==0?'NO':'SI';
                           //
                           //comedor
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           // return source[5];
                           return source[5]==0?'NO':'SI';
                           //tipo
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source[6];
                            }
                            ,"defaultContent": ""
                   },                 

                ]
                
                ,"iDisplayLength": 7                
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
                            columns: [ 0, 1, 2, 3 ,4,5]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5]
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
            var concepto    =$('#descripcion').val();
            var descripcionCorta   =$('#descripcionCorta').val();   
            var idClasificacion = parseInt($('#clasificacion').val());             
            var goceSueldo = $('#goceSueldo').is(':checked')==false?0:1;  
            var comentarios = $('#comentarios').is(':checked')==false?0:1;

                       
            if(validar()){
                var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: base_url+'conceptos/edit',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id': id,
                            'concepto' 	:concepto ,
                            'descripcionCorta' :descripcionCorta,  
                            'idClasificacion' 	:idClasificacion ,
                            'goceSueldo' 	:goceSueldo  ,
                            'comentarios':comentarios
                            
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
                                    window.location.assign( base_url+'conceptos');
                                });
                        }
                    });

                }else{        
                    ///GUARDAR NUEVO        
                    $.ajax({
                        url: base_url+'conceptos/insert',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id': id,
                            'concepto' 	:concepto ,
                            'descripcionCorta' :descripcionCorta,  
                            'idClasificacion' 	:idClasificacion ,
                            'goceSueldo' 	:goceSueldo  ,
                            'comentarios':comentarios
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
                                    window.location.assign( base_url+'conceptos');
                                });
                        }
                    });
                }
            }
        });

        /* BOTON DE LA TABLA ... AGREGAR  */
        $(document).on('click', '#nuevo', function (e) {   
           
            $("#goceSueldo").attr('checked',false);  
            $("#goceSueldo").prop('checked',false);              
            $( "#comentarios" ).attr( "checked", false);    
            $( "#comentarios" ).prop( "checked", false);   
            $('#descripcion').val('');
            $('#descripcionCorta').val('');    
            $('#clasificacion').empty(); 
            $('#id').val('');    
            $.ajax({
                url:  base_url+'conceptos/consultaClasificacion',
                type: 'post',
                dataType: 'json'
            }).done(function (data) {
                clasificacion= JSON.parse(JSON.stringify(data));
                $('#clasificacion').append('<option value="">Selecciona</option>');
                $.each(clasificacion, function( index, value ) {
                    $('#clasificacion').append('<option value="'+value.id+'">'+value.descripcion+'</option>');
                  });
                  $('#clasificacion').val("");
            });
        });
    });
})(jQuery);