USE [Nominas];
GO
-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-07-17
--
--  1) Predicados sargables + OPTION (RECOMPILE).
--  2) Fechas de la semana, incapacidades y vacaciones se calculan UNA vez
--     por (empleado, año, semana) en #Semanas / #Incapacidades.
--  3) NOT IN -> NOT EXISTS.
--  4) Indices sugeridos en script aparte.
--  5) AJUSTE 2026-07-17: #AsistenciaDedup contra PRIMARY KEY violation.
--  6) AJUSTE 2026-07-17: #Incapacidades con condicion de TRASLAPE.
--  7) AJUSTE 2026-07-17: segundo INSERT empareja #Incapacidades por
--     anio/NoSemana.
--  8) AJUSTE 2026-08-28: concepto "Retardo ruta transporte" ('tran'),
--     solo excusa el retardo del dia exacto (#Transporte).
--  9) AJUSTE 2026-09-28: parametro @diasTiempoExtra (L..D). Si se pide
--     tiempo extra y se desmarca Sabado/Domingo, esas horas no van al 078;
--     si se desmarca Domingo, tampoco sale la Prima Dominical (113).
-- 10) AJUSTE 2026-09-30 (SOLO TORREON, empresas.esTorreon = 1):
--       a) [REVERTIDO 2026-10-15] Antes: dias pagados del primer renglon = dias del
--          horario menos dias de vacaciones (concepto VAC) en dia laboral. YA NO: las
--          vacaciones no se descuentan del TXT; se pagan los dias completos del horario (6)
--          y el dia de vacacion va dentro de esos dias (Microsip no suma vacaciones aparte).
--       b) Empleados sin checadas: un dia L-V con incidencia con goce de
--          sueldo (vacacion, permiso con goce) no cuenta como falta.
-- 11) AJUSTE 2026-10-07 (SOLO TORREON): claves de concepto del TXT
--       008 Premio de puntualidad   (en lugar de 061)
--       009 Premio de asistencia    (en lugar de 062; antes no salia en Torreon)
--       090 Prima dominical         (en lugar de 113)
--       078 Descanso trabajado y 073 Dia festivo se quedan igual.
-- 12) AJUSTE 2026-10-08: "Cantidad de dias por semana" (horarios_empleado.cantidad)
--       admite decimales; los dias a pagar del primer renglon salen como
--       6, 5.5 o 6.4 (FORMAT '0.##'), igual que antes cuando son enteros.
-- 14) AJUSTE 2026-10-08: incidencias parciales (incidencias.cubreEntrada, cubreEntradaComedor,
--       cubreSalidaComedor, cubreSalida). Solo la incidencia completa (cubre las 4) cuenta como
--       dia de incidencia (vacaciones, incapacidad, premios). Una parcial que cubre la entrada
--       perdona el retardo de ese dia (#Transporte), como antes solo 'tran'.
-- 13) AJUSTE 2026-10-08 (SOLO TORREON): cada falta vale los dias pagados del horario
--       (horarios_empleado.cantidad) entre los dias que se trabajan (horario con horas):
--       6 / 5 = 1.2 dias = 1 dia 1:36 h con jornada de 8 h. FALTAS del primer renglon
--       va en formato DDHMM de Microsip (dias, hora, minutos), ej. 1 falta -> 01136,
--       2 faltas -> 02312. Incapacidades y permisos sin goce no se multiplican.
--       Tope: no mas que los dias que se trabajan (toda la semana con horario de 5 dias -> 05000).
--
-- EXEC [dbo].[ConsultaAsistenciaExportarTablaTemporal] '39','2026','12',NULL,NULL,NULL,1,'1111100'
--
---CUANDO ES INCAPACIDAD ES EL TOTAL DE LAS FALTAS JUNTO CON LA FALTA DE INCAPACIDAD
---Y SOLO PONE UNA FECHA DE LA FALTA
--EJEMPLO TIENE 2 DIAS DE INCAPACIDAD Y 2 FALTAS SALDRIA ASI:
--             |1|141304,6,04000,0000,00,0000,00
--             |1.2|29/06/2026
--             |1.2|30/06/2026
-- [REVERTIDO 2026-10-15] antes 3 dias de vacaciones restaban de los 6 (DIAS 3). Ya NO:
-- con vacaciones los dias pagados siguen siendo los del horario (6), no se descuentan.
--             |1|141304,6,00000,0000,00,0000,00
--             |1.1|062,1
--             |1.1|061,1
--- si no tiene la hora de entrada no cuenta las horas extras
-- =============================================
CREATE OR ALTER PROCEDURE [dbo].[ConsultaAsistenciaExportarTablaTemporal]
    @NoSemana        INT         = NULL,
    @anio            VARCHAR(50) = NULL,
    @planta          VARCHAR(50) = NULL,
    @Code            VARCHAR(50) = NULL,
    @puesto          VARCHAR(50) = NULL,
    @NoEmpleado      VARCHAR(50) = NULL,
    @tiempoExtra     bit         = NULL,
    @diasTiempoExtra VARCHAR(7)  = '1111111'   -- L M Mi J V S D: 0 = ese dia no paga TE / 078 / 113
AS
BEGIN
SET NOCOUNT ON;

-- AJUSTE 2026-09-28: validar la cadena de dias
IF @diasTiempoExtra IS NULL OR LEN(@diasTiempoExtra) <> 7 OR @diasTiempoExtra LIKE '%[^01]%'
    SET @diasTiempoExtra = '1111111';

-- AJUSTE 2026-08-28: concepto "Retardo ruta transporte" (por descripcionCorta).
DECLARE @ConceptoTransporte VARCHAR(4) = 'tran';
-- AJUSTE 2026-09-30: plantas de Torreon (empresas.esTorreon = 1).
DECLARE @ConceptoVacaciones VARCHAR(10) = 'VAC';
DECLARE @esTorreon BIT = ISNULL((SELECT TOP 1 esTorreon FROM dbo.empresas WHERE id = @planta), 0);

IF OBJECT_ID('tempdb..#tablaExportar')    IS NOT NULL DROP TABLE #tablaExportar;
IF OBJECT_ID('tempdb..#Semanas')          IS NOT NULL DROP TABLE #Semanas;
IF OBJECT_ID('tempdb..#Incapacidades')    IS NOT NULL DROP TABLE #Incapacidades;
IF OBJECT_ID('tempdb..#AsistenciaDedup')  IS NOT NULL DROP TABLE #AsistenciaDedup;
IF OBJECT_ID('tempdb..#Transporte')       IS NOT NULL DROP TABLE #Transporte;
IF OBJECT_ID('tempdb..#FaltasTorreon')    IS NOT NULL DROP TABLE #FaltasTorreon;
-- =========================================================================
-- PASO 0: blindaje contra registros duplicados en asistenciaBioStar.
-- =========================================================================
WITH CTE_Duplicados AS (
SELECT
*,
ROW_NUMBER() OVER (PARTITION BY a.Numero, a.anio, a.NoSemana, a.empresa_id ORDER BY (SELECT NULL)) AS NumFila
FROM asistenciaBioStar as a
)
DELETE FROM CTE_Duplicados
WHERE NumFila > 1;
IF EXISTS (
    SELECT 1
    FROM asistenciaBioStar a
    WHERE (@NoSemana   IS NULL OR a.NoSemana   = @NoSemana)
      AND (@anio       IS NULL OR a.anio       = @anio)
      AND a.empresa_id =@planta
      AND (@NoEmpleado IS NULL OR a.Numero     = @NoEmpleado)
    GROUP BY a.Numero, a.anio, a.NoSemana, a.empresa_id
    HAVING COUNT(*) > 1
)
BEGIN
    DECLARE @NumerosDuplicados NVARCHAR(MAX);
    SELECT @NumerosDuplicados = STRING_AGG(CAST(d.Numero AS VARCHAR(50)), ', ')
    FROM (
        SELECT distinct a.Numero, a.anio, a.NoSemana, a.empresa_id
        FROM asistenciaBioStar a
        WHERE a.NoSemana   = @NoSemana
          AND a.anio       = @anio
          AND a.empresa_id =@planta
          AND (@NoEmpleado IS NULL OR a.Numero     = @NoEmpleado)
        GROUP BY a.Numero, a.anio, a.NoSemana, a.empresa_id
        HAVING COUNT(*) > 1
    ) d;
    RAISERROR('ConsultaAsistenciaExportarTablaTemporal: asistenciaBioStar tiene registros duplicados (mismo Numero/anio/NoSemana/empresa_id) para: %s. Se usará un solo registro por empleado para no truncar la ejecución, pero conviene revisar el origen de la duplicación en BioStar.', 10, 1, @NumerosDuplicados) WITH NOWAIT;
END
;WITH Dedup AS (
    SELECT
        a.*,
        ROW_NUMBER() OVER (
            PARTITION BY a.Numero, a.anio, a.NoSemana, a.empresa_id
            ORDER BY (SELECT NULL)
        ) AS rn_
    FROM asistenciaBioStar a
    WHERE (@NoSemana   IS NULL OR a.NoSemana   = @NoSemana)
      AND (@anio       IS NULL OR a.anio       = @anio)
      AND (@planta     IS NULL OR a.empresa_id = @planta)
      AND (@NoEmpleado IS NULL OR a.Numero     = @NoEmpleado)
)
SELECT *
INTO #AsistenciaDedup
FROM Dedup
WHERE rn_ = 1;
-- Numero es varchar(MAX); NumeroKey acotada solo para poder indexar.
ALTER TABLE #AsistenciaDedup ADD NumeroKey VARCHAR(50) COLLATE DATABASE_DEFAULT NULL;
UPDATE #AsistenciaDedup SET NumeroKey = CAST(Numero AS VARCHAR(50));
CREATE UNIQUE CLUSTERED INDEX IX_AsistenciaDedup_Numero ON #AsistenciaDedup(NumeroKey, anio, NoSemana);
-- =========================================================================
-- PASO 1: (empleado, año, semana) con sus fechas Lunes..Domingo
-- =========================================================================
CREATE TABLE #Semanas (
    RowId          INT IDENTITY(1,1) PRIMARY KEY,
    Numero         VARCHAR(150) COLLATE DATABASE_DEFAULT NOT NULL,
    empleado_id    INT NOT NULL,
    anio           INT NOT NULL,
    NoSemana       INT NOT NULL,
    FechaLunes     SMALLDATETIME NOT NULL,
    FechaMartes    SMALLDATETIME NOT NULL,
    FechaMiercoles SMALLDATETIME NOT NULL,
    FechaJueves    SMALLDATETIME NOT NULL,
    FechaViernes   SMALLDATETIME NOT NULL,
    FechaSabado    SMALLDATETIME NOT NULL,
    FechaDomingo   SMALLDATETIME NOT NULL
);
CREATE UNIQUE INDEX IX_Semanas_Empleado ON #Semanas(empleado_id, anio, NoSemana);
INSERT INTO #Semanas (Numero, empleado_id, anio, NoSemana,
                       FechaLunes, FechaMartes, FechaMiercoles, FechaJueves,
                       FechaViernes, FechaSabado, FechaDomingo)
SELECT
    a.Numero,
    CAST(a.Numero AS INT),
    a.anio,
    a.NoSemana,
    m.MondayTarget,
    DATEADD(DAY,1,m.MondayTarget),
    DATEADD(DAY,2,m.MondayTarget),
    DATEADD(DAY,3,m.MondayTarget),
    DATEADD(DAY,4,m.MondayTarget),
    DATEADD(DAY,5,m.MondayTarget),
    DATEADD(DAY,6,m.MondayTarget)
FROM #AsistenciaDedup a
CROSS APPLY (
    SELECT MondayTarget = DATEADD(
        WEEK, a.NoSemana - 1,
        DATEADD(DAY,
            1 - ((DATEPART(WEEKDAY, DATEFROMPARTS(a.anio,1,4)) + @@DATEFIRST - 2) % 7 + 1),
            DATEFROMPARTS(a.anio,1,4)
        )
    )
) m;
-- =========================================================================
-- PASO 2: incapacidad por dia y dias de vacaciones UNA vez por
-- (empleado, año, semana)
-- =========================================================================
CREATE TABLE #Incapacidades (
    empleado_id      INT NOT NULL,
    anio             INT NOT NULL,
    NoSemana         INT NOT NULL,
    EsIncapLunes     BIT NOT NULL DEFAULT 0,
    EsIncapMartes    BIT NOT NULL DEFAULT 0,
    EsIncapMiercoles BIT NOT NULL DEFAULT 0,
    EsIncapJueves    BIT NOT NULL DEFAULT 0,
    EsIncapViernes   BIT NOT NULL DEFAULT 0,
    EsIncapSabado    BIT NOT NULL DEFAULT 0,
    EsIncapDomingo   BIT NOT NULL DEFAULT 0,
    EsVacLunes       BIT NOT NULL DEFAULT 0,
    EsVacMartes      BIT NOT NULL DEFAULT 0,
    EsVacMiercoles   BIT NOT NULL DEFAULT 0,
    EsVacJueves      BIT NOT NULL DEFAULT 0,
    EsVacViernes     BIT NOT NULL DEFAULT 0,
    EsVacSabado      BIT NOT NULL DEFAULT 0,
    EsVacDomingo     BIT NOT NULL DEFAULT 0,
    DiasVacaciones   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (empleado_id, anio, NoSemana)
);
    INSERT INTO #Incapacidades
    SELECT
        s.empleado_id, s.anio, s.NoSemana,
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaLunes     BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaMartes    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaJueves    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaViernes   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaSabado    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 0 AND s.FechaDomingo   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaLunes     BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaMartes    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaJueves    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaViernes   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaSabado    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        MAX(CASE WHEN c.goceSueldo = 1 AND s.FechaDomingo   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
        SUM(CASE WHEN c.goceSueldo = 1
                  AND inc.fechaInicio >= s.FechaLunes
                  AND inc.fechaFin    <= s.FechaDomingo
                 THEN inc.cantidadDias ELSE 0 END)
    FROM #Semanas s
    JOIN incidencias inc ON inc.empleado_id = s.empleado_id
    JOIN conceptos   c   ON c.id = inc.concepto_id
-- Condicion de TRASLAPE (no "que cubra la semana completa").
WHERE inc.fechaInicio <= s.FechaDomingo
  AND inc.fechaFin    >= s.FechaLunes
-- "Retardo ruta transporte" no entra al bucket de vacaciones.
  AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
GROUP BY s.empleado_id, s.anio, s.NoSemana;
-- =========================================================================
-- PASO 2.1: "Retardo ruta transporte" por dia exacto
-- =========================================================================
CREATE TABLE #Transporte (
    empleado_id      INT NOT NULL,
    anio             INT NOT NULL,
    NoSemana         INT NOT NULL,
    EsTranLunes      BIT NOT NULL DEFAULT 0,
    EsTranMartes     BIT NOT NULL DEFAULT 0,
    EsTranMiercoles  BIT NOT NULL DEFAULT 0,
    EsTranJueves     BIT NOT NULL DEFAULT 0,
    EsTranViernes    BIT NOT NULL DEFAULT 0,
    EsTranSabado     BIT NOT NULL DEFAULT 0,
    EsTranDomingo    BIT NOT NULL DEFAULT 0,
    PRIMARY KEY (empleado_id, anio, NoSemana)
);
INSERT INTO #Transporte
SELECT
    s.empleado_id, s.anio, s.NoSemana,
    MAX(CASE WHEN s.FechaLunes     BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaMartes    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaJueves    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaViernes   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaSabado    BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END),
    MAX(CASE WHEN s.FechaDomingo   BETWEEN inc.fechaInicio AND inc.fechaFin THEN 1 ELSE 0 END)
FROM #Semanas s
JOIN incidencias inc ON inc.empleado_id = s.empleado_id
JOIN conceptos   c   ON c.id = inc.concepto_id
WHERE inc.fechaInicio <= s.FechaDomingo
  AND inc.fechaFin    >= s.FechaLunes
  AND inc.cubreEntrada = 1 AND NOT (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
GROUP BY s.empleado_id, s.anio, s.NoSemana;
-- =========================================================================
-- PASO 3: tabla final de exportacion
-- =========================================================================
CREATE TABLE #tablaExportar (
       [Numero]                    INT  PRIMARY KEY
      ,[Nombre]                     VARCHAR (MAX)
      ,empresa_id                   INT
      ,[RetardoSemanal]             INT
      ,[FaltaLunes]                 INT
      ,[FaltaMartes]                INT
      ,[FaltaMiercoles]             INT
      ,[FaltaJueves]                INT
      ,[FaltaViernes]               INT
      ,[FaltaSabado]                INT
      ,[FaltaDomingo]               INT
      ,FaltasTotales                INT
      ,LunesTiempoExtra             INT
      ,MartesTiempoExtra            INT
      ,MiercolesTiempoExtra         INT
      ,JuevesTiempoExtra            INT
      ,ViernesTiempoExtra           INT
      ,SabadoTiempoExtra            INT
      ,DomingoTiempoExtra           INT
      ,TotalTiempoExtra             varchar(MAX)
      ,TotalTiempoExtraTriple       varchar(MAX)
      ,TotalhorasExtra              varchar(MAX)
      ,TotalminutosExtra            varchar(MAX)
      ,TotalsegundosExtra           varchar(MAX)
      ,TotalhorasExtraTriple        varchar(MAX)
      ,TotalMinutosExtraTriple      varchar(MAX)
      ,TotalSegundosExtraTriple     varchar(MAX)
      ,PremioporPuntualidad         varchar(MAX)
      ,DescansoTrabajado            varchar(MAX)
      ,PrimaDominical               varchar(MAX)
      ,DiaFestivo                   varchar(MAX)
      ,PremioporAsistencia          varchar(MAX)
      ,[NoSemana]                   varchar(MAX)
      ,anio                         varchar(MAX)
      ,FechaFaltaLunes              smalldatetime
      ,FechaFaltaMartes             smalldatetime
      ,FechaFaltaMiercoles          smalldatetime
      ,FechaFaltaJueves             smalldatetime
      ,FechaFaltaViernes            smalldatetime
      ,FechaFaltaSabado             smalldatetime
      ,FechaFaltaDomingo            smalldatetime
      ,diasLaborales                DECIMAL(5,2)   -- AJUSTE 2026-10-08: dias por semana con decimales
      ,EsIncapacidadLunes           BIT
      ,EsIncapacidadMartes          BIT
      ,EsIncapacidadMiercoles       BIT
      ,EsIncapacidadJueves          BIT
      ,EsIncapacidadViernes         BIT
      ,EsIncapacidadSabado          BIT
      ,EsIncapacidadDomingo         BIT
      ,DiasVacaciones               INT
      ,EsVacLunes           BIT
      ,EsVacMartes          BIT
      ,EsVacMiercoles       BIT
      ,EsVacJueves          BIT
      ,EsVacViernes         BIT
      ,EsVacSabado          BIT
      ,EsVacDomingo         BIT
)
SET NOCOUNT ON;
-- =========================================================================
-- RAMA 1: empleados CON registro en asistenciaBioStar esa semana
-- =========================================================================
INSERT INTO #tablaExportar
SELECT distinct
      CAST(e.empleado_id AS INT)
      ,e.[NOMBRE_COMPLETO]
      ,a.empresa_id
      ,tr.RetardoSemanalAjustado
      ,a.[FaltaLunes]
      ,a.[FaltaMartes]
      ,a.[FaltaMiercoles]
      ,a.[FaltaJueves]
      ,a.[FaltaViernes]
      ,a.[FaltaSabado]
      ,a.[FaltaDomingo]
      -- Vacacion y Dia Festivo no trabajado no cuentan como falta;
      -- la incapacidad SI cuenta.
      ,(CASE WHEN ISNULL(i.EsVacLunes,0)=1     OR esfest.EsFestivoLunes=1     THEN 0 ELSE isnull(a.[FaltaLunes],0)     END)
      +(CASE WHEN ISNULL(i.EsVacMartes,0)=1    OR esfest.EsFestivoMartes=1    THEN 0 ELSE isnull(a.[FaltaMartes],0)    END)
      +(CASE WHEN ISNULL(i.EsVacMiercoles,0)=1 OR esfest.EsFestivoMiercoles=1 THEN 0 ELSE isnull(a.[FaltaMiercoles],0) END)
      +(CASE WHEN ISNULL(i.EsVacJueves,0)=1    OR esfest.EsFestivoJueves=1    THEN 0 ELSE isnull(a.[FaltaJueves],0)    END)
      +(CASE WHEN ISNULL(i.EsVacViernes,0)=1   OR esfest.EsFestivoViernes=1   THEN 0 ELSE isnull(a.[FaltaViernes],0)   END)
      +(CASE WHEN ISNULL(i.EsVacSabado,0)=1    OR esfest.EsFestivoSabado=1    THEN 0 ELSE isnull(a.[FaltaSabado],0)    END)
      +(CASE WHEN ISNULL(i.EsVacDomingo,0)=1   OR esfest.EsFestivoDomingo=1   THEN 0 ELSE isnull(a.[FaltaDomingo],0)   END) FaltasTotales
      ,case when a.[LunesTiempoExtra]>'00:50:00' then 1 else 0 end LunesTiempoExtra
      ,case when a.[MartesTiempoExtra]>'00:50:00' then 1 else 0 end [MartesTiempoExtra]
      ,case when a.[MiercolesTiempoExtra]>'00:50:00' then 1 else 0 end [MiercolesTiempoExtra]
      ,case when a.[JuevesTiempoExtra]>'00:50:00' then 1 else 0 end [JuevesTiempoExtra]
      ,case when a.[ViernesTiempoExtra]>'00:50:00' then 1 else 0 end [ViernesTiempoExtra]
      ,case when a.[SabadoTiempoExtra]>'00:50:00' then 1 else 0 end [SabadoTiempoExtra]
      ,case when a.[DomingoTiempoExtra]>'00:50:00' then 1 else 0 end [DomingoTiempoExtra]
      ,te.TotalTiempoExtraCalc AS TotalTiempoExtra
      ,'00:00:00' -- Triple (simplificado)
      ,case when te.TotalTiempoExtraCalc>'09:00:00' then '09' else case when te.TotalTiempoExtraCalc>'00:50:00' then SUBSTRING(te.TotalTiempoExtraCalc,1,2) else '00' end end TotalhorasExtra
      ,case when te.TotalTiempoExtraCalc>'09:00:00' then '00' else case when te.TotalTiempoExtraCalc>'00:50:00' then SUBSTRING(te.TotalTiempoExtraCalc,4,2) else '00' end end TotalminutosExtra
      ,case when te.TotalTiempoExtraCalc>'09:00:00' then '00' else case when te.TotalTiempoExtraCalc>'00:50:00' then SUBSTRING(te.TotalTiempoExtraCalc,7,2) else '00' end end TotalsegundosExtra
      ,'00','00','00' -- Triples
      ,isnull(case when (
            (CASE WHEN ISNULL(i.EsVacLunes,0)=1     OR esfest.EsFestivoLunes=1     THEN 0 ELSE isnull(a.[FaltaLunes],0)     END)
           +(CASE WHEN ISNULL(i.EsVacMartes,0)=1    OR esfest.EsFestivoMartes=1    THEN 0 ELSE isnull(a.[FaltaMartes],0)    END)
           +(CASE WHEN ISNULL(i.EsVacMiercoles,0)=1 OR esfest.EsFestivoMiercoles=1 THEN 0 ELSE isnull(a.[FaltaMiercoles],0) END)
           +(CASE WHEN ISNULL(i.EsVacJueves,0)=1    OR esfest.EsFestivoJueves=1    THEN 0 ELSE isnull(a.[FaltaJueves],0)    END)
           +(CASE WHEN ISNULL(i.EsVacViernes,0)=1   OR esfest.EsFestivoViernes=1   THEN 0 ELSE isnull(a.[FaltaViernes],0)   END)
           +(CASE WHEN ISNULL(i.EsVacSabado,0)=1    OR esfest.EsFestivoSabado=1    THEN 0 ELSE isnull(a.[FaltaSabado],0)    END)
           +(CASE WHEN ISNULL(i.EsVacDomingo,0)=1   OR esfest.EsFestivoDomingo=1   THEN 0 ELSE isnull(a.[FaltaDomingo],0)   END)
        )=0 then
        -- RetardoSemanal ajustado por "Retardo ruta transporte" y
        -- salida temprana tambien cancela el premio.
        case when (tr.RetardoSemanalAjustado=0 AND sth.HuboSalidaTemprana=0) then '|1.1|061,1' else '' end end,'') as PremioporPuntualidad
      ,dt.DescansoTrabajadoCalc as DescansoTrabajado
      ,pdom.PrimaDominicalCalc as PrimaDominical
      ,fest.DiaFestivoCalc as DiaFestivo
      ,isnull(case when (
            (CASE WHEN ISNULL(i.EsVacLunes,0)=1     OR esfest.EsFestivoLunes=1     THEN 0 ELSE isnull(a.[FaltaLunes],0)     END)
           +(CASE WHEN ISNULL(i.EsVacMartes,0)=1    OR esfest.EsFestivoMartes=1    THEN 0 ELSE isnull(a.[FaltaMartes],0)    END)
           +(CASE WHEN ISNULL(i.EsVacMiercoles,0)=1 OR esfest.EsFestivoMiercoles=1 THEN 0 ELSE isnull(a.[FaltaMiercoles],0) END)
           +(CASE WHEN ISNULL(i.EsVacJueves,0)=1    OR esfest.EsFestivoJueves=1    THEN 0 ELSE isnull(a.[FaltaJueves],0)    END)
           +(CASE WHEN ISNULL(i.EsVacViernes,0)=1   OR esfest.EsFestivoViernes=1   THEN 0 ELSE isnull(a.[FaltaViernes],0)   END)
           +(CASE WHEN ISNULL(i.EsVacSabado,0)=1    OR esfest.EsFestivoSabado=1    THEN 0 ELSE isnull(a.[FaltaSabado],0)    END)
           +(CASE WHEN ISNULL(i.EsVacDomingo,0)=1   OR esfest.EsFestivoDomingo=1   THEN 0 ELSE isnull(a.[FaltaDomingo],0)   END)
        )=0 then
        '|1.1|062,1' else '' end,'') as PremioporAsistencia
      ,a.[NoSemana]
      ,a.[anio]
      ,CASE WHEN a.FaltaLunes = 1     AND ISNULL(i.EsVacLunes,0)=0     AND esfest.EsFestivoLunes=0     THEN s.FechaLunes     else NULL END
      ,CASE WHEN a.FaltaMartes = 1    AND ISNULL(i.EsVacMartes,0)=0    AND esfest.EsFestivoMartes=0    THEN s.FechaMartes    else NULL END
      ,CASE WHEN a.FaltaMiercoles = 1 AND ISNULL(i.EsVacMiercoles,0)=0 AND esfest.EsFestivoMiercoles=0 THEN s.FechaMiercoles else NULL END
      ,CASE WHEN a.FaltaJueves = 1    AND ISNULL(i.EsVacJueves,0)=0    AND esfest.EsFestivoJueves=0    THEN s.FechaJueves    else NULL  END
      ,CASE WHEN a.FaltaViernes = 1   AND ISNULL(i.EsVacViernes,0)=0   AND esfest.EsFestivoViernes=0   THEN s.FechaViernes   else NULL END
      ,CASE WHEN a.FaltaSabado = 1    AND ISNULL(i.EsVacSabado,0)=0    AND esfest.EsFestivoSabado=0    THEN s.FechaSabado    else NULL END
      ,CASE WHEN a.FaltaDomingo = 1   AND ISNULL(i.EsVacDomingo,0)=0   AND esfest.EsFestivoDomingo=0   THEN s.FechaDomingo   else NULL END
      -- AJUSTE 2026-10-15: Torreon ya NO descuenta vacaciones del TXT. El dia de vacacion
      -- va pagado dentro de los dias del horario (6); Microsip no suma vacaciones aparte.
      -- (Antes: dias del horario MENOS dias de vacaciones laborales -> 1 VAC daba DIAS 5.)
      ,ISNULL(h.cantidad, 6) as diasLaborales
      ,ISNULL(i.EsIncapLunes, 0)
      ,ISNULL(i.EsIncapMartes, 0)
      ,ISNULL(i.EsIncapMiercoles, 0)
      ,ISNULL(i.EsIncapJueves, 0)
      ,ISNULL(i.EsIncapViernes, 0)
      ,ISNULL(i.EsIncapSabado, 0)
      ,ISNULL(i.EsIncapDomingo, 0)
      ,ISNULL(i.DiasVacaciones, 0)
      ,ISNULL(i.EsVacLunes,0)
      ,ISNULL(i.EsVacMartes,0)
      ,ISNULL(i.EsVacMiercoles,0)
      ,ISNULL(i.EsVacJueves,0)
      ,ISNULL(i.EsVacViernes,0)
      ,ISNULL(i.EsVacSabado,0)
      ,ISNULL(i.EsVacDomingo,0)
FROM #AsistenciaDedup a
JOIN #Semanas s
    ON s.Numero = a.Numero COLLATE DATABASE_DEFAULT
LEFT JOIN #Incapacidades i
    ON i.empleado_id = s.empleado_id AND i.anio = s.anio AND i.NoSemana = s.NoSemana
LEFT JOIN #Transporte t
    ON t.empleado_id = s.empleado_id AND t.anio = s.anio AND t.NoSemana = s.NoSemana
-- RetardoSemanal ajustado por "Retardo ruta transporte" (sin bajar de 0)
CROSS APPLY (
    SELECT RetardoSemanalAjustado =
        CASE WHEN (
              ISNULL(a.RetardoSemanal,0)
            - (CASE WHEN ISNULL(t.EsTranLunes,0)=1     THEN ISNULL(a.RetardoLunes,0)     ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranMartes,0)=1    THEN ISNULL(a.RetardoMartes,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranMiercoles,0)=1 THEN ISNULL(a.RetardoMiercoles,0) ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranJueves,0)=1    THEN ISNULL(a.RetardoJueves,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranViernes,0)=1   THEN ISNULL(a.RetardoViernes,0)   ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranSabado,0)=1    THEN ISNULL(a.RetardoSabado,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranDomingo,0)=1   THEN ISNULL(a.RetardoDomingo,0)   ELSE 0 END)
        ) < 0 THEN 0 ELSE (
              ISNULL(a.RetardoSemanal,0)
            - (CASE WHEN ISNULL(t.EsTranLunes,0)=1     THEN ISNULL(a.RetardoLunes,0)     ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranMartes,0)=1    THEN ISNULL(a.RetardoMartes,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranMiercoles,0)=1 THEN ISNULL(a.RetardoMiercoles,0) ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranJueves,0)=1    THEN ISNULL(a.RetardoJueves,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranViernes,0)=1   THEN ISNULL(a.RetardoViernes,0)   ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranSabado,0)=1    THEN ISNULL(a.RetardoSabado,0)    ELSE 0 END)
            - (CASE WHEN ISNULL(t.EsTranDomingo,0)=1   THEN ISNULL(a.RetardoDomingo,0)   ELSE 0 END)
        ) END
) tr
-- Salida temprana (antes de salida_Inicio) cancela Premio por Puntualidad.
-- NULLIF(...,'') porque '' convertido a TIME da 00:00:00.
CROSS APPLY (
    SELECT
        SalidaTempranaLunes =
            CASE WHEN NULLIF(a.LunesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.LunesSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Lunes) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.LunesSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Lunes)
                 THEN 1 ELSE 0 END,
        SalidaTempranaMartes =
            CASE WHEN NULLIF(a.MartesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MartesSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Martes) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MartesSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Martes)
                 THEN 1 ELSE 0 END,
        SalidaTempranaMiercoles =
            CASE WHEN NULLIF(a.MiercolesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MiercolesSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Miercoles) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MiercolesSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Miercoles)
                 THEN 1 ELSE 0 END,
        SalidaTempranaJueves =
            CASE WHEN NULLIF(a.JuevesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.JuevesSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Jueves) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.JuevesSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Jueves)
                 THEN 1 ELSE 0 END,
        SalidaTempranaViernes =
            CASE WHEN NULLIF(a.ViernesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.ViernesSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Viernes) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.ViernesSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Viernes)
                 THEN 1 ELSE 0 END,
        SalidaTempranaSabado =
            CASE WHEN NULLIF(a.SabadoSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.SabadoSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Sabado) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.SabadoSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Sabado)
                 THEN 1 ELSE 0 END,
        SalidaTempranaDomingo =
            CASE WHEN NULLIF(a.DomingoSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.DomingoSalida) IS NOT NULL
                  AND (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Domingo) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.DomingoSalida) < (SELECT TOP 1 salida_Inicio FROM horarios WHERE id = a.idhorario_Domingo)
                 THEN 1 ELSE 0 END
) st
CROSS APPLY (
    SELECT
        HuboSalidaTemprana =
            CASE WHEN st.SalidaTempranaLunes = 1 OR st.SalidaTempranaMartes = 1
                  OR st.SalidaTempranaMiercoles = 1 OR st.SalidaTempranaJueves = 1
                  OR st.SalidaTempranaViernes = 1 OR st.SalidaTempranaSabado = 1
                  OR st.SalidaTempranaDomingo = 1
                 THEN 1 ELSE 0 END
) sth
-- Asignacion de horario vigente mas reciente (evita duplicar filas)
OUTER APPLY (
    SELECT TOP 1 he.cantidad
    FROM horarios_empleado he
    WHERE he.empleado_id = a.Numero COLLATE DATABASE_DEFAULT
      AND he.fechaAsignacion <= s.FechaDomingo
      and he.empresa_id=@planta
      AND he.idEstatus = 1          -- AJUSTE 2026-09-30: solo asignacion activa
    ORDER BY he.fechaAsignacion DESC, he.id DESC
) h
-- Registro de empleado preferido (ESTATUS='A', id mas alto)
OUTER APPLY (
    SELECT TOP 1 emp.empleado_id, emp.NOMBRE_COMPLETO, emp.salarioDiario
    FROM empleados emp
    WHERE emp.empleado_id COLLATE DATABASE_DEFAULT = a.Numero COLLATE DATABASE_DEFAULT
      AND emp.empresa_id = a.empresa_id
    ORDER BY CASE WHEN emp.ESTATUS = 'A' THEN 0 ELSE 1 END, emp.id DESC
) e
-- AJUSTE 2026-09-30: dias de vacaciones (concepto VAC) que caen en un dia
-- laboral del horario de ese dia (horas_diarias > 0). Solo se usa para
-- Torreon (diasLaborales).
CROSS APPLY (
    SELECT DiasVacLaborables =
          (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Lunes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Martes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Miercoles), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Jueves), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Viernes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaSabado BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Sabado), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaDomingo BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Domingo), 0) > 0
                THEN 1 ELSE 0 END)
) vacdias
-- Total tiempo extra L-V (Sabado/Domingo van por el 078)
CROSS APPLY (
    SELECT TotalTiempoExtraCalc =
        CASE
            WHEN a.TotalTiempoExtra > '09:00:00' THEN '09:00:00'
            WHEN a.TotalTiempoExtra > '00:50:00' THEN
                CONVERT(
                    CHAR(8),
                    DATEADD(SECOND,
                     DATEDIFF(SECOND, '00:00:00',
                            CASE WHEN a.LunesTiempoExtra > '03:00:00' THEN '03:00:00' ELSE case when a.LunesTiempoExtra <'00:50:00' then '00:00:00' else a.LunesTiempoExtra end END
                        ) +
                        DATEDIFF(SECOND, '00:00:00',
                            CASE WHEN a.MartesTiempoExtra > '03:00:00' THEN '03:00:00' ELSE case when a.MartesTiempoExtra <'00:50:00' then '00:00:00' else a.MartesTiempoExtra end  END
                        ) +
                        DATEDIFF(SECOND, '00:00:00',
                            CASE WHEN a.MiercolesTiempoExtra > '03:00:00' THEN '03:00:00' ELSE case when a.MiercolesTiempoExtra <'00:50:00' then '00:00:00' else a.MiercolesTiempoExtra end END
                        ) +
                        DATEDIFF(SECOND, '00:00:00',
                            CASE WHEN a.JuevesTiempoExtra > '03:00:00' THEN '03:00:00' ELSE case when a.JuevesTiempoExtra <'00:50:00' then '00:00:00' else a.JuevesTiempoExtra end END
                        ) +
                        DATEDIFF(SECOND, '00:00:00',
                            CASE WHEN a.ViernesTiempoExtra > '03:00:00' THEN '03:00:00' ELSE case when a.ViernesTiempoExtra <'00:50:00' then '00:00:00' else a.ViernesTiempoExtra end END
                        ),
                        CAST('00:00:00' AS TIME)
                    ),
                    108
                )
            ELSE '00'
        END
) te
-- Horas de Sabado/Domingo (primera entrada a ultima salida, sin descontar
-- comedor; +24h solo si es nocturno y la resta da negativo).
CROSS APPLY (
    SELECT
        SabadoSegundosCrudo =
            CASE WHEN NULLIF(a.SabadoEntrada,'') IS NOT NULL
                  AND NULLIF(a.SabadoSalida,'')  IS NOT NULL
                  AND TRY_CONVERT(TIME, a.SabadoEntrada) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.SabadoSalida)  IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.SabadoEntrada), TRY_CONVERT(TIME, a.SabadoSalida))
                 ELSE NULL
            END,
        DomingoSegundosCrudo =
            CASE WHEN NULLIF(a.DomingoEntrada,'') IS NOT NULL
                  AND NULLIF(a.DomingoSalida,'')  IS NOT NULL
                  AND TRY_CONVERT(TIME, a.DomingoEntrada) IS NOT NULL
                  AND TRY_CONVERT(TIME, a.DomingoSalida)  IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.DomingoEntrada), TRY_CONVERT(TIME, a.DomingoSalida))
                 ELSE NULL
            END
) dtcrudo
CROSS APPLY (
    SELECT
        SabadoSegundos =
            CASE
                WHEN dtcrudo.SabadoSegundosCrudo IS NULL THEN 0
                WHEN dtcrudo.SabadoSegundosCrudo > 0 THEN dtcrudo.SabadoSegundosCrudo
                WHEN dtcrudo.SabadoSegundosCrudo < 0 AND ISNULL(a.tipoHorarioSabado,0) = 1
                    THEN dtcrudo.SabadoSegundosCrudo + 86400
                ELSE 0
            END,
        DomingoSegundos =
            CASE
                WHEN dtcrudo.DomingoSegundosCrudo IS NULL THEN 0
                WHEN dtcrudo.DomingoSegundosCrudo > 0 THEN dtcrudo.DomingoSegundosCrudo
                WHEN dtcrudo.DomingoSegundosCrudo < 0 AND ISNULL(a.tipoHorarioDomingo,0) = 1
                    THEN dtcrudo.DomingoSegundosCrudo + 86400
                ELSE 0
            END
) dtc
-- 078 solo cuando Sabado/Domingo es realmente dia de descanso
-- (horas_diarias = 0 en el horario de ese dia).
CROSS APPLY (
    SELECT
        SabadoEsLaboral  = CASE WHEN ISNULL((SELECT TOP 1 h.horas_diarias FROM horarios h WHERE h.id = a.idhorario_Sabado),  0) > 0 THEN 1 ELSE 0 END,
        DomingoEsLaboral = CASE WHEN ISNULL((SELECT TOP 1 h.horas_diarias FROM horarios h WHERE h.id = a.idhorario_Domingo), 0) > 0 THEN 1 ELSE 0 END
) horfindesc
-- AJUSTE 2026-09-28: si en el Reporte Txt se desmarco Sabado o Domingo
-- en "Dias con tiempo extra", las horas de ese dia no van al 078.
CROSS APPLY (
    SELECT
        SabadoSegundosDescanso  = CASE WHEN horfindesc.SabadoEsLaboral  = 1
                                         OR SUBSTRING(@diasTiempoExtra, 6, 1) = '0'
                                       THEN 0 ELSE dtc.SabadoSegundos  END,
        DomingoSegundosDescanso = CASE WHEN horfindesc.DomingoEsLaboral = 1
                                         OR SUBSTRING(@diasTiempoExtra, 7, 1) = '0'
                                       THEN 0 ELSE dtc.DomingoSegundos END
) dtcdesc
CROSS APPLY (
    SELECT
        DescansoTrabajadoCalc =
            CASE WHEN (dtcdesc.SabadoSegundosDescanso + dtcdesc.DomingoSegundosDescanso) > 0
            THEN '|1.1|078,'
                + CAST((dtcdesc.SabadoSegundosDescanso + dtcdesc.DomingoSegundosDescanso) / 3600 AS VARCHAR)
                + '.'
                + RIGHT('00' + CAST(CAST(ROUND((((dtcdesc.SabadoSegundosDescanso + dtcdesc.DomingoSegundosDescanso) % 3600) / 60) * 100.0 / 60, 0) AS INT) AS VARCHAR), 2)
            ELSE NULL
            END
) dt
-- ============================================================
-- Prima Dominical (113):
--   1 a 9 horas el domingo : salarioDiario x 0.30
--   mas de 9 horas         : (salarioDiario/8*12) x 0.30
--   menos de 1 hora        : no aplica
-- ============================================================
CROSS APPLY (
    SELECT
        SalarioDiarioNum = TRY_CONVERT(DECIMAL(10,2), e.salarioDiario)
) sal
CROSS APPLY (
    SELECT
        DomingoHoras = dtc.DomingoSegundos / 3600.0
) domh
-- AJUSTE 2026-09-28: si se marco "Tiempo Extra" y se desmarco el Domingo,
-- la Prima Dominical no se reporta (ese dia se paga en efectivo). Si el
-- checkbox general de Tiempo Extra NO esta marcado, se reporta como siempre.
CROSS APPLY (
    SELECT
        PrimaDominicalCalc =
            CASE
                WHEN ISNULL(@tiempoExtra, 0) = 1 AND SUBSTRING(@diasTiempoExtra, 7, 1) = '0' THEN NULL
                WHEN sal.SalarioDiarioNum IS NULL THEN NULL
                WHEN domh.DomingoHoras >= 1 AND domh.DomingoHoras <= 9
                    THEN '|1.1|113,' + CAST(CONVERT(DECIMAL(10,2), ROUND(sal.SalarioDiarioNum * 0.30, 2)) AS VARCHAR)
                WHEN domh.DomingoHoras > 9
                    THEN '|1.1|113,' + CAST(CONVERT(DECIMAL(10,2), ROUND(sal.SalarioDiarioNum / 8 * 12 * 0.30, 2)) AS VARCHAR)
                ELSE NULL
            END
) pdom
-- ============================================================
-- Dia Festivo trabajado (073):
--   1 a 9 horas  : salarioDiario x 2
--   mas de 9     : (salarioDiario/8 x 2) x 12
--   menos de 1 h : 0
-- ============================================================
CROSS APPLY (
    SELECT
        LunesSegCrudo =
            CASE WHEN NULLIF(a.LunesEntrada,'') IS NOT NULL AND NULLIF(a.LunesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.LunesEntrada) IS NOT NULL AND TRY_CONVERT(TIME, a.LunesSalida) IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.LunesEntrada), TRY_CONVERT(TIME, a.LunesSalida))
                 ELSE NULL END,
        MartesSegCrudo =
            CASE WHEN NULLIF(a.MartesEntrada,'') IS NOT NULL AND NULLIF(a.MartesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MartesEntrada) IS NOT NULL AND TRY_CONVERT(TIME, a.MartesSalida) IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.MartesEntrada), TRY_CONVERT(TIME, a.MartesSalida))
                 ELSE NULL END,
        MiercolesSegCrudo =
            CASE WHEN NULLIF(a.MiercolesEntrada,'') IS NOT NULL AND NULLIF(a.MiercolesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.MiercolesEntrada) IS NOT NULL AND TRY_CONVERT(TIME, a.MiercolesSalida) IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.MiercolesEntrada), TRY_CONVERT(TIME, a.MiercolesSalida))
                 ELSE NULL END,
        JuevesSegCrudo =
            CASE WHEN NULLIF(a.JuevesEntrada,'') IS NOT NULL AND NULLIF(a.JuevesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.JuevesEntrada) IS NOT NULL AND TRY_CONVERT(TIME, a.JuevesSalida) IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.JuevesEntrada), TRY_CONVERT(TIME, a.JuevesSalida))
                 ELSE NULL END,
        ViernesSegCrudo =
            CASE WHEN NULLIF(a.ViernesEntrada,'') IS NOT NULL AND NULLIF(a.ViernesSalida,'') IS NOT NULL
                  AND TRY_CONVERT(TIME, a.ViernesEntrada) IS NOT NULL AND TRY_CONVERT(TIME, a.ViernesSalida) IS NOT NULL
                 THEN DATEDIFF(SECOND, TRY_CONVERT(TIME, a.ViernesEntrada), TRY_CONVERT(TIME, a.ViernesSalida))
                 ELSE NULL END
) diascrudo
-- "< 0" estricto: Entrada=Salida (checada eco) no es cruce de medianoche.
CROSS APPLY (
    SELECT
        LunesSeg =
            CASE WHEN diascrudo.LunesSegCrudo IS NULL THEN 0
                 WHEN diascrudo.LunesSegCrudo > 0 THEN diascrudo.LunesSegCrudo
                 WHEN diascrudo.LunesSegCrudo < 0 AND ISNULL(a.tipoHorarioLunes,0) = 1 THEN diascrudo.LunesSegCrudo + 86400
                 ELSE 0 END,
        MartesSeg =
            CASE WHEN diascrudo.MartesSegCrudo IS NULL THEN 0
                 WHEN diascrudo.MartesSegCrudo > 0 THEN diascrudo.MartesSegCrudo
                 WHEN diascrudo.MartesSegCrudo < 0 AND ISNULL(a.tipoHorarioMartes,0) = 1 THEN diascrudo.MartesSegCrudo + 86400
                 ELSE 0 END,
        MiercolesSeg =
            CASE WHEN diascrudo.MiercolesSegCrudo IS NULL THEN 0
                 WHEN diascrudo.MiercolesSegCrudo > 0 THEN diascrudo.MiercolesSegCrudo
                 WHEN diascrudo.MiercolesSegCrudo < 0 AND ISNULL(a.tipoHorarioMiercoles,0) = 1 THEN diascrudo.MiercolesSegCrudo + 86400
                 ELSE 0 END,
        JuevesSeg =
            CASE WHEN diascrudo.JuevesSegCrudo IS NULL THEN 0
                 WHEN diascrudo.JuevesSegCrudo > 0 THEN diascrudo.JuevesSegCrudo
                 WHEN diascrudo.JuevesSegCrudo < 0 AND ISNULL(a.tipoHorarioJueves,0) = 1 THEN diascrudo.JuevesSegCrudo + 86400
                 ELSE 0 END,
        ViernesSeg =
            CASE WHEN diascrudo.ViernesSegCrudo IS NULL THEN 0
                 WHEN diascrudo.ViernesSegCrudo > 0 THEN diascrudo.ViernesSegCrudo
                 WHEN diascrudo.ViernesSegCrudo < 0 AND ISNULL(a.tipoHorarioViernes,0) = 1 THEN diascrudo.ViernesSegCrudo + 86400
                 ELSE 0 END
) diasem
CROSS APPLY (
    SELECT
        EsFestivoLunes     = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaLunes     AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoMartes    = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaMartes    AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoMiercoles = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaMiercoles AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoJueves    = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaJueves    AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoViernes   = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaViernes   AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoSabado    = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaSabado    AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoDomingo   = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(s.FechaDomingo   AS DATE)) THEN 1 ELSE 0 END
) esfest
CROSS APPLY (
    SELECT
        MontoFestivoLunes =
            CASE WHEN esfest.EsFestivoLunes = 1 AND diasem.LunesSeg / 3600.0 >= 1 AND diasem.LunesSeg / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoLunes = 1 AND diasem.LunesSeg / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoMartes =
            CASE WHEN esfest.EsFestivoMartes = 1 AND diasem.MartesSeg / 3600.0 >= 1 AND diasem.MartesSeg / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoMartes = 1 AND diasem.MartesSeg / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoMiercoles =
            CASE WHEN esfest.EsFestivoMiercoles = 1 AND diasem.MiercolesSeg / 3600.0 >= 1 AND diasem.MiercolesSeg / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoMiercoles = 1 AND diasem.MiercolesSeg / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoJueves =
            CASE WHEN esfest.EsFestivoJueves = 1 AND diasem.JuevesSeg / 3600.0 >= 1 AND diasem.JuevesSeg / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoJueves = 1 AND diasem.JuevesSeg / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoViernes =
            CASE WHEN esfest.EsFestivoViernes = 1 AND diasem.ViernesSeg / 3600.0 >= 1 AND diasem.ViernesSeg / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoViernes = 1 AND diasem.ViernesSeg / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoSabado =
            CASE WHEN esfest.EsFestivoSabado = 1 AND dtc.SabadoSegundos / 3600.0 >= 1 AND dtc.SabadoSegundos / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoSabado = 1 AND dtc.SabadoSegundos / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END,
        MontoFestivoDomingo =
            CASE WHEN esfest.EsFestivoDomingo = 1 AND dtc.DomingoSegundos / 3600.0 >= 1 AND dtc.DomingoSegundos / 3600.0 <= 9 THEN sal.SalarioDiarioNum * 2
                 WHEN esfest.EsFestivoDomingo = 1 AND dtc.DomingoSegundos / 3600.0 > 9 THEN (sal.SalarioDiarioNum / 8 * 2) * 12
                 ELSE 0 END
) montofest
CROSS APPLY (
    SELECT
        DiaFestivoTotal =
            ISNULL(montofest.MontoFestivoLunes,0) + ISNULL(montofest.MontoFestivoMartes,0) + ISNULL(montofest.MontoFestivoMiercoles,0)
          + ISNULL(montofest.MontoFestivoJueves,0) + ISNULL(montofest.MontoFestivoViernes,0) + ISNULL(montofest.MontoFestivoSabado,0)
          + ISNULL(montofest.MontoFestivoDomingo,0)
) festsum
CROSS APPLY (
    SELECT
        DiaFestivoCalc =
            CASE WHEN sal.SalarioDiarioNum IS NOT NULL AND festsum.DiaFestivoTotal > 0
                 THEN '|1.1|073,' + CAST(CONVERT(DECIMAL(10,2), ROUND(festsum.DiaFestivoTotal, 2)) AS VARCHAR)
                 ELSE NULL END
) fest;
-- =========================================================================
-- RAMA 2: empleados SIN checadas esa semana (sin fila en asistenciaBioStar)
-- =========================================================================
SET NOCOUNT ON;
INSERT INTO #tablaExportar
SELECT DISTINCT
 e.empleado_id
,e.[NOMBRE_COMPLETO]
,empresa_id
,0  as [RetardoSemanal]
,1  as [FaltaLunes]
,1  as [FaltaMartes]
,1  as [FaltaMiercoles]
,1  as [FaltaJueves]
,1  as [FaltaViernes]
,0  as [FaltaSabado]
,0  as [FaltaDomingo]
-- Festivo no trabajado no cuenta como falta.
-- AJUSTE 2026-09-30 (solo Torreon): un dia L-V con incidencia con goce de
-- sueldo (vacacion, permiso con goce) tampoco cuenta como falta. En las
-- demas plantas se mantiene el criterio anterior. La incapacidad (sin
-- goce) sigue contando como falta.
-- AJUSTE 2026-10-06: para TODAS las plantas, un dia L-V con incidencia con
-- goce de sueldo (vacacion, permiso con goce) no cuenta como falta para
-- empleados sin checadas -- mismo criterio que ya se usa para quienes si
-- checan (EsVacX). La incapacidad (sin goce) sigue contando como falta.
,(5
   - (CASE WHEN esfest2.EsFestivoLunes=1     OR goce2.EsGoceLunes=1     THEN 1 ELSE 0 END)
   - (CASE WHEN esfest2.EsFestivoMartes=1    OR goce2.EsGoceMartes=1    THEN 1 ELSE 0 END)
   - (CASE WHEN esfest2.EsFestivoMiercoles=1 OR goce2.EsGoceMiercoles=1 THEN 1 ELSE 0 END)
   - (CASE WHEN esfest2.EsFestivoJueves=1    OR goce2.EsGoceJueves=1    THEN 1 ELSE 0 END)
   - (CASE WHEN esfest2.EsFestivoViernes=1   OR goce2.EsGoceViernes=1   THEN 1 ELSE 0 END)
 )  as FaltasTotales
,0  as LunesTiempoExtra
,0  as MartesTiempoExtra
,0  as MiercolesTiempoExtra
,0  as JuevesTiempoExtra
,0  as ViernesTiempoExtra
,0  as SabadoTiempoExtra
,0  as DomingoTiempoExtra
,'00' as TotalTiempoExtra
,'00' as TotalTiempoExtraTriple
,'00' as TotalhorasExtra
,'00' as TotalminutosExtra
,'00' as TotalsegundosExtra
,'00' as TotalhorasExtraTriple
,'00' as TotalMinutosExtraTriple
,'00' as TotalSegundosExtraTriple
-- Premio por Puntualidad si L-V estan cubiertos por vacacion o festivo
,isnull(case when (
      (CASE WHEN esfest2.EsFestivoLunes=1 OR EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
            AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1) AND f.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin)
       THEN 0 ELSE 1 END)
     +(CASE WHEN esfest2.EsFestivoMartes=1 OR EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
            AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1) AND f.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin)
       THEN 0 ELSE 1 END)
     +(CASE WHEN esfest2.EsFestivoMiercoles=1 OR EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
            AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1) AND f.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin)
       THEN 0 ELSE 1 END)
     +(CASE WHEN esfest2.EsFestivoJueves=1 OR EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
            AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1) AND f.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin)
       THEN 0 ELSE 1 END)
     +(CASE WHEN esfest2.EsFestivoViernes=1 OR EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
            AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1) AND f.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin)
       THEN 0 ELSE 1 END)
  )=0 then '|1.1|061,1' else '' end,'') as PremioporPuntualidad
,NULL as DescansoTrabajado
,NULL as PrimaDominical
,NULL as DiaFestivo
,'' as PremioporAsistencia
,@NoSemana as [NoSemana]
,@anio as anio
,CASE WHEN esfest2.EsFestivoLunes=1    THEN NULL ELSE F.FechaLunes    END as FechaFaltaLunes
,CASE WHEN esfest2.EsFestivoMartes=1   THEN NULL ELSE F.FechaMartes   END as FechaFaltaMartes
,CASE WHEN esfest2.EsFestivoMiercoles=1 THEN NULL ELSE F.FechaMiercoles END as FechaFaltaMiercoles
,CASE WHEN esfest2.EsFestivoJueves=1   THEN NULL ELSE F.FechaJueves   END as FechaFaltaJueves
,CASE WHEN esfest2.EsFestivoViernes=1  THEN NULL ELSE F.FechaViernes  END as FechaFaltaViernes
,NULL as FechaFaltaSabado
,NULL as FechaFaltaDomingo
-- AJUSTE 2026-10-15: Torreon ya NO descuenta vacaciones del TXT (ver nota arriba).
-- El dia de vacacion va dentro de los dias del horario (6); Microsip no suma vacaciones aparte.
,CASE WHEN @esTorreon = 1
      THEN ISNULL(he2.cantidad, 6)
      ELSE 6
 END as diasLaborales
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadLunes
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadMartes
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadMiercoles
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadJueves
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadViernes
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaSabado BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadSabado
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 0
          AND f.FechaDomingo BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END as EsIncapacidadDomingo
      ,ISNULL((
          SELECT sum(inc.cantidadDias) FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo=1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND (inc.fechaInicio >= f.FechaLunes
          AND inc.fechaFin <= f.FechaDomingo
          )
      ), 0) as DiasVacaciones
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaSabado BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
      ,CASE WHEN EXISTS (
          SELECT 1 FROM incidencias inc
          JOIN conceptos c ON inc.concepto_id = c.id
          WHERE inc.empleado_id = CAST(e.empleado_id AS INT)
          AND c.goceSueldo = 1
          AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
          AND f.FechaDomingo BETWEEN inc.fechaInicio AND inc.fechaFin
      ) THEN 1 ELSE 0 END
FROM empleados as e
CROSS APPLY (
    SELECT
        MondayTarget = DATEADD(
            WEEK, @NoSemana - 1,
            DATEADD(
                DAY,
                1 - ((DATEPART(WEEKDAY, DATEFROMPARTS(@anio,1,4)) + @@DATEFIRST - 2) % 7 + 1),
                DATEFROMPARTS(@anio,1,4)
            )
        )
) m
CROSS APPLY (
    SELECT
        FechaLunes      = m.MondayTarget,
        FechaMartes     = DATEADD(DAY, 1, m.MondayTarget),
        FechaMiercoles  = DATEADD(DAY, 2, m.MondayTarget),
        FechaJueves     = DATEADD(DAY, 3, m.MondayTarget),
        FechaViernes    = DATEADD(DAY, 4, m.MondayTarget),
        FechaSabado     = DATEADD(DAY, 5, m.MondayTarget),
        FechaDomingo    = DATEADD(DAY, 6, m.MondayTarget)
) f
CROSS APPLY (
    SELECT
        EsFestivoLunes     = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(f.FechaLunes     AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoMartes    = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(f.FechaMartes    AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoMiercoles = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(f.FechaMiercoles AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoJueves    = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(f.FechaJueves    AS DATE)) THEN 1 ELSE 0 END,
        EsFestivoViernes   = CASE WHEN EXISTS (SELECT 1 FROM dbo.DiasFestivos df WHERE df.fecha = CAST(f.FechaViernes   AS DATE)) THEN 1 ELSE 0 END
) esfest2
-- AJUSTE 2026-09-30: incidencia con goce de sueldo por dia L-V
-- (mismo criterio que EsVacX: goceSueldo=1, sin "retardo ruta transporte")
CROSS APPLY (
    SELECT
        EsGoceLunes     = CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
                            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
                              AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
                              AND f.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin) THEN 1 ELSE 0 END,
        EsGoceMartes    = CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
                            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
                              AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
                              AND f.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin) THEN 1 ELSE 0 END,
        EsGoceMiercoles = CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
                            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
                              AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
                              AND f.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin) THEN 1 ELSE 0 END,
        EsGoceJueves    = CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
                            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
                              AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
                              AND f.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin) THEN 1 ELSE 0 END,
        EsGoceViernes   = CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON inc.concepto_id = c.id
                            WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.goceSueldo = 1
                              AND (inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1)
                              AND f.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin) THEN 1 ELSE 0 END
) goce2
-- AJUSTE 2026-09-30: horario vigente (cantidad de dias y dias laborales)
OUTER APPLY (
    SELECT TOP 1 he.cantidad,
           he.idhorario_lunes, he.idhorario_martes, he.idhorario_miercoles, he.idhorario_jueves,
           he.idhorario_viernes, he.idhorario_sabado, he.idhorario_domingo
    FROM horarios_empleado he
    WHERE he.empleado_id = CAST(e.empleado_id AS INT)
      AND he.fechaAsignacion <= f.FechaDomingo
      AND he.empresa_id = @planta
      AND he.idEstatus = 1          -- AJUSTE 2026-09-30: solo asignacion activa
    ORDER BY he.fechaAsignacion DESC, he.id DESC
) he2
-- AJUSTE 2026-09-30: dias de vacaciones (VAC) en dias laborales del horario
CROSS APPLY (
    SELECT DiasVacLaborables =
          (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_lunes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_martes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_miercoles), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_jueves), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_viernes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaSabado BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_sabado), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = CAST(e.empleado_id AS INT) AND c.descripcionCorta = @ConceptoVacaciones
                               AND f.FechaDomingo BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = he2.idhorario_domingo), 0) > 0
                THEN 1 ELSE 0 END)
) vacdias2
WHERE e.empresa_id = @planta
AND e.ESTATUS = 'A'
AND NOT EXISTS (SELECT 1 FROM #tablaExportar t WHERE t.Numero = CAST(e.empleado_id AS INT))
-- AJUSTE 13 (SOLO TORREON): faltas proporcionales en formato DDHMM
CREATE TABLE #FaltasTorreon (Numero INT PRIMARY KEY, Texto VARCHAR(5) NOT NULL);
IF @esTorreon = 1
BEGIN
    DECLARE @HorasJornadaTorreon INT = 8;
    DECLARE @DomingoSemana DATE = DATEADD(DAY, 6, DATEADD(WEEK, @NoSemana - 1,
            DATEADD(DAY, 1 - ((DATEPART(WEEKDAY, DATEFROMPARTS(@anio,1,4)) + @@DATEFIRST - 2) % 7 + 1), DATEFROMPARTS(@anio,1,4))));
    INSERT INTO #FaltasTorreon (Numero, Texto)
    SELECT t.Numero,
           RIGHT('00' + CAST(mm.minutos / (@HorasJornadaTorreon * 60) AS VARCHAR(3)), 2)
         + CAST((mm.minutos % (@HorasJornadaTorreon * 60)) / 60 AS VARCHAR(1))
         + RIGHT('00' + CAST(mm.minutos % 60 AS VARCHAR(2)), 2)
    FROM #tablaExportar t
    OUTER APPLY (
        SELECT TOP 1 he.cantidad, he.idhorario_lunes, he.idhorario_martes, he.idhorario_miercoles, he.idhorario_jueves,
                     he.idhorario_viernes, he.idhorario_sabado, he.idhorario_domingo
        FROM horarios_empleado he
        WHERE he.empleado_id = CAST(t.Numero AS VARCHAR(50)) AND he.empresa_id = @planta
          AND he.idEstatus = 1 AND he.fechaAsignacion <= @DomingoSemana
        ORDER BY he.fechaAsignacion DESC, he.id DESC
    ) h0
    -- AJUSTE 2026-10-08: sin horario activo en esta planta (p.ej. el 110811, que es otra persona en
    -- CUENCAME) se toma el horario con el que se proceso la semana (asistenciaBioStar); antes el
    -- factor quedaba en 1 y la falta salia 01000 en vez de 01136.
    OUTER APPLY (
        SELECT TOP 1 ad.idhorario_Lunes, ad.idhorario_Martes, ad.idhorario_Miercoles, ad.idhorario_Jueves,
                     ad.idhorario_Viernes, ad.idhorario_Sabado, ad.idhorario_Domingo
        FROM #AsistenciaDedup ad
        WHERE h0.cantidad IS NULL AND ad.empresa_id = @planta AND LTRIM(RTRIM(ad.Numero)) = CAST(t.Numero AS VARCHAR(50))
    ) ab
    CROSS APPLY (
        SELECT cantidad            = h0.cantidad,
               idhorario_lunes     = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Lunes     ELSE h0.idhorario_lunes     END,
               idhorario_martes    = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Martes    ELSE h0.idhorario_martes    END,
               idhorario_miercoles = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Miercoles ELSE h0.idhorario_miercoles END,
               idhorario_jueves    = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Jueves    ELSE h0.idhorario_jueves    END,
               idhorario_viernes   = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Viernes   ELSE h0.idhorario_viernes   END,
               idhorario_sabado    = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Sabado    ELSE h0.idhorario_sabado    END,
               idhorario_domingo   = CASE WHEN h0.cantidad IS NULL THEN ab.idhorario_Domingo   ELSE h0.idhorario_domingo   END
    ) h
    CROSS APPLY (
        SELECT diasTrab =
              (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_lunes), 0)     > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_martes), 0)    > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_miercoles), 0) > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_jueves), 0)    > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_viernes), 0)   > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_sabado), 0)    > 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = h.idhorario_domingo), 0)   > 0 THEN 1 ELSE 0 END),
               incap =
              (CASE WHEN ISNULL(t.EsIncapacidadLunes, 0)     = 1 AND ISNULL(t.EsVacLunes, 0)     = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadMartes, 0)    = 1 AND ISNULL(t.EsVacMartes, 0)    = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadMiercoles, 0) = 1 AND ISNULL(t.EsVacMiercoles, 0) = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadJueves, 0)    = 1 AND ISNULL(t.EsVacJueves, 0)    = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadViernes, 0)   = 1 AND ISNULL(t.EsVacViernes, 0)   = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadSabado, 0)    = 1 AND ISNULL(t.EsVacSabado, 0)    = 0 THEN 1 ELSE 0 END)
            + (CASE WHEN ISNULL(t.EsIncapacidadDomingo, 0)   = 1 AND ISNULL(t.EsVacDomingo, 0)   = 0 THEN 1 ELSE 0 END)
    ) d
    CROSS APPLY (
        SELECT factor = CASE WHEN d.diasTrab > 0 AND ISNULL(h.cantidad, 6) > d.diasTrab
                             THEN CAST(ISNULL(h.cantidad, 6) AS DECIMAL(9,4)) / d.diasTrab ELSE 1 END,
               reales = CASE WHEN t.FaltasTotales - d.incap > 0 THEN t.FaltasTotales - d.incap ELSE 0 END,
               incapC = CASE WHEN d.incap < t.FaltasTotales THEN d.incap ELSE t.FaltasTotales END
    ) f
    CROSS APPLY (
        -- tope: las faltas no pasan de los dias que se trabajan (faltar toda la semana = 5, no 6;
        -- Microsip agrega el septimo dia)
        SELECT minutos = CAST(ROUND((CASE WHEN d.diasTrab > 0 AND f.reales * f.factor > d.diasTrab THEN d.diasTrab ELSE f.reales * f.factor END
                                     + f.incapC) * @HorasJornadaTorreon * 60, 0) AS INT)
    ) mm
    WHERE ISNULL(t.FaltasTotales, 0) > 0;
END

-- TABLA FINAL
DECLARE @ASISTENCIAEXPORTARLISTO TABLE(
    Numero INT, PrimerRenglon VARCHAR(MAX), PremioporAsistencia VARCHAR(MAX), PremioporPuntualidad VARCHAR(MAX),
    DescansoTrabajado VARCHAR(MAX), PrimaDominical VARCHAR(MAX), DiaFestivo VARCHAR(MAX),
    LunesFalta VARCHAR(MAX), MartesFalta VARCHAR(MAX), MiercolesFalta VARCHAR(MAX), JuevesFalta VARCHAR(MAX),
    ViernesFalta VARCHAR(MAX), SabadoFalta VARCHAR(MAX), DomingoFalta VARCHAR(MAX)
)
if @tiempoExtra=1
begin
    -- DescansoTrabajado (078) solo cuando @tiempoExtra=1
    INSERT INTO @ASISTENCIAEXPORTARLISTO
    SELECT
        s.Numero,
         isnull(try_cast('|1|' + CAST(s.Numero AS VARCHAR) + ',' + isnull(FORMAT(CASE WHEN @esTorreon = 1 THEN ROUND(s.diasLaborales, 0) ELSE s.diasLaborales END, '0.##', 'en-US'),'06') + ',' +
        ISNULL((SELECT ft.Texto FROM #FaltasTorreon ft WHERE ft.Numero = s.Numero),
           RIGHT('00' + CAST(s.FaltasTotales AS VARCHAR), 2) + '000') + ','   -- AJUSTE 13
        + case when  RIGHT('00' + try_cast((s.LunesTiempoExtra+s.MartesTiempoExtra+s.MiercolesTiempoExtra+s.JuevesTiempoExtra+s.ViernesTiempoExtra) AS VARCHAR), 2) ='00' then  '0000' else  s.TotalhorasExtra+s.TotalminutosExtra  end + ',' +
        -- Sabado y Domingo excluidos del conteo de dias con TE (van por 078)
        RIGHT('00' + try_cast((s.LunesTiempoExtra+s.MartesTiempoExtra+s.MiercolesTiempoExtra+s.JuevesTiempoExtra+s.ViernesTiempoExtra) AS VARCHAR), 2)
        + ',0000,00' as varchar(max))
        ,'|1|'+ CAST(s.Numero AS VARCHAR) +',06,00000,0000,00,0000,00' )
        as PrimerRenglon,
        s.PremioporAsistencia,   -- en Torreon sale como 009 (ver ajuste 11 abajo)
        s.PremioporPuntualidad,
        s.DescansoTrabajado,
        s.PrimaDominical,
        s.DiaFestivo,
        CASE WHEN s.EsVacLunes    =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadLunes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaLunes IS NOT NULL         THEN '|1.2|'+FORMAT(s.FechaFaltaLunes,    'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacMartes   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadMartes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaMartes IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaMartes,   'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacMiercoles=1 THEN NULL ELSE CASE WHEN s.EsIncapacidadMiercoles = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaMiercoles IS NOT NULL THEN '|1.2|'+FORMAT(s.FechaFaltaMiercoles,'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacJueves   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadJueves = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaJueves IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaJueves,   'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacViernes  =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadViernes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaViernes IS NOT NULL     THEN '|1.2|'+FORMAT(s.FechaFaltaViernes,  'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacSabado   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadSabado = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaSabado IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaSabado,   'dd/MM/yyyy') END END END,
        CASE WHEN s.EsVacDomingo  =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadDomingo = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaDomingo IS NOT NULL     THEN '|1.2|'+FORMAT(s.FechaFaltaDomingo,  'dd/MM/yyyy') END END END
    FROM #tablaExportar s
end
else if @tiempoExtra=0
begin
INSERT INTO @ASISTENCIAEXPORTARLISTO
SELECT
    s.Numero,
     isnull(try_cast('|1|' + CAST(s.Numero AS VARCHAR) + ',' + isnull(FORMAT(CASE WHEN @esTorreon = 1 THEN ROUND(s.diasLaborales, 0) ELSE s.diasLaborales END, '0.##', 'en-US'),'06') + ',' +
    ISNULL((SELECT ft.Texto FROM #FaltasTorreon ft WHERE ft.Numero = s.Numero),
           RIGHT('00' + CAST(s.FaltasTotales AS VARCHAR), 2) + '000') + ','   -- AJUSTE 13
    + '0000,00' + ',0000,00' as varchar(max))
    ,'|1|'+ CAST(s.Numero AS VARCHAR) +',06,00000,0000,00,0000,00' )
    as PrimerRenglon,
    s.PremioporAsistencia,   -- en Torreon sale como 009 (ver ajuste 11 abajo)
    s.PremioporPuntualidad,
    -- Con @tiempoExtra=0 NO se reportan ni DescansoTrabajado (078) ni Prima Dominical (113/090):
    -- esos conceptos solo salen cuando se marca Tiempo Extra y el día (el domingo) como extra.
    NULL,                 -- DescansoTrabajado (078)
    NULL,                 -- PrimaDominical: si no seleccionan el domingo como extra, no sale
    s.DiaFestivo,         -- DiaFestivo (073) se sigue reportando siempre
    CASE WHEN s.EsVacLunes    =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadLunes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaLunes IS NOT NULL         THEN '|1.2|'+FORMAT(s.FechaFaltaLunes,    'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacMartes   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadMartes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaMartes IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaMartes,   'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacMiercoles=1 THEN NULL ELSE CASE WHEN s.EsIncapacidadMiercoles = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaMiercoles IS NOT NULL THEN '|1.2|'+FORMAT(s.FechaFaltaMiercoles,'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacJueves   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadJueves = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaJueves IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaJueves,   'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacViernes  =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadViernes = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaViernes IS NOT NULL     THEN '|1.2|'+FORMAT(s.FechaFaltaViernes,  'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacSabado   =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadSabado = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaSabado IS NOT NULL       THEN '|1.2|'+FORMAT(s.FechaFaltaSabado,   'dd/MM/yyyy') END END END,
    CASE WHEN s.EsVacDomingo  =1 THEN NULL ELSE CASE WHEN s.EsIncapacidadDomingo = 1 THEN NULL ELSE CASE WHEN s.FechaFaltaDomingo IS NOT NULL     THEN '|1.2|'+FORMAT(s.FechaFaltaDomingo,  'dd/MM/yyyy') END END END
FROM #tablaExportar s
end

-- AJUSTE 2026-10-08: claves de concepto de Microsip por empresa (dbo.TxtConceptoClave, se eligen en
-- Catalogos > Conceptos Microsip). Arriba se arman con las claves de siempre (061/062/078/113/073)
-- y aqui se cambian por las de la empresa. Sustituye el ajuste fijo de Torreon (008/009/090), que
-- quedo como valor de esas empresas en la tabla.
DECLARE @clvPunt VARCHAR(20) = ISNULL(dbo.fnTxtClave('PUNTUALIDAD', TRY_CAST(@planta AS INT)), '061'),
        @clvAsis VARCHAR(20) = ISNULL(dbo.fnTxtClave('ASISTENCIA', TRY_CAST(@planta AS INT)), '062'),
        @clvDesc VARCHAR(20) = ISNULL(dbo.fnTxtClave('DESCANSO_TRABAJADO', TRY_CAST(@planta AS INT)), '078'),
        @clvPDom VARCHAR(20) = ISNULL(dbo.fnTxtClave('PRIMA_DOMINICAL', TRY_CAST(@planta AS INT)), '113'),
        @clvFest VARCHAR(20) = ISNULL(dbo.fnTxtClave('DIA_FESTIVO', TRY_CAST(@planta AS INT)), '073');
UPDATE @ASISTENCIAEXPORTARLISTO
SET PremioporPuntualidad = REPLACE(PremioporPuntualidad, '|1.1|061,', '|1.1|' + @clvPunt + ','),
    PremioporAsistencia  = REPLACE(PremioporAsistencia,  '|1.1|062,', '|1.1|' + @clvAsis + ','),
    DescansoTrabajado    = REPLACE(DescansoTrabajado,    '|1.1|078,', '|1.1|' + @clvDesc + ','),
    PrimaDominical       = REPLACE(PrimaDominical,       '|1.1|113,', '|1.1|' + @clvPDom + ','),
    DiaFestivo           = REPLACE(DiaFestivo,           '|1.1|073,', '|1.1|' + @clvFest + ',');

delete FROM asistenciaExportar
WHERE
Semana=@NoSemana
AND  empresa_id=@planta;
WITH CTE_Duplicados AS (
SELECT
*,
ROW_NUMBER() OVER (PARTITION BY a.Numero, a.anio, a.NoSemana, a.empresa_id ORDER BY (SELECT NULL)) AS NumFila
FROM asistenciaBioStar as a
)
DELETE FROM CTE_Duplicados
WHERE NumFila > 1;
INSERT INTO asistenciaExportar
    (Numero, PrimerRenglon, PremioporAsistencia, PremioporPuntualidad, DescansoTrabajado, PrimaDominical, DiaFestivo,
     LunesFalta, MartesFalta, MiercolesFalta, JuevesFalta, ViernesFalta, SabadoFalta, DomingoFalta,
     Semana, empresa_id)
SELECT DISTINCT Numero, PrimerRenglon, PremioporAsistencia, PremioporPuntualidad, DescansoTrabajado, PrimaDominical, DiaFestivo,
       LunesFalta, MartesFalta, MiercolesFalta, JuevesFalta, ViernesFalta, SabadoFalta, DomingoFalta,
       @NoSemana, @planta
FROM @ASISTENCIAEXPORTARLISTO ORDER BY Numero;
END
GO
