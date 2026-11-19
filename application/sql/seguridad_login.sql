-- =============================================
-- Seguridad del login
--   * usuarios.password crece a 255 para guardar la contraseña cifrada (bcrypt de PHP, 60 caracteres).
--   * dbo.LoginIntento: bitácora de cada intento (bueno o malo) con IP; con ella se bloquea
--     por usuario y por IP después de varios intentos fallidos y se decide cuándo pedir captcha.
-- =============================================
USE [Nominas];
GO

IF COL_LENGTH('dbo.usuarios', 'password') < 255
    ALTER TABLE dbo.usuarios ALTER COLUMN password VARCHAR(255) NULL;
GO

IF OBJECT_ID('dbo.LoginIntento') IS NULL
CREATE TABLE dbo.LoginIntento (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    usuario     VARCHAR(50)   NULL,
    ip          VARCHAR(45)   NOT NULL,
    exito       BIT           NOT NULL,
    motivo      VARCHAR(30)   NULL,          -- OK / CONTRASENA / CAPTCHA / ROBOT / BLOQUEO_IP / BLOQUEO_USUARIO
    externo     BIT           NOT NULL DEFAULT 0,   -- 1 = vino de internet (no de la red interna)
    navegador   VARCHAR(300)  NULL,
    fecha       DATETIME      NOT NULL DEFAULT GETDATE()
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_LoginIntento_ip_fecha')
    CREATE INDEX IX_LoginIntento_ip_fecha ON dbo.LoginIntento (ip, fecha) INCLUDE (exito);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_LoginIntento_usuario_fecha')
    CREATE INDEX IX_LoginIntento_usuario_fecha ON dbo.LoginIntento (usuario, fecha) INCLUDE (exito);
GO
