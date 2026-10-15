-- =============================================================================
-- Comunicación con relojes (2026-10-12): poner a tiempo, leer hora y borrar
-- checadas desde la pantalla de Relojes (controllerRelojes / RelojSync.exe).
-- =============================================================================
USE [Nominas];
GO

-- Desfase que RelojSync suma al id de evento de los relojes BS2 al guardarlo en
-- RelojEvento. Al borrar el registro de un reloj desde el sistema se mueve por
-- encima de todo lo guardado, por si el reloj reinicia su numeración (si no, las
-- checadas nuevas chocarían con la PK (deviceId, eventoId) y se descartarían).
IF COL_LENGTH('dbo.RelojDispositivo', 'eventoIdBase') IS NULL
    ALTER TABLE dbo.RelojDispositivo ADD eventoIdBase BIGINT NOT NULL CONSTRAINT DF_RelojDispositivo_eventoIdBase DEFAULT 0;
GO

-- Bitácora de cada acción enviada a un reloj (quién, cuándo, qué y el resultado).
IF OBJECT_ID('dbo.RelojComunicacion') IS NULL
CREATE TABLE dbo.RelojComunicacion (
    id          INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_RelojComunicacion PRIMARY KEY,
    fecha       DATETIME      NOT NULL CONSTRAINT DF_RelojComunicacion_fecha DEFAULT GETDATE(),
    idReloj     INT           NOT NULL,           -- dbo.RelojDispositivo.id
    accion      VARCHAR(20)   NOT NULL,           -- descargar | hora | ponerhora | borrar
    usuario     VARCHAR(50)   NULL,               -- usuario de la sesión
    autorizo    VARCHAR(50)   NULL,               -- administrador que dio su clave (borrar)
    ip          VARCHAR(45)   NULL,
    exito       BIT           NOT NULL,
    resultado   VARCHAR(MAX)  NULL
);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_RelojComunicacion_reloj')
    CREATE INDEX IX_RelojComunicacion_reloj ON dbo.RelojComunicacion (idReloj, fecha DESC);
GO
