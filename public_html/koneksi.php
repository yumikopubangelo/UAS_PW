<?php
// --- Konfigurasi Database (MySQL/MariaDB) ---
$host = 'db';          // <-- INI VERSI DOCKER (BENAR)
                       // Nama host = nama layanan di docker-compose.yml

$user = 'root';          // User root
$pass = 'root_password_super_aman'; // Password dari docker-compose.yml
$db   = 'toko_emas_amanda';  // Nama database
$port = 3306;            // Port default MySQL (di dalam Docker)

// ... sisa kode koneksi Anda ...
$koneksi = new mysqli($host, $user, $pass, $db, $port);

// Cek koneksi
if ($koneksi->connect_error) {
    // Jika koneksi gagal, hentikan script dan tampilkan error
    die("Koneksi gagal: " . $koneksi->connect_error);
}



// echo "Koneksi ke database MySQL berhasil!";
?>