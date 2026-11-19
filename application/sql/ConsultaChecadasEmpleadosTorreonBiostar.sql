-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-10-08 (CubreLunes..CubreDomingo)
-- Modificado:  2026-11-11 @planta acepta varias empresas separadas por coma (Torreón unificado)
-- Description: SOLO LECTURA. Ya no recalcula ni dispara ActualizaChecadasBiostar.
--              Lee asistenciaBioStar (que ya trae checadas, faltas, retardos,
--              tiempo extra y vacaciones/permisos/incapacidad como texto en
--              LunesEntrada..DomingoSalida, puestos ah¡ por
--              ActualizaChecadasBiostarDias). El refresco de esos datos lo
--              hace ahora el bot¢n "Procesar" -> dbo.ProcesaChecadasBiostar.
--  EXEC [dbo].[ConsultaChecadasEmpleadosTorreonBiostar] '29','2026','26',NULL,NULL,NULL
-- =============================================
ALTER PROCEDURE [dbo].[ConsultaChecadasEmpleadosTorreonBiostar]
    @NoSemana   INT         = NULL,
    @anio       VARCHAR(50) = NULL,
    @planta     VARCHAR(500) = NULL,   -- una empresa o varias separadas por coma ('1,2,3')
    @Code       VARCHAR(50) = NULL,
    @puesto     VARCHAR(50) = NULL,
    @NoEmpleado VARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    SET DATEFIRST 1;

    SET @NoSemana = ISNULL(@NoSemana, DATEPART(wk, GETDATE()));
    SET @anio     = ISNULL(@anio, CONVERT(VARCHAR(50), DATEPART(year, GETDATE())));

    
WITH CTE_Duplicados AS (
SELECT
*,
ROW_NUMBER() OVER (PARTITION BY a.Numero, a.anio, a.NoSemana, a.empresa_id ORDER BY (SELECT NULL)) AS NumFila
FROM asistenciaBioStar as a
)
DELETE FROM CTE_Duplicados
WHERE NumFila > 1;

    -- Empresas a consultar (sin STRING_SPLIT: compatibilidad 110)
    IF OBJECT_ID('tempdb..#Plantas') IS NOT NULL DROP TABLE #Plantas;
    CREATE TABLE #Plantas (id INT PRIMARY KEY);
    DECLARE @xp XML = CAST('<i>' + REPLACE(REPLACE(ISNULL(@planta, ''), ' ', ''), ',', '</i><i>') + '</i>' AS XML);
    INSERT INTO #Plantas (id)
    SELECT DISTINCT TRY_CAST(t.c.value('.', 'VARCHAR(20)') AS INT) FROM @xp.nodes('/i') t(c)
    WHERE TRY_CAST(t.c.value('.', 'VARCHAR(20)') AS INT) IS NOT NULL;

    BEGIN TRY
        -- Checadas REALES de los días con VACACIONES de día completo (SOLO esos empleados, no todos,
        -- por eso es rápido): en esos días el concepto VAC ocultó las checadas; se recuperan de
        -- dbo.checadas acotando a la planta (idPlanta, evita homónimos de otra empresa) y a la semana
        -- (fechahora, usa el índice). La pantalla muestra "VAC / hora" para que RH lo revise.
        DECLARE @lunes DATE = (SELECT TOP 1 CAST(a.FechaInicio AS date) FROM dbo.asistenciaBioStar a
                               WHERE a.NoSemana = @NoSemana AND a.anio = TRY_CAST(@anio AS INT)
                                 AND a.empresa_id IN (SELECT id FROM #Plantas));
        IF OBJECT_ID('tempdb..#VacReales') IS NOT NULL DROP TABLE #VacReales;
        CREATE TABLE #VacReales (Numero VARCHAR(32) COLLATE DATABASE_DEFAULT, empresa_id INT, dia INT, Horas VARCHAR(200), PRIMARY KEY (Numero, empresa_id, dia));

        IF @lunes IS NOT NULL
        INSERT INTO #VacReales (Numero, empresa_id, dia, Horas)
        SELECT x.Numero, x.empresa_id, x.dd,
               STUFF((SELECT '|' + CONVERT(VARCHAR(5), ch.fechahora, 108)
                      FROM dbo.checadas ch
                      WHERE ch.usuarioid_norm = x.Numero COLLATE SQL_Latin1_General_CP1_CI_AS
                        AND ch.idPlanta = x.empresa_id
                        AND ch.fechahora >= x.fecha AND ch.fechahora < DATEADD(day, 1, x.fecha)
                      ORDER BY ch.fechahora FOR XML PATH('')), 1, 1, '')
        FROM (
            SELECT DISTINCT a.Numero COLLATE DATABASE_DEFAULT AS Numero, a.empresa_id, o.dd, DATEADD(day, o.dd, @lunes) AS fecha
            FROM dbo.asistenciaBioStar a
            CROSS APPLY (VALUES (0),(1),(2),(3),(4),(5),(6)) o(dd)
            WHERE a.NoSemana = @NoSemana AND a.anio = TRY_CAST(@anio AS INT) AND a.empresa_id IN (SELECT id FROM #Plantas)
              AND EXISTS (
                  SELECT 1 FROM dbo.incidencias i JOIN dbo.conceptos c ON c.id = i.concepto_id
                  WHERE i.estatus = 1 AND c.descripcionCorta = 'VAC'
                    AND i.empleado_id = TRY_CAST(a.Numero AS INT)
                    AND (i.empresa_id = a.empresa_id OR i.empresa_id IS NULL)
                    AND i.cubreEntrada = 1 AND i.cubreEntradaComedor = 1 AND i.cubreSalidaComedor = 1 AND i.cubreSalida = 1
                    AND DATEADD(day, o.dd, @lunes) BETWEEN i.fechaInicio AND i.fechaFin
              )
        ) x;
        DELETE FROM #VacReales WHERE Horas IS NULL;

        SELECT DISTINCT
            a.Numero                                               AS Numero,
             a.Nombre                                             AS NombreCompleto,
            a.empresa_id,
            a.nombreEmpresa                                              AS nombreEmpresa,
            a.departamento_id                                          AS departamento_id,
            a.nombreDepartamento                                  AS nombreDepartamento,
            a.PUESTO_ID                                         AS puesto_id,
            a.NOMBREPUESTO                                        AS nombrePuesto,
            a.FechaInicio,
            a.FechaFin,
            a.tipoHorarioLunes, a.tipoHorarioMartes, a.tipoHorarioMiercoles,
            a.tipoHorarioJueves, a.tipoHorarioViernes, a.tipoHorarioSabado, a.tipoHorarioDomingo,
            a.idhorario_Lunes     AS idhorario_lunes,
            a.idhorario_Martes    AS idhorario_martes,
            a.idhorario_Miercoles AS idhorario_miercoles,
            a.idhorario_Jueves    AS idhorario_jueves,
            a.idhorario_Viernes   AS idhorario_viernes,
            a.idhorario_Sabado    AS idhorario_sabado,
            a.idhorario_Domingo   AS idhorario_domingo,
            a.LunesEntrada, a.LunesEntradaComedor, a.LunesSalidaComedor, a.LunesSalida,
            a.MartesEntrada, a.MartesEntradaComedor, a.MartesSalidaComedor, a.MartesSalida,
            a.MiercolesEntrada, a.MiercolesEntradaComedor, a.MiercolesSalidaComedor, a.MiercolesSalida,
            a.JuevesEntrada, a.JuevesEntradaComedor, a.JuevesSalidaComedor, a.JuevesSalida,
            a.ViernesEntrada, a.ViernesEntradaComedor, a.ViernesSalidaComedor, a.ViernesSalida,
            a.SabadoEntrada, a.SabadoEntradaComedor, a.SabadoSalidaComedor, a.SabadoSalida,
            a.DomingoEntrada, a.DomingoEntradaComedor, a.DomingoSalidaComedor, a.DomingoSalida,
            -- Qué checadas cubre la incidencia del día ('1111' = todo el día o sin incidencia);
            -- la pantalla solo repite el concepto en las columnas vacías cuando es '1111'.
            ISNULL(cb_lunes.Cubre, '1111') AS CubreLunes,
            ISNULL(cb_martes.Cubre, '1111') AS CubreMartes,
            ISNULL(cb_miercoles.Cubre, '1111') AS CubreMiercoles,
            ISNULL(cb_jueves.Cubre, '1111') AS CubreJueves,
            ISNULL(cb_viernes.Cubre, '1111') AS CubreViernes,
            ISNULL(cb_sabado.Cubre, '1111') AS CubreSabado,
            ISNULL(cb_domingo.Cubre, '1111') AS CubreDomingo,
            -- Checadas reales (HH:MM separadas por '|') de los días con VAC de día completo
            rl0.Horas AS LunesReales, rl1.Horas AS MartesReales, rl2.Horas AS MiercolesReales,
            rl3.Horas AS JuevesReales, rl4.Horas AS ViernesReales, rl5.Horas AS SabadoReales, rl6.Horas AS DomingoReales
            --,a.fechaAsignacion
        FROM dbo.asistenciaBioStar AS a
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 0, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_lunes
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 1, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_martes
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 2, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_miercoles
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 3, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_jueves
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 4, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_viernes
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 5, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_sabado
        OUTER APPLY (
            SELECT TOP 1 CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
            FROM dbo.incidencias AS inc
            WHERE inc.estatus = 1
              AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
              AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
              AND DATEADD(day, 6, CAST(a.FechaInicio AS date)) BETWEEN inc.fechaInicio AND inc.fechaFin
            ORDER BY inc.fechaInicio DESC
        ) AS cb_domingo
        LEFT JOIN #VacReales rl0 ON rl0.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl0.empresa_id = a.empresa_id AND rl0.dia = 0
        LEFT JOIN #VacReales rl1 ON rl1.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl1.empresa_id = a.empresa_id AND rl1.dia = 1
        LEFT JOIN #VacReales rl2 ON rl2.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl2.empresa_id = a.empresa_id AND rl2.dia = 2
        LEFT JOIN #VacReales rl3 ON rl3.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl3.empresa_id = a.empresa_id AND rl3.dia = 3
        LEFT JOIN #VacReales rl4 ON rl4.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl4.empresa_id = a.empresa_id AND rl4.dia = 4
        LEFT JOIN #VacReales rl5 ON rl5.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl5.empresa_id = a.empresa_id AND rl5.dia = 5
        LEFT JOIN #VacReales rl6 ON rl6.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl6.empresa_id = a.empresa_id AND rl6.dia = 6
        WHERE a.NoSemana   = @NoSemana
          AND a.anio       = TRY_CAST(@anio AS INT)
          AND a.empresa_id IN (SELECT id FROM #Plantas)
          AND (
                @NoEmpleado IS NULL
                OR LTRIM(RTRIM(a.Numero)) COLLATE SQL_Latin1_General_CP1_CI_AS
                   = LTRIM(RTRIM(@NoEmpleado)) COLLATE SQL_Latin1_General_CP1_CI_AS
              )
             
        ORDER BY a.Numero;

    END TRY
    BEGIN CATCH
        SELECT ERROR_NUMBER() AS ErrNum, ERROR_MESSAGE() AS ErrMsg;
    END CATCH
END;

GO
