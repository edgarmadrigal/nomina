-- Stored Procedure to insert schedule rows for all active employees of a company
IF OBJECT_ID('dbo.AgregarHorariosEmpresaSP', 'P') IS NOT NULL
    DROP PROCEDURE dbo.AgregarHorariosEmpresaSP;
GO

CREATE PROCEDURE dbo.AgregarHorariosEmpresaSP
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
    @cantidad INT = NULL
AS
BEGIN
    SET NOCOUNT ON;

    INSERT INTO horarios_empleado
        (empleado_id, idhorario_lunes, idhorario_martes, idhorario_miercoles, idhorario_jueves, idhorario_viernes, idhorario_sabado, idhorario_domingo, idEstatus, descripcion, fechaAsignacion, cantidad)
    SELECT e.empleado_id,
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
           @cantidad
    FROM empleados e
    WHERE e.empresa_id = @empresa_id
      AND e.estatus = 'A';
END;
GO
