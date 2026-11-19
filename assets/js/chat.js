/**
 * Chat entre usuarios (controllers/Chat.php). La lista se refresca cada 5 s y la
 * conversación abierta pide los mensajes nuevos cada 3 s.
 */
var Chat = {
    yo: 0,
    actual: 0,
    conv: null,
    ultimoId: 0,
    lista: [],
    filtro: '',
    usuarios: [],
    adjunto: null,        // { datos: dataURL, nombre }
    adjuntoFalla: null,
    enviando: false,
    nombresTipo: { GENERAL: 'Canal de todos', DIRECTO: 'Mensaje directo', GRUPO: 'Grupo', SOPORTE: 'Reporte de falla' },
    colorEstado: { 'ABIERTO': 'badge-danger', 'EN PROCESO': 'badge-warning', 'RESUELTO': 'badge-success' },
    presencias: {},       // { idUsuario: 'activo' | 'ausente' | 'offline' }
    textoPresencia: { activo: 'En línea', ausente: 'Ausente (sin actividad 10 min)', offline: 'Desconectado' },

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    // Puntito de estado (verde/naranja/rojo) para un usuario. data-user permite actualizarlo en vivo.
    fnDot: function (id) {
        if (!id) return '';
        var e = Chat.presencias[id] || 'offline';
        return '<span class="chat-dot chat-dot-' + e + '" data-user="' + id + '" title="' + Chat.textoPresencia[e] + '"></span>';
    },

    // Consulta el estado en línea de todos y actualiza los puntitos ya pintados.
    fnPresencia: function () {
        var activo = (window.ChatAviso && ChatAviso.fnActivo) ? (ChatAviso.fnActivo() ? 1 : 0) : 1;
        return Chat.fnPost('presencia', { activo: activo }).done(function (r) {
            if (!r || !r.exito) return;
            Chat.presencias = r.presencias || {};
            $('.chat-dot').each(function () {
                var e = Chat.presencias[$(this).data('user')] || 'offline';
                this.className = 'chat-dot chat-dot-' + e;
                this.title = Chat.textoPresencia[e];
            });
        });
    },

    fnPost: function (accion, datos) {
        return $.ajax({ url: 'chat/' + accion, type: 'post', data: datos || {}, dataType: 'json' });
    },

    /** Texto con saltos de línea y ligas. */
    fnFormato: function (t) {
        return Chat.fnEsc(t)
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
            .replace(/\n/g, '<br>');
    },

    fnHora: function (f) {
        if (!f) return '';
        var hoy = new Date(), d = f.substring(0, 10);
        var hoyTxt = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
        return d === hoyTxt ? f.substring(11, 16) : f.substring(8, 10) + '/' + f.substring(5, 7) + ' ' + f.substring(11, 16);
    },

    // ---------------------------------------------------------------- lista
    fnCargarLista: function () {
        return Chat.fnPost('conversaciones').done(function (r) {
            if (!r.exito) return;
            Chat.yo = r.yo;
            Chat.lista = r.conversaciones;
            Chat.fnPintarLista();
        });
    },

    fnPintarLista: function () {
        var buscar = $.trim($('#chatBuscar').val()).toLowerCase(), html = '';
        // asistente IA fijo hasta arriba (assets/js/chatIA.js)
        if (window.ChatIA && !Chat.filtro && (!buscar || 'asistente ia nominas rh'.indexOf(buscar) >= 0)) {
            html += ChatIA.fnItemLista('chat-conv', 'chat-conv-texto', 'chat-conv-fila', 'chat-conv-ultimo', Chat.actual === 'ia');
        }
        $.each(Chat.lista, function (i, c) {
            if (Chat.filtro === 'SOPORTE' && c.tipo !== 'SOPORTE') return;
            if (Chat.filtro === 'NOLEIDOS' && !(c.noLeidos > 0)) return;
            if (buscar && ((c.titulo || '') + ' ' + (c.participantes || '')).toLowerCase().indexOf(buscar) < 0) return;
            var icono = { GENERAL: 'fa-users', DIRECTO: 'fa-user', GRUPO: 'fa-users', SOPORTE: 'fa-bug' }[c.tipo] || 'fa-comment';
            var ultimo = c.ultimoArchivo ? '📷 Imagen' : (c.ultimoTexto || '');
            if (c.ultimoDe && c.tipo !== 'DIRECTO') ultimo = c.ultimoDe.split(' ')[0] + ': ' + ultimo;
            var dot = (c.tipo === 'DIRECTO' && c.otroId) ? Chat.fnDot(c.otroId) : '';
            html += '<div class="chat-conv' + (c.id == Chat.actual ? ' activa' : '') + '" data-id="' + c.id + '">' +
                    '<div class="chat-conv-icono tipo-' + c.tipo + '"><i class="fa ' + icono + '"></i>' + dot + '</div>' +
                    '<div class="chat-conv-texto">' +
                    '  <div class="chat-conv-fila"><b>' + Chat.fnEsc(c.titulo || 'Conversación') + '</b><small>' + Chat.fnHora(c.ultimoMensaje) + '</small></div>' +
                    '  <div class="chat-conv-fila"><span class="chat-conv-ultimo">' + Chat.fnEsc(ultimo) + '</span>' +
                    (c.tipo === 'SOPORTE' ? '<span class="badge ' + (Chat.colorEstado[c.estado] || 'badge-secondary') + '">' + Chat.fnEsc(c.estado) + '</span>' : '') +
                    (c.noLeidos > 0 ? '<span class="chat-noleidos">' + c.noLeidos + '</span>' : '') + '</div>' +
                    '</div></div>';
        });
        $('#chatConversaciones').html(html || '<div class="chat-vacio">No hay conversaciones.</div>');
    },

    // ---------------------------------------------------------------- conversación
    fnAbrir: function (id) {
        if (id === 'ia') { Chat.fnAbrirIA(); return; }
        Chat.actual = Number(id);
        Chat.ultimoId = 0;
        Chat.conv = null;
        Chat.fnQuitarAdjunto();
        $('#chatApp').addClass('con-conversacion');
        $('#chatMensajes').html('<div class="chat-vacio"><i class="fa fa-spinner fa-spin"></i></div>');
        $('#chatEscribir').show();
        $('#chatApp').removeClass('modo-ia');
        $('#chatConversaciones .chat-conv').removeClass('activa').filter('[data-id="' + id + '"]').addClass('activa');
        try { history.replaceState(null, '', 'chat?c=' + id); } catch (e) {}
        Chat.fnTraerMensajes(true);
    },

    /** Conversación con el asistente IA (no usa la recarga cada 3 s). */
    fnAbrirIA: function () {
        Chat.actual = 'ia';
        Chat.ultimoId = 0;
        Chat.conv = null;
        Chat.fnQuitarAdjunto();
        $('#chatApp').addClass('con-conversacion modo-ia');
        $('#chatConversaciones .chat-conv').removeClass('activa').filter('[data-id="ia"]').addClass('activa');
        $('#chatTitulo').html('<b><i class="fa fa-magic"></i> Asistente IA</b>');
        $('#chatSubtitulo').text('Nómina y RH en México y uso del sistema · solo tú ves esta conversación');
        $('#chatEstado, #chatEstadoBadge').hide();
        $('#chatEscribir').hide();
        try { history.replaceState(null, '', 'chat?c=ia'); } catch (e) {}
        ChatIA.fnAbrir($('#chatMensajes'), function (ok) {
            $('#chatEscribir').toggle(ok);
            if (ok) $('#chatTexto').attr('placeholder', 'Pregúntale al asistente...').focus();
        });
    },

    fnTraerMensajes: function (inicial) {
        var id = Chat.actual;
        if (!id || id === 'ia') return $.Deferred().resolve();
        return Chat.fnPost('mensajes', { id: id, desde: Chat.ultimoId }).done(function (r) {
            if (id !== Chat.actual) return;
            if (!r.exito) { $('#chatMensajes').html('<div class="chat-vacio">' + Chat.fnEsc(r.mensaje) + '</div>'); $('#chatEscribir').hide(); return; }
            Chat.conv = r.conversacion;
            Chat.fnPintarCabecera(r.conversacion);
            if (inicial) $('#chatMensajes').html(r.mensajes.length ? '' : '<div class="chat-vacio chat-sin-mensajes">Todavía no hay mensajes. ¡Escribe el primero!</div>');
            if (!r.mensajes.length) return;
            $('#chatMensajes .chat-sin-mensajes').remove();
            var $m = $('#chatMensajes');
            var abajo = inicial || ($m[0].scrollHeight - $m.scrollTop() - $m.innerHeight() < 80);
            // el refresco automático y el de "enviar" pueden traer el mismo mensaje: se pinta una sola vez
            $.each(r.mensajes, function (i, m) {
                if (Number(m.id) <= Chat.ultimoId) return;
                $m.append(Chat.fnMensaje(m));
                Chat.ultimoId = Number(m.id);
            });
            if (abajo) $m.scrollTop($m[0].scrollHeight);
            // ya se leyeron: quitar el contador de la lista y del encabezado
            $('#chatConversaciones .chat-conv[data-id="' + id + '"] .chat-noleidos').remove();
            $.each(Chat.lista, function (i, c) { if (c.id == id) c.noLeidos = 0; });
            if (window.ChatAviso) ChatAviso.fnRevisar();
        });
    },

    fnPintarCabecera: function (c) {
        var item = null;
        $.each(Chat.lista, function (i, x) { if (x.id == c.id) item = x; });
        var titulo = item ? item.titulo : c.titulo;
        var sub = Chat.nombresTipo[c.tipo] || '';
        if (item && item.participantes && c.tipo !== 'GENERAL') sub = item.participantes;
        if (c.tipo === 'SOPORTE' && c.pagina) sub = 'Pantalla: ' + c.pagina + ' · ' + sub;
        $('#chatTitulo').html('<b>' + Chat.fnEsc(titulo || 'Conversación') + '</b>');
        $('#chatSubtitulo').text(sub);
        var soporte = c.tipo === 'SOPORTE';
        var admin = $('#chatApp').attr('data-admin') === '1';
        $('#chatEstado').toggle(soporte && admin);
        if (soporte && admin && !$('#chatEstado').is(':focus')) $('#chatEstado').val(c.estado);
        $('#chatEstadoBadge').toggle(soporte && !admin)
            .attr('class', 'badge ' + (Chat.colorEstado[c.estado] || 'badge-secondary')).text(c.estado || '');
    },

    fnMensaje: function (m) {
        if (!m.idUsuario) {
            return '<div class="chat-msg sistema"><span>' + Chat.fnEsc(m.texto) + ' · ' + Chat.fnHora(m.fecha) + '</span></div>';
        }
        var mio = Number(m.idUsuario) === Chat.yo;
        var html = '<div class="chat-msg ' + (mio ? 'mio' : 'otro') + '"><div class="chat-burbuja">';
        if (!mio && Chat.conv && Chat.conv.tipo !== 'DIRECTO') html += '<div class="chat-autor">' + Chat.fnEsc(m.nombre) + '</div>';
        if (m.idArchivo) {
            html += '<a href="chat/archivo/' + m.idArchivo + '" target="_blank" rel="noopener"><img class="chat-img" src="chat/archivo/' + m.idArchivo +
                    '" alt="' + Chat.fnEsc(m.archivoNombre) + '"></a>';
        }
        if (m.texto) html += '<div>' + Chat.fnFormato(m.texto) + '</div>';
        html += '<div class="chat-hora">' + Chat.fnHora(m.fecha) + '</div></div></div>';
        return html;
    },

    fnEnviar: function () {
        if (Chat.actual === 'ia') {
            var pregunta = $.trim($('#chatTexto').val());
            if (ChatIA.fnEnviar($('#chatMensajes'), pregunta, function () { $('#chatTexto').focus(); })) $('#chatTexto').val('').css('height', '');
            return;
        }
        if (Chat.enviando || !Chat.actual) return;
        var texto = $.trim($('#chatTexto').val());
        if (!texto && !Chat.adjunto) return;
        Chat.enviando = true;
        $('#chatEnviar').prop('disabled', true);
        var datos = { id: Chat.actual, texto: texto };
        if (Chat.adjunto) { datos.imagen = Chat.adjunto.datos; datos.imagenNombre = Chat.adjunto.nombre; }
        Chat.fnPost('enviar', datos).done(function (r) {
            if (!r.exito) { alert(r.mensaje); return; }
            $('#chatTexto').val('').css('height', '');
            Chat.fnQuitarAdjunto();
            Chat.fnTraerMensajes(false).always(function () {
                var $m = $('#chatMensajes'); $m.scrollTop($m[0].scrollHeight);
            });
            Chat.fnCargarLista();
        }).fail(function () {
            alert('No se pudo enviar el mensaje.');
        }).always(function () {
            Chat.enviando = false;
            $('#chatEnviar').prop('disabled', false);
            $('#chatTexto').focus();
        });
    },

    // ---------------------------------------------------------------- imágenes
    /** Reduce la imagen (máx. 1600 px, JPEG) para que no pese de más. */
    fnLeerImagen: function (archivo, listo) {
        if (!archivo || !/^image\//.test(archivo.type)) { alert('Solo se pueden adjuntar imágenes.'); return; }
        var lector = new FileReader();
        lector.onload = function () {
            var img = new Image();
            img.onload = function () {
                var max = 1600, w = img.width, h = img.height;
                if (w <= max && h <= max && archivo.size < 700 * 1024) { listo({ datos: lector.result, nombre: archivo.name || 'captura.png' }); return; }
                var k = Math.min(1, max / Math.max(w, h));
                var c = document.createElement('canvas');
                c.width = Math.round(w * k); c.height = Math.round(h * k);
                var ctx = c.getContext('2d');
                ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
                ctx.drawImage(img, 0, 0, c.width, c.height);
                listo({ datos: c.toDataURL('image/jpeg', 0.85), nombre: (archivo.name || 'captura').replace(/\.\w+$/, '') + '.jpg' });
            };
            img.src = lector.result;
        };
        lector.readAsDataURL(archivo);
    },

    fnMostrarAdjunto: function ($cont, a, quitar) {
        $cont.html('<img src="' + a.datos + '"> <span>' + Chat.fnEsc(a.nombre) + '</span> ' +
                   '<a href="javascript:void(0)" class="text-danger chat-quitar-adjunto" title="Quitar"><i class="fa fa-times"></i></a>').show();
        $cont.find('.chat-quitar-adjunto').on('click', quitar);
    },

    fnQuitarAdjunto: function () {
        Chat.adjunto = null;
        $('#chatAdjunto').hide().empty();
        $('#chatArchivo').val('');
    },

    fnPegarImagen: function (e, listo) {
        var items = (e.originalEvent.clipboardData || {}).items || [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image/') === 0) {
                e.preventDefault();
                Chat.fnLeerImagen(items[i].getAsFile(), listo);
                return;
            }
        }
    },

    // ---------------------------------------------------------------- nueva conversación
    fnAbrirNuevo: function () {
        $('#chatTipoDirecto').prop('checked', true);
        $('#chatGrupoTitulo').val('').hide();
        $('#chatBuscarUsuario').val('');
        var listo = Chat.usuarios.length ? $.Deferred().resolve() : Chat.fnPost('usuarios').done(function (r) { Chat.usuarios = r.usuarios || []; });
        listo.always(function () { Chat.fnPintarUsuarios(); $('#modalChatNuevo').modal('show'); Chat.fnPresencia(); });
    },

    fnPintarUsuarios: function () {
        var grupo = $('#chatTipoGrupo').is(':checked');
        var buscar = $.trim($('#chatBuscarUsuario').val()).toLowerCase();
        var marcados = {};
        $('#chatUsuarios input:checked').each(function () { marcados[this.value] = true; });
        var html = '';
        $.each(Chat.usuarios, function (i, u) {
            if (buscar && (u.nombre + ' ' + u.usuario).toLowerCase().indexOf(buscar) < 0) return;
            html += '<label class="custom-control ' + (grupo ? 'custom-checkbox' : 'custom-radio') + ' chat-usuario">' +
                    '<input type="' + (grupo ? 'checkbox' : 'radio') + '" class="custom-control-input" name="chatUsuarioSel" value="' + u.id + '"' +
                    (marcados[u.id] ? ' checked' : '') + ' id="chatU' + u.id + '">' +
                    '<span class="custom-control-label">' + Chat.fnDot(u.id) + Chat.fnEsc(u.nombre) + ' <small class="text-muted">' + Chat.fnEsc(u.usuario) +
                    (u.admin ? ' · administrador' : '') + '</small></span></label>';
        });
        $('#chatUsuarios').html(html || '<div class="text-muted">Sin resultados.</div>');
    },

    fnCrear: function () {
        var grupo = $('#chatTipoGrupo').is(':checked');
        var sel = $('#chatUsuarios input:checked').map(function () { return this.value; }).get();
        var datos = grupo ? { tipo: 'GRUPO', titulo: $.trim($('#chatGrupoTitulo').val()), usuarios: sel }
                          : { tipo: 'DIRECTO', usuario: sel[0] || '' };
        Chat.fnPost('nueva', datos).done(function (r) {
            if (!r.exito) { alert(r.mensaje); return; }
            $('#modalChatNuevo').modal('hide');
            Chat.fnCargarLista().done(function () { Chat.fnAbrir(r.id); });
        });
    },

    // ---------------------------------------------------------------- reportar falla
    fnAbrirFalla: function () {
        $('#fallaTitulo, #fallaTexto').val('');
        $('#fallaArchivo').val('');
        Chat.adjuntoFalla = null;
        $('#fallaAdjunto').hide().empty();
        // pantalla de donde venía el usuario (si llegó al chat desde otra pantalla del sistema)
        var pagina = '';
        try {
            var ref = document.referrer ? new URL(document.referrer) : null;
            if (ref && ref.host === location.host && !/\/chat/.test(ref.pathname)) pagina = ref.pathname.split('/').pop();
        } catch (e) {}
        $('#fallaPagina').val(pagina);
        $('#modalChatFalla').modal('show');
        setTimeout(function () { $('#fallaTitulo').focus(); }, 400);
    },

    fnEnviarFalla: function () {
        var datos = { tipo: 'SOPORTE', titulo: $.trim($('#fallaTitulo').val()), texto: $.trim($('#fallaTexto').val()), pagina: $.trim($('#fallaPagina').val()) };
        if (!datos.titulo || !datos.texto) { alert('Escribe el título y la descripción de la falla.'); return; }
        if (Chat.adjuntoFalla) { datos.imagen = Chat.adjuntoFalla.datos; datos.imagenNombre = Chat.adjuntoFalla.nombre; }
        var $b = $('#fallaEnviar').prop('disabled', true);
        Chat.fnPost('nueva', datos).done(function (r) {
            if (!r.exito) { alert(r.mensaje); return; }
            $('#modalChatFalla').modal('hide');
            if (typeof swal === 'function') swal('Enviado', 'Tu reporte llegó a los administradores. Aquí verás sus respuestas.', 'success');
            Chat.fnCargarLista().done(function () { Chat.fnAbrir(r.id); });
        }).always(function () { $b.prop('disabled', false); });
    },

    // ---------------------------------------------------------------- inicio
    fnInicializar: function () {
        Chat.fnCargarLista().done(function () {
            var m = /[?&]c=(\d+|ia)/.exec(location.search);
            if (m) Chat.fnAbrir(m[1]);
            else if ($(window).width() > 767 && Chat.lista.length) Chat.fnAbrir(Chat.lista[0].id);   // en computadora abre el canal general
        });
        setInterval(function () { if (!document.hidden) Chat.fnCargarLista(); }, 5000);
        setInterval(function () { if (!document.hidden) Chat.fnTraerMensajes(false); }, 3000);
        Chat.fnPresencia();
        setInterval(function () { if (!document.hidden) Chat.fnPresencia(); }, 20000);

        $('#chatConversaciones').on('click', '.chat-conv', function () { Chat.fnAbrir($(this).data('id')); });
        $('#chatRegresar').on('click', function () { $('#chatApp').removeClass('con-conversacion modo-ia'); Chat.actual = 0; });
        $('.chat-filtro').on('click', function () {
            $('.chat-filtro').removeClass('activo'); $(this).addClass('activo');
            Chat.filtro = $(this).data('filtro'); Chat.fnPintarLista();
        });
        $('#chatBuscar').on('input', Chat.fnPintarLista);
        $('#chatEnviar').on('click', Chat.fnEnviar);
        $('#chatTexto').on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); Chat.fnEnviar(); }
        }).on('input', function () {
            this.style.height = ''; this.style.height = Math.min(this.scrollHeight, 140) + 'px';
        }).on('paste', function (e) {
            Chat.fnPegarImagen(e, function (a) { Chat.adjunto = a; Chat.fnMostrarAdjunto($('#chatAdjunto'), a, Chat.fnQuitarAdjunto); });
        });
        // Arrastrar una captura a la conversación abierta
        var capas = 0, $zona = $('#chatApp');
        var trae = function (e) { var t = e.originalEvent.dataTransfer; return !!t && $.inArray('Files', t.types || []) >= 0 && Chat.actual && Chat.actual !== 'ia'; };
        $zona.on('dragenter', function (e) { if (!trae(e)) return; e.preventDefault(); capas++; $zona.addClass('chat-soltando'); })
             .on('dragover', function (e) { if (!trae(e)) return; e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'copy'; })
             .on('dragleave', function () { if (--capas <= 0) { capas = 0; $zona.removeClass('chat-soltando'); } })
             .on('drop', function (e) {
                 capas = 0; $zona.removeClass('chat-soltando');
                 var f = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
                 if (!f || !f.length || !Chat.actual || Chat.actual === 'ia') return;
                 e.preventDefault();
                 Chat.fnLeerImagen(f[0], function (a) { Chat.adjunto = a; Chat.fnMostrarAdjunto($('#chatAdjunto'), a, Chat.fnQuitarAdjunto); });
             });
        $('#chatArchivo').on('change', function () {
            if (this.files[0]) Chat.fnLeerImagen(this.files[0], function (a) { Chat.adjunto = a; Chat.fnMostrarAdjunto($('#chatAdjunto'), a, Chat.fnQuitarAdjunto); });
        });
        $('#chatEstado').on('change', function () {
            Chat.fnPost('estado', { id: Chat.actual, estado: this.value }).done(function (r) {
                if (!r.exito) { alert(r.mensaje); return; }
                Chat.fnTraerMensajes(false); Chat.fnCargarLista();
            });
        });

        $('#chatBtnNuevo').on('click', Chat.fnAbrirNuevo);
        $('input[name="chatTipoNuevo"]').on('change', function () {
            $('#chatGrupoTitulo').toggle(this.value === 'GRUPO');
            $('#chatUsuarios input').prop('checked', false);
            Chat.fnPintarUsuarios();
        });
        $('#chatBuscarUsuario').on('input', Chat.fnPintarUsuarios);
        $('#chatCrear').on('click', Chat.fnCrear);

        var quitarFalla = function () { Chat.adjuntoFalla = null; $('#fallaAdjunto').hide().empty(); $('#fallaArchivo').val(''); };
        $('#chatBtnFalla').on('click', Chat.fnAbrirFalla);
        $('#fallaEnviar').on('click', Chat.fnEnviarFalla);
        $('#fallaArchivo').on('change', function () {
            if (this.files[0]) Chat.fnLeerImagen(this.files[0], function (a) { Chat.adjuntoFalla = a; Chat.fnMostrarAdjunto($('#fallaAdjunto'), a, quitarFalla); });
        });
        $('#modalChatFalla').on('paste', function (e) {
            Chat.fnPegarImagen(e, function (a) { Chat.adjuntoFalla = a; Chat.fnMostrarAdjunto($('#fallaAdjunto'), a, quitarFalla); });
        });
    }
};

$(document).ready(Chat.fnInicializar);
