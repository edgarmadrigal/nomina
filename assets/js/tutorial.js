/**
 * Tutorial guiado de las novedades del sistema (controllers/Tutorial.php, tabla dbo.UsuarioTutorial).
 * Sale solo en Inicio hasta que el usuario lo termina o lo salta; después se puede volver a ver
 * desde el menú del usuario (arriba a la derecha) > "Ver tutorial".
 * Los pasos de pantallas que el usuario no tiene en su menú se brincan solos.
 * Cada paso nuevo lleva `desde` (la versión en que se agregó): a quien ya vio una versión anterior
 * solo se le muestran las novedades posteriores (más la bienvenida y el cierre).
 */
var Tutorial = {
    i: 0,
    // AJUSTE 2026-11-23: las novedades (soloDesde) no se pueden saltar y cada aviso se lee unos segundos
    obligatorio: false,
    pasos: [],
    // selector: elemento a resaltar (null = al centro)
    todos: [
        { titulo: '¡Bienvenido!', icono: 'fa-hand-paper-o',
          texto: 'El sistema tuvo varias mejoras. En un minuto te mostramos dónde está cada cosa.<br><small>Usa <b>Siguiente</b> o las flechas del teclado.</small>' },
        { titulo: 'Lo que el sistema hace solo', icono: 'fa-magic',
          texto: 'Varias cosas ya son <b>automáticas</b>, no tienes que hacerlas a mano:<br>' +
                 '• Las <b>checadas</b> se bajan solas de los relojes checadores (en <b>Checadas</b> ves la hora de la última descarga de cada reloj).<br>' +
                 '• Los <b>empleados</b> se traen de Microsip <b>cada hora</b>.<br>' +
                 '• Los <b>conceptos de nómina</b> de Microsip se actualizan <b>cada hora</b>.<br>' +
                 '• Cada alta, cambio o baja queda guardado solo en el <b>Historial de cambios</b>.<br>' +
                 '<small>Solo si necesitas algo al instante hay botones para hacerlo manual (p. ej. <i>Actualizar Empleados Microsip</i> o <i>Actualizar ahora</i>).</small>' },
        { selector: '.left-sidebar', titulo: 'Tu menú',
          texto: 'Aquí están tus pantallas. Solo ves las que tienes permitidas; si te falta alguna, pídela al administrador.' },
        { selector: '#sidebarnav a[href="checadasBiostar"]', titulo: 'Checadas',
          texto: 'Elige planta, año y semana y da <b>Procesar</b>.<br>• Los días laborales sin checadas ni incidencia salen como <span class="tut-falta">FALTA</span>.<br>• No se puede procesar una semana que todavía no empieza.' },
        { selector: '#sidebarnav a[href="checadasBiostar"]', titulo: 'Checadas: Mostrar columnas', desde: '2026-10-23', icono: 'fa-columns',
          texto: 'Arriba de la tabla hay casillas para <b>poner o quitar columnas</b>:<br>' +
                 '☐ Checadas de comida &nbsp;☑ No. semana &nbsp;☐ Fechas &nbsp;☐ Departamento &nbsp;☐ Puesto<br>' +
                 '• Por omisión solo se ve el <b>No. semana</b> y la entrada y salida de cada día.<br>' +
                 '• Marca una casilla para ver esas columnas; lo que quites <b>tampoco sale en Excel</b>.<br>' +
                 '• Tu elección se recuerda en tu computadora.' },
        { selector: '#sidebarnav a[href="incidencia"]', titulo: 'Incidencias',
          texto: 'Al capturar una incidencia ahora eliges <b>¿Qué cubre?</b>: Entrada, Entrada de comida, Salida de comida o Salida (o <b>Todo el día</b>).<br>Ejemplo: <i>Retardo ruta transporte</i> cubre solo la entrada y las demás checadas se toman del checador.' },
        { selector: '#sidebarnav a[href="incidencia"]', titulo: 'Incidencia a varios empleados', desde: '2026-10-23', icono: 'fa-users',
          texto: 'En <b>Incidencias</b>, elige la empresa y usa el botón <b>Incidencia a varios empleados</b>:<br>' +
                 '• Eliges la incidencia y el día (del / al).<br>' +
                 '• Marcas qué checadas cubre: ☑ Entrada ☑ Entrada a comedor ☑ Salida del comedor ☑ Salida.<br>' +
                 '• Marcas a los empleados con sus casillas (hay buscador y <b>Marcar todos los que se ven</b>).<br>' +
                 'Si alguien ya tenía esa incidencia en esas fechas no se duplica.' },
        { selector: '#sidebarnav a[href="incidenciaEmpresa"]', titulo: 'Incidencias x Empresa',
          texto: 'Aplica la incidencia a <b>todos los activos de la planta</b> o <b>elige empleados</b>.<br>En la columna <b>Empleados</b>, con <b>Ver</b> sabes quién la tiene y puedes quitársela a alguien.' },
        { selector: '#sidebarnav a[href="empleados"]', titulo: 'Empleados',
          texto: 'Se actualizan <b>solos cada hora</b> desde Microsip (verás la hora de la última actualización). Si necesitas un alta ya, usa <b>Actualizar Empleados Microsip</b>.' },
        { selector: '#sidebarnav a[href="conceptos"]', titulo: 'Conceptos de incidencias',
          texto: 'Antes se llamaba <i>Conceptos</i>: son los tipos de incidencia (vacaciones, permisos, transporte…).' },
        { selector: '#sidebarnav a[href="ConceptosMicrosip"]', titulo: 'Conceptos Microsip',
          texto: 'Los conceptos de nómina de cada empresa en Microsip, actualizados cada hora.<br>Aquí eliges la <b>clave</b> que lleva cada renglón del TXT; si una clave no existe en Microsip sale en <span class="tut-falta">rojo</span>.' },
        { selector: '#sidebarnav a[href="controllerReporteAsistenciatxt"]', titulo: 'Reporte Asistencia TXT',
          texto: 'Primero <b>Procesar</b> y luego <b>Descargar fichero</b>; así el TXT siempre lleva lo último.<br>• En Torreón cada falta va como 1.2 días (en Microsip: <b>01:1:36</b>).<br>• Abajo del botón está el <b>Formato para Microsip</b>.' },
        { selector: '#sidebarnav a[href="checadasBiostar"]', titulo: 'Corregido: el día festivo salía como falta', desde: '2026-11-23', icono: 'fa-calendar-check-o',
          texto: '<b>El lunes 16 de noviembre</b> (festivo) salía como <span class="tut-falta">FALTA</span>. <b>Ya está corregido:</b><br>' +
                 '• En <b>Checadas</b> ahora sale <b>FESTIVO</b> en verde.<br>' +
                 '• El <b>TXT</b> y la <b>prenómina</b> ya no lo cuentan como falta.<br>' +
                 '• La <b>semana 47</b> ya se recalculó: si ya la habías sacado, vuelve a sacarla.<br>' +
                 '<small>Solo es falta si ese día hay incapacidad u otra incidencia de día completo sin goce.</small>' },
        { selector: '#sidebarnav a[href="controllerReporteAsistenciatxt"]', titulo: 'TXT: formato nuevo de Microsip', desde: '2026-11-23', icono: 'fa-exclamation-triangle',
          texto: 'El TXT cambió: ahora trae los <b>días de vacaciones</b> y el <b>tipo de movimiento</b>.<br>' +
                 '• En la pantalla descarga el <b>Formato para Microsip</b> y úsalo al importar en Microsip (con el anterior marca error).<br>' +
                 '• Al entrar a la pantalla viene un <b>GIF</b> con los pasos.' },
        { selector: '#sidebarnav a[href="salarios"]', titulo: 'Salarios',
          texto: 'El catálogo de salarios por año con su importe con letra, que usa Reporte Contrato.' },
        { selector: '#sidebarnav a[href="usuarios"]', titulo: 'Usuarios',
          texto: 'Ahora un usuario puede tener <b>varias plantas</b>: la principal y las que puede ver. Los permisos de cada pantalla siguen en <b>Acciones &gt; Permisos</b>.' },
        { selector: '#sidebarnav a[href="bitacora"]', titulo: 'Historial de cambios',
          texto: 'Quién dio de alta, modificó o eliminó algo en los catálogos, con fecha y hora, en tiempo real.' },
        { selector: '#sidebarnav a[href="reportesMicrosip"]', titulo: 'Reportes Microsip', desde: '2026-10-23', icono: 'fa-file-text-o',
          texto: 'Los reportes del Reporteador de Microsip ya están aquí: <b>Catálogo de empleados</b>, <b>Fonacot</b> e <b>Infonavit x empleado</b>.<br>Eliges empresa y fechas, y lo imprimes o bajas a Excel.' },
        { selector: '#sidebarnav a[href="horariosempresa"]', titulo: 'Horarios x Empresa', desde: '2026-10-23',
          texto: 'Al <b>Eliminar</b> un horario primero te muestra la lista de empleados y asignaciones que se van a borrar, y solo borra si confirmas.' },
        { selector: '#toggleNocturno', titulo: 'Modo nocturno',
          texto: 'Cambia entre fondo claro y oscuro. Se queda como lo dejes.' },
        { selector: '.cb-burbuja:visible', titulo: 'Chat y ayuda',
          texto: 'Con estos botones platicas con otros usuarios o reportas una falla. Puedes arrastrar una captura de pantalla al chat.' },
        { selector: '.u-pro', titulo: '¿Lo quieres ver otra vez?',
          texto: 'Entra a tu nombre y elige <b>Ver tutorial</b> cuando quieras.' },
        { titulo: '¡Listo!', icono: 'fa-check-circle',
          texto: 'Ya no te volverá a salir al entrar. Si algo no funciona como esperas, avísanos por el chat.' }
    ],

    // soloDesde: versión que el usuario ya vio -> solo las novedades posteriores (null = recorrido completo)
    fnIniciar: function (soloDesde) {
        // solo los pasos cuyo elemento existe (los del menú dependen de los permisos)
        var hay = function (p) { return !p.selector || $(p.selector).length > 0; };
        if (soloDesde) {
            var nuevos = $.grep(Tutorial.todos, function (p) { return p.desde && p.desde > soloDesde && hay(p); });
            if (!nuevos.length) { $.post('tutorial/terminar', { resultado: 'terminado' }); return; }
            // AJUSTE 2026-11-23: directo a lo nuevo, sin paso de introducción
            Tutorial.pasos = nuevos
                .concat([{ titulo: '¡Listo!', icono: 'fa-check-circle',
                           texto: 'Eso es todo lo nuevo. El tutorial completo está en tu nombre &gt; <b>Ver tutorial</b>.' }]);
        } else {
            Tutorial.pasos = $.grep(Tutorial.todos, hay);
        }
        Tutorial.obligatorio = !!soloDesde;
        Tutorial.i = 0;
        if (!$('#tutFondo').length) {
            $('body').append('<div id="tutFondo"></div><div id="tutLuz"></div>' +
                '<div id="tutCaja" role="dialog" aria-live="polite"><div class="tut-cab"><span class="tut-titulo"></span><span class="tut-num"></span></div>' +
                '<div class="tut-texto"></div><div class="tut-pie">' +
                '<a href="#" class="tut-saltar">Saltar tutorial</a>' +
                '<button type="button" class="btn btn-sm btn-secondary tut-ant"><i class="fa fa-chevron-left"></i> Anterior</button>' +
                '<button type="button" class="btn btn-sm btn-info tut-sig">Siguiente <i class="fa fa-chevron-right"></i></button></div></div>');
        }
        $('#tutFondo, #tutLuz, #tutCaja').show();
        $('body').addClass('tut-activo');
        Tutorial.fnMostrar();
    },

    fnMostrar: function () {
        var p = Tutorial.pasos[Tutorial.i], ultimo = Tutorial.i === Tutorial.pasos.length - 1;
        $('#tutCaja .tut-titulo').html((p.icono ? '<i class="fa ' + p.icono + '"></i> ' : '') + p.titulo);
        $('#tutCaja .tut-num').text((Tutorial.i + 1) + ' de ' + Tutorial.pasos.length);
        $('#tutCaja .tut-texto').html(p.texto);
        $('#tutCaja .tut-ant').toggle(Tutorial.i > 0);
        $('#tutCaja .tut-saltar').toggle(!ultimo && !Tutorial.obligatorio);
        if (Tutorial.obligatorio) {   // Siguiente se habilita a los 4 s para que alcancen a leerlo
            var $sig = $('#tutCaja .tut-sig').prop('disabled', true), paso = Tutorial.i;
            setTimeout(function () { if (Tutorial.i === paso) $sig.prop('disabled', false); }, 4000);
        } else {
            $('#tutCaja .tut-sig').prop('disabled', false);
        }
        $('#tutCaja .tut-sig').html(ultimo ? 'Terminar <i class="fa fa-check"></i>' : 'Siguiente <i class="fa fa-chevron-right"></i>');

        var $el = p.selector ? $(p.selector).first() : $();
        // si está dentro de una sección cerrada del menú (Catálogos, Reportes…), se abre
        $el.parents('ul.collapse').addClass('in').attr('aria-expanded', true).parent('li').addClass('active');
        if ($el.length && $el.is(':visible') && $el[0].scrollIntoView) $el[0].scrollIntoView({ block: 'nearest' });
        setTimeout(function () { Tutorial.fnPosicionar($el); }, 60);
    },

    /** Resalta el elemento y pone la caja a un lado; sin elemento visible va al centro. */
    fnPosicionar: function ($el) {
        var caja = $('#tutCaja'), luz = $('#tutLuz'), anchoV = $(window).width(), altoV = $(window).height();
        var r = $el.length && $el.is(':visible') ? $el[0].getBoundingClientRect() : null;
        if (!r || r.width === 0 || r.height === 0) {
            luz.hide(); $('#tutFondo').addClass('tut-oscuro');
            caja.css({ left: Math.max(8, (anchoV - caja.outerWidth()) / 2), top: Math.max(8, (altoV - caja.outerHeight()) / 2) });
            return;
        }
        $('#tutFondo').removeClass('tut-oscuro');
        var m = 6;
        luz.show().css({ left: r.left - m, top: r.top - m, width: r.width + m * 2, height: Math.min(r.height, altoV - 20) + m * 2 });
        var w = caja.outerWidth(), h = caja.outerHeight(), left, top;
        if (r.right + 16 + w <= anchoV) { left = r.right + 16; top = r.top; }               // a la derecha (menú lateral)
        else if (r.left - 16 - w >= 0) { left = r.left - 16 - w; top = r.top; }            // a la izquierda (barra de arriba)
        else { left = (anchoV - w) / 2; top = r.bottom + 16 <= altoV - h ? r.bottom + 16 : Math.max(8, r.top - 16 - h); }
        caja.css({ left: Math.max(8, Math.min(left, anchoV - w - 8)), top: Math.max(8, Math.min(top, altoV - h - 8)) });
    },

    fnCerrar: function (resultado) {
        $('#tutFondo, #tutLuz, #tutCaja').hide();
        $('body').removeClass('tut-activo');
        $.post('tutorial/terminar', { resultado: resultado });
    }
};

$(document).on('click', '#tutCaja .tut-sig', function () {
    if (Tutorial.i < Tutorial.pasos.length - 1) { Tutorial.i++; Tutorial.fnMostrar(); } else Tutorial.fnCerrar('terminado');
});
$(document).on('click', '#tutCaja .tut-ant', function () { if (Tutorial.i > 0) { Tutorial.i--; Tutorial.fnMostrar(); } });
$(document).on('click', '#tutCaja .tut-saltar', function (e) {
    e.preventDefault();
    if (Tutorial.obligatorio) return;
    Tutorial.fnCerrar('omitido');
    if (typeof swal === 'function') swal('Tutorial', 'Ya no te saldrá al entrar. Puedes verlo cuando quieras desde tu nombre > Ver tutorial.', 'info');
});
$(document).on('keydown', function (e) {
    if (!$('body').hasClass('tut-activo')) return;
    if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); $('#tutCaja .tut-sig:enabled').trigger('click'); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); $('#tutCaja .tut-ant:visible').trigger('click'); }
    else if (e.key === 'Escape') { $('#tutCaja .tut-saltar').trigger('click'); }
});
$(window).on('resize', function () {
    if (!$('body').hasClass('tut-activo')) return;
    var p = Tutorial.pasos[Tutorial.i];
    Tutorial.fnPosicionar(p.selector ? $(p.selector).first() : $());
});

$(function () {
    // "Ver tutorial" en el menú del usuario (arriba a la derecha)
    var salir = $('#logout_admin').closest('li');
    if (salir.length && !$('#verTutorial').length) {
        salir.before('<li><a href="#" id="verTutorial"><i class="fa fa-graduation-cap"></i> Ver tutorial</a></li>');
    }
    $(document).on('click', '#verTutorial', function (e) { e.preventDefault(); Tutorial.fnIniciar(null); });

    // Solo sale solo en Inicio (la pantalla a la que llegan al entrar)
    var ruta = window.location.pathname.replace(/\/+$/, '').split('/').pop().toLowerCase();
    if (ruta !== 'home' && ruta !== 'nomina' && ruta !== 'index.php') return;
    $.post('tutorial/estado', {}, null, 'json').done(function (r) {
        // a quien ya vio una versión anterior: solo lo nuevo; a quien nunca lo vio: el recorrido completo
        if (r && r.mostrar) setTimeout(function () { Tutorial.fnIniciar(r.vista || null); }, 900);   // después del preloader
    });
});
