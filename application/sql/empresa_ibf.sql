-- Empresa nueva IBF (no está en Microsip: sus empleados se capturan aquí; la sincronización
-- ActualizaEmpleadosMicrosip solo toca las empresas que tienen servidor de Microsip).
-- Empleados iniciales: personal de teñido en IBF (correo de Alejandra Rosas, 2026-10-22) + Edgar Madrigal.
SET NOCOUNT ON;
DECLARE @ibf INT = (SELECT id FROM dbo.empresas WHERE nombre = 'IBF');
IF @ibf IS NULL
BEGIN
    INSERT INTO dbo.empresas (nombre, estatus, code, esTorreon) VALUES ('IBF', 1, 'IBF', 0);
    SET @ibf = (SELECT id FROM dbo.empresas WHERE nombre = 'IBF');
END;

DECLARE @e TABLE (num VARCHAR(20) COLLATE DATABASE_DEFAULT, paterno NVARCHAR(60), materno NVARCHAR(60), nombres NVARCHAR(80), depto NVARCHAR(60));
INSERT INTO @e VALUES
 ('10056', N'MADRIGAL',  N'REYNOSO',   N'EDGAR OSWALDO',              NULL),
 ('82792', N'BALDERAS',  N'CUEVAS',    N'MIGUEL ANGEL',               N'TEÑIDO'),
 ('82781', N'CARRILLO',  N'MURILLO',   N'JESUS',                      N'TEÑIDO'),
 ('82779', N'CARRILLO',  N'PALACIOS',  N'JESUS ADRIAN',               N'TEÑIDO'),
 ('82795', N'ESPINOZA',  N'ARGUELLES', N'LUIS ALBERTO',               N'TEÑIDO'),
 ('82780', N'JUAREZ',    N'MORENO',    N'CARLOS ALBERTO',             N'TEÑIDO'),
 ('82777', N'ORTA',      N'SAUCEDO',   N'BLANCA PERLA DE GUADALUPE',  N'TEÑIDO'),
 ('82790', N'RAMIREZ',   N'ENRIQUEZ',  N'CHRISTIAN',                  N'TEÑIDO'),
 ('82778', N'RENTERIA',  N'SANCHEZ',   N'MAYRA',                      N'TEÑIDO'),
 ('82788', N'ROBLEDO',   N'MURILLO',   N'DERWIN LORENZO',             N'TEÑIDO'),
 ('82776', N'TORRES',    N'GONZALEZ',  N'MARIA DEL CARMEN',           N'TEÑIDO');

INSERT INTO dbo.empleados (empresa_id, empleado_id, NUMERO, NO_BYTE, NOMBRE_COMPLETO, APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRES,
                           NOMBRE_DEPARTAMENTO, ESTATUS)
SELECT @ibf, e.num, e.num, 0, e.paterno + N'/' + e.materno + N',' + e.nombres, e.paterno, e.materno, e.nombres, e.depto, 'A'
FROM @e e
WHERE NOT EXISTS (SELECT 1 FROM dbo.empleados x WHERE x.empresa_id = @ibf AND x.empleado_id = e.num COLLATE DATABASE_DEFAULT);

SELECT @ibf AS empresa_id, empleado_id, NOMBRE_COMPLETO, NOMBRE_DEPARTAMENTO, ESTATUS
FROM dbo.empleados WHERE empresa_id = @ibf ORDER BY NOMBRE_COMPLETO;
GO

-- Menú Catálogos > Empleados IBF (lo ven los administradores; a otros se les da en Usuarios > Permisos)
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'EmpleadosIBF')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Catalogos', N'Empleados IBF', 'EmpleadosIBF', '<i class="fa fa-address-card"></i>', 125, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'EmpleadosIBF' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
