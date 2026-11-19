@echo off
rem Descarga de checadas de todos los relojes (tarea programada "RelojSync" de la VM de produccion).
rem Corre como SYSTEM, sin ventana. Lo que imprime RelojSync queda en logs\sync.log
rem (al pasar de 5 MB se renombra a sync.old.log y empieza uno nuevo).
setlocal
set DIR=%~dp0
if not exist "%DIR%logs" mkdir "%DIR%logs"
set LOG=%DIR%logs\sync.log
if exist "%LOG%" for %%A in ("%LOG%") do if %%~zA GTR 5000000 move /y "%LOG%" "%DIR%logs\sync.old.log" >nul
echo ===== %date% %time% >> "%LOG%"
"%DIR%publish\RelojSync.exe" sync >> "%LOG%" 2>&1
echo ===== fin, codigo %errorlevel% >> "%LOG%"
endlocal
