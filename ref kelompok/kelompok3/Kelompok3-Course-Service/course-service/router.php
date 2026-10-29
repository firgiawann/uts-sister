<?php
// Router untuk PHP built-in server (dipakai saat Course Service
// dijalankan di port khusus untuk Gateway Registry Kelompok 1,
// misalnya port 8103, BUKAN lewat Apache XAMPP).
// Cara pakai (di folder ini, setelah XAMPP terinstall ulang):
//   C:\xampp\php\php.exe -S 127.0.0.1:8103 router.php
// Atau klik 2 kali jalankan-course-8103.bat
// Semua request (termasuk /api/courses dan /api/health) diteruskan
// ke index.php, yang sudah menangani parsing path /api/courses
// apa pun prefix foldernya. File statis tidak ada di service ini,
// jadi tidak ada pengecualian yang diperlukan.

require __DIR__ . "/index.php";
