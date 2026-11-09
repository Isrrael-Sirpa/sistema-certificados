@echo off
setlocal
cd /d "%~dp0"
if errorlevel 1 exit /b 1
title Certificacion Digital FT-UMSA

:MENU
cls
echo ================================================================
echo             CERTIFICACION DIGITAL - FT UMSA
echo ================================================================
echo.
echo  Abre dos terminales en la carpeta del proyecto.
echo  Usa la opcion 1 en una y la opcion 2 en la otra.
echo.
echo  [1] Backend Laravel - puerto 8000
echo  [2] Frontend Vite - puerto 5173
echo  [3] Compilar frontend para el servidor
echo  [4] Salir
echo.
choice /c 1234 /n /m "Selecciona una opcion [1-4]: "
if errorlevel 4 goto FIN
if errorlevel 3 goto COMPILAR
if errorlevel 2 goto FRONTEND
if errorlevel 1 goto BACKEND
goto FIN

:BACKEND
if not exist vendor\autoload.php (
    echo Instala las dependencias con composer install.
    goto ERROR
)
if not exist .env (
    echo Configura .env y ejecuta las migraciones segun el README.
    goto ERROR
)
echo.
echo Backend Laravel en http://127.0.0.1:8000
echo Inicia el frontend desde otra terminal con la opcion 2.
echo Presiona Ctrl+C para detener este proceso.
php artisan serve --host=127.0.0.1 --port=8000 --tries=1
if errorlevel 1 goto ERROR
goto FIN

:FRONTEND
if not exist node_modules\vite\bin\vite.js (
    echo Instala las dependencias con npm ci.
    goto ERROR
)
echo.
echo Frontend Vite en http://127.0.0.1:5173
echo Abre la aplicacion en http://127.0.0.1:8000
echo Presiona Ctrl+C para detener este proceso.
call npm.cmd run dev -- --host 127.0.0.1 --port 5173 --strictPort
if errorlevel 1 goto ERROR
goto FIN

:COMPILAR
if not exist node_modules\vite\bin\vite.js (
    echo Instala las dependencias con npm ci.
    goto ERROR
)
if exist public\hot (
    echo Deten Vite antes de compilar. Revisa public\hot segun el README.
    goto ERROR
)
call npm.cmd run build
if errorlevel 1 goto ERROR
echo.
echo Frontend compilado en public\build.
echo Sigue la seccion de instalacion en servidor del README.
pause
goto FIN

:ERROR
echo.
echo El proceso no finalizo correctamente. Revisa el mensaje anterior.
pause
endlocal
exit /b 1

:FIN
endlocal
exit /b 0
