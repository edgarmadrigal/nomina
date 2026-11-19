-- =============================================================================
-- Reporte: Acumulado de conceptos de Microsip por empresa (2026-10-08)
--   Suma IMPORTE y UNIDADES de PAGOS_NOMINA_DET (pagos de nómina ya aplicados) por
--   concepto, en un rango de fechas. @empresa_id NULL = todas las empresas de MicrosipEmpresa,
--   combinadas por CLAVE de concepto. Lee cada empresa por su linked server (OPENQUERY).
--   EXEC dbo.ReporteAcumuladoConceptosMicrosip @empresa_id=8, @fechaInicio='2026-01-01', @fechaFin='2026-12-31'
-- =============================================================================
CREATE OR ALTER PROCEDURE dbo.ReporteAcumuladoConceptosMicrosip
    @empresa_id   INT  = NULL,
    @fechaInicio  DATE = NULL,
    @fechaFin     DATE = NULL
AS
BEGIN
    SET NOCOUNT ON;
    IF @fechaInicio IS NULL SET @fechaInicio = DATEFROMPARTS(YEAR(GETDATE()), 1, 1);
    IF @fechaFin    IS NULL SET @fechaFin    = CAST(GETDATE() AS DATE);

    DECLARE @emp INT, @srv SYSNAME, @sql NVARCHAR(MAX),
            @fi VARCHAR(10) = CONVERT(VARCHAR(10), @fechaInicio, 23),
            @ff VARCHAR(10) = CONVERT(VARCHAR(10), @fechaFin, 23);

    CREATE TABLE #acum (
        empresa_id  INT,
        clave       VARCHAR(20),
        nombre      NVARCHAR(150),
        naturaleza  CHAR(1),
        unidades    DECIMAL(18,2),
        movimientos INT,
        importe     DECIMAL(18,2)
    );

    DECLARE emps CURSOR LOCAL FAST_FORWARD FOR
        SELECT empresa_id, servidor FROM dbo.MicrosipEmpresa
        WHERE activo = 1 AND (@empresa_id IS NULL OR empresa_id = @empresa_id);
    OPEN emps;
    FETCH NEXT FROM emps INTO @emp, @srv;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        BEGIN TRY
            SET @sql = N'INSERT INTO #acum (empresa_id, clave, nombre, naturaleza, unidades, movimientos, importe)
                SELECT ' + CAST(@emp AS VARCHAR(10)) + N', LTRIM(RTRIM(CLAVE)), NOMBRE, NATURALEZA, UNID, MOVS, IMP
                FROM OPENQUERY(' + QUOTENAME(@srv) + N', ''
                    SELECT TRIM(c.CLAVE) AS CLAVE, c.NOMBRE AS NOMBRE, c.NATURALEZA AS NATURALEZA,
                           SUM(d.UNIDADES) AS UNID, COUNT(*) AS MOVS, SUM(d.IMPORTE) AS IMP
                    FROM PAGOS_NOMINA_DET d
                    JOIN CONCEPTOS_NO c ON c.CONCEPTO_NO_ID = d.CONCEPTO_NO_ID
                    WHERE d.FECHA BETWEEN ''''' + @fi + N''''' AND ''''' + @ff + N'''''
                    GROUP BY c.CLAVE, c.NOMBRE, c.NATURALEZA'')';
            EXEC (@sql);
        END TRY
        BEGIN CATCH
            -- una empresa sin conexión no debe tumbar todo el reporte
            PRINT 'No se pudo leer ' + @srv + ': ' + ERROR_MESSAGE();
        END CATCH
        FETCH NEXT FROM emps INTO @emp, @srv;
    END
    CLOSE emps; DEALLOCATE emps;

    -- Combinado por clave (para una sola empresa es su propio detalle; para todas, la suma)
    SELECT
        clave,
        MAX(nombre)                      AS nombre,
        MAX(naturaleza)                  AS naturaleza,
        SUM(unidades)                    AS unidades,
        SUM(movimientos)                 AS movimientos,
        SUM(importe)                     AS importe,
        COUNT(DISTINCT empresa_id)       AS empresas
    FROM #acum
    GROUP BY clave
    ORDER BY CASE WHEN MAX(naturaleza) = 'P' THEN 0 WHEN MAX(naturaleza) = 'R' THEN 1 ELSE 2 END, SUM(importe) DESC;
END;
GO

-- Menú Reportes > Acumulado Conceptos Microsip (lo ven los administradores; a otros se les da en Usuarios > Permisos)
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'reporteConceptosMicrosip')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Reportes', N'Acumulado Conceptos Microsip', 'reporteConceptosMicrosip', '<i class="fa fa-calculator"></i>', 500, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'reporteConceptosMicrosip' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
