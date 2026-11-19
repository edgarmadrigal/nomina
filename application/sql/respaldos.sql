-- =============================================
-- Respaldos automáticos de Nominas
--   RespaldoConfig : qué se respalda, cada cuándo y cuántos días se guardan (se edita en la página)
--   RespaldoDestino: carpeta principal y segunda copia opcional (se edita en la página)
--   RespaldoLog    : historial de cada respaldo (lo muestra Catálogos > Respaldos)
--   EjecutaRespaldos        : respaldo completo (BASE) y de log (LOG) cuando toca, o forzado
--   RespaldoChecadasInicio / RespaldoChecadasFin : los usa el paso PowerShell que exporta checadas
--   Job "Nominas - Respaldos" : corre cada 15 minutos en SQL Server Agent
--   NominasRespaldos.dbo.ArchivoChecadas : los ZIP de checadas (la cuenta de SQL Agent no
--                                          tiene permiso de escribir en la carpeta de respaldos)
-- Ejecutar con sqlcmd -I o desde SSMS.
-- =============================================
IF DB_ID('NominasRespaldos') IS NULL
BEGIN
    CREATE DATABASE [NominasRespaldos];
    ALTER DATABASE [NominasRespaldos] SET RECOVERY SIMPLE;
END
GO
USE [NominasRespaldos];
GO
IF OBJECT_ID('dbo.ArchivoChecadas') IS NULL
CREATE TABLE dbo.ArchivoChecadas (
    idRespaldo  INT            NOT NULL PRIMARY KEY,   -- Nominas.dbo.RespaldoLog.id
    nombre      NVARCHAR(200)  NOT NULL,
    datos       VARBINARY(MAX) NOT NULL,
    fecha       DATETIME       NOT NULL DEFAULT GETDATE()
);
GO

USE [Nominas];
GO

IF OBJECT_ID('dbo.RespaldoConfig') IS NULL
CREATE TABLE dbo.RespaldoConfig (
    tipo            VARCHAR(10)  NOT NULL PRIMARY KEY,   -- BASE / LOG / CHECADAS
    descripcion     VARCHAR(100) NOT NULL,
    activo          BIT          NOT NULL DEFAULT 1,
    hora            CHAR(5)      NULL,                   -- HH:mm, para BASE y CHECADAS (una vez al día)
    cadaMinutos     INT          NULL,                   -- para LOG
    retencionDias   INT          NOT NULL,
    diasExportar    INT          NULL,                   -- CHECADAS: días hacia atrás que lleva el archivo
    solicitado      BIT          NOT NULL DEFAULT 0      -- "Respaldar ahora" de checadas desde la página
);
GO

IF NOT EXISTS (SELECT 1 FROM dbo.RespaldoConfig)
INSERT INTO dbo.RespaldoConfig (tipo, descripcion, activo, hora, cadaMinutos, retencionDias, diasExportar) VALUES
    ('BASE',     'Base de datos completa',               1, '00:00', NULL, 30, NULL),
    ('LOG',      'Log de transacciones',                 1, NULL,    60,   7,  NULL),
    ('CHECADAS', 'Checadas (archivo CSV en ZIP)',        1, '23:30', NULL, 90, 35);
GO

IF OBJECT_ID('dbo.RespaldoLog') IS NULL
CREATE TABLE dbo.RespaldoLog (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    tipo        VARCHAR(10)   NOT NULL,
    inicio      DATETIME      NOT NULL DEFAULT GETDATE(),
    fin         DATETIME      NULL,
    estado      VARCHAR(15)   NOT NULL,      -- EN PROCESO / OK / ERROR / BORRADO
    archivo     VARCHAR(400)  NULL,
    tamanoBytes BIGINT        NULL,
    registros   INT           NULL,          -- CHECADAS: renglones exportados
    origen      VARCHAR(60)   NOT NULL,      -- Programado / Manual (usuario)
    mensaje     VARCHAR(MAX)  NULL
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_RespaldoLog_tipo_inicio')
CREATE INDEX IX_RespaldoLog_tipo_inicio ON dbo.RespaldoLog (tipo, inicio DESC);
GO

-- Dónde se guardan los respaldos (se cambia en la página):
--   orden 1     = carpeta principal (ruta NULL = <carpeta de respaldos de la instancia>\Nominas)
--   orden 2 a 4 = copias adicionales (hasta 3), según servicio:
--       CARPETA                        -> otra carpeta o carpeta de red \\servidor\carpeta
--       MEGA / GDRIVE / ONEDRIVE / DROPBOX -> carpeta que sincroniza la app de esa nube en el servidor
--       AZURE                          -> contenedor de Azure Blob Storage (ruta = URL, sas = token)
--   tipo = CARPETA (se escribe en disco) o NUBE (Azure, se sube por URL)
IF OBJECT_ID('dbo.RespaldoDestino') IS NULL
CREATE TABLE dbo.RespaldoDestino (
    orden   TINYINT       NOT NULL PRIMARY KEY,
    ruta    NVARCHAR(300) NULL,
    activo  BIT           NOT NULL DEFAULT 1
);
GO
IF NOT EXISTS (SELECT 1 FROM dbo.RespaldoDestino)
INSERT INTO dbo.RespaldoDestino (orden, ruta, activo) VALUES (1, NULL, 1);
GO
IF COL_LENGTH('dbo.RespaldoLog', 'archivo2') IS NULL
ALTER TABLE dbo.RespaldoLog ADD archivo2 VARCHAR(2000) NULL;   -- copias (separadas por " | ")
GO
IF COL_LENGTH('dbo.RespaldoLog', 'archivo2') < 2000
ALTER TABLE dbo.RespaldoLog ALTER COLUMN archivo2 VARCHAR(2000) NULL;
GO
IF COL_LENGTH('dbo.RespaldoDestino', 'tipo') IS NULL
ALTER TABLE dbo.RespaldoDestino ADD tipo VARCHAR(10) NOT NULL CONSTRAINT DF_RespaldoDestino_tipo DEFAULT 'CARPETA',
                                    sas  NVARCHAR(1000) NULL;   -- token SAS de Azure
GO
IF COL_LENGTH('dbo.RespaldoDestino', 'servicio') IS NULL
ALTER TABLE dbo.RespaldoDestino ADD servicio VARCHAR(20) NOT NULL CONSTRAINT DF_RespaldoDestino_servicio DEFAULT 'CARPETA';
GO
-- copias que estaban apagadas o vacías ya no se usan (ahora se agregan y quitan en la página)
DELETE FROM dbo.RespaldoDestino WHERE orden > 1 AND (activo = 0 OR NULLIF(ruta, N'') IS NULL);
GO

-- Carpeta principal: la configurada o <carpeta de respaldos de la instancia>\Nominas
CREATE OR ALTER FUNCTION dbo.RespaldoCarpeta (@sub VARCHAR(20))
RETURNS NVARCHAR(400)
AS
BEGIN
    RETURN ISNULL((SELECT NULLIF(ruta, N'') FROM dbo.RespaldoDestino WHERE orden = 1),
                  CONVERT(NVARCHAR(300), SERVERPROPERTY('InstanceDefaultBackupPath')) + N'\Nominas')
         + N'\' + @sub;
END
GO
IF OBJECT_ID('dbo.RespaldoCarpeta2') IS NOT NULL DROP FUNCTION dbo.RespaldoCarpeta2;
IF OBJECT_ID('dbo.RespaldoNube') IS NOT NULL DROP FUNCTION dbo.RespaldoNube;
GO

-- =============================================
-- Copia en la nube (Azure Blob Storage). Crea/actualiza la credencial de SQL Server
-- para el contenedor y hace un respaldo de prueba de [model] (Base/prueba_escritura.bak).
--   EXEC dbo.RespaldoProbarNube N'https://cuenta.blob.core.windows.net/respaldos', N'sv=...&sig=...'
-- =============================================
CREATE OR ALTER PROCEDURE dbo.RespaldoProbarNube
    @url NVARCHAR(300),
    @sas NVARCHAR(1000)
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @sql NVARCHAR(MAX) =
        CASE WHEN EXISTS (SELECT 1 FROM sys.credentials WHERE name = @url) THEN N'ALTER' ELSE N'CREATE' END
        + N' CREDENTIAL [' + REPLACE(@url, N']', N']]') + N'] WITH IDENTITY = ''SHARED ACCESS SIGNATURE'', SECRET = N'''
        + REPLACE(@sas, N'''', N'''''') + N'''';
    EXEC (@sql);

    DECLARE @prueba NVARCHAR(400) = @url + N'/Base/prueba_escritura.bak';
    BEGIN TRY
        BACKUP DATABASE [model] TO URL = @prueba WITH COPY_ONLY, FORMAT, NAME = N'Prueba de escritura';
    END TRY
    BEGIN CATCH
        RAISERROR(N'No se pudo subir el respaldo de prueba a la nube. Revisa la URL del contenedor y que el token SAS esté vigente y tenga permisos de lectura, escritura, lista y borrado (rwdl).', 16, 1);
        RETURN;
    END CATCH
    SELECT CAST(1 AS INT) AS ok;
END
GO

-- =============================================
-- Revisa que SQL Server pueda escribir en una carpeta: crea las subcarpetas y
-- hace un respaldo de prueba de [model] que luego borra. Si no puede, manda error.
--   EXEC dbo.RespaldoProbarCarpeta N'D:\Respaldos'
-- =============================================
CREATE OR ALTER PROCEDURE dbo.RespaldoProbarCarpeta
    @ruta NVARCHAR(300)
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @sub NVARCHAR(400);
    SET @sub = @ruta + N'\Base';     EXEC master.dbo.xp_create_subdir @sub;
    SET @sub = @ruta + N'\Log';      EXEC master.dbo.xp_create_subdir @sub;
    SET @sub = @ruta + N'\Checadas'; EXEC master.dbo.xp_create_subdir @sub;

    DECLARE @prueba NVARCHAR(400) = @ruta + N'\Base\prueba_escritura.bak';
    BEGIN TRY
        BACKUP DATABASE [model] TO DISK = @prueba WITH COPY_ONLY, FORMAT, INIT, NAME = N'Prueba de escritura';
    END TRY
    BEGIN CATCH
        DECLARE @msg NVARCHAR(2000) = N'SQL Server no puede escribir en ' + @ruta
            + N'. La cuenta ' + ISNULL((SELECT TOP 1 service_account FROM sys.dm_server_services WHERE servicename LIKE 'SQL Server (%'), N'del servicio de SQL Server')
            + N' (en carpetas de red: el equipo ' + CONVERT(NVARCHAR(100), SERVERPROPERTY('MachineName')) + N'$) necesita permiso de escritura.';
        RAISERROR(@msg, 16, 1);
        RETURN;
    END CATCH
    EXEC master.dbo.xp_delete_file 0, @prueba;
    SELECT CAST(1 AS INT) AS ok;
END
GO

-- =============================================
-- Author:      Edgar Madrigal
-- Create date: 2026-10-07
-- Description: Respaldo completo (BASE) y de log (LOG) de Nominas.
--              Sin @forzar hace solo lo que toca según dbo.RespaldoConfig
--              (lo llama el job cada 15 min). Con @forzar = 'BASE' / 'LOG' lo hace ya.
--              Borra los archivos más viejos que retencionDias.
--   EXEC dbo.EjecutaRespaldos
--   EXEC dbo.EjecutaRespaldos @forzar = 'BASE', @origen = 'Manual (EDGAR)'
-- =============================================
CREATE OR ALTER PROCEDURE dbo.EjecutaRespaldos
    @forzar VARCHAR(10) = NULL,
    @origen VARCHAR(60) = 'Programado'
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @ahora DATETIME = GETDATE();
    DECLARE @hoy   DATE     = CONVERT(DATE, @ahora);
    DECLARE @sello VARCHAR(20) = FORMAT(@ahora, 'yyyyMMdd_HHmmss');   -- con segundos: dos respaldos en el mismo minuto no se sobrescriben
    DECLARE @tipo VARCHAR(10), @id INT, @archivo NVARCHAR(400), @carpeta NVARCHAR(400), @sql NVARCHAR(MAX);
    DECLARE @archivo2 NVARCHAR(2000), @aviso NVARCHAR(2000), @nombreArch NVARCHAR(200), @sub VARCHAR(10);
    DECLARE @c NVARCHAR(400), @n INT, @m1 NVARCHAR(400), @m2 NVARCHAR(400), @m3 NVARCHAR(400), @url NVARCHAR(400);
    DECLARE @existe TABLE (archivo INT, esCarpeta INT, padre INT);
    DECLARE @destinos TABLE (orden INT, carpeta NVARCHAR(400));        -- copias en carpeta configuradas
    DECLARE @copias   TABLE (n INT IDENTITY, carpeta NVARCHAR(400), archivo NVARCHAR(400));   -- las que sí están disponibles
    DECLARE @nubes    TABLE (orden INT, url NVARCHAR(300));

    DECLARE @pendientes TABLE (tipo VARCHAR(10), orden INT);

    -- ¿Qué toca?
    INSERT INTO @pendientes (tipo, orden)
    SELECT c.tipo, CASE c.tipo WHEN 'BASE' THEN 1 ELSE 2 END
    FROM dbo.RespaldoConfig c
    WHERE c.tipo IN ('BASE', 'LOG')
      AND (
            c.tipo = @forzar
         OR (@forzar IS NULL AND c.activo = 1 AND (
                -- BASE: una vez al día, después de la hora configurada
                (c.tipo = 'BASE' AND CONVERT(TIME, @ahora) >= CONVERT(TIME, c.hora)
                    AND NOT EXISTS (SELECT 1 FROM dbo.RespaldoLog l
                                    WHERE l.tipo = 'BASE' AND l.estado IN ('OK', 'EN PROCESO')
                                      AND l.inicio >= DATEADD(MINUTE, DATEDIFF(MINUTE, CAST('00:00' AS TIME), CONVERT(TIME, c.hora)), CONVERT(DATETIME, @hoy))))
                -- LOG: cada N minutos
             OR (c.tipo = 'LOG'
                    AND NOT EXISTS (SELECT 1 FROM dbo.RespaldoLog l
                                    WHERE l.tipo = 'LOG' AND l.estado IN ('OK', 'EN PROCESO')
                                      AND l.inicio > DATEADD(MINUTE, -c.cadaMinutos + 1, @ahora)))
         ))
      );

    -- El respaldo de log requiere modelo FULL y al menos un respaldo completo previo
    IF EXISTS (SELECT 1 FROM @pendientes WHERE tipo = 'LOG')
       AND (  (SELECT recovery_model_desc FROM sys.databases WHERE name = 'Nominas') = 'SIMPLE'
           OR NOT EXISTS (SELECT 1 FROM msdb.dbo.backupset WHERE database_name = 'Nominas' AND type = 'D'))
        DELETE FROM @pendientes WHERE tipo = 'LOG';

    WHILE EXISTS (SELECT 1 FROM @pendientes)
    BEGIN
        SELECT TOP 1 @tipo = tipo FROM @pendientes ORDER BY orden;
        DELETE FROM @pendientes WHERE tipo = @tipo;

        SET @sub      = CASE @tipo WHEN 'BASE' THEN 'Base' ELSE 'Log' END;
        SET @carpeta  = dbo.RespaldoCarpeta(@sub);
        SET @archivo  = @carpeta + N'\Nominas_' + CASE @tipo WHEN 'BASE' THEN 'FULL_' ELSE 'LOG_' END
                      + @sello + CASE @tipo WHEN 'BASE' THEN '.bak' ELSE '.trn' END;
        SET @nombreArch = RIGHT(@archivo, CHARINDEX(N'\', REVERSE(@archivo)) - 1);
        SET @aviso = N'';
        DELETE FROM @destinos; DELETE FROM @copias; DELETE FROM @nubes;

        -- Copias en carpeta (otra carpeta, red o carpeta sincronizada de MEGA/Drive/...).
        -- Deben existir (se crean al guardarlas en la página); si una no está disponible
        -- (red caída, disco desconectado) se respalda en las demás y queda el aviso.
        INSERT INTO @destinos (orden, carpeta)
        SELECT orden, ruta + N'\' + @sub FROM dbo.RespaldoDestino
        WHERE orden > 1 AND activo = 1 AND tipo = 'CARPETA' AND NULLIF(ruta, N'') IS NOT NULL;
        WHILE EXISTS (SELECT 1 FROM @destinos)
        BEGIN
            SELECT TOP 1 @n = orden, @c = carpeta FROM @destinos ORDER BY orden;
            DELETE FROM @destinos WHERE orden = @n;
            DELETE FROM @existe;
            INSERT INTO @existe EXEC master.dbo.xp_fileexist @c;
            IF EXISTS (SELECT 1 FROM @existe WHERE esCarpeta = 1)
                INSERT INTO @copias (carpeta, archivo) VALUES (@c, @c + N'\' + @nombreArch);
            ELSE
                SET @aviso += N'No se guardó la copia en ' + @c + N': no se encontró la carpeta. ';
        END

        INSERT INTO dbo.RespaldoLog (tipo, estado, archivo, origen) VALUES (@tipo, 'EN PROCESO', @archivo, @origen);
        SET @id = SCOPE_IDENTITY();

        BEGIN TRY
            EXEC master.dbo.xp_create_subdir @carpeta;

            -- MIRROR TO escribe el mismo respaldo en todas las carpetas a la vez (hasta 3 copias)
            IF EXISTS (SELECT 1 FROM @copias)
            BEGIN
                SELECT @m1 = archivo FROM @copias WHERE n = (SELECT MIN(n) FROM @copias);
                SELECT @m2 = archivo FROM @copias WHERE n = (SELECT MIN(n) + 1 FROM @copias);
                SELECT @m3 = archivo FROM @copias WHERE n = (SELECT MIN(n) + 2 FROM @copias);
                SET @sql = N'BACKUP ' + CASE @tipo WHEN 'BASE' THEN N'DATABASE' ELSE N'LOG' END + N' [Nominas] TO DISK = @a'
                         + N' MIRROR TO DISK = @m1'
                         + CASE WHEN @m2 IS NOT NULL THEN N' MIRROR TO DISK = @m2' ELSE N'' END
                         + CASE WHEN @m3 IS NOT NULL THEN N' MIRROR TO DISK = @m3' ELSE N'' END
                         + N' WITH FORMAT, INIT, COMPRESSION, CHECKSUM, NAME = @nom';
                BEGIN TRY
                    EXEC sp_executesql @sql, N'@a NVARCHAR(400), @m1 NVARCHAR(400), @m2 NVARCHAR(400), @m3 NVARCHAR(400), @nom NVARCHAR(100)',
                         @archivo, @m1, @m2, @m3, N'Nominas';
                END TRY
                BEGIN CATCH
                    SET @aviso += N'No se pudieron guardar las copias en ' + STUFF((SELECT N', ' + carpeta FROM @copias FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, N'')
                                + N' (revisa el permiso de escritura); se guardó solo en la carpeta principal. ';
                    DELETE FROM @copias;
                END CATCH
                SET @m1 = NULL; SET @m2 = NULL; SET @m3 = NULL;
            END

            IF NOT EXISTS (SELECT 1 FROM @copias)
            BEGIN
                IF @tipo = 'BASE'
                    BACKUP DATABASE [Nominas] TO DISK = @archivo
                        WITH FORMAT, INIT, COMPRESSION, CHECKSUM, NAME = N'Nominas - completo';
                ELSE
                    BACKUP LOG [Nominas] TO DISK = @archivo
                        WITH FORMAT, INIT, COMPRESSION, CHECKSUM, NAME = N'Nominas - log';
            END

            -- Azure: la base completa se sube como COPY_ONLY (el log se queda en el servidor)
            IF @tipo = 'BASE'
            BEGIN
                INSERT INTO @nubes (orden, url)
                SELECT orden, ruta FROM dbo.RespaldoDestino
                WHERE orden > 1 AND activo = 1 AND tipo = 'NUBE' AND NULLIF(ruta, N'') IS NOT NULL;
                WHILE EXISTS (SELECT 1 FROM @nubes)
                BEGIN
                    SELECT TOP 1 @n = orden, @c = url FROM @nubes ORDER BY orden;
                    DELETE FROM @nubes WHERE orden = @n;
                    SET @url = @c + N'/Base/' + @nombreArch;
                    BEGIN TRY
                        BACKUP DATABASE [Nominas] TO URL = @url
                            WITH COPY_ONLY, FORMAT, COMPRESSION, CHECKSUM, MAXTRANSFERSIZE = 4194304, BLOCKSIZE = 65536,
                                 NAME = N'Nominas - copia en la nube';
                        INSERT INTO @copias (carpeta, archivo) VALUES (NULL, @url);
                    END TRY
                    BEGIN CATCH
                        SET @aviso += N'No se pudo subir la copia a ' + @c + N' (revisa internet y que el token SAS siga vigente). ';
                    END CATCH
                END
            END

            SET @archivo2 = STUFF((SELECT N' | ' + archivo FROM @copias ORDER BY n FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 3, N'');
            UPDATE dbo.RespaldoLog
            SET fin = GETDATE(), estado = 'OK', archivo2 = @archivo2, mensaje = NULLIF(RTRIM(@aviso), N''),
                tamanoBytes = (SELECT TOP 1 b.compressed_backup_size
                               FROM msdb.dbo.backupset b
                               JOIN msdb.dbo.backupmediafamily m ON m.media_set_id = b.media_set_id
                               WHERE m.physical_device_name = @archivo
                               ORDER BY b.backup_finish_date DESC)
            WHERE id = @id;
        END TRY
        BEGIN CATCH
            UPDATE dbo.RespaldoLog SET fin = GETDATE(), estado = 'ERROR', mensaje = ERROR_MESSAGE() WHERE id = @id;
        END CATCH

        -- Limpieza: archivos de respaldo más viejos que la retención
        BEGIN TRY
            DECLARE @corte DATETIME = DATEADD(DAY, -(SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = @tipo), @ahora);
            DECLARE @ext NVARCHAR(4) = CASE @tipo WHEN 'BASE' THEN N'bak' ELSE N'trn' END;
            EXEC master.dbo.xp_delete_file 0, @carpeta, @ext, @corte, 0;
            WHILE EXISTS (SELECT 1 FROM @copias WHERE carpeta IS NOT NULL)
            BEGIN
                SELECT TOP 1 @n = n, @c = carpeta FROM @copias WHERE carpeta IS NOT NULL ORDER BY n;
                DELETE FROM @copias WHERE n = @n;
                EXEC master.dbo.xp_delete_file 0, @c, @ext, @corte, 0;
            END
            UPDATE dbo.RespaldoLog SET estado = 'BORRADO'
            WHERE tipo = @tipo AND estado = 'OK' AND inicio < @corte;
        END TRY
        BEGIN CATCH
        END CATCH
    END

    -- Un respaldo "EN PROCESO" de hace más de 3 horas quedó colgado
    UPDATE dbo.RespaldoLog SET estado = 'ERROR', fin = GETDATE(), mensaje = ISNULL(mensaje, 'No terminó (se interrumpió).')
    WHERE estado = 'EN PROCESO' AND inicio < DATEADD(HOUR, -3, @ahora);
END
GO

-- =============================================
-- Respaldo de checadas: el paso PowerShell del job pregunta si toca, registra
-- el inicio, exporta el archivo y registra el fin.
-- =============================================
CREATE OR ALTER PROCEDURE dbo.RespaldoChecadasInicio
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @ahora DATETIME = GETDATE(), @hoy DATE = CONVERT(DATE, GETDATE());
    DECLARE @toca BIT = 0, @origen VARCHAR(60) = 'Programado';

    SELECT @toca = 1, @origen = CASE WHEN c.solicitado = 1 THEN 'Manual' ELSE 'Programado' END
    FROM dbo.RespaldoConfig c
    WHERE c.tipo = 'CHECADAS'
      AND (c.solicitado = 1
        OR (c.activo = 1 AND CONVERT(TIME, @ahora) >= CONVERT(TIME, c.hora)
            AND NOT EXISTS (SELECT 1 FROM dbo.RespaldoLog l
                            WHERE l.tipo = 'CHECADAS' AND l.estado IN ('OK', 'EN PROCESO')
                              AND l.inicio >= DATEADD(MINUTE, DATEDIFF(MINUTE, CAST('00:00' AS TIME), CONVERT(TIME, c.hora)), CONVERT(DATETIME, @hoy)))));

    IF @toca = 0
    BEGIN
        SELECT CAST(0 AS INT) AS id;
        RETURN;
    END

    UPDATE dbo.RespaldoConfig SET solicitado = 0 WHERE tipo = 'CHECADAS';
    -- el origen manual guarda quién lo pidió (lo deja la página en el mensaje del último registro "SOLICITADO")
    SELECT TOP 1 @origen = ISNULL(l.origen, @origen) FROM dbo.RespaldoLog l
    WHERE l.tipo = 'CHECADAS' AND l.estado = 'SOLICITADO' ORDER BY l.id DESC;
    DELETE FROM dbo.RespaldoLog WHERE tipo = 'CHECADAS' AND estado = 'SOLICITADO';

    INSERT INTO dbo.RespaldoLog (tipo, estado, origen) VALUES ('CHECADAS', 'EN PROCESO', @origen);
    SELECT CAST(SCOPE_IDENTITY() AS INT) AS id, c.diasExportar, c.retencionDias,
           -- carpeta1 solo si se cambió (la predeterminada no la puede escribir la cuenta de SQL Agent)
           CASE WHEN EXISTS (SELECT 1 FROM dbo.RespaldoDestino WHERE orden = 1 AND NULLIF(ruta, N'') IS NOT NULL)
                THEN dbo.RespaldoCarpeta('Checadas') END AS carpeta1,
           -- copias adicionales (el paso PowerShell deja ahí también el ZIP)
           STUFF((SELECT N'|' + d.ruta + N'\Checadas' FROM dbo.RespaldoDestino d
                  WHERE d.orden > 1 AND d.activo = 1 AND d.tipo = 'CARPETA' AND NULLIF(d.ruta, N'') IS NOT NULL
                  ORDER BY d.orden FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 1, N'') AS copias
    FROM dbo.RespaldoConfig c WHERE c.tipo = 'CHECADAS';
END
GO

CREATE OR ALTER PROCEDURE dbo.RespaldoChecadasFin
    @id INT, @estado VARCHAR(15), @archivo NVARCHAR(400) = NULL, @tamano BIGINT = NULL,
    @registros INT = NULL, @mensaje NVARCHAR(MAX) = NULL, @archivo2 NVARCHAR(2000) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.RespaldoLog
    SET fin = GETDATE(), estado = @estado, archivo = @archivo, archivo2 = @archivo2, tamanoBytes = @tamano, registros = @registros, mensaje = @mensaje
    WHERE id = @id;

    -- Retención: se borran los ZIP más viejos que retencionDias
    DECLARE @corte DATETIME = DATEADD(DAY, -(SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = 'CHECADAS'), GETDATE());
    UPDATE l SET estado = 'BORRADO'
    FROM dbo.RespaldoLog l
    WHERE l.tipo = 'CHECADAS' AND l.estado = 'OK' AND l.inicio < @corte;
    DELETE FROM NominasRespaldos.dbo.ArchivoChecadas WHERE fecha < @corte;
END
GO

-- Opción del menú (Catálogos > Respaldos) para los administradores
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'controllerRespaldos')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden) VALUES ('Catalogos', N'Respaldos', 'controllerRespaldos', '<i class="fa fa-hdd-o"></i>', 295);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'controllerRespaldos' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
