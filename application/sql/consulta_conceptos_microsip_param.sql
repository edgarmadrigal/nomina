-- =============================================================================
-- ConsultaConceptosMICROSIP parametrizado por año (2026-10-08)
--   Antes tenía el año 2020 fijo en los 19 bloques (uno por empresa). Ahora recibe @anio
--   (default: el año actual) y arma cada OPENQUERY con ese año. Devuelve un result set por
--   empresa (matriz: empleados en filas, conceptos en columnas), igual que antes.
--   EXEC [dbo].[ConsultaConceptosMICROSIP] @anio = 2026
--   Nota: QUOTENAME regresa NULL con textos > 128 caracteres, por eso el query se escapa con REPLACE.
-- =============================================================================
ALTER PROCEDURE [dbo].[ConsultaConceptosMICROSIP]
    @anio INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    IF @anio IS NULL SET @anio = YEAR(GETDATE());
    DECLARE @fi VARCHAR(10) = '01.01.' + CAST(@anio AS VARCHAR(4));
    DECLARE @ff VARCHAR(10) = '31.12.' + CAST(@anio AS VARCHAR(4));

    IF OBJECT_ID('tempdb..#C') IS NOT NULL DROP TABLE #C;
    CREATE TABLE #C (
        NUMERO VARCHAR(20), APELLIDO_PATERNO NVARCHAR(100), APELLIDO_MATERNO NVARCHAR(100), NOMBRES NVARCHAR(100),
        RFC VARCHAR(20), CURP VARCHAR(25), REG_IMSS VARCHAR(20), REG_PATRONAL VARCHAR(40), gpo NVARCHAR(50),
        empresa NVARCHAR(50), IMPORTE DECIMAL(18,2), FRECUENCIA_NOMBRE NVARCHAR(60), NOMBRECONCEPTO NVARCHAR(150)
    );

    DECLARE @srv SYSNAME, @lbl NVARCHAR(50), @q NVARCHAR(MAX), @sql NVARCHAR(MAX), @cols NVARCHAR(MAX), @query NVARCHAR(MAX);
    -- Las mismas 19 empresas (servidor vinculado) que tenía el SP original, en el mismo orden
    DECLARE cur CURSOR LOCAL FAST_FORWARD FOR
        SELECT srv, lbl FROM (VALUES
            ('DTL','DTL'),('DTLNOM','DTLNOM'),('DTLNOM_MENS','DTLNOM_MENS'),('DTLNOM2','DTLNOM2'),('DTL3','DTL3'),
            ('CUENCAME','CUENCAME'),('NAZARENO1','NAZARENO1'),('NAZARENO2','NAZARENO2'),('NAZARENO3','NAZARENO3'),
            ('TERMINADO','TERMINADO'),('ACABADOS_ESPECIALES','ACABADOS_ESPECIALES'),('ADMINISTRATIVO','ADMINISTRATIVO'),
            ('CORTE','CORTE'),('DESARROLLO','DESARROLLO'),('LAVANDERIA','LAVANDERIA'),('PILAR','PILAR'),
            ('PLANCHAS','PLANCHAS'),('PROCESOS_SECOS','PROCESOS_SECOS'),('SEGUNDAS','SEGUNDAS')
        ) v(srv, lbl);
    OPEN cur;
    FETCH NEXT FROM cur INTO @srv, @lbl;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        TRUNCATE TABLE #C;
        BEGIN TRY
            SET @q = 'SELECT e.NUMERO, e.APELLIDO_PATERNO, e.APELLIDO_MATERNO, e.NOMBRES, e.RFC, e.CURP, e.REG_IMSS,
                REG.NUM_REG_PATRONAL AS REG_PATRONAL, (SELECT OTRO_REG2 FROM DATOS_EMPRESA) as gpo, ''' + @lbl + ''' as empresa,
                pd.IMPORTE, f.NOMBRE FRECUENCIA_NOMBRE, c.NOMBRE AS NOMBRECONCEPTO
                FROM PAGOS_NOMINA pn
                  INNER JOIN EMPLEADOS e ON (e.EMPLEADO_ID = pn.EMPLEADO_ID)
                  LEFT OUTER JOIN DEPTOS_NO d ON (d.DEPTO_NO_ID = e.DEPTO_NO_ID)
                  LEFT OUTER JOIN PUESTOS_NO p ON (p.PUESTO_NO_ID = e.PUESTO_NO_ID)
                  INNER JOIN FRECUENCIAS_PAGO f ON (f.FREPAG_ID = e.FREPAG_ID)
                  INNER JOIN NOMINAS n ON (n.NOMINA_ID = pn.NOMINA_ID)
                  INNER JOIN PAGOS_NOMINA_DET pd ON (pn.PAGO_NOMINA_ID = pd.PAGO_NOMINA_ID)
                  INNER JOIN CONCEPTOS_NO c ON (c.CONCEPTO_NO_ID = pd.CONCEPTO_NO_ID)
                  LEFT JOIN REG_PATRONALES REG ON (REG.REG_PATRONAL_ID = e.REG_PATRONAL_ID)
                WHERE n.FECHA between ''' + @fi + ''' and ''' + @ff + '''';
            SET @sql = 'INSERT INTO #C SELECT * FROM OPENQUERY(' + QUOTENAME(@srv) + ', ''' + REPLACE(@q, '''', '''''') + ''')';
            EXEC (@sql);
        END TRY
        BEGIN CATCH
            PRINT 'No se pudo leer ' + @srv + ': ' + ERROR_MESSAGE();
        END CATCH

        -- Un result set por empresa (pivote: concepto -> columna), como el SP original
        SET @cols = STUFF((SELECT DISTINCT ',' + QUOTENAME(NOMBRECONCEPTO) FROM #C FOR XML PATH(''), TYPE).value('.', 'nvarchar(max)'), 1, 1, '');
        IF @cols IS NOT NULL
        BEGIN
            SET @query = 'SELECT [NUMERO] AS Numero, APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRES, RFC, CURP, REG_IMSS, REG_PATRONAL, gpo, empresa, ' + @cols + '
                FROM (SELECT NUMERO, APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRES, RFC, CURP, REG_IMSS, REG_PATRONAL, gpo, empresa, IMPORTE, NOMBRECONCEPTO FROM #C) x
                PIVOT (SUM(IMPORTE) FOR NOMBRECONCEPTO IN (' + @cols + ')) p';
            EXEC (@query);
        END
        FETCH NEXT FROM cur INTO @srv, @lbl;
    END
    CLOSE cur; DEALLOCATE cur;
END;
GO
