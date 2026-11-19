-- =============================================
-- Bitácora de cambios (altas, modificaciones y eliminaciones) de los catálogos.
--
--   dbo.Bitacora              un renglón por registro que cambió: quién, cuándo, desde dónde,
--                             valores de antes y de después (JSON).
--   dbo.fnBitacoraQuien()     quién hizo el cambio: el usuario del sistema web (SESSION_CONTEXT
--                             que pone application/hooks/BitacoraContexto.php en cada petición),
--                             el job de SQL Agent, o el login si se cambió directo en SQL.
--   dbo.BitacoraCrearTrigger  crea/actualiza el trigger trg_Bitacora_<tabla> de una tabla.
--
-- Reglas del trigger:
--   * ALTA = INSERT, ELIMINACION = DELETE o baja lógica (estatus/idEstatus/status/activo pasa
--     de 1/A a 0/B/I), MODIFICACION = UPDATE en el que de verdad cambió algún valor.
--   * La contraseña de usuarios no se guarda: solo una huella (para ver que cambió).
--   * Si un solo cambio toca más de 1000 registros se guarda un renglón de resumen.
--   * @soloWeb = 1 (checadas): solo cambios hechos desde el sistema web; lo que bajan
--     los relojes y los procesos automáticos no se registra (son miles por hora).
-- Pantalla: Catálogos > Historial de cambios (controllers/Bitacora.php).
-- =============================================
USE [Nominas];
GO

IF OBJECT_ID('dbo.Bitacora') IS NULL
CREATE TABLE dbo.Bitacora (
    id          BIGINT IDENTITY(1,1) PRIMARY KEY,
    fecha       DATETIME       NOT NULL CONSTRAINT DF_Bitacora_fecha DEFAULT GETDATE(),
    tabla       SYSNAME        NOT NULL,
    accion      VARCHAR(12)    NOT NULL,      -- ALTA / MODIFICACION / ELIMINACION
    llave       NVARCHAR(200)  NULL,          -- id del registro (o "N registros (cambio masivo)")
    idUsuario   INT            NULL,
    usuario     NVARCHAR(150)  NOT NULL,
    ip          VARCHAR(45)    NULL,
    pantalla    NVARCHAR(200)  NULL,
    origen      NVARCHAR(300)  NULL,          -- programa | equipo | login de SQL
    antes       NVARCHAR(MAX)  NULL,
    despues     NVARCHAR(MAX)  NULL
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Bitacora_fecha')
    CREATE INDEX IX_Bitacora_fecha ON dbo.Bitacora (fecha DESC) INCLUDE (tabla, accion, usuario);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Bitacora_tabla_llave')
    CREATE INDEX IX_Bitacora_tabla_llave ON dbo.Bitacora (tabla, llave, fecha DESC);
GO

CREATE OR ALTER FUNCTION dbo.fnBitacoraQuien()
RETURNS NVARCHAR(150)
AS
BEGIN
    DECLARE @u NVARCHAR(150) = CAST(SESSION_CONTEXT(N'usuario') AS NVARCHAR(150));
    IF @u IS NOT NULL RETURN @u;
    DECLARE @app NVARCHAR(128) = APP_NAME();
    IF @app LIKE N'SQLAgent - TSQL JobStep (Job 0x%'
    BEGIN
        DECLARE @job NVARCHAR(128) = (SELECT TOP 1 j.name FROM msdb.dbo.sysjobs j
                                      WHERE j.job_id = TRY_CONVERT(UNIQUEIDENTIFIER, TRY_CONVERT(BINARY(16), SUBSTRING(@app, 30, 34), 1)));
        RETURN N'Proceso automático: ' + ISNULL(@job, N'SQL Agent');
    END
    IF @app LIKE N'SQLAgent%' RETURN N'Proceso automático: SQL Agent';
    RETURN N'Directo en SQL: ' + ORIGINAL_LOGIN();
END
GO

CREATE OR ALTER PROCEDURE dbo.BitacoraCrearTrigger
    @tabla   SYSNAME,
    @soloWeb BIT = 0
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @obj INT = OBJECT_ID(N'dbo.' + QUOTENAME(@tabla));
    IF @obj IS NULL BEGIN RAISERROR('No existe la tabla dbo.%s', 16, 1, @tabla); RETURN; END

    DECLARE @c TABLE (name SYSNAME, column_id INT);
    INSERT INTO @c
    SELECT c.name, c.column_id FROM sys.columns c JOIN sys.types t ON t.user_type_id = c.user_type_id
    WHERE c.object_id = @obj AND c.is_computed = 0
      AND t.name NOT IN ('text','ntext','image','xml','varbinary','binary','timestamp','geography','geometry','hierarchyid','sql_variant');

    -- columnas para el JSON (la contraseña solo como huella) y para comparar
    DECLARE @jsonI NVARCHAR(MAX) = STUFF((
        SELECT N', ' + CASE WHEN LOWER(name) = 'password'
                            THEN N'CONVERT(VARCHAR(12), HASHBYTES(''SHA2_256'', CAST(i.' + QUOTENAME(name) + N' AS NVARCHAR(4000))), 2) AS ' + QUOTENAME(name)
                            ELSE N'i.' + QUOTENAME(name) END
        FROM @c ORDER BY column_id FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, N'');
    DECLARE @cmpI NVARCHAR(MAX) = STUFF((SELECT N', i.' + QUOTENAME(name) FROM @c ORDER BY column_id FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, N'');
    DECLARE @jsonD NVARCHAR(MAX) = REPLACE(@jsonI, N'i.[', N'd.['), @cmpD NVARCHAR(MAX) = REPLACE(@cmpI, N'i.[', N'd.[');

    -- llave: columnas de la llave primaria (o id, o la primera columna)
    DECLARE @k TABLE (name SYSNAME, orden INT);
    INSERT INTO @k SELECT c.name, ic.key_ordinal FROM sys.indexes i
        JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id
        JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
        WHERE i.object_id = @obj AND i.is_primary_key = 1;
    IF NOT EXISTS (SELECT 1 FROM @k)
        INSERT INTO @k SELECT TOP 1 name, 1 FROM @c ORDER BY CASE WHEN LOWER(name) = 'id' THEN 0 ELSE 1 END, column_id;
    DECLARE @llaveI NVARCHAR(MAX) = STUFF((SELECT N' + N''|'' + ISNULL(CAST(i.' + QUOTENAME(name) + N' AS NVARCHAR(60)), N'''')' FROM @k ORDER BY orden FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 10, N'');
    DECLARE @llaveD NVARCHAR(MAX) = REPLACE(@llaveI, N'i.[', N'd.[');
    DECLARE @join NVARCHAR(MAX) = STUFF((SELECT N' AND i.' + QUOTENAME(name) + N' = d.' + QUOTENAME(name) FROM @k ORDER BY orden FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 5, N'');

    -- baja lógica
    DECLARE @colBaja SYSNAME = (SELECT TOP 1 name FROM @c WHERE LOWER(name) IN ('idestatus','estatus','status','activo')
                                ORDER BY CASE LOWER(name) WHEN 'idestatus' THEN 1 WHEN 'estatus' THEN 2 WHEN 'status' THEN 3 ELSE 4 END);
    DECLARE @baja NVARCHAR(MAX) = CASE WHEN @colBaja IS NULL THEN N'1 = 0' ELSE
        N'CAST(d.' + QUOTENAME(@colBaja) + N' AS VARCHAR(10)) IN (''1'', ''A'') AND ISNULL(CAST(i.' + QUOTENAME(@colBaja) + N' AS VARCHAR(10)), ''0'') IN (''0'', ''B'', ''I'')' END;

    DECLARE @trg SYSNAME = N'trg_Bitacora_' + @tabla;
    DECLARE @sql NVARCHAR(MAX) = N'
CREATE OR ALTER TRIGGER dbo.' + QUOTENAME(@trg) + N' ON dbo.' + QUOTENAME(@tabla) + N'
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    -- Generado por dbo.BitacoraCrearTrigger (application/sql/bitacora.sql). No editar a mano.
    SET NOCOUNT ON;
    IF NOT EXISTS (SELECT 1 FROM inserted) AND NOT EXISTS (SELECT 1 FROM deleted) RETURN;
    ' + CASE WHEN @soloWeb = 1 THEN N'IF SESSION_CONTEXT(N''usuario'') IS NULL RETURN;   -- solo cambios desde el sistema web' ELSE N'' END + N'
    DECLARE @b TABLE (accion VARCHAR(12), llave NVARCHAR(200), antes NVARCHAR(MAX), despues NVARCHAR(MAX));
    IF NOT EXISTS (SELECT 1 FROM deleted)
        INSERT INTO @b SELECT ''ALTA'', ' + @llaveI + N', NULL,
               (SELECT ' + @jsonI + N' FOR JSON PATH, WITHOUT_ARRAY_WRAPPER, INCLUDE_NULL_VALUES)
        FROM inserted i;
    ELSE IF NOT EXISTS (SELECT 1 FROM inserted)
        INSERT INTO @b SELECT ''ELIMINACION'', ' + @llaveD + N',
               (SELECT ' + @jsonD + N' FOR JSON PATH, WITHOUT_ARRAY_WRAPPER, INCLUDE_NULL_VALUES), NULL
        FROM deleted d;
    ELSE
        INSERT INTO @b SELECT CASE WHEN ' + @baja + N' THEN ''ELIMINACION'' ELSE ''MODIFICACION'' END, ' + @llaveI + N',
               (SELECT ' + @jsonD + N' FOR JSON PATH, WITHOUT_ARRAY_WRAPPER, INCLUDE_NULL_VALUES),
               (SELECT ' + @jsonI + N' FOR JSON PATH, WITHOUT_ARRAY_WRAPPER, INCLUDE_NULL_VALUES)
        FROM inserted i JOIN deleted d ON ' + @join + N'
        WHERE EXISTS (SELECT ' + @cmpI + N' EXCEPT SELECT ' + @cmpD + N');

    DECLARE @n INT = (SELECT COUNT(*) FROM @b);
    IF @n = 0 RETURN;
    DECLARE @quien NVARCHAR(150) = dbo.fnBitacoraQuien(),
            @idUsuario INT = TRY_CAST(CAST(SESSION_CONTEXT(N''idUsuario'') AS NVARCHAR(20)) AS INT),
            @ip VARCHAR(45) = CAST(SESSION_CONTEXT(N''ip'') AS VARCHAR(45)),
            @pantalla NVARCHAR(200) = CAST(SESSION_CONTEXT(N''pantalla'') AS NVARCHAR(200)),
            @origen NVARCHAR(300) = LEFT(ISNULL(APP_NAME(), N'''') + N'' | '' + ISNULL(HOST_NAME(), N'''') + N'' | '' + ORIGINAL_LOGIN(), 300);
    IF @n <= 1000
        INSERT INTO dbo.Bitacora (tabla, accion, llave, idUsuario, usuario, ip, pantalla, origen, antes, despues)
        SELECT N''' + @tabla + N''', accion, llave, @idUsuario, @quien, @ip, @pantalla, @origen, antes, despues FROM @b;
    ELSE
        INSERT INTO dbo.Bitacora (tabla, accion, llave, idUsuario, usuario, ip, pantalla, origen)
        SELECT N''' + @tabla + N''', accion, CAST(COUNT(*) AS NVARCHAR(20)) + N'' registros (cambio masivo)'', @idUsuario, @quien, @ip, @pantalla, @origen
        FROM @b GROUP BY accion;
END';
    EXEC sys.sp_executesql @sql;
    PRINT N'Bitácora activa en dbo.' + @tabla;
END
GO

-- Catálogos que se registran
EXEC dbo.BitacoraCrearTrigger 'empleados';
EXEC dbo.BitacoraCrearTrigger 'usuarios';
EXEC dbo.BitacoraCrearTrigger 'perfiles';
EXEC dbo.BitacoraCrearTrigger 'UsuarioPermiso';
EXEC dbo.BitacoraCrearTrigger 'UsuarioEmpresa';
EXEC dbo.BitacoraCrearTrigger 'empresas';
EXEC dbo.BitacoraCrearTrigger 'conceptos';
EXEC dbo.BitacoraCrearTrigger 'clasificacion';
EXEC dbo.BitacoraCrearTrigger 'horarios';
EXEC dbo.BitacoraCrearTrigger 'horarios_empleado';
EXEC dbo.BitacoraCrearTrigger 'incidencias';
EXEC dbo.BitacoraCrearTrigger 'DiasFestivos';
EXEC dbo.BitacoraCrearTrigger 'RelojDispositivo';
EXEC dbo.BitacoraCrearTrigger 'MenuOpcion';
EXEC dbo.BitacoraCrearTrigger 'RespaldoConfig';
EXEC dbo.BitacoraCrearTrigger 'RespaldoDestino';
EXEC dbo.BitacoraCrearTrigger 'checadas', @soloWeb = 1;
GO

-- Opción del menú (Catálogos) y permiso para los administradores
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'bitacora')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Catalogos', N'Historial de cambios', 'bitacora', '<i class="fa fa-history"></i>', 297, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'bitacora' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
