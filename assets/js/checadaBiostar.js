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
    //http://192.168.128.24:8080/nomina/
    validator: false,
    datosTablaChecadas: []
    //Válida campos Obligatorios

	, fnEsHora: function (valor) {
    if (valor === null || valor === undefined) return false;
    return /^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/.test($.trim(valor));
}

	, fnValorCelda: function (source, campoEntrada, campo) {
		var dia = campoEntrada.replace('Entrada', '');
		// Si el día es FALTA se muestra FALTA en todas las columnas del día (como un día sin checadas).
		if (ModuloChecada.fnEsFalta(source, dia)) {
			return '<span class="chk-falta" title="Día laboral sin ninguna checada real">FALTA</span>';
		}
		var entrada = source[campoEntrada], valor = source[campo];
		var vacio = function (v) { return v == null || $.trim(v) === ''; };
		// Si "Entrada" no es una hora válida es un concepto (VAC/INC/PER/etc.) y se repite en las
		// columnas vacías del día. Las incidencias parciales (solo algunas checadas, como "tran")
		// dejan las checadas reales en las demás columnas y esas se respetan; si les falta una
		// checada esa columna queda vacía (Cubre<Dia> viene del SP; '1111' = todo el día).
		var cubre = source['Cubre' + dia];
		var completa = cubre == null || String(cubre) === '1111';

		// VACACIONES con checadas reales: el día es VAC (día completo) pero el empleado SÍ checó.
		// Se muestra "VAC / hora" en cada columna (las horas reales vienen de dbo.checadas) para
		// que RH lo revise. Las horas van en orden; la 1a a Entrada, 2a a EntradaComedor, etc.
		var concepto = (entrada && !ModuloChecada.fnEsHora(entrada)) ? $.trim(entrada) : '';
		if (concepto && completa && /VAC/i.test(concepto)) {
			var reales = String(source[dia + 'Reales'] || '').split('|').filter(function (h) { return h; });
			if (reales.length) {
				var slot = { 'Entrada': 0, 'EntradaComedor': 1, 'SalidaComedor': 2, 'Salida': 3 }[campo.substring(dia.length)];
				var h = reales[slot];
				// Al hacer clic lleva a la incidencia de ese empleado (empresa + número) para revisarla.
				var url = 'incidencia?empresa=' + encodeURIComponent(source.empresa_id)
				        + '&empleado=' + encodeURIComponent(source.Numero)
				        + '&nombre=' + encodeURIComponent(source.NombreCompleto || '');
				return '<a href="' + url + '" target="_blank" class="chk-vac-chk" '
				     + 'title="Tiene VACACIONES pero SÍ registró checadas. Revisar con RH. Clic para ir a la incidencia.">'
				     + (h ? 'VAC / ' + h : 'VAC') + '</a>';
			}
		}

		if (vacio(valor) && entrada && !ModuloChecada.fnEsHora(entrada)) return completa ? entrada : '';
		return valor;
	}
	/**
	 * Falta: día laboral de su horario (no DESCANSO), que ya pasó, SIN ninguna checada REAL (hora)
	 * y sin una incidencia que cubra TODO el día. Una incidencia PARCIAL (p. ej. "tran", que solo
	 * cubre la entrada) NO evita la falta: si no hay checadas reales, es falta. Una de día completo
	 * (VAC, Cubre='1111') sí justifica y no es falta.
	 */
	, fnEsFalta: function (source, dia) {
		var esHora = ModuloChecada.fnEsHora;
		// ¿Hay al menos una checada real (una hora) ese día?
		if (esHora(source[dia + 'Entrada']) || esHora(source[dia + 'EntradaComedor']) ||
			esHora(source[dia + 'SalidaComedor']) || esHora(source[dia + 'Salida'])) return false;
		// ¿Incidencia que cubre TODO el día? (VAC / permiso de día completo) -> justificado, no es falta
		var entrada = source[dia + 'Entrada'];
		var hayConcepto = entrada != null && $.trim(entrada) !== '' && !esHora(entrada);
		var cubre = source['Cubre' + dia];
		if (hayConcepto && String(cubre) === '1111') return false;
		var horario = source['idhorario_' + dia.toLowerCase()];
		if (horario == null || String(horario) === '1') return false;   // 1 = DESCANSO
		var dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
		var lunes = String(source.FechaInicio || '').substr(0, 10).split('-');
		if (lunes.length !== 3) return true;
		var fecha = new Date(+lunes[0], +lunes[1] - 1, +lunes[2] + dias.indexOf(dia));
		var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
		return fecha < hoy;
	}
	/*
    ,fnDayStatus: function(entrada, entradaComedor, salidaComedor, salida) {
        var e = (entrada || '').trim();
        var ec = (entradaComedor || '').trim();
        var sc = (salidaComedor || '').trim();
        var s = (salida || '').trim();
        if (!e && !ec && !sc && !s) {
            return '<span class="badge badge-danger" style="font-size:90%">FALTA</span>';
        }
        return '<span class="badge badge-success" style="font-size:90%">ASISTE</span>';
    }/*/
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
	, fnMostrarUltimoProceso: function (planta, NoSemana, anio) {
    var varURL = ModuloChecada.baseurl + "checadasBiostar/ultimoProceso";
    $.ajax({
        url: varURL,
        data: { planta, NoSemana, anio },
        type: 'post',
    }).done(function (data) {
        var res = JSON.parse(data);
        var info = res[0] || {};
        var $div = $('#infoUltimoProceso');

        if (info.Texto) { $div.html(info.Texto); return; }   // Torreón unificado: resumen de todas las plantas
        if (info.YaProcesado) {
            var fecha = info.FechaUltimoProceso ? info.FechaUltimoProceso : '';
            var texto = 'Última vez procesado por <b>' + info.UsuarioUltimoProceso + '</b> el <b>' + fecha + '</b>';
            if (info.EsReproceso == 1) {
                texto += ' (reproceso)';
            }
            $div.html(texto);
        } else {
            $div.html('Esta semana aún no ha sido procesada.');
        }
    });
}

, fnProcesarSemana: function (planta,departamento,puesto,noempleado,NoSemana,anio,forzar) {
    $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
    var varURL = ModuloChecada.baseurl + "checadasBiostar/procesar";
    $.ajax({
        url: varURL,
        data: { planta, departamento, puesto, noempleado, NoSemana, anio, forzar: forzar ? 1 : 0 },
        type: 'post',
    }).done(function (data) {
        $.unblockUI();
        var res = JSON.parse(data);
        var info = res[0] || {};

        if (info.Exito) {
            alert(info.Mensaje);
            ModuloChecada.fnObtenerDatos(planta,departamento,puesto,noempleado,NoSemana,anio);
            ModuloChecada.fnMostrarUltimoProceso(planta, NoSemana, anio);
            return;
        }

        if (info.YaProcesado) {
            if (confirm(info.Mensaje)) {
                ModuloChecada.fnProcesarSemana(planta,departamento,puesto,noempleado,NoSemana,anio, true);
            }
            return;
        }
		//alert('Error: ' + info.Mensaje);
    });
}
    , fnDescargarReloj: function () {
        $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Descargando checadas del reloj...</h1>' });
        var varURL = ModuloChecada.baseurl + "checadasBiostar/descargarReloj";
        $.ajax({
            url: varURL,
            type: 'post',
        }).done(function (data) {
            $.unblockUI();
            var res = JSON.parse(data);
            alert(res.Mensaje);
            ModuloChecada.fnResumenRelojes();
        }).fail(function () {
            $.unblockUI();
            alert('No se pudo descargar del reloj.');
            ModuloChecada.fnResumenRelojes();
        });
    }
    // ---- Resumen de relojes (última descarga de cada uno) ----
    , relojes: []
    , fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    }
    , fnResumenRelojes: function () {
        if ($('#resumenRelojes').length === 0) return;   // solo usuarios de Torreón
        $.ajax({
            url: ModuloChecada.baseurl + 'controllerRelojes/listar',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            ModuloChecada.relojes = data;
            var html = '';
            $.each(data, function (i, r) {
                if (String(r.activo) !== '1') return;
                var hora = r.ultimaDescarga ? r.ultimaDescarga.substring(5, 16) : 'sin descargar';
                var n = r.ultimasChecadas == null ? '' : parseInt(r.ultimasChecadas, 10);
                var etiqueta, fondo;
                if (r.ultimoError) {
                    etiqueta = '<i class="fa fa-times-circle"></i> Error'; fondo = '#dc3545';
                } else {
                    etiqueta = n === '' ? '—' : n; fondo = n > 0 ? '#17a2b8' : '#6c757d';
                }
                html += '<a href="#" class="lnkBajadasReloj" data-id="' + r.id + '" title="Ver las checadas de la última descarga" ' +
                        'style="display:inline-flex; align-items:center; gap:6px; border:1px solid ' + fondo + '; border-radius:4px; padding:2px 4px 2px 8px; font-size:12px; text-decoration:none;">' +
                        '<span>' + ModuloChecada.fnEsc(r.descripcion) + ' <small style="opacity:.75;">' + hora + '</small></span>' +
                        '<span style="background:' + fondo + '; color:#fff !important; font-weight:600; border-radius:3px; padding:1px 7px;">' + etiqueta + ' <i class="fa fa-search"></i></span>' +
                        '</a>';
            });
            $('#resumenRelojes').html(html ? '<span style="font-size:12px; align-self:center;"><b>Última descarga por reloj:</b></span>' + html : '');
        });
    }
    , fnVerBajadasReloj: function (id) {
        var r = $.grep(ModuloChecada.relojes, function (x) { return String(x.id) === String(id); })[0];
        $('#modalBajadasRelojTitulo').text('Última descarga' + (r ? ' — ' + r.descripcion : ''));
        $('#bajadasRelojBuscar').val('');
        $('#resumenBajadasReloj').text('');
        var det = '';
        if (r && r.ultimoError) {
            var partes = /^(.*?)\s*\[Detalle: (.*)\]$/.exec(r.ultimoError);
            det = '<div class="alert alert-danger"><strong><i class="fa fa-times-circle"></i> La última descarga falló.</strong><br>' +
                  ModuloChecada.fnEsc(partes ? partes[1] : r.ultimoError) +
                  (partes ? '<br><small>Detalle técnico: ' + ModuloChecada.fnEsc(partes[2]) + '</small>' : '') + '</div>';
        } else if (r) {
            det = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Última descarga: <b>' + ModuloChecada.fnEsc(r.ultimaDescarga || '—') + '</b>' +
                  ((r.ultimasChecadas | 0) === 0 ? '. El reloj respondió bien, pero no había checadas nuevas.' : '') + '</div>';
        }
        $('#detalleBajadasReloj').html(det);
        $('#cuerpoBajadasReloj').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $('#modalBajadasReloj').modal('show');
        $.ajax({
            url: ModuloChecada.baseurl + 'controllerRelojes/bajadas',
            type: 'post',
            data: { id: id },
            dataType: 'json'
        }).done(function (data) {
            var html = '', nuevas = 0;
            $.each(data, function (i, b) {
                var nueva = b.resultado.indexOf('insertada') === 0;
                if (nueva) nuevas++;
                html += '<tr' + (b.nombre ? '' : ' style="background:#fff3cd;"') + '>';
                html += '<td>' + ModuloChecada.fnEsc(b.fechahora) + '</td>';
                html += '<td>' + ModuloChecada.fnEsc(b.numero) + '</td>';
                html += '<td>' + (b.nombre ? ModuloChecada.fnEsc(b.nombre) : '<span class="text-warning">No existe en empleados</span>') + '</td>';
                html += '<td>' + ModuloChecada.fnEsc(b.planta) + '</td>';
                html += '<td><span class="badge ' + (nueva ? 'badge-success' : 'badge-secondary') + '" style="color:#fff !important;">' + ModuloChecada.fnEsc(b.resultado) + '</span></td>';
                html += '</tr>';
            });
            if (html === '') html = '<tr><td colspan="5" class="text-center">La última descarga no trajo checadas.</td></tr>';
            $('#cuerpoBajadasReloj').html(html);
            $('#resumenBajadasReloj').text(data.length + ' checadas, ' + nuevas + ' pasaron a nómina');
        }).fail(function () {
            $('#cuerpoBajadasReloj').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar</td></tr>');
        });
    }
    , fnActualizarChecadasNomiplus: function (){
        var varURL = ModuloChecada.baseurl + "checadasBiostar/ActualizarChecadasNomiplus";
        /**Ajax */
        $.ajax({
            url: varURL,
            type: 'post',
        }).done(function (data) {            
            alert("Se actualizo con exito");
            $.unblockUI();
            $("#tablaEmp").css("display", "block");
            $(".loadTable").css("display", "none");
        });
    }
    //Obtiene los datos
    /** Incidencia del día de una fila: 'FALTA', el concepto (VAC/INC/PER/...) en mayúsculas, o '' si trabajó/descansó. */
    , fnIncidenciaDia: function (source, dia) {
        if (ModuloChecada.fnEsFalta(source, dia)) return 'FALTA';
        var e = source[dia + 'Entrada'];
        if (e != null && $.trim(e) !== '' && !ModuloChecada.fnEsHora(e)) return $.trim(e).toUpperCase();
        return '';
    }
    /** Llena el combo de incidencias con las que aparecen en los datos cargados. */
    , fnLlenarFiltroInc: function () {
        var dias = ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'];
        var set = {};
        $.each(ModuloChecada.datosTablaChecadas, function (i, s) {
            $.each(dias, function (j, d) { var v = ModuloChecada.fnIncidenciaDia(s, d); if (v) set[v] = true; });
        });
        var vals = Object.keys(set).sort();
        var actual = $('#cbFiltroInc').val();
        var html = '<option value="">Todas</option>';
        $.each(vals, function (i, v) { html += '<option value="' + v + '"' + (v === actual ? ' selected' : '') + '>' + v + '</option>'; });
        $('#cbFiltroInc').html(html);
        $('#cbFiltroIncidencias').toggle(vals.length > 0);
    }
    , fnObtenerDatos: function (planta,departamento,puesto,noempleado,NoSemana,anio) {
        $("#tablaEmp").css("display", "none");
        $(".loadTable").css("display", "block");
        var varURL = ModuloChecada.baseurl + "checadasBiostar/consulta";
        /**Ajax */
        $.ajax({
            url: varURL,
            data: {
                planta:planta,
                departamento: departamento,
                puesto: puesto,
                noempleado:noempleado,
                NoSemana:NoSemana,
                anio:anio,
            },
            type: 'post',
            //contentType: 'application/json; charset=utf-8',
        }).done(function (data) {
            ModuloChecada.datosTablaChecadas=JSON.parse(data);
            ModuloChecada.fnConstruitTabla();
            ModuloChecada.fnAplicarColumnas();
            ModuloChecada.fnLlenarFiltroInc();

        });
    }
    // Casillas "Mostrar columnas": oculta las checadas de comida (2a y 3a de cada día), departamento y/o
    // puesto. Se recuerda en este navegador. Lo oculto tampoco se exporta (exportOptions ':visible').
    , fnAplicarColumnas: function () {
        if (!$.fn.DataTable.isDataTable('#example')) return;
        var t = $('#example').DataTable();
        var comida = [];
        for (var d = 0; d < 7; d++) comida.push(9 + 4 * d, 10 + 4 * d);
        t.columns(comida).visible($('#cbColComida').is(':checked'), false);
        t.column(2).visible($('#cbColEmpresa').is(':checked'), false);
        t.column(3).visible($('#cbColSemana').is(':checked'), false);
        t.columns([4, 5]).visible($('#cbColFechas').is(':checked'), false);
        t.column(6).visible($('#cbColDepto').is(':checked'), false);
        t.column(7).visible($('#cbColPuesto').is(':checked'), false);
        t.columns.adjust().draw(false);
    }
    // Número de semana ISO (lunes a domingo) de una fecha YYYY-MM-DD, como "Semana 43: 19 oct – 25 oct"
    , fnSemanaISO: function (f) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(f || '');
        if (!m) return '';
        var d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
        var dia = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dia);
        var inicioAnio = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil(((d - inicioAnio) / 86400000 + 1) / 7);
    }
    , fnPrepararColumnas: function () {
        var guardado = {};
        try { guardado = JSON.parse(localStorage.getItem('checadas.columnas.v3') || '{}') || {}; } catch (e) { }
        // por omisión: solo el No. semana; comida, fechas, departamento y puesto quitados; se respeta lo que el usuario dejó
        $.each({ cbColComida: 'comida', cbColDepto: 'depto', cbColPuesto: 'puesto', cbColSemana: 'semana', cbColFechas: 'fechas', cbColEmpresa: 'empresa' }, function (id, clave) {
            if (typeof guardado[clave] === 'boolean') $('#' + id).prop('checked', guardado[clave]);
        });
        $('#cbColComida, #cbColDepto, #cbColPuesto, #cbColSemana, #cbColFechas, #cbColEmpresa').on('change', function () {
            try {
                localStorage.setItem('checadas.columnas.v3', JSON.stringify({
                    comida: $('#cbColComida').is(':checked'), depto: $('#cbColDepto').is(':checked'), puesto: $('#cbColPuesto').is(':checked'),
                    semana: $('#cbColSemana').is(':checked'), fechas: $('#cbColFechas').is(':checked'), empresa: $('#cbColEmpresa').is(':checked') }));
            } catch (e) { }
            ModuloChecada.fnAplicarColumnas();
        });
    }
    //Construye la tabla 
    , fnConstruitTabla: function () {
        
        table=$('#example').DataTable({
                data: ModuloChecada.datosTablaChecadas,               
                "bDestroy": true,
                "scrollX": false,//"100%",
                "draw": true,
                "order": [[ 5, "asc" ]],
                "iDisplayLength": 10
                // En el celular las horas se ven sin segundos (07:59) para que quepa más; Copy/Excel/CSV no cambian
                , "drawCallback": function () {
                    if (!window.matchMedia || !window.matchMedia('(max-width: 767px)').matches) return;
                    $('#example tbody td').each(function () {
                        if (this.children.length) return;
                        var t = this.textContent, c = t.replace(/^\s*(\d{2}:\d{2}):\d{2}\s*$/, '$1');
                        if (c !== t) this.textContent = c;
                    });
                }
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
        "mData": function (source, type, val) { return source.Numero; },
        "defaultContent": ""
    },  
    {
        "aTargets": [1],
        "mData": function (source, type, val) { return source.NombreCompleto; },
        "defaultContent": ""
    },
    {
        // Empresa (planta) de cada empleado; útil sobre todo en Torreón unificado
        "aTargets": [2],
        "mData": function (source, type, val) { return source.nombreEmpresa; },
        "defaultContent": ""
    },
    {
        "aTargets": [3],
        "mData": function (source, type, val) { return ModuloChecada.fnSemanaISO(source.FechaInicio); },
        "defaultContent": ""
    },
    {
        "aTargets": [4],
        "mData": function (source, type, val) { return source.FechaInicio; },
        "defaultContent": ""
    },
    {
        "aTargets": [5],
        "mData": function (source, type, val) { return source.FechaFin; },
        "defaultContent": ""
    },
    {
        "aTargets": [6],
        "mData": function (source, type, val) { return source.nombreDepartamento; },
        "defaultContent": ""
    },
    {
        "aTargets": [7],
        "mData": function (source, type, val) { return source.nombrePuesto; },
        "defaultContent": ""
    },

    // LUNES
    { "aTargets": [8],  "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'LunesEntrada', 'LunesEntrada'); }, "defaultContent": "" },
    { "aTargets": [9],  "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'LunesEntrada', 'LunesEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [10],  "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'LunesEntrada', 'LunesSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [11],  "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'LunesEntrada', 'LunesSalida'); }, "defaultContent": "" },

    // MARTES
    { "aTargets": [12], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MartesEntrada', 'MartesEntrada'); }, "defaultContent": "" },
    { "aTargets": [13], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MartesEntrada', 'MartesEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [14], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MartesEntrada', 'MartesSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [15], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MartesEntrada', 'MartesSalida'); }, "defaultContent": "" },

    // MIERCOLES
    { "aTargets": [16], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MiercolesEntrada', 'MiercolesEntrada'); }, "defaultContent": "" },
    { "aTargets": [17], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MiercolesEntrada', 'MiercolesEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [18], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MiercolesEntrada', 'MiercolesSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [19], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'MiercolesEntrada', 'MiercolesSalida'); }, "defaultContent": "" },

    // JUEVES
    { "aTargets": [20], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'JuevesEntrada', 'JuevesEntrada'); }, "defaultContent": "" },
    { "aTargets": [21], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'JuevesEntrada', 'JuevesEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [22], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'JuevesEntrada', 'JuevesSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [23], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'JuevesEntrada', 'JuevesSalida'); }, "defaultContent": "" },

    // VIERNES
    { "aTargets": [24], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'ViernesEntrada', 'ViernesEntrada'); }, "defaultContent": "" },
    { "aTargets": [25], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'ViernesEntrada', 'ViernesEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [26], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'ViernesEntrada', 'ViernesSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [27], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'ViernesEntrada', 'ViernesSalida'); }, "defaultContent": "" },

    // SABADO
    { "aTargets": [28], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'SabadoEntrada', 'SabadoEntrada'); }, "defaultContent": "" },
    { "aTargets": [29], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'SabadoEntrada', 'SabadoEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [30], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'SabadoEntrada', 'SabadoSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [31], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'SabadoEntrada', 'SabadoSalida'); }, "defaultContent": "" },

    // DOMINGO
    { "aTargets": [32], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'DomingoEntrada', 'DomingoEntrada'); }, "defaultContent": "" },
    { "aTargets": [33], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'DomingoEntrada', 'DomingoEntradaComedor'); }, "defaultContent": "" },
    { "aTargets": [34], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'DomingoEntrada', 'DomingoSalidaComedor'); }, "defaultContent": "" },
    { "aTargets": [35], "mData": function (s) { return ModuloChecada.fnValorCelda(s, 'DomingoEntrada', 'DomingoSalida'); }, "defaultContent": "" }
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
    columns: ':visible'   // lo que se ocultó con las casillas (comida, departamento, puesto) no se exporta
}
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'excelHtml5',
                        exportOptions: {
    columns: ':visible'   // lo que se ocultó con las casillas (comida, departamento, puesto) no se exporta
}
                    } ),
                    $.extend( true, {}, buttonCommon, {
                        extend: 'csvHtml5',
                        exportOptions: {
    columns: ':visible'   // lo que se ocultó con las casillas (comida, departamento, puesto) no se exporta
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
        // Reporte fijo de una empresa (Checadas IBF): solo esa planta, sin poder cambiarla
        if (typeof SOLO_EMPRESA !== 'undefined' && SOLO_EMPRESA) {
            $('#planta').html('<option value="' + Number(SOLO_EMPRESA.id) + '" selected>' + $('<div>').text(SOLO_EMPRESA.nombre).html() + '</option>').prop('disabled', true);
            return;
        }
        var varURL = ModuloChecada.baseurl + "checadasBiostar/consultaPlanta";
        $.ajax({
            url:  varURL,
            data: { unificado: 1 },   // agrega "Torreón unificado" si el usuario tiene varias plantas de Torreón
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
    //Load
    , Init: function () {
        ModuloChecada.fnCargarSelect();
        ModuloChecada.fnPrepararColumnas();
        //ModuloChecada.fnObtenerDatos('2','','','','','');
    }
}
var buttonCommon = {
    exportOptions: {
        format: {
            body: function ( data, row, column, node ) {
                // Copiar / Excel / CSV: sin el HTML de las celdas (la marca roja de falta sale solo "FALTA")
                return typeof data === 'string' && data.indexOf('<') >= 0 ? $.trim($('<div>').html(data).text()) : data;
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

        ModuloChecada.fnResumenRelojes();
        $(document).on('click', '.lnkBajadasReloj', function (e) {
            e.preventDefault();
            ModuloChecada.fnVerBajadasReloj($(this).data('id'));
        });
        $(document).on('keyup', '#bajadasRelojBuscar', function () {
            var t = $(this).val().toLowerCase();
            $('#cuerpoBajadasReloj tr').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(t) >= 0);
            });
        });

		$(document).on('click', '#procesarSemana', function (e) {
    var planta=$('#planta').val();
    var departamento=$('#departamento').val();
    var puesto=$('#puesto').val();
    var noempleado=$('#noempleado').val();
    var NoSemana=$('#NoSemana').val();
    var anio=$('#anio').val();
    ModuloChecada.fnProcesarSemana(planta,departamento,puesto,noempleado,NoSemana,anio, false);
});


$(document).on('click', '#buscar', function (e) {
    var planta=$('#planta').val();
    var departamento=$('#departamento').val();
    var puesto=$('#puesto').val();
    var noempleado=$('#noempleado').val();
    var NoSemana=$('#NoSemana').val();
    var anio=$('#anio').val();
    ModuloChecada.fnObtenerDatos(planta,departamento,puesto,noempleado,NoSemana,anio);
    ModuloChecada.fnMostrarUltimoProceso(planta, NoSemana, anio);
});
        table = $('#example').DataTable();
        ModuloChecada.Init();
        $("#example").dataTable().fnDestroy();
        baseurl= "",
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
/*
            $('#planta').on('change', function() {
                $('#departamento').empty();   
                $('#puesto').empty();
                var planta=parseInt($('#planta').val());                
                var varURL = ModuloChecada.baseurl + "checadasBiostar/consultaDepartamento";
                $.ajax({
                    url:  varURL,
                    data: {
                        planta:planta,
                    },
                    type: 'post',
                    dataType: 'json'
                }).done(function (data) {                
                    perfiles= JSON.parse(JSON.stringify(data));                     
                    $('#departamento').empty();                   
                    $('#puesto').empty();  
                    $('#departamento').append('<option value="">Selecciona un departamento</option>');
                    $.each(perfiles, function( index, value ) { 
                        $('#departamento').append('<option value="'+value.ID+'">'+value.NOMBRE+'</option>');
                    });
                });
            });
            */
    /*
            $('#departamento').on('change', function() {
                var planta=parseInt($('#planta').val());                       
                var varURL = ModuloChecada.baseurl + "checadasBiostar/consultaPuesto";
                $.ajax({
                    url:  varURL,
                    data: {
                        planta:planta
                    },
                    type: 'post',
                    dataType: 'json'
                }).done(function (data) {                
                    perfiles= JSON.parse(JSON.stringify(data));                  
                    $('#puesto').empty();  
                    $('#puesto').append('<option value="">Selecciona una puesto</option>');
                    $.each(perfiles, function( index, value ) { 
                        $('#puesto').append('<option value="'+value.ID+'">'+value.descripcion+'</option>');
                    });
                });                
            });
*/


        $(document).on('click', '#buscar', function (e) {
            var planta=$('#planta').val();
            var departamento=$('#departamento').val();
            var puesto=$('#puesto').val();
            var noempleado=$('#noempleado').val();
            var NoSemana=$('#NoSemana').val();
            var anio=$('#anio').val();
            ModuloChecada.fnObtenerDatos(planta,departamento,puesto,noempleado,NoSemana,anio);
        });


        $(document).on('click', '#descargarReloj', function (e) {
            e.preventDefault();
            ModuloChecada.fnDescargarReloj();
        });

        $(document).on('click', '#actualizarNomi', function (e) {
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
            $("#tablaEmp").css("display", "none");
            $(".loadTable").css("display", "block");
            ModuloChecada.fnActualizarChecadasNomiplus();
        });

        // Filtro por incidencia y día (se aplica sobre las filas ya cargadas)
        if (!ModuloChecada._filtroReg) {
            ModuloChecada._filtroReg = true;
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'example') return true;
                var inc = $('#cbFiltroInc').val();
                if (!inc) return true;
                var dia = $('#cbFiltroDia').val();
                var source = settings.aoData[dataIndex]._aData;
                if (!source) return true;
                var dias = dia ? [dia] : ['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'];
                for (var i = 0; i < dias.length; i++) {
                    if (ModuloChecada.fnIncidenciaDia(source, dias[i]) === inc) return true;
                }
                return false;
            });
        }
        var fnBotonLimpiar = function () {
            var hayFiltro = !!($('#cbFiltroInc').val() || $('#cbFiltroDia').val());
            $('#cbFiltroLimpiar').toggle(hayFiltro);
        };
        $(document).on('change', '#cbFiltroDia, #cbFiltroInc', function () { fnBotonLimpiar(); if (table) table.draw(); });
        $(document).on('click', '#cbFiltroLimpiar', function () { $('#cbFiltroDia').val(''); $('#cbFiltroInc').val(''); fnBotonLimpiar(); if (table) table.draw(); });


    });
