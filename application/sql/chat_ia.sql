-- =============================================
-- Asistente IA del chat (nóminas y RH), con Groq (gratis, groq.com).
-- La clave de Groq va en dbo.ConfigSistema ('groq_api_key') y el modelo en
-- 'groq_modelo'; la captura el administrador desde la conversación del asistente.
-- Cada usuario tiene su propia conversación con el asistente (no la ven los demás).
-- El asistente NO consulta datos del sistema: solo responde dudas.
-- =============================================
USE [Nominas];
GO
IF OBJECT_ID('dbo.ChatIAMensaje') IS NULL
CREATE TABLE dbo.ChatIAMensaje (
    id        INT IDENTITY(1,1) PRIMARY KEY,
    idUsuario INT           NOT NULL,
    rol       VARCHAR(10)   NOT NULL CHECK (rol IN ('user', 'assistant')),
    texto     NVARCHAR(MAX) NOT NULL,
    fecha     DATETIME      NOT NULL DEFAULT GETDATE(),
    borrado   BIT           NOT NULL DEFAULT 0   -- "Nueva conversación" oculta lo anterior
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_ChatIAMensaje_Usuario')
    CREATE INDEX IX_ChatIAMensaje_Usuario ON dbo.ChatIAMensaje (idUsuario, borrado, id);
GO
