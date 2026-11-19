-- =============================================
-- Radio (símbolo de música junto al nombre del usuario).
-- La contraseña de la música se guarda cifrada (password_hash de PHP) en
-- dbo.ConfigSistema con clave 'musica_clave'; la define el administrador desde la página.
-- =============================================
USE [Nominas];
GO
IF OBJECT_ID('dbo.ConfigSistema') IS NULL
CREATE TABLE dbo.ConfigSistema (
    clave           VARCHAR(50)    NOT NULL PRIMARY KEY,
    valor           NVARCHAR(400)  NULL,
    fechaModifico   DATETIME       NOT NULL DEFAULT GETDATE(),
    usuarioModifico VARCHAR(60)    NULL
);
GO

-- Opción del menú (Catálogos > Música) para los administradores: cambiar la contraseña de la música
IF NOT EXISTS (SELECT 1 FROM dbo.MenuOpcion WHERE url = 'musica')
    INSERT INTO dbo.MenuOpcion (seccion, nombre, url, icono, orden) VALUES ('Catalogos', N'Música', 'musica', '<i class="fa fa-music"></i>', 296);
INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
SELECT u.id, o.id FROM dbo.usuarios u CROSS JOIN dbo.MenuOpcion o
WHERE o.url = 'musica' AND u.idPerfil = 1
  AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idUsuario = u.id AND p.idOpcion = o.id);
GO

-- =============================================
-- YouTube en el panel de música (mini reproductor visible, solo búsqueda).
-- La clave de la API de YouTube (YouTube Data API v3) se guarda en dbo.ConfigSistema
-- con clave 'youtube_api_key' (Catálogos > Música). Cada búsqueda gasta 100 de las
-- 10,000 unidades diarias gratis, así que los resultados se guardan 7 días aquí.
-- =============================================
IF OBJECT_ID('dbo.MusicaYoutubeCache') IS NULL
CREATE TABLE dbo.MusicaYoutubeCache (
    consulta  NVARCHAR(200) NOT NULL PRIMARY KEY,
    resultado NVARCHAR(MAX) NOT NULL,
    fecha     DATETIME      NOT NULL DEFAULT GETDATE()
);
GO
