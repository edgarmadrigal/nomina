  
Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    datosTabla: []    
    //Obtiene los datos
    , fnObtenerDatos: function (em,fecha) {
        $("#diario").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "diarioController/consulta";
        /**Ajax */
        $.ajax({
            url: varURL,
            data: {
                fecha: fecha,
                empresa_id: parseInt(em),
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
        table=$('#diario').DataTable({
                data: Modulo.datosTabla,               
                "bDestroy": true,
                "scrollX": false,//"100%",
                "draw": true,
                "order": [[ 1, "asc" ]],
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
                            return source.nombreEmpresa;
                             }
                    },    
                   {
                       "aTargets": [1],
                       "mData": function (source, type, val) {
                           return source.TOTALEMPLEADOSPLANTA;
                            }
                   },    
                   {
                       "aTargets": [2],
                       "mData": function (source, type, val) {
                           return source.TOTALEMPLEADOSMATUTINO;
                            }
                   },    
                   {
                       "aTargets": [3],
                       "mData": function (source, type, val) {
                           return source.TOTALEMPLEADOSNOCTURNO;
                            }
                   },    
                   {
                       "aTargets": [4],
                       "mData": function (source, type, val) {
                           return source.ASISTENCIAMATUTINO;
                            }
                   },    
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           return source.ASISTENCIANOCTURNO;
                            }
                   },    
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source.FALTASMATUTINO;
                            }
                   },    
                   {
                       "aTargets": [7],
                       "mData": function (source, type, val) {
                           return source.FALTASNOCTURNO;
                            }
                   },
                   {
                       "aTargets": [8],
                       "mData": function (source, type, val) {
                           return source.RETARDOMATUTINO;
                            }
                   },
                   {
                       "aTargets": [9],
                       "mData": function (source, type, val) {
                           return source.RETARDONOCTURNO;
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
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ,8,9]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7,8,9]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7,8,9]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ,8,9]
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
        $("#diario").css("display", "block");
        $(".loadTable").css("display", "none");
    }
    
    , fnCargarSelect(){
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPlanta";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#planta').append('<option value="0">Selecciona una planta</option>');
            $.each(perfiles, function( index, value ) { 
                $('#planta').append('<option value="'+value.id+'">'+value.nombre+'</option>');
              });
        });
    }
    //Load
    , Init: function () {
        Modulo.fnCargarSelect();
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
        $('#empresa').on('change', function() {
            var empresa=parseInt($('#empresa').val());
        });
        $(document).on('click', '#actualizarTodo', function (e) {
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' }); 
               var varURL = Modulo.baseurl + "asistencias/actualizarTodo";
               $.ajax({
                   url:  varURL,
                   type: 'post',
                   dataType: 'json'
               }).done(function (data) { 
                   $.unblockUI();
                  alert('Termino la actualizacion Completa con exito');
               });
       });
        $(document).on('click', '#buscar', function (e) {
            var empresa=$('#planta').val();
            var fecha=$('#fecha').val();
            Modulo.fnObtenerDatos(empresa,fecha);
        });

    });