@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0"
set "PORT=8000"
set "URL=http://127.0.0.1:%PORT%/index.html"
echo ========================================
echo Windows 95 Web Emulator
echo ========================================
echo.
echo 1. 로컬 웹서버 시작
echo 2. 브라우저 자동 실행
echo 3. 원격 Win95.img 자동 연결 시도
echo 4. 실패하면 images\Win95.img 자동 연결
echo 5. 그래도 실패하면 파일 선택
 echo.
where py >nul 2>nul
if %errorlevel%==0 goto RUNPY
where python >nul 2>nul
if %errorlevel%==0 goto RUNPYTHON
echo Python 3가 필요합니다.
pause
exit /b 1

:RUNPY
start "Win95 Web Server" /b py -m http.server %PORT%
goto OPEN

:RUNPYTHON
start "Win95 Web Server" /b python -m http.server %PORT%
goto OPEN

:OPEN
ping 127.0.0.1 -n 2 >nul
start "" "%URL%"
echo.
echo 서버: %URL%
echo 브라우저를 닫아도 서버 프로세스는 이 창에서 계속 실행됩니다.
echo 종료하려면 이 창에서 Ctrl+C를 누르세요.
pause
