<?php header('Access-Control-Allow-Origin: *');  ?>
<!DOCTYPE html >
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Sube a HTTPS solo si el equipo confía en el certificado (debe ir antes que nada) -->
    <script src="assets/js/httpsUpgrade.js?v=<?php echo filemtime(FCPATH.'assets/js/httpsUpgrade.js'); ?>"></script>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>Nominas</title>
    <script src="assets/js/jquery-3.3.1.js"></script>
    <script>
    // Si la sesión terminó (401 de application/hooks/SesionRequerida.php), regresa al login una sola vez
    $(document).ajaxError(function (e, xhr) {
        if (xhr && xhr.status === 401 && !window.sesionTerminada) { window.sesionTerminada = true; window.location = 'home'; }
    });
    </script>
    <!-- Bootstrap Core CSS -->
    <link href="assets/node_modules/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/node_modules/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
    <!-- DATATABLES -->
    
        <!-- CSS -->
            <!--cdn -->
            <!--
            <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"/>
            <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/buttons/1.5.2/css/buttons.dataTables.css">
            -->
            <!--end cdn -->
            <!--local -->
                    <link rel="stylesheet" type="text/css" href="assets/css/DataTables/jquery.dataTables.min.css"/>
                    <link rel="stylesheet" type="text/css" href="assets/css/DataTables/buttons.dataTables.min.css">            
            <!--end local -->  
        <!-- END CSS -->        
        <!-- JS -->
            <!--cdn -->
            <!--
                    <script src=" https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" type="text/javascript"></script>
                    <script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js" type="text/javascript"></script>
            -->
            <!--end cdn -->
            <!-- local -->
                    <!-- DataTables core and Bootstrap integration are included in assets/js/main.js -->
            
            <!--end local -->  
        <!-- END JS -->
    <!-- END DATATABLES -->
    <link rel="stylesheet" href="assets/css/site-demos.css">
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="assets/js/additional-methods.min.js"></script>  
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <script src="assets/js/gijgo.min.js" type="text/javascript"></script>
    <link href="assets/css/gijgo.min.css" rel="stylesheet" type="text/css" />
    <!--c3 CSS -->
    <link href="assets/node_modules/c3-master/c3.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style2.css" rel="stylesheet">    
    <link href="assets/css/style.css" rel="stylesheet">    
    <link href="assets/css/estilo.css" rel="stylesheet">  
    <!-- Responsive Menu CSS -->
    <link href="assets/css/responsive-menu.css" rel="stylesheet">
    <!-- Dashboard 1 Page CSS -->
    <link href="assets/css/pages/icon-page.css" rel="stylesheet">
    <!-- You can change the theme colors from here -->
    <link href="assets/css/colors/default.css" id="theme" rel="stylesheet">
    <script src="assets/js/login.js" type="text/javascript"></script>
    <script src="assets/js/main.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/dataTables.buttons.min.js" type="text/javascript"></script>
    <script src="assets/js/jszip.min.js" type="text/javascript"></script>
    <script src="assets/js/pdfmake.min.js" type="text/javascript"></script>
    <script src="assets/js/vfs_fonts.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.2/js/buttons.html5.min.js" type="text/javascript"></script>
    <script src="assets/js/responsive-menu.js?v=<?php echo filemtime(FCPATH.'assets/js/responsive-menu.js'); ?>" type="text/javascript"></script>
    <link rel="stylesheet" href="assets/css/evil-icons.min.css">
    <script src="assets/js/evil-icons.min.js"></script>
    <style>
        .icon--ei-clock{
            float: left!important;
            margin-right: 2px!important;
        }
        .icon {
            float: left!important;
            margin-right: 15px;
        }
        .icon--ei-calendar  {
            margin-right: 0px!important;
        }
        </style>
        <link href="assets/css/modo-nocturno.css?v=<?php echo filemtime(FCPATH.'assets/css/modo-nocturno.css'); ?>" rel="stylesheet" type="text/css" />
        <!-- Ajustes para celular (solo pantallas angostas) -->
        <link href="assets/css/responsive-movil.css?v=<?php echo filemtime(FCPATH.'assets/css/responsive-movil.css'); ?>" rel="stylesheet" type="text/css" />
        <!-- Radio (símbolo de música junto al usuario) -->
        <link href="assets/css/radio.css?v=<?php echo filemtime(FCPATH.'assets/css/radio.css'); ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/radio.js?v=<?php echo filemtime(FCPATH.'assets/js/radio.js'); ?>" type="text/javascript"></script>
        <!-- Campos de fecha: el calendario se abre al hacer clic en todo el campo -->
        <script src="assets/js/fechas.js?v=<?php echo filemtime(FCPATH.'assets/js/fechas.js'); ?>" type="text/javascript"></script>
        <?php
        // Invitados: solo música y chat (ver hooks/PermisoAcceso.php)
        get_instance()->load->model('PermisoModel');
        $esInvitado = PermisoModel::esInvitado(isset($user) ? $user : null);
        ?>
        <!-- Chat entre usuarios -->
        <link href="assets/css/chat.css?v=<?php echo filemtime(FCPATH.'assets/css/chat.css'); ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/chatAviso.js?v=<?php echo filemtime(FCPATH.'assets/js/chatAviso.js'); ?>" type="text/javascript"></script>
        <script src="assets/js/chatIA.js?v=<?php echo filemtime(FCPATH.'assets/js/chatIA.js'); ?>" type="text/javascript"></script>
        <script src="assets/js/chatBurbuja.js?v=<?php echo filemtime(FCPATH.'assets/js/chatBurbuja.js'); ?>" type="text/javascript"></script>
<script>
    // Aplica el modo nocturno antes de pintar la pagina (evita el parpadeo en blanco)
    try { if (localStorage.getItem('modoNocturno') === '1') { document.documentElement.classList.add('modo-nocturno'); } } catch (e) {}
</script>
</head>
<body class="fix-header fix-sidebar card-no-border">
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="loader">
            <div class="loader__figure"></div>
            <p class="loader__label">Nominas</p>
        </div>
    </div>
    <?php
    // Barra "Estás viendo como…": aparece cuando un administrador está viendo el sistema como otro usuario
    // (controllers/Usuarios.php -> suplantar/volver; la sesión guarda 'suplantacion').
    $CIsup =& get_instance();
    $sup = $CIsup->session->userdata('suplantacion');
    if (!empty($sup) && !empty($sup['admin'])):
    ?>
    <div id="barraSuplantacion">
        <span><i class="fa fa-eye"></i> Estás viendo el sistema como
            <b><?php echo htmlspecialchars(isset($sup['destinoNombre']) ? $sup['destinoNombre'] : '', ENT_QUOTES); ?></b>
            (<?php echo htmlspecialchars(isset($sup['destinoUsuario']) ? $sup['destinoUsuario'] : '', ENT_QUOTES); ?>)
            — no eres tú.</span>
        <button type="button" id="btnVolverAdmin"><i class="fa fa-reply"></i> Volver a mi cuenta
            (<?php echo htmlspecialchars(isset($sup['adminUsuario']) ? $sup['adminUsuario'] : '', ENT_QUOTES); ?>)</button>
    </div>
    <style>
        #barraSuplantacion { position: fixed; left: 0; right: 0; bottom: 0; z-index: 99999; background: #bf360c;
            color: #fff; padding: 11px 14px; text-align: center; font-size: 15px; font-weight: 600;
            box-shadow: 0 -2px 12px rgba(0,0,0,.45); border-top: 2px solid #ff9800; }
        #barraSuplantacion span { color: #fff; }
        #barraSuplantacion b { color: #ffe0b2; font-weight: 800; }
        #barraSuplantacion button { margin-left: 16px; background: #fff; color: #bf360c; border: none; border-radius: 4px;
            padding: 6px 16px; font-weight: 800; font-size: 14px; cursor: pointer; }
        #barraSuplantacion button:hover { background: #ffe0b2; }
        body.fix-header .page-wrapper { padding-bottom: 54px; }
    </style>
    <script>
        $(function () {
            $('#btnVolverAdmin').on('click', function () {
                var b = $(this).prop('disabled', true);
                $.ajax({ url: 'usuarios/volver', type: 'post', dataType: 'json' })
                    .always(function () { window.location = 'home'; });
            });
        });
    </script>
    <?php endif; ?>
    <?php
    // Barra MODO DESARROLLO: solo en el equipo de desarrollo (config 'es_desarrollo', ver config.php).
    // El administrador ve además el botón Publicar (controllers/Publicar.php) que copia los cambios a producción.
    if ($CIsup->config->item('es_desarrollo')):
        $esAdminDev = isset($user->idPerfil) && $user->idPerfil == 1;
    ?>
    <div id="barraDesarrollo">
        <span><i class="fa fa-wrench"></i> MODO DESARROLLO · <?php echo htmlspecialchars(gethostname(), ENT_QUOTES); ?></span>
        <?php if ($esAdminDev): ?>
        <button type="button" id="btnPublicar" title="Copiar los cambios de este equipo a producción (SISAI)"><i class="fa fa-cloud-upload"></i> Publicar</button>
        <?php endif; ?>
    </div>
    <?php if ($esAdminDev): ?>
    <div id="panelPublicar" style="display:none;">
        <div class="pp-caja">
            <div class="pp-titulo"><i class="fa fa-cloud-upload"></i> Publicar a producción <button type="button" class="pp-cerrar" title="Cerrar">&times;</button></div>
            <div class="pp-msg"></div>
            <div class="pp-lista"></div>
            <div class="pp-botones">
                <button type="button" class="pp-cancelar">Cancelar</button>
                <button type="button" class="pp-confirmar" style="display:none;"><i class="fa fa-check"></i> Publicar ahora</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <style>
        #barraDesarrollo { position: fixed; top: 6px; left: 50%; transform: translateX(-50%); z-index: 100000;
            background: repeating-linear-gradient(45deg, #ffc107, #ffc107 12px, #ffb300 12px, #ffb300 24px);
            color: #212121; border: 2px solid #212121; border-radius: 20px; padding: 4px 6px 4px 14px;
            font-weight: 800; font-size: 13px; letter-spacing: .5px; box-shadow: 0 2px 10px rgba(0,0,0,.35); white-space: nowrap; }
        #barraDesarrollo span { color: #212121; }
        #barraDesarrollo button { margin-left: 10px; background: #212121; color: #ffc107; border: none; border-radius: 14px;
            padding: 3px 12px; font-weight: 800; font-size: 12px; cursor: pointer; }
        #barraDesarrollo button:hover { background: #000; color: #fff; }
        #panelPublicar { position: fixed; inset: 0; z-index: 100001; background: rgba(0,0,0,.55);
            display: flex; align-items: center; justify-content: center; }
        #panelPublicar .pp-caja { background: #fff; color: #222; width: 720px; max-width: calc(100vw - 32px); max-height: 85vh;
            display: flex; flex-direction: column; border-radius: 8px; box-shadow: 0 6px 30px rgba(0,0,0,.5); }
        #panelPublicar .pp-titulo { background: #212121; color: #ffc107; font-weight: 800; padding: 10px 14px; border-radius: 8px 8px 0 0; }
        #panelPublicar .pp-cerrar { float: right; background: none; border: none; color: #ffc107; font-size: 20px; line-height: 1; cursor: pointer; }
        #panelPublicar .pp-msg { padding: 12px 14px; font-size: 15px; font-weight: 600; }
        #panelPublicar .pp-lista { padding: 0 14px; overflow: auto; flex: 1; font-family: Consolas, monospace; font-size: 12px; }
        #panelPublicar .pp-lista div { padding: 2px 0; border-bottom: 1px solid #eee; white-space: nowrap; }
        #panelPublicar .pp-lista .pp-tipo { display: inline-block; width: 110px; color: #1565c0; }
        #panelPublicar .pp-lista .pp-err { color: #c62828; white-space: normal; }
        /* el tema esconde los checkbox nativos (los pinta con label::before): aquí se fuerzan visibles */
        #panelPublicar .pp-lista .pp-fila { display: block; font-weight: normal; margin: 0; padding: 3px 0 3px 0 !important;
            border-bottom: 1px solid #eee; white-space: nowrap; cursor: pointer; }
        #panelPublicar .pp-lista .pp-fila::before, #panelPublicar .pp-lista .pp-fila::after { display: none !important; content: none !important; }
        #panelPublicar .pp-lista input.pp-chk { position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important;
            -webkit-appearance: checkbox !important; appearance: checkbox !important; display: inline-block !important;
            width: 15px; height: 15px; margin: 0 8px 0 0 !important; vertical-align: middle; pointer-events: auto; }
        #panelPublicar .pp-lista .pp-fecha { display: inline-block; width: 120px; color: #757575; }
        #panelPublicar .pp-lista .pp-sel { position: sticky; top: 0; z-index: 1; background: #fff; display: flex; align-items: center;
            font-weight: 700; padding: 4px 0; border-bottom: 2px solid #ccc; }
        #panelPublicar .pp-lista .pp-sel .pp-fila { border: none; font-weight: 700; }
        #panelPublicar .pp-cuenta { color: #757575; font-weight: normal; margin-left: 12px; }
        #panelPublicar .pp-botones { padding: 10px 14px; text-align: right; border-top: 1px solid #ddd; }
        #panelPublicar .pp-botones button { border: none; border-radius: 4px; padding: 7px 16px; font-weight: 700; cursor: pointer; margin-left: 8px; }
        #panelPublicar .pp-cancelar { background: #e0e0e0; color: #333; }
        #panelPublicar .pp-confirmar { background: #2e7d32; color: #fff; }
        #panelPublicar .pp-confirmar:disabled { opacity: .6; cursor: wait; }
        html.modo-nocturno #panelPublicar .pp-caja { background: #263238; color: #eceff1; }
        html.modo-nocturno #panelPublicar .pp-lista div { border-bottom-color: #37474f; }
        html.modo-nocturno #panelPublicar .pp-lista .pp-tipo { color: #90caf9; }
        html.modo-nocturno #panelPublicar .pp-lista .pp-sel { background: #263238; border-bottom-color: #546e7a; }
        html.modo-nocturno #panelPublicar .pp-lista .pp-fila { border-bottom-color: #37474f; color: #eceff1; }
        html.modo-nocturno #panelPublicar .pp-lista .pp-fecha, html.modo-nocturno #panelPublicar .pp-cuenta { color: #90a4ae; }
    </style>
    <?php if ($esAdminDev): ?>
    <script>
        $(function () {
            var $p = $('#panelPublicar'), $msg = $p.find('.pp-msg'), $lista = $p.find('.pp-lista'), $ok = $p.find('.pp-confirmar');
            var $cancel = $p.find('.pp-cancelar');   // "Cancelar" si hay algo por publicar; "Cerrar" si no
            var enCurso = false;
            function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
            // conCasillas: en la revisión cada archivo trae su casilla para publicar solo los marcados
            function pintar(r, conCasillas) {
                var h = '';
                // Se pre-marca lo más reciente: los archivos del último día en que hubo cambios
                var ultimoDia = '';
                $.each(r.archivos || [], function (i, a) { var d = (a.fecha || '').substr(0, 10); if (d > ultimoDia) ultimoDia = d; });
                if (conCasillas && (r.archivos || []).length) {
                    h += '<div class="pp-sel"><label class="pp-fila"><input type="checkbox" class="pp-todos pp-chk"> Todos</label>'
                       + '<span class="pp-cuenta"></span></div>';
                }
                $.each(r.archivos || [], function (i, a) {
                    var marcado = (a.fecha || '').substr(0, 10) === ultimoDia;
                    h += '<label class="pp-fila">'
                       + (conCasillas ? '<input type="checkbox" class="pp-arch pp-chk"' + (marcado ? ' checked' : '') + ' value="' + esc(a.ruta) + '">' : '')
                       + '<span class="pp-fecha">' + esc(a.fecha || '') + '</span>'
                       + '<span class="pp-tipo">' + esc(a.tipo) + '</span>' + esc(a.ruta) + '</label>';
                });
                if (r.total > (r.archivos || []).length) h += '<div>… y ' + (r.total - r.archivos.length) + ' más</div>';
                $.each(r.errores || [], function (i, e) { h += '<div class="pp-err"><i class="fa fa-warning"></i> ' + esc(e) + '</div>'; });
                $lista.html(h);
                contar();
            }
            var totalRevisado = 0;
            function contar() {
                var n = $lista.find('.pp-arch:checked').length, t = $lista.find('.pp-arch').length;
                $lista.find('.pp-cuenta').text(n + ' de ' + t + ' marcados');
                $lista.find('.pp-todos').prop('checked', n === t).prop('indeterminate', n > 0 && n < t);
                $ok.prop('disabled', n === 0);
            }
            $lista.on('change', '.pp-todos', function () { $lista.find('.pp-arch').prop('checked', this.checked); contar(); });
            $lista.on('change', '.pp-arch', contar);
            function cerrar() { if (!enCurso) $p.hide(); }
            $('#btnPublicar').on('click', function () {
                $lista.empty(); $ok.hide().prop('disabled', false); $cancel.text('Cerrar');
                $msg.html('<i class="fa fa-spinner fa-spin"></i> Revisando qué cambió contra producción…');
                $p.css('display', 'flex');
                enCurso = true;
                $.ajax({ url: 'publicar/revisar', type: 'post', dataType: 'json' })
                    .done(function (r) {
                        $msg.text(r.mensaje || '').css('color', r.exito ? '' : '#c62828');
                        totalRevisado = r.total || 0;
                        pintar(r, r.exito && r.total > 0);
                        if (r.exito && r.total > 0) { $ok.show(); $cancel.text('Cancelar'); contar(); }
                    })
                    .fail(function () { $msg.text('No se pudo revisar (revisa que Apache siga prendido).').css('color', '#c62828'); })
                    .always(function () { enCurso = false; });
            });
            $ok.on('click', function () {
                var marcados = $lista.find('.pp-arch:checked').map(function () { return this.value; }).get();
                var todos = marcados.length === totalRevisado;   // todo marcado = publicación completa
                if (!marcados.length) return;
                if (!confirm(todos ? '¿Publicar TODOS los cambios a producción? Los verán todos los usuarios.'
                                   : '¿Publicar solo los ' + marcados.length + ' archivo(s) marcados? Los demás se quedan pendientes.')) return;
                enCurso = true;
                $ok.prop('disabled', true);
                $msg.html('<i class="fa fa-spinner fa-spin"></i> Publicando… no cierres esta ventana.').css('color', '');
                $.ajax({ url: 'publicar/ejecutar', type: 'post', dataType: 'json', data: todos ? {} : { archivos: marcados } })
                    .done(function (r) {
                        $msg.html((r.exito ? '<i class="fa fa-check-circle" style="color:#2e7d32"></i> ' : '<i class="fa fa-times-circle"></i> ') + esc(r.mensaje))
                            .css('color', r.exito ? '' : '#c62828');
                        pintar(r);
                        $ok.hide();
                        $cancel.text('Cerrar');
                    })
                    .fail(function () { $msg.text('Falló la publicación (revisa application/logs/publicar.log).').css('color', '#c62828'); $ok.prop('disabled', false); })
                    .always(function () { enCurso = false; });
            });
            $p.find('.pp-cancelar, .pp-cerrar').on('click', cerrar);
        });
    </script>
    <?php endif; ?>
    <?php endif; ?>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- ============================================================== -->
                <!-- Logo -->
                <!-- ============================================================== -->
                <div class="navbar-header">
                    <a class="navbar-brand" href="home">
                        <b>
                            <!-- Dark Logo icon -->
                            <img src="assets/images/logo-icon.png" class="dark-logo" style="WIDTH: 50PX!IMPORTANT;" />
                            <!-- Light Logo icon -->
                            <img src="assets/images/logo-light-icon.png" class="light-logo"/>
                        </b>
                        <!--End Logo icon -->
                        <!-- Logo text -->
                        <span>
                         <!-- 
                         <img src="assets/images/logo-text.png" class="dark-logo" />dark Logo text -->
                         <!--
                         <img src="assets/images/logo-light-text.png" class="light-logo"/>-- Light Logo text -->    
                        </span> 
                        </a>
                </div>
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse">
                    <!-- ============================================================== -->
                    <!-- toggle and nav items -->
                    <!-- ============================================================== -->
                     
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" id="toggleNocturno" title="Modo nocturno">
                                <i class="fa fa-moon-o"></i>
                            </a>
                        </li>
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up waves-effect waves-dark" href="javascript:void(0)" id="menu-toggle"><i class="fa fa-bars"></i></a> </li>
                    </ul>
                    <!-- ============================================================== -->
                    <!-- User profile and search -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav my-lg-0">
                        <?php
                        // Radio (assets/js/radio.js): se desbloquea con la contraseña de la música
                        $CI =& get_instance();
                        $hayClaveMusica = false;
                        try {
                            $fm = $CI->db->query("SELECT CASE WHEN OBJECT_ID('dbo.ConfigSistema') IS NOT NULL THEN 1 ELSE 0 END AS t")->row_array();
                            if ($fm && $fm['t'] == 1) {
                                $hayClaveMusica = $CI->db->query("SELECT 1 AS x FROM dbo.ConfigSistema WHERE clave = 'musica_clave' AND valor IS NOT NULL")->num_rows() > 0;
                            }
                        } catch (Exception $e) {}
                        ?>
                        <!-- Chat entre usuarios: oculto arriba, se usa la burbuja (assets/js/chatBurbuja.js);
                             se deja el elemento porque chatAviso.js y chatBurbuja.js lo usan para saber que hay sesión -->
                        <li class="nav-item d-none">
                            <a class="nav-link" href="chat" id="chatIcono" title="Chat y reporte de fallas">
                                <i class="fa fa-comments"></i><span class="chat-badge" style="display:none;"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" id="radioIcono" title="Radio"
                               data-desbloqueada="<?php echo ($esInvitado || $CI->session->userdata('musicaDesbloqueada')) ? '1' : '0'; ?>"
                               data-hayclave="<?php echo $hayClaveMusica ? '1' : '0'; ?>"
                               data-admin="<?php echo (isset($user->idPerfil) && $user->idPerfil == 1) ? '1' : '0'; ?>"
                               data-equipo="sisai">
                                <i class="fa fa-music"></i>
                            </a>
                        </li>
                        <!-- ============================================================== -->
                        <!-- Profile -->
                        <!-- ============================================================== -->
                        <li class="nav-item dropdown u-pro ">
                            <a class="nav-link dropdown-toggle waves-effect waves-dark profile-pic" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <img src="assets/images/users/user.png" alt="user" class=""> 
                            <span class="hidden-md-down"><?php echo strtoupper($user->nombre); ?><i class="fa fa-angle-down"></i></span> </a>
                            <div class="dropdown-menu dropdown-menu-right animated flipInY">
                                <ul class="dropdown-user">
                                    <li>
                                        <div class="dw-user-box">
                                            <!--<div class="u-img"><img src="assets/images/users/1.jpg" alt="user"></div>--> 
                                            <div class="u-text">
                                                <h4><?php echo strtoupper($user->nombre); ?></h4>
                                                <p class="text-muted"><?php echo $user->usuario; ?></p>
                                            </div>
                                        </div>
                                    </li>
                                    <li><a href="licencia"><i class="fa fa-key"> </i> Licencia</a></li>
                                    <?php if (isset($user->idPerfil) && $user->idPerfil == 1): ?>
                                    <li><a href="#" id="menuIpPublica"><i class="fa fa-globe"> </i> IP pública</a></li>
                                    <?php endif; ?>
                                    <li>
                                    <a href="#" id="logout_admin"><i class="fa fa-power-off"> </i> Cerrar Sesión</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!--    id	perfil
                1	Administrador
                2	Nominas
                3	Operadores
                4	Invitados
                5	RH
                6	Terminado  -->
        <!-- ============================================================== -->
        <aside class="left-sidebar">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar">
                <!-- Sidebar navigation-->
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                       <li> <a class="waves-effect waves-dark" href="home" aria-expanded="true">
                        <i class="fa fa-home"></i>
                        <span class="hide-menu">Inicio</span></a>
                        </li>
                        <?php
                        // Menú según los permisos del usuario (dbo.UsuarioPermiso, ver Catálogos > Usuarios > Permisos)
                        $CI =& get_instance();
                        $CI->load->model('PermisoModel');
                        $menu = $CI->PermisoModel->menuUsuario($user);
                        $titulos = array('Catalogos' => 'Catalogos', 'Reportes' => 'Reportes', 'Importar/Exportar' => 'Importar/Exportar');
                        foreach (PermisoModel::$secciones as $seccion) {
                            if (empty($menu[$seccion])) continue;
                            if ($seccion === 'General') {
                                foreach ($menu[$seccion] as $op) { ?>
                        <li>
                            <a class="waves-effect waves-dark" href="<?php echo htmlspecialchars($op['url']); ?>" aria-expanded="true">
                                <?php echo $op['icono']; ?>
                                <span class="hide-menu"><?php echo htmlspecialchars($op['nombre']); ?></span>
                            </a>
                        </li>
                        <?php   }
                                continue;
                            } ?>
                        <li class=""> <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="true">
                            <i class="icon-Double-Circle"></i><span class="hide-menu"><?php echo $titulos[$seccion]; ?></span></a>
                            <ul aria-expanded="true" class="collapse">
                                <?php foreach ($menu[$seccion] as $op) { ?>
                                <li>
                                    <a class="waves-effect waves-dark" href="<?php echo htmlspecialchars($op['url']); ?>" aria-expanded="true">
                                        <?php echo $op['icono']; ?>
                                        <span class="hide-menu"><?php echo htmlspecialchars($op['nombre']); ?></span>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
