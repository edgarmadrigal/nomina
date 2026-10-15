-- =============================================
-- Descarga directa de checadas de relojes Suprema (BioStar 2 Device SDK)
-- Lo usa tools/RelojSync/RelojSync.exe
--
--  RelojDispositivo : un renglon por reloj (IP, puerto, planta, ultimo evento descargado)
--  RelojEvento      : eventos crudos tal como vienen del reloj (se guardan todos)
--  ProcesaChecadasReloj : pasa las checadas validas de RelojEvento a dbo.checadas
-- =============================================
USE [Nominas];
GO

IF OBJECT_ID('dbo.RelojDispositivo') IS NULL
CREATE TABLE dbo.RelojDispositivo (
    id               INT IDENTITY(1,1) PRIMARY KEY,
    deviceId         BIGINT        NOT NULL UNIQUE,     -- "Device ID" que muestra el reloj
    descripcion      VARCHAR(100)  NULL,
    ip               VARCHAR(50)   NOT NULL,
    puerto           INT           NOT NULL DEFAULT 51211,
    idPlanta         INT           NOT NULL,            -- planta por defecto del reloj
    grupoPlantas     VARCHAR(200)  NULL,                -- plantas donde se busca al empleado, ej. '1,2,3'
    zonaHoraria      VARCHAR(100)  NOT NULL DEFAULT 'Central Standard Time (Mexico)',
    fechaInicio      DATE          NULL,                -- checadas anteriores no se pasan a dbo.checadas
    ultimoEventoId   BIGINT        NOT NULL DEFAULT 0,
    ultimaDescarga   DATETIME      NULL,
    ultimoError      VARCHAR(MAX)  NULL,
    activo           BIT           NOT NULL DEFAULT 1,
    ultimasChecadas  INT           NULL,
    ultimaDescargaInicio DATETIME  NULL,
    sdk              VARCHAR(3)    NOT NULL DEFAULT 'BS2', -- BS2 = BioStar 2 (51211), BS1 = FaceStation viejo (1470), HV = FaceGo / Hanvon (9922)
    insertarDesconocidos BIT       NOT NULL DEFAULT 1      -- 0 = no pasar numeros que no existen en dbo.empleados
);
GO

IF COL_LENGTH('dbo.RelojDispositivo', 'sdk') IS NULL
ALTER TABLE dbo.RelojDispositivo ADD sdk VARCHAR(3) NOT NULL DEFAULT 'BS2';
GO

-- checadas nuevas que trajo la última descarga (lo llena RelojSync.exe)
IF COL_LENGTH('dbo.RelojDispositivo', 'ultimasChecadas') IS NULL
ALTER TABLE dbo.RelojDispositivo ADD ultimasChecadas INT NULL;
GO

-- hora (del servidor) en que empezó la última descarga; con ultimaDescarga
-- delimita qué eventos de RelojEvento trajo esa descarga
IF COL_LENGTH('dbo.RelojDispositivo', 'ultimaDescargaInicio') IS NULL
ALTER TABLE dbo.RelojDispositivo ADD ultimaDescargaInicio DATETIME NULL;
GO

IF COL_LENGTH('dbo.RelojDispositivo', 'insertarDesconocidos') IS NULL
ALTER TABLE dbo.RelojDispositivo ADD insertarDesconocidos BIT NOT NULL DEFAULT 1;
GO

IF OBJECT_ID('dbo.RelojEvento') IS NULL
CREATE TABLE dbo.RelojEvento (
    deviceId         BIGINT        NOT NULL,
    eventoId         BIGINT        NOT NULL,
    fechahoraUtc     DATETIME2(0)  NOT NULL,
    fechahora        DATETIME2(0)  NOT NULL,            -- hora local
    usuarioid        VARCHAR(32)   NOT NULL,
    code             INT           NOT NULL,
    esChecada        BIT           NOT NULL,
    procesado        BIT           NOT NULL DEFAULT 0,
    resultado        VARCHAR(100)  NULL,                -- insertada / ya existia / motivo de rechazo
    fechaDescarga    DATETIME      NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_RelojEvento PRIMARY KEY (deviceId, eventoId)
);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_RelojEvento_pendientes')
CREATE INDEX IX_RelojEvento_pendientes ON dbo.RelojEvento (procesado, esChecada) INCLUDE (usuarioid, fechahora);
GO

-- =============================================
-- Author:      Edgar Madrigal
-- Create date: 2026-10-06
-- Description: Pasa las checadas descargadas de los relojes (RelojEvento) a dbo.checadas.
--              - Una checada por empleado por minuto (igual que la importacion de Excel).
--              - La planta sale del empleado activo dentro del grupoPlantas del reloj;
--                si no se encuentra se usa la planta del reloj. Si el numero existe
--                en 2 empresas del grupo no se inserta (igual que importar.php).
--              - No duplica: si ya existe la checada (usuarioid_norm + minuto) no se inserta.
--   EXEC dbo.ProcesaChecadasReloj
--   EXEC dbo.ProcesaChecadasReloj @soloVista = 1   -- muestra lo que haria sin insertar
-- =============================================
CREATE OR ALTER PROCEDURE dbo.ProcesaChecadasReloj
    @deviceId   BIGINT = NULL,
    @soloVista  BIT    = 0
AS
BEGIN
    SET NOCOUNT ON;
    SET DATEFIRST 1;

    IF OBJECT_ID('tempdb..#Pend')  IS NOT NULL DROP TABLE #Pend;
    IF OBJECT_ID('tempdb..#Emp')   IS NOT NULL DROP TABLE #Emp;
    IF OBJECT_ID('tempdb..#Min')   IS NOT NULL DROP TABLE #Min;

    -- Eventos pendientes que son checada valida
    SELECT  e.deviceId, e.eventoId,
            CONVERT(VARCHAR(32), TRY_CONVERT(BIGINT, e.usuarioid)) AS usuarioid,
            DATEADD(MINUTE, DATEDIFF(MINUTE, 0, e.fechahora), 0)    AS minuto,
            d.idPlanta, d.grupoPlantas, d.insertarDesconocidos,
            CAST(0 AS BIT) AS existeEmpleado
    INTO    #Pend
    FROM    dbo.RelojEvento e
    JOIN    dbo.RelojDispositivo d ON d.deviceId = e.deviceId
    WHERE   e.procesado = 0 AND e.esChecada = 1
      AND   (d.fechaInicio IS NULL OR e.fechahora >= d.fechaInicio)
      AND   (@deviceId IS NULL OR e.deviceId = @deviceId);

    -- El numero existe en dbo.empleados (cualquier empresa / estatus)
    UPDATE p SET existeEmpleado = 1
    FROM   #Pend p
    WHERE  EXISTS (SELECT 1 FROM dbo.empleados em WHERE TRY_CONVERT(BIGINT, em.empleado_id) = TRY_CONVERT(BIGINT, p.usuarioid));

    -- Empleado -> planta dentro del grupo de cada reloj
    SELECT  p.deviceId, p.usuarioid,
            MIN(em.empresa_id) AS empresa_id,
            COUNT(DISTINCT em.empresa_id) AS nEmpresas
    INTO    #Emp
    FROM    (SELECT DISTINCT deviceId, usuarioid, grupoPlantas FROM #Pend WHERE usuarioid IS NOT NULL) p
    JOIN    dbo.empleados em
            ON  TRY_CONVERT(BIGINT, em.empleado_id) = TRY_CONVERT(BIGINT, p.usuarioid)
            AND em.ESTATUS = 'A'
            AND (p.grupoPlantas IS NULL
                 -- (sin STRING_SPLIT: Nominas tiene compatibilidad < 130)
                 OR ',' + REPLACE(p.grupoPlantas, ' ', '') + ',' LIKE '%,' + CONVERT(VARCHAR(10), em.empresa_id) + ',%')
    GROUP BY p.deviceId, p.usuarioid;

    -- Un renglon por empleado/minuto con su resultado
    SELECT  p.usuarioid, p.minuto,
            MIN(p.deviceId) AS deviceId,
            MAX(CASE WHEN em.nEmpresas = 1 THEN em.empresa_id ELSE p.idPlanta END) AS idPlanta,
            MAX(CASE
                    WHEN p.usuarioid IS NULL                          THEN 'usuario no numerico'
                    WHEN em.nEmpresas > 1                             THEN 'repetido en 2 empresas'
                    WHEN p.insertarDesconocidos = 0 AND p.existeEmpleado = 0 THEN 'no existe en empleados'
                    WHEN em.nEmpresas IS NULL                         THEN 'insertada (planta del reloj)'
                    ELSE 'insertada'
                END) AS resultado
    INTO    #Min
    FROM    #Pend p
    LEFT JOIN #Emp em ON em.deviceId = p.deviceId AND em.usuarioid = p.usuarioid
    GROUP BY p.usuarioid, p.minuto;

    -- Ya existe en checadas (por la importacion de Excel u otra corrida)
    UPDATE m SET resultado = 'ya existia'
    FROM   #Min m
    WHERE  m.resultado LIKE 'insertada%'
      AND  EXISTS (SELECT 1 FROM dbo.checadas c WHERE c.usuarioid_norm = m.usuarioid COLLATE SQL_Latin1_General_CP1_CI_AS AND c.fechahora = m.minuto);

    IF @soloVista = 1
    BEGIN
        SELECT resultado, COUNT(*) AS checadas FROM #Min GROUP BY resultado;
        SELECT TOP 200 * FROM #Min ORDER BY minuto;
        RETURN;
    END

    DECLARE @insertadas INT = 0;

    BEGIN TRY
        BEGIN TRAN;

        -- Evita que dos corridas simultaneas inserten la misma checada
        EXEC sp_getapplock @Resource = 'ProcesaChecadasReloj', @LockMode = 'Exclusive', @LockOwner = 'Transaction', @LockTimeout = 60000;

        -- Re-validar dentro del candado por si otra corrida ya las inserto
        UPDATE m SET resultado = 'ya existia'
        FROM   #Min m
        WHERE  m.resultado LIKE 'insertada%'
          AND  EXISTS (SELECT 1 FROM dbo.checadas c WHERE c.usuarioid_norm = m.usuarioid COLLATE SQL_Latin1_General_CP1_CI_AS AND c.fechahora = m.minuto);

        -- Nota: trg_checadas regresa un SELECT 1 al cliente en cada INSERT
        INSERT INTO dbo.checadas (usuarioid, dia_id, fechahora, fecha, hora, reloj, usuario_id, fechaSubida, idPlanta)
        SELECT  m.usuarioid,
                CONVERT(VARCHAR(50), DATEPART(WEEKDAY, m.minuto)),     -- 1=lunes ... 7=domingo
                m.minuto,
                CONVERT(DATE, m.minuto),
                CONVERT(TIME(0), m.minuto),
                CONVERT(VARCHAR(150), m.deviceId),
                NULL,
                GETDATE(),
                m.idPlanta
        FROM    #Min m
        WHERE   m.resultado LIKE 'insertada%';
        SET @insertadas = @@ROWCOUNT;

        -- usuarioid NULL (no numerico) no hace match en el JOIN con #Min
        UPDATE  e
        SET     e.procesado = 1,
                e.resultado = ISNULL(m.resultado, 'usuario no numerico')
        FROM    dbo.RelojEvento e
        JOIN    #Pend p ON p.deviceId = e.deviceId AND p.eventoId = e.eventoId
        LEFT JOIN #Min m ON m.usuarioid = p.usuarioid AND m.minuto = p.minuto;

        UPDATE  e
        SET     e.procesado = 1,
                e.resultado = CASE WHEN e.esChecada = 0 THEN 'no es checada' ELSE 'anterior a fechaInicio' END
        FROM    dbo.RelojEvento e
        JOIN    dbo.RelojDispositivo d ON d.deviceId = e.deviceId
        WHERE   e.procesado = 0
          AND   (e.esChecada = 0 OR e.fechahora < d.fechaInicio)
          AND   (@deviceId IS NULL OR e.deviceId = @deviceId);

        COMMIT;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        THROW;
    END CATCH

    SELECT  @insertadas AS insertadas,
            (SELECT COUNT(*) FROM #Min WHERE resultado = 'ya existia')             AS yaExistian,
            (SELECT COUNT(*) FROM #Min WHERE resultado = 'repetido en 2 empresas') AS repetidos;
END
GO

-- Relojes BIOSTAR 2 de Torreon (Device ID verificado con RelojSync probe el 2026-10-06).
-- La planta real sale del numero de empleado; idPlanta es solo la de respaldo.
-- Sin agregar: los "TORREON RECIBO" (IP 192.168.128.999, invalida) y los ANVIZ.
-- fechaInicio: lunes de la semana 40 (28-sep-2026). Cambiarla si se quieren recuperar semanas anteriores.
INSERT INTO dbo.RelojDispositivo (deviceId, descripcion, ip, puerto, idPlanta, grupoPlantas, fechaInicio)
SELECT v.deviceId, v.descripcion, v.ip, 51211, 2, '1,2,3,4,5,6,7,8,9,10,18,19,22,40,41', '2026-09-28'
FROM (VALUES
    (542389460, '152 DIRECCION',         '192.168.128.208'),
    (542450708, '205 Fundacion 000001',  '192.168.128.205'),
    (542189346, '207 fundacion 000002',  '192.168.128.207'),
    (542189366, '206 fundacion 000005',  '192.168.128.206'),
    (542340160, '214 fundacion 0003',    '192.168.128.251'),
    (542361058, '215 FUNDACION 0004',    '192.168.128.215'),
    (542362128, '90 fundacion 0006',     '192.168.128.209')
) v (deviceId, descripcion, ip)
WHERE NOT EXISTS (SELECT 1 FROM dbo.RelojDispositivo d WHERE d.deviceId = v.deviceId);
GO

-- 170 Desarrollo: FaceStation de primera generacion (Windows CE), BioStar 1 SDK, puerto 1470.
-- Guarda la hora local (no UTC).
INSERT INTO dbo.RelojDispositivo (deviceId, descripcion, ip, puerto, idPlanta, grupoPlantas, fechaInicio, sdk)
SELECT 542184408, '170 Desarrollo', '192.168.128.213', 1470, 4, '1,2,3,4,5,6,7,8,9,10,18,19,22,40,41', '2026-09-28', 'BS1'
WHERE NOT EXISTS (SELECT 1 FROM dbo.RelojDispositivo d WHERE d.deviceId = 542184408);
GO

-- fundacion 001: FaceGo (Hanvon), protocolo de texto en el puerto 9922, hora local. deviceId = dev_id del reloj.
INSERT INTO dbo.RelojDispositivo (deviceId, descripcion, ip, puerto, idPlanta, grupoPlantas, fechaInicio, sdk)
SELECT 6715318120000305, 'fundacion 001 (FaceGo)', '192.168.128.211', 9922, 2, '1,2,3,4,5,6,7,8,9,10,18,19,22,40,41', '2026-09-28', 'HV'
WHERE NOT EXISTS (SELECT 1 FROM dbo.RelojDispositivo d WHERE d.deviceId = 6715318120000305);
GO
