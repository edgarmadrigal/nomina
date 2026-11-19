-- 2026-10-26: las incidencias se aplican solo a la empresa del empleado (o sin empresa).
-- Los numeros de empleado se repiten entre empresas: 119385 es JESUS GABRIEL en PLANCHAS (7)
-- y MARCO ANTONIO en NAZARENO2 (12); las INCA/VAC de Microsip NAZARENO2 le salian al de PLANCHAS.
-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-07-13 (optimizaciÃ³n de rendimiento)
--
-- AJUSTE 2026-10-09: FALTA/ASISTENCIA solo usan el goce de la incidencia cuando ES DE DÃA
--   COMPLETO (inc_X.Completa=1). Una incidencia PARCIAL (p. ej. "Retardo ruta transporte"
--   'tran', que solo cubre la entrada y tiene goceSueldo=1) ya NO fuerza asistencia ni evita
--   la falta: el dÃ­a se decide con las checadas reales. Si no hay ninguna checada, es FALTA
--   (antes el goce de la parcial lo marcaba como asistencia). La condiciÃ³n pasÃ³ de
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
-- POR QUÃ‰ ERA LENTO (diagnÃ³stico):
--
-- Por cada empleado, por cada dÃ­a de la semana, el proc original volvÃ­a a
-- consultar la tabla `incidencias` y la tabla `horarios` desde cero, en
-- 3 lugares distintos e independientes:
--   1) El bloque de Falta/Asistencia de ese dÃ­a
--   2) El bloque de Retardo de ese dÃ­a (COUNT(...)>0 contra incidencias,
--      + hasta 5 subconsultas repetidas contra horarios por el MISMO
--      idhorario_X: entrada_Desde, entrada_Inicio, COUNT(id))
--   3) El bloque de TiempoExtra de ese dÃ­a (otra vez COUNT(...)>0 contra
--      incidencias, + hasta 8-10 subconsultas repetidas contra horarios
--      por el MISMO idhorario_X: entrada_Inicio, tipo, salida_Inicio)
--   4) El UPDATE final (LunesEntrada, MartesEntrada, etc.) volvÃ­a a
--      consultar incidencias/conceptos POR CUARTA VEZ, para las mismas
--      fechas, solo para obtener el descripcionCorta del concepto.
--
-- Multiplicando 7 dÃ­as x hasta 4 lugares x varias subconsultas cada uno,
-- una sola fila de empleado podÃ­a disparar decenas de accesos repetidos
-- a incidencias/horarios que ya se habÃ­an resuelto antes en la misma fila.
--
-- QUÃ‰ CAMBIA EN ESTA VERSIÃ“N (misma lÃ³gica de negocio, sin nuevos
-- supuestos):
--   - Se agrega UN JOIN a horarios por dÃ­a (h_lunes...h_domingo), en vez
--     de repetir "SELECT TOP 1 columna FROM horarios WHERE id=idhorario_X"
--     una y otra vez para la misma columna/dÃ­a.
--   - El OUTER APPLY de incidencias por dÃ­a (inc_lunes...inc_domingo, que
--     ya existÃ­a) se extiende para traer tambiÃ©n el descripcionCorta del
--     concepto (ConceptoCorto), y ese mismo resultado se reutiliza en
--     Falta/Asistencia, Retardo, TiempoExtra Y en el UPDATE final -- ya
--     no se vuelve a consultar incidencias/conceptos para lo mismo.
--   - El UPDATE final ahora hace JOIN contra #ASISTENCIA (que ya trae
--     IncExiste/ConceptoCorto por dÃ­a) en vez de re-derivar todo desde
--     incidencias/conceptos otra vez.
--   - Los 3 SELECTs distintos a horarios_empleado (rotativo, cantidad,
--     cantidad otra vez) para el mismo empleado/rango se consolidan en
--     un solo OUTER APPLY.
--
-- Recuerda correr tambiÃ©n indices_sugeridos.sql (incidencias, conceptos,
-- horarios_empleado) -- sin esos Ã­ndices, aunque se reduzcan las
-- repeticiones, cada consulta a incidencias sigue siendo table scan.
-- =============================================
-------------- EXEC ActualizaChecadasBiostarDias 27,'2026',34,NULL,NULL,NULL
ALTER PROCEDURE [dbo].[ActualizaChecadasBiostarDias]
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
 -- Si LunesEntrada/etc. trae texto que NO es una hora vÃ¡lida (por
 -- ejemplo, un cÃ³digo corto de concepto como 'vac' que quedÃ³ grabado
 -- ahÃ­ por el UPDATE final de una corrida anterior, cuando esa fecha
 -- SÃ tenÃ­a una incidencia y ahora ya no la tiene -- inc_X.IncExiste=0
 -- en esta corrida pero el texto viejo del concepto sigue en la
 -- columna), CONVERT(TIME,...) truena todo el proc con el error 241
 -- "Conversion failed when converting date and/or time from character
 -- string". TRY_CONVERT regresa NULL en vez de tronar, y ese caso se
 -- trata como "sin retardo" (0) -- no se puede saber si hubo retardo
 -- a partir de un dato corrupto, asÃ­ que no se penaliza al empleado.
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
  -- AJUSTE: misma lÃ³gica exacta que el original, solo que cada
  -- "(select distinct TOP 1 columna FROM horarios where id=idhorario_X)"
  -- se sustituye por h_X.columna (un solo JOIN, no N subconsultas), y el
  -- "(select COUNT(...) FROM incidencias ...)>0" se sustituye por
  -- ISNULL(inc_X.IncExiste,0)=1 (mismo cÃ¡lculo que ya se hizo arriba).
  --
  -- AJUSTE (NUEVO) 2026-09-15b: cuando el horario tiene ventana fija de
  -- entrada (entrada_Inicio<>'00:00:00'), antes el cÃ¡lculo cambiaba de
  -- fÃ³rmula si el empleado llegÃ³ tarde: usaba (salida real - entrada
  -- real) en vez de (salida real - entrada_Inicio programada). Esto
  -- hacÃ­a que un retardo "se comiera" tiempo extra real trabajado
  -- despuÃ©s de la hora de salida, porque al restar despuÃ©s la
  -- duraciÃ³n completa del turno programado, el resultado podÃ­a dar
  -- 0 aunque el empleado se hubiera quedado horas extra genuinas.
  -- Ahora SIEMPRE se mide desde entrada_Inicio (la hora de entrada
  -- programada), sin importar si hubo retardo o no -- exactamente
  -- igual que ya se hacÃ­a para quien llegÃ³ a tiempo. Con esto, el
  -- tiempo extra se calcula Ãºnicamente contra la hora de salida
  -- programada, ignorando por completo la entrada tardÃ­a.
  -- AJUSTE (NUEVO) 2026-09-15c: si falta la checada de ENTRADA pero SÃ
  -- hay checada de SALIDA (y el horario es de ventana fija, es decir
  -- entrada_Inicio<>'00:00:00'), antes esto se reportaba como 0 horas
  -- extra sin condiciÃ³n alguna. Ahora, igual que con el retardo, se usa
  -- la hora de entrada PROGRAMADA (entrada_Inicio) como punto de
  -- partida en vez de exigir la entrada real -- asÃ­ si el empleado no
  -- checÃ³ su entrada pero sÃ­ trabajÃ³ tiempo extra genuino (se quedÃ³
  -- despuÃ©s de su salida programada), sÃ­ se le cuenta. Esto solo
  -- aplica a horario de ventana fija; en horario flexible
  -- (entrada_Inicio='00:00:00') no hay una hora de entrada programada
  -- de la cual partir, asÃ­ que ahÃ­ se sigue exigiendo la entrada real.
  -- AJUSTE (NUEVO) 2026-09-15: el guard de entrada ahora TAMBIÃ‰N exige
  -- que LunesEntrada/LunesSalida (y para tipo=1, el dÃ­a siguiente) sean
  -- horas vÃ¡lidas (TRY_CONVERT no NULL). Mismo razonamiento que en
  -- RETARDOS DIAS: un texto corrupto/cÃ³digo de concepto viejo en estas
  -- columnas ya no truena todo el proc con el error 241 -- ese dÃ­a
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

 -- Se cargan aquÃ­ los datos de incidencia por dÃ­a (existe/goce/concepto)
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
-- Un solo JOIN por dÃ­a a horarios (PK id), en vez de repetir
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
-- OUTER APPLY por dÃ­a (uno por dÃ­a, como en la versiÃ³n anterior),
-- extendido para traer tambiÃ©n el descripcionCorta del concepto,
-- asÃ­ el mismo resultado sirve para Falta/Asistencia, Retardo,
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   AND (inc.empresa_id = a.empresa_id OR inc.empresa_id IS NULL)
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
   -- vÃ­a OUTER APPLY (he.rotativo, he.cantidad).
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
   -- AJUSTE 2026-10-09: el tiempo extra SÃ se cuenta aunque ese dÃ­a haya
   -- habido retardo o le falte la checada de entrada. Se revirtiÃ³ el
   -- ajuste 2026-09-15 que lo ponÃ­a en 0 si habÃ­a retardo: el T.E. se
   -- calcula como (salida âˆ’ fin de turno programado), asÃ­ que la entrada
   -- tardÃ­a/faltante NO infla el extra (la hora de entrada programada se
   -- cancela en TrabSegâˆ’ProgSeg). Solo se sigue poniendo en 0 si el dÃ­a
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
 -- conceptos. Usa #ASISTENCIA (m), que ya trae por dÃ­a si hay
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
-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-10-08 (CubreLunes..CubreDomingo)
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
    @planta     VARCHAR(50) = NULL,
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

    BEGIN TRY
        -- Checadas REALES de los días con VACACIONES de día completo (SOLO esos empleados, no todos,
        -- por eso es rápido): en esos días el concepto VAC ocultó las checadas; se recuperan de
        -- dbo.checadas acotando a la planta (idPlanta, evita homónimos de otra empresa) y a la semana
        -- (fechahora, usa el índice). La pantalla muestra "VAC / hora" para que RH lo revise.
        DECLARE @lunes DATE = (SELECT TOP 1 CAST(a.FechaInicio AS date) FROM dbo.asistenciaBioStar a
                               WHERE a.NoSemana = @NoSemana AND a.anio = TRY_CAST(@anio AS INT)
                                 AND a.empresa_id = TRY_CAST(@planta AS INT));
        IF OBJECT_ID('tempdb..#VacReales') IS NOT NULL DROP TABLE #VacReales;
        CREATE TABLE #VacReales (Numero VARCHAR(32) COLLATE DATABASE_DEFAULT, dia INT, Horas VARCHAR(200), PRIMARY KEY (Numero, dia));

        IF @lunes IS NOT NULL
        INSERT INTO #VacReales (Numero, dia, Horas)
        SELECT x.Numero, x.dd,
               STUFF((SELECT '|' + CONVERT(VARCHAR(5), ch.fechahora, 108)
                      FROM dbo.checadas ch
                      WHERE ch.usuarioid_norm = x.Numero COLLATE SQL_Latin1_General_CP1_CI_AS
                        AND ch.idPlanta = TRY_CAST(@planta AS INT)
                        AND ch.fechahora >= x.fecha AND ch.fechahora < DATEADD(day, 1, x.fecha)
                      ORDER BY ch.fechahora FOR XML PATH('')), 1, 1, '')
        FROM (
            SELECT DISTINCT a.Numero COLLATE DATABASE_DEFAULT AS Numero, o.dd, DATEADD(day, o.dd, @lunes) AS fecha
            FROM dbo.asistenciaBioStar a
            CROSS APPLY (VALUES (0),(1),(2),(3),(4),(5),(6)) o(dd)
            WHERE a.NoSemana = @NoSemana AND a.anio = TRY_CAST(@anio AS INT) AND a.empresa_id = TRY_CAST(@planta AS INT)
              AND EXISTS (
                  SELECT 1 FROM dbo.incidencias i JOIN dbo.conceptos c ON c.id = i.concepto_id
                  WHERE i.estatus = 1 AND c.descripcionCorta = 'VAC'
                    AND i.empleado_id = TRY_CAST(a.Numero AS INT)
                    AND (i.empresa_id = TRY_CAST(@planta AS INT) OR i.empresa_id IS NULL)
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
        LEFT JOIN #VacReales rl0 ON rl0.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl0.dia = 0
        LEFT JOIN #VacReales rl1 ON rl1.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl1.dia = 1
        LEFT JOIN #VacReales rl2 ON rl2.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl2.dia = 2
        LEFT JOIN #VacReales rl3 ON rl3.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl3.dia = 3
        LEFT JOIN #VacReales rl4 ON rl4.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl4.dia = 4
        LEFT JOIN #VacReales rl5 ON rl5.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl5.dia = 5
        LEFT JOIN #VacReales rl6 ON rl6.Numero = a.Numero COLLATE DATABASE_DEFAULT AND rl6.dia = 6
        WHERE a.NoSemana   = @NoSemana
          AND a.anio       = TRY_CAST(@anio AS INT)
          AND a.empresa_id = TRY_CAST(@planta AS INT)
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
-- =============================================
-- Author:      Edgar Madrigal
-- Modificado:  2026-07-17
--
--  1) Predicados sargables + OPTION (RECOMPILE).
--  2) Fechas de la semana, incapacidades y vacaciones se calculan UNA vez
--     por (empleado, aÃ±o, semana) en #Semanas / #Incapacidades.
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
ALTER PROCEDURE [dbo].[ConsultaAsistenciaExportarTablaTemporal]
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
    RAISERROR('ConsultaAsistenciaExportarTablaTemporal: asistenciaBioStar tiene registros duplicados (mismo Numero/anio/NoSemana/empresa_id) para: %s. Se usarÃ¡ un solo registro por empleado para no truncar la ejecuciÃ³n, pero conviene revisar el origen de la duplicaciÃ³n en BioStar.', 10, 1, @NumerosDuplicados) WITH NOWAIT;
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
-- PASO 1: (empleado, aÃ±o, semana) con sus fechas Lunes..Domingo
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
-- (empleado, aÃ±o, semana)
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
    JOIN incidencias inc ON inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL)
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
JOIN incidencias inc ON inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL)
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
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaLunes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Lunes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaMartes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Martes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaMiercoles BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Miercoles), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaJueves BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Jueves), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaViernes BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Viernes), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
                               AND s.FechaSabado BETWEEN inc.fechaInicio AND inc.fechaFin)
                 AND ISNULL((SELECT TOP 1 hh.horas_diarias FROM horarios hh WHERE hh.id = a.idhorario_Sabado), 0) > 0
                THEN 1 ELSE 0 END)
        + (CASE WHEN EXISTS (SELECT 1 FROM incidencias inc JOIN conceptos c ON c.id = inc.concepto_id
                             WHERE inc.empleado_id = s.empleado_id AND (inc.empresa_id = TRY_CAST(@planta AS INT) OR inc.empresa_id IS NULL OR @planta IS NULL) AND c.descripcionCorta = @ConceptoVacaciones
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
    -- esos conceptos solo salen cuando se marca Tiempo Extra y el dÃ­a (el domingo) como extra.
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
