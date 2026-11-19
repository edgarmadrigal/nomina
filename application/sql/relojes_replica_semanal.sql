-- Réplica semanal de relojes (controllerRelojes/replicaSemanal; tareas programadas en EDGAR:
-- "Nominas - Replica relojes" domingo 22:00 y "Nominas - Replica relojes (revision lunes)" lunes 19:00).
-- Valores por deviceId de dbo.RelojDispositivo.
--   principal: 542189366 = 206 fundacion 000005
--   excluir:   542389460 = 152 DIRECCION, 542184408 = 170 Desarrollo
IF NOT EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = 'relojes_replica_principal')
    INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES ('relojes_replica_principal', '542189366', GETDATE(), 'automatico');
IF NOT EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = 'relojes_replica_excluir')
    INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES ('relojes_replica_excluir', '542389460,542184408', GETDATE(), 'automatico');
GO
