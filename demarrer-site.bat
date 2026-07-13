@echo off
setlocal

cd /d "%~dp0"

set "PHP_EXE=C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

echo.
echo ==========================================
echo   Vite et Gourmand - serveur local
echo ==========================================
echo.

if not exist "public\router.php" (
    echo ERREUR : public\router.php est introuvable.
    echo Verifie que ce fichier est bien dans le dossier du projet.
    pause
    exit /b 1
)

if not exist "templates\partials\_header.html.twig" (
    echo ERREUR : templates\partials\_header.html.twig est introuvable.
    pause
    exit /b 1
)

echo Nettoyage du cache Symfony...
if exist "var\cache\dev" rmdir /s /q "var\cache\dev"

echo Liberation du port 8005...
for /f "tokens=5" %%P in ('netstat -ano ^| findstr ":8005"') do (
    taskkill /PID %%P /F >nul 2>nul
)

echo.
echo Site lance sur : http://127.0.0.1:8005
echo Garde cette fenetre ouverte.
echo Pour arreter le site : CTRL + C puis O.
echo.

start "" "http://127.0.0.1:8005"
"%PHP_EXE%" -S 127.0.0.1:8005 -t public public/router.php

echo.
echo Le serveur s'est arrete.
pause
