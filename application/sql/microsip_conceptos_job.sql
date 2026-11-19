-- =============================================
-- Job de SQL Server Agent "Nominas - Conceptos Microsip": cada hora copia el catálogo de
-- conceptos de nómina de cada empresa de Microsip (dbo.SincronizaConceptosMicrosip), lo mismo
-- que el botón "Actualizar ahora" de Catálogos > Conceptos Microsip. Corre a los :10.
-- Ejecutar con sqlcmd -x o desde SSMS.
-- =============================================
USE [msdb];
GO
IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = N'Nominas - Conceptos Microsip')
    EXEC msdb.dbo.sp_delete_job @job_name = N'Nominas - Conceptos Microsip';
GO
EXEC msdb.dbo.sp_add_job
    @job_name = N'Nominas - Conceptos Microsip',
    @enabled = 1,
    @description = N'Copia los conceptos de nómina (CONCEPTOS_NO) de cada empresa de Microsip a dbo.MicrosipConcepto. También se corre con el botón "Actualizar ahora" de Catálogos > Conceptos Microsip.',
    @owner_login_name = N'sa';
EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'Nominas - Conceptos Microsip',
    @step_name = N'Actualizar conceptos',
    @subsystem = N'TSQL',
    @database_name = N'Nominas',
    -- como sa: con la cuenta de Windows del Agente los ODBC de Microsip (MSDASQL) no abren
    @command = N'EXECUTE AS LOGIN = ''sa'';
EXEC dbo.SincronizaConceptosMicrosip @origen = ''job'';
REVERT;';
EXEC msdb.dbo.sp_add_jobschedule
    @job_name = N'Nominas - Conceptos Microsip',
    @name = N'Cada hora',
    @freq_type = 4,              -- diario
    @freq_interval = 1,
    @freq_subday_type = 8,       -- horas
    @freq_subday_interval = 1,
    @active_start_time = 1000;   -- a los :10 de cada hora
EXEC msdb.dbo.sp_add_jobserver @job_name = N'Nominas - Conceptos Microsip', @server_name = N'(local)';
GO
