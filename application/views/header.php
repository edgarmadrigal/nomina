<?php header('Access-Control-Allow-Origin: *');  ?>
<!DOCTYPE html >
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>Nominas</title>
    <script src="assets/js/jquery-3.3.1.js"></script>
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
                    <script src="assets/js/DataTables/jquery.dataTables.min.js" type="text/javascript"></script>
                    <script src="assets/js/DataTables/dataTables.buttons.min.js" type="text/javascript"></script>
            
            <!--end local -->  
        <!-- END JS -->
    <!-- END DATATABLES -->
    <script src="assets/js/jszip.min.js" type="text/javascript"></script>
    <script src="assets/js/pdfmake.min.js" type="text/javascript"></script>
    <script src="assets/js/vfs_fonts.js" type="text/javascript"></script>
    <!-- DATATABLES -->
    <script src="assets/js/buttons.html5.min.js" type="text/javascript"></script>
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
    <!-- Dashboard 1 Page CSS -->
    <link href="assets/css/pages/icon-page.css" rel="stylesheet">
    <!-- You can change the theme colors from here -->
    <link href="assets/css/colors/default.css" id="theme" rel="stylesheet">
    <script src="assets/js/dataTables.buttons.min.js"></script>
    <script src="assets/js/login.js" type="text/javascript"></script>
    <script src="assets/js/main.js" type="text/javascript"></script>
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
                        <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up waves-effect waves-dark" href="javascript:void(0)"><i class="fa fa-bars"></i></a> </li>
                    </ul>
                    <!-- ============================================================== -->
                    <!-- User profile and search -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav my-lg-0">
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
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
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
                        <li class=""> <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="true">
                            <i class="icon-Double-Circle"></i><span class="hide-menu">Catalogos</span></a>
                            <ul aria-expanded="true" class="collapse" style="">                             
                                <?php if ($user->idPerfil==1) {?>
                                    <li style="float: left;width: 100%;"> 
                                        <a class="waves-effect waves-dark" href="usuarios" aria-expanded="true">
                                        <i class="fa fa-user-circle-o"></i>
                                        <span class="hide-menu">Usuarios </span></a>
                                    </li>
                                <?php } ?>  
                                <li style="float: left;width: 100%;">  
                                    <a class="waves-effect waves-dark" href="empleados" aria-expanded="true">
                                    <i class="fa fa-address-card"></i>
                                    <span class="hide-menu">Empleados</span></a>
                                </li> 
                                <li style="float: left;width: 100%;">  
                                    <a class="waves-effect waves-dark" href="EmpleadosController_Pedri" aria-expanded="true">
                                    <i class="fa fa-address-card"></i>
                                    <span class="hide-menu">Empleados Pedriceña</span></a>
                                </li>                                                                                               
                                <li style="float: left;width: 100%;">  
                                    <a class="waves-effect waves-dark" href="byte" aria-expanded="true">
                                    <i class="fa fa-male"></i><i class="fa fa-random"></i>
                                    <span class="hide-menu">Empleados Byte</span></a>
                                </li>                                                                
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="horariosusuario" aria-expanded="true">
                                    <i class="fa fa-male"></i><i class="fa fas fa-plus-circle"></i>
                                    <span class="hide-menu">Asignacion de Horarios</span>
                                    </a>
                                </li>
                                <li style="float: left!important;width: 100%;">  
                                    <a class="waves-effect waves-dark" href="horarios" aria-expanded="true">                               
                                        <div id="horario" data-icon="ei-clock" data-size="s" ></div>
                                        <span class="hide-menu">Horarios</span>
                                    </a>
                                </li>
                                <li style="float: left!important;width: 100%;">  
                                    <a class="waves-effect waves-dark" href="incidencia" aria-expanded="true">   
                                        <span class="icon-holder">
                                            <img class="irc_mi" src="assets/images/incidencia.png" data-atf="0" width="25" height="25" style="">
                                        </span>
                                        <span class="hide-menu">incidencias</span>
                                    </a>
                                </li>
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="conceptos" aria-expanded="true">        
                                        <div data-icon="ei-navicon" ></div>                       
                                        <span class="hide-menu">Conceptos</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="true">
                            <i class="icon-Double-Circle"></i><span class="hide-menu">Reportes</span></a>
                            <ul aria-expanded="true" class="collapse" style="height: 0px;">                               
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="checadas" aria-expanded="true">
                                    <i class="fa fa-table"></i>
                                    <span class="hide-menu">Reporte Checadas</span></a>
                                </li>                              
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="checadasBiostar" aria-expanded="true">
                                    <i class="fa fa-table"></i>
                                    <span class="hide-menu">Reporte Checadas Biostar</span></a>
                                </li>
                                <li style="float: left;width: 100%;">
                                    <a class="waves-effect waves-dark" href="asistencias" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Asistencia</span></a>
                                </li>
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="contrato" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Contrato</span></a>
                                </li>

                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="diarioController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Diario</span></a>
                                </li>
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="percepcionesController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu" style="font-size: 15px;" >Reporte Percepciones</span></a>
                                </li>
                                
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="retencionesController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Retenciones</span></a>
                                </li>
                                
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="relacionSolicitudesController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Relacion de Solicitudes</span></a>
                                </li>
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="costoController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Costo</span></a>
                                </li>
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="pensionController" aria-expanded="true">                                    
                                    <div data-icon="ei-calendar"  style="margin:0px!important;"></div>
                                    <span class="hide-menu">Reporte Pension Alimenticia</span></a>
                                </li>
                            </ul>
                        </li>
                        <li class=""> <a class="has-arrow waves-effect waves-dark" href="#" aria-expanded="true">
                            <i class="icon-Double-Circle"></i><span class="hide-menu">Importar/Exportar</span></a>
                            <ul aria-expanded="true" class="collapse" style="height: 0px;">                               
                                <li style="float: left;width: 100%;"> 
                                    <a class="waves-effect waves-dark" href="importar" aria-expanded="true">
                                    <i class="fa fa-upload"></i>
                                    <span class="hide-menu">Importar</span></a>
                                </li> 
                            </ul>
                        </li>
                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->