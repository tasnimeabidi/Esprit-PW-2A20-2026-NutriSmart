@echo off
echo ========================================
echo   Nettoyage des processus XAMPP
echo ========================================
echo.

echo Arret de tous les processus Apache...
taskkill /F /IM httpd.exe 2>nul
if %errorlevel% == 0 (echo Apache arrete avec succes) else (echo Aucun processus Apache trouve)

echo.
echo Arret de tous les processus MySQL...
taskkill /F /IM mysqld.exe 2>nul
if %errorlevel% == 0 (echo MySQL arrete avec succes) else (echo Aucun processus MySQL trouve)

echo.
echo Arret du service VMware (qui bloque le port 443)...
net stop VMwareHostd 2>nul
if %errorlevel% == 0 (echo VMware arrete avec succes) else (echo Service VMware deja arrete)

echo.
echo ========================================
echo   Nettoyage termine!
echo ========================================
echo.
echo Maintenant, lancez XAMPP Control Panel en tant qu'administrateur
echo (clic droit sur xampp-control.exe puis "Executer en tant qu'administrateur")
echo.
pause
