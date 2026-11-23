USE [Nominas];
GO
-- =============================================
-- 2026-11-23  El mismo número de empleado puede existir en dos empresas
-- (ej. 110811 = JUAN MANUEL FRAYRE en CUENCAME y MARIA GUADALUPE MARTINEZ en
-- PROCESOS_SECOS). Antes el horario se buscaba solo por número, así que las dos
-- empresas veían y editaban el MISMO renglón de horarios_empleado.
-- Ahora cada empresa ve las asignaciones con su empresa_id, las que no tienen
-- empresa, y las de una empresa donde ese número ya no está activo (cambio de planta).
-- =============================================

CREATE OR ALTER PROCEDURE [dbo].[ConsultaHorariosEmpleado]
    @empleado_id INT = NULL,
    @dia         INT = NULL,
    @empresa_id  INT = NULL      -- AJUSTE 2026-11-23
AS
BEGIN
    SELECT
        he.id,
        he.empleado_id,
        h.nombre AS horario,
        he.descripcion,
        CASE WHEN idh.idhorario = 1 THEN '' ELSE CONVERT(VARCHAR(8), h.entrada_Inicio, 108) END AS entrada,
        CASE
            WHEN idh.idhorario = 1 THEN ''
            else
             CONVERT(VARCHAR(8), h.salida_Inicio, 108)
        END AS salida,
        DATEADD(day, @dia - 1, he.fechaAsignacion) AS fechaAsignacion,
        @dia AS dia,
        he.cantidad
    FROM [Nominas].[dbo].[horarios_empleado] he
    CROSS APPLY (
        SELECT idhorario = CASE @dia
            WHEN 1 THEN he.idhorario_lunes
            WHEN 2 THEN he.idhorario_martes
            WHEN 3 THEN he.idhorario_miercoles
            WHEN 4 THEN he.idhorario_jueves
            WHEN 5 THEN he.idhorario_viernes
            WHEN 6 THEN he.idhorario_sabado
            WHEN 7 THEN he.idhorario_domingo
        END
    ) idh
    LEFT JOIN [Nominas].[dbo].horarios h ON h.id = idh.idhorario
    WHERE he.empleado_id = @empleado_id AND he.idEstatus = 1
      AND ( @empresa_id IS NULL
         OR he.empresa_id = @empresa_id
         OR he.empresa_id IS NULL
         OR NOT EXISTS (SELECT 1 FROM dbo.empleados e
                        WHERE e.empleado_id = he.empleado_id AND e.empresa_id = he.empresa_id AND e.estatus = 'A') )
    ORDER BY he.id
END
GO

-- Igual que dbo.EmpleadoTieneNocturno pero con la asignación de ESA empresa
-- (primero la de la empresa, luego sin empresa, al final cualquier otra).
CREATE OR ALTER FUNCTION dbo.EmpleadoTieneNocturnoEmpresa (@empleado_id VARCHAR(20), @empresa_id INT)
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
          ORDER BY CASE WHEN he.empresa_id = @empresa_id THEN 0 WHEN he.empresa_id IS NULL THEN 1 ELSE 2 END,
                   he.fechaAsignacion DESC, he.id DESC) v;
    RETURN @r;
END;
GO
