@echo off
title XVIDSUP Uploader
chcp 65001 >nul

:: Cari PHP
set PHP_CMD=php.exe
where php.exe >nul 2>&1
if %errorlevel% neq 0 (
    if exist "%~dp0php\php.exe" (
        set PHP_CMD="%~dp0php\php.exe"
    ) else if exist "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" (
        set PHP_CMD="%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    ) else (
        echo [ERROR] PHP tidak ditemukan!
        echo Install PHP dulu: winget install PHP.PHP.8.3
        pause
        exit /b 1
    )
)

echo ====================================
echo   XVIDSUP Uploader v1.0
echo   Upload video -^> LuluStream + Database
echo ====================================
echo.
set /p folder="Folder video: "
if "%folder%"=="" exit /b
set /p category="Kategori (enter skip): "
echo.
echo Mulai processing...
echo.
%PHP_CMD% "%~dp0uploader.php" "%folder%" "%category%"
echo.
pause
