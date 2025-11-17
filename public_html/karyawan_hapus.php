<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Hanya Admin/Pemilik)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role']; 
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
    header("Location: home.php");
    exit;
}

// 3. AMBIL DATA DARI URL (GET)
$id_karyawan_hapus = $_GET['id'] ?? null;
$karyawan_id_login = $_SESSION['karyawan_id'];

// 4. VALIDASI
if (empty($id_karyawan_hapus)) {
    $_SESSION['error_message'] = "ID Karyawan tidak ditemukan.";
    header("Location: karyawan.php");
    exit;
}

// 5. KEAMANAN: Cek agar user tidak bisa menghapus diri sendiri
if ($id_karyawan_hapus == $karyawan_id_login) {
    $_SESSION['error_message'] = "Anda tidak dapat menghapus akun Anda sendiri!";
    header("Location: karyawan.php");
    exit;
}

// 6. PROSES HAPUS DATA
try {
    // Gunakan Prepared Statement untuk keamanan
    $sql = "DELETE FROM KARYAWAN WHERE KaryawanID = ?";
    $stmt = $koneksi->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Gagal mempersiapkan statement: " . $koneksi->error);
    }

    $stmt->bind_param("i", $id_karyawan_hapus);

    if ($stmt->execute()) {
        // Cek apakah ada baris yang benar-benar terhapus
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Karyawan berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Karyawan dengan ID tersebut tidak ditemukan.";
        }
    } else {
        throw new Exception("Gagal mengeksekusi penghapusan: " . $stmt->error);
    }
    
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    // Tangani error spesifik
    // Kode 1451: Foreign Key constraint fails (gagal hapus karena data terpakai)
    if ($e->getCode() == 1451) {
        $_SESSION['error_message'] = "Gagal menghapus! Karyawan ini sudah memiliki riwayat transaksi di sistem.";
    } else {
        $_SESSION['error_message'] = "Error Database: " . $e->getMessage();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

$koneksi->close();

// 7. KEMBALIKAN KE HALAMAN KARYAWAN
header("Location: karyawan.php");
exit;
?>