/**
 * Radio de la página: símbolo de música junto al nombre del usuario.
 * Busca estaciones de radio por internet (radio-browser.info, gratis, solo audio)
 * por género, grupo o nombre y las reproduce sin mostrar nada más que el símbolo.
 * Se desbloquea con la contraseña de la música (controllers/Musica.php).
 * Como cada pantalla recarga la página, el estado se guarda en localStorage y la
 * estación vuelve a sonar sola al cambiar de pantalla.
 * Pestaña YouTube (MiniYT, abajo): busca canciones (primero las "Artista - Topic", que
 * solo muestran la portada) y las toca en un mini reproductor VISIBLE de 200x200 en una
 * esquina, como piden las reglas de YouTube; al cambiar de pantalla sigue en el mismo segundo.
 */
var Radio = {
    servidores: ['https://de1.api.radio-browser.info', 'https://de2.api.radio-browser.info', 'https://fi1.api.radio-browser.info'],
    generos: ['Banda', 'Norteño', 'Cumbia', 'Regional mexicano', 'Rancheras', 'Baladas', 'Pop', 'Rock', 'Rock en español',
              'Oldies', '80s', 'Salsa', 'Reggaeton', 'Electrónica', 'Jazz', 'Clásica'],
    audio: null,
    estado: { query: '', estaciones: [], idx: -1, sonando: false, volumen: 0.6 },
    fallos: 0,

    fnEsc: function (t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    },

    fnLeer: function () {
        try {
            var e = JSON.parse(localStorage.getItem('radioEstado') || 'null');
            if (e && e.estaciones) Radio.estado = $.extend(Radio.estado, e);
        } catch (err) {}
    },

    fnGuardar: function () {
        try { localStorage.setItem('radioEstado', JSON.stringify(Radio.estado)); } catch (err) {}
    },

    desbloqueada: function () {
        return $('#radioIcono').attr('data-desbloqueada') === '1';
    },

    // ---------------------------------------------------------------- panel
    fnPanel: function () {
        if ($('#radioPanel').length) return;
        var chips = '';
        $.each(Radio.generos, function (i, g) {
            chips += '<button type="button" class="radio-chip" data-q="' + Radio.fnEsc(g) + '">' + Radio.fnEsc(g) + '</button>';
        });
        $('body').append(
            '<div id="radioPanel" style="display:none;">' +
            '  <div class="radio-cab"><i class="fa fa-music"></i> Radio <a href="javascript:void(0)" class="radio-cerrar" title="Cerrar">&times;</a></div>' +
            '  <div id="radioBloqueo">' +
            '    <p class="radio-ayuda">Escribe la contraseña de la música.</p>' +
            '    <input type="password" id="radioClave" class="form-control form-control-sm" autocomplete="off" placeholder="Contraseña">' +
            '    <button type="button" id="radioEntrar" class="btn btn-sm btn-primary btn-block mt-2" style="color:#fff !important;">Entrar</button>' +
            '    <div id="radioClaveMsg" class="radio-msg"></div>' +
            '  </div>' +
            '  <div id="radioReproductor" style="display:none;">' +
            '    <div class="radio-tabs">' +
            '      <button type="button" data-tab="radio"><i class="fa fa-podcast"></i> Radio</button>' +
            '      <button type="button" data-tab="youtube"><i class="fa fa-youtube-play"></i> YouTube</button>' +
            '    </div>' +
            '    <div id="radioTabYoutube" style="display:none;">' +
            '      <div id="ytAvisoIp" class="yt-aviso" style="display:none;"></div>' +
            '      <div class="yt-gasto"><i class="fa fa-wifi"></i> <b>YouTube gasta mucho internet:</b> unos <b>11 MB por minuto</b> (~680 MB por hora) en el cuadrito. ' +
            'La <b>radio</b> gasta cerca de <b>1 MB por minuto</b> (~55 MB por hora): <b>unas 12 veces menos</b>. Para escuchar todo el día, mejor la radio.' +
            '<br><b>Ayúdanos:</b> en el cuadrito da clic en <i class="fa fa-cog"></i>, luego en el ⚙ de YouTube › Calidad › <b>144p</b> (gasta hasta 5 veces menos).</div>' +
            '      <div class="radio-buscar">' +
            '        <input type="text" id="ytQuery" class="form-control form-control-sm" placeholder="Canción, grupo o artista">' +
            '        <button type="button" id="ytBuscar" class="btn btn-sm btn-danger" style="color:#fff !important;"><i class="fa fa-search"></i></button>' +
            '      </div>' +
            '      <div id="ytAhora" class="radio-ahora"></div>' +
            '      <div id="ytLista" class="radio-lista"></div>' +
            '      <div class="radio-msg" style="font-size:11px;">Suena en el cuadrito de la esquina; lo puedes mover arrastrando la barra. Primero salen las versiones de solo audio (con la portada del disco).</div>' +
            '    </div>' +
            '    <div id="radioTabRadio">' +
            '    <div class="radio-buscar">' +
            '      <input type="text" id="radioQuery" class="form-control form-control-sm" placeholder="Género, grupo o artista">' +
            '      <button type="button" id="radioBuscar" class="btn btn-sm btn-primary" style="color:#fff !important;"><i class="fa fa-search"></i></button>' +
            '    </div>' +
            '    <div class="radio-chips">' + chips + '</div>' +
            '    <div id="radioAhora" class="radio-ahora"></div>' +
            '    <div class="radio-controles">' +
            '      <button type="button" id="radioAnterior" title="Estación anterior"><i class="fa fa-step-backward"></i></button>' +
            '      <button type="button" id="radioPlay" title="Pausa / Reproducir"><i class="fa fa-play"></i></button>' +
            '      <button type="button" id="radioSiguiente" title="Siguiente estación"><i class="fa fa-step-forward"></i></button>' +
            '      <i class="fa fa-volume-down"></i><input type="range" id="radioVolumen" min="0" max="1" step="0.05">' +
            '    </div>' +
            '    <div id="radioLista" class="radio-lista"></div>' +
            '    </div>' +
            '    <div id="musicaConsumo" class="radio-consumo"></div>' +
            '    <div class="radio-pie">' +
            '      <a href="javascript:void(0)" id="radioBloquear"><i class="fa fa-lock"></i> Bloquear</a>' +
            '      <a href="musica" id="radioCambiarClave" style="display:none;"><i class="fa fa-key"></i> Contraseña de la música</a>' +
            '    </div>' +
            '  </div>' +
            '</div>');

        $('#radioPanel .radio-cerrar').on('click', function () { $('#radioPanel').hide(); });
        $('#radioEntrar').on('click', Radio.fnDesbloquear);
        $('#radioClave').on('keydown', function (e) { if (e.key === 'Enter') Radio.fnDesbloquear(); });
        $('#radioBuscar').on('click', function () { Radio.fnBuscar($('#radioQuery').val()); });
        $('#radioQuery').on('keydown', function (e) { if (e.key === 'Enter') Radio.fnBuscar($(this).val()); });
        $('#radioPanel').on('click', '.radio-chip', function () {
            $('#radioQuery').val($(this).data('q'));
            Radio.fnBuscar($(this).data('q'));
        });
        $('#radioPanel').on('click', '.radio-estacion', function () { Radio.fnTocar(Number($(this).data('i'))); });
        $('#radioPlay').on('click', Radio.fnPausa);
        $('#radioSiguiente').on('click', function () { Radio.fnTocar(Radio.estado.idx + 1); });
        $('#radioAnterior').on('click', function () { Radio.fnTocar(Radio.estado.idx - 1); });
        $('#radioVolumen').val(Radio.estado.volumen).on('input', function () {
            Radio.estado.volumen = Number(this.value);
            if (Radio.audio) Radio.audio.volume = Radio.estado.volumen;
            Radio.fnGuardar();
        });
        $('#radioBloquear').on('click', Radio.fnBloquear);
        $('#radioPanel .radio-tabs button').on('click', function () { Radio.fnTab($(this).attr('data-tab')); });
        $('#ytBuscar').on('click', function () { MiniYT.fnBuscar($('#ytQuery').val()); });
        $('#ytQuery').on('keydown', function (e) { if (e.key === 'Enter') MiniYT.fnBuscar($(this).val()); });
        $('#radioPanel').on('click', '.yt-cancion', function () { MiniYT.fnTocar(Number($(this).attr('data-i')), 0); });
        // clic fuera del panel lo cierra (la música sigue)
        $(document).on('mousedown touchstart', function (e) {
            if (!$(e.target).closest('#radioPanel, #radioIcono').length) $('#radioPanel').hide();
        });
    },

    fnTab: function (tab) {
        Radio.estado.tab = tab === 'youtube' ? 'youtube' : 'radio';
        Radio.fnGuardar();
        $('#radioPanel .radio-tabs button').removeClass('activo').filter('[data-tab="' + Radio.estado.tab + '"]').addClass('activo');
        $('#radioTabRadio').toggle(Radio.estado.tab === 'radio');
        $('#radioTabYoutube').toggle(Radio.estado.tab === 'youtube');
        if (Radio.estado.tab === 'youtube') {
            MiniYT.fnAvisoIp();
            $('#ytQuery').val(MiniYT.estado.query || '');
            MiniYT.fnPintarLista();
        }
    },

    fnAbrir: function () {
        Radio.fnPanel();
        var $p = $('#radioPanel');
        if ($p.is(':visible')) { $p.hide(); return; }
        if (Radio.desbloqueada()) {
            $('#radioBloqueo').hide();
            $('#radioReproductor').show();
            $('#radioCambiarClave').toggle($('#radioIcono').attr('data-admin') === '1');
            $('#radioQuery').val(Radio.estado.query || '');
            Radio.fnPintarLista();
            Radio.fnPintarAhora();
            Radio.fnTab(Radio.estado.tab);
        } else {
            $('#radioReproductor').hide();
            $('#radioBloqueo').show();
            $('#radioClaveMsg').text('');
            // la contraseña se define en Catálogos > Música (solo administrador)
            if ($('#radioIcono').attr('data-hayclave') !== '1') {
                $('#radioBloqueo .radio-ayuda').html($('#radioIcono').attr('data-admin') === '1'
                    ? 'Todavía no hay contraseña de la música. Defínela en <a href="musica">Catálogos › Música</a>.'
                    : 'El administrador todavía no define la contraseña de la música.');
            }
        }
        $p.show();
        Consumo.ahora = Consumo.fnTasa();   // el medidor no se redibuja con el panel cerrado
        Consumo.fnPintar();
        if (!Radio.desbloqueada()) $('#radioClave').val('').focus();
    },

    // ---------------------------------------------------------------- contraseña
    fnDesbloquear: function () {
        var clave = $('#radioClave').val();
        if (!clave) return;
        $('#radioEntrar').prop('disabled', true);
        $.ajax({
            url: 'musica/desbloquear',
            type: 'post',
            data: { clave: clave },
            dataType: 'json'
        }).done(function (r) {
            if (!r.exito) { $('#radioClaveMsg').text(r.mensaje); return; }
            $('#radioIcono').attr('data-desbloqueada', '1').attr('data-hayclave', '1');
            $('#radioPanel').hide();
            Radio.fnAbrir();
        }).fail(function () {
            $('#radioClaveMsg').text('No se pudo validar la contraseña.');
        }).always(function () {
            $('#radioEntrar').prop('disabled', false);
        });
    },

    fnBloquear: function () {
        Radio.fnDetener();
        MiniYT.fnCerrar();
        $.post('musica/bloquear');
        $('#radioIcono').attr('data-desbloqueada', '0');
        $('#radioPanel').hide();
    },

    // ---------------------------------------------------------------- estaciones
    fnApi: function (ruta, i) {
        i = i || 0;
        // .then(fn) para quedarse solo con los datos (sin fn, jQuery pasa [datos, estado, xhr])
        return $.ajax({ url: Radio.servidores[i] + ruta, dataType: 'json', timeout: 8000 }).then(function (d) { return d; }, function () {
            return i + 1 < Radio.servidores.length ? Radio.fnApi(ruta, i + 1) : $.Deferred().reject();
        });
    },

    fnBuscar: function (q) {
        q = $.trim(q || '');
        if (!q) return;
        Radio.estado.query = q;
        $('#radioLista').html('<div class="radio-msg"><i class="fa fa-spinner fa-spin"></i> Buscando estaciones...</div>');
        var base = '&hidebroken=true&order=clickcount&reverse=true&limit=40';
        var tag = q.toLowerCase();
        $.when(
            Radio.fnApi('/json/stations/search?tag=' + encodeURIComponent(tag) + base).then(function (d) { return d; }, function () { return $.Deferred().resolve([]); }),
            Radio.fnApi('/json/stations/search?name=' + encodeURIComponent(q) + base).then(function (d) { return d; }, function () { return $.Deferred().resolve([]); })
        ).done(function (porGenero, porNombre) {
            var vistas = {}, lista = [];
            var https = location.protocol === 'https:';
            $.each([].concat(porNombre || [], porGenero || []), function (i, s) {
                var url = s.url_resolved || s.url;
                if (!url || vistas[s.stationuuid] || s.hls == 1) return;
                if (https && url.indexOf('https://') !== 0) return;   // la página en https no puede tocar http
                vistas[s.stationuuid] = true;
                lista.push({ uuid: s.stationuuid, nombre: $.trim(s.name), url: url, pais: s.countrycode || '',
                             tags: (s.tags || '').split(',').slice(0, 3).join(', '), votos: s.clickcount || 0, kbps: Number(s.bitrate) || 0 });
            });
            lista.sort(function (a, b) { return b.votos - a.votos; });
            Radio.estado.estaciones = lista.slice(0, 40);
            Radio.estado.idx = -1;
            Radio.fnGuardar();
            Radio.fnPintarLista();
            if (lista.length) Radio.fnTocar(0);
            else $('#radioLista').html('<div class="radio-msg">No se encontraron estaciones de "' + Radio.fnEsc(q) + '". Prueba con un género (ej. banda, rock, baladas).</div>');
        });
    },

    fnPintarLista: function () {
        var html = '';
        $.each(Radio.estado.estaciones, function (i, s) {
            html += '<div class="radio-estacion' + (i === Radio.estado.idx ? ' activa' : '') + '" data-i="' + i + '">' +
                    '<b>' + Radio.fnEsc(s.nombre) + '</b> <small>' + Radio.fnEsc(s.pais) + '</small>' +
                    (s.tags ? '<br><small>' + Radio.fnEsc(s.tags) + '</small>' : '') + '</div>';
        });
        $('#radioLista').html(html || '<div class="radio-msg">Elige un género o escribe un grupo o artista.</div>');
    },

    fnPintarAhora: function () {
        var s = Radio.estado.estaciones[Radio.estado.idx];
        $('#radioAhora').html(s ? '<i class="fa fa-' + (Radio.estado.sonando ? 'volume-up' : 'pause') + '"></i> ' + Radio.fnEsc(s.nombre) : '');
        $('#radioPlay i').attr('class', 'fa fa-' + (Radio.estado.sonando ? 'pause' : 'play'));
        $('#radioIcono').toggleClass('radio-sonando', Radio.estado.sonando || MiniYT.estado.sonando);
        $('#radioLista .radio-estacion').removeClass('activa').filter('[data-i="' + Radio.estado.idx + '"]').addClass('activa');
    },

    fnTocar: function (i) {
        var lista = Radio.estado.estaciones;
        if (!lista.length) return;
        i = (i + lista.length) % lista.length;
        Radio.estado.idx = i;
        Radio.estado.sonando = true;
        Radio.fnGuardar();
        MiniYT.fnCerrar();   // no suenan los dos a la vez
        if (!Radio.audio) {
            Radio.audio = new Audio();
            // si la estación no responde, pasa sola a la siguiente
            Radio.audio.addEventListener('error', function () {
                if (!Radio.estado.sonando) return;
                Radio.fallos++;
                if (Radio.fallos < Math.min(8, Radio.estado.estaciones.length)) Radio.fnTocar(Radio.estado.idx + 1);
                else { Radio.estado.sonando = false; Radio.fnPintarAhora(); $('#radioAhora').html('<span class="text-danger">Las estaciones no responden.</span>'); }
            });
            Radio.audio.addEventListener('playing', function () { Radio.fallos = 0; });
        }
        Radio.audio.volume = Radio.estado.volumen;
        Radio.audio.src = lista[i].url;
        var p = Radio.audio.play();
        if (p && p.catch) p.catch(function (err) {
            // el navegador no deja sonar hasta que el usuario toque la página
            if (err && err.name === 'NotAllowedError') Radio.fnEsperarClic();
        });
        Radio.fnPintarAhora();
    },

    fnPausa: function () {
        if (!Radio.audio || Radio.estado.idx < 0) { if (Radio.estado.estaciones.length) Radio.fnTocar(Math.max(0, Radio.estado.idx)); return; }
        if (Radio.estado.sonando) {
            Radio.audio.pause();
            Radio.audio.removeAttribute('src');   // en radio no tiene caso pausar: se corta el stream
            Radio.audio.load();
            Radio.estado.sonando = false;
            Radio.fnGuardar();
            Radio.fnPintarAhora();
        } else {
            Radio.fnTocar(Radio.estado.idx);
        }
    },

    fnDetener: function () {
        if (Radio.audio) { Radio.audio.pause(); Radio.audio.removeAttribute('src'); Radio.audio.load(); }
        Radio.estado.sonando = false;
        Radio.fnGuardar();
        Radio.fnPintarAhora();
    },

    fnEsperarClic: function () {
        $('#radioIcono').addClass('radio-espera').attr('title', 'Toca cualquier parte de la página para seguir escuchando');
        $(document).one('click keydown touchstart', function () {
            $('#radioIcono').removeClass('radio-espera').attr('title', 'Radio');
            if (Radio.estado.sonando && Radio.audio) Radio.audio.play().catch(function () {});
        });
    },

    fnInicializar: function () {
        if (!$('#radioIcono').length) return;
        if (Marco.enMarco()) { Marco.fnHijo(); return; }   // la música vive en la página de afuera
        Radio.fnLeer();
        $('#radioIcono').on('click', function (e) { e.preventDefault(); Radio.fnAbrir(); });
        // seguía sonando en la pantalla anterior: continúa
        if (Radio.desbloqueada() && Radio.estado.sonando && Radio.estado.idx >= 0 && Radio.estado.estaciones.length) {
            Radio.fnTocar(Radio.estado.idx);
        } else if (!Radio.desbloqueada() && Radio.estado.sonando) {
            Radio.estado.sonando = false;
            Radio.fnGuardar();
        }
        MiniYT.fnInicializar();
        Consumo.fnInicializar();
        Marco.fnInicializar();
    }
};

/**
 * Para que la música no se corte al cambiar de pantalla: mientras suena, los clics a otra
 * pantalla del sistema no recargan esta página; la pantalla se abre en un marco a pantalla
 * completa (#marcoPagina) y el audio sigue aquí afuera. Dentro del marco, el símbolo de
 * música manda a este reproductor. Sin música, todo navega como siempre.
 */
var Marco = {
    frame: null,

    /** ¿Esta página es la pantalla que va dentro del marco? */
    enMarco: function () {
        try { return window.top !== window && !!window.top.Marco && window.top.Marco.frame === window.frameElement; }
        catch (e) { return false; }
    },

    sonando: function () {
        return !!(Radio.estado.sonando || MiniYT.estado.sonando);
    },

    fnInicializar: function () {
        $(document).on('click', 'a[href]', function (e) {
            if (!Marco.sonando() || e.isDefaultPrevented() || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
            var href = this.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) return;
            if ((this.target && this.target !== '_self') || this.hasAttribute('download') || this.origin !== location.origin) return;
            e.preventDefault();
            Marco.fnAbrir(this.href);
        });
    },

    fnAbrir: function (url) {
        if (!Marco.frame) {
            Marco.frame = $('<iframe id="marcoPagina" title="Pantalla"></iframe>').appendTo('body')[0];
            $(Marco.frame).on('load', Marco.fnCargo);
            $('#radioPanel').hide();
            $('body').addClass('marco-activo');
        }
        Marco.frame.src = url;
    },

    /** Cada pantalla que carga el marco: la dirección y el título de arriba la siguen (F5 vuelve a ella). */
    fnCargo: function () {
        var w = Marco.frame.contentWindow, d;
        try { d = w.document; } catch (e) { location.href = Marco.frame.src; return; }
        // el login (sesión terminada o Cerrar sesión) u otra página sin encabezado: sale del marco
        if (!d.getElementById('radioIcono')) { location.href = w.location.href; return; }
        try { history.replaceState(null, '', w.location.href); } catch (e) {}
        document.title = d.title || document.title;
    },

    /** Dentro del marco: el símbolo abre el reproductor de afuera y muestra si suena. */
    fnHijo: function () {
        var arriba = window.top;
        $('#radioIcono').on('click', function (e) { e.preventDefault(); arriba.Radio.fnAbrir(); });
        $(document).on('mousedown touchstart', function (e) {
            if (!$(e.target).closest('#radioIcono').length) arriba.$('#radioPanel').hide();
        });
        // si el navegador pide un clic para dejar sonar, el clic aquí también cuenta
        $(document).on('click keydown touchstart', function () {
            if (arriba.$('#radioIcono').hasClass('radio-espera')) arriba.$(arriba.document).trigger('click');
        });
        var pintar = function () {
            try {
                $('#radioIcono').toggleClass('radio-sonando', arriba.Marco.sonando())
                                .toggleClass('radio-espera', arriba.$('#radioIcono').hasClass('radio-espera'));
            } catch (e) {}
        };
        pintar();
        setInterval(pintar, 1500);
    }
};

/**
 * Mini reproductor de YouTube (IFrame API oficial). El cuadro se queda visible (200x200)
 * y con los controles de YouTube; la página no oculta el video ni quita anuncios.
 */
var MiniYT = {
    player: null,
    listo: null,
    estado: { query: '', lista: [], idx: -1, tiempo: 0, sonando: false, x: null, y: null },
    fallos: 0,
    reloj: null,

    fnLeer: function () {
        try {
            var e = JSON.parse(localStorage.getItem('ytEstado') || 'null');
            if (e && e.lista) MiniYT.estado = $.extend(MiniYT.estado, e);
        } catch (err) {}
    },

    fnGuardar: function () {
        try { localStorage.setItem('ytEstado', JSON.stringify(MiniYT.estado)); } catch (err) {}
    },

    /** YouTube no deja tocar sus videos en páginas abiertas por IP (error 150): hay que entrar por el nombre del equipo. */
    porIp: function () {
        return /^\d{1,3}(\.\d{1,3}){3}$/.test(location.hostname);
    },

    fnAvisoIp: function () {
        var equipo = $('#radioIcono').attr('data-equipo');
        if (!MiniYT.porIp() || !equipo) { $('#ytAvisoIp').hide(); return false; }
        var url = location.protocol + '//' + equipo + (location.port ? ':' + location.port : '') + location.pathname + location.search;
        $('#ytAvisoIp').html('<i class="fa fa-exclamation-triangle"></i> YouTube no toca música cuando el sistema se abre con la IP (' +
            Radio.fnEsc(location.hostname) + '). Entra por el nombre del equipo:<br><a href="' + Radio.fnEsc(url) + '"><b>' +
            Radio.fnEsc(location.protocol + '//' + equipo + (location.port ? ':' + location.port : '') + '/nomina') + '</b></a>' +
            '<br><small>Vuelve a iniciar sesión ahí y guárdala en favoritos.</small>').show();
        return true;
    },

    fnApi: function () {
        if (MiniYT.listo) return MiniYT.listo;
        MiniYT.listo = $.Deferred();
        if (window.YT && window.YT.Player) { MiniYT.listo.resolve(); return MiniYT.listo; }
        var antes = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () { if (antes) antes(); MiniYT.listo.resolve(); };
        var sc = document.createElement('script');
        sc.src = 'https://www.youtube.com/iframe_api';
        sc.onerror = function () { MiniYT.listo.reject(); MiniYT.listo = null; };
        document.head.appendChild(sc);
        return MiniYT.listo;
    },

    // ---------------------------------------------------------------- búsqueda
    fnBuscar: function (q) {
        q = $.trim(q || '');
        if (!q) return;
        MiniYT.estado.query = q;
        $('#ytLista').html('<div class="radio-msg"><i class="fa fa-spinner fa-spin"></i> Buscando en YouTube...</div>');
        $.ajax({ url: 'musica/youtube', type: 'post', data: { q: q }, dataType: 'json' }).done(function (r) {
            if (!r.exito) { $('#ytLista').html('<div class="radio-msg text-danger">' + Radio.fnEsc(r.mensaje) + '</div>'); return; }
            MiniYT.estado.lista = $.map(r.videos || [], function (v) { return { id: v.id, titulo: v.titulo, canal: v.canal, imagen: v.imagen }; });
            MiniYT.estado.idx = -1;
            MiniYT.fnGuardar();
            MiniYT.fnPintarLista();
            if (MiniYT.estado.lista.length) MiniYT.fnTocar(0, 0);
            else $('#ytLista').html('<div class="radio-msg">No se encontraron canciones de "' + Radio.fnEsc(q) + '".</div>');
        }).fail(function () {
            $('#ytLista').html('<div class="radio-msg text-danger">No se pudo buscar en YouTube.</div>');
        });
    },

    fnPintarLista: function () {
        var html = '';
        $.each(MiniYT.estado.lista, function (i, v) {
            html += '<div class="radio-estacion yt-cancion' + (i === MiniYT.estado.idx ? ' activa' : '') + '" data-i="' + i + '">' +
                    (v.imagen ? '<img src="' + Radio.fnEsc(v.imagen) + '" alt="">' : '') +
                    '<div><b>' + Radio.fnEsc(v.titulo) + '</b><br><small>' + Radio.fnEsc(v.canal) + '</small></div></div>';
        });
        $('#ytLista').html(html || '<div class="radio-msg">Escribe una canción, grupo o artista.</div>');
        MiniYT.fnPintarAhora();
    },

    fnPintarAhora: function () {
        var v = MiniYT.estado.lista[MiniYT.estado.idx];
        var t = v ? Radio.fnEsc(v.titulo) : '';
        $('#ytAhora').html(v ? '<i class="fa fa-' + (MiniYT.estado.sonando ? 'volume-up' : 'pause') + '"></i> ' + t : '');
        $('#ytMini .yt-titulo').html(t).attr('title', v ? v.titulo + ' · ' + v.canal : '');
        $('#ytMini .yt-play i').attr('class', 'fa fa-' + (MiniYT.estado.sonando ? 'pause' : 'play'));
        $('#ytLista .yt-cancion').removeClass('activa').filter('[data-i="' + MiniYT.estado.idx + '"]').addClass('activa');
        $('#radioIcono').toggleClass('radio-sonando', Radio.estado.sonando || MiniYT.estado.sonando);
    },

    // ---------------------------------------------------------------- reproductor
    fnMini: function () {
        if ($('#ytMini').length) { $('#ytMini').show(); return; }
        $('body').append(
            '<div id="ytMini">' +
            '  <div class="yt-barra" title="Arrastra para mover">' +
            '    <i class="fa fa-youtube-play"></i><span class="yt-titulo"></span>' +
            '    <button type="button" class="yt-ant" title="Anterior"><i class="fa fa-step-backward"></i></button>' +
            '    <button type="button" class="yt-play" title="Pausa / Reproducir"><i class="fa fa-pause"></i></button>' +
            '    <button type="button" class="yt-calidad" title="Agrandar para bajar la calidad (gasta menos internet)"><i class="fa fa-cog"></i></button>' +
            '    <button type="button" class="yt-sig" title="Siguiente"><i class="fa fa-step-forward"></i></button>' +
            '    <button type="button" class="yt-cerrar" title="Cerrar">&times;</button>' +
            '  </div>' +
            '  <div id="ytPlayer"></div>' +
            '</div>');
        var $m = $('#ytMini');
        if (MiniYT.estado.x != null) MiniYT.fnPosicion(MiniYT.estado.x, MiniYT.estado.y);
        $m.find('.yt-ant').on('click', function () { MiniYT.fnTocar(MiniYT.estado.idx - 1, 0); });
        $m.find('.yt-sig').on('click', function () { MiniYT.fnTocar(MiniYT.estado.idx + 1, 0); });
        $m.find('.yt-play').on('click', MiniYT.fnPausa);
        $m.find('.yt-cerrar').on('click', MiniYT.fnCerrar);
        $m.find('.yt-calidad').on('click', MiniYT.fnCalidad);
        // arrastrar desde la barra
        $m.find('.yt-barra').on('mousedown touchstart', function (e) {
            if ($(e.target).closest('button').length) return;
            var p = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
            var o = $m.offset(), dx = p.pageX - o.left, dy = p.pageY - o.top;
            $m.addClass('yt-moviendo');
            $(document).on('mousemove.ytmini touchmove.ytmini', function (ev) {
                var q = ev.originalEvent.touches ? ev.originalEvent.touches[0] : ev;
                MiniYT.fnPosicion(q.clientX - dx, q.clientY - dy);
                ev.preventDefault();
            }).on('mouseup.ytmini touchend.ytmini', function () {
                $(document).off('.ytmini');
                $m.removeClass('yt-moviendo');
                MiniYT.fnGuardar();
            });
            e.preventDefault();
        });
        $(window).on('resize', function () { if (MiniYT.estado.x != null) MiniYT.fnPosicion(MiniYT.estado.x, MiniYT.estado.y); });
    },

    /**
     * YouTube no deja que la página elija la calidad, y en 200x200 esconde su engrane.
     * Este botón agranda el cuadrito un momento para que se vea su ⚙ y se pueda poner 144p.
     */
    fnCalidad: function () {
        var $m = $('#ytMini'), grande = !$m.hasClass('yt-grande');
        $m.toggleClass('yt-grande', grande);
        var w = grande ? Math.min(480, window.innerWidth - 24) : 200, h = grande ? Math.round(w * 9 / 16) + 40 : 200;
        if (MiniYT.player && MiniYT.player.setSize) MiniYT.player.setSize(w, h);
        $m.css('width', grande ? w + 'px' : '');
        $m.find('iframe').css({ width: w + 'px', height: h + 'px' });
        $m.find('.yt-ayuda').remove();
        if (grande) {
            $m.append('<div class="yt-ayuda"><i class="fa fa-lightbulb-o"></i> Pasa el mouse sobre el video, da clic en el <b>⚙</b> de abajo a la derecha › <b>Calidad</b> › <b>144p</b>. ' +
                      'Luego vuelve a dar clic en <i class="fa fa-cog"></i> para achicarlo. Gasta hasta 5 veces menos.</div>');
        }
        $m.find('.yt-calidad i').attr('class', 'fa ' + (grande ? 'fa-compress' : 'fa-cog'));
        if (MiniYT.estado.x != null) MiniYT.fnPosicion(MiniYT.estado.x, MiniYT.estado.y);
    },

    fnPosicion: function (x, y) {
        var $m = $('#ytMini');
        x = Math.max(0, Math.min(x, window.innerWidth - $m.outerWidth()));
        y = Math.max(0, Math.min(y, window.innerHeight - $m.outerHeight()));
        MiniYT.estado.x = x; MiniYT.estado.y = y;
        $m.css({ left: x + 'px', top: y + 'px', right: 'auto', bottom: 'auto' });
    },

    fnTocar: function (i, inicio) {
        var lista = MiniYT.estado.lista;
        if (!lista.length) return;
        i = (i + lista.length) % lista.length;
        MiniYT.estado.idx = i;
        MiniYT.estado.tiempo = inicio || 0;
        MiniYT.estado.sonando = true;
        MiniYT.fnGuardar();
        if (Radio.estado.sonando) Radio.fnDetener();   // no suenan los dos a la vez
        MiniYT.fnMini();
        MiniYT.fnPintarAhora();
        MiniYT.fnApi().done(function () {
            var id = lista[i].id, desde = Math.floor(inicio || 0);
            if (MiniYT.player && MiniYT.player.loadVideoById) {
                MiniYT.player.loadVideoById({ videoId: id, startSeconds: desde });
                return;
            }
            MiniYT.player = new YT.Player('ytPlayer', {
                width: 200, height: 200, videoId: id,
                playerVars: { autoplay: 1, start: desde, playsinline: 1, rel: 0, fs: 0 },
                events: {
                    onReady: function (e) {
                        e.target.playVideo();
                        // el navegador no deja sonar solo al cambiar de pantalla: espera un clic
                        setTimeout(function () {
                            if (MiniYT.estado.sonando && MiniYT.player.getPlayerState() !== YT.PlayerState.PLAYING) MiniYT.fnEsperarClic();
                        }, 2500);
                    },
                    onStateChange: MiniYT.fnCambio,
                    onError: function (e) {
                        // abierto por IP: YouTube rechaza todos los videos, no tiene caso seguir probando
                        if ((e.data === 150 || e.data === 101) && MiniYT.porIp()) {
                            MiniYT.fnCerrar();
                            Radio.fnAbrir();
                            if (!$('#radioPanel').is(':visible')) Radio.fnAbrir();
                            Radio.fnTab('youtube');
                            return;
                        }
                        // video quitado o que no se deja insertar: pasa al siguiente
                        if (++MiniYT.fallos < Math.min(6, MiniYT.estado.lista.length)) MiniYT.fnTocar(MiniYT.estado.idx + 1, 0);
                    }
                }
            });
            clearInterval(MiniYT.reloj);
            MiniYT.reloj = setInterval(MiniYT.fnGuardarTiempo, 2000);
        }).fail(function () {
            $('#ytAhora').html('<span class="text-danger">No se pudo cargar el reproductor de YouTube.</span>');
        });
    },

    fnCambio: function (e) {
        if (e.data === YT.PlayerState.PLAYING) {
            MiniYT.fallos = 0;
            MiniYT.estado.sonando = true;
            $('#radioIcono').removeClass('radio-espera');
        } else if (e.data === YT.PlayerState.PAUSED) {
            MiniYT.estado.sonando = false;
        } else if (e.data === YT.PlayerState.ENDED) {
            MiniYT.fnTocar(MiniYT.estado.idx + 1, 0);
            return;
        }
        MiniYT.fnGuardarTiempo();
        MiniYT.fnPintarAhora();
    },

    fnGuardarTiempo: function () {
        if (!MiniYT.player || !MiniYT.player.getCurrentTime) return;
        MiniYT.estado.tiempo = MiniYT.player.getCurrentTime() || 0;
        MiniYT.fnGuardar();
    },

    fnPausa: function () {
        if (!MiniYT.player || !MiniYT.player.getPlayerState) return;
        if (MiniYT.player.getPlayerState() === YT.PlayerState.PLAYING) MiniYT.player.pauseVideo();
        else MiniYT.player.playVideo();
    },

    fnEsperarClic: function () {
        $('#radioIcono').addClass('radio-espera').attr('title', 'Toca cualquier parte de la página para seguir escuchando');
        $(document).one('click keydown touchstart', function () {
            $('#radioIcono').removeClass('radio-espera').attr('title', 'Radio');
            if (MiniYT.estado.sonando && MiniYT.player && MiniYT.player.playVideo) MiniYT.player.playVideo();
        });
    },

    fnCerrar: function () {
        if (!$('#ytMini').length && !MiniYT.estado.sonando) return;
        clearInterval(MiniYT.reloj);
        if (MiniYT.player && MiniYT.player.destroy) MiniYT.player.destroy();
        MiniYT.player = null;
        $('#ytMini').remove();
        MiniYT.estado.sonando = false;
        MiniYT.fnGuardar();
        MiniYT.fnPintarAhora();
    },

    fnInicializar: function () {
        MiniYT.fnLeer();
        $(window).on('pagehide beforeunload', MiniYT.fnGuardarTiempo);
        // seguía sonando en la pantalla anterior: continúa en el mismo segundo
        if (Radio.desbloqueada() && MiniYT.estado.sonando && MiniYT.estado.idx >= 0 && MiniYT.estado.lista.length) {
            MiniYT.fnTocar(MiniYT.estado.idx, MiniYT.estado.tiempo);
        } else if (MiniYT.estado.sonando) {
            MiniYT.estado.sonando = false;
            MiniYT.fnGuardar();
        }
    }
};

/**
 * Consumo de internet de la música (estimado). La página no puede leer los bytes reales:
 * YouTube los baja dentro de su propio reproductor y la radio es un stream continuo.
 * Se estima cada 2 s con lo que se sabe: la calidad que eligió YouTube y los kbps de la
 * estación. Lleva la cuenta del día en este navegador (localStorage).
 */
var Consumo = {
    // megabits por segundo (video + audio) según la calidad de YouTube; medido: 480p ~1.5 Mbps
    ytMbps: { tiny: 0.25, small: 0.45, medium: 0.8, large: 1.5, hd720: 2.8, hd1080: 5, highres: 8 },
    radioKbpsOmision: 128,
    hoy: { fecha: '', radio: 0, youtube: 0 },   // MB
    ahora: { fuente: '', mbMin: 0, detalle: '' },
    ultimo: 0,

    fnFecha: function () {
        var d = new Date();
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    },

    fnLeer: function () {
        try {
            var c = JSON.parse(localStorage.getItem('musicaConsumo') || 'null');
            if (c && c.fecha === Consumo.fnFecha()) Consumo.hoy = c;
        } catch (e) {}
        if (Consumo.hoy.fecha !== Consumo.fnFecha()) Consumo.hoy = { fecha: Consumo.fnFecha(), radio: 0, youtube: 0 };
    },

    fnGuardar: function () {
        try { localStorage.setItem('musicaConsumo', JSON.stringify(Consumo.hoy)); } catch (e) {}
    },

    /** Qué está sonando ahora y a cuántos MB por minuto. */
    fnTasa: function () {
        if (MiniYT.player && MiniYT.player.getPlayerState && window.YT && MiniYT.player.getPlayerState() === YT.PlayerState.PLAYING) {
            var q = MiniYT.player.getPlaybackQuality ? MiniYT.player.getPlaybackQuality() : '';
            var mbps = Consumo.ytMbps[q] || Consumo.ytMbps.large;
            var nombres = { tiny: '144p', small: '240p', medium: '360p', large: '480p', hd720: '720p', hd1080: '1080p', highres: '4K' };
            return { fuente: 'youtube', mbMin: mbps * 60 / 8, detalle: 'YouTube ' + (nombres[q] || 'auto') };
        }
        if (Radio.audio && Radio.estado.sonando && !Radio.audio.paused) {
            var est = Radio.estado.estaciones[Radio.estado.idx] || {};
            var kbps = est.kbps > 0 ? est.kbps : Consumo.radioKbpsOmision;
            return { fuente: 'radio', mbMin: kbps * 60 / 8 / 1024, detalle: 'Radio ' + kbps + ' kbps' };
        }
        return { fuente: '', mbMin: 0, detalle: '' };
    },

    fnTic: function () {
        var t = Date.now(), seg = Consumo.ultimo ? Math.min((t - Consumo.ultimo) / 1000, 10) : 0;
        Consumo.ultimo = t;
        if (Consumo.hoy.fecha !== Consumo.fnFecha()) Consumo.hoy = { fecha: Consumo.fnFecha(), radio: 0, youtube: 0 };
        Consumo.ahora = Consumo.fnTasa();
        if (Consumo.ahora.fuente && seg > 0) {
            Consumo.hoy[Consumo.ahora.fuente] += Consumo.ahora.mbMin / 60 * seg;
            Consumo.fnGuardar();
        }
        Consumo.fnPintar();
    },

    fnMB: function (mb) {
        return mb >= 1024 ? (mb / 1024).toFixed(2) + ' GB' : (mb >= 10 ? Math.round(mb) : mb.toFixed(1)) + ' MB';
    },

    fnPintar: function () {
        var $c = $('#musicaConsumo');
        if (!$c.length || !$c.is(':visible')) return;
        var a = Consumo.ahora, h = Consumo.hoy;
        $c.html('<div class="rc-titulo"><i class="fa fa-tachometer"></i> Consumo de internet <small>(estimado)</small></div>' +
            '<div class="rc-ahora' + (a.fuente === 'youtube' ? ' rc-alto' : '') + '">' +
            (a.fuente ? '<b>Ahora:</b> ' + a.mbMin.toFixed(1) + ' MB/min · ' + Radio.fnEsc(a.detalle) +
                        ' <small>(~' + Consumo.fnMB(a.mbMin * 60) + '/hora)</small>' : '<b>Ahora:</b> nada sonando') + '</div>' +
            '<div class="rc-hoy"><b>Hoy en esta computadora:</b> Radio ' + Consumo.fnMB(h.radio) + ' · YouTube ' + Consumo.fnMB(h.youtube) +
            ' · <b>Total ' + Consumo.fnMB(h.radio + h.youtube) + '</b></div>');
        $('#ytMini .yt-barra').attr('title', a.fuente === 'youtube' ? 'Gastando ~' + a.mbMin.toFixed(1) + ' MB/min · arrastra para mover' : 'Arrastra para mover');
    },

    fnInicializar: function () {
        if (!$('#radioIcono').length) return;
        Consumo.fnLeer();
        setInterval(Consumo.fnTic, 2000);
    }
};

$(document).ready(Radio.fnInicializar);
