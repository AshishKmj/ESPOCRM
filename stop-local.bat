@echo off
title Stop EspoCRM
echo Stopping EspoCRM web server and MariaDB...
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im mariadbd.exe >nul 2>&1
echo EspoCRM has been stopped.
timeout /t 2 >nul
