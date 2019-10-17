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

            var nombre          =$('#nombre').valid();    
            var entradaInicio   =$('#entradaInicio').valid();                                     
            var salidaDesde     =$('#salidaDesde').valid();                                                     
            var salidaHasta     =$('#salidaHasta').valid();                                                            
            var tipoNocturno    =$('#tipoNocturno').valid();                                      
            var entradaDesde    =$('#entradaDesde').valid();                         
            var entradaHasta    =$('#entradaHasta').valid();                             
            var salidaInicio    =$('#salidaInicio').valid();                                    
            var horasdiarias    =$('#horasdiarias').valid();                             
            var tipoDiurno      =$('#tipoDiurno').valid();
            var comedor         =$('#comedor').valid();  
            var tiempoComida    =$('#tiempoComida').valid();  

            if(entradaInicio==true 
                && salidaDesde== true 
                && salidaHasta == true
                && tipoNocturno== true 
                && entradaDesde == true && entradaHasta== true 
                && salidaInicio == true && horasdiarias== true 
                && tipoDiurno == true && comedor == true && nombre == true && tiempoComida== true){
                return true;
            }else{
                return false;
            }
        }
        function editar(id_edit) {
            
            $('#nombre').val(''); 
            $('#entradaInicio').val(''); 
            $('#salidaDesde').val('');             
            $('#salidaHasta').val('');
            $('#tipoDiurno').val(''); 
            $('#tipoNocturno').val('');            
            $('#entradaDesde').val('');
            $('#entradaHasta').val(''); 
            $('#salidaInicio').val('');             
            $('#horasdiarias').val('');
            $('#comedor').val('');   
            $('#tiempoComida').val('');          
            $('#tipoDiurno').attr('checked', false);                    
            $("#tipoNocturno").attr('checked', false);
            $( "#tipoDiurno" ).prop( "checked", false );
            $( "#tipoNocturno" ).prop( "checked", false );
            $("#comedor").attr('checked', false);
            $( "#comedor" ).prop( "checked", false );
            $('#id').val(id_edit);   
             
            $.ajax({
                url: base_url+'horarios/consulta',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_edit
                }
            }).done(function (data) {
                if (data) {    
                    var tipoDiurno      = data.tipo == 0 ? true : false;
                    var tipoNocturno    = data.tipo == 1 ? true : false;
                    var comedor         = data.comedor == 1 ? true : false;
                    $('#nombre').val(data.nombre); 
                    $('#entradaInicio').val(data.entrada_Inicio); 
                    $('#salidaDesde').val(data.salida_Desde);             
                    $('#salidaHasta').val(data.salida_Hasta);
                    $('#tipoDiurno').attr('checked',tipoDiurno);                    
                    $("#tipoNocturno").attr('checked',tipoNocturno);                    
                    $( "#tipoDiurno" ).prop( "checked", tipoDiurno );
                    $( "#tipoNocturno" ).prop( "checked", tipoNocturno);
                    $('#entradaDesde').val(data.entrada_Desde);
                    $('#entradaHasta').val(data.entrada_Hasta); 
                    $('#salidaInicio').val(data.salida_Inicio);             
                    $('#horasdiarias').val(data.horas_diarias);
                    $("#comedor").attr('checked', comedor);
                    $( "#comedor" ).prop( "checked", comedor);
                    $('#tiempoComida').val(data.tiempoComida);

                    if(data.tiempoComida=='00:00:00' || data.tiempoComida=='00:00'){                        
                        $("#comedor").attr('checked', false);
                        $( "#comedor" ).prop( "checked", false);
                    }

                    var d = new Date();

                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = d.getFullYear() + '-' +
                        (month<10 ? '0' : '') + month + '-' +
                        (day<10 ? '0' : '') + day;


                    $('#fechaAsignacion').val(data.fechaAsignacion==null? output:data.fechaAsignacion);                    

                    $('#id').val(data.id);
                } else {
                    $('#nombre').val(''); 
                    $('#entradaInicio').val(''); 
                    $('#salidaDesde').val('');             
                    $('#salidaHasta').val('');
                    $('#tipoDiurno').val(''); 
                    $('#tipoNocturno').val('');            
                    $('#entradaDesde').val('');
                    $('#entradaHasta').val(''); 
                    $('#salidaInicio').val('');             
                    $('#horasdiarias').val('');
                    $('#tiempoComida').val('');
                    $('#comedor').val('');             
                    $('#tipoDiurno').attr('checked', false);                    
                    $("#tipoNocturno").attr('checked', false);
                    $("#comedor").attr('checked', false);
                    $('#id').val(id_edit);   
                }
            });
        }    
        function eliminar(id_delete){
            swal({
                title: "Estas seguro?",
                text: "Se eliminara el horario ?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  base_url+'horarios/delete',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            id: id_delete,
                            
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
                ajax: {
                    type: "POST",
                    url: base_url+'horarios/consulta_horarios',
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
                            return source[4];
                           //comedor
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                            return source[5];
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
                   {
                    "aTargets": [7],
                    "mData": function (source, type, val) {
                        return source[7];
                         }
                         ,"defaultContent": ""
                    }, 
                    {
                     "aTargets": [8],
                     "mData": function (source, type, val) {
                         return source[8];
                          }
                          ,"defaultContent": ""
                     }, 
                     {
                      "aTargets": [9],
                      "mData": function (source, type, val) {
                        if(source[0]==1){                                
                            return '';
                        }else{
                            return source[9]==0?'NO':'SI';
                        }
                    }
                    ,"defaultContent": ""
                         
                      }, 
                   {
                       "aTargets": [10],
                       "mData": function (source, type, val) {
                                if(source[0]==1){                                
                                    return '';
                                }else{
                        
                                return source[10];
                                }
                            }
                            ,"defaultContent": ""   
                   },    
                   {
                       "aTargets": [11],
                       "mData": function (source, type, val) {
                            if(source[0]==1){                                
                                return '';
                            }else{
                                return source[11]==0?'DIURNO':'NOCTURNO';
                            }
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [12],
                       "mData": function (source, type, val) {
                                if(source[0]==1){                                
                                    return '';
                                }else{
                                return source[12];
                                }
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [13],
                       "mData": function (source, type, val) {
                           if(source[0]==1){

                           }else{
                            return source[13];
                           }
                            }
                            ,"defaultContent": ""
                   },                    

                ]
                
                ,"iDisplayLength": 14               
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
                            columns: [ 0, 1, 2, 3 ,4,5,6,7,8,9,10,11,12]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5,6,7,8,9,10,11,12]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5,6,7,8,9,10,11,12]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3 ,4,5,6,7,8,9,10,11,12]
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

            var nombre          =$('#nombre').val();
            var entradaDesde    =$('#entradaDesde').val();
            var entradaInicio   =$('#entradaInicio').val();                           
            var entradaHasta    =$('#entradaHasta').val();
            
            
            var salidaDesde     =$('#salidaDesde').val(); 
            var salidaInicio    =$('#salidaInicio').val();                                                                  
            var salidaHasta     =$('#salidaHasta').val();

            var tipoNocturno    =$('#tipoNocturno').is(':checked')==false?0:1;                      
            var horasdiarias    =$('#horasdiarias').val();                   
            var comedor         =$('#comedor').is(':checked')==false?0:1;   
            var tiempoComida    =$('#tiempoComida').val();

            var tipo            =   tipoNocturno==1?1:0;

            var fechaAsignacion =$('#fechaAsignacion').val();

                       
            if(validar()){
                var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: base_url+'horarios/edit',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id': id,
                            'nombre' 	    :nombre ,
                            'entradaDesde' 	:entradaDesde ,
                            'entradaInicio' :entradaInicio,  
                            'entradaHasta' 	:entradaHasta ,

                            'salidaDesde' 	:salidaDesde  ,
                            'salidaInicio' 	:salidaInicio ,
                            'salidaHasta' 	:salidaHasta  ,

                            'tipo' 	        :tipo ,
                            'horasdiarias' 	:horasdiarias ,
                            'comedor' 	    :comedor,
                            'tiempoComida' 	:tiempoComida,
                            'fechaAsignacion':fechaAsignacion
                            
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
                                    window.location.assign( base_url+'horarios');
                                });
                        }
                    });

                }else{        
                    ///GUARDAR NUEVO        
                    $.ajax({
                        url: base_url+'horarios/insert',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'nombre' 	    :nombre ,
                            'entradaDesde' 	:entradaDesde ,
                            'entradaInicio' :entradaInicio,  
                            'entradaHasta' 	:entradaHasta ,

                            'salidaDesde' 	:salidaDesde  ,
                            'salidaInicio' 	:salidaInicio ,
                            'salidaHasta' 	:salidaHasta  ,
                            
                            'tipo' 	        :tipo ,
                            'horasdiarias' 	:horasdiarias ,
                            'comedor' 	    :comedor  ,        
                            'tiempoComida' 	:tiempoComida,                    
                            'fechaAsignacion':fechaAsignacion
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
                                    window.location.assign( base_url+'horarios');
                                });
                        }
                    });
                }
            }
        });
        $(document).on('change','.clockpicker',function(e) {
            var tiempo=$("#tiempoComida").val();
                if( tiempo=="00:00:00" || tiempo=="00:00"){                        
                    $("#comedor").attr('checked', false);
                    $( "#comedor" ).prop( "checked", false);
                }else{                     
                    $("#comedor").attr('checked', true);
                    $( "#comedor" ).prop( "checked", true);
                }
          });

          $(document).on('change','#comedor',function(e) {
            if($('#tiempoComida').is(':checked')){
                $("#tiempoComida").val("00:30");
            }else{
                $("#tiempoComida").val("00:00");
            }

          });

        /* BOTON DE LA TABLA ... AGREGAR  */
        $(document).on('click', '#nuevo', function (e) {   
            $('#nombre').val(''); 
            $('#entradaInicio').val(''); 
            $('#salidaDesde').val('');             
            $('#salidaHasta').val('');
            $('#tipoDiurno').val(''); 
            $('#tipoNocturno').val('');            
            $('#entradaDesde').val('');
            $('#entradaHasta').val(''); 
            $('#salidaInicio').val('');             
            $('#horasdiarias').val('');
            $('#tiempoComida').val('');
            $('#comedor').val('');             
            $('#tipoDiurno').attr('checked', true);   
            $( "#tipoDiurno" ).prop( "checked", true );                 
            $("#tipoNocturno").attr('checked', false);
            $( "#tipoNocturno" ).prop( "checked", false );
            $("#comedor").attr('checked', false);
            $( "#comedor" ).prop( "checked", false );
            $('#id').val(0);                 
            
            var d = new Date();
            var month = d.getMonth()+1;
            var day = d.getDate();
            var output = d.getFullYear() + '-' +
                (month<10 ? '0' : '') + month + '-' +
                (day<10 ? '0' : '') + day;

            $('#fechaAsignacion').val(output);                




        });
    });
})(jQuery);