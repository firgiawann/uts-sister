KELOMPOK 3 - COURSE SERVICE (PAKET BENAR)
============================================

ISI PAKET (jangan diratakan, biarkan strukturnya seperti ini):
  course-service/index.php, db.php, .htaccess, router.php
  database/course_db.sql
  postman/Kelompok3-Course-Service-v2-Gateway.postman_collection.json
  jalankan-course-8103.bat        (satu laptop, 127.0.0.1:8103)
  jalankan-course-8103-lan.bat    (lintas laptop, 0.0.0.0:8103)
  jalankan-course-8203.bat        (uji ubah port, hanya untuk tes)
  registrasi-course-SATU-LAPTOP.json
  registrasi-course-LINTAS-LAPTOP.json
  Panduan_Demo_Kelompok3_v2_Gateway.docx
  README-Gateway-Registry.txt

ENDPOINT COURSE (tidak berubah):
  GET  /api/health
  GET  /api/courses
  GET  /api/courses/{id}
  POST /api/courses   {"code":"PTIK303","name":"Basis Data","credits":3}
  DELETE /api/courses/{id}
  Database: course_db, tabel courses (code/name/credits)
  Data awal: PTIK301 Sistem Terdistribusi (3), PTIK302 Pemrograman Web (3)

CARA PAKAI 1 - UJI MANDIRI XAMPP:
  1. Salin HANYA index.php, db.php, .htaccess dari course-service/
     ke C:\xampp\htdocs\Kelompok3-Course-Service\
  2. Start Apache + MySQL di XAMPP, import database/course_db.sql
     lewat phpMyAdmin.
  3. Buka http://localhost/Kelompok3-Course-Service/api/courses
     dan /api/health di browser/Postman.

CARA PAKAI 2 - SATU LAPTOP (Gateway juga di laptop ini):
  1. Klik dua kali jalankan-course-8103.bat (jangan ditutup selama demo).
  2. Kirim ke Kelompok 1 isi registrasi-course-SATU-LAPTOP.json:
     {"course":"http://127.0.0.1:8103"}

CARA PAKAI 3 - LINTAS LAPTOP (Gateway di laptop Kelompok 1):
  1. Tutup dulu jendela 8103 yang lama (port tidak bisa dipakai dua kali).
  2. Klik dua kali jalankan-course-8103-lan.bat (0.0.0.0:8103),
     izinkan lewat Windows Firewall kalau diminta.
  3. Kedua laptop harus di Wi-Fi yang SAMA.
  4. Kirim ke Kelompok 1 isi registrasi-course-LINTAS-LAPTOP.json:
     {"course":"http://192.168.1.8:8103"}
     Angka 192.168.1.8 adalah IPv4 Wi-Fi laptop ini pada 2026-10-27.
     IP LAN BISA BERUBAH kalau ganti Wi-Fi - cek lagi dengan ipconfig
     di hari demo sebelum kirim ke Kelompok 1.

YANG TIDAK ADA DI PAKET INI (sengaja):
  Folder gateway-sementara TIDAK dimasukkan. Itu hanya alat latihan
  di laptop sendiri, BUKAN Gateway asli Kelompok 1, dan jangan
  dikirim seolah-olah itu kerjaan Kelompok 1.
  Postman API key TIDAK diperlukan - Course ini tidak pakai API key.
