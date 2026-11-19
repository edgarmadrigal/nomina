-- Empresas restringidas (IBF): NADIE las ve en ningún reporte ni combo de empresas a menos que
-- tenga un permiso de menú que la otorgue (MenuOpcion.empresaRestringida = id de la empresa), aunque
-- sea administrador o no tenga plantas restringidas. Lo aplica PermisoModel (empresasOcultas).
IF COL_LENGTH('dbo.empresas', 'restringida') IS NULL
    ALTER TABLE dbo.empresas ADD restringida BIT NOT NULL CONSTRAINT DF_empresas_restringida DEFAULT 0;
GO
IF COL_LENGTH('dbo.MenuOpcion', 'empresaRestringida') IS NULL
    ALTER TABLE dbo.MenuOpcion ADD empresaRestringida INT NULL;
GO
SET NOCOUNT ON;
DECLARE @ibf INT = (SELECT id FROM dbo.empresas WHERE nombre = 'IBF');
UPDATE dbo.empresas SET restringida = 1 WHERE id = @ibf AND restringida = 0;

-- Reportes > Checadas IBF (mismo reporte que "Checadas Directo de Checadores", solo de IBF)
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'checadasIBF')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo, empresaRestringida)
    VALUES ('Reportes', N'Checadas IBF', 'checadasIBF', '<i class="fa fa-clock-o"></i>', 445, 1, @ibf);
-- Tener cualquiera de estas dos opciones = permiso de IBF
UPDATE dbo.MenuOpcion SET empresaRestringida = @ibf WHERE url IN ('EmpleadosIBF', 'checadasIBF');
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'checadasIBF' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);

-- Los relojes de Torreón asignan la planta según el empleado ACTIVO dentro de su grupoPlantas:
-- sin IBF ahí, las checadas de IBF caían a la planta por omisión del reloj (ADMINISTRATIVO).
UPDATE dbo.RelojDispositivo
SET grupoPlantas = grupoPlantas + ',' + CONVERT(VARCHAR(10), @ibf)
WHERE grupoPlantas IS NOT NULL
  AND ',' + REPLACE(grupoPlantas, ' ', '') + ',' NOT LIKE '%,' + CONVERT(VARCHAR(10), @ibf) + ',%';

-- Checadas de empleados de IBF que ya se habían guardado en la planta por omisión (2) y que
-- no son de un empleado activo de esa planta: pasan a IBF.
UPDATE c SET idPlanta = @ibf
FROM dbo.checadas c
WHERE c.idPlanta = 2 AND c.fecha >= '2026-10-01'
  AND c.usuarioid COLLATE DATABASE_DEFAULT IN (SELECT empleado_id FROM dbo.empleados WHERE empresa_id = @ibf AND ESTATUS = 'A')
  AND NOT EXISTS (SELECT 1 FROM dbo.empleados e2 WHERE e2.empresa_id = 2 AND e2.ESTATUS = 'A'
                  AND e2.empleado_id = c.usuarioid COLLATE DATABASE_DEFAULT);
SELECT @@ROWCOUNT AS checadasMovidasAIBF;
GO
