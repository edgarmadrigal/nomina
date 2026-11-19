-- =============================================
-- Job de SQL Server Agent "Nominas - Incidencias Microsip": cada hora trae de Microsip
-- las vacaciones e incapacidades (dbo.SincronizaIncidenciasMicrosip, ver microsip_incidencias.sql).
-- Ejecutar con sqlcmd -x o desde SSMS.
-- =============================================
USE [msdb];
GO
IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = N'Nominas - Incidencias Microsip')
    EXEC msdb.dbo.sp_delete_job @job_name = N'Nominas - Incidencias Microsip';
GO
EXEC msdb.dbo.sp_add_job
    @job_name = N'Nominas - Incidencias Microsip',
    @enabled = 1,
    @description = N'Trae de Microsip las vacaciones e incapacidades a Nominas.dbo.incidencias. También se corre con el botón "Traer de Microsip" de la pantalla de Incidencias.',
    @owner_login_name = N'sa';
EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'Nominas - Incidencias Microsip',
    @step_name = N'Sincronizar',
    @subsystem = N'TSQL',
    @database_name = N'Nominas',
    -- como sa: con la cuenta de Windows del Agente los ODBC de Microsip (MSDASQL) no abren
    @command = N'EXECUTE AS LOGIN = ''sa'';
EXEC dbo.SincronizaIncidenciasMicrosip @origen = ''Programado'';
REVERT;';
EXEC msdb.dbo.sp_add_jobschedule
    @job_name = N'Nominas - Incidencias Microsip',
    @name = N'Cada hora',
    @freq_type = 4,              -- diario
    @freq_interval = 1,
    @freq_subday_type = 8,       -- horas
    @freq_subday_interval = 1,
    @active_start_time = 1500;   -- a los :15 de cada hora
EXEC msdb.dbo.sp_add_jobserver @job_name = N'Nominas - Incidencias Microsip', @server_name = N'(local)';
GO
