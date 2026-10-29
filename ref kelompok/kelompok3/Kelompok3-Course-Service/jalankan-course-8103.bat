@echo off
REM Jalankan Course Service Kelompok 3 di port 8103
REM agar cocok dengan services.json Gateway Kelompok 1
REM (resolved_url awal: http://127.0.0.1:8103).
REM Syarat: XAMPP sudah terinstall (php.exe di C:\xampp\php\)
REM dan MySQL sudah Start + course_db sudah di-import.
REM Setelah jendela ini terbuka, JANGAN ditutup selama demo.
cd /d "%~dp0course-service"
"C:\xampp\php\php.exe" -S 127.0.0.1:8103 router.php
pause
