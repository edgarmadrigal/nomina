-- =============================================
-- Permisos por usuario
--   MenuOpcion      : opciones del menú lateral, agrupadas por sección
--   UsuarioPermiso  : qué opciones ve cada usuario
--   UsuarioEmpresa  : empresas que puede consultar cada usuario
--                     (sin renglones = todas, como antes)
-- Las tablas viejas dbo.modulo y dbo.Permisos no se usan.
-- Ejecutar con sqlcmd -I (QUOTED_IDENTIFIER ON) o desde SSMS.
-- =============================================
USE [Nominas];
GO

IF OBJECT_ID('dbo.MenuOpcion') IS NULL
CREATE TABLE dbo.MenuOpcion (
    id       INT IDENTITY(1,1) PRIMARY KEY,
    seccion  VARCHAR(30)   NOT NULL,     -- General / Catalogos / Reportes / Importar/Exportar
    nombre   VARCHAR(100)  NOT NULL,
    url      VARCHAR(150)  NOT NULL UNIQUE,
    icono    VARCHAR(400)  NULL,         -- HTML del ícono del menú
    orden    INT           NOT NULL,
    activo   BIT           NOT NULL DEFAULT 1
);
GO

IF OBJECT_ID('dbo.UsuarioPermiso') IS NULL
CREATE TABLE dbo.UsuarioPermiso (
    idUsuario INT NOT NULL,
    idOpcion  INT NOT NULL,
    CONSTRAINT PK_UsuarioPermiso PRIMARY KEY (idUsuario, idOpcion)
);
GO

IF OBJECT_ID('dbo.UsuarioEmpresa') IS NULL
CREATE TABLE dbo.UsuarioEmpresa (
    idUsuario INT NOT NULL,
    idEmpresa INT NOT NULL,
    CONSTRAINT PK_UsuarioEmpresa PRIMARY KEY (idUsuario, idEmpresa)
);
GO

-- Opciones del menú (mismo orden e íconos que tenía header.php)
MERGE dbo.MenuOpcion AS t
USING (VALUES
    ('General',          'Produccion',                          'produccion',                              '<i class="fa fa-product-hunt"></i>', 10),

    ('Catalogos',        'Usuarios',                            'usuarios',                                '<i class="fa fa-user-circle-o"></i>', 100),
    ('Catalogos',        'Empleados',                           'empleados',                               '<i class="fa fa-address-card"></i>', 110),
    ('Catalogos',        'Empleados SYSEL',                     'EmpleadosController_SYSEL',               '<i class="fa fa-address-card"></i>', 120),
    ('Catalogos',        'EmpleadosRH',                         'EmpleadosRH',                             '<i class="fa fa-address-card"></i>', 130),
    ('Catalogos',        'Empleados Pedriceña',                 'EmpleadosController_Pedri',               '<i class="fa fa-address-card"></i>', 140),
    ('Catalogos',        'Empleados León Guzmán',               'EmpleadosController_Leon_Guzman',         '<i class="fa fa-address-card"></i>', 150),
    ('Catalogos',        'Empleados TCT',                       'EmpleadosController_TCT',                 '<i class="fa fa-address-card"></i>', 160),
    ('Catalogos',        'Empleados León Guzmán Almohadas',     'EmpleadosController_Leon_Guzman_Almohadas','<i class="fa fa-address-card"></i>', 170),
    ('Catalogos',        'Empleados Arcinas',                   'EmpleadosController_Arcinas',             '<i class="fa fa-address-card"></i>', 180),
    ('Catalogos',        'Empleados Tlahualilo',                'EmpleadosController_Tlahualilo',          '<i class="fa fa-address-card"></i>', 190),
    ('Catalogos',        'Empleados Eventuales',                'EmpleadosController_Eventuales',          '<i class="fa fa-address-card"></i>', 200),
    ('Catalogos',        'Empleados Byte',                      'byte',                                    '<i class="fa fa-male"></i><i class="fa fa-random"></i>', 210),
    ('Catalogos',        'Asignacion de Horarios',              'horariosusuario',                         '<i class="fa fa-male"></i><i class="fa fas fa-plus-circle"></i>', 220),
    ('Catalogos',        'Asignacion de Horarios x Empresa',    'horariosempresa',                         '<i class="fa fa-building"></i>', 230),
    ('Catalogos',        'Horarios',                            'horarios',                                '<div id="horario" data-icon="ei-clock" data-size="s"></div>', 240),
    ('Catalogos',        'incidencias',                         'incidencia',                              '<span class="icon-holder"><img class="irc_mi" src="assets/images/incidencia.png" width="25" height="25"></span>', 250),
    ('Catalogos',        'incidencias x Empresa',               'incidenciaEmpresa',                       '<span class="icon-holder"><img class="irc_mi" src="assets/images/incidencia.png" width="25" height="25"></span>', 260),
    ('Catalogos',        'Conceptos',                           'conceptos',                               '<div data-icon="ei-navicon"></div>', 270),
    ('Catalogos',        'Días Festivos',                       'controllerDiasFestivos',                  '<i class="fa fa-calendar-check-o"></i>', 280),
    ('Catalogos',        'Relojes Checadores',                  'controllerRelojes',                       '<i class="fa fa-clock-o"></i>', 290),

    ('Reportes',         'Reporte Asistencia',                  'asistencias',                             '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 300),
    ('Reportes',         'Reporte Diario',                      'diarioController',                        '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 310),
    ('Reportes',         'Reporte Facturacion',                 'facturacionController',                   '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 320),
    ('Reportes',         'Reporte Percepciones',                'percepcionesController',                  '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 330),
    ('Reportes',         'Reporte Retenciones',                 'retencionesController',                   '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 340),
    ('Reportes',         'Reporte Relacion de Solicitudes',     'relacionSolicitudesController',           '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 350),
    ('Reportes',         'Reporte Costo',                       'costoController',                         '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 360),
    ('Reportes',         'Reporte 137',                         'controller137',                           '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 370),
    ('Reportes',         'Reporte Asistencia Txt',              'controllerReporteAsistenciatxt',          '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 380),
    ('Reportes',         'Reporte Pension Alimenticia',         'pensionController',                       '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 390),
    ('Reportes',         'Reporte HorasxCosto',                 'HorasxCostoController',                   '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 400),
    ('Reportes',         'Reporte HorasxCostoxPuesto',          'HorasxCostoPorPuestoController',          '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 410),
    ('Reportes',         'Reporte Percepciones No timbradas',   'percepcionesNotimbradasController',       '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 420),
    ('Reportes',         'Reporte Checadas Nomiplus',           'checadas',                                '<i class="fa fa-table"></i>', 430),
    ('Reportes',         'Checadas Directo de Checadores y Biostar', 'checadasBiostar',                         '<i class="fa fa-table"></i>', 440),
    ('Reportes',         'Reporte Contrato',                    'contrato',                                '<div data-icon="ei-calendar" style="margin:0px!important;"></div>', 450),

    ('Importar/Exportar','ImportarBiostar',                     'importar',                                '<i class="fa fa-upload"></i>', 500),
    ('Importar/Exportar','importarAnviz',                       'importarAnviz',                           '<i class="fa fa-upload"></i>', 510),
    ('Importar/Exportar','Subir CSV CROM',                      'Reporte',                                 '<i class="fa fa-upload"></i>', 520),
    ('Importar/Exportar','Importar Checadas Nomiplus',          'ActualizaChecadasNomiPlusController',     '<i class="fa fa-upload"></i>', 530)
) AS s (seccion, nombre, url, icono, orden)
ON t.url = s.url
WHEN NOT MATCHED THEN
    INSERT (seccion, nombre, url, icono, orden) VALUES (s.seccion, s.nombre, s.url, s.icono, s.orden);
GO
