-- Menú Reportes > Reportes Microsip (Catálogo de empleados, Fonacot, Infonavit x empleado).
-- Lo ven los administradores; a otros se les da en Usuarios > Permisos.
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'reportesMicrosip')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden, activo)
    VALUES ('Reportes', N'Reportes Microsip', 'reportesMicrosip', '<i class="fa fa-file-text-o"></i>', 510, 1);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'reportesMicrosip' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO
