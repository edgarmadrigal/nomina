USE [Nominas];
GO
-- =============================================
-- 2026-11-25  IP pública de la empresa en dbo.ConfigSistema (la tabla ya existe, ver chat_ia.sql).
-- Los enlaces de descarga la toman de aquí (helpers/ip_publica_helper.php) y el administrador
-- la cambia con "IP pública" en su menú de usuario (controllers/IpPublica.php).
-- =============================================
IF NOT EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = 'ip_publica')
    INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES ('ip_publica', '177.244.42.94', GETDATE(), 'automatico');
IF NOT EXISTS (SELECT 1 FROM dbo.ConfigSistema WHERE clave = 'ip_publica_nueva')
    INSERT INTO dbo.ConfigSistema (clave, valor, fechaModifico, usuarioModifico) VALUES ('ip_publica_nueva', '201.165.179.106', GETDATE(), 'automatico');
GO
