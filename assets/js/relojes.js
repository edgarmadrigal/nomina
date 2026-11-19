Modulo = {
    baseurl: "",
    relojes: [],
    resultados: {},          // id -> { ok, mensaje } de la última descarga hecha en esta página
    autorizadoHasta: 0,      // ms; autorización con clave de administrador
    accionPendiente: null,
    plantas: [],
    puertos: { BS2: 51211, BS1: 1470, HV: 9922 },
    tipos: { BS2: 'BioStar 2', BS1: 'FaceStation', HV: 'FaceGo' },
    grupoTorreon: '1,2,3,4,5,6,7,8,9,10,18,19,22,40,41',

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnCargarPlantas: function () {
        return $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/plantas',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            Modulo.plantas = data;
            var opciones = '', casillas = '';
            $.each(data, function (i, p) {
                opciones += '<option value="' + p.id + '">' + p.id + ' - ' + Modulo.fnEsc(p.nombre) + '</option>';
                casillas += '<div class="col-4"><label class="custom-control custom-checkbox" style="margin:0;">' +
                            '<input type="checkbox" class="custom-control-input chkGrupo" value="' + p.id + '">' +
                            '<span class="custom-control-label">' + p.id + ' - ' + Modulo.fnEsc(p.nombre) + '</span></label></div>';
            });
            $('#relojPlanta').html(opciones);
            $('#relojGrupo').html(casillas);
        });
    },

    fnCargarRelojes: function () {
        $(".loadTable").css("display", "block");
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/listar',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            $(".loadTable").css("display", "none");
            Modulo.relojes = data;
            Modulo.fnPintarTabla(data);
            Modulo.fnRevisarConexiones();
        }).fail(function () {
            $(".loadTable").css("display", "none");
            alert('Error al cargar los relojes');
        });
    },

    fnPintarTabla: function (data) {
        var html = '';
        $.each(data, function (i, r) {
            var activo = String(r.activo) === '1';
            var error = r.ultimoError ? ' <i class="fa fa-exclamation-triangle text-danger" title="' + Modulo.fnEsc(r.ultimoError) + '"></i>' : '';
            html += '<tr' + (activo ? '' : ' style="opacity:.55;"') + '>';
            var etiqueta = '<i class="fa ' + (activo ? 'fa-check' : 'fa-ban') + '"></i> ' + (activo ? 'Activo' : 'Inactivo');
            html += '<td class="text-center"><button class="btn btn-sm btnActivo ' + (activo ? 'btn-success' : 'btn-secondary') + '" data-id="' + r.id + '" data-activo="' + (activo ? 1 : 0) + '" title="Clic para ' + (activo ? 'desactivar' : 'activar') + '">' + etiqueta + '</button></td>';
            html += '<td class="estadoConexion" data-id="' + r.id + '">' +
                    (activo ? '<i class="fa fa-spinner fa-spin text-info"></i> Revisando...' : '<span class="text-muted">—</span>') + '</td>';
            html += '<td>' + Modulo.fnEsc(r.descripcion) + '</td>';
            html += '<td>' + (Modulo.tipos[r.sdk] || r.sdk) + '</td>';
            html += '<td>' + Modulo.fnEsc(r.ip) + ' : ' + r.puerto + '</td>';
            html += '<td>' + r.deviceId + '</td>';
            html += '<td>' + r.idPlanta + ' - ' + Modulo.fnEsc(r.planta) + '</td>';
            html += '<td>' + (r.fechaInicio || '') + '</td>';
            html += '<td>' + (r.ultimaDescarga || '') + error + '</td>';
            html += '<td>' + (r.ultimaChecada || '') + '</td>';
            html += '<td class="text-right">' + r.checadasHoy + '</td>';
            html += '<td class="celdaBajadas text-right" data-id="' + r.id + '" style="white-space:nowrap;">' +
                    (r.ultimoError ? Modulo.fnBadgeError(r.id) : Modulo.fnLinkBajadas(r.id, r.ultimasChecadas, false)) + '</td>';
            html += '<td>';
            html += '<button class="btn btn-sm btn-warning btnEditar" data-id="' + r.id + '" title="Editar"><i class="fa fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-success btnDescargar" data-id="' + r.id + '" title="Descargar ahora"' + (activo ? '' : ' disabled') + '><i class="fa fa-download"></i></button> ';
            html += '<button class="btn btn-sm btn-info btnEmpleados" data-id="' + r.id + '" title="Ver empleados que checaron"><i class="fa fa-users"></i></button>';
            html += '</td>';
            html += '</tr>';
        });
        if (html === '') {
            html = '<tr><td colspan="13" class="text-center">No hay relojes registrados.</td></tr>';
        }
        $('#cuerpoTablaRelojes').html(html);
    },

    /** Revisa en paralelo cada reloj activo y pinta su estado en la columna Conexión. */
    fnRevisarConexiones: function () {
        $('.estadoConexion').each(function () {
            var $celda = $(this);
            var r = $.grep(Modulo.relojes, function (x) { return String(x.id) === String($celda.data('id')); })[0];
            if (!r || String(r.activo) !== '1') return;
            $celda.html('<i class="fa fa-spinner fa-spin text-info"></i> Revisando...');
            $.ajax({
                url: Modulo.baseurl + 'controllerRelojes/revisar',
                type: 'post',
                data: { id: r.id },
                dataType: 'json'
            }).done(function (resp) {
                if (resp.exito) {
                    $celda.html('<span class="badge badge-success"><i class="fa fa-check-circle"></i> ' + Modulo.fnEsc(resp.mensaje) + '</span>');
                } else {
                    $celda.html('<span class="badge badge-danger" title="' + Modulo.fnEsc(r.ip) + '"><i class="fa fa-times-circle"></i> ' + Modulo.fnEsc(resp.mensaje) + '</span>');
                }
            }).fail(function () {
                $celda.html('<span class="badge badge-warning">No se pudo revisar</span>');
            });
        });
    },

    fnMarcarGrupo: function (grupo) {
        var ids = (grupo || '').split(',');
        $('.chkGrupo').each(function () {
            $(this).prop('checked', ids.indexOf($(this).val()) >= 0);
        });
    },

    fnAbrirModalNuevo: function () {
        $('#relojId').val('');
        $('#relojDescripcion').val('');
        $('#relojSdk').val('BS2');
        $('#relojIp').val('');
        $('#relojPuerto').val(Modulo.puertos.BS2);
        $('#relojDeviceId').val('').prop('readonly', false);
        $('#btnDetectar').prop('disabled', false);
        $('#relojPlanta').val('2');
        $('#relojFechaInicio').val(new Date().toISOString().substring(0, 10));
        $('#relojZona').val('Central Standard Time (Mexico)');
        Modulo.fnMarcarGrupo(Modulo.grupoTorreon);
        $('#relojActivo').prop('checked', true);
        $('#modalRelojTitulo').text('Agregar reloj');
        $('#modalReloj').modal('show');
    },

    fnAbrirModalEditar: function (id) {
        var r = null;
        $.each(Modulo.relojes, function (i, x) {
            if (String(x.id) === String(id)) { r = x; return false; }
        });
        if (!r) return;

        $('#relojId').val(r.id);
        $('#relojDescripcion').val(r.descripcion);
        $('#relojSdk').val(r.sdk);
        $('#relojIp').val(r.ip);
        $('#relojPuerto').val(r.puerto);
        // el ID no se cambia: es la llave de las checadas ya descargadas
        $('#relojDeviceId').val(r.deviceId).prop('readonly', true);
        $('#btnDetectar').prop('disabled', true);
        $('#relojPlanta').val(String(r.idPlanta));
        $('#relojFechaInicio').val(r.fechaInicio || '');
        $('#relojZona').val(r.zonaHoraria);
        Modulo.fnMarcarGrupo(r.grupoPlantas);
        $('#relojActivo').prop('checked', String(r.activo) === '1');
        $('#modalRelojTitulo').text('Editar reloj');
        $('#modalReloj').modal('show');
    },

    fnDetectar: function () {
        var datos = { sdk: $('#relojSdk').val(), ip: $('#relojIp').val().trim(), puerto: $('#relojPuerto').val() };
        if (!datos.ip) { alert('Captura la IP'); return; }
        var $btn = $('#btnDetectar').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Conectando...');
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/detectar',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (resp.requiereClave) {
                Modulo.fnConAutorizacion(Modulo.fnDetectar,
                    '<p>Conectarse al reloj <b>' + Modulo.fnEsc(datos.ip + ':' + datos.puerto) + '</b> para leer su número de equipo.</p>' +
                    '<p class="cc-gris">No cambia nada en el reloj.</p>', 'Detectar reloj');
                return;
            }
            if (resp.exito) $('#relojDeviceId').val(resp.deviceId);
            alert(resp.mensaje);
        }).fail(function () {
            alert('Error al conectar con el reloj');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Detectar ID');
        });
    },

    fnGuardar: function () {
        var grupo = $('.chkGrupo:checked').map(function () { return $(this).val(); }).get().join(',');
        var datos = {
            id: $('#relojId').val(),
            deviceId: $('#relojDeviceId').val().trim(),
            descripcion: $('#relojDescripcion').val().trim(),
            sdk: $('#relojSdk').val(),
            ip: $('#relojIp').val().trim(),
            puerto: $('#relojPuerto').val(),
            idPlanta: $('#relojPlanta').val(),
            grupoPlantas: grupo,
            zonaHoraria: $('#relojZona').val(),
            fechaInicio: $('#relojFechaInicio').val(),
            activo: $('#relojActivo').is(':checked') ? 1 : 0
        };
        if (!datos.descripcion || !datos.ip) {
            alert('Favor de capturar descripción e IP');
            return;
        }
        Modulo.fnConAutorizacion(function () { Modulo.fnGuardarEnviar(datos); }, Modulo.fnHtmlCambiosReloj(datos),
                                 datos.id ? 'Confirmar cambios al reloj' : 'Confirmar reloj nuevo');
    },

    /** Qué cambia en el catálogo del reloj (campo: antes → después). */
    fnHtmlCambiosReloj: function (datos) {
        var antes = $.grep(Modulo.relojes, function (r) { return String(r.id) === String(datos.id); })[0] || {};
        var campos = [['descripcion', 'Descripción'], ['deviceId', 'ID del reloj'], ['sdk', 'Tipo'], ['ip', 'IP'], ['puerto', 'Puerto'],
                      ['idPlanta', 'Planta de respaldo'], ['grupoPlantas', 'Plantas'], ['zonaHoraria', 'Zona horaria'],
                      ['fechaInicio', 'Desde'], ['activo', 'Activo']];
        var lineas = [];
        $.each(campos, function (i, c) {
            var a = antes[c[0]] == null ? '' : String(antes[c[0]]), d = datos[c[0]] == null ? '' : String(datos[c[0]]);
            if (c[0] === 'activo') { a = a === '' ? '' : (a === '1' ? 'Sí' : 'No'); d = d === '1' ? 'Sí' : 'No'; }
            if (!datos.id) { if (d !== '') lineas.push('<b>' + c[1] + ':</b> ' + Modulo.fnEsc(d)); }
            else if (a !== d) lineas.push('<b>' + c[1] + ':</b> ' + Modulo.fnEsc(a || '(vacío)') + ' → <b>' + Modulo.fnEsc(d || '(vacío)') + '</b>');
        });
        return '<p>' + (datos.id ? 'Cambios al reloj <b>' + Modulo.fnEsc(antes.descripcion || '') + '</b>:' : 'Se agrega un reloj nuevo:') + '</p>' +
               (lineas.length ? '<ul><li>' + lineas.join('</li><li>') + '</li></ul>' : '<p class="cc-gris">No cambió ningún dato.</p>');
    },

    fnGuardarEnviar: function (datos) {
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/guardar',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (resp.exito) {
                $('#modalReloj').modal('hide');
                Modulo.fnCargarRelojes();
            } else if (resp.requiereClave) {
                Modulo.fnConAutorizacion(function () { Modulo.fnGuardarEnviar(datos); }, Modulo.fnHtmlCambiosReloj(datos));
            } else {
                alert(resp.mensaje);
            }
        }).fail(function () {
            alert('Error al guardar el reloj');
        });
    },

    fnHtmlActivo: function (id, activo) {
        return '<p>' + (activo ? 'Se <b>activa</b>' : 'Se <b>desactiva</b>') + ' el reloj <b>' + Modulo.fnEsc(Modulo.fnNombreReloj(id)) + '</b>.</p>' +
               (activo ? '' : '<p class="cc-aviso">Ya no se descargarán sus checadas.</p>');
    },

    fnCambiarActivo: function (id, activo) {
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/cambiarActivo',
            type: 'post',
            data: { id: id, activo: activo ? 1 : 0 },
            dataType: 'json'
        }).done(function (resp) {
            if (resp.requiereClave) {
                Modulo.fnConAutorizacion(function () { Modulo.fnCambiarActivo(id, activo); }, Modulo.fnHtmlActivo(id, activo));
                return;
            }
            if (!resp.exito) alert(resp.mensaje);
            Modulo.fnCargarRelojes();
        }).fail(function () {
            alert('Error al cambiar el estado del reloj');
            Modulo.fnCargarRelojes();
        });
    },

    /**
     * Descarga un reloj y pinta el avance en su celda "Bajadas".
     * Regresa una promesa con { ok, checadas, mensaje }.
     */
    fnDescargarUno: function (id) {
        var $celda = $('.celdaBajadas[data-id="' + id + '"]');
        var $btn = $('.btnDescargar[data-id="' + id + '"]').prop('disabled', true);
        $celda.html('<i class="fa fa-spinner fa-spin text-info"></i> Descargando...');
        var d = $.Deferred();
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/descargar',
            type: 'post',
            data: { id: id },
            dataType: 'json'
        }).done(function (resp) {
            var m = /(\d+) checadas\./.exec(resp.mensaje || '');
            Modulo.resultados[id] = { ok: !!(resp.exito && m), mensaje: resp.mensaje || '' };
            if (resp.exito && m) {
                var n = parseInt(m[1], 10);
                $celda.html(Modulo.fnLinkBajadas(id, n, true));
                d.resolve({ ok: true, checadas: n, mensaje: resp.mensaje });
            } else {
                $celda.html(Modulo.fnBadgeError(id));
                d.resolve({ ok: false, checadas: 0, mensaje: Modulo.fnErrorDeSalida(resp.mensaje) });
            }
        }).fail(function () {
            Modulo.resultados[id] = { ok: false, mensaje: 'El servidor web no respondió (la descarga pudo tardar demasiado).' };
            $celda.html(Modulo.fnBadgeError(id));
            d.resolve({ ok: false, checadas: 0, mensaje: 'Sin respuesta del servidor' });
        }).always(function () {
            $btn.prop('disabled', false);
        });
        return d.promise();
    },

    /** Un reloj (botón del renglón): descarga y muestra el resultado. */
    fnDescargar: function (id) {
        $('#avanceDescarga').html('<i class="fa fa-spinner fa-spin text-info"></i> Descargando...');
        Modulo.fnDescargarUno(id).done(function (r) {
            $('#avanceDescarga').html(r.ok
                ? '<span class="text-success"><i class="fa fa-check"></i> ' + r.checadas + ' checadas nuevas.</span>'
                : '<span class="text-danger"><i class="fa fa-times"></i> ' + Modulo.fnEsc(Modulo.fnMensajeReloj(r.mensaje)) + '</span>');
        });
    },

    /** Todos los activos, uno por uno, mostrando el avance en cada renglón. */
    fnDescargarTodos: function () {
        var activos = $.grep(Modulo.relojes, function (r) { return String(r.activo) === '1'; });
        if (activos.length === 0) return;

        var $btn = $('#btnDescargarTodos').prop('disabled', true);
        $('#btnActualizar').prop('disabled', true);
        $.each(activos, function (i, r) {
            $('.celdaBajadas[data-id="' + r.id + '"]').html('<span class="text-muted"><i class="fa fa-clock-o"></i> En espera</span>');
        });

        var total = 0, errores = 0, i = 0;
        var siguiente = function () {
            if (i >= activos.length) {
                $btn.prop('disabled', false).html('<i class="fa fa-download"></i> Descargar todos');
                $('#btnActualizar').prop('disabled', false);
                $('#avanceDescarga').html('<span class="' + (errores ? 'text-danger' : 'text-success') + '"><i class="fa ' + (errores ? 'fa-exclamation-triangle' : 'fa-check') + '"></i> ' +
                    'Listo: ' + activos.length + ' relojes, ' + total + ' checadas nuevas' + (errores ? ', ' + errores + ' con error' : '') + '.</span>');
                return;
            }
            var r = activos[i];
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Descargando ' + (i + 1) + ' de ' + activos.length + '...');
            $('#avanceDescarga').html('<i class="fa fa-spinner fa-spin text-info"></i> ' + Modulo.fnEsc(r.descripcion) + '...');
            Modulo.fnDescargarUno(r.id).done(function (res) {
                total += res.checadas;
                if (!res.ok) errores++;
                i++;
                siguiente();
            });
        };
        siguiente();
    },

    fnHoy: function () {
        var d = new Date();
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    },

    fnVerEmpleados: function (id) {
        var r = $.grep(Modulo.relojes, function (x) { return String(x.id) === String(id); })[0];
        if (!r) return;
        $('#empleadosRelojId').val(id);
        $('#modalEmpleadosTitulo').text('Empleados que checaron — ' + r.descripcion);
        if (!$('#empleadosFecha').val()) $('#empleadosFecha').val(Modulo.fnHoy());
        $('#empleadosBuscar').val('');
        $('#modalEmpleados').modal('show');
        Modulo.fnCargarEmpleados();
    },

    fnCargarEmpleados: function () {
        $('#cuerpoEmpleados').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $('#resumenEmpleados').text('');
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/empleados',
            type: 'post',
            data: { id: $('#empleadosRelojId').val(), fecha: $('#empleadosFecha').val() },
            dataType: 'json'
        }).done(function (data) {
            var html = '', checadas = 0;
            $.each(data, function (i, e) {
                checadas += parseInt(e.checadas, 10);
                var sinNombre = !e.nombre;
                html += '<tr class="filaEmpleado"' + (sinNombre ? ' style="background:#fff3cd;"' : '') + '>';
                html += '<td>' + Modulo.fnEsc(e.numero) + '</td>';
                html += '<td>' + (sinNombre ? '<span class="text-warning">No existe en empleados</span>' : Modulo.fnEsc(e.nombre)) + '</td>';
                html += '<td>' + Modulo.fnEsc(e.planta) + '</td>';
                html += '<td class="text-center">' + (e.estatus === 'A' ? 'Activo' : (e.estatus === '' ? '' : 'Baja')) + '</td>';
                html += '<td class="text-right">' + e.checadas + '</td>';
                html += '<td>' + e.primera + '</td>';
                html += '<td>' + e.ultima + '</td>';
                html += '</tr>';
            });
            if (html === '') html = '<tr><td colspan="7" class="text-center">Nadie checó en este reloj en esa fecha.</td></tr>';
            $('#cuerpoEmpleados').html(html);
            $('#resumenEmpleados').text(data.length + ' empleados, ' + checadas + ' checadas');
        }).fail(function () {
            $('#cuerpoEmpleados').html('<tr><td colspan="7" class="text-center text-danger">Error al cargar</td></tr>');
        });
    },

    /**
     * Ejecuta una acción que modifica el catálogo. Si el usuario no es
     * administrador y no tiene autorización vigente, primero pide la clave.
     */
    // ---------- lista de cambios que se muestra antes de pedir la clave ----------
    fnListaHtml: function (items, fmt, max) {
        max = max || 40;
        var h = '<ul>';
        $.each(items.slice(0, max), function (i, x) { h += '<li>' + fmt(x) + '</li>'; });
        if (items.length > max) h += '<li class="cc-gris">… y ' + (items.length - max) + ' más</li>';
        return h + '</ul>';
    },
    fnNombreUsr: function (num) {
        var f = $.grep(Modulo.usr.filas || [], function (x) { return String(x.num) === String(num); })[0];
        return f ? (f.nombre || f.nombreReloj || '') : '';
    },
    fnHtmlUsuarios: function (nums) {
        return Modulo.fnListaHtml(nums, function (n) { return '<b>' + Modulo.fnEsc(n) + '</b> ' + Modulo.fnEsc(Modulo.fnNombreUsr(n)); });
    },
    fnHtmlPrevia: function (r) {
        var usr = function (x) { return '<b>' + Modulo.fnEsc(x.id) + '</b> ' + Modulo.fnEsc(x.nombre); };
        var h = '<p class="mb-2">Desde <b>' + Modulo.fnEsc(r.origen) + '</b> <span class="cc-gris">(lectura del ' + Modulo.fnEsc(r.fechaOrigen || '?') + ')</span></p>';
        $.each(r.relojes, function (i, d) {
            h += '<div class="cc-reloj"><b class="cc-nombre"><i class="fa fa-clock-o"></i> ' + Modulo.fnEsc(d.reloj) + '</b>';
            if (d.sinCache) {
                h += '<div class="cc-aviso">No hay lectura guardada de este reloj: no se puede mostrar el detalle (da "Actualizar del reloj" en él para verlo).</div></div>';
                return;
            }
            h += ' <span class="cc-gris">(lectura del ' + Modulo.fnEsc(d.fecha || '?') + ')</span>';
            var hay = false;
            if (d.borrarTodos) { hay = true; h += '<div class="cc-aviso"><b>Se BORRAN sus ' + d.borrarTodos + ' usuarios</b> antes de copiar.</div>'; }
            if (d.viejos.length) {
                hay = true;
                h += '<div class="cc-aviso">Se borra el número viejo de quien cambió de número (' + d.viejos.length + '):</div>' +
                     Modulo.fnListaHtml(d.viejos, function (x) { return '<b>' + Modulo.fnEsc(x.id) + '</b> ' + Modulo.fnEsc(x.nombre) + ' → queda el <b>' + Modulo.fnEsc(x.nuevo) + '</b>'; });
            }
            if (d.nuevos.length)     { hay = true; h += '<div>Se copian con su rostro y foto (' + d.nuevos.length + '):</div>' + Modulo.fnListaHtml(d.nuevos, usr); }
            if (d.actualizar.length) { hay = true; h += '<div>Se actualizan con el rostro y foto del principal (' + d.actualizar.length + '):</div>' + Modulo.fnListaHtml(d.actualizar, usr); }
            if (d.altas.length)      { hay = true; h += '<div>Se dan de alta sin rostro (' + d.altas.length + '):</div>' + Modulo.fnListaHtml(d.altas, usr); }
            if (!hay) h += '<div class="cc-gris">Sin cambios.</div>';
            if (d.sinCambio) h += '<div class="cc-gris">No se tocan: ' + d.sinCambio + '</div>';
            h += '</div>';
        });
        h += '<p class="cc-gris mb-0">Calculado con la última lectura de cada reloj. Si alguien se registró después de esa lectura, también entra en la copia.</p>';
        return h;
    },
    /** Pide al servidor la vista previa de una copia (no toca relojes) y la entrega ya en html. */
    fnPreviaCopia: function (datos, mostrar) {
        swal({ title: 'Revisando cambios…', text: 'Calculando qué se va a copiar en cada reloj.', buttons: false, closeOnClickOutside: false });
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/previaCopia', type: 'post', dataType: 'json', data: datos })
            .done(function (r) {
                swal.close();
                if (!r.exito) { swal('Aviso', r.mensaje || 'No se pudo calcular la vista previa.', 'warning'); return; }
                mostrar(Modulo.fnHtmlPrevia(r), r);
            })
            .fail(function () { swal.close(); swal('Error', 'Sin respuesta del servidor.', 'error'); });
    },

    /**
     * Antes de cambiar CUALQUIER cosa en los relojes: muestra qué se va a hacer (cambios = html o
     * texto) y pide SIEMPRE usuario y clave de un administrador, aunque quien está sea administrador.
     */
    fnConAutorizacion: function (accion, cambios, titulo) {
        $('#tituloClave').text(titulo || 'Confirmar cambios');
        $('#cambiosClave').html(cambios || '<p class="cc-aviso">Se van a hacer cambios en los relojes.</p>');
        Modulo.accionPendiente = accion;
        $('#usuarioAdmin').val('');
        $('#claveAdmin').val('');
        $('#mensajeClave').text('');
        $('#modalClave').modal('show');
        setTimeout(function () { $('#usuarioAdmin').focus(); }, 400);
    },

    fnAutorizar: function () {
        var datos = { usuario: $('#usuarioAdmin').val().trim(), password: $('#claveAdmin').val() };
        if (!datos.usuario || !datos.password) {
            $('#mensajeClave').text('Captura usuario y clave del administrador.');
            return;
        }
        var $btn = $('#btnAutorizar').prop('disabled', true);
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/autorizar',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.exito) {
                $('#mensajeClave').text(resp.mensaje);
                $('#claveAdmin').val('').focus();
                return;
            }
            // un poco antes que el servidor para no chocar con la expiración
            Modulo.autorizadoHasta = Date.now() + (resp.minutos * 60 - 15) * 1000;
            $('#modalClave').modal('hide');
            var accion = Modulo.accionPendiente;
            Modulo.accionPendiente = null;
            if (accion) setTimeout(accion, 400);   // espera a que cierre el modal
        }).fail(function () {
            $('#mensajeClave').text('Error al validar la clave.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    },

    /** Número de "Bajadas": si hay checadas se puede dar clic para ver cuáles. */
    fnLinkBajadas: function (id, n, recien) {
        if (n == null || n === '') return '';
        n = parseInt(n, 10);
        var texto = (recien ? '+' : '') + n;
        var clase = n === 0 ? 'badge-secondary' : (recien ? 'badge-success' : 'badge-info');
        return '<a href="#" class="lnkBajadas badge ' + clase + '" data-id="' + id + '" title="Ver el detalle de la descarga" style="font-size:13px; color:#fff !important; font-weight:600; padding:4px 8px;">' +
               texto + ' <i class="fa fa-search"></i></a>';
    },

    fnBadgeError: function (id) {
        return '<a href="#" class="lnkBajadas badge badge-danger" data-id="' + id + '" title="Ver por qué falló" style="font-size:13px; color:#fff !important; font-weight:600; padding:4px 8px;">' +
               '<i class="fa fa-times-circle"></i> Error <i class="fa fa-search"></i></a>';
    },

    /** Recuadro superior del modal: qué pasó en la última descarga. */
    fnPintarDetalle: function (id, r) {
        var res = Modulo.resultados[id];
        var error = res ? (res.ok ? '' : Modulo.fnErrorDeSalida(res.mensaje)) : (r && r.ultimoError ? r.ultimoError : '');
        var html = '';
        if (error) {
            var partes = /^(.*?)\s*\[Detalle: (.*)\]$/.exec(error);
            html += '<div class="alert alert-danger" style="margin-bottom:8px;"><strong><i class="fa fa-times-circle"></i> La descarga falló.</strong><br>' +
                    Modulo.fnEsc(partes ? partes[1] : error) +
                    (partes ? '<br><small class="text-muted">Detalle técnico: ' + Modulo.fnEsc(partes[2]) + '</small>' : '') + '</div>';
        } else if (r) {
            html += '<div class="alert alert-info" style="margin-bottom:8px;"><i class="fa fa-info-circle"></i> Última descarga: <strong>' +
                    Modulo.fnEsc(r.ultimaDescarga || '—') + '</strong>. ' +
                    ((r.ultimasChecadas | 0) === 0 ? 'El reloj respondió bien, pero no había checadas nuevas (solo eventos del sistema, como conexiones).' : '') + '</div>';
        }
        if (res && res.mensaje) {
            html += '<details><summary style="cursor:pointer;">Ver bitácora de la descarga</summary>' +
                    '<pre style="white-space:pre-wrap; font-size:12px; max-height:160px; overflow-y:auto; margin:6px 0 0;">' + Modulo.fnEsc(res.mensaje) + '</pre></details>';
        }
        $('#detalleBajadas').html(html);
    },

    fnErrorDeSalida: function (mensaje) {
        var m = /ERROR [^:]*\): (.*)/.exec(mensaje || '') || /ERROR: (.*)/.exec(mensaje || '');
        return m ? m[1] : (mensaje || 'Error desconocido');
    },

    fnVerBajadas: function (id) {
        var r = $.grep(Modulo.relojes, function (x) { return String(x.id) === String(id); })[0];
        $('#modalBajadasTitulo').text('Última descarga' + (r ? ' — ' + r.descripcion : ''));
        Modulo.fnPintarDetalle(id, r);
        $('#bajadasBuscar').val('');
        $('#resumenBajadas').text('');
        $('#cuerpoBajadas').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $('#modalBajadas').modal('show');
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/bajadas',
            type: 'post',
            data: { id: id },
            dataType: 'json'
        }).done(function (data) {
            var html = '', insertadas = 0;
            $.each(data, function (i, b) {
                var nueva = b.resultado.indexOf('insertada') === 0;
                if (nueva) insertadas++;
                html += '<tr class="filaBajada"' + (b.nombre ? '' : ' style="background:#fff3cd;"') + '>';
                html += '<td>' + Modulo.fnEsc(b.fechahora) + '</td>';
                html += '<td>' + Modulo.fnEsc(b.numero) + '</td>';
                html += '<td>' + (b.nombre ? Modulo.fnEsc(b.nombre) : '<span class="text-warning">No existe en empleados</span>') + '</td>';
                html += '<td>' + Modulo.fnEsc(b.planta) + '</td>';
                html += '<td><span class="badge ' + (nueva ? 'badge-success' : 'badge-secondary') + '">' + Modulo.fnEsc(b.resultado) + '</span></td>';
                html += '</tr>';
            });
            if (html === '') html = '<tr><td colspan="5" class="text-center">' +
                (Modulo.resultados[id] && !Modulo.resultados[id].ok ? 'No se descargó nada en el último intento.' : 'La última descarga no trajo checadas nuevas.') + '</td></tr>';
            $('#cuerpoBajadas').html(html);
            $('#resumenBajadas').text(data.length + ' checadas, ' + insertadas + ' pasaron a nómina');
        }).fail(function () {
            $('#cuerpoBajadas').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar</td></tr>');
        });
    },

    // ------------------------------------------------ comunicación con relojes
    comEtiquetas: { descargar: 'Bajar checadas', hora: 'Ver hora', ponerhora: 'Poner a tiempo', borrar: 'Borrar checadas',
                    alta: 'Alta de usuarios', baja: 'Quitar usuarios', copiar: 'Copiar rostros' },
    comOrden: ['descargar', 'hora', 'ponerhora', 'borrar'],
    comCorriendo: false,
    borradoContinuar: null,

    fnAbrirComunicacion: function () {
        var html = '';
        $.each(Modulo.relojes, function (i, r) {
            var activo = String(r.activo) === '1';
            html += '<tr class="comFila"' + (activo ? '' : ' style="opacity:.6;"') + '>' +
                    '<td><input type="checkbox" class="comReloj" value="' + r.id + '"></td>' +
                    '<td>' + Modulo.fnEsc(r.descripcion) + (activo ? '' : ' <small class="text-muted">(inactivo)</small>') + '</td>' +
                    '<td>' + Modulo.fnEsc(Modulo.tipos[r.sdk] || r.sdk) + '</td>' +
                    '<td>' + Modulo.fnEsc(r.ip) + '</td></tr>';
        });
        $('#comRelojes').html(html || '<tr><td colspan="4" class="text-center text-muted">No hay relojes en el catálogo.</td></tr>');
        $('#comTodos').prop('checked', false);
        $('#modalComunicacion').modal('show');
    },

    fnNombreReloj: function (id) {
        var r = $.grep(Modulo.relojes, function (x) { return String(x.id) === String(id); })[0];
        return r ? r.descripcion : ('Reloj ' + id);
    },

    fnIniciarComunicacion: function () {
        if (Modulo.comCorriendo) return;
        var ids = $('.comReloj:checked').map(function () { return this.value; }).get();
        var marcadas = $('.comAccion:checked').map(function () { return this.value; }).get();
        if (!ids.length) { swal('Aviso', 'Marca al menos un reloj.', 'warning'); return; }
        if (!marcadas.length) { swal('Aviso', 'Marca al menos una acción.', 'warning'); return; }
        var acciones = $.grep(Modulo.comOrden, function (a) { return marcadas.indexOf(a) >= 0; });

        var correr = function () { Modulo.fnCorrerComunicacion(ids, acciones); };
        var siguePaso = function () {
            if (acciones.indexOf('borrar') >= 0) Modulo.fnPedirBorrado(ids, correr);
            else correr();
        };
        // poner a tiempo requiere administrador (o su clave); borrar pide SIEMPRE clave + "BORRAR"
        if (acciones.indexOf('ponerhora') >= 0)
            Modulo.fnConAutorizacion(siguePaso,
                '<p>Se pone la hora de este equipo en ' + ids.length + ' reloj(es):</p>' +
                Modulo.fnListaHtml(ids, function (id) { return Modulo.fnEsc(Modulo.fnNombreReloj(id)); }) +
                (acciones.indexOf('borrar') >= 0 ? '<p class="cc-aviso">Después se pedirá otra vez la clave para BORRAR las checadas.</p>' : ''),
                'Poner los relojes a tiempo');
        else siguePaso();
    },

    fnPedirBorrado: function (ids, continuar) {
        Modulo.borradoContinuar = continuar;
        $('#borradoRelojes').html($.map(ids, function (id) { return '<li>' + Modulo.fnEsc(Modulo.fnNombreReloj(id)) + '</li>'; }).join(''));
        $('#borradoUsuario, #borradoClave, #borradoConfirma').val('');
        $('#borradoMensaje').text('');
        $('#modalBorrado').modal('show');
        setTimeout(function () { $('#borradoUsuario').focus(); }, 400);
    },

    fnConfirmarBorrado: function () {
        var datos = {
            usuario: $('#borradoUsuario').val().trim(),
            password: $('#borradoClave').val(),
            confirmacion: $('#borradoConfirma').val().trim()
        };
        if (!datos.usuario || !datos.password) { $('#borradoMensaje').text('Captura usuario y contraseña del administrador.'); return; }
        if (datos.confirmacion.toUpperCase() !== 'BORRAR') { $('#borradoMensaje').text('Para confirmar escribe la palabra BORRAR.'); return; }
        var $btn = $('#btnConfirmarBorrado').prop('disabled', true);
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/autorizarBorrado',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.exito) {
                $('#borradoMensaje').text(resp.mensaje);
                $('#borradoClave').val('').focus();
                return;
            }
            $('#modalBorrado').modal('hide');
            var continuar = Modulo.borradoContinuar;
            Modulo.borradoContinuar = null;
            if (continuar) setTimeout(continuar, 400);
        }).fail(function () {
            $('#borradoMensaje').text('Error al validar la clave.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    },

    /** Ejecuta cada acción en cada reloj, una por una, mostrando el avance. */
    fnCorrerComunicacion: function (ids, acciones) {
        var tareas = [];
        $.each(ids, function (i, id) {
            $.each(acciones, function (j, a) { tareas.push({ id: id, accion: a }); });
        });
        var html = '';
        $.each(tareas, function (k, t) {
            html += '<tr id="comT' + k + '"><td>' + Modulo.fnEsc(Modulo.fnNombreReloj(t.id)) + '</td>' +
                    '<td>' + Modulo.comEtiquetas[t.accion] + '</td>' +
                    '<td class="comRes"><span class="text-muted"><i class="fa fa-hourglass-o"></i> En espera</span></td></tr>';
        });
        $('#comAvance').html(html);

        Modulo.comCorriendo = true;
        var $btn = $('#btnIniciarCom').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Comunicando...');
        var k = 0, ok = 0;
        var siguiente = function () {
            if (k >= tareas.length) {
                Modulo.comCorriendo = false;
                $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Iniciar comunicaciones');
                swal(ok === tareas.length ? 'Listo' : 'Terminado',
                     ok + ' de ' + tareas.length + ' acciones se hicieron bien.' + (ok === tareas.length ? '' : ' Revisa el avance.'),
                     ok === tareas.length ? 'success' : 'warning');
                Modulo.fnCargarRelojes();
                return;
            }
            var t = tareas[k];
            var $fila = $('#comT' + k), $c = $fila.find('.comRes');
            $c.html('<i class="fa fa-spinner fa-spin"></i> Comunicando...');
            var cont = $fila.closest('.com-avance')[0];
            if (cont) cont.scrollTop = $fila[0].offsetTop - 40;
            $.ajax({
                url: Modulo.baseurl + 'controllerRelojes/comunicar',
                type: 'post',
                data: { id: t.id, accion: t.accion },
                dataType: 'json'
            }).done(function (r) {
                if (r.exito) ok++;
                $c.html((r.exito ? '<i class="fa fa-check-circle text-success"></i> ' : '<i class="fa fa-times-circle text-danger"></i> ') +
                        Modulo.fnEsc(r.exito ? r.mensaje : Modulo.fnMensajeReloj(r.mensaje)));
                if (t.accion === 'descargar') Modulo.resultados[t.id] = { ok: r.exito, mensaje: r.detalle || r.mensaje };
            }).fail(function () {
                $c.html('<i class="fa fa-times-circle text-danger"></i> Sin respuesta del servidor.');
            }).always(function () {
                k++;
                siguiente();
            });
        };
        siguiente();
    },

    fnVerHistorialCom: function () {
        $('#comHistorial').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $('#modalHistorialCom').modal('show');
        $.ajax({
            url: Modulo.baseurl + 'controllerRelojes/historialComunicacion',
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            var html = '';
            $.each(data, function (i, h) {
                html += '<tr><td style="white-space:nowrap;">' + Modulo.fnEsc(h.fecha) + '</td><td>' + Modulo.fnEsc(h.reloj) + '</td>' +
                        '<td>' + Modulo.fnEsc(Modulo.comEtiquetas[h.accion] || h.accion) + '</td><td>' + Modulo.fnEsc(h.usuario) + '</td>' +
                        '<td>' + Modulo.fnEsc(h.autorizo || '') + '</td>' +
                        '<td>' + (String(h.exito) === '1' ? '<i class="fa fa-check-circle text-success"></i> ' : '<i class="fa fa-times-circle text-danger"></i> ') +
                        Modulo.fnEsc(h.resultado) + '</td></tr>';
            });
            $('#comHistorial').html(html || '<tr><td colspan="6" class="text-center text-muted">Aún no hay comunicaciones.</td></tr>');
        }).fail(function () {
            $('#comHistorial').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar</td></tr>');
        });
    },

    // ------------------------------------------------ usuarios de los relojes
    usr: { id: null, sdk: '', filas: [], filtro: 'todos', corriendo: false },
    usrEstados: {
        falta:       { texto: 'Falta en el reloj', clase: 'badge-warning', orden: 1 },
        sinRostro:   { texto: 'Sin rostro', clase: 'badge-info', orden: 2 },
        baja:        { texto: 'Dado de baja', clase: 'badge-danger', orden: 3 },
        desconocido: { texto: 'No existe en el sistema', clase: 'badge-secondary', orden: 4 },
        ok:          { texto: 'OK', clase: 'badge-success', orden: 5 }
    },
    // qué situaciones admite cada acción
    usrAdmite: { alta: ['falta'], copiar: ['falta', 'sinRostro'], copiartodo: ['falta', 'sinRostro', 'ok', 'baja', 'desconocido'], baja: ['baja', 'desconocido'] },

    fnAbrirUsuarios: function () {
        var opciones = '', origen = '';
        $.each(Modulo.relojes, function (i, r) {
            opciones += '<option value="' + r.id + '">' + Modulo.fnEsc(r.descripcion) + ' (' + Modulo.fnEsc(Modulo.tipos[r.sdk] || r.sdk) + ')</option>';
            if (r.sdk === 'BS2') origen += '<option value="' + r.id + '">' + Modulo.fnEsc(r.descripcion) + '</option>';
        });
        $('#usrReloj').html(opciones);
        $('#usrOrigen').html(origen);
        Modulo.usr.filas = [];
        $('#usrFiltros').html('');
        $('#usrAvance').text('');
        $('#usrCuerpo').html('<tr><td colspan="7" class="text-center text-muted">Elige un reloj y da <b>Leer usuarios del reloj</b> (tarda unos segundos: los relojes tienen miles de usuarios).</td></tr>');
        Modulo.fnContarSeleccion();
        $('#usrCopiaProgreso').hide();
        Modulo.fnIniciarProgresoCopia();   // muestra la barra si hay una copia en segundo plano
        $('#modalUsuarios').modal('show');
    },

    // Carga la lista desde el CACHÉ (instantáneo). No lee el reloj; para eso está "Actualizar del reloj".
    fnLeerUsuarios: function () {
        var id = $('#usrReloj').val();
        if (!id) return;
        Modulo.fnDetenerPoll();
        var $btn = $('#btnUsrLeer').prop('disabled', true);
        $('#usrFiltros').html('');
        $('#usrCuerpo').html('<tr><td colspan="7" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr>');
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/usuariosReloj', type: 'post', data: { id: id }, dataType: 'json' })
        .done(function (r) {
            if (!r.exito) {
                $('#usrCuerpo').html('<tr><td colspan="7" class="text-center text-danger"><i class="fa fa-times-circle"></i> ' + Modulo.fnEsc(Modulo.fnMensajeReloj(r.mensaje)) + '</td></tr>');
                return;
            }
            Modulo.fnMostrarUltima(r.fecha);
            if (r.sinCache) {
                Modulo.usr.id = id; Modulo.usr.sdk = r.sdk; Modulo.usr.filas = [];
                $('#usrOrigen option').prop('disabled', false).filter('[value="' + id + '"]').prop('disabled', true);
                $('#btnUsrAlta').prop('disabled', r.sdk !== 'BS2');
                $('.usr-copiar').toggle(r.sdk === 'BS2');
                $('#usrFiltros').html('');
                $('#usrCuerpo').html('<tr><td colspan="7" class="text-center text-muted">Este reloj aún no se ha leído. Usa <b>Actualizar del reloj</b> para traer la lista (corre en segundo plano).</td></tr>');
            } else {
                Modulo.fnRecibirUsuarios(id, r);
            }
            if (r.leyendo) Modulo.fnPollEstado(id);   // ya había una actualización en curso
        })
        .fail(function () { $('#usrCuerpo').html('<tr><td colspan="7" class="text-center text-danger">No se pudo cargar (sin respuesta del servidor).</td></tr>'); })
        .always(function () { $btn.prop('disabled', false); });
    },

    // Traduce los códigos crudos del SDK del reloj a un mensaje que el usuario entienda.
    fnMensajeReloj: function (msg) {
        msg = msg || 'No se pudo leer el reloj.';
        if (/-102\b/.test(msg) || /no se pudo conectar/i.test(msg))
            return 'El reloj no responde: puede estar apagado o fuera de la red. Revisa en la lista la columna de estado (ping) y vuelve a intentar.';
        if (/-907\b|-10[345]\b|ocupad|busy/i.test(msg))
            return 'El reloj está ocupado en este momento (puede estar sincronizando o atendiendo una checada). Espera unos segundos e intenta de nuevo.';
        if (/-601\b|timeout|no contest/i.test(msg))
            return 'El reloj tardó demasiado en responder. Intenta de nuevo en un momento.';
        return msg;
    },

    fnMostrarUltima: function (fecha) {
        $('#usrUltima').html(fecha
            ? '<i class="fa fa-clock-o"></i> Última lectura del reloj: ' + Modulo.fnEsc(fecha)
            : '<i class="fa fa-clock-o"></i> Nunca se ha leído del reloj');
    },

    // Pinta las filas que llegan del caché o de una actualización ya terminada.
    fnRecibirUsuarios: function (id, r) {
        Modulo.usr.id = id;
        Modulo.usr.sdk = r.sdk;
        Modulo.usr.filas = r.filas.sort(function (a, b) {
            return (Modulo.usrEstados[a.estado].orden - Modulo.usrEstados[b.estado].orden) || String(a.num).localeCompare(String(b.num), undefined, { numeric: true });
        });
        $('#usrOrigen option').prop('disabled', false).filter('[value="' + id + '"]').prop('disabled', true);
        if ($('#usrOrigen option:selected').prop('disabled')) $('#usrOrigen').val($('#usrOrigen option:not(:disabled)').first().val());
        $('#btnUsrAlta').prop('disabled', r.sdk !== 'BS2');
        $('.usr-copiar').toggle(r.sdk === 'BS2');
        Modulo.usr.filtro = 'enreloj';   // por defecto: solo lo que el reloj realmente tiene
        Modulo.fnPintarUsuarios();
    },

    // Lanza la lectura del reloj en SEGUNDO PLANO y empieza a consultar su avance.
    fnActualizarUsuarios: function () {
        var id = $('#usrReloj').val();
        if (!id) return;
        $('#btnUsrActualizar').prop('disabled', true);
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/usuariosRefrescar', type: 'post', data: { id: id }, dataType: 'json' })
        .done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje || 'No se pudo iniciar.', 'warning'); $('#btnUsrActualizar').prop('disabled', false); return; }
            Modulo.fnPollEstado(id);
        })
        .fail(function () { swal('Aviso', 'No se pudo iniciar la actualización.', 'warning'); $('#btnUsrActualizar').prop('disabled', false); });
    },

    fnDetenerPoll: function () {
        if (Modulo.usr && Modulo.usr.poll) { clearInterval(Modulo.usr.poll); Modulo.usr.poll = null; }
    },

    // --- Progreso de una copia de rostros corriendo en segundo plano (barra en el modal) ---
    fnIniciarProgresoCopia: function () {
        Modulo.fnConsultarProgresoCopia();
        Modulo.copiaPoll = setInterval(Modulo.fnConsultarProgresoCopia, 4000);
    },
    fnDetenerProgresoCopia: function () {
        if (Modulo.copiaPoll) { clearInterval(Modulo.copiaPoll); Modulo.copiaPoll = null; }
    },
    fnConsultarProgresoCopia: function () {
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/copiaProgreso', type: 'post', dataType: 'json' })
        .done(function (r) {
            if (!r || !r.hay) { $('#usrCopiaProgreso').hide(); return; }
            Modulo.fnPintarProgresoCopia(r);
        });
    },
    fnPintarProgresoCopia: function (r) {
        var activo = !!r.activo;
        var fase = r.fase || (activo ? 'copiando' : 'terminado');
        var reconectando = fase === 'reconectando' || fase === 'pausa';
        var total = r.total || 0, copiados = r.copiados || 0;
        // Mientras lee las listas (o aún no sabe el total) la barra va llena y animada (indeterminada).
        var indet = activo && (fase === 'leyendo' || total === 0);
        var pct = total > 0 ? Math.round(100 * copiados / total) : 0;
        $('#usrCopiaProgreso').show();
        var $barra = $('#usrCopiaBarra').css('width', (indet ? 100 : pct) + '%')
              .text(fase === 'pausa' ? 'En pausa (reloj ocupado)…' : reconectando ? 'Reconectando…' : (indet ? 'Leyendo usuarios…' : pct + '%'));
        // En "reconectando" la barra se queda donde iba, animada y en amarillo (sigue intentando).
        $barra.toggleClass('progress-bar-animated progress-bar-striped', activo)
              .removeClass('bg-primary bg-success bg-warning')
              .addClass(reconectando ? 'bg-warning' : (activo ? 'bg-primary' : ((r.errores || fase === 'error') ? 'bg-warning' : 'bg-success')));
        var desc = Modulo.fnEsc(r.origen || '') + ' → ' + Modulo.fnEsc(r.destino || '') +
            (r.lote ? '  [' + Modulo.fnEsc(r.lote) + ']' : '') +
            (total ? '  — ' + copiados + ' de ' + total : '');
        $('#usrCopiaTexto').html(
            (reconectando
                ? '<i class="fa fa-wifi"></i> <b>Esperando al reloj…</b> (sigue intentando solo): '
                : (r.tipo === 'nombres'
                    ? (activo ? 'Poniendo la empresa en los nombres' : (fase === 'error' ? 'Cambio de nombres interrumpido' : 'Nombres con empresa terminados')) + ': '
                    : (fase === 'detenido' ? 'Copia detenida' : (activo ? 'Copiando rostros' : (fase === 'error' ? 'Copia interrumpida' : 'Copia terminada'))) + ': ')) + desc);
        $('#usrCopiaNums').text(
            'Copiados: ' + copiados +
            '  · ya tenían: ' + (r.yaTenian || 0) +
            '  · sin rostro: ' + (r.sinRostro || 0) +
            (r.reemplazados ? '  · números viejos borrados: ' + r.reemplazados : '') +
            '  · errores: ' + (r.errores || 0));

        // tiempo estimado (sin contar lo que se pierde reconectando)
        var eta = '';
        if (activo && r.etaSeg != null && total > copiados) {
            var min = Math.round(r.etaSeg / 60);
            var termina = new Date(Date.now() + r.etaSeg * 1000);
            eta = '<i class="fa fa-clock-o"></i> Faltan aprox. <b>' + (min < 1 ? 'menos de 1 min' : (min >= 60 ? Math.floor(min / 60) + ' h ' + (min % 60) + ' min' : min + ' min')) +
                  '</b> (terminaría como a las ' + termina.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) + ')';
        } else if (activo && fase === 'copiando') {
            eta = '<i class="fa fa-clock-o"></i> Calculando cuánto falta…';
        }
        $('#usrCopiaEta').html(eta);
        $('#btnUsrCopiaDetener').toggle(r.tipo !== 'nombres' && activo && !!r.destinoId)
            .prop('disabled', !!r.deteniendo)
            .html(r.deteniendo ? '<i class="fa fa-spinner fa-spin"></i> Deteniendo…' : '<i class="fa fa-stop-circle"></i> Detener copia de ' + Modulo.fnEsc(r.destino || 'este reloj'));

        // avisos: se perdió la conexión (con qué reloj, desde cuándo, causa), copia sin respuesta, detenidos, error
        var aviso = '', rojo = false;
        var rc = r.reconexion;
        if (r.sinRespuesta) {
            rojo = true;
            aviso = '<i class="fa fa-exclamation-triangle"></i> <b>La copia dejó de responder</b> (sin avance desde ' + Modulo.fnEsc(r.actualizado) +
                    '). Puede que el proceso se haya cerrado; vuelve a lanzarla: sigue donde se quedó.';
        } else if (activo && rc && rc.pausa) {
            var reanuda = new Date(String(rc.reanuda).replace(' ', 'T'));
            aviso = '<i class="fa fa-pause-circle"></i> <b>Copia en pausa: se cruzó con otra conexión en ' + Modulo.fnEsc(rc.reloj) + '</b> (código ' + rc.codigo + ').<br>' +
                    Modulo.fnEsc(rc.causa) + '<br>Se reanuda sola a las <b>' + reanuda.toLocaleTimeString('es-MX') + '</b> y continúa donde se quedó.';
        } else if (activo && rc) {
            var desde = new Date(String(rc.desde).replace(' ', 'T'));
            var mins = Math.max(0, Math.round((Date.now() - desde.getTime()) / 60000));
            aviso = '<i class="fa fa-wifi"></i> <b>Se perdió la conexión con ' + Modulo.fnEsc(rc.reloj) + '</b> desde las ' +
                    desde.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) + ' (' + mins + ' min' + (rc.intento > 0 ? ', intento ' + rc.intento + ', código ' + rc.codigo : ', sin respuesta') + ').<br>' +
                    Modulo.fnEsc(rc.causa) + '<br>La copia <b>no se rinde</b>: sigue intentando sola y continúa donde se quedó en cuanto el reloj conteste. ' +
                    'Si no quieres esperar, usa <b>Detener copia</b> (solo ese reloj).';
        } else if (fase === 'error' && r.mensaje) {
            rojo = true;
            aviso = '<i class="fa fa-times-circle"></i> <b>Error:</b> ' + Modulo.fnEsc(r.mensaje);
        } else if (r.mensaje && fase === 'detenido') {
            aviso = '<i class="fa fa-stop-circle"></i> ' + Modulo.fnEsc(r.mensaje);
        }
        if (r.detenidos && r.detenidos.length && fase !== 'detenido')
            aviso += (aviso ? '<br>' : '') + '<i class="fa fa-stop-circle"></i> Detenidos por el usuario: ' + Modulo.fnEsc(r.detenidos.join(', '));
        $('#usrCopiaAviso').toggleClass('rojo', rojo).html(aviso).toggle(!!aviso);
    },

    // Detener la copia solo del reloj actual (si hay más en la lista sigue con el siguiente)
    fnDetenerCopia: function () {
        var destino = $('#btnUsrCopiaDetener').text().replace('Detener copia de', '').trim();
        if (!confirm('¿Detener la copia de rostros hacia ' + destino + '?\n\nLo ya copiado se queda. Si hay más relojes en la lista, la copia sigue con el siguiente. Después puedes volver a lanzarla y sigue donde se quedó.')) return;
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/copiaDetener', type: 'post', dataType: 'json' })
        .done(function (r) {
            if (r.requiereClave) {
                Modulo.fnConAutorizacion(Modulo.fnDetenerCopiaSinPreguntar,
                    '<p>Detener la copia de rostros hacia <b>' + Modulo.fnEsc(destino) + '</b>.</p>', 'Detener copia');
                return;
            }
            if (!r.exito) { alert(r.mensaje); return; }
            Modulo.fnConsultarProgresoCopia();
        });
    },
    fnDetenerCopiaSinPreguntar: function () {
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/copiaDetener', type: 'post', dataType: 'json' })
        .done(function (r) { if (!r.exito) alert(r.mensaje); Modulo.fnConsultarProgresoCopia(); });
    },

    // Consulta cada 3 s si la lectura en segundo plano ya terminó; al terminar pinta las filas.
    fnPollEstado: function (id) {
        Modulo.fnDetenerPoll();
        // Estilo visible mientras actualiza (un botón "disabled" se ve casi transparente).
        var estiloActivo = { 'opacity': '1', 'background-color': '#fb8c00', 'border-color': '#fb8c00', 'color': '#fff', 'font-weight': '600' };
        var estiloAlerta = { 'opacity': '1', 'background-color': '#e53935', 'border-color': '#e53935', 'color': '#fff', 'font-weight': '600' };
        var limpiarEstilo = function () { $('#btnUsrActualizar').css({ 'opacity': '', 'background-color': '', 'border-color': '', 'color': '', 'font-weight': '' }); };
        $('#btnUsrActualizar').prop('disabled', true).css(estiloActivo).html('<i class="fa fa-spinner fa-spin"></i> Actualizando...');
        var consultar = function () {
            $.ajax({ url: Modulo.baseurl + 'controllerRelojes/usuariosEstado', type: 'post', data: { id: id }, dataType: 'json' })
            .done(function (r) {
                if (!r) return;
                if (r.listo) {
                    Modulo.fnDetenerPoll();
                    limpiarEstilo();
                    $('#btnUsrActualizar').prop('disabled', false).html('<i class="fa fa-refresh"></i> Actualizar del reloj');
                    if (r.exito) { Modulo.fnMostrarUltima(r.fecha); Modulo.fnRecibirUsuarios(id, r); }
                    else swal('No se pudo leer el reloj', Modulo.fnMensajeReloj(r.mensaje), 'error');
                } else if (r.idle) {
                    Modulo.fnDetenerPoll();
                    limpiarEstilo();
                    $('#btnUsrActualizar').prop('disabled', false).html('<i class="fa fa-refresh"></i> Actualizar del reloj');
                } else {
                    var seg = r.segundos || 0;
                    if (seg > 60) {
                        // Tardando demasiado: casi siempre significa que no está conectando al reloj.
                        $('#btnUsrActualizar').css(estiloAlerta)
                            .html('<i class="fa fa-spinner fa-spin"></i> Conectando... ' + seg + ' s (tarda mucho, revisa la conexión)');
                    } else {
                        $('#btnUsrActualizar').css(estiloActivo)
                            .html('<i class="fa fa-spinner fa-spin"></i> Actualizando... ' + seg + ' s');
                    }
                }
            });
        };
        consultar();
        Modulo.usr.poll = setInterval(consultar, 3000);
    },

    fnPintarUsuarios: function () {
        var cuenta = { todos: Modulo.usr.filas.length, enreloj: 0, pendientes: 0, conrostro: 0 };
        $.each(Modulo.usrEstados, function (k) { cuenta[k] = 0; });
        var html = '';
        $.each(Modulo.usr.filas, function (i, f) {
            cuenta[f.estado]++;
            if (f.estado !== 'falta') cuenta.enreloj++;                              // está en el reloj
            if (f.estado !== 'ok' && f.estado !== 'falta') cuenta.pendientes++;       // en el reloj, con pendiente
            if (f.rostros > 0) cuenta.conrostro++;                                    // tiene rostro en el reloj
            var e = Modulo.usrEstados[f.estado];
            html += '<tr class="usrFila" data-estado="' + f.estado + '" data-rostro="' + (f.rostros > 0 ? 1 : 0) + '" data-planta="' + Modulo.fnEsc(f.planta || '') + '">' +
                    '<td><input type="checkbox" class="usrChk" value="' + Modulo.fnEsc(f.num) + '" data-estado="' + f.estado + '"></td>' +
                    '<td>' + Modulo.fnEsc(f.num) + '</td><td>' + Modulo.fnEsc(f.nombre) + '</td><td>' + Modulo.fnEsc(f.nombreReloj) + '</td>' +
                    '<td>' + Modulo.fnEsc(f.planta) + '</td>' +
                    '<td class="text-center">' + (f.rostros > 0 ? '<i class="fa fa-check text-success" title="Tiene rostro"></i>' : '<span class="text-muted">—</span>') + '</td>' +
                    '<td><span class="badge ' + e.clase + '">' + e.texto + '</span></td></tr>';
        });
        $('#usrCuerpo').html(html || '<tr><td colspan="7" class="text-center text-muted">El reloj no tiene usuarios.</td></tr>');

        // "En el reloj" (lo que el reloj realmente tiene) primero y por defecto; luego el desglose
        // de esos; al final "Agregar de Microsip" (los activos que faltan) y "Todos".
        var filtros = [['enreloj', 'En el reloj', 'badge-success'], ['conrostro', 'Con rostro', 'badge-success']];
        $.each(Modulo.usrEstados, function (k, e) { if (k !== 'falta') filtros.push([k, e.texto, e.clase]); });
        filtros.push(['falta', 'Agregar de Microsip', 'badge-warning']);
        filtros.push(['todos', 'Todos', 'badge-light']);
        $('#usrFiltros').html($.map(filtros, function (f) {
            return '<span class="badge usr-filtro ' + f[2] + (Modulo.usr.filtro === f[0] ? ' activo' : '') + '" data-f="' + f[0] + '">' + f[1] + ': ' + cuenta[f[0]] + '</span>';
        }).join(''));
        $('#usrTodos').prop('checked', false);
        // flecha de orden en el encabezado de la columna activa
        $('#modalUsuarios .usr-flecha').attr('class', 'fa usr-flecha');
        if (Modulo.usr.orden && Modulo.usr.orden.col) {
            $('#modalUsuarios .usr-orden[data-sort="' + Modulo.usr.orden.col + '"] .usr-flecha')
                .addClass(Modulo.usr.orden.dir > 0 ? 'fa-sort-up' : 'fa-sort-down');
        }
        Modulo.fnLlenarPlantas();
        Modulo.fnFiltrarUsuarios();
    },

    // Ordena la lista por la columna del encabezado; segundo clic invierte el sentido.
    fnOrdenarUsuarios: function (col) {
        if (!col || !Modulo.usr.filas) return;
        var o = Modulo.usr.orden || (Modulo.usr.orden = { col: null, dir: 1 });
        o.dir = (o.col === col) ? -o.dir : 1;
        o.col = col;
        var dir = o.dir;
        Modulo.usr.filas.sort(function (a, b) {
            var r;
            if (col === 'rostros')      r = (Number(a.rostros) || 0) - (Number(b.rostros) || 0);
            else if (col === 'estado')  r = Modulo.usrEstados[a.estado].orden - Modulo.usrEstados[b.estado].orden;
            else                        r = String(a[col] || '').localeCompare(String(b[col] || ''), undefined, { numeric: true });
            return (r * dir) || String(a.num).localeCompare(String(b.num), undefined, { numeric: true });
        });
        Modulo.fnPintarUsuarios();
    },

    // Llena el selector de planta con las plantas presentes en la lista (conserva la elegida).
    fnLlenarPlantas: function () {
        var set = {};
        $.each(Modulo.usr.filas, function (i, f) { if (f.planta) set[f.planta] = true; });
        var plantas = Object.keys(set).sort(function (a, b) { return a.localeCompare(b, undefined, { numeric: true }); });
        var sel = $('#usrPlanta').val() || '';
        var html = '<option value="">Todas las plantas</option>';
        $.each(plantas, function (i, p) { html += '<option value="' + Modulo.fnEsc(p) + '"' + (p === sel ? ' selected' : '') + '>' + Modulo.fnEsc(p) + '</option>'; });
        $('#usrPlanta').html(html);
    },

    fnFiltrarUsuarios: function () {
        var f = Modulo.usr.filtro, t = ($('#usrBuscar').val() || '').toLowerCase();
        var planta = $('#usrPlanta').val() || '';
        $('#usrCuerpo .usrFila').each(function () {
            var est = this.getAttribute('data-estado');
            var pasa = (f === 'todos' ? true
                      : f === 'enreloj' ? est !== 'falta'
                      : f === 'conrostro' ? this.getAttribute('data-rostro') === '1'
                      : f === 'pendientes' ? (est !== 'ok' && est !== 'falta')
                      : est === f) &&
                       (!planta || this.getAttribute('data-planta') === planta) &&
                       (!t || this.textContent.toLowerCase().indexOf(t) >= 0);
            this.style.display = pasa ? '' : 'none';
            if (!pasa) $(this).find('.usrChk').prop('checked', false);
        });
        Modulo.fnContarSeleccion();
    },

    fnContarSeleccion: function () {
        $('#usrSeleccion').text($('.usrChk:checked').length + ' seleccionados');
    },

    /** Números seleccionados que admite la acción; avisa cuántos se ignoran. */
    fnSeleccionPara: function (accion) {
        var admite = Modulo.usrAdmite[accion], nums = [], otros = 0;
        $('.usrChk:checked').each(function () {
            if (admite.indexOf(this.getAttribute('data-estado')) >= 0) nums.push(this.value); else otros++;
        });
        return { nums: nums, otros: otros };
    },

    fnUsrAlta: function () {
        var s = Modulo.fnSeleccionPara('alta');
        if (!s.nums.length) { swal('Aviso', 'Selecciona empleados en "Falta en el reloj".', 'warning'); return; }
        Modulo.fnConAutorizacion(function () {
            Modulo.fnUsrCorrer('alta', s.nums, {}, s.otros);
        }, '<p>Se dan de alta en el reloj <b>' + Modulo.fnEsc($('#usrReloj option:selected').text()) + '</b> (número y nombre, sin rostro) ' +
           s.nums.length + ' empleado(s):</p>' + Modulo.fnHtmlUsuarios(s.nums), 'Dar de alta en el reloj');
    },

    fnUsrCopiar: function () {
        var s = Modulo.fnSeleccionPara('copiar');
        var origen = $('#usrOrigen').val();
        if (!origen) { swal('Aviso', 'Elige el reloj de origen.', 'warning'); return; }
        if (!s.nums.length) { swal('Aviso', 'Selecciona empleados en "Falta en el reloj" o "Sin rostro".', 'warning'); return; }
        var reemplazar = $('#usrReemplazar').is(':checked') ? 1 : 0;
        Modulo.fnPreviaCopia({ origen: origen, destinos: [Modulo.usr.id], nums: s.nums, reemplazar: reemplazar }, function (html) {
            Modulo.fnConAutorizacion(function () {
                Modulo.fnUsrCorrer('copiar', s.nums, { origen: origen, reemplazar: reemplazar }, s.otros);
            }, html, 'Copiar rostro a este reloj');
        });
    },

    // Igual que copiar, pero además da de alta (número y nombre) a los que no tienen rostro en el origen.
    fnUsrCopiarTodo: function () {
        var s = Modulo.fnSeleccionPara('copiartodo');
        var origen = $('#usrOrigen').val();
        if (!origen) { swal('Aviso', 'Elige el reloj de origen.', 'warning'); return; }
        if (!s.nums.length) { swal('Aviso', 'Selecciona usuarios para copiar desde el otro reloj.', 'warning'); return; }
        var reemplazar = $('#usrReemplazar').is(':checked') ? 1 : 0;
        Modulo.fnPreviaCopia({ origen: origen, destinos: [Modulo.usr.id], nums: s.nums, incluir: 1, reemplazar: reemplazar }, function (html) {
            Modulo.fnConAutorizacion(function () {
                Modulo.fnUsrCorrer('copiar', s.nums, { origen: origen, incluir: 1, reemplazar: reemplazar }, s.otros);
            }, html, 'Copiar a este reloj (+ sin rostro)');
        });
    },

    // Copiar TODOS los usuarios del reloj PRINCIPAL (el de arriba, "Reloj:") a varios relojes a la vez.
    fnAbrirCopiarVarios: function () {
        var origenId = $('#usrReloj').val();
        if (!origenId) { swal('Aviso', 'Elige primero el reloj principal arriba, en "Reloj:".', 'warning'); return; }
        var r0 = $.grep(Modulo.relojes, function (r) { return String(r.id) === String(origenId); })[0];
        if (!r0 || r0.sdk !== 'BS2') { swal('Aviso', 'El reloj principal debe ser BioStar 2.', 'warning'); return; }
        $('#cvOrigen').text($('#usrReloj option:selected').text());
        var html = '';
        $.each(Modulo.relojes, function (i, r) {
            if (r.sdk === 'BS2' && String(r.id) !== String(origenId))
                html += '<label class="d-block mb-1" style="font-size:13px;"><input type="checkbox" class="cvDest" value="' + r.id + '" checked> ' + Modulo.fnEsc(r.descripcion) + '</label>';
        });
        $('#cvDestinos').html(html || '<span class="text-muted">No hay otros relojes BioStar 2.</span>');
        $('#cvReemplazar').prop('checked', $('#usrReemplazar').is(':checked'));
        $('#cvIncluir').prop('checked', false);
        $('#cvBorrar').prop('checked', false);
        $('#modalCopiarVarios').modal('show');
    },
    // Un clic: del principal ("Reloj:") a TODOS los demás BioStar 2, solo los empleados nuevos.
    // Es la misma copia a varios relojes sin "Reemplazar" ni "sin rostro": a quien ya tiene rostro
    // en el destino no se le toca (y RelojSync quita el número viejo de quien cambió de número).
    fnCopiarNuevos: function () {
        var origen = $('#usrReloj').val();
        if (!origen) { swal('Aviso', 'Elige primero el reloj principal arriba, en "Reloj:".', 'warning'); return; }
        var r0 = $.grep(Modulo.relojes, function (r) { return String(r.id) === String(origen); })[0];
        if (!r0 || r0.sdk !== 'BS2') { swal('Aviso', 'El reloj principal debe ser BioStar 2.', 'warning'); return; }
        Modulo.fnElegirRelojes('Copiar empleados nuevos',
            'Desde el principal <b>' + Modulo.fnEsc(r0.descripcion) + '</b> se copian los que aún no tienen rostro en cada reloj. Elige los relojes destino:',
            origen, function (ids) { Modulo.fnCopiarNuevosA(origen, ids); });
    },
    fnCopiarNuevosA: function (origen, ids) {
        var datos = { origen: origen, destinos: ids, reemplazar: 0, incluir: 0, borrar: 0 };
        Modulo.fnPreviaCopia(datos, function (html) {
            Modulo.fnConAutorizacion(function () {
                $.ajax({ url: Modulo.baseurl + 'controllerRelojes/copiarVarios', type: 'post', dataType: 'json', data: datos })
                    .done(function (r) {
                        if (r.exito) {
                            Modulo.fnIniciarProgresoCopia();
                            swal('En proceso', 'Copiando empleados nuevos a ' + r.destinos + ' reloj(es). Sigue el avance en la barra de arriba.', 'success');
                        } else {
                            swal('Aviso', r.mensaje || 'No se pudo iniciar.', 'warning');
                        }
                    }).fail(function () { swal('Error', 'Sin respuesta del servidor.', 'error'); });
            }, html, 'Copiar empleados nuevos');
        });
    },
    fnCopiarVariosRun: function () {
        var origen = $('#usrReloj').val();
        var destinos = $('.cvDest:checked').map(function () { return this.value; }).get();
        if (!destinos.length) { swal('Aviso', 'Marca al menos un reloj destino.', 'warning'); return; }
        var borrar = $('#cvBorrar').is(':checked') ? 1 : 0;
        var datos = {
            origen: origen, destinos: destinos,
            reemplazar: $('#cvReemplazar').is(':checked') ? 1 : 0,
            incluir: $('#cvIncluir').is(':checked') ? 1 : 0,
            borrar: borrar
        };

        // La clave ya se validó (modal de cambios, o el de BORRAR que también autoriza el catálogo).
        var lanzar = function () {
            $.ajax({ url: Modulo.baseurl + 'controllerRelojes/copiarVarios', type: 'post', dataType: 'json', data: datos })
                .done(function (r) {
                    if (r.exito) {
                        $('#modalCopiarVarios').modal('hide');
                        Modulo.fnIniciarProgresoCopia();
                        swal('En proceso', 'Copiando a ' + r.destinos + ' reloj(es). Sigue el avance en la barra de arriba.', 'success');
                    } else {
                        swal('Aviso', r.mensaje || 'No se pudo iniciar.', 'warning');
                    }
                }).fail(function () { swal('Error', 'Sin respuesta del servidor.', 'error'); });
        };

        // Cerrar primero el modal de "copiar a varios" para no apilar modales (el fondo del de
        // atrás puede tapar el botón).
        $('#modalCopiarVarios').modal('hide');
        Modulo.fnPreviaCopia(datos, function (html) {
            if (borrar) {
                // Operación peligrosa (vacía los relojes destino): clave de administrador + "BORRAR",
                // con la lista de cambios en el mismo modal.
                Modulo.cvLanzarPendiente = lanzar;
                $('#cbCuantos').text(destinos.length);
                $('#cbCambios').html(html);
                $('#cbUsuario, #cbClave, #cbConfirma').val('');
                $('#cbMensaje').text('');
                setTimeout(function () {
                    $('#modalCopiaBorrar').modal('show');
                    setTimeout(function () { $('#cbUsuario').focus(); }, 400);
                }, 350);
            } else {
                Modulo.fnConAutorizacion(lanzar, html, 'Copiar a varios relojes');
            }
        });
    },

    /** Valida la clave del administrador antes de borrar y reemplazar; si pasa, lanza la copia. */
    fnConfirmarCopiaBorrar: function () {
        var datos = { tipo: 'copiaborrar', usuario: $('#cbUsuario').val().trim(), password: $('#cbClave').val(), confirmacion: $('#cbConfirma').val().trim() };
        if (!datos.usuario || !datos.password) { $('#cbMensaje').text('Captura usuario y contraseña del administrador.'); return; }
        if (datos.confirmacion.toUpperCase() !== 'BORRAR') { $('#cbMensaje').text('Para confirmar escribe la palabra BORRAR.'); return; }
        var $btn = $('#btnConfirmarCopiaBorrar').prop('disabled', true);
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/autorizarBorrado', type: 'post', data: datos, dataType: 'json' })
            .done(function (resp) {
                if (!resp.exito) { $('#cbMensaje').text(resp.mensaje); $('#cbClave').val('').focus(); return; }
                $('#modalCopiaBorrar').modal('hide');
                var f = Modulo.cvLanzarPendiente;
                Modulo.cvLanzarPendiente = null;
                if (typeof f === 'function') setTimeout(f, 300);
            })
            .fail(function () { $('#cbMensaje').text('Error al validar la clave.'); })
            .always(function () { $btn.prop('disabled', false); });
    },

    fnUsrQuitar: function () {
        var s = Modulo.fnSeleccionPara('baja');
        if (!s.nums.length) { swal('Aviso', 'Selecciona empleados en "Dado de baja" o "No existe en el sistema". Los activos no se quitan.', 'warning'); return; }
        Modulo.usr.quitarPendiente = s;
        $('#quitarCuantos').text(s.nums.length);
        $('#quitarReloj').text($('#usrReloj option:selected').text());
        $('#quitarLista').html(Modulo.fnHtmlUsuarios(s.nums));
        $('#quitarUsuario, #quitarClave, #quitarConfirma').val('');
        $('#quitarMensaje').text('');
        $('#modalQuitar').modal('show');
        setTimeout(function () { $('#quitarUsuario').focus(); }, 400);
    },

    /**
     * Paso previo de las acciones de varios relojes: lista de relojes BioStar 2 activos (todos
     * marcados) para que la persona desmarque los que no quiere; luego llama continuar(ids).
     */
    fnElegirRelojes: function (titulo, texto, excluirId, continuar) {
        var html = '';
        $.each(Modulo.relojes, function (i, r) {
            if (r.sdk === 'BS2' && String(r.activo) === '1' && String(r.id) !== String(excluirId || ''))
                html += '<label class="d-block mb-1" style="font-size:13px; cursor:pointer;"><input type="checkbox" class="erReloj" value="' + r.id + '" checked> ' +
                        Modulo.fnEsc(r.descripcion) + ' <span class="text-muted">(' + Modulo.fnEsc(r.ip) + ')</span></label>';
        });
        if (!html) { swal('Aviso', 'No hay relojes BioStar 2 activos para elegir.', 'warning'); return; }
        $('#erTitulo').text(titulo);
        $('#erTexto').html(texto);
        $('#erLista').html(html);
        $('#erTodos').prop('checked', true).prop('indeterminate', false);
        Modulo.erContinuar = continuar;
        $('#modalElegirRelojes').modal('show');
    },
    fnElegirRelojesOk: function () {
        var ids = $('.erReloj:checked').map(function () { return this.value; }).get();
        if (!ids.length) { swal('Aviso', 'Marca al menos un reloj.', 'warning'); return; }
        var f = Modulo.erContinuar;
        Modulo.erContinuar = null;
        $('#modalElegirRelojes').modal('hide');
        if (typeof f === 'function') setTimeout(function () { f(ids); }, 350);   // espera a que cierre el modal
    },

    /** En los BioStar 2 que se elijan: quitar a los que no tienen rostro y están de baja o no existen. */
    fnLimpiarSinRostro: function () {
        Modulo.fnElegirRelojes('Quitar bajas sin rostro',
            'Se quitan los que <b>no tienen rostro</b> y están <b>dados de baja o no existen</b> en el sistema. Elige los relojes:',
            null, Modulo.fnLimpiarSinRostroEn);
    },
    fnLimpiarSinRostroEn: function (ids) {
        swal({ title: 'Revisando relojes…', text: 'Buscando bajas sin rostro en cada reloj.', buttons: false, closeOnClickOutside: false });
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/previaLimpieza', type: 'post', dataType: 'json', data: { relojes: ids } })
            .done(function (r) {
                swal.close();
                var total = 0, multi = [], h = '';
                $.each(r.relojes || [], function (i, d) {
                    h += '<div class="cc-reloj"><b class="cc-nombre"><i class="fa fa-clock-o"></i> ' + Modulo.fnEsc(d.reloj) + '</b>';
                    if (d.sinCache) { h += '<div class="cc-aviso">No hay lectura guardada: no se revisa este reloj (da "Actualizar del reloj" en él).</div></div>'; return; }
                    h += ' <span class="cc-gris">(lectura del ' + Modulo.fnEsc(d.fecha || '?') + ')</span>';
                    if (!d.usuarios.length) { h += '<div class="cc-gris">Nada que quitar.</div></div>'; return; }
                    total += d.usuarios.length;
                    multi.push({ id: d.id, reloj: d.reloj, nums: $.map(d.usuarios, function (u) { return u.id; }) });
                    h += '<div>Se quitan ' + d.usuarios.length + ':</div>' + Modulo.fnListaHtml(d.usuarios, function (u) {
                        return '<b>' + Modulo.fnEsc(u.id) + '</b> ' + Modulo.fnEsc(u.nombre) +
                               ' <span class="cc-gris">(' + (u.estado === 'baja' ? 'dado de baja' : 'no existe en el sistema') + ')</span>';
                    }) + '</div>';
                });
                if (!total) { swal('Listo', 'No hay bajas sin rostro que quitar (según la última lectura de cada reloj).', 'info'); return; }
                h += '<p class="cc-gris mb-0">Justo antes de quitar a cada uno se revisa en el reloj que no tenga rostro; si ya se lo registraron, no se quita.</p>';
                Modulo.usr.quitarPendiente = { multi: multi };
                $('#quitarCuantos').text(total);
                $('#quitarReloj').text(multi.length + ' reloj(es) elegido(s)');
                $('#quitarLista').html(h);
                $('#quitarUsuario, #quitarClave, #quitarConfirma').val('');
                $('#quitarMensaje').text('');
                $('#modalQuitar').modal('show');
                setTimeout(function () { $('#quitarUsuario').focus(); }, 400);
            })
            .fail(function () { swal.close(); swal('Error', 'Sin respuesta del servidor.', 'error'); });
    },

    /** Quita por bloques en varios relojes, uno tras otro, y al final resume. */
    fnQuitarVarios: function (multi) {
        var res = { hechos: 0, conRostro: 0, protegidos: 0, errores: [] }, i = 0, j = 0, bloque = 200;
        var siguiente = function () {
            if (i >= multi.length) {
                $('#usrAvance').text('');
                swal(res.errores.length ? 'Terminado con avisos' : 'Listo',
                     'Quitados: ' + res.hechos +
                     (res.conRostro ? '\nNo se quitaron porque ya tienen rostro: ' + res.conRostro : '') +
                     (res.protegidos ? '\nActivos protegidos (no se quitaron): ' + res.protegidos : '') +
                     (res.errores.length ? '\nCon error: ' + res.errores.length + ' (' + res.errores.slice(0, 5).join('; ') + ')' : ''),
                     res.errores.length ? 'warning' : 'success');
                if (Modulo.usr.id) Modulo.fnLeerUsuarios();
                return;
            }
            var d = multi[i], parte = d.nums.slice(j, j + bloque);
            $('#usrAvance').html('<i class="fa fa-spinner fa-spin"></i> Quitando en ' + Modulo.fnEsc(d.reloj) + ' (' + Math.min(j + bloque, d.nums.length) + ' de ' + d.nums.length + ')…');
            $.ajax({ url: Modulo.baseurl + 'controllerRelojes/bajaUsuarios', type: 'post', dataType: 'json',
                     data: { id: d.id, nums: parte, soloSinRostro: 1 } })
                .done(function (r) {
                    if (!r.exito) res.errores.push(d.reloj + ': ' + r.mensaje);
                    else {
                        res.hechos += r.datos.hechos.length;
                        res.conRostro += (r.datos.conRostro || []).length;
                        res.protegidos += (r.activosProtegidos || []).length;
                        $.each(r.datos.errores || [], function (k, e) { res.errores.push(d.reloj + ' ' + e.id + ': ' + e.mensaje); });
                    }
                })
                .fail(function () { res.errores.push(d.reloj + ': sin respuesta del servidor'); })
                .always(function () {
                    j += bloque;
                    if (j >= d.nums.length) { i++; j = 0; }
                    siguiente();
                });
        };
        siguiente();
    },

    /** En TODOS los BioStar 2: poner la empresa al final del nombre ("NOMBRE · SPM"). */
    fnNombresEmpresa: function () {
        swal({ title: 'Revisando relojes…', text: 'Calculando qué nombres cambian.', buttons: false, closeOnClickOutside: false });
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/previaNombres', type: 'post', dataType: 'json' })
            .done(function (r) {
                swal.close();
                var total = 0, h = '';
                $.each(r.relojes || [], function (i, d) {
                    h += '<div class="cc-reloj"><b class="cc-nombre"><i class="fa fa-clock-o"></i> ' + Modulo.fnEsc(d.reloj) + '</b>';
                    if (d.sinCache) { h += '<div class="cc-aviso">No hay lectura guardada: no se cambia este reloj (da "Actualizar del reloj" en él).</div></div>'; return; }
                    h += ' <span class="cc-gris">(lectura del ' + Modulo.fnEsc(d.fecha || '?') + ')</span>';
                    total += d.cambios.length;
                    h += (d.cambios.length
                        ? '<div>Cambian ' + d.cambios.length + ' nombre(s):</div>' + Modulo.fnListaHtml(d.cambios, function (c) {
                              return '<b>' + Modulo.fnEsc(c.id) + '</b> ' + Modulo.fnEsc(c.antes || '(sin nombre)') + ' → <b>' + Modulo.fnEsc(c.despues) + '</b>';
                          }, 15)
                        : '<div class="cc-gris">Todos ya traen su empresa.</div>') + '</div>';
                });
                if (!total) { swal('Listo', 'Todos los nombres ya traen su empresa (según la última lectura de cada reloj).', 'info'); return; }
                h = '<p>Se pone la empresa al final del nombre de <b>' + total + '</b> empleado(s). Su rostro, foto y tarjeta no se tocan. ' +
                    'Corre en segundo plano (puede tardar: cada reloj por separado).</p>' + h;
                Modulo.fnConAutorizacion(function () {
                    $.ajax({ url: Modulo.baseurl + 'controllerRelojes/renombrarVarios', type: 'post', dataType: 'json' })
                        .done(function (x) {
                            if (x.exito) {
                                Modulo.fnIniciarProgresoCopia();
                                swal('En proceso', 'Poniendo la empresa a ' + x.usuarios + ' nombre(s) en ' + x.relojes + ' reloj(es). Sigue el avance en la barra de arriba.', 'success');
                            } else swal('Aviso', x.mensaje || 'No se pudo iniciar.', 'warning');
                        }).fail(function () { swal('Error', 'Sin respuesta del servidor.', 'error'); });
                }, h, 'Empresa en los nombres');
            })
            .fail(function () { swal.close(); swal('Error', 'Sin respuesta del servidor.', 'error'); });
    },

    fnConfirmarQuitar: function () {
        var datos = { tipo: 'quitar', usuario: $('#quitarUsuario').val().trim(), password: $('#quitarClave').val(), confirmacion: $('#quitarConfirma').val().trim() };
        if (!datos.usuario || !datos.password) { $('#quitarMensaje').text('Captura usuario y contraseña del administrador.'); return; }
        if (datos.confirmacion.toUpperCase() !== 'QUITAR') { $('#quitarMensaje').text('Para confirmar escribe la palabra QUITAR.'); return; }
        var $btn = $('#btnConfirmarQuitar').prop('disabled', true);
        $.ajax({ url: Modulo.baseurl + 'controllerRelojes/autorizarBorrado', type: 'post', data: datos, dataType: 'json' })
            .done(function (resp) {
                if (!resp.exito) { $('#quitarMensaje').text(resp.mensaje); $('#quitarClave').val('').focus(); return; }
                $('#modalQuitar').modal('hide');
                var s = Modulo.usr.quitarPendiente;
                if (s.multi) setTimeout(function () { Modulo.fnQuitarVarios(s.multi); }, 400);
                else setTimeout(function () { Modulo.fnUsrCorrer('baja', s.nums, {}, s.otros); }, 400);
            })
            .fail(function () { $('#quitarMensaje').text('Error al validar la clave.'); })
            .always(function () { $btn.prop('disabled', false); });
    },

    /** Manda la acción por bloques (los relojes tardan) y al final resume y vuelve a leer el reloj. */
    fnUsrCorrer: function (accion, nums, extra, ignorados) {
        if (Modulo.usr.corriendo) return;
        var urls = { alta: 'altaUsuarios', baja: 'bajaUsuarios', copiar: 'copiarRostros' };
        var bloque = accion === 'copiar' ? 100 : 200;
        var total = { hechos: 0, yaEstaban: 0, sinRostro: 0, errores: [], protegidos: 0, reemplazados: [] };
        var i = 0;
        Modulo.usr.corriendo = true;
        $('#btnUsrAlta, #btnUsrCopiar, #btnUsrQuitar, #btnUsrLeer').prop('disabled', true);
        var fin = function (mensajeError) {
            Modulo.usr.corriendo = false;
            $('#btnUsrAlta').prop('disabled', Modulo.usr.sdk !== 'BS2');
            $('#btnUsrCopiar, #btnUsrQuitar, #btnUsrLeer').prop('disabled', false);
            $('#usrAvance').text('');
            var verbo = { alta: 'Dados de alta', baja: 'Quitados del reloj', copiar: 'Copiados con su rostro' }[accion];
            var txt = verbo + ': ' + total.hechos +
                      (total.yaEstaban ? '\nSin cambio (' + (accion === 'alta' ? 'ya estaban' : accion === 'baja' ? 'no estaban' : 'ya tenían rostro') + '): ' + total.yaEstaban : '') +
                      (total.sinRostro ? '\nSin rostro en el reloj de origen: ' + total.sinRostro : '') +
                      (total.reemplazados.length ? '\nNúmero viejo borrado (cambio de número): ' + total.reemplazados.length + ' (' + total.reemplazados.slice(0, 5).join(', ') + (total.reemplazados.length > 5 ? '…' : '') + ')' : '') +
                      (total.protegidos ? '\nActivos protegidos (no se quitaron): ' + total.protegidos : '') +
                      (ignorados ? '\nSeleccionados que no aplican para esta acción: ' + ignorados : '') +
                      (total.errores.length ? '\nCon error: ' + total.errores.length + ' (' + $.map(total.errores.slice(0, 5), function (e) { return e.id + ': ' + e.mensaje; }).join('; ') + ')' : '') +
                      (mensajeError ? '\n\n' + mensajeError : '');
            swal(mensajeError || total.errores.length ? 'Terminado con avisos' : 'Listo', txt, mensajeError ? 'error' : (total.errores.length ? 'warning' : 'success'));
            Modulo.fnLeerUsuarios();
        };
        var siguiente = function () {
            if (i >= nums.length) { fin(); return; }
            var parte = nums.slice(i, i + bloque);
            $('#usrAvance').html('<i class="fa fa-spinner fa-spin"></i> Procesando ' + Math.min(i + bloque, nums.length) + ' de ' + nums.length + '...');
            $.ajax({
                url: Modulo.baseurl + 'controllerRelojes/' + urls[accion],
                type: 'post',
                data: $.extend({ id: Modulo.usr.id, nums: parte }, extra),
                dataType: 'json'
            }).done(function (r) {
                if (!r.exito) { fin(r.mensaje); return; }
                total.hechos += r.datos.hechos.length;
                total.yaEstaban += r.datos.yaEstaban.length;
                total.sinRostro += r.datos.sinRostro.length;
                total.reemplazados = total.reemplazados.concat(r.datos.reemplazados || []);
                total.errores = total.errores.concat(r.datos.errores);
                total.protegidos += (r.activosProtegidos || []).length;
                i += bloque;
                siguiente();
            }).fail(function () {
                fin('Sin respuesta del servidor; lo ya hecho quedó guardado en el reloj.');
            });
        };
        siguiente();
    },

    fnInicializar: function () {
        Modulo.fnCargarPlantas().always(function () {
            Modulo.fnCargarRelojes();
        });

        // usuarios de los relojes
        $('#btnUsuariosReloj').on('click', Modulo.fnAbrirUsuarios);
        $('#btnUsrLeer').on('click', Modulo.fnLeerUsuarios);
        $('#btnUsrActualizar').on('click', Modulo.fnActualizarUsuarios);
        $('#usrBuscar').on('keyup', Modulo.fnFiltrarUsuarios);
        $('#usrPlanta').on('change', Modulo.fnFiltrarUsuarios);
        $(document).on('click', '#modalUsuarios .usr-orden', function () { Modulo.fnOrdenarUsuarios($(this).data('sort')); });
        $(document).on('click', '#usrFiltros .usr-filtro', function () {
            Modulo.usr.filtro = $(this).data('f');
            $('#usrFiltros .usr-filtro').removeClass('activo');
            $(this).addClass('activo');
            Modulo.fnFiltrarUsuarios();
        });
        $('#usrTodos').on('change', function () {
            var marcar = this.checked;
            $('#usrCuerpo .usrFila').each(function () { if (this.style.display !== 'none') $(this).find('.usrChk').prop('checked', marcar); });
            Modulo.fnContarSeleccion();
        });
        $(document).on('change', '.usrChk', Modulo.fnContarSeleccion);
        $('#btnUsrAlta').on('click', Modulo.fnUsrAlta);
        $('#btnUsrCopiar').on('click', Modulo.fnUsrCopiar);
        $('#btnUsrCopiarTodo').on('click', Modulo.fnUsrCopiarTodo);
        $('#btnUsrCopiarVarios').on('click', Modulo.fnAbrirCopiarVarios);
        $('#btnUsrCopiarNuevos').on('click', Modulo.fnCopiarNuevos);
        $('#btnUsrLimpiar').on('click', Modulo.fnLimpiarSinRostro);
        $('#btnErContinuar').on('click', Modulo.fnElegirRelojesOk);
        $(document).on('change', '#erTodos', function () { $('.erReloj').prop('checked', this.checked); });
        $(document).on('change', '.erReloj', function () {
            var n = $('.erReloj:checked').length, t = $('.erReloj').length;
            $('#erTodos').prop('checked', n === t).prop('indeterminate', n > 0 && n < t);
        });
        $('#btnUsrNombres').on('click', Modulo.fnNombresEmpresa);
        $('#btnCvCopiar').on('click', Modulo.fnCopiarVariosRun);
        $('#btnConfirmarCopiaBorrar').on('click', Modulo.fnConfirmarCopiaBorrar);
        $('#cbConfirma').on('keydown', function (e) { if (e.which === 13) Modulo.fnConfirmarCopiaBorrar(); });
        // Borrar todo implica repoblar completo: auto-marca "incluir sin rostro"
        $('#cvBorrar').on('change', function () { if (this.checked) $('#cvIncluir').prop('checked', true); });
        $('#btnUsrQuitar').on('click', Modulo.fnUsrQuitar);
        $('#btnConfirmarQuitar').on('click', Modulo.fnConfirmarQuitar);
        $('#quitarConfirma').on('keypress', function (e) { if (e.which === 13) Modulo.fnConfirmarQuitar(); });

        // comunicación con relojes
        $('#btnComunicacion').on('click', Modulo.fnAbrirComunicacion);
        $('#comTodos').on('change', function () { $('.comReloj').prop('checked', this.checked); });
        $(document).on('click', '#comRelojes .comFila td', function (e) {
            if ($(e.target).is('input')) return;
            var $c = $(this).closest('tr').find('.comReloj');
            $c.prop('checked', !$c.prop('checked'));
        });
        $('#btnIniciarCom').on('click', Modulo.fnIniciarComunicacion);
        $('#btnConfirmarBorrado').on('click', Modulo.fnConfirmarBorrado);
        $('#borradoConfirma').on('keypress', function (e) { if (e.which === 13) Modulo.fnConfirmarBorrado(); });
        $('#comVerHistorial').on('click', function (e) { e.preventDefault(); Modulo.fnVerHistorialCom(); });
        // modales encimados (clave / borrado sobre comunicación): el de arriba siempre al frente
        $(document).on('show.bs.modal', '.modal', function () {
            var z = 1050 + 10 * $('.modal.show').length;
            $(this).css('z-index', z);
            setTimeout(function () { $('.modal-backdrop').not('.modal-apilado').css('z-index', z - 1).addClass('modal-apilado'); }, 0);
        });
        $(document).on('hidden.bs.modal', '.modal', function () {
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });
        // Al cerrar el modal de usuarios se detiene el sondeo de la actualización en segundo plano
        $('#modalUsuarios').on('hidden.bs.modal', function () { Modulo.fnDetenerPoll(); Modulo.fnDetenerProgresoCopia(); });

        // Abrir el formulario no cambia nada: la clave se pide al Guardar, con la lista de cambios.
        $('#btnNuevo').on('click', Modulo.fnAbrirModalNuevo);
        $('#btnAutorizar').on('click', Modulo.fnAutorizar);
        $('#btnUsrCopiaDetener').on('click', Modulo.fnDetenerCopia);
        $('#claveAdmin').on('keypress', function (e) {
            if (e.which === 13) Modulo.fnAutorizar();
        });
        $('#btnActualizar').on('click', Modulo.fnCargarRelojes);
        $('#btnDescargarTodos').on('click', Modulo.fnDescargarTodos);
        $('#empleadosFecha').on('change', Modulo.fnCargarEmpleados);
        $('#empleadosBuscar').on('keyup', function () {
            var t = $(this).val().toLowerCase();
            $('#cuerpoEmpleados .filaEmpleado').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(t) >= 0);
            });
        });
        $(document).on('click', '.lnkBajadas', function (e) {
            e.preventDefault();
            Modulo.fnVerBajadas($(this).data('id'));
        });
        $('#bajadasBuscar').on('keyup', function () {
            var t = $(this).val().toLowerCase();
            $('#cuerpoBajadas .filaBajada').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(t) >= 0);
            });
        });
        $(document).on('click', '.btnEmpleados', function () {
            Modulo.fnVerEmpleados($(this).data('id'));
        });
        $('#btnGuardarReloj').on('click', Modulo.fnGuardar);
        $('#btnDetectar').on('click', Modulo.fnDetectar);

        // al cambiar el tipo se sugiere su puerto
        $('#relojSdk').on('change', function () {
            $('#relojPuerto').val(Modulo.puertos[$(this).val()]);
        });

        $(document).on('click', '.btnEditar', function () {
            Modulo.fnAbrirModalEditar($(this).data('id'));
        });
        $(document).on('click', '.btnDescargar', function () {
            Modulo.fnDescargar($(this).data('id'));
        });
        $(document).on('click', '.btnActivo', function () {
            var activar = String($(this).data('activo')) !== '1';
            var id = $(this).data('id');
            Modulo.fnConAutorizacion(function () { Modulo.fnCambiarActivo(id, activar); },
                                     Modulo.fnHtmlActivo(id, activar), activar ? 'Activar reloj' : 'Desactivar reloj');
        });
    }
};

$(document).ready(function () {
    Modulo.fnInicializar();
});
