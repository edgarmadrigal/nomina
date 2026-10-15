@echo off
chcp 65001 >nul
echo ============================================================
echo  Ajuste Torreon: NO descontar vacaciones del TXT
echo  (reaplica el procedimiento ConsultaAsistenciaExportarTablaTemporal)
echo ============================================================
echo.
echo Se te pedira la contrasena de 'sa' (no se guarda en ningun lado).
echo.
sqlcmd -S AIN-MSSRV2\SISTEMASAIN -U sa -d Nominas -b -i "C:\xampp\htdocs\nomina\application\sql\ConsultaAsistenciaExportarTablaTemporal.sql"
if %errorlevel%==0 (
  echo.
  echo  LISTO: el procedimiento quedo actualizado.
  echo  Ahora regenera el TXT de la semana 41 y 111097 saldra con 6.
) else (
  echo.
  echo  HUBO UN ERROR ^(codigo %errorlevel%^). Revisa el mensaje de arriba.
  echo  Si dice "Login failed", la contrasena de sa fue incorrecta.
)
echo.
pause
