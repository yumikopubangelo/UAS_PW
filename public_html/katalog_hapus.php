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

// 3. VALIDASI PARAMETER GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID Katalog tidak valid.";
    header("Location: katalog_produk.php");
    exit;
}
$katalog_id = $_GET['id'];

// === PENGAMAN DATABASE (FOREIGN KEY CHECK) ===
// 4. Cek apakah katalog ini sedang dipakai di BARANG_STOK
$sql_check = "SELECT BarangID FROM BARANG_STOK WHERE ProdukKatalogID = ? LIMIT 1";
$stmt_check = $koneksi->prepare($sql_check);
$stmt_check->bind_param("i", $katalog_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // JIKA KATALOG SEDANG DIPAKAI: GAGALKAN HAPUS
    $_SESSION['error_message'] = "Gagal hapus: Katalog ini sedang digunakan oleh barang di stok. Hapus dulu semua barang stok yang terkait dengan katalog ini.";
    $stmt_check->close();
    $koneksi->close();
    header("Location: katalog_produk.php");
    exit;
}
$stmt_check->close();
// ============================================


// 5. JIKA AMAN: Lanjutkan proses hapus
$sql_delete = "DELETE FROM PRODUK_KATALOG WHERE ProdukKatalogID = ?";
$stmt = $koneksi->prepare($sql_delete);
$stmt->bind_param("i", $katalog_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Katalog produk berhasil dihapus.";
} else {
    // Error database umum (misal: foreign key lain)
    $_SESSION['error_message'] = "Gagal menghapus katalog: " . $stmt->error;
}

// 7. TUTUP KONEKSI DAN REDIRECT
$stmt->close();
$koneksi->close();

header("Location: katalog_produk.php");
exit;
?>