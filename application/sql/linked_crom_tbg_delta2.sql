-- Linked servers CROM de ADMINISTRATIVO_TBG (TBGSEM CROM) y DELTA2 (DELTA 2 CROM), igual que
-- ADMINISTRATIVO_MMS_CROM: MSDASQL sobre el DSN de sistema (Firebird) del servidor. El nombre
-- es <empresas.nombre>_CROM porque así lo busca dbo.ConsultaReporte137 para la columna CROM.
-- Los DSN ya existen en el servidor y guardan el usuario/contraseña de SYSDBA.
IF NOT EXISTS (SELECT 1 FROM sys.servers WHERE name = N'ADMINISTRATIVO_TBG_CROM')
    EXEC master.dbo.sp_addlinkedserver @server = N'ADMINISTRATIVO_TBG_CROM', @srvproduct = N'TBGSEM CROM',
         @provider = N'MSDASQL', @datasrc = N'TBGSEM CROM';
IF NOT EXISTS (SELECT 1 FROM sys.servers WHERE name = N'DELTA2_CROM')
    EXEC master.dbo.sp_addlinkedserver @server = N'DELTA2_CROM', @srvproduct = N'DELTA 2 CROM',
         @provider = N'MSDASQL', @datasrc = N'DELTA 2 CROM';
GO
EXEC master.dbo.sp_serveroption N'ADMINISTRATIVO_TBG_CROM', N'collation compatible', N'true';
EXEC master.dbo.sp_serveroption N'ADMINISTRATIVO_TBG_CROM', N'data access', N'true';
EXEC master.dbo.sp_serveroption N'ADMINISTRATIVO_TBG_CROM', N'rpc out', N'true';
EXEC master.dbo.sp_serveroption N'ADMINISTRATIVO_TBG_CROM', N'use remote collation', N'true';
EXEC master.dbo.sp_serveroption N'DELTA2_CROM', N'collation compatible', N'true';
EXEC master.dbo.sp_serveroption N'DELTA2_CROM', N'data access', N'true';
EXEC master.dbo.sp_serveroption N'DELTA2_CROM', N'rpc out', N'true';
EXEC master.dbo.sp_serveroption N'DELTA2_CROM', N'use remote collation', N'true';
GO
