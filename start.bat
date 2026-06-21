@echo off
title XVIDSUP Uploader - Server
chcp 65001 >nul

:: Cari PHP
set PHP_CMD=php.exe
where php.exe >nul 2>&1
if %errorlevel% neq 0 (
    if exist "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" (
        set "PHP_CMD=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    ) else (
        echo [ERROR] PHP tidak ditemukan!
        echo Install PHP: winget install PHP.PHP.8.3
        pause
        exit /b 1
    )
)

:: Cari port kosong
set PORT=8080
:checkport
netstat -an | find ":%PORT% " >nul 2>&1
if %errorlevel% equ 0 (
    set /a PORT+=1
    goto checkport
)

:: Dapatkan IP komputer di semua jaringan
for /f "tokens=*" %%i in ('powershell -command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -ne '127.0.0.1' }).IPAddress" 2^>nul') do (
    if not "%%i"=="" set "IP_LIST=%%i"
)

cls
echo ============================================
echo        XVIDSUP Uploader - SERVER
echo ============================================
echo.
echo [INFO] Server running on port %PORT%
echo.

:: Tampilkan IP yang bisa diakses
for %%a in (%IP_LIST%) do (
    echo [ACCESS] http://%%a:%PORT%
)
echo.
echo Dari HP (USB Tether):
echo  Buka browser HP, masuk ke salah satu URL di atas
echo.
echo Dari HP (WiFi):
echo  Pastikan HP dan PC di jaringan WiFi yang sama
echo  Lalu buka URL di atas
echo.
echo [PUBLIC] Jalankan tunnel.bat untuk akses publik
echo.
echo -------------------------------------------
echo Server siap, buka http://localhost:%PORT% di PC
echo Tekan CTRL+C untuk menghentikan server
echo ============================================
echo.

:: Buka browser untuk localhost
start http://localhost:%PORT%

:: Jalankan server bind ke semua interface (0.0.0.0)
%PHP_CMD% -d upload_max_filesize=2G -d post_max_size=2G -d max_execution_time=0 -d memory_limit=512M -S 0.0.0.0:%PORT% "%~dp0router.php"
pause
