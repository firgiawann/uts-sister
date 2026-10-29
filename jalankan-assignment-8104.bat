@echo off
title Kelompok 4 - Assignment Service (Port 8104 - Satu Laptop)
cls
echo =================================================================
echo   KELOMPOK 4 - ASSIGNMENT SERVICE (PORT 8104)
echo   Mode: SATU LAPTOP (127.0.0.1:8104)
echo =================================================================
echo.
echo   URL Service  : http://127.0.0.1:8104
echo   Health Check : http://127.0.0.1:8104/health (Kelompok 7)
echo   Dashboard    : http://127.0.0.1:8104/
echo.
echo   Kirim file ini ke Kelompok 1 (Gateway):
echo   registrasi-assignment-SATU-LAPTOP.json
echo =================================================================
echo   Jendela ini HARUS TETAP TERBUKA selama pengujian/demo.
echo   (Tekan Ctrl+C untuk menghentikan server)
echo =================================================================
echo.
php -S 127.0.0.1:8104 router.php
pause
