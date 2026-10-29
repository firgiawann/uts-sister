@echo off
title Kelompok 4 - Assignment Service (Sistem Terdistribusi UTS)
color 0A
cls
echo =====================================================================
echo       KELOMPOK 4 - ASSIGNMENT SERVICE (SISTEM TERDISTRIBUSI)
echo =====================================================================
echo  Service ini berjalan pada:
echo   - Localhost : http://localhost:4000
echo   - Local IP  : Silakan cek IPv4 Wi-Fi (contoh: http://10.194.169.115:4000)
echo.
echo  Endpoint Utama:
echo   - GET    /health                     (Monitoring Kelompok 7)
echo   - GET    /api/assignments            (List Tugas)
echo   - POST   /api/assignments            (Buat Tugas Baru - Verifikasi K3)
echo   - GET    /api/assignments/{id}       (Detail Tugas)
echo   - PUT    /api/assignments/{id}       (Update Tugas)
echo   - DELETE /api/assignments/{id}       (Hapus Tugas)
echo   - GET    /api/assignments/{id}/submissions (Ambil Submission K5)
echo.
echo  Web Dashboard: Buka browser di http://localhost:4000
echo =====================================================================
echo  Menjalankan PHP Built-in Server di 0.0.0.0:4000...
echo  (Tekan Ctrl+C untuk menghentikan server)
echo =====================================================================
echo.
php -S 0.0.0.0:4000 router.php
pause
