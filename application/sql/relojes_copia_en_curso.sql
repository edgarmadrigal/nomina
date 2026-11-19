-- Latido de la copia de rostros: mientras RelojSync copia rostros entre dos relojes pone
-- copiaHasta = ahora + 10 min (lo renueva cada minuto) y la descarga automática se salta esos relojes,
-- porque cada reloj acepta una sola conexión y cruzarse tiraba la copia.
IF COL_LENGTH('dbo.RelojDispositivo', 'copiaHasta') IS NULL
    ALTER TABLE dbo.RelojDispositivo ADD copiaHasta DATETIME NULL;
GO
