@echo off
echo Iniciando MySQL...
cd /d E:\laragon\laragonphp84\bin\mysql\mysql-8.4.3-winx64\bin
start mysqld.exe --defaults-file=E:\laragon\laragonphp84\bin\mysql\mysql-8.4.3-winx64\my.ini
echo MySQL iniciado en segundo plano
timeout /t 5
echo.
echo Verificando conexion...
mysql -u root -e "SELECT 'MySQL esta corriendo!' as Status;"
pause
