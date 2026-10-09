@echo off
title Sistema de Certificados UMSA - Lanzador Oficial
color 0F
cls

echo ===============================================================================
echo        SISTEMA DE CERTIFICADOS ACADEMICOS - VICEDECANATO FCPN UMSA
echo ===============================================================================
echo.
echo  Seleccione como desea iniciar el proyecto:
echo.
echo  [1] Modo Autonomo (Recomendado para Laragon)
echo      - Elimina archivos residuales de Vite
echo      - Asegura que los estilos carguen al 100%% con solo php artisan serve
echo.
echo  [2] Modo Desarrollo (Recarga rapida en vivo)
echo      - Inicia php artisan serve (puerto 8000)
echo      - Inicia npm run dev (Vite en paralelo en una nueva ventana)
echo.
echo  [3] Recompilar estilos de produccion (npm run build)
echo.
echo  [4] Salir
echo.
echo ===============================================================================

set /p opcion=" Ingrese su opcion [1-4]: "

if "%opcion%"=="1" goto MODO_AUTONOMO
if "%opcion%"=="2" goto MODO_DEV
if "%opcion%"=="3" goto COMPILAR
if "%opcion%"=="4" goto FIN

:MODO_AUTONOMO
cls
echo ===============================================================================
echo  Iniciando en Modo Autonomo...
echo ===============================================================================
if exist public\hot (
    echo  Limpiando archivo temporal public\hot...
    del /f /q public\hot
)
echo.
echo  Iniciando servidor Laravel en http://127.0.0.1:8000 ...
echo  Presione Ctrl + C para detener el servidor.
echo.
php artisan serve --port=8000
goto FIN

:MODO_DEV
cls
echo ===============================================================================
echo  Iniciando en Modo Desarrollo (Laravel + Vite)...
echo ===============================================================================
echo.
echo  1. Abriendo Vite dev server (npm run dev) en ventana secundaria...
start "Vite Dev Server (Tailwind)" cmd /k "npm run dev"
echo.
echo  2. Iniciando servidor Laravel en http://127.0.0.1:8000 ...
echo  Presione Ctrl + C para detener el servidor.
echo.
php artisan serve --port=8000
goto FIN

:COMPILAR
cls
echo ===============================================================================
echo  Compilando estilos y componentes (npm run build)...
echo ===============================================================================
if exist public\hot (
    del /f /q public\hot
)
call npm run build
echo.
echo  Compilacion exitosa. Ahora puede usar la opcion [1] o Laragon directamente.
echo.
pause
goto FIN

:FIN
exit
