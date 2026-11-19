-- =============================================
-- Catálogos > Salarios (controllers/Salarios.php) sobre dbo.salarioPorAnio.
--   * activo: los salarios no se borran (Reporte Contrato los usa por id); se dan de baja
--     y dejan de salir en la lista para elegir.
--   * descripcion crece a 150 para que quepan importes grandes con letra.
--   * Los cambios quedan en la bitácora (Catálogos > Historial de cambios).
-- =============================================
USE [Nominas];
GO
IF COL_LENGTH('dbo.salarioPorAnio', 'activo') IS NULL
    ALTER TABLE dbo.salarioPorAnio ADD activo BIT NOT NULL CONSTRAINT DF_salarioPorAnio_activo DEFAULT 1;
GO
IF COL_LENGTH('dbo.salarioPorAnio', 'descripcion') < 150
    ALTER TABLE dbo.salarioPorAnio ALTER COLUMN descripcion VARCHAR(150) NULL;
GO

-- Lista para Reporte Contrato: solo los activos (mismas columnas que antes)
CREATE OR ALTER PROCEDURE [dbo].[ConsultaSalario]
AS
BEGIN
SELECT  [id]
      ,[salario]
      ,[año]
      ,[descripcion]
  FROM [Nominas].[dbo].[salarioPorAnio]
 WHERE activo = 1          -- AJUSTE 2026-10-08: catálogo de salarios (baja sin borrar)
 ORDER BY [año]
END
GO

EXEC dbo.BitacoraCrearTrigger 'salarioPorAnio';
GO

IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'salarios')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Catalogos', N'Salarios', 'salarios', '<i class="fa fa-money"></i>', 285, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'salarios' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
