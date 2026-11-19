var Respaldos = {
    base: '',
    nombres: { BASE: 'Base de datos completa', LOG: 'Log de transacciones', CHECADAS: 'Checadas (CSV en ZIP)', CODIGO: 'Código del sistema (PHP en ZIP)' },
    iconos: { BASE: 'fa-database', LOG: 'fa-history', CHECADAS: 'fa-file-excel-o', CODIGO: 'fa-code' },
    estados: {
        'OK': 'badge-success', 'ERROR': 'badge-danger', 'EN PROCESO': 'badge-info', 'BORRADO': 'badge-secondary'
    },

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnTamano: function (b) {
        if (b == null || b === '') return '';
        b = Number(b);
        if (b >= 1073741824) return (b / 1073741824).toFixed(2) + ' GB';
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        return Math.max(1, Math.round(b / 1024)) + ' KB';
    },

    fnDuracion: function (s) {
        s = Number(s || 0);
        if (s < 60) return s + ' s';
        return Math.floor(s / 60) + ' min ' + (s % 60) + ' s';
    },

    fnCargar: function () {
        $.ajax({
            url: Respaldos.base + 'controllerRespaldos/datos',
            type: 'post',
            dataType: 'json'
        }).done(function (resp) {
            if (!resp.exito) {
                $('#estadoRespaldos').html('<div class="text-danger">' + Respaldos.fnEsc(resp.mensaje) + '</div>');
                return;
            }
            Respaldos.fnPintarEstado(resp.estado);
            Respaldos.fnPintarTarjetas(resp.config);
        }).fail(function () {
            $('#estadoRespaldos').html('<div class="text-danger">Error al cargar los respaldos.</div>');
        });
        Respaldos.fnCargarHistorial();
    },

    fnPintarEstado: function (e) {
        var j = e.job || {};
        var jobOk = j.enabled == 1;
        var resultado = j.ultimoResultado == null ? '' : (j.ultimoResultado == 1
            ? ' <span class="badge badge-success">correcta</span>'
            : ' <span class="badge badge-danger">con error</span>');
        var html = '<div class="row">';
        html += '<div class="col-md-3"><b>Respaldo automático</b><br>' +
                (jobOk ? '<span class="badge badge-success"><i class="fa fa-check"></i> Activo</span> revisa cada 15 min'
                       : '<span class="badge badge-danger"><i class="fa fa-times"></i> Detenido</span> (job de SQL Server Agent)') + '</div>';
        html += '<div class="col-md-3"><b>Última revisión</b><br>' + Respaldos.fnEsc(j.ultimaCorrida || '—') + resultado +
                '<br><small class="text-muted">Siguiente: ' + Respaldos.fnEsc(j.siguiente || '—') + '</small></div>';
        if (e.log) {
            html += '<div class="col-md-3"><b>Archivo de log</b><br>' + Number(e.log.logMB).toLocaleString() + ' MB' +
                    ' <small class="text-muted">(' + Number(e.log.logUsadoMB).toLocaleString() + ' MB en uso)</small></div>';
        }
        if (e.disco) {
            var pct = e.disco.totalGB > 0 ? Math.round(100 * e.disco.libreGB / e.disco.totalGB) : 0;
            html += '<div class="col-md-3"><b>Disco del servidor ' + Respaldos.fnEsc(e.disco.unidad) + '</b><br>' +
                    '<span class="' + (pct < 15 ? 'text-danger' : '') + '">' + e.disco.libreGB + ' GB libres de ' + e.disco.totalGB + ' GB</span></div>';
        }
        html += '</div>';
        var d = e.destinos || {};
        Respaldos.destinos = d;
        var copias = '';
        $.each(d.copias || [], function (i, c) {
            var s = Respaldos.servicios[c.servicio] || Respaldos.servicios.CARPETA;
            copias += '<br><i class="fa ' + s.icono + '"></i> <b>' + s.nombre + ':</b> ' + Respaldos.fnEsc(c.ruta);
        });
        html += '<hr class="my-2"><div class="d-flex flex-wrap align-items-center justify-content-between">' +
                '<div style="font-size:13px;"><i class="fa fa-folder-open"></i> <b>Se guardan en:</b> ' + Respaldos.fnEsc(e.carpeta) +
                (d.ruta1 ? '' : ' <small class="text-muted">(predeterminada)</small>') +
                (copias || '<br><span class="text-muted"><i class="fa fa-clone"></i> Sin copias adicionales</span>') +
                '<br><small class="text-muted">Checadas: además en la base de datos NominasRespaldos (para descargarlas aquí)</small></div>' +
                '<div class="mt-2"><button class="btn btn-sm btn-primary" id="btnUbicacion" style="color:#fff !important;"><i class="fa fa-folder"></i> Cambiar ubicación</button> ' +
                '<button class="btn btn-sm btn-info" id="btnNube" style="color:#fff !important;"><i class="fa fa-cloud"></i> Agregar copia en la nube</button></div></div>';
        $('#estadoRespaldos').html(html);
    },

    servicios: {
        CARPETA:  { nombre: 'Carpeta / red', icono: 'fa-folder', ejemplo: 'Ej. E:\\Respaldos o \\\\SERVIDOR\\Respaldos' },
        MEGA:     { nombre: 'MEGA',          icono: 'fa-cloud',  ejemplo: 'Carpeta de MEGA en el servidor, ej. C:\\Users\\Administrador\\MEGA\\Respaldos' },
        GDRIVE:   { nombre: 'Google Drive',  icono: 'fa-cloud',  ejemplo: 'Carpeta de Google Drive en el servidor, ej. G:\\Mi unidad\\Respaldos' },
        ONEDRIVE: { nombre: 'OneDrive',      icono: 'fa-cloud',  ejemplo: 'Carpeta de OneDrive en el servidor, ej. C:\\Users\\Administrador\\OneDrive\\Respaldos' },
        DROPBOX:  { nombre: 'Dropbox',       icono: 'fa-cloud',  ejemplo: 'Carpeta de Dropbox en el servidor, ej. C:\\Users\\Administrador\\Dropbox\\Respaldos' },
        AZURE:    { nombre: 'Azure (directo)', icono: 'fa-cloud', ejemplo: 'https://micuenta.blob.core.windows.net/respaldos' }
    },

    /** Renglón de una copia en la ventana de ubicación. */
    fnFilaCopia: function (c) {
        c = c || { servicio: 'CARPETA', ruta: '', tieneSas: 0 };
        var opciones = '';
        $.each(Respaldos.servicios, function (k, s) {
            opciones += '<option value="' + k + '"' + (k === c.servicio ? ' selected' : '') + '>' + s.nombre + '</option>';
        });
        var $f = $('<div class="ubi-copia card mb-2"><div class="card-body p-2">' +
            '<div class="d-flex align-items-center mb-1" style="gap:6px;">' +
            '  <b class="ubi-num"></b>' +
            '  <select class="custom-select custom-select-sm ubi-servicio" style="max-width:200px;">' + opciones + '</select>' +
            '  <button type="button" class="btn btn-sm btn-danger ml-auto ubi-quitar" title="Quitar copia" style="color:#fff !important;"><i class="fa fa-trash"></i></button>' +
            '</div>' +
            '<input type="text" class="form-control form-control-sm ubi-ruta" maxlength="250">' +
            '<input type="password" class="form-control form-control-sm mt-1 ubi-sas" autocomplete="off">' +
            '<small class="text-muted ubi-ayuda"></small>' +
            '</div></div>');
        $f.find('.ubi-ruta').val(c.ruta || '');
        $f.data('tieneSas', c.tieneSas == 1);
        $('#ubiCopias').append($f);
        Respaldos.fnServicio($f);
        Respaldos.fnNumerar();
        return $f;
    },

    fnServicio: function ($f) {
        var k = $f.find('.ubi-servicio').val(), s = Respaldos.servicios[k];
        var azure = k === 'AZURE';
        $f.find('.ubi-ruta').attr('placeholder', s.ejemplo);
        $f.find('.ubi-sas').toggle(azure).attr('placeholder', $f.data('tieneSas')
            ? 'Token SAS: ya hay uno guardado; déjalo vacío para conservarlo' : 'Token SAS del contenedor (sv=...&sig=...)');
        $f.find('.ubi-ayuda').html(azure
            ? 'Portal de Azure: cuenta de almacenamiento › contenedor › <i>Tokens de acceso compartido</i>, permisos lectura, escritura, eliminación y lista. Se suben la base completa y las checadas.'
            : (k === 'CARPETA' ? 'Se copian la base completa, el log y las checadas.'
                               : 'Se guarda en la carpeta que sincroniza la aplicación de ' + s.nombre + ' en el servidor; la aplicación lo sube a la nube.'));
    },

    fnNumerar: function () {
        $('#ubiCopias .ubi-copia').each(function (i) { $(this).find('.ubi-num').text('Copia ' + (i + 1)); });
        $('#ubiAgregar').toggle($('#ubiCopias .ubi-copia').length < 3);
    },

    fnAbrirUbicacion: function (nubeDirecto) {
        var d = Respaldos.destinos || {};
        $('#ubiPredeterminada').text(d.predeterminada || '');
        $('#ubiUsarPred').prop('checked', !d.ruta1);
        $('#ubiRuta1').val(d.ruta1 || '').prop('disabled', !d.ruta1);
        $('#ubiCopias').empty();
        $.each(d.copias || [], function (i, c) { Respaldos.fnFilaCopia(c); });
        Respaldos.fnNumerar();
        // botón "Agregar copia en la nube": abre con una copia nueva de MEGA lista para llenar
        if (nubeDirecto === true && (d.copias || []).length < 3) {
            var $f = Respaldos.fnFilaCopia({ servicio: 'MEGA', ruta: '', tieneSas: 0 });
            setTimeout(function () { $f.find('.ubi-servicio').focus(); }, 500);
        }
        $('#modalUbicacion').modal('show');
    },

    fnGuardarUbicacion: function () {
        var datos = { ruta1: $('#ubiUsarPred').is(':checked') ? '' : $.trim($('#ubiRuta1').val()), copias: [] };
        if (!$('#ubiUsarPred').is(':checked') && datos.ruta1 === '') { alert('Escribe la carpeta principal o marca "Usar la carpeta predeterminada".'); return; }
        var falta = false;
        $('#ubiCopias .ubi-copia').each(function () {
            var c = { servicio: $(this).find('.ubi-servicio').val(), ruta: $.trim($(this).find('.ubi-ruta').val()), sas: $.trim($(this).find('.ubi-sas').val()) };
            if (!c.ruta) falta = true;
            datos.copias.push(c);
        });
        if (falta) { alert('Escribe la carpeta (o la URL) de cada copia, o quítala con el botón rojo.'); return; }
        var $btn = $('#btnGuardarUbicacion').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Probando...');
        $.ajax({
            url: Respaldos.base + 'controllerRespaldos/guardarDestinos',
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (typeof swal === 'function') swal(resp.exito ? 'Listo' : 'Revisa', resp.mensaje, resp.exito ? 'success' : 'warning');
            else alert(resp.mensaje);
            if (resp.exito) { $('#modalUbicacion').modal('hide'); Respaldos.fnCargar(); }
        }).fail(function () {
            alert('Error al guardar la ubicación.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-check"></i> Probar y guardar');
        });
    },

    fnPintarTarjetas: function (config) {
        var html = '';
        $.each(config, function (i, c) {
            var activo = String(c.activo) === '1';
            var t = c.tipo;
            html += '<div class="col-md-4"><div class="card"><div class="card-body">';
            html += '<h5><i class="fa ' + Respaldos.iconos[t] + '"></i> ' + Respaldos.fnEsc(Respaldos.nombres[t] || c.descripcion) + '</h5>';
            html += '<div style="font-size:13px; margin-bottom:8px;">Último correcto: <b>' + Respaldos.fnEsc(c.ultimoOk || 'ninguno') + '</b>' +
                    (c.ultimoTamano ? ' (' + Respaldos.fnTamano(c.ultimoTamano) + ')' : '') +
                    (c.ultimoEstado === 'ERROR' ? '<br><span class="badge badge-danger">El último intento falló</span>' : '') +
                    '<br><span class="text-muted">' + c.archivos + ' guardados, ' + (Respaldos.fnTamano(c.espacioBytes) || '0 KB') + '</span></div>';

            html += '<label class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input cfgActivo" data-tipo="' + t + '"' + (activo ? ' checked' : '') + '>' +
                    '<span class="custom-control-label">Automático</span></label>';
            html += '<div class="row">';
            if (t === 'LOG') {
                html += '<div class="col-6 form-group"><label>Cada (minutos)</label><input type="number" min="15" max="1440" step="15" class="form-control cfgCada" data-tipo="' + t + '" value="' + (c.cadaMinutos || 60) + '"></div>';
            } else {
                html += '<div class="col-6 form-group"><label>Diario a las</label><input type="time" class="form-control cfgHora" data-tipo="' + t + '" value="' + (c.hora || '00:00') + '"></div>';
            }
            html += '<div class="col-6 form-group"><label>Guardar (días)</label><input type="number" min="1" max="3650" class="form-control cfgRetencion" data-tipo="' + t + '" value="' + c.retencionDias + '"></div>';
            if (t === 'CHECADAS') {
                html += '<div class="col-6 form-group"><label>Días que lleva</label><input type="number" min="1" max="400" class="form-control cfgDias" data-tipo="' + t + '" value="' + (c.diasExportar || 35) + '"></div>';
            }
            html += '</div>';
            html += '<button class="btn btn-sm btn-success btnGuardarCfg" data-tipo="' + t + '"><i class="fa fa-save"></i> Guardar</button> ';
            html += '<button class="btn btn-sm btn-primary btnAhora" data-tipo="' + t + '"><i class="fa fa-play"></i> Respaldar ahora</button>';
            html += '</div></div></div>';
        });
        $('#tarjetasRespaldo').html(html);
    },

    fnCargarHistorial: function () {
        $('#cuerpoHistorial').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
        $.ajax({
            url: Respaldos.base + 'controllerRespaldos/historial',
            type: 'post',
            data: { tipo: $('#filtroTipo').val(), dias: $('#filtroDias').val() },
            dataType: 'json'
        }).done(function (data) {
            var html = '';
            $.each(data, function (i, r) {
                var archivo = r.archivo ? r.archivo.split('\\').pop() : '';
                html += '<tr>';
                html += '<td style="white-space:nowrap;">' + Respaldos.fnEsc(r.inicio) + '</td>';
                html += '<td>' + Respaldos.fnEsc(Respaldos.nombres[r.tipo] || r.tipo) + '</td>';
                html += '<td><span class="badge ' + (Respaldos.estados[r.estado] || 'badge-light') + '" style="color:#fff !important;">' + Respaldos.fnEsc(r.estado) + '</span>' +
                        (r.mensaje && r.estado === 'ERROR' ? '<br><small class="text-danger">' + Respaldos.fnEsc(r.mensaje) + '</small>' : '') + '</td>';
                html += '<td>' + (r.fin ? Respaldos.fnDuracion(r.segundos) : '') + '</td>';
                html += '<td style="white-space:nowrap;">' + Respaldos.fnTamano(r.tamanoBytes) +
                        (r.registros ? '<br><small class="text-muted">' + Number(r.registros).toLocaleString() + ' checadas</small>' : '') + '</td>';
                html += '<td><small title="' + Respaldos.fnEsc(r.archivo) + '">' + Respaldos.fnEsc(archivo) + '</small>' +
                        (r.archivo2 ? '<br><small class="text-success" title="' + Respaldos.fnEsc(r.archivo2.split(' | ').join('\n')) + '"><i class="fa fa-clone"></i> ' +
                            (r.archivo2.split(' | ').length === 1 ? 'con 1 copia' : 'con ' + r.archivo2.split(' | ').length + ' copias') + '</small>' : '') +
                        (r.mensaje && r.estado === 'OK' && r.mensaje.indexOf('No se') >= 0
                            ? '<br><small class="text-warning"><i class="fa fa-exclamation-triangle"></i> ' + Respaldos.fnEsc(r.mensaje.substring(r.mensaje.indexOf('No se'))) + '</small>' : '') + '</td>';
                html += '<td><small>' + Respaldos.fnEsc(r.origen) + '</small></td>';
                html += '<td>' + ((r.tipo === 'CHECADAS' || r.tipo === 'CODIGO') && r.estado === 'OK'
                        ? '<a class="btn btn-sm btn-info" href="' + Respaldos.base + 'controllerRespaldos/descargar/' + r.id + '" title="Descargar ZIP"><i class="fa fa-download"></i></a>'
                        : '') + '</td>';
                html += '</tr>';
            });
            if (html === '') html = '<tr><td colspan="8" class="text-center">No hay respaldos en ese periodo.</td></tr>';
            $('#cuerpoHistorial').html(html);
        }).fail(function () {
            $('#cuerpoHistorial').html('<tr><td colspan="8" class="text-center text-danger">Error al cargar el historial.</td></tr>');
        });
    },

    fnGuardarConfig: function (tipo) {
        var sel = '[data-tipo="' + tipo + '"]';
        $.ajax({
            url: Respaldos.base + 'controllerRespaldos/guardarConfig',
            type: 'post',
            data: {
                tipo: tipo,
                activo: $('.cfgActivo' + sel).is(':checked') ? 1 : 0,
                hora: $('.cfgHora' + sel).val(),
                cadaMinutos: $('.cfgCada' + sel).val(),
                retencionDias: $('.cfgRetencion' + sel).val(),
                diasExportar: $('.cfgDias' + sel).val()
            },
            dataType: 'json'
        }).done(function (resp) {
            if (typeof swal === 'function') swal(resp.exito ? 'Listo' : 'Revisa', resp.mensaje, resp.exito ? 'success' : 'warning');
            else alert(resp.mensaje);
            if (resp.exito) Respaldos.fnCargar();
        }).fail(function () {
            alert('Error al guardar la configuración.');
        });
    },

    fnRespaldarAhora: function (tipo) {
        if (!confirm('¿Hacer ahora el respaldo de "' + Respaldos.nombres[tipo] + '"?')) return;
        var $btn = $('.btnAhora[data-tipo="' + tipo + '"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Respaldando...');
        $.ajax({
            url: Respaldos.base + 'controllerRespaldos/respaldarAhora',
            type: 'post',
            data: { tipo: tipo },
            dataType: 'json'
        }).done(function (resp) {
            if (typeof swal === 'function') swal(resp.exito ? 'Listo' : 'Error', resp.mensaje, resp.exito ? 'success' : 'error');
            else alert(resp.mensaje);
            Respaldos.fnCargar();
            // el de checadas lo hace el job: se refresca hasta que termine
            if (tipo === 'CHECADAS' && resp.exito) Respaldos.fnSeguirChecadas(0);
        }).fail(function () {
            alert('No se pudo hacer el respaldo.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Respaldar ahora');
        });
    },

    fnSeguirChecadas: function (intento) {
        if (intento > 40) return;   // ~4 minutos
        setTimeout(function () {
            Respaldos.fnCargarHistorial();
            var enProceso = $('#cuerpoHistorial').text().indexOf('EN PROCESO') >= 0;
            if (enProceso || intento < 2) Respaldos.fnSeguirChecadas(intento + 1);
            else Respaldos.fnCargar();
        }, 6000);
    },

    fnInicializar: function () {
        Respaldos.fnCargar();
        $('#btnRefrescar').on('click', Respaldos.fnCargar);
        $('#filtroTipo, #filtroDias').on('change', Respaldos.fnCargarHistorial);
        $(document).on('click', '.btnGuardarCfg', function () { Respaldos.fnGuardarConfig($(this).data('tipo')); });
        $(document).on('click', '.btnAhora', function () { Respaldos.fnRespaldarAhora($(this).data('tipo')); });
        $(document).on('click', '#btnUbicacion', function () { Respaldos.fnAbrirUbicacion(false); });
        $(document).on('click', '#btnNube', function () { Respaldos.fnAbrirUbicacion(true); });
        $('#btnGuardarUbicacion').on('click', Respaldos.fnGuardarUbicacion);
        $('#ubiUsarPred').on('change', function () { $('#ubiRuta1').prop('disabled', this.checked); if (!this.checked) $('#ubiRuta1').focus(); });
        $('#ubiAgregar').on('click', function () { Respaldos.fnFilaCopia(); });
        $('#ubiCopias').on('change', '.ubi-servicio', function () { Respaldos.fnServicio($(this).closest('.ubi-copia')); });
        $('#ubiCopias').on('click', '.ubi-quitar', function () { $(this).closest('.ubi-copia').remove(); Respaldos.fnNumerar(); });
    }
};

$(document).ready(function () {
    Respaldos.fnInicializar();
});
