@echo off
REM VARIAN LINTAS LAPTOP - hanya dipakai kalau Gateway Kelompok 1 jalan di laptop Kelompok 1.
REM File asli jalankan-course-8103.bat TIDAK diubah: file itu tetap mengikat 127.0.0.1:8103.
REM Varian ini mengikat 0.0.0.0:8103 supaya laptop Kelompok 1 bisa menjangkau Course ini lewat IP LAN.
REM Port, router.php, /api/courses, dan /api/health TIDAK berubah.
REM Syarat: XAMPP sudah terinstall (php.exe di C:\xampp\php\)
REM dan MySQL sudah Start + course_db sudah di-import.
REM Sebelum memakai varian ini: tutup dulu jendela jalankan-course-8103.bat yang sedang jalan,
REM karena port 8103 tidak bisa dipakai dua program sekaligus.
REM Pastikan kedua laptop di Wi-Fi yang sama, cek ulang IPv4 dengan ipconfig di hari demo,
REM dan izinkan php.exe lewat Windows Firewall kalau diminta.
REM Setelah jendela ini terbuka, JANGAN ditutup selama demo.
cd /d "%~dp0course-service"
"C:\xampp\php\php.exe" -S 0.0.0.0:8103 router.php
pause
