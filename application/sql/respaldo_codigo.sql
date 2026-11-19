-- =============================================
-- Respaldo del código del sistema (proyecto PHP C:\xampp\htdocs\nomina en SISAI).
-- Lo hace el propio sistema (RespaldoModel::respaldoCodigo): arma un ZIP y lo guarda en
-- NominasRespaldos.dbo.ArchivoCodigo, porque el código está en SISAI y los respaldos en el servidor de SQL.
-- Corre con la tarea programada de Windows "Nominas - Respaldo codigo" (cada hora revisa si ya toca)
-- o con "Respaldar ahora" en Catálogos > Respaldos.
-- No lleva .git (está en GitHub), uploads (Excel subidos) ni lo compilado de tools\RelojSync.
-- =============================================
USE [NominasRespaldos];
GO
IF OBJECT_ID('dbo.ArchivoCodigo') IS NULL
CREATE TABLE dbo.ArchivoCodigo (
    idRespaldo  INT            NOT NULL PRIMARY KEY,   -- Nominas.dbo.RespaldoLog.id
    nombre      NVARCHAR(200)  NOT NULL,
    datos       VARBINARY(MAX) NOT NULL,
    fecha       DATETIME       NOT NULL DEFAULT GETDATE()
);
GO

USE [Nominas];
GO
IF NOT EXISTS (SELECT 1 FROM dbo.RespaldoConfig WHERE tipo = 'CODIGO')
    INSERT INTO dbo.RespaldoConfig (tipo, descripcion, activo, hora, cadaMinutos, retencionDias, diasExportar)
    VALUES ('CODIGO', 'Código del sistema (PHP en ZIP)', 1, '22:00', NULL, 15, NULL);
GO
