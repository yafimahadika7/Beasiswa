# Project Pemrograman Web – Rancangan Basis Data Sistem Informasi Pendaftaran Beasiswa

## 📌 Deskripsi Sistem
Sistem Informasi Pendaftaran Beasiswa berbasis web yang dibangun menggunakan
PHP dan MySQL dengan konsep **multi-role user**, yaitu:
- Admin
- Reviewer
- Mahasiswa

Setiap role memiliki hak akses dan menu yang berbeda sesuai dengan fungsinya.

## 🔐 Akun Login Default
**Admin**
- **Username** : admin  
- **Password** : admin123

**Reviewer**
- **Username** : Reviewer  
- **Password** : Reviewer123

**Mahasiswa**
- **Username** : NIM  
- **Password** : `#unpam` + 6 digit terakhir NIM  

**Contoh:**
- NIM : `221011400189`
- Password : `#unpam400189`

## 🛠️ Teknologi yang Digunakan
- PHP Native
- MySQL
- HTML, CSS, Bootstrap
- JavaScript
- Apache (XAMPP / Hosting)

### Diagram Database
<img width="1228" height="642" alt="image" src="https://github.com/user-attachments/assets/0d5bb494-1f67-466a-a430-05e7a633c700" />

## 📷 Tampilan Aplikasi

### Halaman Login
<img width="1365" height="602" alt="image" src="https://github.com/user-attachments/assets/f4e20612-d54b-4a4a-bd2f-5b342fc5b6ba" />

### Dashboard Admin
<img width="1353" height="599" alt="image" src="https://github.com/user-attachments/assets/e38859e4-ae13-4647-a6e2-71a2c01b8dd5" />

### Dashboard Reviewer
<img width="1350" height="599" alt="image" src="https://github.com/user-attachments/assets/7b4f0128-d43d-46c1-a356-1677284825c6" />

### Dashboard Mahasiswa
<img width="1351" height="595" alt="image" src="https://github.com/user-attachments/assets/508b9634-cd52-4482-9dd1-c27e3052b576" />

## 📁 Fitur Utama
- Login multi-role (Admin, Reviewer, Mahasiswa)
- Manajemen user oleh Admin
- Pendaftaran beasiswa oleh Mahasiswa
- Proses penilaian oleh Reviewer
- Dashboard sesuai role

## 🚀 Cara Menjalankan Aplikasi

Berikut adalah langkah-langkah untuk menjalankan aplikasi **Sistem Informasi
Pendaftaran Beasiswa** pada lingkungan lokal:

1. Clone repository project ke komputer lokal.
2. Pindahkan folder project ke dalam direktori web server XAMPP, yaitu folder htdocs.
3. Jalankan layanan Apache dan MySQL melalui XAMPP Control Panel.
4. Buka browser dan akses phpMyAdmin melalui alamat http://localhost/phpmyadmin.
5. Buat database baru dengan nama db_beasiswa.
6. Import file database db_beasiswa.sql yang tersedia pada folder database di dalam project.
7. Buka file konfigurasi koneksi database pada folder config (file koneksi.php), kemudian sesuaikan pengaturan koneksi database dengan server lokal.
8. Akses aplikasi melalui browser dengan alamat http://localhost/nama-folder-project.
9. Login ke sistem menggunakan akun default sesuai dengan role pengguna (Admin, Reviewer, dan Mahasiswa).

> Aplikasi ini dijalankan pada lingkungan lokal menggunakan XAMPP sebagai web server dan MySQL sebagai basis data.
