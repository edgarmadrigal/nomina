  
Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    datosTabla: []    
    //Obtiene los datos
    , fnObtenerDatos: function (fechaInicio,fechaFin) {
        $("#costo").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "costoController/exportar";
        /**Ajax */
        $.ajax({
            url: varURL,
            type: 'post',
            data: {
                fechaInicio: fechaInicio,
                fechaFin:fechaFin,
            },
        }).done(function (data) {
            $("#costo").css("display", "inline-table");
            $(".loadTable").css("display", "none");
            //Modulo.datosTabla=data;

            var a = document.createElement('a');
            var file_name = "CT_"+fechaFin;
            a.href = "http://177.244.42.94:8087/nomina/costo.txt";
            a.download = file_name;
            a.click();
        });
        


    }
    //Construye la tabla 
    , fnConstruitTabla: function () {
        table=$('#costo').DataTable({
                data: Modulo.datosTabla,               
                "bDestroy": true,
                "scrollX": false,//"100%",
                "draw": true,
                //"order": [[ 1, "asc" ]],
                "iDisplayLength": 30               
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
                            return source.CENTRO_COSTO;
                             }
                    },    
                   {
                       "aTargets": [1],
                       "mData": function (source, type, val) {
                           return source.DEPARTAMENTO;
                            }
                   },    
                   {
                       "aTargets": [2],
                       "mData": function (source, type, val) {
                           return source.PLANILLA;
                            }
                   },    
                   {
                       "aTargets": [3],
                       "mData": function (source, type, val) {
                           return source.PERCEP;
                            }
                   },    
                   {
                       "aTargets": [4],
                       "mData": function (source, type, val) {
                           return source.HOREXT;
                            }
                   },        
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           return source.GRATIF;
                            }
                   },    
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source.INCENT;
                            }
                   },   
                   {
                       "aTargets": [7],
                       "mData": function (source, type, val) {
                           return source.PPYADE;
                            }
                   },   
                   {
                       "aTargets": [8],
                       "mData": function (source, type, val) {
                           return source.CROM;
                            }
                   },   
                   {
                       "aTargets": [9],
                       "mData": function (source, type, val) {
                           return source.TOTPER;
                            }
                   },   
                   {
                       "aTargets": [10],
                       "mData": function (source, type, val) {
                           return source.SUBEMP;
                            }
                   },   
                   {
                       "aTargets": [11],
                       "mData": function (source, type, val) {
                           return source.ISPT;
                            }
                   },   
                   {
                       "aTargets": [12],
                       "mData": function (source, type, val) {
                           return source.IMSS;
                            }
                   },   
                   {
                       "aTargets": [13],
                       "mData": function (source, type, val) {
                           return source.CAJAAHO;
                            }
                   },   
                   {
                       "aTargets": [14],
                       "mData": function (source, type, val) {
                           return source.INFONAVIT;
                            }
                   },   
                   {
                       "aTargets": [15],
                       "mData": function (source, type, val) {
                           return source.PREAHO;
                            }
                   },   
                   {
                       "aTargets": [16],
                       "mData": function (source, type, val) {
                           return source.PREEMP;
                            }
                   },   
                   {
                       "aTargets": [17],
                       "mData": function (source, type, val) {
                           return source.INTERGAS;
                            }
                   },   
                   {
                       "aTargets": [18],
                       "mData": function (source, type, val) {
                           return source.OTRASRET;
                            }
                   },   
                   {
                       "aTargets": [19],
                       "mData": function (source, type, val) {
                           return source.TOTAL;
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
                            columns: [ 0, 1, 2, 3, 4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
                        }
                        ,title: 'RELACION DE SOLICITUDES'
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
                        }
                        ,title: 'RELACION DE SOLICITUDES'
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
                        }
                        ,title: 'RELACION DE SOLICITUDES'
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19]
                        }
                        ,title: ''
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
        $("#costo").css("display", "inline-table");
        $(".loadTable").css("display", "none");
       //$(table.column(1).header()).text('My title');
    }
    
    //Load
    , Init: function () {
        
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
        
        $("#descargar").css("display", "none");
        $("#costo").css("display", "none");
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
        
        
        $(document).on('click', '#buscar', function (e) {
            var fechaInicio=$('#fecha1').val();
            var fechaFin=$('#fecha2').val();
            Modulo.fnObtenerDatos(fechaInicio,fechaFin);
        });

        

    });