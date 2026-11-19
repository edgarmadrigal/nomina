/**
 * Asistente IA de nóminas y RH dentro del chat (pantalla del chat y burbuja).
 * Cada usuario tiene su propia conversación con el asistente (controllers/Chat.php ia*,
 * models/ChatIAModel.php, IA gratuita de Groq). No consulta datos del sistema.
 * El que lo usa le pasa su contenedor de mensajes y su caja de texto.
 */
var ChatIA = {
    ID: 'ia',
    esperando: false,
    configurada: true,
    esAdmin: false,

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    /** Renglón fijo arriba de la lista de conversaciones. */
    fnItemLista: function (claseItem, claseTexto, claseFila, claseUltimo, activa) {
        return '<div class="' + claseItem + (activa ? ' activa' : '') + ' chat-ia-item" data-id="ia">' +
               '<div class="chat-conv-icono tipo-IA"><i class="fa fa-magic"></i></div>' +
               '<div class="' + claseTexto + '"><div class="' + claseFila + '"><b>Asistente IA</b><small>nóminas y RH</small></div>' +
               '<div class="' + claseFila + '"><span class="' + claseUltimo + '">Pregúntame de nómina, RH o del sistema</span></div></div></div>';
    },

    /** Formato sencillo: negritas, listas y saltos de línea. */
    fnFormato: function (t) {
        return ChatIA.fnEsc(t)
            .replace(/\*\*([^*\n]+)\*\*/g, '<b>$1</b>')
            .replace(/^#{1,4}\s*(.+)$/gm, '<b>$1</b>')
            .replace(/^\s*[-*]\s+/gm, '• ')
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
            .replace(/\n/g, '<br>');
    },

    fnHora: function (f) {
        return f ? f.substring(11, 16) : '';
    },

    fnMensaje: function (m) {
        var mio = m.rol === 'user';
        return '<div class="chat-msg ' + (mio ? 'mio' : 'otro') + '"><div class="chat-burbuja' + (mio ? '' : ' chat-ia-burbuja') + '">' +
               (mio ? '' : '<div class="chat-autor"><i class="fa fa-magic"></i> Asistente IA</div>') +
               '<div>' + (mio ? ChatIA.fnEsc(m.texto).replace(/\n/g, '<br>') : ChatIA.fnFormato(m.texto)) + '</div>' +
               '<div class="chat-hora">' + ChatIA.fnHora(m.fecha) + '</div></div></div>';
    },

    fnPintar: function ($m, mensajes) {
        var html = '<div class="chat-ia-aviso"><i class="fa fa-info-circle"></i> Respuestas de una IA gratuita: revisa las cifras importantes. ' +
                   'No ve datos de empleados. <a href="javascript:void(0)" class="chat-ia-nueva">Nueva conversación</a></div>';
        if (!mensajes.length) {
            html += '<div class="chat-msg otro"><div class="chat-burbuja chat-ia-burbuja"><div class="chat-autor"><i class="fa fa-magic"></i> Asistente IA</div>' +
                    '<div>¡Hola! Soy el asistente de nóminas y RH. Pregúntame, por ejemplo:<br>• ¿Cuántos días de vacaciones le tocan a alguien con 7 años?<br>' +
                    '• ¿Cómo se pagan las horas extra?<br>• ¿Qué significa "tran" en las checadas?</div></div></div>';
        }
        $.each(mensajes, function (i, m) { html += ChatIA.fnMensaje(m); });
        $m.html(html);
        $m.scrollTop($m[0].scrollHeight);
    },

    /** Formulario para el administrador cuando todavía no hay clave. */
    fnPintarConfig: function ($m) {
        if (!ChatIA.esAdmin) {
            $m.html('<div class="chat-vacio cb-vacio">El asistente todavía no está configurado. Pídele al administrador que lo active.</div>');
            return;
        }
        $m.html('<div class="chat-ia-config">' +
            '<h6><i class="fa fa-magic"></i> Activar el asistente IA</h6>' +
            '<ol><li>Entra a <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com/keys</a> (puedes entrar con una cuenta de Google; es gratis y no pide tarjeta).</li>' +
            '<li>Da <b>Create API Key</b>, ponle de nombre <i>Nominas</i> y copia la clave (empieza con <code>gsk_</code>).</li>' +
            '<li>Pégala aquí y da Guardar (se prueba antes de guardarse).</li></ol>' +
            '<input type="password" class="form-control form-control-sm chat-ia-clave" placeholder="gsk_..." autocomplete="off">' +
            '<button type="button" class="btn btn-sm btn-success mt-2 chat-ia-guardar" style="color:#fff !important;"><i class="fa fa-save"></i> Guardar clave</button>' +
            '<div class="chat-ia-config-msg"></div></div>');
    },

    /**
     * Abre la conversación del asistente en el contenedor $m.
     * listo(habilitarEscritura) avisa al que llama si se puede escribir.
     */
    fnAbrir: function ($m, listo) {
        $m.html('<div class="chat-vacio cb-vacio"><i class="fa fa-spinner fa-spin"></i></div>');
        $.ajax({ url: 'chat/iaMensajes', type: 'post', dataType: 'json', global: false }).done(function (r) {
            if (!r || !r.exito) { $m.html('<div class="chat-vacio cb-vacio">' + ChatIA.fnEsc(r && r.mensaje) + '</div>'); return; }
            ChatIA.configurada = r.configurada;
            ChatIA.esAdmin = r.esAdmin;
            if (!r.configurada) { ChatIA.fnPintarConfig($m); if (listo) listo(false); return; }
            ChatIA.fnPintar($m, r.mensajes);
            if (listo) listo(true);
        });
        // los botones viven dentro de $m: se ligan una vez por contenedor
        if ($m.data('chatIA')) return;
        $m.data('chatIA', true);
        $m.on('click', '.chat-ia-nueva', function () {
            if (!confirm('¿Empezar una conversación nueva con el asistente? La anterior se borra.')) return;
            $.ajax({ url: 'chat/iaNueva', type: 'post', dataType: 'json', global: false }).done(function () { ChatIA.fnPintar($m, []); });
        });
        $m.on('click', '.chat-ia-guardar', function () {
            var $b = $(this).prop('disabled', true), $msg = $m.find('.chat-ia-config-msg').text('Probando la clave...');
            $.ajax({ url: 'chat/iaClave', type: 'post', dataType: 'json', global: false, data: { clave: $.trim($m.find('.chat-ia-clave').val()) } })
                .done(function (r) {
                    $msg.text(r.mensaje).toggleClass('text-danger', !r.exito).toggleClass('text-success', !!r.exito);
                    if (r.exito) setTimeout(function () { ChatIA.fnAbrir($m, listo); }, 800);
                })
                .fail(function () { $msg.text('No se pudo guardar la clave.').addClass('text-danger'); })
                .always(function () { $b.prop('disabled', false); });
        });
    },

    /** Manda la pregunta y pinta la respuesta. */
    fnEnviar: function ($m, texto, alTerminar) {
        if (ChatIA.esperando || !texto) return false;
        ChatIA.esperando = true;
        $m.find('.chat-ia-escribiendo').remove();
        $m.append(ChatIA.fnMensaje({ rol: 'user', texto: texto, fecha: '' }) +
                  '<div class="chat-msg otro chat-ia-escribiendo"><div class="chat-burbuja chat-ia-burbuja"><i class="fa fa-circle-o-notch fa-spin"></i> Pensando...</div></div>');
        $m.scrollTop($m[0].scrollHeight);
        $.ajax({ url: 'chat/iaEnviar', type: 'post', dataType: 'json', global: false, data: { texto: texto } }).done(function (r) {
            if (r.mensajes) ChatIA.fnPintar($m, r.mensajes);
            if (!r.exito) {
                $m.find('.chat-ia-escribiendo').remove();
                $m.append('<div class="chat-msg sistema"><span>' + ChatIA.fnEsc(r.mensaje) + '</span></div>');
                $m.scrollTop($m[0].scrollHeight);
            }
        }).fail(function () {
            $m.find('.chat-ia-escribiendo').remove();
            $m.append('<div class="chat-msg sistema"><span>No se pudo hablar con el asistente. Intenta de nuevo.</span></div>');
        }).always(function () {
            ChatIA.esperando = false;
            if (alTerminar) alTerminar();
        });
        return true;
    }
};
