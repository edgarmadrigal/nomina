-- =============================================
-- Vacaciones e incapacidades desde Microsip (Nómina, Firebird) a dbo.incidencias.
--   MicrosipEmpresa      : empresa del sistema -> linked server de Microsip (los mismos de
--                          ActualizaEmpleadosMicrosip / vistas empleadosByte_*)
--   incidencias.origenClave : '<servidor>:VAC:<VACACIONES_ID>' / '<servidor>:INC:<INCAPACIDAD_ID>'
--                          (NULL = capturada a mano en la pantalla de Incidencias)
--   MicrosipSincLog      : bitácora de cada sincronización (la muestra la pantalla de Incidencias)
--   SincronizaIncidenciasMicrosip : la corre el job "Nominas - Incidencias Microsip" cada hora
--                          y el botón "Traer de Microsip"
-- Reglas:
--   - Vacaciones: Microsip guarda fecha inicial + días hábiles; la fecha final se calcula con el
--     horario del empleado (se saltan sus días de DESCANSO; sin horario, se salta el domingo).
--   - Incapacidades: días naturales (IMSS): fin = fecha + días - 1.
--   - No duplica: si ya hay una incidencia del mismo concepto capturada a mano que se empalma,
--     no se agrega. Si en Microsip se modifica o se borra, aquí se actualiza o se quita.
-- Ejecutar con sqlcmd -I.
-- =============================================
USE [Nominas];
GO

IF OBJECT_ID('dbo.MicrosipEmpresa') IS NULL
CREATE TABLE dbo.MicrosipEmpresa (
    empresa_id  INT         NOT NULL PRIMARY KEY,
    servidor    SYSNAME     NOT NULL,     -- linked server (ODBC a la base de Microsip)
    activo      BIT         NOT NULL DEFAULT 1
);
GO
MERGE dbo.MicrosipEmpresa AS t
USING (VALUES (1, 'ACABADOS_ESPECIALES'), (2, 'ADMINISTRATIVO'), (3, 'CORTE'), (4, 'DESARROLLO'), (5, 'IBAMENSUAL'),
              (6, 'LAVANDERIA'), (7, 'PLANCHAS'), (8, 'PROCESOS_SECOS'), (9, 'SEGUNDAS'), (10, 'TERMINADO'),
              (11, 'NAZARENO1'), (12, 'NAZARENO2'), (13, 'NAZARENO3'), (14, 'CUENCAME'), (15, 'PILAR'),
              (16, 'DTL3'), (17, 'DTL'), (18, 'DTLNOM2'), (19, 'ADMINISTRATIVO_TBG'), (22, 'SYSEL_NOM'),
              (32, 'TRANSPORTELAGOMA_NOM'), (34, 'DELTA3'), (40, 'DELTA2'), (42, 'DTL_EL_PILAR'), (43, 'DTL_DURANGO')
      ) AS s (empresa_id, servidor)
ON t.empresa_id = s.empresa_id
WHEN NOT MATCHED THEN INSERT (empresa_id, servidor) VALUES (s.empresa_id, s.servidor);
GO

IF COL_LENGTH('dbo.incidencias', 'origenClave') IS NULL
ALTER TABLE dbo.incidencias ADD origenClave VARCHAR(80) NULL;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UX_incidencias_origenClave')
CREATE UNIQUE INDEX UX_incidencias_origenClave ON dbo.incidencias (origenClave) WHERE origenClave IS NOT NULL;
GO

IF OBJECT_ID('dbo.MicrosipSincLog') IS NULL
CREATE TABLE dbo.MicrosipSincLog (
    id            INT IDENTITY(1,1) PRIMARY KEY,
    corrida       UNIQUEIDENTIFIER NOT NULL,
    inicio        DATETIME      NOT NULL DEFAULT GETDATE(),
    fin           DATETIME      NULL,
    empresa_id    INT           NOT NULL,
    servidor      SYSNAME       NOT NULL,
    leidas        INT           NULL,      -- registros leídos de Microsip en el periodo
    nuevas        INT           NULL,
    actualizadas  INT           NULL,
    borradas      INT           NULL,      -- ya no existen en Microsip
    yaCapturadas  INT           NULL,      -- ya estaban capturadas a mano
    sinEmpleado   INT           NULL,      -- el número no existe en dbo.empleados
    error         NVARCHAR(2000) NULL,
    origen        VARCHAR(60)   NOT NULL
);
GO

-- =============================================
-- Fecha final de unas vacaciones de @dias hábiles que empiezan en @inicio,
-- según el horario del empleado (horario id 1 = DESCANSO).
-- =============================================
CREATE OR ALTER FUNCTION dbo.MicrosipFinVacaciones (@numero INT, @inicio DATE, @dias INT)
RETURNS DATE
AS
BEGIN
    IF @dias IS NULL OR @dias <= 0 RETURN @inicio;

    DECLARE @l INT, @m INT, @x INT, @j INT, @v INT, @s INT, @d INT, @hay BIT = 0;
    SELECT TOP 1 @l = he.idhorario_lunes, @m = he.idhorario_martes, @x = he.idhorario_miercoles, @j = he.idhorario_jueves,
                 @v = he.idhorario_viernes, @s = he.idhorario_sabado, @d = he.idhorario_domingo, @hay = 1
    FROM dbo.horarios_empleado he
    WHERE he.empleado_id = @numero AND he.idEstatus = 1
    ORDER BY CASE WHEN he.fechaAsignacion <= @inicio THEN 0 ELSE 1 END, he.fechaAsignacion DESC, he.id DESC;

    DECLARE @f DATE = @inicio, @cuenta INT = 0, @vueltas INT = 0, @dia INT, @h INT;
    WHILE @vueltas < 400
    BEGIN
        SET @dia = DATEDIFF(DAY, '19000101', @f) % 7 + 1;          -- 1 = lunes ... 7 = domingo
        SET @h = CASE @dia WHEN 1 THEN @l WHEN 2 THEN @m WHEN 3 THEN @x WHEN 4 THEN @j
                           WHEN 5 THEN @v WHEN 6 THEN @s ELSE @d END;
        IF (@hay = 1 AND ISNULL(@h, 1) <> 1) OR (@hay = 0 AND @dia <> 7)
        BEGIN
            SET @cuenta += 1;
            IF @cuenta >= @dias RETURN @f;
        END
        SET @f = DATEADD(DAY, 1, @f);
        SET @vueltas += 1;
    END
    RETURN DATEADD(DAY, @dias - 1, @inicio);
END
GO

-- =============================================
-- Author:      Edgar Madrigal
-- Create date: 2026-10-07
-- Description: Trae de Microsip las vacaciones e incapacidades de los últimos
--              @diasAtras días (y las ya programadas a futuro) y las deja en dbo.incidencias.
--   EXEC dbo.SincronizaIncidenciasMicrosip                       -- todas las empresas
--   EXEC dbo.SincronizaIncidenciasMicrosip @empresa_id = 10      -- solo TERMINADO
-- Regresa el resumen por empresa de esta corrida.
-- =============================================
CREATE OR ALTER PROCEDURE dbo.SincronizaIncidenciasMicrosip
    @empresa_id INT = NULL,
    @diasAtras  INT = 60,
    @origen     VARCHAR(60) = 'Programado'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT OFF;

    DECLARE @corrida UNIQUEIDENTIFIER = NEWID();
    DECLARE @hoy DATE = CAST(GETDATE() AS DATE);
    DECLARE @desdeVac DATE = DATEADD(DAY, -@diasAtras, @hoy);
    DECLARE @desdeInc DATE = DATEADD(DAY, -(@diasAtras + 60), @hoy);   -- una incapacidad larga puede empezar antes
    DECLARE @hasta DATE = DATEADD(DAY, 180, @hoy);                       -- Microsip trae fechas absurdas (2077...)
    DECLARE @conVac INT = (SELECT TOP 1 id FROM dbo.conceptos WHERE descripcionCorta = 'VAC' AND descripcion LIKE 'VACACION%' ORDER BY id DESC);
    DECLARE @conInc INT = (SELECT TOP 1 id FROM dbo.conceptos WHERE descripcionCorta = 'INCA' ORDER BY id DESC);

    DECLARE @emp INT, @srv SYSNAME, @log INT, @q NVARCHAR(MAX), @sql NVARCHAR(MAX);
    DECLARE @leidas INT, @nuevas INT, @act INT, @borr INT, @ya INT, @sinEmp INT;

    CREATE TABLE #ms (tipo CHAR(3), idOrigen BIGINT, numero INT, fecha DATE, dias DECIMAL(9,2));
    CREATE TABLE #reg (clave VARCHAR(80) PRIMARY KEY, tipo CHAR(3), numero INT, concepto_id INT, ini DATE, fin DATE, dias INT);

    DECLARE empresas CURSOR LOCAL FAST_FORWARD FOR
        SELECT empresa_id, servidor FROM dbo.MicrosipEmpresa
        WHERE activo = 1 AND (@empresa_id IS NULL OR empresa_id = @empresa_id)
        ORDER BY empresa_id;
    OPEN empresas;
    FETCH NEXT FROM empresas INTO @emp, @srv;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        INSERT INTO dbo.MicrosipSincLog (corrida, empresa_id, servidor, origen) VALUES (@corrida, @emp, @srv, @origen);
        SET @log = SCOPE_IDENTITY();
        TRUNCATE TABLE #ms;
        TRUNCATE TABLE #reg;
        SELECT @nuevas = 0, @act = 0, @borr = 0, @ya = 0, @sinEmp = 0;

        BEGIN TRY
            -- Vacaciones
            SET @q = N'SELECT v.VACACIONES_ID, e.NUMERO, v.FECHA_INICIAL, v.DIAS FROM VACACIONES v '
                   + N'JOIN EMPLEADOS e ON e.EMPLEADO_ID = v.EMPLEADO_ID '
                   + N'WHERE v.FECHA_INICIAL BETWEEN ''' + CONVERT(CHAR(10), @desdeVac, 120) + N''' AND ''' + CONVERT(CHAR(10), @hasta, 120) + N'''';
            SET @sql = N'INSERT INTO #ms (tipo, idOrigen, numero, fecha, dias) SELECT ''VAC'', VACACIONES_ID, TRY_CAST(NUMERO AS INT), FECHA_INICIAL, DIAS FROM OPENQUERY('
                     + QUOTENAME(@srv) + N', ''' + REPLACE(@q, N'''', N'''''') + N''')';
            EXEC (@sql);

            -- Incapacidades
            SET @q = N'SELECT i.INCAPACIDAD_ID, e.NUMERO, i.FECHA, i.DIAS FROM INCAPACIDADES i '
                   + N'JOIN EMPLEADOS e ON e.EMPLEADO_ID = i.EMPLEADO_ID '
                   + N'WHERE i.FECHA BETWEEN ''' + CONVERT(CHAR(10), @desdeInc, 120) + N''' AND ''' + CONVERT(CHAR(10), @hasta, 120) + N'''';
            SET @sql = N'INSERT INTO #ms (tipo, idOrigen, numero, fecha, dias) SELECT ''INC'', INCAPACIDAD_ID, TRY_CAST(NUMERO AS INT), FECHA, DIAS FROM OPENQUERY('
                     + QUOTENAME(@srv) + N', ''' + REPLACE(@q, N'''', N'''''') + N''')';
            EXEC (@sql);

            SET @leidas = (SELECT COUNT(*) FROM #ms);

            -- Solo empleados que existen en el sistema (dbo.empleados.empleado_id = número de Microsip; es texto)
            SELECT @sinEmp = COUNT(*) FROM #ms m
            WHERE m.numero IS NULL OR NOT EXISTS (SELECT 1 FROM dbo.empleados e WHERE e.empleado_id = CAST(m.numero AS VARCHAR(20)));

            INSERT INTO #reg (clave, tipo, numero, concepto_id, ini, fin, dias)
            SELECT LEFT(@srv, 50) + ':' + m.tipo + ':' + CAST(m.idOrigen AS VARCHAR(20)), m.tipo, m.numero,
                   CASE m.tipo WHEN 'VAC' THEN @conVac ELSE @conInc END,
                   m.fecha,
                   CASE m.tipo WHEN 'VAC' THEN dbo.MicrosipFinVacaciones(m.numero, m.fecha, CEILING(m.dias))
                               ELSE DATEADD(DAY, CEILING(m.dias) - 1, m.fecha) END,
                   CEILING(m.dias)
            FROM #ms m
            WHERE m.numero IS NOT NULL AND m.dias > 0
              AND EXISTS (SELECT 1 FROM dbo.empleados e WHERE e.empleado_id = CAST(m.numero AS VARCHAR(20)));

            -- Modificadas en Microsip
            UPDATE i SET fechaInicio = r.ini, fechaFin = r.fin, cantidadDias = r.dias, empleado_id = r.numero, estatus = 1
            FROM dbo.incidencias i JOIN #reg r ON r.clave = i.origenClave
            WHERE i.fechaInicio <> r.ini OR i.fechaFin <> r.fin OR ISNULL(i.cantidadDias, -1) <> r.dias OR i.empleado_id <> r.numero OR i.estatus <> 1;
            SET @act = @@ROWCOUNT;

            -- Borradas en Microsip (solo las traídas de este servidor dentro del periodo consultado)
            DELETE i FROM dbo.incidencias i
            WHERE i.origenClave LIKE LEFT(@srv, 50) + ':%'
              AND NOT EXISTS (SELECT 1 FROM #reg r WHERE r.clave = i.origenClave)
              AND ((i.origenClave LIKE '%:VAC:%' AND i.fechaInicio BETWEEN @desdeVac AND @hasta)
                OR (i.origenClave LIKE '%:INC:%' AND i.fechaInicio BETWEEN @desdeInc AND @hasta));
            SET @borr = @@ROWCOUNT;

            -- Ya capturadas a mano (mismo concepto que se empalma): no se duplican
            DELETE r FROM #reg r
            WHERE NOT EXISTS (SELECT 1 FROM dbo.incidencias i WHERE i.origenClave = r.clave)
              AND EXISTS (SELECT 1 FROM dbo.incidencias i
                          WHERE i.origenClave IS NULL AND i.estatus = 1 AND i.empleado_id = r.numero
                            AND i.concepto_id = r.concepto_id AND i.fechaInicio <= r.fin AND i.fechaFin >= r.ini);
            SET @ya = @@ROWCOUNT;

            -- Nuevas: una por una (el trigger de incidencias no acepta varios renglones a la vez)
            DECLARE @clave VARCHAR(80), @num INT, @con INT, @ini DATE, @fin DATE, @dias INT;
            DECLARE nuevas CURSOR LOCAL FAST_FORWARD FOR
                SELECT r.clave, r.numero, r.concepto_id, r.ini, r.fin, r.dias FROM #reg r
                WHERE NOT EXISTS (SELECT 1 FROM dbo.incidencias i WHERE i.origenClave = r.clave);
            OPEN nuevas;
            FETCH NEXT FROM nuevas INTO @clave, @num, @con, @ini, @fin, @dias;
            WHILE @@FETCH_STATUS = 0
            BEGIN
                -- empresa_id = la empresa del servidor Microsip (antes NULL): evita que una
                -- incidencia de un número que se repite en otra empresa aparezca en la empresa equivocada.
                INSERT INTO dbo.incidencias (empleado_id, concepto_id, fechaInicio, fechaFin, estatus, cantidadDias, empresa_id, origenClave)
                VALUES (@num, @con, @ini, @fin, 1, @dias, @emp, @clave);
                SET @nuevas += 1;
                FETCH NEXT FROM nuevas INTO @clave, @num, @con, @ini, @fin, @dias;
            END
            CLOSE nuevas; DEALLOCATE nuevas;

            UPDATE dbo.MicrosipSincLog
            SET fin = GETDATE(), leidas = @leidas, nuevas = @nuevas, actualizadas = @act, borradas = @borr,
                yaCapturadas = @ya, sinEmpleado = @sinEmp
            WHERE id = @log;
        END TRY
        BEGIN CATCH
            IF CURSOR_STATUS('local', 'nuevas') >= -1 BEGIN CLOSE nuevas; DEALLOCATE nuevas; END
            UPDATE dbo.MicrosipSincLog SET fin = GETDATE(), error = LEFT(ERROR_MESSAGE(), 2000) WHERE id = @log;
        END CATCH

        FETCH NEXT FROM empresas INTO @emp, @srv;
    END
    CLOSE empresas; DEALLOCATE empresas;

    -- La bitácora se queda 90 días
    DELETE FROM dbo.MicrosipSincLog WHERE inicio < DATEADD(DAY, -90, GETDATE());

    SELECT l.empresa_id, emp.nombre AS empresa, l.servidor, l.leidas, l.nuevas, l.actualizadas, l.borradas,
           l.yaCapturadas, l.sinEmpleado, l.error
    FROM dbo.MicrosipSincLog l LEFT JOIN dbo.empresas emp ON emp.id = l.empresa_id
    WHERE l.corrida = @corrida ORDER BY l.empresa_id;
END
GO
