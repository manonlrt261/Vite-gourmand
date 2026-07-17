@echo off
cd /d "%~dp0"

set "PHP_EXE=C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe"

start "" "http://127.0.0.1:8005"
"%PHP_EXE%" -S 127.0.0.1:8005 -t public public/router.php

pause
