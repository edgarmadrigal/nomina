<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

// Sin sesión no responde nada salvo el login (debe ir antes de PermisoAcceso)
$hook['post_controller_constructor'][] = array(
    'class'    => 'SesionRequerida',
    'function' => 'revisar',
    'filename' => 'SesionRequerida.php',
    'filepath' => 'hooks',
);

// Quién hace cada petición, para la bitácora de cambios (dbo.Bitacora)
$hook['post_controller_constructor'][] = array(
    'class'    => 'EmpresaRestringida',
    'function' => 'revisar',
    'filename' => 'EmpresaRestringida.php',
    'filepath' => 'hooks',
);

$hook['post_controller_constructor'][] = array(
    'class'    => 'BitacoraContexto',
    'function' => 'poner',
    'filename' => 'BitacoraContexto.php',
    'filepath' => 'hooks',
);

// Bloquea la entrada por liga a pantallas sin permiso (Catálogos > Usuarios > Permisos)
$hook['post_controller_constructor'][] = array(
    'class'    => 'PermisoAcceso',
    'function' => 'revisar',
    'filename' => 'PermisoAcceso.php',
    'filepath' => 'hooks',
);

// Sin licencia vigente bloquea algunas pantallas (application/config/licencia.php)
$hook['post_controller_constructor'][] = array(
    'class'    => 'LicenciaRevisar',
    'function' => 'revisar',
    'filename' => 'LicenciaRevisar.php',
    'filepath' => 'hooks',
);
