-- =============================================================================
-- Incidencias x Empresa: a quién se aplica (2026-10-08)
--   - incidencias.incidenciaEmpresa_id: la copia de cada empleado apunta al renglón de la
--     empresa (empleado_id NULL). Antes se ligaban por empresa + concepto + fechas.
--   - El trigger ya no copia a TODOS los empleados que alguna vez estuvieron en la planta
--     (la de "tran" del 02/10 hizo 11,057 copias, 10,322 de bajas): solo a los activos
--     (empleados.ESTATUS = 'A'), y nada si la app ya eligió a quiénes
--     (SESSION_CONTEXT 'incEmpresaSinCopias' = 1; las copias las hace IncidenciasEmpresa.php).
-- =============================================================================
IF COL_LENGTH('dbo.incidencias', 'incidenciaEmpresa_id') IS NULL
    ALTER TABLE dbo.incidencias ADD incidenciaEmpresa_id INT NULL;
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_incidencias_incidenciaEmpresa' AND object_id = OBJECT_ID('dbo.incidencias'))
    CREATE INDEX IX_incidencias_incidenciaEmpresa ON dbo.incidencias (incidenciaEmpresa_id) INCLUDE (empleado_id, estatus) WHERE incidenciaEmpresa_id IS NOT NULL;
GO
-- Ligar las copias que ya existen con su incidencia de empresa
UPDATE c SET c.incidenciaEmpresa_id = m.id
FROM dbo.incidencias AS c
JOIN dbo.incidencias AS m
  ON m.empleado_id IS NULL AND m.empresa_id = c.empresa_id AND m.concepto_id = c.concepto_id
 AND m.fechaInicio = c.fechaInicio AND m.fechaFin = c.fechaFin AND m.estatus = c.estatus
WHERE c.empleado_id IS NOT NULL AND c.incidenciaEmpresa_id IS NULL;
GO
ALTER TRIGGER dbo.trg_InsertEmpresa_Incidencias
ON dbo.incidencias
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    -- La pantalla Incidencias x Empresa hace sus propias copias (toda la planta o los elegidos)
    IF CAST(SESSION_CONTEXT(N'incEmpresaSinCopias') AS INT) = 1 RETURN;

    INSERT INTO dbo.incidencias (empleado_id, concepto_id, fechaInicio, fechaFin, estatus, cantidadDias, empresa_id,
                                 cubreEntrada, cubreEntradaComedor, cubreSalidaComedor, cubreSalida, incidenciaEmpresa_id)
    SELECT e.empleado_id, i.concepto_id, i.fechaInicio, i.fechaFin, i.estatus, i.cantidadDias, i.empresa_id,
           i.cubreEntrada, i.cubreEntradaComedor, i.cubreSalidaComedor, i.cubreSalida, i.id
    FROM inserted AS i
    CROSS APPLY (SELECT DISTINCT TRY_CAST(em.empleado_id AS INT) AS empleado_id
                 FROM dbo.empleados AS em
                 WHERE em.empresa_id = i.empresa_id AND em.ESTATUS = 'A'
                   AND TRY_CAST(em.empleado_id AS INT) IS NOT NULL) AS e
    WHERE i.empleado_id IS NULL AND i.empresa_id > 0;
END;
GO
-- la bitácora lista las columnas: regenerarla para que incluya incidenciaEmpresa_id
EXEC dbo.BitacoraCrearTrigger 'incidencias';
GO
