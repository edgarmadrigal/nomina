-- =============================================
-- Chat entre usuarios de Nominas (controllers/Chat.php, assets/js/chat.js)
--   ChatConversacion : GENERAL (todos), DIRECTO (dos usuarios), GRUPO, SOPORTE (fallas del sistema)
--   ChatParticipante : quién está en cada conversación y hasta qué mensaje leyó
--   ChatMensaje      : mensajes (idUsuario NULL = aviso del sistema, ej. cambio de estado)
--   ChatArchivo      : imágenes adjuntas (capturas de pantalla), en base64
-- La conversación GENERAL incluye a todos los usuarios (se agregan solos al entrar).
-- En SOPORTE participan quien reporta y los administradores (idPerfil = 1).
-- =============================================
USE [Nominas];
GO
IF OBJECT_ID('dbo.ChatConversacion') IS NULL
CREATE TABLE dbo.ChatConversacion (
    id             INT IDENTITY(1,1) PRIMARY KEY,
    tipo           VARCHAR(10)    NOT NULL,              -- GENERAL / DIRECTO / GRUPO / SOPORTE
    titulo         NVARCHAR(150)  NULL,
    estado         VARCHAR(15)    NULL,                  -- SOPORTE: ABIERTO / EN PROCESO / RESUELTO
    pagina         NVARCHAR(300)  NULL,                  -- SOPORTE: pantalla donde pasó la falla
    creadoPor      INT            NULL,
    fecha          DATETIME       NOT NULL DEFAULT GETDATE(),
    ultimoMensaje  DATETIME       NOT NULL DEFAULT GETDATE()
);
GO
IF OBJECT_ID('dbo.ChatParticipante') IS NULL
CREATE TABLE dbo.ChatParticipante (
    idConversacion INT NOT NULL,
    idUsuario      INT NOT NULL,
    ultimoLeido    INT NOT NULL DEFAULT 0,               -- último ChatMensaje.id leído
    CONSTRAINT PK_ChatParticipante PRIMARY KEY (idConversacion, idUsuario)
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_ChatParticipante_usuario')
CREATE INDEX IX_ChatParticipante_usuario ON dbo.ChatParticipante (idUsuario) INCLUDE (ultimoLeido);
GO
IF OBJECT_ID('dbo.ChatArchivo') IS NULL
CREATE TABLE dbo.ChatArchivo (
    id      INT IDENTITY(1,1) PRIMARY KEY,
    nombre  NVARCHAR(200) NOT NULL,
    mime    VARCHAR(50)   NOT NULL,
    tamano  INT           NOT NULL,
    datos   VARCHAR(MAX)  NOT NULL                        -- base64
);
GO
IF OBJECT_ID('dbo.ChatMensaje') IS NULL
CREATE TABLE dbo.ChatMensaje (
    id             INT IDENTITY(1,1) PRIMARY KEY,
    idConversacion INT            NOT NULL,
    idUsuario      INT            NULL,                  -- NULL = aviso del sistema
    texto          NVARCHAR(4000) NULL,
    idArchivo      INT            NULL,
    fecha          DATETIME       NOT NULL DEFAULT GETDATE()
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_ChatMensaje_conversacion')
CREATE INDEX IX_ChatMensaje_conversacion ON dbo.ChatMensaje (idConversacion, id) INCLUDE (idUsuario);
GO
IF NOT EXISTS (SELECT 1 FROM dbo.ChatConversacion WHERE tipo = 'GENERAL')
INSERT INTO dbo.ChatConversacion (tipo, titulo) VALUES ('GENERAL', N'Nóminas (todos)');
GO
