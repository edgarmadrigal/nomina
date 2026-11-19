-- =============================================================================
-- PRENÓMINA — Fase 1 (núcleo automático)  2026-10-09
--
-- Reproduce en el sistema el cálculo que el de nóminas hace a mano en el Excel
-- "NOM <planta>" (hoja PRENOMINA): por cada empleado ACTIVO (ESTATUS='A') de la
-- planta, en una semana, toma lo que el sistema YA procesó (faltas, retardos,
-- tiempo extra, en dbo.asistenciaBioStar) + el salario diario (dbo.empleados.
-- salarioDiario = "SD Micro") y calcula sueldo, premios, pago de tiempo extra,
-- descuentos por falta/retardo y el TOTAL A PAGAR.
--
-- Las checadas salen de los relojes (asistenciaBioStar), así que los números
-- pueden diferir del Excel viejo (que usaba checadas pegadas de RH/Nomiplus).
--
-- Las constantes CAMBIAN por planta -> viven en dbo.PrenominaConfig (una fila por
-- empresa). Sembrado para empresa 8 (PROCESOS_SECOS = "SB1") con los valores del
-- Excel: premio 29.63 (se paga x2), falta = SD*1.4, T.E. L-V 200/2.5 por hora,
-- sábado y domingo 400/6 por hora, tope de 9 h de T.E. doble.
--
-- Conceptos manuales / de Microsip / de producción (prima vacacional, fondo de
-- ahorro, prima dominical, festivo, defunción, bono calidad, producción, horas
-- rebajadas) NO entran en Fase 1: se agregan en fases 2-4. Aquí van en 0.
-- =============================================================================
USE [Nominas];
GO

-- --------------------------------------------------------------------------
-- Constantes por planta
-- --------------------------------------------------------------------------
IF OBJECT_ID('dbo.PrenominaConfig') IS NULL
CREATE TABLE dbo.PrenominaConfig (
    empresa_id    INT           NOT NULL PRIMARY KEY,
    premio        DECIMAL(12,4) NOT NULL CONSTRAINT DF_PreCfg_premio   DEFAULT 29.63,   -- premio puntualidad/asistencia (se paga x2; se quita x1 por cada retardo o falta)
    factorFalta   DECIMAL(12,4) NOT NULL CONSTRAINT DF_PreCfg_factor   DEFAULT 1.4,     -- cada falta descuenta SD * factorFalta
    tarifaTE_LV   DECIMAL(12,6) NOT NULL CONSTRAINT DF_PreCfg_telv      DEFAULT 80.0,        -- 200/2.5 por hora extra L-V
    tarifaTE_Sab  DECIMAL(12,6) NOT NULL CONSTRAINT DF_PreCfg_tesab     DEFAULT 66.666667,   -- 400/6 por hora extra sábado
    tarifaTE_Dom  DECIMAL(12,6) NOT NULL CONSTRAINT DF_PreCfg_tedom     DEFAULT 66.666667,   -- por hora extra domingo
    diasSemana    INT           NOT NULL CONSTRAINT DF_PreCfg_dias      DEFAULT 7,       -- sueldo para falta = SD * diasSemana
    topeTEDoble   DECIMAL(12,4) NOT NULL CONSTRAINT DF_PreCfg_topete    DEFAULT 9.0,     -- tope de horas para el T.E. doble
    activo        BIT           NOT NULL CONSTRAINT DF_PreCfg_activo     DEFAULT 1
);
GO
IF NOT EXISTS (SELECT 1 FROM dbo.PrenominaConfig WHERE empresa_id = 8)
    INSERT INTO dbo.PrenominaConfig (empresa_id) VALUES (8);   -- SB1 = PROCESOS_SECOS, valores por defecto (los del Excel)
GO

-- --------------------------------------------------------------------------
-- Conceptos capturados / importados por empleado y semana (Fase 2 y 3):
-- prima vacacional, fondo de ahorro, prima dominical, festivo, defunción
-- (captura manual o traídos de Microsip), producción (importada del Excel de
-- la planta), bono de calidad y horas rebajadas (para el descuento de retardos).
-- --------------------------------------------------------------------------
IF OBJECT_ID('dbo.PrenominaCaptura') IS NULL
CREATE TABLE dbo.PrenominaCaptura (
    empresa_id       INT          NOT NULL,
    anio             INT          NOT NULL,
    NoSemana         INT          NOT NULL,
    empleado_id      VARCHAR(20)  NOT NULL,
    primaVacacional  DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_pv  DEFAULT 0,
    fondoAhorro      DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_fa  DEFAULT 0,
    primaDominical   DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_pd  DEFAULT 0,
    festivo          DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_fe  DEFAULT 0,
    defuncion        DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_de  DEFAULT 0,
    produccion       DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_pr  DEFAULT 0,
    bonoCalidad      DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_bc  DEFAULT 0,
    horasRebajadas   DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_hr  DEFAULT 0,
    pagoSistema      DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_ps  DEFAULT 0,   -- lo que paga Microsip, para comparar (Fase 4)
    CONSTRAINT PK_PrenominaCaptura PRIMARY KEY (empresa_id, anio, NoSemana, empleado_id)
);
GO
-- por si la tabla ya existía sin la columna
IF COL_LENGTH('dbo.PrenominaCaptura', 'pagoSistema') IS NULL
    ALTER TABLE dbo.PrenominaCaptura ADD pagoSistema DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_ps DEFAULT 0;
GO
-- produccionGeneral = "PAGO TOTAL GENERAL" del Excel de producción (día+ext+bono). La producción
-- que se paga = produccionGeneral - total base (ver ConsultaPrenomina). La columna vieja 'produccion'
-- queda sin uso.
IF COL_LENGTH('dbo.PrenominaCaptura', 'produccionGeneral') IS NULL
    ALTER TABLE dbo.PrenominaCaptura ADD produccionGeneral DECIMAL(12,2) NOT NULL CONSTRAINT DF_PreCap_pg DEFAULT 0;
GO
-- teHoras = horas de T.E. capturadas a mano (NULL = usar las de los relojes). Editable en la pantalla.
IF COL_LENGTH('dbo.PrenominaCaptura', 'teHoras') IS NULL
    ALTER TABLE dbo.PrenominaCaptura ADD teHoras DECIMAL(12,4) NULL;
GO

-- --------------------------------------------------------------------------
-- Cálculo de la prenómina de una planta en una semana
-- --------------------------------------------------------------------------
CREATE OR ALTER PROCEDURE dbo.ConsultaPrenomina
    @planta   INT,
    @NoSemana INT,
    @anio     INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @premio DECIMAL(12,4), @factorFalta DECIMAL(12,4),
            @teLV DECIMAL(12,6), @teSab DECIMAL(12,6), @teDom DECIMAL(12,6),
            @dias INT, @topeTE DECIMAL(12,4);
    SELECT @premio = premio, @factorFalta = factorFalta, @teLV = tarifaTE_LV,
           @teSab = tarifaTE_Sab, @teDom = tarifaTE_Dom, @dias = diasSemana, @topeTE = topeTEDoble
    FROM dbo.PrenominaConfig WHERE empresa_id = @planta;
    -- Si la planta no tiene config, se usan los valores del Excel por defecto
    IF @premio IS NULL SELECT @premio=29.63, @factorFalta=1.4, @teLV=80.0, @teSab=66.666667, @teDom=66.666667, @dias=7, @topeTE=9.0;

    ;WITH datos AS (
        SELECT
            e.empleado_id                                   AS Numero,
            e.NOMBRE_COMPLETO                               AS Nombre,
            ISNULL(NULLIF(LTRIM(RTRIM(e.NOMBRE_PUESTO)), ''), a.NOMBREPUESTO) AS Puesto,
            CAST(ISNULL(e.salarioDiario, 0) AS DECIMAL(12,2)) AS SD,
            ISNULL(a.RetardoSemanal, 0)                     AS Retardos,
            -- FaltasTotales suele venir NULL; las faltas reales están por día (FaltaLunes..Domingo). AJUSTE 2026-10-09.
            (ISNULL(a.FaltaLunes,0) + ISNULL(a.FaltaMartes,0) + ISNULL(a.FaltaMiercoles,0)
             + ISNULL(a.FaltaJueves,0) + ISNULL(a.FaltaViernes,0) + ISNULL(a.FaltaSabado,0)
             + ISNULL(a.FaltaDomingo,0))                    AS Faltas,
            -- tiempo extra en horas decimales (las columnas son TIME)
            CAST((DATEDIFF(SECOND, 0, ISNULL(a.LunesTiempoExtra,0))
                + DATEDIFF(SECOND, 0, ISNULL(a.MartesTiempoExtra,0))
                + DATEDIFF(SECOND, 0, ISNULL(a.MiercolesTiempoExtra,0))
                + DATEDIFF(SECOND, 0, ISNULL(a.JuevesTiempoExtra,0))
                + DATEDIFF(SECOND, 0, ISNULL(a.ViernesTiempoExtra,0))) / 3600.0 AS DECIMAL(12,4)) AS TE_LV,
            CAST(DATEDIFF(SECOND, 0, ISNULL(a.SabadoTiempoExtra,0))  / 3600.0 AS DECIMAL(12,4)) AS TE_Sab,
            CAST(DATEDIFF(SECOND, 0, ISNULL(a.DomingoTiempoExtra,0)) / 3600.0 AS DECIMAL(12,4)) AS TE_Dom,
            CASE WHEN a.Numero IS NULL THEN 0 ELSE 1 END     AS Procesado,
            -- conceptos capturados / importados (Fase 2 y 3); 0 si no hay captura
            ISNULL(cap.primaVacacional,0) AS PrimaVacacional, ISNULL(cap.fondoAhorro,0) AS FondoAhorro,
            ISNULL(cap.primaDominical,0)  AS PrimaDominical,  ISNULL(cap.festivo,0)     AS Festivo,
            ISNULL(cap.defuncion,0)       AS Defuncion,       ISNULL(cap.produccionGeneral,0) AS ProduccionGeneral,
            ISNULL(cap.bonoCalidad,0)     AS BonoCalidad,     ISNULL(cap.horasRebajadas,0) AS HorasRebajadas,
            ISNULL(cap.pagoSistema,0)     AS PagoSistema,
            cap.teHoras                   AS TeHorasOverride,   -- horas de T.E. capturadas a mano (NULL = automático)
            -- Salida temprana en cualquier día (salida real < salida_Inicio del horario): cancela el
            -- premio de puntualidad, igual que el TXT (ConsultaAsistenciaExportarTablaTemporal). AJUSTE 2026-10-10.
            CASE WHEN TRY_CONVERT(TIME, NULLIF(a.LunesSalida,''))     < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Lunes)
                   OR TRY_CONVERT(TIME, NULLIF(a.MartesSalida,''))    < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Martes)
                   OR TRY_CONVERT(TIME, NULLIF(a.MiercolesSalida,'')) < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Miercoles)
                   OR TRY_CONVERT(TIME, NULLIF(a.JuevesSalida,''))    < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Jueves)
                   OR TRY_CONVERT(TIME, NULLIF(a.ViernesSalida,''))   < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Viernes)
                   OR TRY_CONVERT(TIME, NULLIF(a.SabadoSalida,''))    < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Sabado)
                   OR TRY_CONVERT(TIME, NULLIF(a.DomingoSalida,''))   < (SELECT TOP 1 salida_Inicio FROM dbo.horarios WHERE id = a.idhorario_Domingo)
                 THEN 1 ELSE 0 END                             AS SalidaTemprana
        FROM dbo.empleados e
        LEFT JOIN dbo.asistenciaBioStar a
               ON a.Numero COLLATE DATABASE_DEFAULT = e.empleado_id COLLATE DATABASE_DEFAULT
              AND a.empresa_id = e.empresa_id
              AND a.NoSemana = @NoSemana AND a.anio = @anio
        LEFT JOIN dbo.PrenominaCaptura cap
               ON cap.empresa_id = e.empresa_id AND cap.anio = @anio AND cap.NoSemana = @NoSemana
              AND cap.empleado_id COLLATE DATABASE_DEFAULT = e.empleado_id COLLATE DATABASE_DEFAULT
        WHERE e.empresa_id = @planta AND e.ESTATUS = 'A'
    ),
    calc AS (
        SELECT d.*,
            CAST(SD * @dias AS DECIMAL(12,2))                AS SueldoFalta,   -- E
            CAST(@premio * 2 AS DECIMAL(12,2))               AS Premio,        -- F
            -- T.E. total (AQ): si hay horas capturadas a mano (TeHorasOverride) se usan esas; si no, la
            -- suma de los relojes, pero si es MENOR a 1 hora no cuenta (AJUSTE 2026-10-09/10).
            CAST(CASE WHEN d.TeHorasOverride IS NOT NULL THEN d.TeHorasOverride
                      WHEN (TE_LV + TE_Sab + TE_Dom) >= 1 THEN (TE_LV + TE_Sab + TE_Dom) ELSE 0 END AS DECIMAL(12,4)) AS TE_Total,
            -- Pago T.E.: con override, todo a tarifa L-V; si no, por componente (0 si < 1 h).
            CAST(CASE WHEN d.TeHorasOverride IS NOT NULL THEN @teLV * d.TeHorasOverride
                      WHEN (TE_LV+TE_Sab+TE_Dom) >= 1 THEN @teLV  * TE_LV  ELSE 0 END AS DECIMAL(12,2)) AS PagoTE_LV,   -- AR
            CAST(CASE WHEN d.TeHorasOverride IS NOT NULL THEN 0
                      WHEN (TE_LV+TE_Sab+TE_Dom) >= 1 THEN @teSab * TE_Sab ELSE 0 END AS DECIMAL(12,2)) AS PagoTE_Sab,  -- AS
            CAST(CASE WHEN d.TeHorasOverride IS NOT NULL THEN 0
                      WHEN (TE_LV+TE_Sab+TE_Dom) >= 1 THEN @teDom * TE_Dom ELSE 0 END AS DECIMAL(12,2)) AS PagoTE_Dom   -- AT
        FROM datos d
    ),
    base AS (
    SELECT
        c.Numero, c.Nombre, c.Puesto, c.SD, c.Procesado,
        c.SueldoFalta,
        c.Premio,
        CAST(c.SueldoFalta + c.Premio AS DECIMAL(12,2))      AS SueldoAutorizado,   -- D
        c.Retardos, c.Faltas,
        c.TE_LV, c.TE_Sab, c.TE_Dom, c.TE_Total,
        c.PagoTE_LV, c.PagoTE_Sab, c.PagoTE_Dom,
        CAST(CASE WHEN c.Retardos >= 1 OR c.SalidaTemprana = 1 THEN -@premio ELSE 0 END AS DECIMAL(12,2))   AS DescPuntualidad,    -- BD (retardo o salida temprana)
        CAST(CASE WHEN c.Faltas   >= 1 THEN -@premio ELSE 0 END AS DECIMAL(12,2))   AS DescAsistencia,     -- BE
        CAST(CASE WHEN c.Faltas  <> 0 THEN -(c.SueldoFalta/7.0)*c.Faltas*@factorFalta ELSE 0 END AS DECIMAL(12,2)) AS DescFalta,  -- BF
        CAST(-(c.SueldoFalta/48.0) * c.HorasRebajadas AS DECIMAL(12,2))              AS MontoRetardos,     -- BG
        c.PrimaVacacional, c.FondoAhorro, c.PrimaDominical, c.Festivo, c.Defuncion, c.BonoCalidad, c.HorasRebajadas,
        c.ProduccionGeneral,
        -- Base para la PRODUCCIÓN = sueldo + premio + pago T.E. - descuentos. NO incluye los
        -- conceptos de Microsip (prima vac, fondo ahorro, etc.) ni bono calidad: esos se suman
        -- aparte al total. La producción pagada = PAGO TOTAL GENERAL - esta base. AJUSTE 2026-10-09.
        CAST(c.SueldoFalta + c.Premio
             + c.PagoTE_LV + c.PagoTE_Sab + c.PagoTE_Dom
             + (CASE WHEN c.Retardos >= 1 OR c.SalidaTemprana = 1 THEN -@premio ELSE 0 END)
             + (CASE WHEN c.Faltas   >= 1 THEN -@premio ELSE 0 END)
             + (CASE WHEN c.Faltas  <> 0 THEN -(c.SueldoFalta/7.0)*c.Faltas*@factorFalta ELSE 0 END)
             + (-(c.SueldoFalta/48.0) * c.HorasRebajadas)
             AS DECIMAL(12,2))                               AS BaseProd,
        c.PagoSistema
    FROM calc c
    ),
    res AS (
    SELECT b.*,
        -- Producción pagada = PAGO TOTAL GENERAL del Excel - base. Si produjo menos que su base, 0
        -- (se le garantiza la base). AJUSTE 2026-10-09.
        CAST(CASE WHEN b.ProduccionGeneral > b.BaseProd THEN b.ProduccionGeneral - b.BaseProd ELSE 0 END AS DECIMAL(12,2)) AS Produccion,
        -- TOTAL A PAGAR = base + producción + conceptos de Microsip + bono calidad
        CAST(b.BaseProd
             + (CASE WHEN b.ProduccionGeneral > b.BaseProd THEN b.ProduccionGeneral - b.BaseProd ELSE 0 END)
             + b.PrimaVacacional + b.FondoAhorro + b.PrimaDominical + b.Festivo + b.Defuncion + b.BonoCalidad
             AS DECIMAL(12,2))                               AS TotalAPagar
    FROM base b
    )
    SELECT res.*,
        -- Diferencia vs lo que paga Microsip, SIN la producción (Microsip no la incluye).
        CAST(res.PagoSistema - (res.TotalAPagar - res.Produccion) AS DECIMAL(12,2)) AS Diferencia
    FROM res
    ORDER BY res.Numero;
END;
GO

-- Opción del menú (Reportes) y permiso para los administradores
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'prenomina')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Reportes', N'Prenómina', 'prenomina', '<i class="fa fa-calculator"></i>', 510, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'prenomina' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO

-- Permiso también para Nóminas (idPerfil = 2) PERO solo los de Torreón (su planta principal
-- es una empresa esTorreon = 1). Los foráneos se agregan después (su prenómina será distinta),
-- desde Catálogos > Usuarios > Permisos, cuando se definan sus conceptos especiales.
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u
  JOIN dbo.empresas e ON e.id = u.idPlanta
  CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'prenomina' AND u.idPerfil = 2 AND e.esTorreon = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
