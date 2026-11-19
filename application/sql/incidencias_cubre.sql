-- =============================================
-- Incidencias: qué checadas cubre (entrada, entrada de comida, salida de comida, salida).
--   * Completa (las 4): igual que siempre. El código del concepto sale en la Entrada, las demás
--     columnas quedan vacías, no hay falta (con goce), ni retardo, ni tiempo extra.
--   * Parcial (solo algunas): el código sale solo en las columnas que cubre; las demás conservan
--     la checada real. Se calcula el tiempo extra; el retardo solo se perdona si cubre la entrada.
--     En el TXT no cuenta como día completo de incidencia (vacaciones/premios).
--   Antes solo "Retardo ruta transporte" (tran) se comportaba así; ahora sus incidencias quedan
--   como "solo entrada" y cualquier incidencia puede ser parcial.
-- Usan estas columnas: ActualizaChecadasBiostarDias, ConsultaAsistenciaExportarTablaTemporal,
-- trg_InsertEmpresa_Incidencias (copia a los empleados de la empresa) y la pantalla de Incidencias.
-- =============================================
USE [Nominas];
GO
IF COL_LENGTH('dbo.incidencias', 'cubreEntrada') IS NULL
BEGIN
    ALTER TABLE dbo.incidencias ADD
        cubreEntrada        BIT NOT NULL CONSTRAINT DF_incidencias_cubreEntrada        DEFAULT 1,
        cubreEntradaComedor BIT NOT NULL CONSTRAINT DF_incidencias_cubreEntradaComedor DEFAULT 1,
        cubreSalidaComedor  BIT NOT NULL CONSTRAINT DF_incidencias_cubreSalidaComedor  DEFAULT 1,
        cubreSalida         BIT NOT NULL CONSTRAINT DF_incidencias_cubreSalida         DEFAULT 1;
END
GO
-- Las de "Retardo ruta transporte" ya existentes: solo la entrada (como funcionaban)
UPDATE i SET cubreEntradaComedor = 0, cubreSalidaComedor = 0, cubreSalida = 0
FROM dbo.incidencias i JOIN dbo.conceptos c ON c.id = i.concepto_id
WHERE c.descripcionCorta = 'tran' AND (i.cubreEntradaComedor = 1 OR i.cubreSalidaComedor = 1 OR i.cubreSalida = 1);
GO
-- La bitácora toma las columnas nuevas
EXEC dbo.BitacoraCrearTrigger 'incidencias';
GO

-- Incidencia por empresa: las copias a los empleados llevan también lo que cubre
ALTER TRIGGER trg_InsertEmpresa_Incidencias
ON [Nominas].[dbo].[incidencias]
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
     IF  (
        SELECT empresa_id
        FROM inserted
    )>0
    BEGIN
        INSERT INTO [Nominas].[dbo].[incidencias] ([empleado_id]
                                                ,[concepto_id]
                                                ,[fechaInicio]
                                                ,[fechaFin]
                                                ,[estatus]
                                                ,[cantidadDias]
                                                ,[empresa_id]
                                                ,[cubreEntrada]
                                                ,[cubreEntradaComedor]
                                                ,[cubreSalidaComedor]
                                                ,[cubreSalida])
        SELECT e.empleado_id,i.concepto_id,i.fechaInicio,i.fechaFin,i.estatus,i.cantidadDias, i.empresa_id,
               i.cubreEntrada, i.cubreEntradaComedor, i.cubreSalidaComedor, i.cubreSalida
        FROM [Nominas].[dbo].[empleados] e
        INNER JOIN inserted i ON e.empresa_id = i.empresa_id;
        END
    END TRY
    BEGIN CATCH
        DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
        DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
        DECLARE @ErrorState INT = ERROR_STATE();
        RAISERROR(@ErrorMessage, @ErrorSeverity, @ErrorState);
    END CATCH
END;
GO

ALTER PROCEDURE [dbo].[ConsultaIncidenciaEditar]
@id INT
AS
BEGIN
SELECT
       i.empleado_id
      ,i.concepto_id
      ,i.fechaInicio
      ,i.fechaFin
      ,usuarioBaja_id
      ,i.cubreEntrada, i.cubreEntradaComedor, i.cubreSalidaComedor, i.cubreSalida
  FROM [Nominas].[dbo].[incidencias] as i
  INNER JOIN [Nominas].[dbo].conceptos as c ON (i.concepto_id=c.id)
  WHERE i.id=@id
END
GO
