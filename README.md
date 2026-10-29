# 📚 Kelompok 4 — Assignment Service (Sistem Terdistribusi UTS)

Layanan Microservice **Assignment (Tugas)** yang dibangun menggunakan **PHP Native** untuk Ujian Tengah Semester (UTS) Mata Kuliah Sistem Terdistribusi.

---

## 🏛️ Topologi Arsitektur & Standar Port Kelas

Berdasarkan kesepakatan dan referensi kelompok yang telah diterima:

| Kelompok | Nama Layanan | Port Bawaan | Contoh Alamat Lokal / LAN | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **Kelompok 1** | API Gateway | `8101` | `http://192.168.x.x:8101` | Single entry point & service resolver |
| **Kelompok 2** | Authentication | `8102` | `http://192.168.x.x:8102` | Token JWT verifier |
| **Kelompok 3** | Course Service | `8103` | `http://192.168.x.x:8103` | `PTIK301` Sistem Terdistribusi, `PTIK302` Pemrograman Web |
| **Kelompok 4** | **Assignment Service** | **`8104` / `4000`** | **`http://10.194.169.115:8104`** | **LAYANAN KITA (Tugas & Pengumpulan)** |
| **Kelompok 5** | Submission / File | `8105` | `http://192.168.x.x:8105` | Upload file tugas (`db_kelompok5`) |
| **Kelompok 6** | Notification | `8106` | `http://10.236.161.36/notification/api.php` | Menerima trigger notifikasi otomatis dari Kelompok 5 |
| **Kelompok 7** | Monitoring Service | - | - | Memeriksa `GET /health` pada semua service |

---

## 🚀 Cara Menjalankan Layanan

### 1. Mode Standar Port 8104 (Direkomendasikan untuk UTS)
- **Satu Laptop (Testing Mandiri):**
  Double-click: **`jalankan-assignment-8104.bat`** (berjalan di `127.0.0.1:8104`)
- **Lintas Laptop (Satu Wi-Fi dengan Kelompok Lain):**
  Double-click: **`jalankan-assignment-8104-lan.bat`** (berjalan di `0.0.0.0:8104`)

### 2. Mode Port 4000 (Alternatif)
- Double-click: **`run.bat`** (berjalan di `0.0.0.0:4000`)

---

## 🔗 Cara Menghubungkan ke IP Perangkat Kelompok 3 & 5

Saat Anda berada di lab/kelas dan mendapatkan IP Wi-Fi dari teman kelompok 3 dan 5:

1. Buka browser di Dashboard Kelompok 4:
   👉 **`http://localhost:4000`** (atau `http://localhost:8104`)
2. Pada bagian **"Pengaturan IP Perangkat Kelompok 3 & 5"**:
   - **Kelompok 3:** Masukkan IP laptop teman K3 (contoh: `http://192.168.1.8:8103`).
     Klik tombol **"Ping"** atau **"📥 Tarik Daftar Matkul dari Kelompok 3"**.
   - **Kelompok 5:** Masukkan IP laptop teman K5 (contoh: `http://192.168.1.15:8105` atau `http://192.168.1.15/kelompok5-submission/api.php`).
     Klik tombol **"Ping"**.
   - **Kelompok 1:** Masukkan IP Gateway teman K1 (contoh: `http://192.168.1.10:8101`).
3. Klik tombol **"Simpan IP Perangkat"**.
4. Semua request pembuatan tugas dan penarikan submission akan otomatis diarahkan secara langsung ke perangkat teman Anda!

> **Catatan Smart Fallback:** Jika perangkat teman sedang offline atau belum siap, centang opsi **"Mode Toleransi (Smart Fallback)"**. Sistem akan otomatis menggunakan data simulasi `PTIK301` dan `PTIK302` sehingga demo Anda di depan dosen tetap berjalan lancar 100%!

---

## 📤 Apa yang Harus Diserahkan ke Kelompok 1 (API Gateway)

Kelompok 1 membutuhkan entri untuk dimasukkan ke `services.json` mereka:

- **Untuk Demo Satu Laptop:**
  Serahkan file: [`registrasi-assignment-SATU-LAPTOP.json`](file:///d:/Semester%205/SISTEM%20TERDISTRIBUSI/uts/registrasi-assignment-SATU-LAPTOP.json)
  ```json
  {"assignment": "http://127.0.0.1:8104"}
  ```
- **Untuk Demo Lintas Laptop (Jaringan Wi-Fi):**
  Serahkan file: [`registrasi-assignment-LINTAS-LAPTOP.json`](file:///d:/Semester%205/SISTEM%20TERDISTRIBUSI/uts/registrasi-assignment-LINTAS-LAPTOP.json)
  ```json
  {"assignment": "http://10.194.169.115:8104"}
  ```

---

## 📮 Pengujian Menggunakan Postman

Koleksi Postman siap pakai:
📂 [`UTS_Sistr_Kelompok_4_Assignment.postman_collection.json`](file:///d:/Semester%205/SISTEM%20TERDISTRIBUSI/uts/UTS_Sistr_Kelompok_4_Assignment.postman_collection.json)

Request yang tersedia:
1. **Kelompok 7 Monitoring:**
   - `GET /health` (memeriksa status UP, memory, dan latensi ke Kelompok 1, 3, 5).
2. **Assignment CRUD (Kelompok 4):**
   - `GET /api/assignments` (Daftar semua tugas).
   - `GET /api/assignments?course_id=PTIK301` (Filter mata kuliah PTIK301).
   - `GET /api/assignments/ASN-001` (Detail tugas ASN-001).
   - `POST /api/assignments` (Buat tugas baru & verifikasi otomatis ke Kelompok 3).
   - `PUT /api/assignments/ASN-001` (Update tugas).
   - `DELETE /api/assignments/ASN-003` (Hapus tugas).
3. **Integrasi Kelompok 3:**
   - `GET /api/external/courses` (Tarik live list mata kuliah dari Kelompok 3).
4. **Integrasi Kelompok 5 & 6:**
   - `GET /api/assignments/ASN-001/submissions` (Ambil submission dari Kelompok 5).
   - `POST /api/assignments/ASN-001/submissions` (Unggah file mahasiswa ke Kelompok 5, yang otomatis memicu notifikasi ke Kelompok 6).
5. **Konfigurasi IP Jaringan:**
   - `GET /api/config` & `POST /api/config`.

---

## 🎯 Panduan Skenario Demo ke Dosen

1. **Jalankan Service:** Buka `jalankan-assignment-8104.bat` atau `run.bat`.
2. **Buka Dashboard:** Buka `http://localhost:4000` (atau `http://localhost:8104`).
3. **Tunjukkan Monitoring Kelompok 7:**
   Klik `GET /health`. Tunjukkan status `UP`, waktu aktif, dan dependensi ke kelompok lain.
4. **Tunjukkan Integrasi ke Kelompok 3 (Course):**
   - Klik `Tarik Daftar Matkul dari Kelompok 3`. Terlihat mata kuliah `PTIK301 - Sistem Terdistribusi` dan `PTIK302 - Pemrograman Web`.
   - Klik `+ Tambah Tugas Baru`. Pilih `PTIK301`, lalu kirim (POST). Jelaskan bahwa Kelompok 4 menghubungi Kelompok 3 untuk memvalidasi mata kuliah tersebut.
5. **Tunjukkan Integrasi ke Kelompok 5 & 6 (Submission & Notification):**
   - Klik tombol `Submissions (K5)` pada tugas.
   - Unggah file tugas simulasi mahasiswa (POST ke Kelompok 5).
   - Jelaskan bahwa data tersimpan di Kelompok 5 dan otomatis memicu notifikasi ke Kelompok 6.
6. **Tunjukkan Integrasi Gateway Kelompok 1:**
   Tunjukkan file `registrasi-assignment-LINTAS-LAPTOP.json` yang telah didaftarkan ke Kelompok 1 sehingga client bisa mengakses assignment melalui API Gateway.
