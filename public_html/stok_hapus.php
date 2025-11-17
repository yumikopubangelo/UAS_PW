<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Hanya Admin/Pemilik)
if (!isset($_SESSION['index']) || $_SESSION['index'] !== true) {
    $_SESSION['error_message'] = "Akses ditolak. Silakan login.";
    header("Location: index.php");
    exit;
}
$role = $_SESSION['Role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses untuk aksi ini.";
    header("Location: home.php");
    exit;
}

// 3. VALIDASI PARAMETER GET
// Kita mengambil 'kode' dari URL (contoh: stok_hapus.php?kode=STK-001)
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    $_SESSION['error_message'] = "Kode barang tidak valid atau tidak ditemukan.";
    header("Location: stok.php");
    exit;
}
$kode_barang_hapus = $_GET['kode'];

// 4. SIAPKAN KUERI SQL (Prepared Statement)
//
// PENTING: Kita tambahkan "AND Status = 'Tersedia'"
// Ini adalah PENGAMAN GANDA. Halaman stok.php Anda sudah menyembunyikan tombol
// hapus untuk barang 'Terjual'. Tapi jika ada yang mengakses URL ini manual,
// kueri ini akan MENCEGAH penghapusan barang yang sudah 'Terjual'
// (yang mungkin sudah terikat di tabel DETAIL_TRANSAKSI_BARANG).

$sql_delete = "DELETE FROM BARANG_STOK WHERE KodeBarang = ? AND Status = 'Tersedia'";

$stmt = $koneksi->prepare($sql_delete);

// 5. BIND PARAMETER (s = string)
$stmt->bind_param("s", $kode_barang_hapus);

// 6. EKSEKUSI
if ($stmt->execute()) {
    // Cek apakah ada baris yang benar-benar terhapus
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Stok barang ($kode_barang_hapus) berhasil dihapus.";
    } else {
        // Ini terjadi jika kodenya ada, tapi statusnya 'Terjual'
        $_SESSION['error_message'] = "Gagal menghapus: Barang ($kode_barang_hapus) tidak ditemukan atau statusnya sudah 'Terjual'.";
    }
} else {
     // Error database umum
     $_SESSION['error_message'] = "Gagal menghapus stok: " . $stmt->error;
}

// 7. TUTUP KONEKSI DAN REDIRECT
$stmt->close();
$koneksi->close();

// Kembalikan user ke halaman stok (di sana pesan sukses/error akan tampil)
header("Location: stok.php");
exit;
?>