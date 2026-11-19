-- =============================================
-- Job de SQL Server Agent "Nominas - Empleados Microsip": cada hora actualiza el catálogo
-- de empleados desde Microsip (dbo.ActualizaEmpleadosMicrosip), lo mismo que el botón
-- "Actualizar Empleados Microsip" de la pantalla de Empleados.
-- Corre a los :05 para que los empleados nuevos ya estén cuando se traen las
-- incidencias a los :15 (job "Nominas - Incidencias Microsip").
-- Ejecutar con sqlcmd -x o desde SSMS.
-- =============================================
USE [msdb];
GO
IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = N'Nominas - Empleados Microsip')
    EXEC msdb.dbo.sp_delete_job @job_name = N'Nominas - Empleados Microsip';
GO
EXEC msdb.dbo.sp_add_job
    @job_name = N'Nominas - Empleados Microsip',
    @enabled = 1,
    @description = N'Agrega los empleados nuevos de Microsip y actualiza estatus, puesto, departamento y salario. También se corre con el botón "Actualizar Empleados Microsip" de la pantalla de Empleados.',
    @owner_login_name = N'sa';
EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'Nominas - Empleados Microsip',
    @step_name = N'Actualizar empleados',
    @subsystem = N'TSQL',
    @database_name = N'Nominas',
    -- como sa: con la cuenta de Windows del Agente los ODBC de Microsip (MSDASQL) no abren
    @command = N'EXECUTE AS LOGIN = ''sa'';
EXEC dbo.ActualizaEmpleadosMicrosip;
REVERT;';
EXEC msdb.dbo.sp_add_jobschedule
    @job_name = N'Nominas - Empleados Microsip',
    @name = N'Cada hora',
    @freq_type = 4,              -- diario
    @freq_interval = 1,
    @freq_subday_type = 8,       -- horas
    @freq_subday_interval = 1,
    @active_start_time = 500;    -- a los :05 de cada hora
EXEC msdb.dbo.sp_add_jobserver @job_name = N'Nominas - Empleados Microsip', @server_name = N'(local)';
GO
