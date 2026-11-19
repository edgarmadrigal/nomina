-- Licencia del sistema (número de serie + códigos de activación por correo).
-- Un solo renglón. La serie sale de instalacionId + nombre del servidor + base de datos,
-- así que si la base se copia a otro servidor la serie cambia y la licencia deja de valer.
IF OBJECT_ID('dbo.Licencia') IS NULL
CREATE TABLE dbo.Licencia (
    id              INT NOT NULL CONSTRAINT PK_Licencia PRIMARY KEY CONSTRAINT CK_Licencia_Uno CHECK (id = 1),
    instalacionId   UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_Licencia_Inst DEFAULT NEWID(),
    serieActivada   VARCHAR(20) NULL,      -- serie con la que se activó (si cambia, no vale)
    activa          BIT NOT NULL CONSTRAINT DF_Licencia_Activa DEFAULT 0,
    indefinida      BIT NOT NULL CONSTRAINT DF_Licencia_Indef DEFAULT 0,
    venceEl         DATETIME NULL,
    activadaEl      DATETIME NULL,
    solicitudId     VARCHAR(16) NULL,      -- cambia en cada correo; los códigos sirven una vez
    solicitudFecha  DATETIME NULL,
    solicitudPor    VARCHAR(100) NULL
);
GO
IF NOT EXISTS (SELECT 1 FROM dbo.Licencia) INSERT INTO dbo.Licencia (id) VALUES (1);
GO
IF OBJECT_ID('dbo.LicenciaMovimiento') IS NULL
CREATE TABLE dbo.LicenciaMovimiento (
    id       INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_LicenciaMovimiento PRIMARY KEY,
    fecha    DATETIME NOT NULL CONSTRAINT DF_LicMov_Fecha DEFAULT GETDATE(),
    usuario  VARCHAR(100) NULL,
    accion   VARCHAR(30) NOT NULL,         -- SOLICITUD / ACTIVACION / DESACTIVACION / CODIGO_INVALIDO
    detalle  VARCHAR(400) NULL
);
GO
