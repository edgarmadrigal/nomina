-- Deja la licencia ACTIVA e INDEFINIDA sin pasar por el correo.
-- La serie se calcula igual que en PHP: SHA256( instalacionId | SERVIDOR | BASE ), en mayúsculas.
-- Correrlo DESPUÉS de licencia.sql. Para volver a tiempo definido después, se usa la pantalla
-- Soporte > Licencia (solicitar código → activar 30/90/180/365 días) o desactivar.
SET NOCOUNT ON;

DECLARE @serie VARCHAR(20);
SELECT @serie =
    'NOM-' + SUBSTRING(h, 1, 4) + '-' + SUBSTRING(h, 5, 4)
FROM (
    SELECT CONVERT(VARCHAR(64),
             HASHBYTES('SHA2_256',
                 CONVERT(VARCHAR(36), l.instalacionId) + '|'
               + UPPER(CONVERT(VARCHAR(128), SERVERPROPERTY('MachineName'))) + '|'
               + UPPER(DB_NAME())), 2) AS h
    FROM dbo.Licencia l WHERE l.id = 1
) x;

UPDATE dbo.Licencia
   SET activa = 1, indefinida = 1, venceEl = NULL, activadaEl = GETDATE(),
       serieActivada = @serie, solicitudId = NULL
 WHERE id = 1;

INSERT INTO dbo.LicenciaMovimiento (usuario, accion, detalle)
VALUES (SUSER_SNAME(), 'ACTIVACION', 'Indefinida directa (SQL). Serie ' + @serie);

SELECT @serie AS SerieActivada, 'ACTIVA INDEFINIDA' AS Estado;
GO
