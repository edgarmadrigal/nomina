-- =============================================================================
-- Conceptos de Microsip (2026-10-08)
--   MicrosipConcepto        : catálogo de conceptos de nómina (CONCEPTOS_NO) de cada empresa,
--                             copiado cada hora por el job "Nominas - Conceptos Microsip"
--                             o con el botón "Actualizar ahora" de Catálogos > Conceptos Microsip.
--   MicrosipConceptoSinc    : bitácora de cada sincronización (última actualización en pantalla).
--   TxtConceptoClave        : qué clave de Microsip lleva cada renglón |1.1| del TXT de asistencia
--                             (empresa_id NULL = valor general; con empresa_id = solo esa empresa).
--                             Antes estaban fijas en ConsultaAsistenciaExportarTablaTemporal.
--   SincronizaConceptosMicrosip / fnTxtClave
-- =============================================================================
IF OBJECT_ID('dbo.MicrosipConcepto') IS NULL
CREATE TABLE dbo.MicrosipConcepto (
    empresa_id      INT            NOT NULL,
    conceptoNoId    INT            NOT NULL,      -- CONCEPTOS_NO.CONCEPTO_NO_ID
    clave           VARCHAR(20)    NOT NULL,
    nombre          NVARCHAR(150)  NULL,
    nombreAbrev     NVARCHAR(100)  NULL,
    naturaleza      CHAR(1)        NULL,          -- P percepción, R retención (deducción)
    tipo            CHAR(1)        NULL,
    oculto          CHAR(1)        NULL,
    activo          BIT            NOT NULL CONSTRAINT DF_MicrosipConcepto_activo DEFAULT 1,  -- 0 = ya no existe en Microsip
    fechaAlta       DATETIME       NOT NULL CONSTRAINT DF_MicrosipConcepto_alta DEFAULT GETDATE(),
    fechaActualiza  DATETIME       NOT NULL CONSTRAINT DF_MicrosipConcepto_act DEFAULT GETDATE(),
    CONSTRAINT PK_MicrosipConcepto PRIMARY KEY (empresa_id, conceptoNoId)
);
GO
IF OBJECT_ID('dbo.MicrosipConceptoSinc') IS NULL
CREATE TABLE dbo.MicrosipConceptoSinc (
    id          INT IDENTITY PRIMARY KEY,
    corrida     UNIQUEIDENTIFIER NOT NULL,
    fecha       DATETIME NOT NULL DEFAULT GETDATE(),
    empresa_id  INT NOT NULL,
    servidor    SYSNAME NOT NULL,
    origen      VARCHAR(20) NOT NULL,            -- job / boton
    leidos      INT NULL,
    nuevos      INT NULL,
    cambiados   INT NULL,
    bajas       INT NULL,
    error       NVARCHAR(2000) NULL
);
GO
IF OBJECT_ID('dbo.TxtConceptoClave') IS NULL
BEGIN
    CREATE TABLE dbo.TxtConceptoClave (
        id          INT IDENTITY PRIMARY KEY,
        concepto    VARCHAR(30)  NOT NULL,      -- PUNTUALIDAD, ASISTENCIA, DESCANSO_TRABAJADO, PRIMA_DOMINICAL, DIA_FESTIVO
        empresa_id  INT          NULL,          -- NULL = todas las empresas sin valor propio
        clave       VARCHAR(20)  NOT NULL,
        CONSTRAINT UQ_TxtConceptoClave UNIQUE (concepto, empresa_id)
    );
    -- los valores que estaban fijos en el SP del TXT
    INSERT INTO dbo.TxtConceptoClave (concepto, empresa_id, clave) VALUES
        ('PUNTUALIDAD', NULL, '061'), ('ASISTENCIA', NULL, '062'), ('DESCANSO_TRABAJADO', NULL, '078'),
        ('PRIMA_DOMINICAL', NULL, '113'), ('DIA_FESTIVO', NULL, '073');
    -- Torreón (empresas.esTorreon = 1) usaba 008 / 009 / 090
    INSERT INTO dbo.TxtConceptoClave (concepto, empresa_id, clave)
    SELECT v.concepto, e.id, v.clave
    FROM dbo.empresas AS e
    CROSS JOIN (VALUES ('PUNTUALIDAD', '008'), ('ASISTENCIA', '009'), ('PRIMA_DOMINICAL', '090')) AS v (concepto, clave)
    WHERE e.esTorreon = 1;
END
GO
-- Clave de Microsip para un renglón del TXT: la de la empresa o, si no tiene, la general
CREATE OR ALTER FUNCTION dbo.fnTxtClave (@concepto VARCHAR(30), @empresa_id INT)
RETURNS VARCHAR(20)
AS
BEGIN
    RETURN (SELECT TOP 1 LTRIM(RTRIM(clave)) FROM dbo.TxtConceptoClave
            WHERE concepto = @concepto AND (empresa_id = @empresa_id OR empresa_id IS NULL)
            ORDER BY CASE WHEN empresa_id IS NULL THEN 1 ELSE 0 END);
END;
GO
CREATE OR ALTER PROCEDURE dbo.SincronizaConceptosMicrosip
    @empresa_id INT = NULL,               -- NULL = todas las empresas activas de MicrosipEmpresa
    @origen     VARCHAR(20) = 'job'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @corrida UNIQUEIDENTIFIER = NEWID(), @emp INT, @srv SYSNAME, @sql NVARCHAR(MAX),
            @leidos INT, @nuevos INT, @cambiados INT, @bajas INT;
    CREATE TABLE #c (conceptoNoId INT PRIMARY KEY, clave VARCHAR(20), nombre NVARCHAR(150), nombreAbrev NVARCHAR(100),
                     naturaleza CHAR(1), tipo CHAR(1), oculto CHAR(1));

    DECLARE emps CURSOR LOCAL FAST_FORWARD FOR
        SELECT empresa_id, servidor FROM dbo.MicrosipEmpresa
        WHERE activo = 1 AND (@empresa_id IS NULL OR empresa_id = @empresa_id);
    OPEN emps;
    FETCH NEXT FROM emps INTO @emp, @srv;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        TRUNCATE TABLE #c;
        SELECT @leidos = NULL, @nuevos = 0, @cambiados = 0, @bajas = 0;
        BEGIN TRY
            SET @sql = N'INSERT INTO #c (conceptoNoId, clave, nombre, nombreAbrev, naturaleza, tipo, oculto)
                SELECT CONCEPTO_NO_ID, LTRIM(RTRIM(CLAVE)), NOMBRE, NOMBRE_ABREV, NATURALEZA, TIPO, OCULTO
                FROM OPENQUERY(' + QUOTENAME(@srv) + N', ''SELECT CONCEPTO_NO_ID, CLAVE, NOMBRE, NOMBRE_ABREV, NATURALEZA, TIPO, OCULTO FROM CONCEPTOS_NO'')';
            EXEC (@sql);
            SET @leidos = (SELECT COUNT(*) FROM #c);

            UPDATE m SET m.clave = c.clave, m.nombre = c.nombre, m.nombreAbrev = c.nombreAbrev, m.naturaleza = c.naturaleza,
                         m.tipo = c.tipo, m.oculto = c.oculto, m.activo = 1, m.fechaActualiza = GETDATE()
            FROM dbo.MicrosipConcepto AS m JOIN #c AS c ON c.conceptoNoId = m.conceptoNoId
            WHERE m.empresa_id = @emp
              AND (m.activo = 0 OR m.clave <> c.clave OR ISNULL(m.nombre, N'') <> ISNULL(c.nombre, N'')
                   OR ISNULL(m.nombreAbrev, N'') <> ISNULL(c.nombreAbrev, N'') OR ISNULL(m.naturaleza, '') <> ISNULL(c.naturaleza, '')
                   OR ISNULL(m.tipo, '') <> ISNULL(c.tipo, '') OR ISNULL(m.oculto, '') <> ISNULL(c.oculto, ''));
            SET @cambiados = @@ROWCOUNT;

            INSERT INTO dbo.MicrosipConcepto (empresa_id, conceptoNoId, clave, nombre, nombreAbrev, naturaleza, tipo, oculto)
            SELECT @emp, c.conceptoNoId, c.clave, c.nombre, c.nombreAbrev, c.naturaleza, c.tipo, c.oculto
            FROM #c AS c
            WHERE NOT EXISTS (SELECT 1 FROM dbo.MicrosipConcepto AS m WHERE m.empresa_id = @emp AND m.conceptoNoId = c.conceptoNoId);
            SET @nuevos = @@ROWCOUNT;

            -- solo si Microsip contestó algo: un error de lectura no debe dar de baja todo el catálogo
            IF @leidos > 0
            BEGIN
                UPDATE m SET m.activo = 0, m.fechaActualiza = GETDATE()
                FROM dbo.MicrosipConcepto AS m
                WHERE m.empresa_id = @emp AND m.activo = 1
                  AND NOT EXISTS (SELECT 1 FROM #c AS c WHERE c.conceptoNoId = m.conceptoNoId);
                SET @bajas = @@ROWCOUNT;
            END

            INSERT INTO dbo.MicrosipConceptoSinc (corrida, empresa_id, servidor, origen, leidos, nuevos, cambiados, bajas)
            VALUES (@corrida, @emp, @srv, @origen, @leidos, @nuevos, @cambiados, @bajas);
        END TRY
        BEGIN CATCH
            INSERT INTO dbo.MicrosipConceptoSinc (corrida, empresa_id, servidor, origen, leidos, error)
            VALUES (@corrida, @emp, @srv, @origen, @leidos, LEFT(ERROR_MESSAGE(), 2000));
        END CATCH
        FETCH NEXT FROM emps INTO @emp, @srv;
    END
    CLOSE emps; DEALLOCATE emps;

    SELECT s.empresa_id, s.servidor, s.leidos, s.nuevos, s.cambiados, s.bajas, s.error
    FROM dbo.MicrosipConceptoSinc AS s WHERE s.corrida = @corrida ORDER BY s.empresa_id;
END;
GO
-- Menú Catálogos > Conceptos Microsip (lo ven los administradores; a otros se les da en Usuarios > Permisos)
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'ConceptosMicrosip')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Catalogos', N'Conceptos Microsip', 'ConceptosMicrosip', '<i class="fa fa-list-alt"></i>', 272, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'ConceptosMicrosip' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
-- Empleados: la pantalla muestra la última actualización (job "Nominas - Empleados Microsip" o botón)
IF OBJECT_ID('dbo.EmpleadosMicrosipSinc') IS NULL
CREATE TABLE dbo.EmpleadosMicrosipSinc (
    id      INT IDENTITY PRIMARY KEY,
    fecha   DATETIME NOT NULL DEFAULT GETDATE(),
    origen  VARCHAR(20) NOT NULL,      -- boton
    usuario VARCHAR(50) NULL,
    total   INT NULL
);
GO
