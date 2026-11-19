-- =============================================================================
-- Tutorial guiado (2026-10-08): quién ya lo terminó (o lo saltó) y de qué versión.
-- Al subir la versión en application/controllers/Tutorial.php se vuelve a mostrar a todos.
-- =============================================================================
IF OBJECT_ID('dbo.UsuarioTutorial') IS NULL
CREATE TABLE dbo.UsuarioTutorial (
    idUsuario  INT          NOT NULL,
    tutorial   VARCHAR(30)  NOT NULL,
    version    VARCHAR(20)  NOT NULL,
    resultado  VARCHAR(20)  NOT NULL,     -- terminado / omitido
    fecha      DATETIME     NOT NULL CONSTRAINT DF_UsuarioTutorial_fecha DEFAULT GETDATE(),
    CONSTRAINT PK_UsuarioTutorial PRIMARY KEY (idUsuario, tutorial)
);
GO
