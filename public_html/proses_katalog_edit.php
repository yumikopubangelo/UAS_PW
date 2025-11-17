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
    $katalog_id = $_POST['produkkatalogid']; // ID dari input tersembunyi
    $nama_produk = trim($_POST['nama_produk']);
    $tipe = trim($_POST['tipe']);
    $kadar = trim($_POST['kadar']);
    $satuan = trim($_POST['satuan']);

    // 5. Validasi
    if (empty($katalog_id) || empty($nama_produk) || empty($tipe) || empty($kadar) || empty($satuan)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: katalog_edit.php?id=" . $katalog_id);
        exit;
    }

    // 6. Siapkan Kueri SQL (Prepared Statement)
    $sql_update = "UPDATE PRODUK_KATALOG SET 
                       NamaProduk = ?, 
                       Tipe = ?, 
                       Kadar = ?, 
                       Satuan = ?
                   WHERE 
                       ProdukKatalogID = ?";
    
    $stmt = $koneksi->prepare($sql_update);
    
    // 7. Bind Parameter (s = string, i = integer)
    $stmt->bind_param("ssssi", $nama_produk, $tipe, $kadar, $satuan, $katalog_id);
    
    // 8. Eksekusi
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Katalog produk '$nama_produk' berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui katalog: " . $stmt->error;
        // Jika gagal, kembalikan ke form edit
        $stmt->close();
        $koneksi->close();
        header("Location: katalog_edit.php?id=" . $katalog_id);
        exit;
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