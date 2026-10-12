@echo off
title EspoCRM Local Server
echo ========================================================
echo       Starting EspoCRM Local Server & MariaDB
echo ========================================================

:: 1. Start MariaDB in background if not already running
tasklist /fi "imagename eq mariadbd.exe" | find /i "mariadbd.exe" >nul
if %errorlevel% neq 0 (
    echo Starting MariaDB...
    start /b "" "d:\mariadb\bin\mariadbd.exe" --datadir="d:\mariadb\data"
    timeout /t 2 /nobreak >nul
) else (
    echo MariaDB is already running.
)

:: 2. Set PHP Path
set PHP_BIN=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe

:: 3. Open browser after 2 seconds
start "" http://localhost:8080

echo.
echo EspoCRM is running at: http://localhost:8080
echo Login Username: admin
echo Login Password: admin
echo.
echo Press Ctrl+C to stop the web server.
echo ========================================================

:: 4. Start PHP Built-in Server with local router
"%PHP_BIN%" -S localhost:8080 local_router.php
pause
