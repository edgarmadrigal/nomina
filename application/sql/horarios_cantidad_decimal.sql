-- =============================================
-- 2026-10-08: "Cantidad de días por semana" de la asignación de horarios admite
-- decimales (ej. 5.5, 6.4). horarios_empleado.cantidad pasa de INT a DECIMAL(5,2).
-- El cambio no pierde datos (6 -> 6.00). Los dos índices que la incluyen se
-- vuelven a crear igual, igual que el valor por omisión (5). Además: AgregarHorariosEmpresaSP (@cantidad DECIMAL) y el
-- TXT (ConsultaAsistenciaExportarTablaTemporal: días a pagar con decimales).
-- =============================================
USE [Nominas];
GO
SET XACT_ABORT ON;
BEGIN TRAN;

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.horarios_empleado') AND name = 'cantidad' AND system_type_id = TYPE_ID('int'))
BEGIN
    DROP INDEX IX_horarios_empleado_lookup ON dbo.horarios_empleado;
    DROP INDEX IX_horarios_empleado_Empleado ON dbo.horarios_empleado;
    ALTER TABLE dbo.horarios_empleado DROP CONSTRAINT DF_horarios_empleado_cantidad;

    ALTER TABLE dbo.horarios_empleado ALTER COLUMN cantidad DECIMAL(5,2) NULL;
    ALTER TABLE dbo.horarios_empleado ADD CONSTRAINT DF_horarios_empleado_cantidad DEFAULT ((5)) FOR cantidad;

    CREATE NONCLUSTERED INDEX IX_horarios_empleado_lookup ON dbo.horarios_empleado (empleado_id, idEstatus, fechaAsignacion)
        INCLUDE (idhorario_lunes, idhorario_martes, idhorario_miercoles, idhorario_jueves, idhorario_viernes, idhorario_sabado, idhorario_domingo, cantidad, rotativo);
    CREATE NONCLUSTERED INDEX IX_horarios_empleado_Empleado ON dbo.horarios_empleado (empleado_id) INCLUDE (cantidad);
END

COMMIT;
GO
