-- Importar/Exportar > Modifica excepciones Microsip (controllers/modificaExcepciones.php).
-- Sustituye al programa "Modifica Excepciones.exe" (SOTI\Programa Nóminas).
-- Lo ven los administradores; a otros se les da en Usuarios > Permisos.
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'modificaExcepciones')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Importar/Exportar', N'Modifica excepciones Microsip', 'modificaExcepciones', '<i class="fa fa-pencil-square-o"></i>', 530, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'modificaExcepciones' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO

-- Bitácora de cada cuota que se cambió o agregó en Microsip (para saber quién, cuándo y el valor anterior).
IF OBJECT_ID('dbo.ModificaExcepcionesLog') IS NULL
BEGIN
    CREATE TABLE dbo.ModificaExcepcionesLog (
        id               INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_ModificaExcepcionesLog PRIMARY KEY,
        empresa_id       INT            NOT NULL,
        nomina_id        INT            NOT NULL,
        fecha_nomina     DATE           NULL,
        tipo_nomina      CHAR(1)        NULL,
        numero           INT            NOT NULL,
        concepto_no_id   INT            NOT NULL,
        concepto         NVARCHAR(100)  NULL,
        accion           CHAR(1)        NOT NULL,  -- U = cambió la cuota, I = agregó el concepto a la excepción
        cuota_anterior   DECIMAL(18,6)  NULL,
        cuota_nueva      DECIMAL(18,6)  NOT NULL,
        archivo          NVARCHAR(200)  NULL,
        idUsuario        INT            NULL,
        fecha            DATETIME       NOT NULL CONSTRAINT DF_ModificaExcepcionesLog_fecha DEFAULT GETDATE()
    );
    CREATE INDEX IX_ModificaExcepcionesLog_nomina ON dbo.ModificaExcepcionesLog (empresa_id, nomina_id);
END
GO
