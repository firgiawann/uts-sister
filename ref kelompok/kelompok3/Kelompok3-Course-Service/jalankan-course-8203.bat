@echo off
REM Jalankan Course Service Kelompok 3 di port 8203
REM DIPAKAI HANYA untuk Uji Perubahan Port Kelompok 1:
REM 1. Tutup jendela port 8103 dulu.
REM 2. Minta Kelompok 1 mengubah services.json course: 8103 -> 8203.
REM 3. Jalankan file ini, lalu uji lagi GET /api/registry/course
REM    di Gateway (8101) — resolved_url harus berubah jadi 8203
REM    TANPA mengubah kode gateway maupun kode course.
cd /d "%~dp0course-service"
"C:\xampp\php\php.exe" -S 127.0.0.1:8203 router.php
pause
