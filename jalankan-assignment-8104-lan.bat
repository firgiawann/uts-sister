@echo off
title Kelompok 4 - Assignment Service (Port 8104 - LINTAS LAPTOP)
cls
echo =================================================================
echo   KELOMPOK 4 - ASSIGNMENT SERVICE (PORT 8104 - LAN)
echo   Mode: LINTAS LAPTOP (0.0.0.0:8104)
echo =================================================================
echo.
echo   Pastikan laptop Anda dan kelompok lain terhubung ke Wi-Fi yang SAMA.
echo.
echo   Cek IP Wi-Fi Anda saat ini:
ipconfig | findstr /i "IPv4"
echo.
echo   Kirim file ini ke Kelompok 1 (Gateway):
echo   registrasi-assignment-LINTAS-LAPTOP.json
echo =================================================================
echo   Jendela ini HARUS TETAP TERBUKA selama pengujian/demo.
echo   (Tekan Ctrl+C untuk menghentikan server)
echo =================================================================
echo.
php -S 0.0.0.0:8104 router.php
pause
