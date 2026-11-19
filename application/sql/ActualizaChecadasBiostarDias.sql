USE [Nominas];
GO
-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-07-13 (optimización de rendimiento)
--
-- AJUSTE 2026-10-09: FALTA/ASISTENCIA solo usan el goce de la incidencia cuando ES DE DÍA
--   COMPLETO (inc_X.Completa=1). Una incidencia PARCIAL (p. ej. "Retardo ruta transporte"
--   'tran', que solo cubre la entrada y tiene goceSueldo=1) ya NO fuerza asistencia ni evita
--   la falta: el día se decide con las checadas reales. Si no hay ninguna checada, es FALTA
--   (antes el goce de la parcial lo marcaba como asistencia). La condición pasó de
--   (inc_X.Completa=1 OR inc_X.GoceDia=1) a (inc_X.Completa=1) en los bloques FALTA y ASISTENCIA.
--   Requiere reprocesar la semana para que surta efecto.
-- AJUSTE 2026-10-08b: incidencias parciales (incidencias.cubreEntrada / cubreEntradaComedor /
--   cubreSalidaComedor / cubreSalida, ver application/sql/incidencias_cubre.sql):
--   - Completa (cubre las 4): como siempre.
--   - Parcial: el codigo del concepto va solo en las columnas que cubre y las demas
--     conservan la checada real; si cubre la entrada no hay retardo; se calcula el
--     tiempo extra; sin goce no fuerza la falta (se calcula con las checadas).
--   Sustituye el caso especial de 'tran' (sus incidencias quedaron como "solo entrada").
-- AJUSTE 2026-10-08: concepto "Retardo ruta transporte" ('tran'):
--   - Solo se escribe en la columna de ENTRADA del dia; EntradaComedor,
--     SalidaComedor y Salida conservan las checadas reales.
--   - No es retardo (premio de puntualidad intacto) y SI cuenta el
--     tiempo extra de ese dia, como un dia trabajado normal.
--
-- POR QUÉ ERA LENTO (diagnóstico):
--
-- Por cada empleado, por cada día de la semana, el proc original volvía a
-- consultar la tabla `incidencias` y la tabla `horarios` desde cero, en
-- 3 lugares distintos e independientes:
--   1) El bloque de Falta/Asistencia de ese día
--   2) El bloque de Retardo de ese día (COUNT(...)>0 contra incidencias,
--      + hasta 5 subconsultas repetidas contra horarios por el MISMO
--      idhorario_X: entrada_Desde, entrada_Inicio, COUNT(id))
--   3) El bloque de TiempoExtra de ese día (otra vez COUNT(...)>0 contra
--      incidencias, + hasta 8-10 subconsultas repetidas contra horarios
--      por el MISMO idhorario_X: entrada_Inicio, tipo, salida_Inicio)
--   4) El UPDATE final (LunesEntrada, MartesEntrada, etc.) volvía a
--      consultar incidencias/conceptos POR CUARTA VEZ, para las mismas
--      fechas, solo para obtener el descripcionCorta del concepto.
--
-- Multiplicando 7 días x hasta 4 lugares x varias subconsultas cada uno,
-- una sola fila de empleado podía disparar decenas de accesos repetidos
-- a incidencias/horarios que ya se habían resuelto antes en la misma fila.
--
-- QUÉ CAMBIA EN ESTA VERSIÓN (misma lógica de negocio, sin nuevos
-- supuestos):
--   - Se agrega UN JOIN a horarios por día (h_lunes...h_domingo), en vez
--     de repetir "SELECT TOP 1 columna FROM horarios WHERE id=idhorario_X"
--     una y otra vez para la misma columna/día.
--   - El OUTER APPLY de incidencias por día (inc_lunes...inc_domingo, que
--     ya existía) se extiende para traer también el descripcionCorta del
--     concepto (ConceptoCorto), y ese mismo resultado se reutiliza en
--     Falta/Asistencia, Retardo, TiempoExtra Y en el UPDATE final -- ya
--     no se vuelve a consultar incidencias/conceptos para lo mismo.
--   - El UPDATE final ahora hace JOIN contra #ASISTENCIA (que ya trae
--     IncExiste/ConceptoCorto por día) en vez de re-derivar todo desde
--     incidencias/conceptos otra vez.
--   - Los 3 SELECTs distintos a horarios_empleado (rotativo, cantidad,
--     cantidad otra vez) para el mismo empleado/rango se consolidan en
--     un solo OUTER APPLY.
--
-- Recuerda correr también indices_sugeridos.sql (incidencias, conceptos,
-- horarios_empleado) -- sin esos índices, aunque se reduzcan las
-- repeticiones, cada consulta a incidencias sigue siendo table scan.
-- =============================================
-------------- EXEC ActualizaChecadasBiostarDias 27,'2026',34,NULL,NULL,NULL
CREATE OR ALTER PROCEDURE [dbo].[ActualizaChecadasBiostarDias]
@NoSemana     INT=NULL,
@anio      VARCHAR(50)=NULL,
@planta      VARCHAR(50) =NULL,
@Code      VARCHAR(50) =NULL,
@puesto      VARCHAR(50) =NULL,
@NoEmpleado     VARCHAR(50) =NULL
AS
BEGIN

DECLARE @FechaInicio   datetime
DECLARE @FechaFin       datetime

SET DATEFIRST 1;

DECLARE @esTorreon BIT = ISNULL((SELECT TOP 1 esTorreon FROM dbo.empresas WHERE id = @planta), 0);
DECLARE @ConceptoVacaciones VARCHAR(10) = 'VAC';
-- AJUSTE 2026-10-08: "Retardo ruta transporte" no borra checadas ni quita tiempo extra
DECLARE @ConceptoTransporte VARCHAR(10) = 'tran';
SET @FechaInicio=(select distinct DATEADD(wk, DATEDIFF(wk, 6, '1/1/' + @anio) + (@NoSemana-1), 7) AS FechaInicio)
SET @FechaFin=(select distinct DATEADD(wk, DATEDIFF(wk, 5, '1/1/' + @anio) + (@NoSemana-1), 6) AS FechaFin)
-- AJUSTE 2026-10-06: en Torreon las vacaciones no cuentan como asistencia

IF OBJECT_ID('tempdb..#ASISTENCIA') IS NOT NULL DROP TABLE #ASISTENCIA;
IF OBJECT_ID('tempdb..#ASISTENCIA2') IS NOT NULL DROP TABLE #ASISTENCIA2;

 SELECT distinct
 @NoSemana AS NoSemana
 ,@anio AS anio
 ,@planta as planta
 ,Numero COLLATE DATABASE_DEFAULT as Numero
 ,
 ----------------------------FALTAS  DIAS --------------------------------------------------------------------------
 CASE WHEN ISNULL(inc_lunes.IncExiste,0)=1 AND (inc_lunes.Completa=1) THEN
  CASE WHEN inc_lunes.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN LunesEntrada='' AND LunesSalida='' AND  idhorario_Lunes<>1
  THEN 1
  ELSE(CASE WHEN isnull(LunesEntrada,'0')='0' AND isnull(LunesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Lunes,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaLunes
 ,CASE WHEN ISNULL(inc_martes.IncExiste,0)=1 AND (inc_martes.Completa=1) THEN
  CASE WHEN inc_martes.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN MartesEntrada='' AND MartesSalida='' AND  idhorario_Martes<>1
  THEN 1
  ELSE(CASE WHEN isnull(MartesEntrada,'0')='0' AND isnull(MartesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Martes,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaMartes
 ,CASE WHEN ISNULL(inc_miercoles.IncExiste,0)=1 AND (inc_miercoles.Completa=1) THEN
  CASE WHEN inc_miercoles.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN MiercolesEntrada='' AND MiercolesSalida='' AND  idhorario_Miercoles<>1
  THEN 1
  ELSE(CASE WHEN isnull(MiercolesEntrada,'0')='0' AND isnull(MiercolesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Miercoles,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaMiercoles
 ,CASE WHEN ISNULL(inc_jueves.IncExiste,0)=1 AND (inc_jueves.Completa=1) THEN
  CASE WHEN inc_jueves.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN JuevesEntrada='' AND JuevesSalida='' AND  idhorario_Jueves<>1
  THEN 1
  ELSE(CASE WHEN isnull(JuevesEntrada,'0')='0' AND isnull(JuevesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Jueves,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaJueves
 ,CASE WHEN ISNULL(inc_viernes.IncExiste,0)=1 AND (inc_viernes.Completa=1) THEN
  CASE WHEN inc_viernes.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN ViernesEntrada='' AND ViernesSalida='' AND  idhorario_Viernes<>1
  THEN 1
  ELSE(CASE WHEN isnull(ViernesEntrada,'0')='0' AND isnull(ViernesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Viernes,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaViernes
 ,CASE WHEN ISNULL(inc_sabado.IncExiste,0)=1 AND (inc_sabado.Completa=1) THEN
  CASE WHEN inc_sabado.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN SabadoEntrada='' AND SabadoSalida='' AND  idhorario_Sabado<>1
  THEN 1
  ELSE(CASE WHEN isnull(SabadoEntrada,'0')='0' AND isnull(SabadoSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Sabado,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaSabado
 ,CASE WHEN ISNULL(inc_domingo.IncExiste,0)=1 AND (inc_domingo.Completa=1) THEN
  CASE WHEN inc_domingo.GoceDia=1 THEN 0 ELSE 1 END
 ELSE
  CASE WHEN DomingoEntrada='' AND DomingoSalida='' AND  idhorario_Domingo<>1
  THEN 1
  ELSE(CASE WHEN isnull(DomingoEntrada,'0')='0' AND isnull(DomingoSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Domingo,1) =1 THEN 0 ELSE 1 END
  ELSE 0 END
   )END
 END FaltaDomingo
 ----------------------------FIN FALTAS DIAS -----------------------------------------------------------------------------------

 ----------------------------ASISTENCIA  DIAS --------------------------------------------------------------------------
  ----------------------------ASISTENCIA  DIAS --------------------------------------------------------------------------
 -- AJUSTE 2026-10-06: en Torreon (@esTorreon=1) un dia de VACACIONES (VAC)
 -- no es falta, pero tampoco cuenta como asistencia. En las demas plantas,
 -- y para cualquier otra incidencia con goce, se mantiene Asistencia=1.
 ,CASE WHEN ISNULL(inc_lunes.IncExiste,0)=1 AND (inc_lunes.Completa=1) THEN
  CASE WHEN inc_lunes.GoceDia=1     AND NOT (@esTorreon=1 AND ISNULL(inc_lunes.ConceptoCorto,'')=@ConceptoVacaciones)     THEN 1 ELSE 0 END
 ELSE
  CASE WHEN LunesEntrada='' AND LunesSalida='' AND  idhorario_Lunes<>1
  THEN 0
  ELSE(CASE WHEN isnull(LunesEntrada,'0')='0' AND isnull(LunesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Lunes,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaLunes
 ,CASE WHEN ISNULL(inc_martes.IncExiste,0)=1 AND (inc_martes.Completa=1) THEN
  CASE WHEN inc_martes.GoceDia=1    AND NOT (@esTorreon=1 AND ISNULL(inc_martes.ConceptoCorto,'')=@ConceptoVacaciones)    THEN 1 ELSE 0 END
 ELSE
  CASE WHEN MartesEntrada='' AND MartesSalida='' AND  idhorario_Martes<>1
  THEN 0
  ELSE(CASE WHEN isnull(MartesEntrada,'0')='0' AND isnull(MartesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Martes,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaMartes
 ,CASE WHEN ISNULL(inc_miercoles.IncExiste,0)=1 AND (inc_miercoles.Completa=1) THEN
  CASE WHEN inc_miercoles.GoceDia=1 AND NOT (@esTorreon=1 AND ISNULL(inc_miercoles.ConceptoCorto,'')=@ConceptoVacaciones) THEN 1 ELSE 0 END
 ELSE
  CASE WHEN MiercolesEntrada='' AND MiercolesSalida='' AND  idhorario_Miercoles<>1
  THEN 0
  ELSE(CASE WHEN isnull(MiercolesEntrada,'0')='0' AND isnull(MiercolesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Miercoles,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaMiercoles
 ,CASE WHEN ISNULL(inc_jueves.IncExiste,0)=1 AND (inc_jueves.Completa=1) THEN
  CASE WHEN inc_jueves.GoceDia=1    AND NOT (@esTorreon=1 AND ISNULL(inc_jueves.ConceptoCorto,'')=@ConceptoVacaciones)    THEN 1 ELSE 0 END
 ELSE
  CASE WHEN JuevesEntrada='' AND JuevesSalida='' AND  idhorario_Jueves<>1
  THEN 0
  ELSE(CASE WHEN isnull(JuevesEntrada,'0')='0' AND isnull(JuevesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Jueves,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaJueves
 ,CASE WHEN ISNULL(inc_viernes.IncExiste,0)=1 AND (inc_viernes.Completa=1) THEN
  CASE WHEN inc_viernes.GoceDia=1   AND NOT (@esTorreon=1 AND ISNULL(inc_viernes.ConceptoCorto,'')=@ConceptoVacaciones)   THEN 1 ELSE 0 END
 ELSE
  CASE WHEN ViernesEntrada='' AND ViernesSalida='' AND  idhorario_Viernes<>1
  THEN 0
  ELSE(CASE WHEN isnull(ViernesEntrada,'0')='0' AND isnull(ViernesSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Viernes,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaViernes
 ,CASE WHEN ISNULL(inc_sabado.IncExiste,0)=1 AND (inc_sabado.Completa=1) THEN
  CASE WHEN inc_sabado.GoceDia=1    AND NOT (@esTorreon=1 AND ISNULL(inc_sabado.ConceptoCorto,'')=@ConceptoVacaciones)    THEN 1 ELSE 0 END
 ELSE
  CASE WHEN SabadoEntrada='' AND SabadoSalida='' AND  idhorario_Sabado<>1
  THEN 0
  ELSE(CASE WHEN isnull(SabadoEntrada,'0')='0' AND isnull(SabadoSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Sabado,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaSabado
 ,CASE WHEN ISNULL(inc_domingo.IncExiste,0)=1 AND (inc_domingo.Completa=1) THEN
  CASE WHEN inc_domingo.GoceDia=1   AND NOT (@esTorreon=1 AND ISNULL(inc_domingo.ConceptoCorto,'')=@ConceptoVacaciones)   THEN 1 ELSE 0 END
 ELSE
  CASE WHEN DomingoEntrada='' AND DomingoSalida='' AND  idhorario_Domingo<>1
  THEN 0
  ELSE(CASE WHEN isnull(DomingoEntrada,'0')='0' AND isnull(DomingoSalida,'0')='0' THEN
   CASE WHEN isnull(idhorario_Domingo,1) =1 THEN 1 ELSE 0 END
  ELSE 1 END
   )END
 END AsistenciaDomingo
 -----------------------------FIN ASISTENCIA DIAS ------------------------------------------------------------------------------------
 -----------------------------FIN ASISTENCIA DIAS ------------------------------------------------------------------------------------

 -----------------------------RETARDOS DIAS ------------------------------------------------------------------------------------
 -- AJUSTE: la COUNT(...)>0 contra incidencias se sustituye por el flag ya
 -- calculado en inc_X (mismo OUTER APPLY que usa Falta/Asistencia arriba).
 -- Las hasta 5 subconsultas repetidas contra horarios por el mismo
 -- idhorario_X se sustituyen por columnas de h_X (JOIN de abajo).
 --
 -- AJUSTE (NUEVO) 2026-09-15: CONVERT(TIME,...) -> TRY_CONVERT(TIME,...).
 -- Si LunesEntrada/etc. trae texto que NO es una hora válida (por
 -- ejemplo, un código corto de concepto como 'vac' que quedó grabado
 -- ahí por el UPDATE final de una corrida anterior, cuando esa fecha
 -- SÍ tenía una incidencia y ahora ya no la tiene -- inc_X.IncExiste=0
 -- en esta corrida pero el texto viejo del concepto sigue en la
 -- columna), CONVERT(TIME,...) truena todo el proc con el error 241
 -- "Conversion failed when converting date and/or time from character
 -- string". TRY_CONVERT regresa NULL en vez de tronar, y ese caso se
 -- trata como "sin retardo" (0) -- no se puede saber si hubo retardo
 -- a partir de un dato corrupto, así que no se penaliza al empleado.
 ,CASE WHEN ISNULL(inc_lunes.IncExiste,0)=1 AND (inc_lunes.Completa=1 OR inc_lunes.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(LunesEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Lunes,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,LunesEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,LunesEntrada) BETWEEN CONVERT(TIME,h_lunes.entrada_Desde) AND CONVERT(TIME,h_lunes.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_lunes.id IS NULL OR idhorario_Lunes=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoLunes
 ,CASE WHEN ISNULL(inc_martes.IncExiste,0)=1 AND (inc_martes.Completa=1 OR inc_martes.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(MartesEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Martes,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,MartesEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,MartesEntrada) BETWEEN CONVERT(TIME,h_martes.entrada_Desde) AND CONVERT(TIME,h_martes.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_martes.id IS NULL OR idhorario_Martes=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoMartes
 ,CASE WHEN ISNULL(inc_miercoles.IncExiste,0)=1 AND (inc_miercoles.Completa=1 OR inc_miercoles.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(MiercolesEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Miercoles,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,MiercolesEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,MiercolesEntrada) BETWEEN CONVERT(TIME,h_miercoles.entrada_Desde) AND CONVERT(TIME,h_miercoles.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_miercoles.id IS NULL OR idhorario_Miercoles=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoMiercoles
 ,CASE WHEN ISNULL(inc_jueves.IncExiste,0)=1 AND (inc_jueves.Completa=1 OR inc_jueves.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(JuevesEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Jueves,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,JuevesEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,JuevesEntrada) BETWEEN CONVERT(TIME,h_jueves.entrada_Desde) AND CONVERT(TIME,h_jueves.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_jueves.id IS NULL OR idhorario_Jueves=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoJueves
 ,CASE WHEN ISNULL(inc_viernes.IncExiste,0)=1 AND (inc_viernes.Completa=1 OR inc_viernes.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(ViernesEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Viernes,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,ViernesEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,ViernesEntrada) BETWEEN CONVERT(TIME,h_viernes.entrada_Desde) AND CONVERT(TIME,h_viernes.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_viernes.id IS NULL OR idhorario_Viernes=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoViernes
 ,CASE WHEN ISNULL(inc_sabado.IncExiste,0)=1 AND (inc_sabado.Completa=1 OR inc_sabado.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(SabadoEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Sabado,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,SabadoEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,SabadoEntrada) BETWEEN CONVERT(TIME,h_sabado.entrada_Desde) AND CONVERT(TIME,h_sabado.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_sabado.id IS NULL OR idhorario_Sabado=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoSabado
 ,CASE WHEN ISNULL(inc_domingo.IncExiste,0)=1 AND (inc_domingo.Completa=1 OR inc_domingo.CubreEntrada=1) THEN 0
 ELSE
  CASE WHEN isnull(DomingoEntrada,'0')!='0' THEN
   CASE WHEN isnull(idhorario_Domingo,1) =1 THEN 0
   ELSE
    CASE WHEN TRY_CONVERT(TIME,DomingoEntrada) IS NULL THEN 0
     WHEN TRY_CONVERT(TIME,DomingoEntrada) BETWEEN CONVERT(TIME,h_domingo.entrada_Desde) AND CONVERT(TIME,h_domingo.entrada_Inicio)
     THEN 0
     ELSE CASE WHEN h_domingo.id IS NULL OR idhorario_Domingo=1 THEN 0 ELSE 1 END
    END
   END
  ELSE 0 END
 END RetardoDomingo
  -------------------------------------FIN RETARDOS DIAS-----------------------------------------------------------------------------------

  ------------------------------------TIEMPO EXTRA DIAS ------------------------------------------------------------------------------------
  -- AJUSTE: misma lógica exacta que el original, solo que cada
  -- "(select distinct TOP 1 columna FROM horarios where id=idhorario_X)"
  -- se sustituye por h_X.columna (un solo JOIN, no N subconsultas), y el
  -- "(select COUNT(...) FROM incidencias ...)>0" se sustituye por
  -- ISNULL(inc_X.IncExiste,0)=1 (mismo cálculo que ya se hizo arriba).
  --
  -- AJUSTE (NUEVO) 2026-09-15b: cuando el horario tiene ventana fija de
  -- entrada (entrada_Inicio<>'00:00:00'), antes el cálculo cambiaba de
  -- fórmula si el empleado llegó tarde: usaba (salida real - entrada
  -- real) en vez de (salida real - entrada_Inicio programada). Esto
  -- hacía que un retardo "se comiera" tiempo extra real trabajado
  -- después de la hora de salida, porque al restar después la
  -- duración completa del turno programado, el resultado podía dar
  -- 0 aunque el empleado se hubiera quedado horas extra genuinas.
  -- Ahora SIEMPRE se mide desde entrada_Inicio (la hora de entrada
  -- programada), sin importar si hubo retardo o no -- exactamente
  -- igual que ya se hacía para quien llegó a tiempo. Con esto, el
  -- tiempo extra se calcula únicamente contra la hora de salida
  -- programada, ignorando por completo la entrada tardía.
  -- AJUSTE (NUEVO) 2026-09-15c: si falta la checada de ENTRADA pero SÍ
  -- hay checada de SALIDA (y el horario es de ventana fija, es decir
  -- entrada_Inicio<>'00:00:00'), antes esto se reportaba como 0 horas
  -- extra sin condición alguna. Ahora, igual que con el retardo, se usa
  -- la hora de entrada PROGRAMADA (entrada_Inicio) como punto de
  -- partida en vez de exigir la entrada real -- así si el empleado no
  -- checó su entrada pero sí trabajó tiempo extra genuino (se quedó
  -- después de su salida programada), sí se le cuenta. Esto solo
  -- aplica a horario de ventana fija; en horario flexible
  -- (entrada_Inicio='00:00:00') no hay una hora de entrada programada
  -- de la cual partir, así que ahí se sigue exigiendo la entrada real.
  -- AJUSTE (NUEVO) 2026-09-15: el guard de entrada ahora TAMBIÉN exige
  -- que LunesEntrada/LunesSalida (y para tipo=1, el día siguiente) sean
  -- horas válidas (TRY_CONVERT no NULL). Mismo razonamiento que en
  -- RETARDOS DIAS: un texto corrupto/código de concepto viejo en estas
  -- columnas ya no truena todo el proc con el error 241 -- ese día
  -- simplemente se reporta como '00:00:00' de tiempo extra.
      -- AJUSTE 2026-09-28: sin salida valida o con incidencia -> 0 extra.
  -- El calculo de horas trabajadas/programadas esta en los CROSS APPLY
  -- x_X / i_X / te_X de abajo.
 ,CASE WHEN (ISNULL(inc_lunes.IncExiste,0) = 1 AND inc_lunes.Completa = 1) OR te_lunes.TrabSeg IS NULL
        OR te_lunes.TrabSeg - te_lunes.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_lunes.TrabSeg - te_lunes.ProgSeg, 0)
  END AS LunesTiempoExtra
 ,CASE WHEN (ISNULL(inc_martes.IncExiste,0) = 1 AND inc_martes.Completa = 1) OR te_martes.TrabSeg IS NULL
        OR te_martes.TrabSeg - te_martes.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_martes.TrabSeg - te_martes.ProgSeg, 0)
  END AS MartesTiempoExtra
 ,CASE WHEN (ISNULL(inc_miercoles.IncExiste,0) = 1 AND inc_miercoles.Completa = 1) OR te_miercoles.TrabSeg IS NULL
        OR te_miercoles.TrabSeg - te_miercoles.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_miercoles.TrabSeg - te_miercoles.ProgSeg, 0)
  END AS MiercolesTiempoExtra
 ,CASE WHEN (ISNULL(inc_jueves.IncExiste,0) = 1 AND inc_jueves.Completa = 1) OR te_jueves.TrabSeg IS NULL
        OR te_jueves.TrabSeg - te_jueves.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_jueves.TrabSeg - te_jueves.ProgSeg, 0)
  END AS JuevesTiempoExtra
 ,CASE WHEN (ISNULL(inc_viernes.IncExiste,0) = 1 AND inc_viernes.Completa = 1) OR te_viernes.TrabSeg IS NULL
        OR te_viernes.TrabSeg - te_viernes.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_viernes.TrabSeg - te_viernes.ProgSeg, 0)
  END AS ViernesTiempoExtra
 ,CASE WHEN (ISNULL(inc_sabado.IncExiste,0) = 1 AND inc_sabado.Completa = 1) OR te_sabado.TrabSeg IS NULL
        OR te_sabado.TrabSeg - te_sabado.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_sabado.TrabSeg - te_sabado.ProgSeg, 0)
  END AS SabadoTiempoExtra
 ,CASE WHEN (ISNULL(inc_domingo.IncExiste,0) = 1 AND inc_domingo.Completa = 1) OR te_domingo.TrabSeg IS NULL
        OR te_domingo.TrabSeg - te_domingo.ProgSeg <= 0
       THEN '00:00:00'
       ELSE dbo.fnSegundos(te_domingo.TrabSeg - te_domingo.ProgSeg, 0)
  END AS DomingoTiempoExtra
 ----------------------------------------------------FIN TIEMPO EXTRA DIAS---------------------------------------------------------------------------

 -- Se cargan aquí los datos de incidencia por día (existe/goce/concepto)
 -- para que el UPDATE final ya no tenga que volver a consultar
 -- incidencias/conceptos por cuarta vez.
 ,ISNULL(inc_lunes.IncExiste,0)      AS IncExisteLunes,      inc_lunes.ConceptoCorto      AS ConceptoCortoLunes, ISNULL(inc_lunes.Cubre, '1111') AS CubreLunes
 ,ISNULL(inc_martes.IncExiste,0)     AS IncExisteMartes,     inc_martes.ConceptoCorto     AS ConceptoCortoMartes, ISNULL(inc_martes.Cubre, '1111') AS CubreMartes
 ,ISNULL(inc_miercoles.IncExiste,0)  AS IncExisteMiercoles,  inc_miercoles.ConceptoCorto  AS ConceptoCortoMiercoles, ISNULL(inc_miercoles.Cubre, '1111') AS CubreMiercoles
 ,ISNULL(inc_jueves.IncExiste,0)     AS IncExisteJueves,     inc_jueves.ConceptoCorto     AS ConceptoCortoJueves, ISNULL(inc_jueves.Cubre, '1111') AS CubreJueves
 ,ISNULL(inc_viernes.IncExiste,0)    AS IncExisteViernes,    inc_viernes.ConceptoCorto    AS ConceptoCortoViernes, ISNULL(inc_viernes.Cubre, '1111') AS CubreViernes
 ,ISNULL(inc_sabado.IncExiste,0)     AS IncExisteSabado,     inc_sabado.ConceptoCorto     AS ConceptoCortoSabado, ISNULL(inc_sabado.Cubre, '1111') AS CubreSabado
 ,ISNULL(inc_domingo.IncExiste,0)    AS IncExisteDomingo,    inc_domingo.ConceptoCorto    AS ConceptoCortoDomingo, ISNULL(inc_domingo.Cubre, '1111') AS CubreDomingo

INTO #ASISTENCIA
FROM asistenciaBioStar as a
-- ============================================================
-- Un solo JOIN por día a horarios (PK id), en vez de repetir
-- "SELECT TOP 1 columna FROM horarios WHERE id=idhorario_X" muchas
-- veces por la misma fila.
-- ============================================================
LEFT JOIN horarios AS h_lunes     ON h_lunes.id     = a.idhorario_Lunes
LEFT JOIN horarios AS h_martes    ON h_martes.id    = a.idhorario_Martes
LEFT JOIN horarios AS h_miercoles ON h_miercoles.id = a.idhorario_Miercoles
LEFT JOIN horarios AS h_jueves    ON h_jueves.id    = a.idhorario_Jueves
LEFT JOIN horarios AS h_viernes   ON h_viernes.id   = a.idhorario_Viernes
LEFT JOIN horarios AS h_sabado    ON h_sabado.id    = a.idhorario_Sabado
LEFT JOIN horarios AS h_domingo   ON h_domingo.id   = a.idhorario_Domingo
-- ============================================================
-- AJUSTE 2026-09-22: corrige el calculo de Tiempo Extra para horario de
-- ventana fija que cruza medianoche (nocturno, tipo=1). Antes ocurrian dos
-- problemas juntos:
--   1) DATEDIFF entre horas de reloj (TIME) no cruza medianoche por si
--      solo -- si la hora de cierre es "menor" que entrada_Inicio (turno
--      nocturno), el resultado crudo sale NEGATIVO en vez de sumar las 24h
--      que realmente transcurrieron. Ni las horas trabajadas ni la
--      duracion programada del turno se corregian, y la resta de dos
--      negativos parecidos dejaba un residuo absurdo cercano a 24h menos
--      la duracion real del turno (turnos normales de ~8.5h saliendo con
--      15+ horas de "extra").
--   2) Para nocturno se usaba SIEMPRE la Entrada del dia siguiente como si
--      fuera el punch de salida del dia actual cruzando medianoche -- pero
--      cuando el empleado SI trabaja tambien el dia siguiente (comun en
--      horarios de 6 dias), esa Entrada es la de SU PROPIO turno del dia
--      siguiente, no la salida del dia actual. Ahora se prefiere la
--      Salida real registrada ese mismo dia cuando existe y es una hora
--      valida; solo se usa la Entrada del dia siguiente como respaldo si
--      la Salida viene vacia o invalida (mismo comportamiento de antes
--      para ese caso extremo).
-- ============================================================
-- ============================================================
-- AJUSTE 2026-09-28: calculo de horas trabajadas por dia.
--  - Si NO hay checada de salida valida -> no hay tiempo extra
--    (ya no se usa la Entrada del dia siguiente como respaldo; desde
--    2026-09-24 ActualizaChecadasBiostar agrupa las checadas por dia
--    de turno, asi que la salida del nocturno ya viene en su dia).
--  - Horario fijo: se mide desde entrada_Inicio (programada).
--    Horario flexible: se mide desde la entrada real.
--  - +24h solo para nocturno (tipo=1) cuando la salida cae en la
--    madrugada (antes de entrada_Desde). En turno de dia, una salida
--    anterior a la entrada da negativo -> 0 extra.
-- ============================================================
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.LunesEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.LunesSalida),''))
) x_lunes
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_lunes.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_lunes.entrada_Inicio AS TIME)
                       ELSE x_lunes.EntradaT END
) i_lunes
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_lunes.SalidaT IS NULL OR i_lunes.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_lunes.IniT, x_lunes.SalidaT)
                          + CASE WHEN ISNULL(h_lunes.tipo,0) = 1
                                  AND x_lunes.SalidaT < i_lunes.IniT
                                  AND ( ISNULL(h_lunes.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_lunes.SalidaT < CAST(ISNULL(h_lunes.entrada_Desde, h_lunes.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_lunes.entrada_Inicio, h_lunes.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_lunes.entrada_Inicio, h_lunes.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_lunes
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.MartesEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.MartesSalida),''))
) x_martes
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_martes.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_martes.entrada_Inicio AS TIME)
                       ELSE x_martes.EntradaT END
) i_martes
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_martes.SalidaT IS NULL OR i_martes.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_martes.IniT, x_martes.SalidaT)
                          + CASE WHEN ISNULL(h_martes.tipo,0) = 1
                                  AND x_martes.SalidaT < i_martes.IniT
                                  AND ( ISNULL(h_martes.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_martes.SalidaT < CAST(ISNULL(h_martes.entrada_Desde, h_martes.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_martes.entrada_Inicio, h_martes.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_martes.entrada_Inicio, h_martes.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_martes
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.MiercolesEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.MiercolesSalida),''))
) x_miercoles
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_miercoles.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_miercoles.entrada_Inicio AS TIME)
                       ELSE x_miercoles.EntradaT END
) i_miercoles
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_miercoles.SalidaT IS NULL OR i_miercoles.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_miercoles.IniT, x_miercoles.SalidaT)
                          + CASE WHEN ISNULL(h_miercoles.tipo,0) = 1
                                  AND x_miercoles.SalidaT < i_miercoles.IniT
                                  AND ( ISNULL(h_miercoles.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_miercoles.SalidaT < CAST(ISNULL(h_miercoles.entrada_Desde, h_miercoles.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_miercoles.entrada_Inicio, h_miercoles.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_miercoles.entrada_Inicio, h_miercoles.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_miercoles
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.JuevesEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.JuevesSalida),''))
) x_jueves
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_jueves.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_jueves.entrada_Inicio AS TIME)
                       ELSE x_jueves.EntradaT END
) i_jueves
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_jueves.SalidaT IS NULL OR i_jueves.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_jueves.IniT, x_jueves.SalidaT)
                          + CASE WHEN ISNULL(h_jueves.tipo,0) = 1
                                  AND x_jueves.SalidaT < i_jueves.IniT
                                  AND ( ISNULL(h_jueves.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_jueves.SalidaT < CAST(ISNULL(h_jueves.entrada_Desde, h_jueves.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_jueves.entrada_Inicio, h_jueves.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_jueves.entrada_Inicio, h_jueves.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_jueves
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.ViernesEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.ViernesSalida),''))
) x_viernes
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_viernes.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_viernes.entrada_Inicio AS TIME)
                       ELSE x_viernes.EntradaT END
) i_viernes
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_viernes.SalidaT IS NULL OR i_viernes.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_viernes.IniT, x_viernes.SalidaT)
                          + CASE WHEN ISNULL(h_viernes.tipo,0) = 1
                                  AND x_viernes.SalidaT < i_viernes.IniT
                                  AND ( ISNULL(h_viernes.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_viernes.SalidaT < CAST(ISNULL(h_viernes.entrada_Desde, h_viernes.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_viernes.entrada_Inicio, h_viernes.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_viernes.entrada_Inicio, h_viernes.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_viernes
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.SabadoEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.SabadoSalida),''))
) x_sabado
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_sabado.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_sabado.entrada_Inicio AS TIME)
                       ELSE x_sabado.EntradaT END
) i_sabado
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_sabado.SalidaT IS NULL OR i_sabado.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_sabado.IniT, x_sabado.SalidaT)
                          + CASE WHEN ISNULL(h_sabado.tipo,0) = 1
                                  AND x_sabado.SalidaT < i_sabado.IniT
                                  AND ( ISNULL(h_sabado.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_sabado.SalidaT < CAST(ISNULL(h_sabado.entrada_Desde, h_sabado.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_sabado.entrada_Inicio, h_sabado.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_sabado.entrada_Inicio, h_sabado.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_sabado
CROSS APPLY (
    SELECT EntradaT = TRY_CONVERT(TIME, NULLIF(RTRIM(a.DomingoEntrada),'')),
           SalidaT  = TRY_CONVERT(TIME, NULLIF(RTRIM(a.DomingoSalida),''))
) x_domingo
CROSS APPLY (
    SELECT IniT = CASE WHEN ISNULL(h_domingo.entrada_Inicio,'00:00:00') <> '00:00:00'
                       THEN CAST(h_domingo.entrada_Inicio AS TIME)
                       ELSE x_domingo.EntradaT END
) i_domingo
CROSS APPLY (
    SELECT
        TrabSeg = CASE WHEN x_domingo.SalidaT IS NULL OR i_domingo.IniT IS NULL THEN NULL
                       ELSE DATEDIFF(S, i_domingo.IniT, x_domingo.SalidaT)
                          + CASE WHEN ISNULL(h_domingo.tipo,0) = 1
                                  AND x_domingo.SalidaT < i_domingo.IniT
                                  AND ( ISNULL(h_domingo.entrada_Inicio,'00:00:00') = '00:00:00'
                                        OR x_domingo.SalidaT < CAST(ISNULL(h_domingo.entrada_Desde, h_domingo.entrada_Inicio) AS TIME) )
                                 THEN 86400 ELSE 0 END
                  END,
        ProgSeg = ISNULL(DATEDIFF(S, h_domingo.entrada_Inicio, h_domingo.salida_Inicio), 0)
                + CASE WHEN DATEDIFF(S, h_domingo.entrada_Inicio, h_domingo.salida_Inicio) < 0 THEN 86400 ELSE 0 END
) te_domingo
-- ============================================================
-- OUTER APPLY por día (uno por día, como en la versión anterior),
-- extendido para traer también el descripcionCorta del concepto,
-- así el mismo resultado sirve para Falta/Asistencia, Retardo,
-- TiempoExtra Y el UPDATE final -- ya no se repite la consulta.
-- ============================================================
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND CAST(a.FechaInicio AS date) >= inc.FechaInicio
   AND CAST(a.FechaInicio AS date) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_lunes
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND Convert(date,DATEADD(DD, +1, a.FechaInicio)) >= inc.FechaInicio
   AND Convert(date,DATEADD(DD, +1, a.FechaInicio)) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_martes
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND Convert(date,DATEADD(DD, +2, a.FechaInicio)) >= inc.FechaInicio
   AND Convert(date,DATEADD(DD, +2, a.FechaInicio)) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_miercoles
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND Convert(date,DATEADD(DD, +3, a.FechaInicio)) >= inc.FechaInicio
   AND Convert(date,DATEADD(DD, +3, a.FechaInicio)) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_jueves
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND Convert(date,DATEADD(DD, +4, a.FechaInicio)) >= inc.FechaInicio
   AND Convert(date,DATEADD(DD, +4, a.FechaInicio)) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_viernes
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND Convert(date,DATEADD(DD, +5, a.FechaInicio)) >= inc.FechaInicio
   AND Convert(date,DATEADD(DD, +5, a.FechaInicio)) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_sabado
OUTER APPLY (
 SELECT TOP 1 1 AS IncExiste, c.goceSueldo AS GoceDia, c.descripcionCorta AS ConceptoCorto,
        CASE WHEN inc.cubreEntrada = 1 AND inc.cubreEntradaComedor = 1 AND inc.cubreSalidaComedor = 1 AND inc.cubreSalida = 1 THEN 1 ELSE 0 END AS Completa,
        CAST(inc.cubreEntrada AS INT) AS CubreEntrada,
        CONCAT(CAST(inc.cubreEntrada AS INT), CAST(inc.cubreEntradaComedor AS INT), CAST(inc.cubreSalidaComedor AS INT), CAST(inc.cubreSalida AS INT)) AS Cubre
 FROM [Nominas].[dbo].[incidencias] AS inc
 INNER JOIN [Nominas].[dbo].[conceptos] AS c ON c.id = inc.concepto_id
 WHERE inc.estatus = 1
   AND inc.empleado_id = TRY_CAST(a.Numero AS INT)
   AND CAST(a.FechaFin AS date) >= inc.FechaInicio
   AND CAST(a.FechaFin AS date) <= inc.fechaFin
 ORDER BY inc.FechaInicio DESC
) inc_domingo
WHERE NoSemana=@NoSemana and anio=@anio and empresa_id=@planta
order by Numero


 select distinct
    NoSemana
   ,anio
   ,planta
   ,Numero COLLATE DATABASE_DEFAULT as Numero
   ,case when a.FaltaLunes=1 then 0 else  a.RetardoLunes     end as RetardoLunes
   ,case when a.FaltaMartes=1 then 0 else a.RetardoMartes    end as    RetardoMartes
   ,case when a.FaltaMiercoles=1 then 0 else a.RetardoMiercoles end  as RetardoMiercoles
   ,case when a.FaltaJueves =1  then 0 else a.RetardoJueves   end  as RetardoJueves
   ,case when a.FaltaViernes =1  then 0 else a.RetardoViernes  end as RetardoViernes
   ,case when a.FaltaSabado =1  then 0 else a.RetardoSabado   end  as RetardoSabado
   ,case when a.FaltaDomingo =1   then 0 else a.RetardoDomingo  end   as RetardoDomingo
   -- AJUSTE: rotativo/cantidad de horarios_empleado se consultaban 3
   -- veces (mismo empleado, mismo rango de fechas); ahora 1 sola vez
   -- vía OUTER APPLY (he.rotativo, he.cantidad).
   ,(
    case when he.rotativo = 1 then
      case when he.cantidad >=((a.AsistenciaLunes)+(a.AsistenciaMartes)+(a.AsistenciaMiercoles)+(a.AsistenciaJueves)+(a.AsistenciaViernes)+(a.AsistenciaSabado)
      +(a.AsistenciaDomingo))
      then 0
      else
      case when he.cantidad <((a.AsistenciaLunes)+(a.AsistenciaMartes)+(a.AsistenciaMiercoles)+(a.AsistenciaJueves)+(a.AsistenciaViernes)+(a.AsistenciaSabado)
      +(a.AsistenciaDomingo))
      then
      (he.cantidad -((a.AsistenciaLunes)+(a.AsistenciaMartes)+(a.AsistenciaMiercoles)+(a.AsistenciaJueves)+(a.AsistenciaViernes)+(a.AsistenciaSabado)
      +(a.AsistenciaDomingo)))
      else
    ((a.FaltaLunes)
   +(a.FaltaMartes)
   +(a.FaltaMiercoles)
   +(a.FaltaJueves)
   +(a.FaltaViernes)
   +(a.FaltaSabado)
   +(a.FaltaDomingo))
   end end end)
    as FaltasTotales
   ,(
    (a.AsistenciaLunes)
   +(a.AsistenciaMartes)
   +(a.AsistenciaMiercoles)
   +(a.AsistenciaJueves)
   +(a.AsistenciaViernes)
   +(a.AsistenciaSabado)
   +(a.AsistenciaDomingo)
   ) as AsistenciasTotales
   ,
   (
   (case when a.FaltaLunes=1 then 0 else  a.RetardoLunes     end)+
   (case when a.FaltaMartes=1 then 0 else a.RetardoMartes    end)+
   (case when a.FaltaMiercoles=1 then 0 else a.RetardoMiercoles end)+
   (case when a.FaltaJueves =1  then 0 else a.RetardoJueves   end)+
   (case when a.FaltaViernes =1  then 0 else a.RetardoViernes  end)+
   (case when a.FaltaSabado =1  then 0 else a.RetardoSabado   end)+
   (case when a.FaltaDomingo =1   then 0 else a.RetardoDomingo  end)
   )
    as RetardoSemanal
   ,a.FaltaLunes
   ,a.FaltaMartes
   ,a.FaltaMiercoles
   ,a.FaltaJueves
   ,a.FaltaViernes
   ,a.FaltaSabado
   ,a.FaltaDomingo
   ,a.AsistenciaLunes
   ,a.AsistenciaMartes
   ,a.AsistenciaMiercoles
   ,a.AsistenciaJueves
   ,a.AsistenciaViernes
   ,a.AsistenciaSabado
   ,a.AsistenciaDomingo
   -- AJUSTE 2026-10-09: el tiempo extra SÍ se cuenta aunque ese día haya
   -- habido retardo o le falte la checada de entrada. Se revirtió el
   -- ajuste 2026-09-15 que lo ponía en 0 si había retardo: el T.E. se
   -- calcula como (salida − fin de turno programado), así que la entrada
   -- tardía/faltante NO infla el extra (la hora de entrada programada se
   -- cancela en TrabSeg−ProgSeg). Solo se sigue poniendo en 0 si el día
   -- es FALTA (sin entrada y sin salida -> no hay salida de la cual medir).
   ,case when a.FaltaLunes=1 then '00:00:00' else  a.LunesTiempoExtra end  as LunesTiempoExtra
   ,case when a.FaltaMartes=1 then '00:00:00' else  a.MartesTiempoExtra end  as MartesTiempoExtra
   ,case when a.FaltaMiercoles=1 then '00:00:00' else  a.MiercolesTiempoExtra end  as MiercolesTiempoExtra
   ,case when a.FaltaJueves=1 then '00:00:00' else  a.JuevesTiempoExtra end  as JuevesTiempoExtra
   ,case when a.FaltaViernes=1 then '00:00:00' else  a.ViernesTiempoExtra end  as ViernesTiempoExtra
   ,case when a.FaltaSabado=1 then '00:00:00' else  a.SabadoTiempoExtra end  as SabadoTiempoExtra
   ,case when a.FaltaDomingo=1 then '00:00:00' else  a.DomingoTiempoExtra end  as DomingoTiempoExtra
   ,dbo.fnSegundos(
(case when a.FaltaLunes=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.LunesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.LunesTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.LunesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.LunesTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.LunesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.LunesTiempoExtra)) end)   end)
+(case when a.FaltaMartes=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.MartesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MartesTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.MartesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MartesTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.MartesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MartesTiempoExtra)) end)   end)
+(case when a.FaltaMiercoles=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.MiercolesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MiercolesTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.MiercolesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MiercolesTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.MiercolesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.MiercolesTiempoExtra)) end)   end)
+(case when a.FaltaJueves=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.JuevesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.JuevesTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.JuevesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.JuevesTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.JuevesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.JuevesTiempoExtra)) end)   end)
+(case when a.FaltaViernes=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.ViernesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.ViernesTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.ViernesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.ViernesTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.ViernesTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.ViernesTiempoExtra)) end)   end)
+(case when a.FaltaSabado=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.SabadoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.SabadoTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.SabadoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.SabadoTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.SabadoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.SabadoTiempoExtra)) end)   end)
+(case when a.FaltaDomingo=1 then 0 else DATEPART(SECOND, (case when RTRIM(LTRIM(a.DomingoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.DomingoTiempoExtra)) end) )
+ 60 * DATEPART(MINUTE, (case when RTRIM(LTRIM(a.DomingoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.DomingoTiempoExtra)) end) )
+ 3600 * DATEPART(HOUR, case when RTRIM(LTRIM(a.DomingoTiempoExtra)) >'23:59:59'then '00:00:00'else  RTRIM(LTRIM(a.DomingoTiempoExtra)) end)   end)

,0) as TotalTiempoExtra
 INTO #ASISTENCIA2
 FROM #ASISTENCIA as a
  -- AJUSTE 2026-09-30: asignacion activa mas reciente con fecha <= fin de la
 -- semana (antes solo encontraba asignaciones hechas DENTRO de esa semana).
 OUTER APPLY (
  SELECT TOP 1 e.rotativo, e.cantidad
  FROM [Nominas].[dbo].[horarios_empleado] e
  WHERE e.empleado_id = a.Numero COLLATE DATABASE_DEFAULT
    AND e.idEstatus = 1
    AND e.fechaAsignacion <= @FechaFin
  ORDER BY e.fechaAsignacion DESC, e.id DESC
 ) he


 UPDATE
   a
 SET
   a.RetardoLunes                     =x.RetardoLunes
   ,a.RetardoMartes                    =x.RetardoMartes
   ,a.RetardoMiercoles                 =x.RetardoMiercoles
   ,a.RetardoJueves                    =x.RetardoJueves
   ,a.RetardoViernes                   =x.RetardoViernes
   ,a.RetardoSabado                    =x.RetardoSabado
   ,a.RetardoDomingo                   =x.RetardoDomingo
   ,a.RetardoSemanal                   =x.RetardoSemanal
   ,a.FaltaLunes                       =x.FaltaLunes
   ,a.FaltaMartes                      =x.FaltaMartes
   ,a.FaltaMiercoles                   =x.FaltaMiercoles
   ,a.FaltaJueves                      =x.FaltaJueves
   ,a.FaltaViernes                     =x.FaltaViernes
   ,a.FaltaSabado                      =x.FaltaSabado
   ,a.FaltaDomingo                     =x.FaltaDomingo
   ,a.FaltasTotales     =x.FaltasTotales
   ,a.AsistenciaLunes                       =x.AsistenciaLunes
   ,a.AsistenciaMartes                      =x.AsistenciaMartes
   ,a.AsistenciaMiercoles                   =x.AsistenciaMiercoles
   ,a.AsistenciaJueves                      =x.AsistenciaJueves
   ,a.AsistenciaViernes                     =x.AsistenciaViernes
   ,a.AsistenciaSabado                      =x.AsistenciaSabado
   ,a.AsistenciaDomingo                     =x.AsistenciaDomingo
   ,a.AsistenciasTotales     =x.AsistenciasTotales
   ,a.LunesTiempoExtra                 =x.LunesTiempoExtra
   ,a.MartesTiempoExtra   =x.MartesTiempoExtra
   ,a.MiercolesTiempoExtra             =x.MiercolesTiempoExtra
   ,a.JuevesTiempoExtra                =x.JuevesTiempoExtra
   ,a.ViernesTiempoExtra               =x.ViernesTiempoExtra
   ,a.SabadoTiempoExtra                =x.SabadoTiempoExtra
   ,a.DomingoTiempoExtra               =x.DomingoTiempoExtra
   ,a.TotalTiempoExtra               =x.TotalTiempoExtra
 FROM
    asistenciaBioStar AS a
    INNER JOIN #ASISTENCIA2 AS x ON(a.anio= @anio AND a.NoSemana=x.NoSemana
 and a.empresa_id=@planta
 and a.Numero = x.Numero COLLATE Modern_Spanish_CI_AS)


 -- ============================================================
 -- AJUSTE: el UPDATE final ya NO vuelve a consultar incidencias ni
 -- conceptos. Usa #ASISTENCIA (m), que ya trae por día si hay
 -- incidencia (IncExisteX) y el descripcionCorta del concepto
 -- (ConceptoCortoX), calculados una sola vez arriba.
 -- ============================================================
 UPDATE
 a
SET
 a.LunesEntrada          = CASE WHEN m.IncExisteLunes=1 AND SUBSTRING(m.CubreLunes,1,1)='1' THEN m.ConceptoCortoLunes ELSE a.LunesEntrada END
 ,a.LunesEntradaComedor = CASE WHEN m.IncExisteLunes=1 AND m.CubreLunes='1111' THEN '' WHEN m.IncExisteLunes=1 AND SUBSTRING(m.CubreLunes,2,1)='1' THEN m.ConceptoCortoLunes ELSE a.LunesEntradaComedor END
 ,a.LunesSalidaComedor = CASE WHEN m.IncExisteLunes=1 AND m.CubreLunes='1111' THEN '' WHEN m.IncExisteLunes=1 AND SUBSTRING(m.CubreLunes,3,1)='1' THEN m.ConceptoCortoLunes ELSE a.LunesSalidaComedor END
 ,a.LunesSalida = CASE WHEN m.IncExisteLunes=1 AND m.CubreLunes='1111' THEN '' WHEN m.IncExisteLunes=1 AND SUBSTRING(m.CubreLunes,4,1)='1' THEN m.ConceptoCortoLunes ELSE a.LunesSalida END

 ,a.MartesEntrada          = CASE WHEN m.IncExisteMartes=1 AND SUBSTRING(m.CubreMartes,1,1)='1' THEN m.ConceptoCortoMartes ELSE a.MartesEntrada END
 ,a.MartesEntradaComedor = CASE WHEN m.IncExisteMartes=1 AND m.CubreMartes='1111' THEN '' WHEN m.IncExisteMartes=1 AND SUBSTRING(m.CubreMartes,2,1)='1' THEN m.ConceptoCortoMartes ELSE a.MartesEntradaComedor END
 ,a.MartesSalidaComedor = CASE WHEN m.IncExisteMartes=1 AND m.CubreMartes='1111' THEN '' WHEN m.IncExisteMartes=1 AND SUBSTRING(m.CubreMartes,3,1)='1' THEN m.ConceptoCortoMartes ELSE a.MartesSalidaComedor END
 ,a.MartesSalida = CASE WHEN m.IncExisteMartes=1 AND m.CubreMartes='1111' THEN '' WHEN m.IncExisteMartes=1 AND SUBSTRING(m.CubreMartes,4,1)='1' THEN m.ConceptoCortoMartes ELSE a.MartesSalida END

 ,a.MiercolesEntrada          = CASE WHEN m.IncExisteMiercoles=1 AND SUBSTRING(m.CubreMiercoles,1,1)='1' THEN m.ConceptoCortoMiercoles ELSE a.MiercolesEntrada END
 ,a.MiercolesEntradaComedor = CASE WHEN m.IncExisteMiercoles=1 AND m.CubreMiercoles='1111' THEN '' WHEN m.IncExisteMiercoles=1 AND SUBSTRING(m.CubreMiercoles,2,1)='1' THEN m.ConceptoCortoMiercoles ELSE a.MiercolesEntradaComedor END
 ,a.MiercolesSalidaComedor = CASE WHEN m.IncExisteMiercoles=1 AND m.CubreMiercoles='1111' THEN '' WHEN m.IncExisteMiercoles=1 AND SUBSTRING(m.CubreMiercoles,3,1)='1' THEN m.ConceptoCortoMiercoles ELSE a.MiercolesSalidaComedor END
 ,a.MiercolesSalida = CASE WHEN m.IncExisteMiercoles=1 AND m.CubreMiercoles='1111' THEN '' WHEN m.IncExisteMiercoles=1 AND SUBSTRING(m.CubreMiercoles,4,1)='1' THEN m.ConceptoCortoMiercoles ELSE a.MiercolesSalida END

 ,a.JuevesEntrada          = CASE WHEN m.IncExisteJueves=1 AND SUBSTRING(m.CubreJueves,1,1)='1' THEN m.ConceptoCortoJueves ELSE a.JuevesEntrada END
 ,a.JuevesEntradaComedor = CASE WHEN m.IncExisteJueves=1 AND m.CubreJueves='1111' THEN '' WHEN m.IncExisteJueves=1 AND SUBSTRING(m.CubreJueves,2,1)='1' THEN m.ConceptoCortoJueves ELSE a.JuevesEntradaComedor END
 ,a.JuevesSalidaComedor = CASE WHEN m.IncExisteJueves=1 AND m.CubreJueves='1111' THEN '' WHEN m.IncExisteJueves=1 AND SUBSTRING(m.CubreJueves,3,1)='1' THEN m.ConceptoCortoJueves ELSE a.JuevesSalidaComedor END
 ,a.JuevesSalida = CASE WHEN m.IncExisteJueves=1 AND m.CubreJueves='1111' THEN '' WHEN m.IncExisteJueves=1 AND SUBSTRING(m.CubreJueves,4,1)='1' THEN m.ConceptoCortoJueves ELSE a.JuevesSalida END

 ,a.ViernesEntrada          = CASE WHEN m.IncExisteViernes=1 AND SUBSTRING(m.CubreViernes,1,1)='1' THEN m.ConceptoCortoViernes ELSE a.ViernesEntrada END
 ,a.ViernesEntradaComedor = CASE WHEN m.IncExisteViernes=1 AND m.CubreViernes='1111' THEN '' WHEN m.IncExisteViernes=1 AND SUBSTRING(m.CubreViernes,2,1)='1' THEN m.ConceptoCortoViernes ELSE a.ViernesEntradaComedor END
 ,a.ViernesSalidaComedor = CASE WHEN m.IncExisteViernes=1 AND m.CubreViernes='1111' THEN '' WHEN m.IncExisteViernes=1 AND SUBSTRING(m.CubreViernes,3,1)='1' THEN m.ConceptoCortoViernes ELSE a.ViernesSalidaComedor END
 ,a.ViernesSalida = CASE WHEN m.IncExisteViernes=1 AND m.CubreViernes='1111' THEN '' WHEN m.IncExisteViernes=1 AND SUBSTRING(m.CubreViernes,4,1)='1' THEN m.ConceptoCortoViernes ELSE a.ViernesSalida END

 ,a.SabadoEntrada          = CASE WHEN m.IncExisteSabado=1 AND SUBSTRING(m.CubreSabado,1,1)='1' THEN m.ConceptoCortoSabado ELSE a.SabadoEntrada END
 ,a.SabadoEntradaComedor = CASE WHEN m.IncExisteSabado=1 AND m.CubreSabado='1111' THEN '' WHEN m.IncExisteSabado=1 AND SUBSTRING(m.CubreSabado,2,1)='1' THEN m.ConceptoCortoSabado ELSE a.SabadoEntradaComedor END
 ,a.SabadoSalidaComedor = CASE WHEN m.IncExisteSabado=1 AND m.CubreSabado='1111' THEN '' WHEN m.IncExisteSabado=1 AND SUBSTRING(m.CubreSabado,3,1)='1' THEN m.ConceptoCortoSabado ELSE a.SabadoSalidaComedor END
 ,a.SabadoSalida = CASE WHEN m.IncExisteSabado=1 AND m.CubreSabado='1111' THEN '' WHEN m.IncExisteSabado=1 AND SUBSTRING(m.CubreSabado,4,1)='1' THEN m.ConceptoCortoSabado ELSE a.SabadoSalida END

 ,a.DomingoEntrada          = CASE WHEN m.IncExisteDomingo=1 AND SUBSTRING(m.CubreDomingo,1,1)='1' THEN m.ConceptoCortoDomingo ELSE a.DomingoEntrada END
 ,a.DomingoEntradaComedor = CASE WHEN m.IncExisteDomingo=1 AND m.CubreDomingo='1111' THEN '' WHEN m.IncExisteDomingo=1 AND SUBSTRING(m.CubreDomingo,2,1)='1' THEN m.ConceptoCortoDomingo ELSE a.DomingoEntradaComedor END
 ,a.DomingoSalidaComedor = CASE WHEN m.IncExisteDomingo=1 AND m.CubreDomingo='1111' THEN '' WHEN m.IncExisteDomingo=1 AND SUBSTRING(m.CubreDomingo,3,1)='1' THEN m.ConceptoCortoDomingo ELSE a.DomingoSalidaComedor END
 ,a.DomingoSalida = CASE WHEN m.IncExisteDomingo=1 AND m.CubreDomingo='1111' THEN '' WHEN m.IncExisteDomingo=1 AND SUBSTRING(m.CubreDomingo,4,1)='1' THEN m.ConceptoCortoDomingo ELSE a.DomingoSalida END

  FROM [Nominas].[dbo].[asistenciaBioStar] as a
  INNER JOIN #ASISTENCIA m
     ON m.anio = @anio AND m.NoSemana = @NoSemana
    AND a.empresa_id = @planta
    AND a.Numero = m.Numero COLLATE DATABASE_DEFAULT

END
GO
