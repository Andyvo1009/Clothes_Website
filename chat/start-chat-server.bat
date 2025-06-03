@echo off
:: start-chat-server.bat - Windows batch file to start the WebSocket chat server

cd /d "%~dp0"

:: Check if server is already running
netstat -an | findstr ":8080" > nul
if %errorlevel% equ 0 (
    echo Chat server is already running on port 8080
    exit /b 0
)

:: Start the server in the background
echo Starting WebSocket chat server...
start /B php bin/chat-server.php

:: Wait a moment and check if it started successfully
timeout /t 3 /nobreak > nul

netstat -an | findstr ":8080" > nul
if %errorlevel% equ 0 (
    echo Chat server started successfully on port 8080
    exit /b 0
) else (
    echo Failed to start chat server
    exit /b 1
)
