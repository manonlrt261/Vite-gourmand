@echo off
setlocal

cd /d "%~dp0"

set "PHP_EXE=C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

if not exist "public\router.php" (
    echo ERREUR : public\router.php est introuvable.
    echo Ce fichier doit etre lance depuis le dossier vite_gourmandtest.
    pause
    exit /b 1
)

if not exist "templates\partials\_header.html.twig" (
    echo ERREUR : templates\partials\_header.html.twig est introuvable.
    pause
    exit /b 1
)

echo Liberation du port 8005...
for /f "tokens=5" %%P in ('netstat -ano ^| findstr ":8005"') do (
    taskkill /PID %%P /F >nul 2>nul
)

echo Ouverture du serveur dans une nouvelle fenetre...
start "Serveur Vite et Gourmand" cmd /k ""%PHP_EXE%" -S 127.0.0.1:8005 -t public public/router.php"

timeout /t 2 /nobreak >nul
start "" "http://127.0.0.1:8005"
