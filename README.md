🌟 Sistem Informasi Manajemen Toko Emas "Amanda" (POS & Inventori)

Ini adalah proyek UAS (Ujian Akhir Semester) yang mengintegrasikan tiga mata kuliah: Sistem Informasi Manajemen (SIM), Manajemen Basis Data (MBD), dan Pemrograman Web.

Proyek ini adalah sistem POS (Point of Sale) dan Manajemen Inventori berbasis web yang fungsional, dirancang untuk memecahkan masalah operasional nyata di UMKM Toko Emas. Sistem ini menggantikan pencatatan manual, mengatasi kesulitan pelacakan stok dan modal (HPP), serta menyediakan laporan laba-rugi yang akurat.

🚀 Fitur Utama & Keunggulan Teknis

Sistem ini dirancang dengan pendekatan Security-First dan Scalability, melampaui standar CRUD dasar:

Akuntansi Laba/Rugi Akurat: Melacak Harga Beli Modal (HPP) untuk setiap unit barang fisik (BARANG_STOK), memungkinkan perhitungan Laba Kotor yang presisi per transaksi.

Keamanan Role-Based (RBAC): Menerapkan hak akses berbeda. Role "Kasir" dapat melakukan transaksi, tetapi tidak dapat melihat HPP/Modal atau Laporan Keuangan, yang hanya bisa diakses oleh "Pemilik" dan "Admin".

Inventori Cerdas (Pemisahan Stok): Membedakan antara PRODUK_KATALOG (Master Jenis Barang) dan BARANG_STOK (Barang Fisik Unik di etalase).

Transaksi Multi-Tipe: Mendukung tiga alur bisnis utama: Penjualan, Buyback (Beli dari Pelanggan), dan Jasa (Cuci/Patri).

Database Aktif (Triggers & Views):

Trigger: Otomatis membuat KodeBarang unik (BRG00001) dan mencatat setiap perubahan status barang ke tabel LOG_STATUS_BARANG untuk audit trail.

View: Menyediakan tabel virtual (misal V_STOK_TERSEDIA dan V_LAPORAN_LABA_RUGI) untuk menyederhanakan kueri di sisi aplikasi.

Rekomendasi Harga Cerdas: Form input stok (stok_baru.php) secara otomatis memberikan rekomendasi harga modal berdasarkan harga emas 24K harian dan asal barang (Supplier/Buyback).

Lingkungan Portabel (DevOps): Keseluruhan aplikasi (Web Server + Database + DB Admin) di-kontainerisasi menggunakan Docker Compose untuk menjamin lingkungan yang stabil dan identik bagi semua developer dan untuk deployment.

Rapid Deployment: Siap didemokan secara online dalam 30 detik menggunakan Ngrok tunneling.

🛠️ Tumpukan Teknologi (Tech Stack)

Kategori

Teknologi

Justifikasi

Backend

PHP 8.1+

Logika sisi server, manajemen session, dan keamanan.

Database

MariaDB 10.6 (MySQL)

RDBMS untuk menyimpan data dengan integritas (SQL Lanjutan).

Frontend

HTML5, CSS3, JavaScript

UI/UX (Dashboard, Form POS, Modal Pop-up, Search Filter).

Environment

Docker Compose

Menggantikan XAMPP untuk environment yang terisolasi & portabel.

DB Admin

phpMyAdmin

Dijalankan via Docker untuk manajemen database.

Metodologi

RAD (Rapid App Dev)

Dipilih untuk time-boxing UAS & akselerasi via AI Tools.

Deployment

Ngrok

Tunneling untuk demo live tanpa hosting permanen.

🚀 Panduan Instalasi & Menjalankan (via Docker)

Proyek ini dirancang untuk berjalan di Docker. Anda tidak perlu menginstal XAMPP.

Prasyarat

Docker Desktop terinstal dan berjalan di komputer Anda.

Langkah-langkah

Clone atau Unduh Proyek
Pastikan Anda memiliki semua file dalam struktur folder yang benar:

/ProyekTokoEmas/
├── public_html/
│   ├── index.php
│   ├── home.php
│   ├── koneksi.php
│   ├── (dan semua 30+ file PHP/CSS lainnya...)
│
├── database/
│   └── toko_mas_amanda.sql  <-- (File SQL lengkap Anda)
│
├── docker-compose.yml
└── Dockerfile


Edit File SQL (PENTING)
Buka file database/toko_mas_amanda.sql Anda dengan editor teks. HAPUS baris paling atas yang bertuliskan:

CREATE DATABASE toko_emas_amanda;


(Biarkan baris USE toko_emas_amanda; tetap ada. Ini wajib dihapus agar tidak konflik dengan Docker).

Jalankan Docker Compose
Buka Terminal (CMD atau PowerShell) di folder utama (/ProyekTokoEmas/) dan jalankan:

docker-compose up -d


Tunggu beberapa menit saat Docker mengunduh image dan mengimpor database Anda.

Selesai!
Environment Anda sekarang berjalan:

Aplikasi Web: http://localhost:8000

phpMyAdmin: http://localhost:8081

Server: db

Username: root

Password: root_password_super_aman

🔑 Demo & Login Pertama Kali

PENTING: Script toko_mas_amanda.sql Anda memasukkan password menggunakan hash MySQL lama yang tidak kompatibel dengan password_verify() PHP.

Anda harus membuat user baru terlebih dahulu.

Cara 1 (Disarankan): Registrasi Akun Publik

Buka http://localhost:8000/register.php

Daftarkan akun baru (misal: "kasir_baru" / "123456" / Role: Kasir).

Sekarang login di http://localhost:8000 menggunakan akun tersebut.

Cara 2: Buat Akun Admin (via phpMyAdmin)

Buka phpMyAdmin di http://localhost:8081.

Pilih database toko_emas_amanda -> tabel KARYAWAN.

Hapus user amanda_owner yang lama.

Buat user baru secara manual (misal: admin / admin123 / pemilik).

PENTING: Untuk kolom Password, pilih fungsi PASSWORD_BCRYPT (di phpMyAdmin) agar di-hash dengan benar, atau gunakan hash ini: $2y$10$T8.uF1.LwL4k0Xz.t0n0V.K/nSgexsNl.m.jL5vN/O5U.v/u.yO0i (ini adalah hash untuk "admin123").

📄 Metodologi Proyek (SIM)

Proyek ini dikembangkan menggunakan metodologi Rapid Application Development (RAD), yang difokuskan pada time-boxing yang ketat (11 minggu) dan akselerasi konstruksi menggunakan Generative AI sebagai alat bantu coding dan Docker sebagai environment tool.
