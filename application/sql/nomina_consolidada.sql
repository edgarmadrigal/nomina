-- Menú Reportes > Nómina consolidada Microsip (controllers/nominaConsolidada.php).
-- Sustituye al programa "Reportes Consolidados de Nóminas" (TECNOMART\REPORTES NOMINA\NOMINAS.exe).
-- Lo ven los administradores; a otros se les da en Usuarios > Permisos.
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'nominaConsolidada')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Reportes', N'Nómina consolidada Microsip', 'nominaConsolidada', '<i class="fa fa-building-o"></i>', 511, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'nominaConsolidada' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO

-- Clasificación de departamentos de Microsip en Directo / Indirecto / Administrativo (reporte D, I y A).
-- El programa viejo (NOMINAS.exe) la guardaba en su propia base; aquí se asigna desde el reporte
-- (vista "Costo total"), por empresa y nombre de departamento de Microsip.
IF OBJECT_ID('dbo.NominaDeptoClasificacion') IS NULL
BEGIN
    CREATE TABLE dbo.NominaDeptoClasificacion (
        empresa_id     INT           NOT NULL,
        depto          NVARCHAR(100) NOT NULL,
        tipo           CHAR(1)       NOT NULL CONSTRAINT CK_NominaDeptoClasificacion_tipo CHECK (tipo IN ('D','I','A')),
        idUsuario      INT           NULL,
        fecha          DATETIME      NOT NULL CONSTRAINT DF_NominaDeptoClasificacion_fecha DEFAULT GETDATE(),
        CONSTRAINT PK_NominaDeptoClasificacion PRIMARY KEY (empresa_id, depto)
    );
END
GO
