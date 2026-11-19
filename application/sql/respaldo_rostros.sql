-- =============================================
-- Respaldo semanal de los rostros de los relojes BioStar 2 (RespaldoModel::respaldoRostros).
-- RelojSync lee cada reloj (usuarios con rostro, foto, nombre y vigencia) a un archivo .bin + .csv;
-- se juntan en un ZIP que se guarda en NominasRespaldos.dbo.ArchivoRostros y se descarga en
-- Catálogos > Respaldos. Para volver a cargarlos: RelojSync restaurarrostros <deviceId> <archivo.bin>
-- (ver LEEME.txt dentro del ZIP).
-- Lo lanza la tarea de Windows "Nominas - Respaldo rostros" (cada hora revisa si ya toca: domingos a la
-- hora configurada; si se perdió, lo repone esa misma noche o las siguientes, de 19:00 a 05:00).
-- Relojes que se respaldan: ConfigSistema 'relojes_respaldo_rostros' (deviceIds separados por coma):
--   542189366 = 206 fundacion 000005 (principal), 542389460 = 152 DIRECCION (tiene sus propios usuarios).
-- =============================================
USE [NominasRespaldos];
GO
IF OBJECT_ID('dbo.ArchivoRostros') IS NULL
CREATE TABLE dbo.ArchivoRostros (
    idRespaldo  INT            NOT NULL PRIMARY KEY,   -- Nominas.dbo.RespaldoLog.id
    nombre      NVARCHAR(200)  NOT NULL,
    datos       VARBINARY(MAX) NOT NULL,
    fecha       DATETIME       NOT NULL DEFAULT GETDATE()
);
GO

USE [Nominas];
GO
IF NOT EXISTS (SELECT 1 FROM dbo.RespaldoConfig WHERE tipo = 'ROSTROS')
    INSERT INTO dbo.RespaldoConfig (tipo, descripcion, activo, hora, cadaMinutos, retencionDias, diasExportar)
    VALUES ('ROSTROS', 'Rostros de los relojes (semanal)', 1, '20:00', NULL, 56, NULL);
IF NOT EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = 'relojes_respaldo_rostros')
    INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES ('relojes_respaldo_rostros', '542189366,542389460', GETDATE(), 'automatico');
GO
