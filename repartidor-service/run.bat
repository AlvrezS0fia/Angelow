@echo off
title ANGELOW - Microservicio Registro Repartidores
cd /d %~dp0

echo ==================================================
echo  ANGELOW - Registro de Repartidores
echo  Instalando dependencias...
echo ==================================================
pip install -r requirements.txt

if errorlevel 1 (
    echo.
    echo ERROR: No se pudieron instalar las dependencias.
    pause
    exit /b 1
)

echo.
echo Iniciando servidor en http://127.0.0.1:8000
echo Detener con Ctrl+C
echo ==================================================
python main.py

pause