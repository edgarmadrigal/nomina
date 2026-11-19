/**
 * Chat en burbujas (como los chatbots de las páginas), en todas las pantallas menos la del chat:
 *  - Azul (ChatBurbuja): canal de todos, directos, grupos y el asistente IA.
 *  - Naranja (ChatFallas): incidencias del sistema (conversaciones SOPORTE / "Reportar falla"),
 *    con botón para crear una nueva; los administradores cambian el estado.
 * Cada una abre su ventanita; solo una abierta a la vez. Usan los servicios de controllers/Chat.php,
 * piden la lista de conversaciones una sola vez para las dos y recuerdan entre pantallas
 * si estaban abiertas y en qué conversación.
 */
function CrearBurbujaChat(cfg) {
    var B = {
        cfg: cfg,
        yo: 0,
        lista: [],
        actual: 0,
        conv: null,
        ultimoId: 0,
        adjunto: null,
        enviando: false,
        timerMensajes: null,

        fnEsc: function (t) {
            return $('<div>').text(t == null ? '' : String(t)).html();
        },

        fnPost: function (accion, datos) {
            return $.ajax({ url: 'chat/' + accion, type: 'post', data: datos || {}, dataType: 'json', global: false });
        },

        fnHora: function (f) {
            if (!f) return '';
            var hoy = new Date(), d = f.substring(0, 10);
            var hoyTxt = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
            return d === hoyTxt ? f.substring(11, 16) : f.substring(8, 10) + '/' + f.substring(5, 7) + ' ' + f.substring(11, 16);
        },

        fnLeer: function (clave, porOmision) {
            try { var v = localStorage.getItem(cfg.id + clave); return v === null ? porOmision : v; } catch (e) { return porOmision; }
        },

        fnGuardar: function (clave, valor) {
            try { if (valor === null) localStorage.removeItem(cfg.id + clave); else localStorage.setItem(cfg.id + clave, valor); } catch (e) {}
        },

        $v: function () { return $('#' + cfg.id + 'Ventana'); },

        esAdmin: function () { return $('#radioIcono').attr('data-admin') === '1'; },

        // ---------------------------------------------------------------- burbuja y ventana
        fnCrear: function () {
            if ($('#' + cfg.id).length) return;
            $('body').addClass('chat-con-burbuja').append(
                '<button type="button" id="' + cfg.id + '" class="cb-burbuja ' + cfg.clase + '" title="' + cfg.titulo + '"><i class="fa ' + cfg.icono + '"></i><span class="chat-badge" style="display:none;"></span></button>' +
                '<div id="' + cfg.id + 'Ventana" class="cb-ventana ' + cfg.clase + '" style="display:none;">' +
                '  <div class="cb-cab">' +
                '    <button type="button" class="cb-regresar" title="Regresar" style="display:none;"><i class="fa fa-arrow-left"></i></button>' +
                '    <div class="cb-titulo"><b></b><small></small></div>' +
                '    <select class="cb-estado" title="Estado de la incidencia" style="display:none;">' +
                '      <option value="ABIERTO">Abierto</option><option value="EN PROCESO">En proceso</option><option value="RESUELTO">Resuelto</option></select>' +
                '    <a href="chat" class="cb-icono" title="Abrir el chat completo"><i class="fa fa-expand"></i></a>' +
                '    <button type="button" class="cb-icono cb-minimizar" title="Minimizar"><i class="fa fa-minus"></i></button>' +
                '  </div>' +
                '  <div class="cb-cuerpo">' +
                '    <div class="cb-lista"><div class="cb-vacio"><i class="fa fa-spinner fa-spin"></i></div></div>' +
                (cfg.fallas ?
                '    <div class="cb-nueva" style="display:none;">' +
                '      <label>¿Qué pasó?</label><input type="text" class="form-control form-control-sm cb-nueva-titulo" maxlength="150" placeholder="Ej. No me deja eliminar la incidencia">' +
                '      <label>Descripción</label><textarea class="form-control form-control-sm cb-nueva-texto" rows="4" placeholder="Qué estabas haciendo, qué esperabas y qué salió. Puedes pegar una captura (Ctrl+V) o arrastrarla aquí."></textarea>' +
                '      <label>Pantalla</label><input type="text" class="form-control form-control-sm cb-nueva-pagina" maxlength="200">' +
                '      <div class="cb-nueva-adjunto"></div>' +
                '      <div class="cb-nueva-botones">' +
                '        <label class="cb-clip" title="Adjuntar captura"><i class="fa fa-paperclip"></i> Captura<input type="file" accept="image/*" style="display:none;"></label>' +
                '        <button type="button" class="btn btn-sm cb-nueva-enviar" style="color:#fff !important;"><i class="fa fa-paper-plane"></i> Enviar incidencia</button>' +
                '      </div>' +
                '    </div>' : '') +
                (cfg.nuevo ?
                '    <div class="cb-nuevo" style="display:none;">' +
                '      <input type="text" class="form-control form-control-sm cb-nuevo-buscar" placeholder="Buscar persona...">' +
                '      <div class="cb-nuevo-lista"></div>' +
                '    </div>' : '') +
                '    <div class="cb-conv" style="display:none;">' +
                '      <div class="cb-mensajes"></div>' +
                '      <div class="cb-adjunto" style="display:none;"></div>' +
                '      <div class="cb-escribir">' +
                '        <label class="cb-clip" title="Adjuntar imagen (o pega una captura con Ctrl+V, o arrástrala a la ventana)"><i class="fa fa-paperclip"></i><input type="file" accept="image/*" style="display:none;"></label>' +
                '        <textarea rows="1" placeholder="Escribe un mensaje..."></textarea>' +
                '        <button type="button" class="cb-enviar" title="Enviar"><i class="fa fa-paper-plane"></i></button>' +
                '      </div>' +
                '    </div>' +
                '  </div>' +
                '</div>');

            var $v = B.$v();
            $('#' + cfg.id).on('click', function () { B.fnAlternar(); });
            $v.find('.cb-minimizar').on('click', function () { B.fnAlternar(false); });
            $v.find('.cb-regresar').on('click', B.fnVerLista);
            $v.on('click', '.cb-item', function () {
                if ($(this).hasClass('cb-persona')) return;   // los de "nuevo chat" tienen su propio manejador
                var id = $(this).attr('data-id');
                B.fnAbrirConv(id === 'ia' ? 'ia' : Number(id));
            });
            $v.on('click', '.cb-btn-nueva', B.fnVerNueva);
            $v.on('click', '.cb-btn-nuevo', B.fnVerNuevo);
            $v.on('input', '.cb-nuevo-buscar', B.fnPintarUsuarios);
            $v.on('click', '.cb-persona', function () {
                var uid = $(this).attr('data-user');
                B.fnPost('nueva', { tipo: 'DIRECTO', usuario: uid }).done(function (r) {
                    if (!r.exito) { alert(r.mensaje); return; }
                    ChatBurbujas.fnCargar().always(function () { B.fnAbrirConv(Number(r.id)); });
                }).fail(function () { alert('No se pudo iniciar el chat.'); });
            });
            $v.find('.cb-enviar').on('click', B.fnEnviar);
            $v.find('.cb-conv textarea').on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); B.fnEnviar(); }
            }).on('input', function () {
                this.style.height = ''; this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            }).on('paste', function (e) { B.fnPegar(e, B.fnPonerAdjunto); });
            $v.find('.cb-conv .cb-clip input').on('change', function () { if (this.files[0]) B.fnLeerImagen(this.files[0], B.fnPonerAdjunto); });
            $v.find('.cb-estado').on('change', function () {
                B.fnPost('estado', { id: B.actual, estado: this.value }).done(function (r) {
                    if (!r.exito) { alert(r.mensaje); return; }
                    B.fnTraerMensajes(false);
                    ChatBurbujas.fnCargar();
                });
            });
            if (cfg.fallas) {
                $v.find('.cb-nueva-enviar').on('click', B.fnEnviarNueva);
                $v.find('.cb-nueva').on('paste', function (e) { B.fnPegar(e, B.fnPonerAdjuntoNueva); });
                $v.find('.cb-nueva .cb-clip input').on('change', function () { if (this.files[0]) B.fnLeerImagen(this.files[0], B.fnPonerAdjuntoNueva); });
            }
            // Arrastrar una captura (archivo o imagen de otra ventana) a la ventana del chat
            var capas = 0;
            $v.on('dragenter', function (e) {
                if (!B.fnDestinoSoltar() || !B.fnTraeArchivo(e)) return;
                e.preventDefault(); capas++; $v.addClass('cb-soltando');
            }).on('dragover', function (e) {
                if (!B.fnDestinoSoltar() || !B.fnTraeArchivo(e)) return;
                e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'copy';
            }).on('dragleave', function () {
                if (--capas <= 0) { capas = 0; $v.removeClass('cb-soltando'); }
            }).on('drop', function (e) {
                capas = 0; $v.removeClass('cb-soltando');
                var destino = B.fnDestinoSoltar(), archivos = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
                if (!destino || !archivos || !archivos.length) return;
                e.preventDefault();
                B.fnLeerImagen(archivos[0], destino);
            });
        },

        /** A dónde va una imagen soltada: formulario de incidencia nueva o conversación abierta (no con el asistente). */
        fnDestinoSoltar: function () {
            var $v = B.$v();
            if ($v.find('.cb-nueva').is(':visible')) return B.fnPonerAdjuntoNueva;
            if ($v.find('.cb-conv').is(':visible') && $v.find('.cb-conv .cb-clip').is(':visible')) return B.fnPonerAdjunto;
            return null;
        },

        fnTraeArchivo: function (e) {
            var t = e.originalEvent.dataTransfer;
            return !!t && $.inArray('Files', t.types || []) >= 0;
        },

        fnAlternar: function (abrir) {
            var $v = B.$v();
            if (abrir === undefined) abrir = !$v.is(':visible');
            // solo una ventanita abierta a la vez
            if (abrir) $.each(ChatBurbujas.todas, function (i, otra) { if (otra !== B && otra.$v().is(':visible')) otra.fnAlternar(false); });
            $v.toggle(abrir);
            $('#' + cfg.id).toggleClass('abierta', abrir);
            B.fnGuardar('Abierta', abrir ? '1' : '0');
            clearInterval(B.timerMensajes);
            if (!abrir) return;
            if (B.actual) B.fnAbrirConv(B.actual);
            else B.fnVerLista();
        },

        // ---------------------------------------------------------------- lista
        fnVerLista: function () {
            B.actual = 0;
            B.fnGuardar('Conv', null);
            clearInterval(B.timerMensajes);
            var $v = B.$v();
            $v.find('.cb-conv, .cb-nueva, .cb-nuevo, .cb-regresar, .cb-estado').hide();
            $v.find('.cb-lista').show();
            $v.find('.cb-titulo').html('<b>' + cfg.titulo + '</b><small>' + cfg.subtitulo + '</small>');
            B.fnPintarLista();
            ChatBurbujas.fnCargar();
        },

        /** La llama ChatBurbujas.fnCargar con la lista ya filtrada para esta burbuja. */
        fnRecibir: function (yo, lista) {
            B.yo = yo;
            B.lista = lista;
            var pendientes = 0;
            $.each(lista, function (i, c) { pendientes += Number(c.noLeidos) || 0; });
            var $b = $('#' + cfg.id + ' .chat-badge');
            if (pendientes > 0) $b.text(pendientes > 99 ? '99+' : pendientes).show(); else $b.hide();
            if (!B.actual && B.$v().find('.cb-lista').is(':visible')) B.fnPintarLista();
        },

        fnPintarLista: function () {
            var html = '';
            if (cfg.nuevo) html += '<button type="button" class="btn btn-sm btn-block cb-btn-nuevo" style="color:#fff !important;"><i class="fa fa-plus"></i> Nuevo chat</button>';
            if (cfg.fallas) html += '<button type="button" class="btn btn-sm btn-block cb-btn-nueva" style="color:#fff !important;"><i class="fa fa-plus"></i> Nueva incidencia</button>';
            if (cfg.conIA && window.ChatIA) html += ChatIA.fnItemLista('cb-item', 'cb-item-texto', 'cb-fila', 'cb-ultimo', false);
            var colores = { 'ABIERTO': 'badge-danger', 'EN PROCESO': 'badge-warning', 'RESUELTO': 'badge-success' };
            $.each(B.lista, function (i, c) {
                var icono = { GENERAL: 'fa-users', DIRECTO: 'fa-user', GRUPO: 'fa-users', SOPORTE: 'fa-bug' }[c.tipo] || 'fa-comment';
                var ultimo = c.ultimoArchivo ? '📷 Imagen' : (c.ultimoTexto || '');
                if (c.ultimoDe && c.tipo !== 'DIRECTO') ultimo = c.ultimoDe.split(' ')[0] + ': ' + ultimo;
                var dot = (c.tipo === 'DIRECTO' && c.otroId) ? ChatBurbujas.fnDot(c.otroId) : '';
                html += '<div class="cb-item" data-id="' + c.id + '">' +
                        '<div class="chat-conv-icono tipo-' + c.tipo + '"><i class="fa ' + icono + '"></i>' + dot + '</div>' +
                        '<div class="cb-item-texto"><div class="cb-fila"><b>' + B.fnEsc(c.titulo || 'Conversación') + '</b><small>' + B.fnHora(c.ultimoMensaje) + '</small></div>' +
                        '<div class="cb-fila"><span class="cb-ultimo">' + B.fnEsc(ultimo) + '</span>' +
                        (c.tipo === 'SOPORTE' ? '<span class="badge ' + (colores[c.estado] || 'badge-secondary') + '">' + B.fnEsc(c.estado) + '</span>' : '') +
                        (c.noLeidos > 0 ? '<span class="chat-noleidos">' + c.noLeidos + '</span>' : '') + '</div></div></div>';
            });
            if (!B.lista.length && !cfg.conIA) html += '<div class="cb-vacio">' + cfg.vacio + '</div>';
            B.$v().find('.cb-lista').html(html);
        },

        // ---------------------------------------------------------------- nueva incidencia
        fnVerNueva: function () {
            var $v = B.$v();
            $v.find('.cb-lista, .cb-conv, .cb-estado').hide();
            $v.find('.cb-nueva, .cb-regresar').show();
            $v.find('.cb-titulo').html('<b>Nueva incidencia</b><small>Llega a los administradores</small>');
            $v.find('.cb-nueva-titulo, .cb-nueva-texto').val('');
            // pantalla donde está el usuario
            var pagina = location.pathname.replace(/\/+$/, '').split('/').pop() || 'home';
            $v.find('.cb-nueva-pagina').val(pagina === 'nomina' ? 'home' : pagina);
            B.adjuntoNueva = null;
            $v.find('.cb-nueva-adjunto').empty();
            setTimeout(function () { $v.find('.cb-nueva-titulo').focus(); }, 50);
        },

        fnEnviarNueva: function () {
            var $v = B.$v();
            var datos = { tipo: 'SOPORTE', titulo: $.trim($v.find('.cb-nueva-titulo').val()), texto: $.trim($v.find('.cb-nueva-texto').val()),
                          pagina: $.trim($v.find('.cb-nueva-pagina').val()) };
            if (!datos.titulo || !datos.texto) { alert('Escribe qué pasó y la descripción.'); return; }
            if (B.adjuntoNueva) { datos.imagen = B.adjuntoNueva.datos; datos.imagenNombre = B.adjuntoNueva.nombre; }
            var $b = $v.find('.cb-nueva-enviar').prop('disabled', true);
            B.fnPost('nueva', datos).done(function (r) {
                if (!r.exito) { alert(r.mensaje); return; }
                ChatBurbujas.fnCargar().always(function () { B.fnAbrirConv(Number(r.id)); });
            }).fail(function () {
                alert('No se pudo enviar la incidencia.');
            }).always(function () { $b.prop('disabled', false); });
        },

        // ---------------------------------------------------------------- nuevo chat (directo)
        fnVerNuevo: function () {
            var $v = B.$v();
            $v.find('.cb-lista, .cb-conv, .cb-nueva, .cb-estado').hide();
            $v.find('.cb-nuevo, .cb-regresar').show();
            $v.find('.cb-titulo').html('<b>Nuevo chat</b><small>Elige a una persona</small>');
            $v.find('.cb-nuevo-buscar').val('');
            B.fnCargarUsuarios().always(function () { B.fnPintarUsuarios(); ChatBurbujas.fnPresencia(); });
            setTimeout(function () { $v.find('.cb-nuevo-buscar').focus(); }, 50);
        },

        fnCargarUsuarios: function () {
            if (ChatBurbujas.usuarios && ChatBurbujas.usuarios.length) return $.Deferred().resolve();
            return B.fnPost('usuarios').done(function (r) { ChatBurbujas.usuarios = (r && r.usuarios) || []; });
        },

        fnPintarUsuarios: function () {
            var buscar = $.trim(B.$v().find('.cb-nuevo-buscar').val()).toLowerCase(), html = '';
            $.each(ChatBurbujas.usuarios || [], function (i, u) {
                if (buscar && (u.nombre + ' ' + u.usuario).toLowerCase().indexOf(buscar) < 0) return;
                html += '<div class="cb-item cb-persona" data-user="' + u.id + '">' +
                        '<div class="chat-conv-icono tipo-DIRECTO"><i class="fa fa-user"></i>' + ChatBurbujas.fnDot(u.id) + '</div>' +
                        '<div class="cb-item-texto"><div class="cb-fila"><b>' + B.fnEsc(u.nombre) + '</b></div>' +
                        '<div class="cb-fila"><span class="cb-ultimo">' + B.fnEsc(u.usuario) + (u.admin ? ' · administrador' : '') + '</span></div></div></div>';
            });
            B.$v().find('.cb-nuevo-lista').html(html || '<div class="cb-vacio">Sin resultados.</div>');
        },

        // ---------------------------------------------------------------- conversación
        fnAbrirConv: function (id) {
            if (id === 'ia') { B.fnAbrirIA(); return; }
            B.actual = Number(id);
            B.ultimoId = 0;
            B.conv = null;
            B.fnQuitarAdjunto();
            B.fnGuardar('Conv', String(id));
            var $v = B.$v();
            $v.find('.cb-lista, .cb-nueva, .cb-nuevo, .cb-estado').hide();
            $v.find('.cb-conv, .cb-regresar, .cb-escribir, .cb-conv .cb-clip').show();
            var item = $.grep(B.lista, function (c) { return c.id == id; })[0];
            $v.find('.cb-titulo').html('<b>' + B.fnEsc(item ? item.titulo : 'Conversación') + '</b><small></small>');
            $v.find('.cb-mensajes').html('<div class="cb-vacio"><i class="fa fa-spinner fa-spin"></i></div>');
            $v.find('.cb-conv textarea').attr('placeholder', 'Escribe un mensaje...');
            clearInterval(B.timerMensajes);
            B.fnTraerMensajes(true);
            B.timerMensajes = setInterval(function () { if (!document.hidden) B.fnTraerMensajes(false); }, 3000);
            setTimeout(function () { $v.find('.cb-conv textarea').focus(); }, 50);
        },

        /** Conversación con el asistente IA (assets/js/chatIA.js). */
        fnAbrirIA: function () {
            B.actual = 'ia';
            B.fnQuitarAdjunto();
            B.fnGuardar('Conv', 'ia');
            clearInterval(B.timerMensajes);
            var $v = B.$v();
            $v.find('.cb-lista, .cb-estado').hide();
            $v.find('.cb-conv, .cb-regresar').show();
            $v.find('.cb-titulo').html('<b><i class="fa fa-magic"></i> Asistente IA</b><small>Nómina, RH y uso del sistema</small>');
            $v.find('.cb-escribir, .cb-conv .cb-clip').hide();
            ChatIA.fnAbrir($v.find('.cb-mensajes'), function (ok) {
                $v.find('.cb-escribir').toggle(ok);
                if (ok) $v.find('.cb-conv textarea').attr('placeholder', 'Pregúntale al asistente...').focus();
            });
        },

        fnTraerMensajes: function (inicial) {
            var id = B.actual;
            if (!id || id === 'ia') return $.Deferred().resolve();
            return B.fnPost('mensajes', { id: id, desde: B.ultimoId }).done(function (r) {
                if (id !== B.actual) return;
                var $v = B.$v(), $m = $v.find('.cb-mensajes');
                if (!r || !r.exito) { $m.html('<div class="cb-vacio">' + B.fnEsc(r && r.mensaje) + '</div>'); return; }
                B.conv = r.conversacion;
                var c = r.conversacion, item = $.grep(B.lista, function (x) { return x.id == id; })[0];
                var sub = c.tipo === 'SOPORTE' ? 'Incidencia · ' + c.estado + (c.pagina ? ' · ' + c.pagina : '')
                        : (item && c.tipo !== 'GENERAL' ? item.participantes : 'Canal de todos');
                $v.find('.cb-titulo').html('<b>' + B.fnEsc(item ? item.titulo : c.titulo || 'Conversación') + '</b><small>' + B.fnEsc(sub) + '</small>');
                var $e = $v.find('.cb-estado').toggle(c.tipo === 'SOPORTE' && B.esAdmin());
                if (!$e.is(':focus')) $e.val(c.estado);
                if (inicial) $m.html(r.mensajes.length ? '' : '<div class="cb-vacio cb-sin">Todavía no hay mensajes.</div>');
                if (!r.mensajes.length) return;
                $m.find('.cb-sin').remove();
                var abajo = inicial || ($m[0].scrollHeight - $m.scrollTop() - $m.innerHeight() < 60);
                $.each(r.mensajes, function (i, msg) {
                    if (Number(msg.id) <= B.ultimoId) return;   // el refresco y "enviar" pueden traer el mismo
                    $m.append(B.fnMensaje(msg));
                    B.ultimoId = Number(msg.id);
                });
                if (abajo) $m.scrollTop($m[0].scrollHeight);
                if (window.ChatAviso) ChatAviso.fnRevisar();
            });
        },

        fnMensaje: function (m) {
            if (!m.idUsuario) {
                return '<div class="chat-msg sistema"><span>' + B.fnEsc(m.texto) + '</span></div>';
            }
            var mio = Number(m.idUsuario) === Number(B.yo);
            var html = '<div class="chat-msg ' + (mio ? 'mio' : 'otro') + '"><div class="chat-burbuja">';
            if (!mio && B.conv && B.conv.tipo !== 'DIRECTO') html += '<div class="chat-autor">' + B.fnEsc(m.nombre) + '</div>';
            if (m.idArchivo) {
                html += '<a href="chat/archivo/' + m.idArchivo + '" target="_blank" rel="noopener"><img class="chat-img" src="chat/archivo/' + m.idArchivo + '" alt=""></a>';
            }
            if (m.texto) {
                html += '<div>' + B.fnEsc(m.texto)
                    .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
                    .replace(/\n/g, '<br>') + '</div>';
            }
            return html + '<div class="chat-hora">' + B.fnHora(m.fecha) + '</div></div></div>';
        },

        fnEnviar: function () {
            var $t = B.$v().find('.cb-conv textarea');
            if (B.actual === 'ia') {
                if (ChatIA.fnEnviar(B.$v().find('.cb-mensajes'), $.trim($t.val()), function () { $t.focus(); })) $t.val('').css('height', '');
                return;
            }
            if (B.enviando || !B.actual) return;
            var texto = $.trim($t.val());
            if (!texto && !B.adjunto) return;
            B.enviando = true;
            var datos = { id: B.actual, texto: texto };
            if (B.adjunto) { datos.imagen = B.adjunto.datos; datos.imagenNombre = B.adjunto.nombre; }
            B.fnPost('enviar', datos).done(function (r) {
                if (!r.exito) { alert(r.mensaje); return; }
                $t.val('').css('height', '');
                B.fnQuitarAdjunto();
                B.fnTraerMensajes(false).always(function () {
                    var $m = B.$v().find('.cb-mensajes'); $m.scrollTop($m[0].scrollHeight);
                });
            }).fail(function () {
                alert('No se pudo enviar el mensaje.');
            }).always(function () {
                B.enviando = false;
                $t.focus();
            });
        },

        // ---------------------------------------------------------------- imágenes (máx. 1600 px, JPEG)
        fnPegar: function (e, listo) {
            var items = (e.originalEvent.clipboardData || {}).items || [];
            for (var i = 0; i < items.length; i++) {
                if (items[i].type && items[i].type.indexOf('image/') === 0) {
                    e.preventDefault();
                    B.fnLeerImagen(items[i].getAsFile(), listo);
                    return;
                }
            }
        },

        fnLeerImagen: function (archivo, listo) {
            if (!archivo || !/^image\//.test(archivo.type)) { alert('Solo se pueden adjuntar imágenes.'); return; }
            var lector = new FileReader();
            lector.onload = function () {
                var img = new Image();
                img.onload = function () {
                    var max = 1600, w = img.width, h = img.height;
                    if (w <= max && h <= max && archivo.size < 700 * 1024) { listo({ datos: lector.result, nombre: archivo.name || 'captura.png' }); return; }
                    var k = Math.min(1, max / Math.max(w, h)), c = document.createElement('canvas');
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

        fnMostrarAdjunto: function ($a, a, quitar) {
            $a.html('<img src="' + a.datos + '"> <span>' + B.fnEsc(a.nombre) + '</span> ' +
                    '<a href="javascript:void(0)" class="text-danger" title="Quitar"><i class="fa fa-times"></i></a>').show();
            $a.find('a').on('click', quitar);
        },

        fnPonerAdjunto: function (a) {
            B.adjunto = a;
            B.fnMostrarAdjunto(B.$v().find('.cb-adjunto'), a, B.fnQuitarAdjunto);
        },

        fnPonerAdjuntoNueva: function (a) {
            B.adjuntoNueva = a;
            B.fnMostrarAdjunto(B.$v().find('.cb-nueva-adjunto'), a, function () {
                B.adjuntoNueva = null;
                B.$v().find('.cb-nueva-adjunto').empty();
                B.$v().find('.cb-nueva .cb-clip input').val('');
            });
        },

        fnQuitarAdjunto: function () {
            B.adjunto = null;
            B.$v().find('.cb-adjunto').hide().empty();
            B.$v().find('.cb-conv .cb-clip input').val('');
        },

        fnIniciar: function () {
            B.fnCrear();
            var guardada = B.fnLeer('Conv', 0);
            B.actual = guardada === 'ia' ? 'ia' : (Number(guardada) || 0);
        }
    };
    return B;
}

var ChatBurbuja = CrearBurbujaChat({
    id: 'chatBurbuja', clase: 'cb-azul', icono: 'fa-comments', titulo: 'Chat', subtitulo: 'Conversaciones',
    vacio: 'No hay conversaciones.', conIA: true, nuevo: true,
    filtro: function (c) { return c.tipo !== 'SOPORTE'; }
});

var ChatFallas = CrearBurbujaChat({
    id: 'chatFallas', clase: 'cb-naranja', icono: 'fa-bug', titulo: 'Incidencias', subtitulo: 'Fallas y detalles del sistema',
    vacio: 'No hay incidencias. Usa "Nueva incidencia" para reportar una falla.', fallas: true,
    filtro: function (c) { return c.tipo === 'SOPORTE'; }
});

/** Lo que comparten las dos burbujas: una sola consulta de conversaciones. */
var ChatBurbujas = {
    todas: [ChatBurbuja, ChatFallas],
    cargando: null,
    presencias: {},       // { idUsuario: 'activo' | 'ausente' | 'offline' }
    usuarios: [],
    textoPresencia: { activo: 'En línea', ausente: 'Ausente (sin actividad 10 min)', offline: 'Desconectado' },

    // Puntito de estado para un usuario (verde/naranja/rojo)
    fnDot: function (id) {
        if (!id) return '';
        var e = ChatBurbujas.presencias[id] || 'offline';
        return '<span class="chat-dot chat-dot-' + e + '" data-user="' + id + '" title="' + ChatBurbujas.textoPresencia[e] + '"></span>';
    },

    // Consulta el estado en línea y actualiza en vivo los puntitos ya pintados
    fnPresencia: function () {
        var activo = (window.ChatAviso && ChatAviso.fnActivo) ? (ChatAviso.fnActivo() ? 1 : 0) : 1;
        return $.ajax({ url: 'chat/presencia', type: 'post', data: { activo: activo }, dataType: 'json', global: false }).done(function (r) {
            if (!r || !r.exito) return;
            ChatBurbujas.presencias = r.presencias || {};
            $('.cb-ventana .chat-dot').each(function () {
                var e = ChatBurbujas.presencias[$(this).data('user')] || 'offline';
                this.className = 'chat-dot chat-dot-' + e;
                this.title = ChatBurbujas.textoPresencia[e];
            });
        });
    },

    fnCargar: function () {
        if (ChatBurbujas.cargando) return ChatBurbujas.cargando;
        ChatBurbujas.cargando = $.ajax({ url: 'chat/conversaciones', type: 'post', dataType: 'json', global: false }).done(function (r) {
            if (!r || !r.exito) return;
            $.each(ChatBurbujas.todas, function (i, b) {
                b.fnRecibir(r.yo, $.grep(r.conversaciones || [], b.cfg.filtro));
            });
        }).always(function () { ChatBurbujas.cargando = null; });
        return ChatBurbujas.cargando;
    },

    /** Burbuja que tiene esa conversación (para el aviso de mensaje nuevo). */
    fnDe: function (idConversacion) {
        return $.grep(ChatFallas.lista, function (c) { return c.id == idConversacion; }).length ? ChatFallas : ChatBurbuja;
    },

    /** Lo llama ChatAviso.fnRevisar (cada 15 s): refresca contadores, listas y estado en línea. */
    fnContador: function () {
        if ($('#chatBurbuja').length) { ChatBurbujas.fnCargar(); ChatBurbujas.fnPresencia(); }
    },

    fnInicializar: function () {
        // en la pantalla del chat no hacen falta las burbujas: "Abrir en burbuja" regresa con la conversación abierta
        if (window.Chat) {
            $('#chatBtnBurbuja').on('click', function () {
                var item = window.Chat && Chat.actual && Chat.actual !== 'ia' ? $.grep(Chat.lista, function (c) { return c.id == Chat.actual; })[0] : null;
                var b = item && item.tipo === 'SOPORTE' ? ChatFallas : ChatBurbuja;
                b.fnGuardar('Abierta', '1');
                b.fnGuardar('Conv', Chat.actual ? String(Chat.actual) : null);
                if (document.referrer && document.referrer.indexOf(location.host) >= 0 && !/\/chat(\?|$)/.test(document.referrer)) history.back();
                else location.href = 'home';
            });
            return;
        }
        if (!$('#chatIcono').length) return;   // sin sesión
        // la naranja arriba de la azul
        ChatFallas.fnIniciar();
        ChatBurbuja.fnIniciar();
        ChatBurbujas.fnCargar().always(function () {
            $.each(ChatBurbujas.todas, function (i, b) {
                if (b.fnLeer('Abierta', '0') === '1') b.fnAlternar(true);
            });
            ChatBurbujas.fnPresencia();
        });
    }
};

$(document).ready(ChatBurbujas.fnInicializar);
