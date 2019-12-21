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
     
ModuloChecada = {
    baseurl: "",
    //baseurl: "http://192.168.128.24:8080/nomina/",
    validator: false,
    datosTablaChecadas: []
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
    , fnObtenerDatos: function (departamento,noempleado,NoSemana,anio) {
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = ModuloChecada.baseurl + "checadas/consulta";
        /**Ajax */
        $.ajax({
            url: varURL,
            data: {
                departamento: departamento,
                noempleado:noempleado,
                NoSemana:NoSemana,
                anio:anio,
            },
            type: 'post',
            //contentType: 'application/json; charset=utf-8',
        }).done(function (data) {
            ModuloChecada.datosTablaChecadas=JSON.parse(data);
            ModuloChecada.fnConstruitTabla();
        });
    }
    //Construye la tabla 
    , fnConstruitTabla: function () {
        
        table=$('#example').DataTable({
                data: ModuloChecada.datosTablaChecadas,               
                "bDestroy": true,
                "scrollX": false,//"100%",
                "draw": true,
                "order": [[ 3, "asc" ]],
                "iDisplayLength": 10                
                , "oLanguage": {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "_MENU_ Registro por página",
                    "zeroRecords": "No se encontraron resultados",
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
                            return source.Numero;
                             }
                
                        ,"defaultContent": ""
                    },  
                   {
                       "aTargets": [1],
                       "mData": function (source, type, val) {
                           return source.NombreCompleto;
                            }             
                        ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [2],
                       "mData": function (source, type, val) {
                           return source.FechaInicio;
                            }             
                        ,"defaultContent": ""
                   },   
                    
                   {
                       "aTargets": [3],
                       "mData": function (source, type, val) {
                           return source.FechaFin;
                            }             
                        ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [4],
                       "mData": function (source, type, val) {
                           return source.Lunes;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           return source.LunesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source.LunesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [7],
                       "mData": function (source, type, val) {
                           return source.LunesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [8],
                       "mData": function (source, type, val) {
                           return source.LunesSalida;
                            }
                            ,"defaultContent": ""
                   },    
                   
                   {
                    "aTargets": [9],
                    "mData": function (source, type, val) {
                        return source.Martes;
                         }
                         ,"defaultContent": ""
                    },   
                   {
                       "aTargets": [10],
                       "mData": function (source, type, val) {
                           return source.MartesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [11],
                       "mData": function (source, type, val) {
                           return source.MartesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [12],
                       "mData": function (source, type, val) {
                           return source.MartesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [13],
                       "mData": function (source, type, val) {
                           return source.MartesSalida;
                            }
                            ,"defaultContent": ""
                   },                  
                   {
                       "aTargets": [14],
                       "mData": function (source, type, val) {
                           return source.Miercoles;
                            }
                            ,"defaultContent": ""
                   },                   
                   {
                       "aTargets": [15],
                       "mData": function (source, type, val) {
                           return source.MiercolesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [16],
                       "mData": function (source, type, val) {
                           return source.MiercolesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [17],
                       "mData": function (source, type, val) {
                           return source.MiercolesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [18],
                       "mData": function (source, type, val) {
                           return source.MiercolesSalida;
                            }
                            ,"defaultContent": ""
                   },                  
                   {
                       "aTargets": [19],
                       "mData": function (source, type, val) {
                           return source.Jueves;
                            }
                            ,"defaultContent": ""
                   },                 
                   {
                       "aTargets": [20],
                       "mData": function (source, type, val) {
                           return source.JuevesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [21],
                       "mData": function (source, type, val) {
                           return source.JuevesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [22],
                       "mData": function (source, type, val) {
                           return source.JuevesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [23],
                       "mData": function (source, type, val) {
                           return source.JuevesSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [24],
                       "mData": function (source, type, val) {
                           return source.Viernes;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [25],
                       "mData": function (source, type, val) {
                           return source.ViernesEntrada;
                            }
                            ,"defaultContent": ""
                   },       
                   {
                       "aTargets": [26],
                       "mData": function (source, type, val) {
                           return source.ViernesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [27],
                       "mData": function (source, type, val) {
                           return source.ViernesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [28],
                       "mData": function (source, type, val) {
                           return source.ViernesSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   
                   {
                       "aTargets": [29],
                       "mData": function (source, type, val) {
                           return source.Sabado;
                            }
                            ,"defaultContent": ""
                   },    
                   
                   {
                       "aTargets": [30],
                       "mData": function (source, type, val) {
                           return source.SabadoEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [31],
                       "mData": function (source, type, val) {
                           return source.SabadoEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [32],
                       "mData": function (source, type, val) {
                           return source.SabadoSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [33],
                       "mData": function (source, type, val) {
                           return source.SabadoSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [34],
                       "mData": function (source, type, val) {
                           return source.Domingo;
                            }
                            ,"defaultContent": ""
                   },  
                   {
                       "aTargets": [35],
                       "mData": function (source, type, val) {
                           return source.DomingoEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [36],
                       "mData": function (source, type, val) {
                           return source.DomingoEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [37],
                       "mData": function (source, type, val) {
                           return source.DomingoSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [38],
                       "mData": function (source, type, val) {
                           return source.DomingoSalida;
                            }
                        ,"defaultContent": ""
                   }
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
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3, 4, 5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38]
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
    , fnCargarSelect(){
        var varURL = ModuloChecada.baseurl + "checadas/consultaDepartamento";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#departamento').append('<option value="">Selecciona un departamento</option>');
            $.each(perfiles, function( index, value ) { 
                $('#departamento').append('<option value="'+value.ID+'">'+value.descripcion+'</option>');
              });
        });
    }
    //Load
    , Init: function () {
        ModuloChecada.fnCargarSelect();
        ModuloChecada.fnObtenerDatos('MMS','','','');
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
        ModuloChecada.Init();
        $("#example").dataTable().fnDestroy();
        base_url="http://127.0.0.1:8080/nomina/";
        TablaVacia = "No se encontraron resultados";
        checadasTabla=[];
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
            ModuloChecada.fnObtenerDatos(empresa);
        });

        $(document).on('click', '#buscar', function (e) {
            var departamento=$('#departamento').val();
            var noempleado=$('#noempleado').val();
            var NoSemana=$('#NoSemana').val();
            var anio=$('#anio').val();
            ModuloChecada.fnObtenerDatos(departamento,noempleado,NoSemana,anio);
        });
        
    });