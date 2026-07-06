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
     
var buttonCommon = {
    exportOptions: {
        format: {
            body: function (data, row, column, node) {
                if (typeof data === 'string') {
                    return data.replace(/<[^>]*>/g, '');
                }
                return data;
            }
        }
    }
};

ModuloChecada = {
    baseurl: "",
    //baseurl: "http://192.168.128.24:8080/nomina/",
    validator: false,
    datosTablaChecadas: [],
    fnDayStatus: function(entrada, entradaComedor, salidaComedor, salida) {
        var e = (entrada || '').trim();
        var ec = (entradaComedor || '').trim();
        var sc = (salidaComedor || '').trim();
        var s = (salida || '').trim();
        if (!e && !ec && !sc && !s) {
            return '<span class="badge badge-danger" style="font-size:90%">FALTA</span>';
        }
        return '<span class="badge badge-success" style="font-size:90%">ASISTE</span>';
    },
    //Válida campos Obligatorios
    fnValidarObligatorios: function () {
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
    , fnObtenerDatosReprocesados: function (departamento,noempleado,NoSemana,anio) {
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");
            var varURL = ModuloChecada.baseurl + "checadas/consultaReprocesar";
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

    , fnObtenerDatosAct: function (departamento,noempleado,NoSemana,anio) {
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");
            var varURL = ModuloChecada.baseurl + "checadas/consultaAct";
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
                           return ModuloChecada.fnDayStatus(source.LunesEntrada, source.LunesEntradaComedor, source.LunesSalidaComedor, source.LunesSalida);
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [5],
                       "mData": function (source, type, val) {
                           return source.Lunes;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [6],
                       "mData": function (source, type, val) {
                           return source.LunesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [7],
                       "mData": function (source, type, val) {
                           return source.LunesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [8],
                       "mData": function (source, type, val) {
                           return source.LunesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [9],
                       "mData": function (source, type, val) {
                           return source.LunesSalida;
                            }
                            ,"defaultContent": ""
                   },    
                   
                   {
                    "aTargets": [10],
                    "mData": function (source, type, val) {
                        return ModuloChecada.fnDayStatus(source.MartesEntrada, source.MartesEntradaComedor, source.MartesSalidaComedor, source.MartesSalida);
                         }
                         ,"defaultContent": ""
                    },   
                   
                   {
                    "aTargets": [11],
                    "mData": function (source, type, val) {
                        return source.Martes;
                         }
                         ,"defaultContent": ""
                    },   
                   {
                       "aTargets": [12],
                       "mData": function (source, type, val) {
                           return source.MartesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [13],
                       "mData": function (source, type, val) {
                           return source.MartesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [14],
                       "mData": function (source, type, val) {
                           return source.MartesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [15],
                       "mData": function (source, type, val) {
                           return source.MartesSalida;
                            }
                            ,"defaultContent": ""
                   },                  
                   {
                       "aTargets": [16],
                       "mData": function (source, type, val) {
                           return ModuloChecada.fnDayStatus(source.MiercolesEntrada, source.MiercolesEntradaComedor, source.MiercolesSalidaComedor, source.MiercolesSalida);
                            }
                            ,"defaultContent": ""
                   },                   
                   {
                       "aTargets": [17],
                       "mData": function (source, type, val) {
                           return source.Miercoles;
                            }
                            ,"defaultContent": ""
                   },                      
                   {
                       "aTargets": [18],
                       "mData": function (source, type, val) {
                           return source.MiercolesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [19],
                       "mData": function (source, type, val) {
                           return source.MiercolesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [20],
                       "mData": function (source, type, val) {
                           return source.MiercolesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [21],
                       "mData": function (source, type, val) {
                           return source.MiercolesSalida;
                            }
                            ,"defaultContent": ""
                   },                  
                   {
                       "aTargets": [22],
                       "mData": function (source, type, val) {
                           return ModuloChecada.fnDayStatus(source.JuevesEntrada, source.JuevesEntradaComedor, source.JuevesSalidaComedor, source.JuevesSalida);
                            }
                            ,"defaultContent": ""
                   },                
                   {
                       "aTargets": [23],
                       "mData": function (source, type, val) {
                           return source.Jueves;
                            }
                            ,"defaultContent": ""
                   },            
                   {
                       "aTargets": [24],
                       "mData": function (source, type, val) {
                           return source.JuevesEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [25],
                       "mData": function (source, type, val) {
                           return source.JuevesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [26],
                       "mData": function (source, type, val) {
                           return source.JuevesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [27],
                       "mData": function (source, type, val) {
                           return source.JuevesSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [28],
                       "mData": function (source, type, val) {
                           return ModuloChecada.fnDayStatus(source.ViernesEntrada, source.ViernesEntradaComedor, source.ViernesSalidaComedor, source.ViernesSalida);
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [29],
                       "mData": function (source, type, val) {
                           return source.Viernes;
                            }
                            ,"defaultContent": ""
                   },     
                   {
                       "aTargets": [30],
                       "mData": function (source, type, val) {
                           return source.ViernesEntrada;
                            }
                            ,"defaultContent": ""
                   },       
                   {
                       "aTargets": [31],
                       "mData": function (source, type, val) {
                           return source.ViernesEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [32],
                       "mData": function (source, type, val) {
                           return source.ViernesSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [33],
                       "mData": function (source, type, val) {
                           return source.ViernesSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   
                   {
                       "aTargets": [34],
                       "mData": function (source, type, val) {
                           return ModuloChecada.fnDayStatus(source.SabadoEntrada, source.SabadoEntradaComedor, source.SabadoSalidaComedor, source.SabadoSalida);
                            }
                            ,"defaultContent": ""
                   },                       
                   {
                       "aTargets": [35],
                       "mData": function (source, type, val) {
                           return source.Sabado;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [36],
                       "mData": function (source, type, val) {
                           return source.SabadoEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [37],
                       "mData": function (source, type, val) {
                           return source.SabadoEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [38],
                       "mData": function (source, type, val) {
                           return source.SabadoSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [39],
                       "mData": function (source, type, val) {
                           return source.SabadoSalida;
                            }
                            ,"defaultContent": ""
                   }, 
                   {
                       "aTargets": [40],
                       "mData": function (source, type, val) {
                           return ModuloChecada.fnDayStatus(source.DomingoEntrada, source.DomingoEntradaComedor, source.DomingoSalidaComedor, source.DomingoSalida);
                            }
                            ,"defaultContent": ""
                   },  
                   {
                       "aTargets": [41],
                       "mData": function (source, type, val) {
                           return source.Domingo
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [42],
                       "mData": function (source, type, val) {
                           return source.DomingoEntrada;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [43],
                       "mData": function (source, type, val) {
                           return source.DomingoEntradaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [44],
                       "mData": function (source, type, val) {
                           return source.DomingoSalidaComedor;
                            }
                            ,"defaultContent": ""
                   },    
                   {
                       "aTargets": [45],
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
                ,initComplete: function () {
                    var api = this.api();
                    api.columns().every(function () {
                        var column = this;
                        var headerCell = $(column.header());
                        var footerCell = $(column.footer());
                        var title = $.trim(headerCell.text());

                        if (headerCell.find('input').length === 0) {
                            headerCell.html(title + '<br><input type="text" placeholder="Buscar ' + title + '" />');
                        }
                        $('input', column.header()).on('keyup change clear', function () {
                            if (column.search() !== this.value) {
                                column.search(this.value).draw();
                            }
                        });

                        if (footerCell.length && footerCell.find('input').length === 0) {
                            var footerTitle = $.trim(footerCell.text());
                            footerCell.html(footerTitle + '<br><input type="text" placeholder="Buscar ' + footerTitle + '" />');
                            $('input', column.footer()).on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                        }
                    });
                }
                ,buttons: [
                    $.extend( true, {}, buttonCommon, {
                        extend: 'copyHtml5',
                        className: 'btn btn-secondary btn-sm',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3,4,  5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,
                                22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        className: 'btn btn-secondary btn-sm',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3,4,  5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,
                                22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45]
                        }
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        className: 'btn btn-secondary btn-sm',
                        exportOptions: {
                            columns: [ 0, 1, 2, 3,4,  5, 6, 7 ,8,9,10,11,12,13,14,15,16,17,18,19,20,21,
                                22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45]
                        }
                    } ),
                ]
        });
        
        $("#tablaEmp").css("display", "block");
        $(".loadTable").css("display", "none");
    }
    , fnCargarSelect: function(){
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
    }
}
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
        ModuloChecada.Init();
        base_url="http://127.0.0.1:8080/nomina/";
        TablaVacia = "No se encontraron resultados";
        checadasTabla=[];
        $('#id').val(0);

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

        

        $(document).on('click', '#act', function (e) {
            var departamento=$('#departamento').val();
            var noempleado=$('#noempleado').val();
            var NoSemana=$('#NoSemana').val();
            var anio=$('#anio').val();
            ModuloChecada.fnObtenerDatosAct(departamento,noempleado,NoSemana,anio);
        });


        $(document).on('click', '#reprocesar', function (e) {
            var departamento=$('#departamento').val();
            var noempleado=$('#noempleado').val();
            var NoSemana=$('#NoSemana').val();
            var anio=$('#anio').val();
            ModuloChecada.fnObtenerDatosReprocesados(departamento,noempleado,NoSemana,anio);
        });

        
        
    });