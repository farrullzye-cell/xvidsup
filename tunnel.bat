@echo off
title XVIDSUP Tunnel - Public Access
chcp 65001 >nul

set PORT=8080

:: Cek server local
netstat -an | find ":%PORT% " >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Server local belum jalan di port %PORT%!
    echo Jalankan start.bat dulu.
    pause
    exit /b 1
)

:: Cek SSH tersedia
where ssh.exe >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] SSH tidak ditemukan di sistem.
    echo Install OpenSSH Client:
    echo   Settings ^> Apps ^> Optional Features ^> OpenSSH Client
    pause
    exit /b 1
)

cls
echo ============================================
echo    XVIDSUP Tunnel - Public Access
echo ============================================
echo.
echo Tunnel via localhost.run (SSH)
echo.
echo Tunggu sebentar...
echo.
echo Kalo muncul "Permanently added..." itu normal.
echo.
echo Pas disuruh login, tekan CTRL+C dan jalanin ulang.
echo.
echo ============================================

:: Tunnel via localhost.run (no account needed)
ssh -o StrictHostKeyChecking=no -o ServerAliveInterval=30 -R 80:localhost:%PORT% nokey@localhost.run

echo.
echo Tunnel terputus.
pause
