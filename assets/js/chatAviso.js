/**
 * Aviso de mensajes del chat en todas las páginas: contador en el símbolo del
 * encabezado y una notificación cuando llega un mensaje nuevo (controllers/Chat.php).
 */
var ChatAviso = {
    tituloOriginal: document.title,
    timer: null,
    ultimaInteraccion: Date.now(),   // mouse/teclado: para el estado "ausente" a los 10 min

    // ¿La persona se ha movido en los últimos 10 min? (verde) o lleva rato quieta (naranja)
    fnActivo: function () { return (Date.now() - ChatAviso.ultimaInteraccion) < 10 * 60 * 1000; },

    fnRevisar: function () {
        if (!$('#chatIcono').length) return;
        $.ajax({ url: 'chat/pendientes', type: 'post', dataType: 'json', global: false,
                 data: { activo: ChatAviso.fnActivo() ? 1 : 0 } }).done(function (r) {
            if (!r || !r.exito) return;
            var $b = $('#chatIcono .chat-badge');
            if (r.total > 0) $b.text(r.total > 99 ? '99+' : r.total).show(); else $b.hide();
            if (window.ChatBurbujas) ChatBurbujas.fnContador();
            document.title = (r.total > 0 ? '(' + r.total + ') ' : '') + ChatAviso.tituloOriginal;
            if (r.ultimo) ChatAviso.fnNotificar(r.ultimo);
        });
    },

    /** Muestra una vez cada mensaje nuevo (recuerda el último avisado entre pantallas). */
    fnNotificar: function (m) {
        var visto = 0;
        try { visto = Number(localStorage.getItem('chatUltimoAvisado') || 0); } catch (e) {}
        if (Number(m.id) <= visto) return;
        try { localStorage.setItem('chatUltimoAvisado', m.id); } catch (e) {}
        // en la pantalla del chat con esa conversación abierta no hace falta avisar
        if (window.Chat && Chat.actual == m.idConversacion && !document.hidden) return;
        // tampoco si la tiene abierta en la burbuja
        if (window.ChatBurbujas && $('#chatBurbuja').length) {
            var b = ChatBurbujas.fnDe(m.idConversacion);
            if (b.actual == m.idConversacion && b.$v().is(':visible') && !document.hidden) return;
        }
        if (visto === 0) return;   // primera vez en este navegador: no avisar mensajes viejos
        var texto = m.idArchivo ? '📷 Imagen' : (m.texto || '');
        if (texto.length > 120) texto = texto.substring(0, 120) + '…';
        $('#chatToast').remove();
        var $t = $('<a id="chatToast" href="chat?c=' + m.idConversacion + '"><b></b><span></span></a>');
        $t.find('b').text(m.de);
        $t.find('span').text(texto);
        // con la burbuja activa, el aviso abre la conversación ahí mismo
        if (window.ChatBurbujas && $('#chatBurbuja').length) {
            $t.on('click', function (e) {
                e.preventDefault();
                $t.remove();
                var destino = ChatBurbujas.fnDe(m.idConversacion);
                destino.actual = Number(m.idConversacion);
                destino.fnAlternar(true);
            });
        }
        $('body').append($t);
        setTimeout(function () { $t.addClass('visible'); }, 30);
        setTimeout(function () { $t.removeClass('visible'); setTimeout(function () { $t.remove(); }, 400); }, 8000);
    },

    fnInicializar: function () {
        if (!$('#chatIcono').length) return;
        // Marca actividad real de la persona (para distinguir "conectado" de "ausente")
        var marcar = function () { ChatAviso.ultimaInteraccion = Date.now(); };
        $(document).on('mousemove keydown mousedown touchstart scroll', marcar);
        ChatAviso.fnRevisar();
        // cada 15 s con la pantalla a la vista; cada minuto si está en otra pestaña
        var cuenta = 0;
        ChatAviso.timer = setInterval(function () {
            cuenta++;
            if (!document.hidden || cuenta % 4 === 0) ChatAviso.fnRevisar();
        }, 15000);
    }
};

$(document).ready(ChatAviso.fnInicializar);
