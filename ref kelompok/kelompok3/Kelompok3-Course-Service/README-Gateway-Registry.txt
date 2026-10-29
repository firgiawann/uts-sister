=================================================================
KELOMPOK 3 — COURSE SERVICE v2
Disesuaikan dengan Gateway Registry Kelompok 1
=================================================================

YANG BERUBAH DARI VERSI SEBELUMNYA — BACA INI DULU
--------------------------------------------------
Versi sebelumnya menebak Gateway Kelompok 1 berupa proxy /api/courses
di http://localhost/api-gateway. Itu SALAH menurut 2 PDF Kelompok 1
yang baru diterima. Gateway Kelompok 1 yang sebenarnya:

  - Pintu masuk: gateway.php, berjalan di http://127.0.0.1:8101
  - gateway.php memanggil fungsi resolveService('course') di resolver.php
  - resolver.php membaca services.json: nama logis -> alamat fisik (IP + port)
  - Gateway mengecek koneksi, meneruskan request ke service tujuan,
    service mengembalikan JSON akhir ke client.
  - Nama logis Course adalah: course
  - Alamat hasil lookup pada pengujian Kelompok 1: http://127.0.0.1:8103
    (setelah uji perubahan port: http://127.0.0.1:8203, tanpa ubah kode)

Jadi tugas Kelompok 3 sekarang BUKAN membuat route proxy baru di Gateway,
melainkan:
  1. Menjalankan Course Service di port 8103 (alamat yang didaftarkan),
  2. Memberikan entri registrasi ke Kelompok 1 untuk dimasukkan ke services.json,
  3. Mendemonstrasikan 4 uji yang sama seperti laporan Kelompok 1.

API Course sendiri TIDAK berubah: tetap GET /api/courses,
GET /api/courses/{id}, POST /api/courses, DELETE /api/courses/{id},
plus GET /api/health. Field tetap code/name/credits, database tetap
course_db, data awal tetap PTIK301/PTIK302.

ISI PAKET v2
------------
Kelompok3-Course-Service-v2-Gateway\
├── README-Gateway-Registry.txt          <- file ini
├── Panduan_Demo_Kelompok3_v2_Gateway.docx
├── registrasi-course-untuk-services.json <- SERAHKAN KE KELOMPOK 1
├── jalankan-course-8103.bat             <- klik 2 kali, course jalan di 8103
├── jalankan-course-8203.bat             <- HANYA untuk uji perubahan port
├── course-service\
│   ├── .htaccess                        <- untuk Apache XAMPP
│   ├── db.php
│   ├── index.php                        <- API Course (tidak berubah)
│   └── router.php                       <- untuk PHP built-in server port 8103
├── database\
│   └── course_db.sql
└── postman\
    └── Kelompok3-Course-Service-v2-Gateway.postman_collection.json

APA YANG HARUS DISERAHKAN KE KELOMPOK 1
---------------------------------------
Satu baris ini, untuk dimasukkan ke services.json mereka:
    "course": "http://127.0.0.1:8103"
File registrasi-course-untuk-services.json di paket ini berisi persis
baris itu. CATATAN JUJUR: isi persis services.json Kelompok 1 tidak
terlihat di PDF mereka (hanya dijelaskan sebagai pemetaan nama logis
& port, dan hasil lookup-nya berupa URL). Jika file services.json
mereka ternyata memakai format host/port terpisah, bukan string URL,
maka entri di atas disesuaikan menjadi host 127.0.0.1 dan port 8103
— nama logisnya tetap "course", portnya tetap 8103. Jangan menebak
format lain sebelum melihat satu entri services.json mereka yang sudah ada.

CARA MENJALANKAN COURSE DI PORT 8103
-------------------------------------
Syarat: XAMPP sudah terinstall ulang, Apache + MySQL Start,
dan course_db.sql sudah di-import lewat phpMyAdmin.
Lalu, di folder paket v2 ini, klik 2 kali jalankan-course-8103.bat.
Jendela hitam akan terbuka dan HARUS tetap terbuka selama demo.
Perintah yang dijalankan file .bat itu (bisa juga diketik manual
di terminal, dari dalam folder course-service):
    C:\xampp\php\php.exe -S 127.0.0.1:8103 router.php
Cek mandiri di browser:
    http://127.0.0.1:8103/api/courses
    http://127.0.0.1:8103/api/health
Jika MySQL belum jalan, API akan membalas 500 "Koneksi database gagal"
— itu tanda MySQL-nya yang belum Start, bukan kode course yang salah.

CARA LAMA (XAMPP Apache) MASIH BERLAKU SEBAGAI CADANGAN
--------------------------------------------------------
Salin isi folder course-service ke C:\xampp\htdocs\Kelompok3-Course-Service\
(atau biarkan folder itu di sana seperti sebelumnya), lalu cek:
    http://localhost/Kelompok3-Course-Service/api/courses
Ini berguna bila Gateway belum siap: demo mandiri tetap bisa jalan.

4 UJI DEMO — SAMA PERSIS SEPERTI LAPORAN KELOMPOK 1
---------------------------------------------------
Impor collection Postman v2, lalu jalankan folder "Tahap Registry"
secara berurutan. Gateway harus sudah jalan di 8101, dan Kelompok 1
harus sudah memasukkan entri "course" ke services.json mereka.

1) Uji nama valid
   GET http://127.0.0.1:8101/api/registry/course
   (course JALAN di 8103)
   Hasil yang diharapkan — 200 OK:
   {
     "status": "success",
     "service": "course",
     "resolved_url": "http://127.0.0.1:8103",
     "message": "Lookup berhasil!"
   }

2) Uji nama tidak ditemukan
   GET http://127.0.0.1:8101/api/registry/sinta
   Hasil yang diharapkan — 400 Bad Request:
   {
     "status": "error",
     "message": "Konfigurasi error: Nama service 'sinta' tidak ditemukan dalam registry."
   }
   Perhatian: Kelompok 1 memakai 400 (bukan 404) untuk kasus ini,
   sesuai screenshot laporan mereka. Jangan "memperbaiki" jadi 404
   saat menjelaskan — sebutkan apa adanya.

3) Uji service mati / 503
   STOP dulu course di 8103 (tutup jendela .bat-nya), lalu kirim lagi:
   GET http://127.0.0.1:8101/api/registry/course
   Hasil yang diharapkan — 503 Service Unavailable:
   {
     "status": "error",
     "code": 503,
     "message": "Service 'course' pada alamat http://127.0.0.1:8103 tidak merespons (Service Unavailable)."
   }
   Setelah uji ini, JALANKAN LAGI course di 8103 sebelum lanjut.

4) Uji perubahan port (tanpa ubah kode)
   a. Tutup jendela course 8103.
   b. Minta Kelompok 1 mengubah services.json: course 8103 -> 8203.
      (Hanya file services.json yang diubah, kode gateway dan kode
      course TIDAK diubah — inilah inti uji ini.)
   c. Jalankan jalankan-course-8203.bat (course jalan di 8203).
   d. Kirim lagi GET http://127.0.0.1:8101/api/registry/course
   Hasil yang diharapkan — 200 OK, resolved_url BERUBAH:
   {
     "status": "success",
     "service": "course",
     "resolved_url": "http://127.0.0.1:8203",
     "message": "Lookup berhasil!"
   }
   Setelah demo, kembalikan ke 8103 (services.json + .bat 8103)
   supaya sesuai kondisi awal.

Setelah 4 uji registry lolos, lanjutkan folder Postman
"Tahap Course - di alamat resolved_url": GET/POST/GET detail/
GET health/DELETE di http://127.0.0.1:8103/api/courses,
persis seperti uji mandiri sebelumnya, hanya alamatnya pindah
dari localhost XAMPP ke port 8103.

TENTANG PDF KEDUA KELOMPOK 1 (Prototipe-Submission)
----------------------------------------------------
PDF kedua itu BUKAN tugas Kelompok 3 dan BUKAN endpoint yang harus
kamu buat. Itu prototipe Submission (domain Kelompok 5) yang dipakai
sebagai contoh service: POST http://localhost:8080/api/submissions
dan GET /api/submissions/{id}, penyimpanan metadata JSON, file_id
format "f-9107", internal_path disembunyikan dari respons (unset
sebelum json_encode), dan belum menangani upload file fisik
multipart/form-data. Yang relevan untuk Kelompok 3 dari PDF itu
hanyalah POLA-nya: kontrak API yang jelas (method, URL, body,
respons sukses 201 dan gagal 400/404), dan respons yang hanya
menampilkan data publik yang aman. Endpoint Kelompok 3 tetap
/api/courses dengan field code/name/credits dan format respons
{success, data/message} seperti versi sebelumnya — jangan dicampur
dengan format {data: ...} milik Submission.

NASKAH UCAP DEMO v2 (singkat)
------------------------------
"Kami Kelompok 3, Course Service. Service kami didaftarkan di Gateway
Kelompok 1 dengan nama logis 'course'. Saat client meminta
GET /api/registry/course ke gateway di port 8101, gateway memanggil
resolver yang membaca services.json dan menemukan alamat kami di
port 8103. Gateway mengecek koneksi lalu meneruskan request ke kami,
dan kami mengembalikan data course dalam bentuk JSON. Kalau nama
service tidak ada di registry, gateway membalas 400; kalau service
kami mati, gateway membalas 503; dan kalau port kami diubah di
services.json saja, tanpa mengubah kode apa pun, lookup berikutnya
langsung menemukan alamat baru. API kami sendiri tetap GET, POST,
dan DELETE di /api/courses, dengan database MySQL course_db."
