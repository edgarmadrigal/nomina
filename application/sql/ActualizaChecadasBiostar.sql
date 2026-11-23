USE [Nominas];
GO
-- =============================================
-- Author:      Edgar Madrigal
-- Create date: 2018-11-30
-- Description: Consulta Asistencia
--   EXEC dbo.ActualizaChecadasBiostar 39,'2026','12',NULL,NULL,NULL
--   EXEC dbo.ActualizaChecadasBiostar 39,'2026','12',NULL,NULL,NULL,1,'1111100'
--
-- AJUSTES:
--  2026-09-24  Las checadas se agrupan por DIA DE TURNO, no por dia de
--              calendario. Una checada de madrugada anterior a la
--              salida_Hasta del horario NOCTURNO (tipo=1, que cruza
--              medianoche) del dia anterior pertenece a ese dia anterior.
--              Asi el turno completo (entrada, comedor, salida) queda en
--              su renglon. Entrada = primera checada, Salida = ultima.
--  2026-09-28  Nuevos parametros @tiempoExtra y @diasTiempoExtra
--              (antes estaban en una copia completa del proc,
--              ActualizaChecadasBiostarTiempoExtra, que ahora solo llama
--              a este). @diasTiempoExtra = 7 posiciones L..D, '0' = ese
--              dia no paga tiempo extra.
--  2026-09-28  El INSERT a asistenciaBioStar solo inserta empleados que
--              aun no existen en esa semana/planta (antes insertaba
--              todos en cada corrida y el UPDATE de despues los tocaba).
--  2026-09-28  TotalTiempoExtra en #Semana2 queda en 0: el valor real lo
--              calcula ActualizaChecadasBiostarDias, que corre despues y
--              lo sobreescribe.
-- =============================================
CREATE OR ALTER PROCEDURE [dbo].[ActualizaChecadasBiostar]
    @NoSemana        INT         = NULL,
    @anio            VARCHAR(50) = NULL,
    @planta          VARCHAR(50) = NULL,
    @Code            VARCHAR(50) = NULL,
    @puesto          VARCHAR(50) = NULL,
    @NoEmpleado      VARCHAR(50) = NULL,
    @tiempoExtra     BIT         = 1,
    @diasTiempoExtra VARCHAR(7)  = '1111111'   -- L M Mi J V S D: 1 = si paga, 0 = no
AS
BEGIN
    SET NOCOUNT ON;
    SET DATEFIRST 1;
    BEGIN TRY

    SET @NoSemana = ISNULL(@NoSemana, DATEPART(wk, GETDATE()));
    SET @anio     = ISNULL(@anio, CAST(DATEPART(year, GETDATE()) AS VARCHAR(4)));
    SET @tiempoExtra = ISNULL(@tiempoExtra, 1);
    IF @diasTiempoExtra IS NULL OR LEN(@diasTiempoExtra) <> 7 OR @diasTiempoExtra LIKE '%[^01]%'
        SET @diasTiempoExtra = '1111111';

    IF OBJECT_ID('tempdb..#Semana2')    IS NOT NULL DROP TABLE #Semana2;
    IF OBJECT_ID('tempdb..#Pivotadas')  IS NOT NULL DROP TABLE #Pivotadas;
    IF OBJECT_ID('tempdb..#Semana')     IS NOT NULL DROP TABLE #Semana;
    IF OBJECT_ID('tempdb..#HorEmp')     IS NOT NULL DROP TABLE #HorEmp;
    IF OBJECT_ID('tempdb..#Punches')    IS NOT NULL DROP TABLE #Punches;
    IF OBJECT_ID('tempdb..#PunchDia')   IS NOT NULL DROP TABLE #PunchDia;

    DECLARE @tabla_sp TABLE (FechaInicio VARCHAR(MAX), FechaFin VARCHAR(MAX));
    INSERT INTO @tabla_sp EXEC [dbo].[ConsultaSemana] @NoSemana, @anio;

    DECLARE @FechaInicio VARCHAR(45), @FechaFin VARCHAR(45);
    SELECT TOP 1
        @FechaInicio = FechaInicio,
        @FechaFin    = FechaFin
    FROM @tabla_sp;

    -- =========================================================
    -- Semana (lunes a domingo) segun DATEPART(wk) con DATEFIRST 1
    -- =========================================================
    DECLARE @anioInt   INT  = CAST(@anio AS INT);
    DECLARE @refDate   DATE = DATEFROMPARTS(@anioInt, 1, 1);
    DECLARE @anchor    DATE = DATEADD(week, @NoSemana - 1, @refDate);
    DECLARE @WeekStart DATE = DATEADD(day, 1 - DATEPART(weekday, @anchor), @anchor);  -- lunes
    DECLARE @WeekEnd   DATE = DATEADD(day, 6, @WeekStart);                           -- domingo
    DECLARE @esTorreon BIT = ISNULL((SELECT TOP 1 esTorreon FROM dbo.empresas WHERE id = @planta), 0);
      -- =========================================================
    -- PASO 1: horario vigente por empleado PARA ESTA SEMANA.
    -- AJUSTE 2026-09-30: asignacion activa mas reciente con
    -- fechaAsignacion <= domingo de la semana. Si no hay ninguna
    -- anterior, se toma la primera posterior (para no perder al empleado).
    -- =========================================================
    SELECT empleado_id, fechaAsignacion,
           idhorario_lunes, idhorario_martes, idhorario_miercoles, idhorario_jueves,
           idhorario_viernes, idhorario_sabado, idhorario_domingo
    INTO #HorEmp
    FROM (
        SELECT he.*,
               ROW_NUMBER() OVER (
                   PARTITION BY he.empleado_id
                   -- AJUSTE 2026-11-23: el mismo numero de empleado puede existir en
                   -- otra empresa (ej. 110811 Cuencame / Procesos Secos): primero la
                   -- asignacion de ESTA planta, luego sin empresa, al final otra.
                   ORDER BY CASE WHEN he.empresa_id = @planta THEN 0 WHEN he.empresa_id IS NULL THEN 1 ELSE 2 END,
                            CASE WHEN he.fechaAsignacion <= @WeekEnd THEN 0 ELSE 1 END,
                            CASE WHEN he.fechaAsignacion <= @WeekEnd THEN he.fechaAsignacion END DESC,
                            he.fechaAsignacion ASC,
                            he.id DESC
               ) AS rn
        FROM horarios_empleado he
        WHERE he.idEstatus = 1
    ) x
    WHERE x.rn = 1;

    -- =========================================================
    -- PASO 2: checadas crudas de la planta, semana + 1 dia antes y
    -- 1 dia despues (para el turno del domingo que termina el lunes
    -- siguiente, y para excluir la cola del domingo anterior).
    -- =========================================================
    SELECT DISTINCT
        TRY_CAST(LTRIM(RTRIM(c.usuarioid)) AS INT) AS usuarioid_int,
        CAST(c.fechahora AS DATETIME)              AS fechahora
    INTO #Punches
    FROM [Nominas].[dbo].checadas AS c
    WHERE c.idPlanta = @planta
      AND c.fechahora >= DATEADD(day, -1, @WeekStart)
      AND c.fechahora <  DATEADD(day,  2, @WeekEnd)
      AND TRY_CAST(LTRIM(RTRIM(c.usuarioid)) AS INT) IS NOT NULL
      AND ( @NoEmpleado IS NULL OR TRY_CAST(LTRIM(RTRIM(c.usuarioid)) AS INT) = TRY_CAST(@NoEmpleado AS INT) );

    -- =========================================================
    -- PASO 3: asignar cada checada a su DIA DE TURNO.
    -- Se jala al dia anterior solo si el horario del dia anterior es
    -- nocturno Y realmente cruza medianoche (salida < entrada) Y la
    -- checada es antes de su salida_Hasta.
    -- =========================================================
    SELECT
        p.usuarioid_int,
        p.fechahora,
        CASE WHEN ISNULL(hPrev.tipo, 0) = 1
              AND hPrev.salida_Inicio < hPrev.entrada_Inicio
              AND CAST(p.fechahora AS TIME) < ISNULL(hPrev.salida_Hasta, '00:00:00')
             THEN DATEADD(day, -1, CAST(p.fechahora AS DATE))
             ELSE CAST(p.fechahora AS DATE)
        END AS DayDate
    INTO #PunchDia
    FROM #Punches p
    LEFT JOIN #HorEmp he ON he.empleado_id = p.usuarioid_int
    OUTER APPLY (
        SELECT CASE DATEPART(weekday, DATEADD(day, -1, CAST(p.fechahora AS DATE)))  -- 1 = lunes
                 WHEN 1 THEN he.idhorario_lunes
                 WHEN 2 THEN he.idhorario_martes
                 WHEN 3 THEN he.idhorario_miercoles
                 WHEN 4 THEN he.idhorario_jueves
                 WHEN 5 THEN he.idhorario_viernes
                 WHEN 6 THEN he.idhorario_sabado
                 WHEN 7 THEN he.idhorario_domingo
               END AS idhorarioPrev
    ) hp
    LEFT JOIN horarios hPrev ON hPrev.id = hp.idhorarioPrev;

    -- =========================================================
    -- PASO 4: una fila por empleado, una columna por dia de turno.
    -- Formato "hh:mm:ss, hh:mm:ss, " (igual que antes) para el parseo
    -- de comedor en #Semana. ORDER BY por fecha-hora completa: 23:08
    -- del miercoles queda antes que 01:54 del jueves.
    -- =========================================================
    ;WITH Dias AS (
        SELECT
            pd.usuarioid_int,
            pd.DayDate,
            (SELECT CONVERT(VARCHAR(8), CAST(x.fechahora AS TIME), 108) + ', '
             FROM #PunchDia x
             WHERE x.usuarioid_int = pd.usuarioid_int
               AND x.DayDate       = pd.DayDate
             ORDER BY x.fechahora
             FOR XML PATH('')) AS Tiempos
        FROM #PunchDia pd
        WHERE pd.DayDate BETWEEN @WeekStart AND @WeekEnd
        GROUP BY pd.usuarioid_int, pd.DayDate
    )
    SELECT
        CAST(d.usuarioid_int AS VARCHAR(50)) AS usuarioid,
        d.usuarioid_int,
        e.NOMBRES          AS Nombres,
        e.APELLIDO_PATERNO AS ApellidoPaterno,
        e.APELLIDO_MATERNO AS ApellidoMaterno,
        e.empresa_id,
        e.PUESTO_NO_ID,
        e.NOMBRE_PUESTO,
        e.DEPTO_NO_ID,
        e.NOMBRE_DEPARTAMENTO,
        MAX(CASE WHEN d.DayDate = @WeekStart                  THEN d.Tiempos END) AS Lunes,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 1, @WeekStart) THEN d.Tiempos END) AS Martes,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 2, @WeekStart) THEN d.Tiempos END) AS Miercoles,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 3, @WeekStart) THEN d.Tiempos END) AS Jueves,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 4, @WeekStart) THEN d.Tiempos END) AS Viernes,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 5, @WeekStart) THEN d.Tiempos END) AS Sabado,
        MAX(CASE WHEN d.DayDate = DATEADD(day, 6, @WeekStart) THEN d.Tiempos END) AS Domingo
    INTO #Pivotadas
    FROM Dias d
    INNER JOIN empleados e
        ON e.empleado_id = d.usuarioid_int
       AND e.ESTATUS     = 'A'
       AND e.empresa_id  = @planta
    GROUP BY d.usuarioid_int, e.NOMBRES, e.APELLIDO_PATERNO, e.APELLIDO_MATERNO,
             e.empresa_id, e.PUESTO_NO_ID, e.NOMBRE_PUESTO, e.DEPTO_NO_ID, e.NOMBRE_DEPARTAMENTO;

    -- =========================================================
    -- PASO 5: agregar empleados de Nomiplus (ChecadasBD) que no tengan
    -- checadas BioStar esa semana. ChecadasBD ya viene agrupado por
    -- dia de turno (ConsultaChecadasProceso usa la misma regla).
    -- =========================================================
    INSERT INTO #Pivotadas
    (
        usuarioid, usuarioid_int, Nombres, ApellidoPaterno, ApellidoMaterno,
        empresa_id, PUESTO_NO_ID, NOMBRE_PUESTO, DEPTO_NO_ID, NOMBRE_DEPARTAMENTO,
        Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo
    )
    SELECT DISTINCT
        LTRIM(RTRIM(CAST(cb.Numero AS VARCHAR(50)))) AS usuarioid,
        TRY_CAST(cb.Numero AS INT) AS usuarioid_int,
        cb.NombreCompleto AS Nombres,
        NULL AS ApellidoPaterno,
        NULL AS ApellidoMaterno,
        cb.Empresa AS empresa_id,
        NULL AS PUESTO_NO_ID,
        NULL AS NOMBRE_PUESTO,
        NULL AS DEPTO_NO_ID,
        NULL AS NOMBRE_DEPARTAMENTO,

        COALESCE(cb.Lunes,
            ISNULL(cb.LunesEntrada,'')
            + CASE WHEN ISNULL(cb.LunesEntrada,'')<>'' AND ISNULL(cb.LunesEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.LunesEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.LunesEntrada,'')<>'' OR ISNULL(cb.LunesEntradaComedor,'')<>'') AND ISNULL(cb.LunesSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.LunesSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.LunesEntrada,'')<>'' OR ISNULL(cb.LunesEntradaComedor,'')<>'' OR ISNULL(cb.LunesSalidaComedor,'')<>'') AND ISNULL(cb.LunesSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.LunesSalida,'')
        ) AS Lunes,

        COALESCE(cb.Martes,
            ISNULL(cb.MartesEntrada,'')
            + CASE WHEN ISNULL(cb.MartesEntrada,'')<>'' AND ISNULL(cb.MartesEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MartesEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.MartesEntrada,'')<>'' OR ISNULL(cb.MartesEntradaComedor,'')<>'') AND ISNULL(cb.MartesSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MartesSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.MartesEntrada,'')<>'' OR ISNULL(cb.MartesEntradaComedor,'')<>'' OR ISNULL(cb.MartesSalidaComedor,'')<>'') AND ISNULL(cb.MartesSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MartesSalida,'')
        ) AS Martes,

        COALESCE(cb.Miercoles,
            ISNULL(cb.MiercolesEntrada,'')
            + CASE WHEN ISNULL(cb.MiercolesEntrada,'')<>'' AND ISNULL(cb.MiercolesEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MiercolesEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.MiercolesEntrada,'')<>'' OR ISNULL(cb.MiercolesEntradaComedor,'')<>'') AND ISNULL(cb.MiercolesSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MiercolesSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.MiercolesEntrada,'')<>'' OR ISNULL(cb.MiercolesEntradaComedor,'')<>'' OR ISNULL(cb.MiercolesSalidaComedor,'')<>'') AND ISNULL(cb.MiercolesSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.MiercolesSalida,'')
        ) AS Miercoles,

        COALESCE(cb.Jueves,
            ISNULL(cb.JuevesEntrada,'')
            + CASE WHEN ISNULL(cb.JuevesEntrada,'')<>'' AND ISNULL(cb.JuevesEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.JuevesEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.JuevesEntrada,'')<>'' OR ISNULL(cb.JuevesEntradaComedor,'')<>'') AND ISNULL(cb.JuevesSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.JuevesSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.JuevesEntrada,'')<>'' OR ISNULL(cb.JuevesEntradaComedor,'')<>'' OR ISNULL(cb.JuevesSalidaComedor,'')<>'') AND ISNULL(cb.JuevesSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.JuevesSalida,'')
        ) AS Jueves,

        COALESCE(cb.Viernes,
            ISNULL(cb.ViernesEntrada,'')
            + CASE WHEN ISNULL(cb.ViernesEntrada,'')<>'' AND ISNULL(cb.ViernesEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.ViernesEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.ViernesEntrada,'')<>'' OR ISNULL(cb.ViernesEntradaComedor,'')<>'') AND ISNULL(cb.ViernesSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.ViernesSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.ViernesEntrada,'')<>'' OR ISNULL(cb.ViernesEntradaComedor,'')<>'' OR ISNULL(cb.ViernesSalidaComedor,'')<>'') AND ISNULL(cb.ViernesSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.ViernesSalida,'')
        ) AS Viernes,

        COALESCE(cb.Sabado,
            ISNULL(cb.SabadoEntrada,'')
            + CASE WHEN ISNULL(cb.SabadoEntrada,'')<>'' AND ISNULL(cb.SabadoEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.SabadoEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.SabadoEntrada,'')<>'' OR ISNULL(cb.SabadoEntradaComedor,'')<>'') AND ISNULL(cb.SabadoSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.SabadoSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.SabadoEntrada,'')<>'' OR ISNULL(cb.SabadoEntradaComedor,'')<>'' OR ISNULL(cb.SabadoSalidaComedor,'')<>'') AND ISNULL(cb.SabadoSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.SabadoSalida,'')
        ) AS Sabado,

        COALESCE(cb.Domingo,
            ISNULL(cb.DomingoEntrada,'')
            + CASE WHEN ISNULL(cb.DomingoEntrada,'')<>'' AND ISNULL(cb.DomingoEntradaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.DomingoEntradaComedor,'')
            + CASE WHEN (ISNULL(cb.DomingoEntrada,'')<>'' OR ISNULL(cb.DomingoEntradaComedor,'')<>'') AND ISNULL(cb.DomingoSalidaComedor,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.DomingoSalidaComedor,'')
            + CASE WHEN (ISNULL(cb.DomingoEntrada,'')<>'' OR ISNULL(cb.DomingoEntradaComedor,'')<>'' OR ISNULL(cb.DomingoSalidaComedor,'')<>'') AND ISNULL(cb.DomingoSalida,'')<>'' THEN ', ' ELSE '' END
            + ISNULL(cb.DomingoSalida,'')
        ) AS Domingo
    FROM [Nominas].[dbo].[ChecadasBD] cb
    WHERE
        ( @NoSemana IS NULL OR TRY_CAST(cb.NumeroSemana AS INT) = @NoSemana )
        AND ( @anio IS NULL OR CAST(cb.[Año] AS VARCHAR(50)) = @anio )
        AND cb.Empresa = ISNULL(@planta, cb.Empresa)
        AND ( @NoEmpleado IS NULL OR TRY_CAST(cb.Numero AS INT) = TRY_CAST(@NoEmpleado AS INT) )
        AND NOT EXISTS (
            SELECT 1 FROM #Pivotadas p
            WHERE p.usuarioid_int = TRY_CAST(cb.Numero AS INT)
              AND p.empresa_id = cb.Empresa
        );
    -- =========================================================
    -- PASO 5.1 (AJUSTE 2026-10-06, SOLO PLANTAS FORANEAS): empleados
    -- ACTIVOS de la planta sin ninguna checada esa semana. Se agregan con
    -- los dias vacios para que aparezcan en asistenciaBioStar: 
    -- ActualizaChecadasBiostarDias los marca como falta en sus dias
    -- laborales, o con el concepto (VAC, INCA, ...) si tienen incidencia.
    -- En Torreon (empresas.esTorreon = 1) no aplica.
    -- =========================================================

        INSERT INTO #Pivotadas
        (
            usuarioid, usuarioid_int, Nombres, ApellidoPaterno, ApellidoMaterno,
            empresa_id, PUESTO_NO_ID, NOMBRE_PUESTO, DEPTO_NO_ID, NOMBRE_DEPARTAMENTO,
            Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo
        )
        SELECT DISTINCT
            CAST(TRY_CAST(e.empleado_id AS INT) AS VARCHAR(50)),
            TRY_CAST(e.empleado_id AS INT),
            e.NOMBRES, e.APELLIDO_PATERNO, e.APELLIDO_MATERNO,
            e.empresa_id, e.PUESTO_NO_ID, e.NOMBRE_PUESTO, e.DEPTO_NO_ID, e.NOMBRE_DEPARTAMENTO,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL
        FROM empleados e
        WHERE e.empresa_id = @planta
          AND e.ESTATUS = 'A'
          AND TRY_CAST(e.empleado_id AS INT) IS NOT NULL
          AND ( @NoEmpleado IS NULL OR TRY_CAST(e.empleado_id AS INT) = TRY_CAST(@NoEmpleado AS INT) )
          AND NOT EXISTS (
              SELECT 1 FROM #Pivotadas p
              WHERE p.usuarioid_int = TRY_CAST(e.empleado_id AS INT)
          );
    -- =========================================================
    -- PASO 6: construir la semana. Como las cadenas ya vienen por dia
    -- de turno, Entrada = primera checada y Salida = ultima para todos
    -- los horarios. Comedor = 2a y 3a checada (igual que antes).
    -- =========================================================
    SELECT DISTINCT
        CAST(c.usuarioid_int AS INT)                AS Numero,
        e.NOMBRE_COMPLETO                           AS NombreCompleto,
        c.empresa_id,
        em.nombre                                   AS nombreEmpresa,
        c.DEPTO_NO_ID                               AS departamento_id,
        c.NOMBRE_DEPARTAMENTO                       AS nombreDepartamento,
        c.PUESTO_NO_ID                              AS puesto_id,
        c.NOMBRE_PUESTO                             AS nombrePuesto,
        @FechaInicio                                AS FechaInicio,
        @FechaFin                                   AS FechaFin,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Lunes)),    0) AS tipoHorarioLunes,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Martes)),   0) AS tipoHorarioMartes,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Miercoles)),0) AS tipoHorarioMiercoles,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Jueves)),   0) AS tipoHorarioJueves,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Viernes)),  0) AS tipoHorarioViernes,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Sabado)),   0) AS tipoHorarioSabado,
        ISNULL(CONVERT(INT,(SELECT TOP 1 tipo FROM horarios WHERE id=he.idhorario_Domingo)),  0) AS tipoHorarioDomingo,
        he.idhorario_Lunes,
        he.idhorario_martes,
        he.idhorario_miercoles,
        he.idhorario_jueves,
        he.idhorario_viernes,
        he.idhorario_sabado,
        he.idhorario_domingo,

        REPLACE(LEFT(c.Lunes,10),',','')                AS LunesEntrada,
        CASE WHEN ((LEN(c.Lunes)+1)/10) >= 3 THEN SUBSTRING(c.Lunes,CHARINDEX(',',c.Lunes)+2,8) WHEN c.Lunes IS NULL THEN NULL ELSE '' END   AS LunesEntradaComedor,
        CASE WHEN ((LEN(c.Lunes)+1)/10) >= 4 THEN SUBSTRING(c.Lunes,CHARINDEX(',',c.Lunes)+11,9) WHEN c.Lunes IS NULL THEN NULL ELSE '' END  AS LunesSalidaComedor,
        CASE WHEN ((LEN(c.Lunes)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Lunes,10),',','') WHEN c.Lunes IS NULL THEN NULL ELSE '' END               AS LunesSalida,

        REPLACE(LEFT(c.Martes,10),',','')                 AS MartesEntrada,
        CASE WHEN ((LEN(c.Martes)+1)/10) >= 3 THEN SUBSTRING(c.Martes,CHARINDEX(',',c.Martes)+2,8) WHEN c.Martes IS NULL THEN NULL ELSE '' END   AS MartesEntradaComedor,
        CASE WHEN ((LEN(c.Martes)+1)/10) >= 4 THEN SUBSTRING(c.Martes,CHARINDEX(',',c.Martes)+11,9) WHEN c.Martes IS NULL THEN NULL ELSE '' END  AS MartesSalidaComedor,
        CASE WHEN ((LEN(c.Martes)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Martes,10),',','') WHEN c.Martes IS NULL THEN NULL ELSE '' END                AS MartesSalida,

        REPLACE(LEFT(c.Miercoles,10),',','')                    AS MiercolesEntrada,
        CASE WHEN ((LEN(c.Miercoles)+1)/10) >= 3 THEN SUBSTRING(c.Miercoles,CHARINDEX(',',c.Miercoles)+2,8) WHEN c.Miercoles IS NULL THEN NULL ELSE '' END   AS MiercolesEntradaComedor,
        CASE WHEN ((LEN(c.Miercoles)+1)/10) >= 4 THEN SUBSTRING(c.Miercoles,CHARINDEX(',',c.Miercoles)+11,9) WHEN c.Miercoles IS NULL THEN NULL ELSE '' END  AS MiercolesSalidaComedor,
        CASE WHEN ((LEN(c.Miercoles)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Miercoles,10),',','') WHEN c.Miercoles IS NULL THEN NULL ELSE '' END                   AS MiercolesSalida,

        REPLACE(LEFT(c.Jueves,10),',','')                 AS JuevesEntrada,
        CASE WHEN ((LEN(c.Jueves)+1)/10) >= 3 THEN SUBSTRING(c.Jueves,CHARINDEX(',',c.Jueves)+2,8) WHEN c.Jueves IS NULL THEN NULL ELSE '' END   AS JuevesEntradaComedor,
        CASE WHEN ((LEN(c.Jueves)+1)/10) >= 4 THEN SUBSTRING(c.Jueves,CHARINDEX(',',c.Jueves)+11,9) WHEN c.Jueves IS NULL THEN NULL ELSE '' END  AS JuevesSalidaComedor,
        CASE WHEN ((LEN(c.Jueves)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Jueves,10),',','') WHEN c.Jueves IS NULL THEN NULL ELSE '' END                AS JuevesSalida,

        REPLACE(LEFT(c.Viernes,10),',','')                  AS ViernesEntrada,
        CASE WHEN ((LEN(c.Viernes)+1)/10) >= 3 THEN SUBSTRING(c.Viernes,CHARINDEX(',',c.Viernes)+2,8) WHEN c.Viernes IS NULL THEN NULL ELSE '' END   AS ViernesEntradaComedor,
        CASE WHEN ((LEN(c.Viernes)+1)/10) >= 4 THEN SUBSTRING(c.Viernes,CHARINDEX(',',c.Viernes)+11,9) WHEN c.Viernes IS NULL THEN NULL ELSE '' END  AS ViernesSalidaComedor,
        CASE WHEN ((LEN(c.Viernes)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Viernes,10),',','') WHEN c.Viernes IS NULL THEN NULL ELSE '' END                 AS ViernesSalida,

        REPLACE(LEFT(c.Sabado,10),',','')                 AS SabadoEntrada,
        CASE WHEN ((LEN(c.Sabado)+1)/10) >= 3 THEN SUBSTRING(c.Sabado,CHARINDEX(',',c.Sabado)+2,8) WHEN c.Sabado IS NULL THEN NULL ELSE '' END   AS SabadoEntradaComedor,
        CASE WHEN ((LEN(c.Sabado)+1)/10) >= 4 THEN SUBSTRING(c.Sabado,CHARINDEX(',',c.Sabado)+11,9) WHEN c.Sabado IS NULL THEN NULL ELSE '' END  AS SabadoSalidaComedor,
        CASE WHEN ((LEN(c.Sabado)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Sabado,10),',','') WHEN c.Sabado IS NULL THEN NULL ELSE '' END                AS SabadoSalida,

        REPLACE(LEFT(c.Domingo,10),',','')                  AS DomingoEntrada,
        CASE WHEN ((LEN(c.Domingo)+1)/10) >= 3 THEN SUBSTRING(c.Domingo,CHARINDEX(',',c.Domingo)+2,8) WHEN c.Domingo IS NULL THEN NULL ELSE '' END   AS DomingoEntradaComedor,
        CASE WHEN ((LEN(c.Domingo)+1)/10) >= 4 THEN SUBSTRING(c.Domingo,CHARINDEX(',',c.Domingo)+11,9) WHEN c.Domingo IS NULL THEN NULL ELSE '' END  AS DomingoSalidaComedor,
        CASE WHEN ((LEN(c.Domingo)+1)/10) >= 2 THEN REPLACE(RIGHT(c.Domingo,10),',','') WHEN c.Domingo IS NULL THEN NULL ELSE '' END                 AS DomingoSalida,

        he.fechaAsignacion
    INTO #Semana
    FROM #Pivotadas AS c
    INNER JOIN empresas          AS em ON em.id = c.empresa_id
        INNER JOIN #HorEmp           AS he ON he.empleado_id = c.usuarioid_int   -- AJUSTE 2026-09-30: una sola asignacion por semana
    INNER JOIN empleados         AS e  ON e.empleado_id = c.usuarioid_int
    WHERE c.empresa_id = @planta
      AND e.empresa_id = @planta
      AND ( @NoEmpleado IS NULL OR TRY_CAST(@NoEmpleado AS INT) = c.usuarioid_int )
      AND e.ESTATUS = 'A'
    ORDER BY Numero;

    UPDATE #Semana SET FechaInicio = @FechaInicio, FechaFin = @FechaFin;

    -- =========================================================
    -- PASO 7: misma estructura de columnas que asistenciaBioStar.
    -- Faltas, retardos, asistencias y tiempo extra los calcula
    -- ActualizaChecadasBiostarDias mas abajo.
    -- =========================================================
    SELECT DISTINCT
        s.*,
        CAST(NULL AS INT) AS RetardoLunes,     CAST(NULL AS INT) AS RetardoMartes,
        CAST(NULL AS INT) AS RetardoMiercoles, CAST(NULL AS INT) AS RetardoJueves,
        CAST(NULL AS INT) AS RetardoViernes,   CAST(NULL AS INT) AS RetardoSabado,
        CAST(NULL AS INT) AS RetardoDomingo,
        0                 AS RetardoSemanal,
        CAST(NULL AS INT) AS FaltaLunes,       CAST(NULL AS INT) AS FaltaMartes,
        CAST(NULL AS INT) AS FaltaMiercoles,   CAST(NULL AS INT) AS FaltaJueves,
        CAST(NULL AS INT) AS FaltaViernes,     CAST(NULL AS INT) AS FaltaSabado,
        CAST(NULL AS INT) AS FaltaDomingo,
        0                 AS FaltasTotales,
        CAST(NULL AS INT) AS AsistenciaLunes,     CAST(NULL AS INT) AS AsistenciaMartes,
        CAST(NULL AS INT) AS AsistenciaMiercoles, CAST(NULL AS INT) AS AsistenciaJueves,
        CAST(NULL AS INT) AS AsistenciaViernes,   CAST(NULL AS INT) AS AsistenciaSabado,
        CAST(NULL AS INT) AS AsistenciaDomingo,
        0                 AS AsistenciasTotales,
        CAST(NULL AS INT) AS LunesTiempoExtra,     CAST(NULL AS INT) AS MartesTiempoExtra,
        CAST(NULL AS INT) AS MiercolesTiempoExtra, CAST(NULL AS INT) AS JuevesTiempoExtra,
        CAST(NULL AS INT) AS ViernesTiempoExtra,   CAST(NULL AS INT) AS SabadoTiempoExtra,
        CAST(NULL AS INT) AS DomingoTiempoExtra,
        CAST('00:00:00' AS VARCHAR(20)) AS TotalTiempoExtra,   -- lo recalcula ActualizaChecadasBiostarDias
        @NoSemana AS NoSemana,
        @anio     AS anio
    INTO #Semana2
    FROM #Semana s
    ORDER BY s.Numero;

    -- =========================================================
    -- PASO 8: INSERT solo de empleados que aun no existen en esta
    -- semana/planta; UPDATE de todos.
    -- =========================================================
    INSERT INTO asistenciaBioStar
    SELECT DISTINCT * FROM #Semana2 x
    WHERE x.empresa_id = @planta
      AND NOT EXISTS (
          SELECT 1 FROM asistenciaBioStar a
          WHERE TRY_CAST(a.Numero AS INT) = x.Numero
            AND a.NoSemana   = x.NoSemana
            AND a.anio       = @anio
            AND a.empresa_id = @planta
      );

    UPDATE a SET
        a.Numero=cast(x.Numero as varchar(max)) COLLATE DATABASE_DEFAULT, a.Nombre=x.NombreCompleto, a.empresa_id=x.empresa_id,
        a.FechaInicio=x.FechaInicio, a.FechaFin=x.FechaFin,
        a.tipoHorarioLunes=x.tipoHorarioLunes, a.tipoHorarioMartes=x.tipoHorarioMartes,
        a.tipoHorarioMiercoles=x.tipoHorarioMiercoles, a.tipoHorarioJueves=x.tipoHorarioJueves,
        a.tipoHorarioViernes=x.tipoHorarioViernes, a.tipoHorarioSabado=x.tipoHorarioSabado,
        a.tipoHorarioDomingo=x.tipoHorarioDomingo,
        a.idhorario_Lunes=x.idhorario_Lunes, a.idhorario_Martes=x.idhorario_Martes,
        a.idhorario_Miercoles=x.idhorario_Miercoles, a.idhorario_Jueves=x.idhorario_Jueves,
        a.idhorario_Viernes=x.idhorario_Viernes, a.idhorario_Sabado=x.idhorario_Sabado,
        a.idhorario_Domingo=x.idhorario_Domingo,
        a.LunesEntrada=x.LunesEntrada, a.LunesEntradaComedor=x.LunesEntradaComedor,
        a.LunesSalidaComedor=x.LunesSalidaComedor, a.LunesSalida=x.LunesSalida,
        a.MartesEntrada=x.MartesEntrada, a.MartesEntradaComedor=x.MartesEntradaComedor,
        a.MartesSalidaComedor=x.MartesSalidaComedor, a.MartesSalida=x.MartesSalida,
        a.MiercolesEntrada=x.MiercolesEntrada, a.MiercolesEntradaComedor=x.MiercolesEntradaComedor,
        a.MiercolesSalidaComedor=x.MiercolesSalidaComedor, a.MiercolesSalida=x.MiercolesSalida,
        a.JuevesEntrada=x.JuevesEntrada, a.JuevesEntradaComedor=x.JuevesEntradaComedor,
        a.JuevesSalidaComedor=x.JuevesSalidaComedor, a.JuevesSalida=x.JuevesSalida,
        a.ViernesEntrada=x.ViernesEntrada, a.ViernesEntradaComedor=x.ViernesEntradaComedor,
        a.ViernesSalidaComedor=x.ViernesSalidaComedor, a.ViernesSalida=x.ViernesSalida,
        a.SabadoEntrada=x.SabadoEntrada, a.SabadoEntradaComedor=x.SabadoEntradaComedor,
        a.SabadoSalidaComedor=x.SabadoSalidaComedor, a.SabadoSalida=x.SabadoSalida,
        a.DomingoEntrada=x.DomingoEntrada, a.DomingoEntradaComedor=x.DomingoEntradaComedor,
        a.DomingoSalidaComedor=x.DomingoSalidaComedor, a.DomingoSalida=x.DomingoSalida,
        a.RetardoSemanal=x.RetardoSemanal, a.FaltasTotales=x.FaltasTotales,
        a.TotalTiempoExtra=x.TotalTiempoExtra, a.NoSemana=x.NoSemana
    FROM asistenciaBioStar AS a
    INNER JOIN #Semana2 AS x
        ON TRY_CAST(a.Numero AS INT) = x.Numero
        AND a.NoSemana   = x.NoSemana
        AND a.anio       = @anio
        AND a.empresa_id = @planta;

    DBCC FREEPROCCACHE WITH NO_INFOMSGS;

    -- =========================================================
    -- PASO 9: faltas, retardos, asistencias y tiempo extra por dia
    -- =========================================================
    EXEC ActualizaChecadasBiostarDias @NoSemana, @anio, @planta, @Code, @puesto, @NoEmpleado;

    -- =========================================================
    -- PASO 10: dias sin tiempo extra (solo si se pidio tiempo extra y
    -- no son todos los dias). Pone en 0 los dias desmarcados y
    -- recalcula el total con los que quedan.
    -- =========================================================
    IF @tiempoExtra = 1 AND @diasTiempoExtra <> '1111111'
    BEGIN
        UPDATE a SET
             a.LunesTiempoExtra     = CASE WHEN SUBSTRING(@diasTiempoExtra,1,1) = '0' THEN '00:00:00' ELSE a.LunesTiempoExtra     END
            ,a.MartesTiempoExtra    = CASE WHEN SUBSTRING(@diasTiempoExtra,2,1) = '0' THEN '00:00:00' ELSE a.MartesTiempoExtra    END
            ,a.MiercolesTiempoExtra = CASE WHEN SUBSTRING(@diasTiempoExtra,3,1) = '0' THEN '00:00:00' ELSE a.MiercolesTiempoExtra END
            ,a.JuevesTiempoExtra    = CASE WHEN SUBSTRING(@diasTiempoExtra,4,1) = '0' THEN '00:00:00' ELSE a.JuevesTiempoExtra    END
            ,a.ViernesTiempoExtra   = CASE WHEN SUBSTRING(@diasTiempoExtra,5,1) = '0' THEN '00:00:00' ELSE a.ViernesTiempoExtra   END
            ,a.SabadoTiempoExtra    = CASE WHEN SUBSTRING(@diasTiempoExtra,6,1) = '0' THEN '00:00:00' ELSE a.SabadoTiempoExtra    END
            ,a.DomingoTiempoExtra   = CASE WHEN SUBSTRING(@diasTiempoExtra,7,1) = '0' THEN '00:00:00' ELSE a.DomingoTiempoExtra   END
        FROM asistenciaBioStar a
        WHERE a.NoSemana = @NoSemana AND a.anio = @anio AND a.empresa_id = @planta
          AND ( @NoEmpleado IS NULL OR TRY_CAST(a.Numero AS INT) = TRY_CAST(@NoEmpleado AS INT) );

        UPDATE a SET
            a.TotalTiempoExtra = dbo.fnSegundos(
                  ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.LunesTiempoExtra)))),     0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.MartesTiempoExtra)))),    0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.MiercolesTiempoExtra)))), 0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.JuevesTiempoExtra)))),    0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.ViernesTiempoExtra)))),   0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.SabadoTiempoExtra)))),    0)
                + ISNULL(DATEDIFF(S, CAST('00:00:00' AS TIME), TRY_CONVERT(TIME, LTRIM(RTRIM(a.DomingoTiempoExtra)))),   0)
            , 0)
        FROM asistenciaBioStar a
        WHERE a.NoSemana = @NoSemana AND a.anio = @anio AND a.empresa_id = @planta
          AND ( @NoEmpleado IS NULL OR TRY_CAST(a.Numero AS INT) = TRY_CAST(@NoEmpleado AS INT) );
    END

    -- =========================================================
    -- PASO 11: reporte de exportacion
    -- =========================================================
        EXEC [dbo].[ConsultaAsistenciaExportarTablaTemporal] @NoSemana, @anio, @planta, @Code, @puesto, @NoEmpleado, @tiempoExtra, @diasTiempoExtra;

    UPDATE a
    SET a.empresa_id = e.id
    FROM asistenciaBioStar AS a
    INNER JOIN empresas AS e ON (a.nombreEmpresa = e.nombre);

    END TRY
    BEGIN CATCH
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
        DECLARE @ErrorNumber INT = ERROR_NUMBER();
        RAISERROR('Error %d: %s', 16, 1, @ErrorNumber, @ErrorMessage);
        THROW;
    END CATCH
END


GO
