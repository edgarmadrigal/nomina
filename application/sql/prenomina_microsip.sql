-- =============================================================================
-- PRENÓMINA — Conceptos traídos de Microsip  2026-10-09
--
-- La prima vacacional, el fondo de ahorro, la prima dominical, el festivo y la
-- defunción NO se capturan a mano: se traen de Microsip (Firebird, vía el
-- servidor vinculado de la empresa, igual que dbo.ConsultaConceptosMICROSIP).
--
-- Microsip guarda el detalle de cada concepto en PAGOS_NOMINA_DET, por nómina
-- (NOMINAS). Cada nómina semanal tiene FECHA_INICIAL..FECHA_FINAL = el rango
-- lunes..domingo de la semana (coincide EXACTO con el NoSemana del sistema).
--
-- OJO (timing): la prenómina se hace ANTES de correr la nómina en Microsip, así
-- que la semana en curso normalmente TODAVÍA no está en Microsip -> no se trae
-- nada (los conceptos quedan en 0) hasta que esa nómina se corra. Para semanas
-- ya cerradas sí trae los montos reales.
--
-- Qué clave de Microsip alimenta cada concepto se define por empresa en
-- dbo.PrenominaConceptoMicrosip (extensible: un campo puede sumar varias claves,
-- y cada planta puede tener claves distintas).
-- =============================================================================
USE [Nominas];
GO

-- --------------------------------------------------------------------------
-- Mapeo: qué clave(s) de Microsip alimenta cada concepto de la prenómina,
-- por empresa. campo ∈ {primaVacacional, fondoAhorro, primaDominical, festivo,
-- defuncion} (las columnas de PrenominaCaptura que se llenan desde Microsip).
-- --------------------------------------------------------------------------
IF OBJECT_ID('dbo.PrenominaConceptoMicrosip') IS NULL
CREATE TABLE dbo.PrenominaConceptoMicrosip (
    empresa_id    INT          NOT NULL,
    campo         VARCHAR(30)  NOT NULL,   -- columna de PrenominaCaptura
    claveMicrosip VARCHAR(20)  NOT NULL,   -- CONCEPTOS_NO.CLAVE en Microsip
    activo        BIT          NOT NULL CONSTRAINT DF_PreCM_activo DEFAULT 1,
    CONSTRAINT PK_PrenominaConceptoMicrosip PRIMARY KEY (empresa_id, campo, claveMicrosip)
);
GO

-- Semilla para empresa 8 (PROCESOS_SECOS = SB1). Claves verificadas en el
-- catálogo CONCEPTOS_NO de esa empresa (dbo.MicrosipConcepto):
--   1pv = Prima vacacional, 142 = Fondo de ahorro, 090 = Prima Dominical,
--   073 = Día festivo, 120 = Apoyo por fallecimiento (defunción).
--   469 = FONDO DE AHORRO EMP (el del empleado, confirmado por el usuario 2026-10-09).
MERGE dbo.PrenominaConceptoMicrosip AS t
USING (VALUES
    (8, 'primaVacacional', '1pv'),
    (8, 'fondoAhorro',     '469'),
    (8, 'primaDominical',  '090'),
    (8, 'festivo',         '073'),
    (8, 'defuncion',       '120')
) s(empresa_id, campo, claveMicrosip)
ON t.empresa_id = s.empresa_id AND t.campo = s.campo AND t.claveMicrosip = s.claveMicrosip
WHEN NOT MATCHED THEN INSERT (empresa_id, campo, claveMicrosip) VALUES (s.empresa_id, s.campo, s.claveMicrosip);
-- Deja solo el 469 como fondo de ahorro (quita 141/142 de corridas anteriores)
DELETE FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id = 8 AND campo = 'fondoAhorro' AND claveMicrosip IN ('141','142');
GO

-- --------------------------------------------------------------------------
-- Trae de Microsip los 5 conceptos de la planta/semana y los guarda en
-- PrenominaCaptura. Devuelve un entero 'actualizados':
--   -1 = la planta no tiene Microsip mapeado (no se hace nada)
--    0 = la nómina de esa semana aún no está en Microsip (pendiente)
--   >0 = número de empleados actualizados
-- --------------------------------------------------------------------------
-- Config: cómo se arma el "pago sistema" que se trae de Microsip.
--   pagoSistemaNeto = 1 -> neto (TOTAL_PERCEP - TOTAL_RETEN, lo que se deposita)
--   pagoSistemaNeto = 0 -> bruto (solo TOTAL_PERCEP, percepciones). Por planta.
IF COL_LENGTH('dbo.PrenominaConfig', 'pagoSistemaNeto') IS NULL
    ALTER TABLE dbo.PrenominaConfig ADD pagoSistemaNeto BIT NOT NULL CONSTRAINT DF_PreCfg_psneto DEFAULT 1;
GO

CREATE OR ALTER PROCEDURE dbo.PrenominaTraeMicrosip
    @planta   INT,
    @NoSemana INT,
    @anio     INT,
    @forzar   BIT = 0   -- 0 = solo llena lo vacío (respeta lo editado); 1 = sobreescribe con Microsip
AS
BEGIN
    SET NOCOUNT ON;
    SET DATEFIRST 1;

    DECLARE @srv SYSNAME = (SELECT TOP 1 servidor FROM dbo.MicrosipEmpresa WHERE empresa_id = @planta AND activo = 1);
    IF @srv IS NULL BEGIN SELECT -1 AS actualizados; RETURN; END;
    DECLARE @neto BIT = ISNULL((SELECT pagoSistemaNeto FROM dbo.PrenominaConfig WHERE empresa_id = @planta), 1);

    -- Rango lunes..domingo de la semana (misma fórmula que ActualizaChecadasBiostarDias)
    DECLARE @anioTxt VARCHAR(4) = CAST(@anio AS VARCHAR(4));
    DECLARE @ini DATE = DATEADD(wk, DATEDIFF(wk, 6, '1/1/' + @anioTxt) + (@NoSemana - 1), 7);
    DECLARE @fin DATE = DATEADD(wk, DATEDIFF(wk, 5, '1/1/' + @anioTxt) + (@NoSemana - 1), 6);
    -- Firebird acepta dd.mm.yyyy (igual que ConsultaConceptosMICROSIP)
    DECLARE @iniTxt VARCHAR(10) = CONVERT(VARCHAR(10), @ini, 104);
    DECLARE @finTxt VARCHAR(10) = CONVERT(VARCHAR(10), @fin, 104);

    -- Lista de claves por campo (puede ser varias). Si un campo no tiene mapeo,
    -- se usa un literal que no coincide con nada -> ese concepto queda en 0.
    DECLARE @pv NVARCHAR(MAX), @fa NVARCHAR(MAX), @pd NVARCHAR(MAX), @fe NVARCHAR(MAX), @de NVARCHAR(MAX);
    SELECT @pv = STUFF((SELECT ',''' + claveMicrosip + '''' FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id=@planta AND activo=1 AND campo='primaVacacional' FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),1,1,'');
    SELECT @fa = STUFF((SELECT ',''' + claveMicrosip + '''' FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id=@planta AND activo=1 AND campo='fondoAhorro'     FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),1,1,'');
    SELECT @pd = STUFF((SELECT ',''' + claveMicrosip + '''' FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id=@planta AND activo=1 AND campo='primaDominical'  FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),1,1,'');
    SELECT @fe = STUFF((SELECT ',''' + claveMicrosip + '''' FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id=@planta AND activo=1 AND campo='festivo'         FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),1,1,'');
    SELECT @de = STUFF((SELECT ',''' + claveMicrosip + '''' FROM dbo.PrenominaConceptoMicrosip WHERE empresa_id=@planta AND activo=1 AND campo='defuncion'       FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),1,1,'');
    SET @pv = ISNULL(@pv, '''@@none@@'''); SET @fa = ISNULL(@fa, '''@@none@@''');
    SET @pd = ISNULL(@pd, '''@@none@@'''); SET @fe = ISNULL(@fe, '''@@none@@'''); SET @de = ISNULL(@de, '''@@none@@''');

    -- Pago sistema: neto (percep - reten) o bruto (solo percep), por config de la planta.
    DECLARE @psExpr NVARCHAR(100) = CASE WHEN @neto = 1 THEN 'pn.TOTAL_PERCEP - pn.TOTAL_RETEN' ELSE 'pn.TOTAL_PERCEP' END;

    -- Query que corre dentro de Microsip (Firebird): un renglón por empleado de esa
    -- nómina. Los conceptos se suman por PAGO_NOMINA en el derivado 'det' (para no
    -- multiplicar los totales de pn al unir el detalle); PS = pago sistema del empleado.
    DECLARE @q NVARCHAR(MAX) =
        'SELECT e.NUMERO,
            SUM(det.PV) AS PV, SUM(det.FA) AS FA, SUM(det.PD) AS PD, SUM(det.FE) AS FE, SUM(det.DE) AS DE,
            SUM(' + @psExpr + ') AS PS
         FROM NOMINAS n
           INNER JOIN PAGOS_NOMINA pn ON pn.NOMINA_ID = n.NOMINA_ID
           INNER JOIN EMPLEADOS e ON e.EMPLEADO_ID = pn.EMPLEADO_ID
           LEFT JOIN (
               SELECT pd.PAGO_NOMINA_ID,
                   SUM(CASE WHEN c.CLAVE IN (' + @pv + ') THEN pd.IMPORTE ELSE 0 END) AS PV,
                   SUM(CASE WHEN c.CLAVE IN (' + @fa + ') THEN pd.IMPORTE ELSE 0 END) AS FA,
                   SUM(CASE WHEN c.CLAVE IN (' + @pd + ') THEN pd.IMPORTE ELSE 0 END) AS PD,
                   SUM(CASE WHEN c.CLAVE IN (' + @fe + ') THEN pd.IMPORTE ELSE 0 END) AS FE,
                   SUM(CASE WHEN c.CLAVE IN (' + @de + ') THEN pd.IMPORTE ELSE 0 END) AS DE
               FROM PAGOS_NOMINA_DET pd
                 INNER JOIN CONCEPTOS_NO c ON c.CONCEPTO_NO_ID = pd.CONCEPTO_NO_ID
               GROUP BY pd.PAGO_NOMINA_ID
           ) det ON det.PAGO_NOMINA_ID = pn.PAGO_NOMINA_ID
         WHERE n.FECHA_INICIAL = ''' + @iniTxt + ''' AND n.FECHA_FINAL = ''' + @finTxt + '''
         GROUP BY e.NUMERO';

    IF OBJECT_ID('tempdb..#M') IS NOT NULL DROP TABLE #M;
    CREATE TABLE #M (NUMERO VARCHAR(20), PV DECIMAL(18,2), FA DECIMAL(18,2), PD DECIMAL(18,2), FE DECIMAL(18,2), DE DECIMAL(18,2), PS DECIMAL(18,2));

    DECLARE @sql NVARCHAR(MAX) = 'INSERT INTO #M SELECT * FROM OPENQUERY(' + QUOTENAME(@srv) + ', ''' + REPLACE(@q, '''', '''''') + ''')';
    BEGIN TRY
        EXEC (@sql);
    END TRY
    BEGIN CATCH
        SELECT -1 AS actualizados; RETURN;   -- Microsip no disponible
    END CATCH

    -- Sin renglones = la nómina de esa semana aún no está en Microsip -> no se toca la captura
    IF NOT EXISTS (SELECT 1 FROM #M) BEGIN SELECT 0 AS actualizados; RETURN; END;

    -- Actualiza los que ya existen en la captura. Si @forzar=0, solo llena lo que está
    -- en 0 (respeta lo que el usuario editó a mano); si @forzar=1, sobreescribe con Microsip.
    UPDATE t SET
        t.primaVacacional = CASE WHEN @forzar = 1 OR t.primaVacacional = 0 THEN m.PV ELSE t.primaVacacional END,
        t.fondoAhorro     = CASE WHEN @forzar = 1 OR t.fondoAhorro     = 0 THEN m.FA ELSE t.fondoAhorro     END,
        t.primaDominical  = CASE WHEN @forzar = 1 OR t.primaDominical  = 0 THEN m.PD ELSE t.primaDominical  END,
        t.festivo         = CASE WHEN @forzar = 1 OR t.festivo         = 0 THEN m.FE ELSE t.festivo         END,
        t.defuncion       = CASE WHEN @forzar = 1 OR t.defuncion       = 0 THEN m.DE ELSE t.defuncion       END,
        t.pagoSistema     = CASE WHEN @forzar = 1 OR t.pagoSistema     = 0 THEN m.PS ELSE t.pagoSistema     END
    FROM dbo.PrenominaCaptura t
    JOIN #M m ON t.empresa_id = @planta AND t.anio = @anio AND t.NoSemana = @NoSemana
             AND t.empleado_id COLLATE DATABASE_DEFAULT = m.NUMERO COLLATE DATABASE_DEFAULT;

    -- ...e inserta los que no existían (con los valores de Microsip)
    INSERT INTO dbo.PrenominaCaptura (empresa_id, anio, NoSemana, empleado_id, primaVacacional, fondoAhorro, primaDominical, festivo, defuncion, pagoSistema)
    SELECT @planta, @anio, @NoSemana, m.NUMERO, m.PV, m.FA, m.PD, m.FE, m.DE, m.PS
    FROM #M m
    WHERE NOT EXISTS (SELECT 1 FROM dbo.PrenominaCaptura t
                      WHERE t.empresa_id = @planta AND t.anio = @anio AND t.NoSemana = @NoSemana
                        AND t.empleado_id COLLATE DATABASE_DEFAULT = m.NUMERO COLLATE DATABASE_DEFAULT);

    SELECT COUNT(*) AS actualizados FROM #M;
END;
GO
