@echo off
REM ============================================================
REM  Tahsin International - local dev server
REM  Serves this folder at http://127.0.0.1:8005/
REM  Prefers PHP (so the contact/RFQ forms work); falls back to Python.
REM ============================================================
setlocal
cd /d "%~dp0"
set "PORT=8005"
set "URL=http://127.0.0.1:%PORT%/"

where php >nul 2>nul
if %errorlevel%==0 goto php
where python >nul 2>nul
if %errorlevel%==0 goto python
where py >nul 2>nul
if %errorlevel%==0 goto py

echo.
echo   Neither PHP nor Python was found on your PATH.
echo   Install PHP (recommended - enables the forms) or Python, then re-run.
echo.
pause
exit /b 1

:php
echo.
echo   Starting PHP server (forms enabled) at %URL%
echo   Press Ctrl+C to stop.
echo.
start "" "%URL%"
php -S 127.0.0.1:%PORT%
goto end

:python
echo.
echo   PHP not found - starting Python static server at %URL%
echo   (Static preview only; forms will not be processed without PHP.)
echo   Press Ctrl+C to stop.
echo.
start "" "%URL%"
python -m http.server %PORT% --bind 127.0.0.1
goto end

:py
echo.
echo   PHP not found - starting Python static server at %URL%
echo   (Static preview only; forms will not be processed without PHP.)
echo   Press Ctrl+C to stop.
echo.
start "" "%URL%"
py -m http.server %PORT% --bind 127.0.0.1

:end
endlocal
