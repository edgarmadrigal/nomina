@echo off
rem Lo corre la tarea programada "Replica Nominas" en la VM (cada hora, sin ventana).
cd /d "%~dp0"
powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0replicar.ps1" %*
exit /b %ERRORLEVEL%
