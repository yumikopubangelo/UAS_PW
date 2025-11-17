<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Hanya Admin/Pemilik)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Akses ditolak. Silakan login.";
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses untuk aksi ini.";
    header("Location: home.php");
    exit;
}

// 3. Pastikan form disubmit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 4. Ambil data dari form
    $nama_produk = trim($_POST['nama_produk']);
    $tipe = trim($_POST['tipe']);
    $kadar = trim($_POST['kadar']);
    $satuan = trim($_POST['satuan']);

    // 5. Validasi
    if (empty($nama_produk) || empty($tipe) || empty($kadar) || empty($satuan)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: katalog_baru.php");
        exit;
    }

    // 6. Siapkan Kueri SQL (Prepared Statement)
    $sql_insert = "INSERT INTO PRODUK_KATALOG (NamaProduk, Tipe, Kadar, Satuan)
                   VALUES (?, ?, ?, ?)";
    
    $stmt = $koneksi->prepare($sql_insert);
    
    // 7. Bind Parameter (s = string)
    $stmt->bind_param("ssss", $nama_produk, $tipe, $kadar, $satuan);
    
    // 8. Eksekusi
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Katalog produk '$nama_produk' berhasil ditambahkan.";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan katalog: " . $stmt->error;
    }

    $stmt->close();
    $koneksi->close();

} else {
    // Jika diakses langsung
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
}

// Redirect kembali ke halaman katalog utama
header("Location: katalog_produk.php");
exit;
?>