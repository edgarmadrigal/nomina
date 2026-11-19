-- Presencia de usuarios en el chat.
-- visto  = último latido (la pantalla sigue abierta / conectado)
-- activo = última vez que la persona movió mouse/teclado (para "ausente" a los 10 min)
IF OBJECT_ID('dbo.ChatPresencia','U') IS NULL
BEGIN
    CREATE TABLE dbo.ChatPresencia (
        idUsuario INT         NOT NULL PRIMARY KEY,
        visto     DATETIME    NOT NULL,
        activo    DATETIME    NULL
    );
END
GO
