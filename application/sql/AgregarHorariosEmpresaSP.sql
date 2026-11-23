USE [Nominas];
GO
-- =============================================
-- 1 si el horario vigente del empleado (la asignación activa más reciente)
-- tiene algún día con horario nocturno (horarios.tipo = 1).
-- =============================================
CREATE OR ALTER FUNCTION dbo.EmpleadoTieneNocturno (@empleado_id VARCHAR(20))   -- texto, como empleados.empleado_id
RETURNS BIT
AS
BEGIN
    DECLARE @r BIT = 0;
    SELECT @r = CASE WHEN EXISTS (
                    SELECT 1 FROM dbo.horarios h
                    WHERE h.tipo = 1
                      AND h.id IN (v.idhorario_lunes, v.idhorario_martes, v.idhorario_miercoles, v.idhorario_jueves,
                                   v.idhorario_viernes, v.idhorario_sabado, v.idhorario_domingo)) THEN 1 ELSE 0 END
    FROM (SELECT TOP 1 he.idhorario_lunes, he.idhorario_martes, he.idhorario_miercoles, he.idhorario_jueves,
                       he.idhorario_viernes, he.idhorario_sabado, he.idhorario_domingo
          FROM dbo.horarios_empleado he
          WHERE he.empleado_id = @empleado_id AND he.idEstatus = 1
          ORDER BY he.fechaAsignacion DESC, he.id DESC) v;
    RETURN @r;
END;
GO

-- =============================================
-- Asigna un horario a todos los empleados activos de una empresa
-- (Asignación de Horarios x Empresa > Agregar).
-- AJUSTE 2026-10-07: @respetarNocturnos = 1 (predeterminado) no le cambia el horario a
--   los empleados cuyo horario vigente tiene algún día nocturno (horarios.tipo = 1);
--   la página pregunta antes. Regresa cuántos se asignaron y cuántos se respetaron.
-- AJUSTE 2026-10-07: @empleados = lista separada por comas de los empleados elegidos en la
--   página ("Solo a algunos"); si viene, solo se asigna a ellos (y @respetarNocturnos no aplica).
-- =============================================
CREATE OR ALTER PROCEDURE [dbo].[AgregarHorariosEmpresaSP]
    @empresa_id INT,
    @idhorario_lunes INT,
    @idhorario_martes INT,
    @idhorario_miercoles INT,
    @idhorario_jueves INT,
    @idhorario_viernes INT,
    @idhorario_sabado INT,
    @idhorario_domingo INT,
    @idEstatus INT = 1,
    @descripcion NVARCHAR(500) = NULL,
    @fechaAsignacion DATE = NULL,
    @cantidad DECIMAL(5,2) = NULL,   -- dias por semana, admite decimales (ej. 5.5)
    @respetarNocturnos BIT = 1,
    @empleados NVARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @lista TABLE (empleado_id VARCHAR(20) PRIMARY KEY, nocturno BIT);   -- empleados.empleado_id es texto
    INSERT INTO @lista (empleado_id, nocturno)
    SELECT DISTINCT e.empleado_id, dbo.EmpleadoTieneNocturnoEmpresa(e.empleado_id, @empresa_id)   -- AJUSTE 2026-11-23: horario de ESTA empresa
    FROM empleados e
    WHERE e.empresa_id = @empresa_id
      AND e.estatus = 'A'
      -- compat 110: sin STRING_SPLIT
      AND (@empleados IS NULL OR ',' + @empleados + ',' LIKE '%,' + CAST(e.empleado_id AS VARCHAR(20)) + ',%');

    INSERT INTO horarios_empleado
        (empleado_id
        , idhorario_lunes
        , idhorario_martes
        , idhorario_miercoles
        , idhorario_jueves
        , idhorario_viernes
        , idhorario_sabado
        , idhorario_domingo
        , idEstatus
        , descripcion
        , fechaAsignacion
        , cantidad,empresa_id)
    SELECT x.empleado_id,
           @idhorario_lunes,
           @idhorario_martes,
           @idhorario_miercoles,
           @idhorario_jueves,
           @idhorario_viernes,
           @idhorario_sabado,
           @idhorario_domingo,
           @idEstatus,
           @descripcion,
           @fechaAsignacion,
           @cantidad,@empresa_id
    FROM @lista x
    WHERE @empleados IS NOT NULL OR @respetarNocturnos = 0 OR x.nocturno = 0;

    SELECT CAST(@@ROWCOUNT AS INT) AS asignados,
           (SELECT COUNT(*) FROM @lista WHERE nocturno = 1 AND @respetarNocturnos = 1 AND @empleados IS NULL) AS respetados;
END;
GO
