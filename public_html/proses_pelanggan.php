<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role
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

// Redirect URL
$redirect_url = 'pelanggan.php';

// Tentukan aksi (Create, Update, atau Delete)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Logika CREATE dan UPDATE (dari Form POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action == 'create' || $action == 'update')) {
    
    // Ambil data utama
    $pelanggan_id = $_POST['pelanggan_id'] ?? null; // Hanya ada saat UPDATE
    $nama_pelanggan = trim($_POST['nama_pelanggan']);
    $no_hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    // 3. Validasi Server-Side
    if (empty($nama_pelanggan) || empty($no_hp)) {
        $_SESSION['error_message'] = "Nama Pelanggan dan Nomor HP wajib diisi.";
        $back_url = ($action == 'update' && $pelanggan_id) ? "pelanggan_edit.php?id=" . $pelanggan_id : "pelanggan_baru.php";
        header("Location: " . $back_url);
        exit;
    }

    if ($action == 'create') {
        // === CREATE (INSERT) ===
        $sql = "INSERT INTO PELANGGAN (NamaPelanggan, NoHP, Alamat) VALUES (?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sss", $nama_pelanggan, $no_hp, $alamat);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Pelanggan baru ($nama_pelanggan) berhasil ditambahkan.";
        } else {
             if ($koneksi->errno == 1062) { // Kode error MySQL untuk UNIQUE constraint
                $_SESSION['error_message'] = "Gagal: Nomor HP ($no_hp) sudah terdaftar.";
            } else {
                $_SESSION['error_message'] = "Gagal menyimpan data: " . $stmt->error;
            }
        }
        $stmt->close();

    } elseif ($action == 'update') {
        // === UPDATE ===
        $sql = "UPDATE PELANGGAN SET NamaPelanggan = ?, NoHP = ?, Alamat = ? WHERE PelangganID = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssi", $nama_pelanggan, $no_hp, $alamat, $pelanggan_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Data pelanggan ($nama_pelanggan) berhasil diperbarui.";
        } else {
            if ($koneksi->errno == 1062) {
                $_SESSION['error_message'] = "Gagal: Nomor HP ($no_hp) sudah terdaftar pada pelanggan lain.";
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui data: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// --- Logika DELETE (dari Link GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action == 'delete') {
    $pelanggan_id = $_GET['id'] ?? null;

    if ($pelanggan_id) {
        // PENGAMAN: Cek apakah pelanggan memiliki transaksi terkait
        $sql_check = "SELECT TransaksiID FROM TRANSAKSI WHERE PelangganID = ? LIMIT 1";
        $stmt_check = $koneksi->prepare($sql_check);
        $stmt_check->bind_param("i", $pelanggan_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['error_message'] = "Gagal hapus: Pelanggan ini memiliki riwayat transaksi dan tidak dapat dihapus.";
        } else {
            // HAPUS (DELETE)
            $sql = "DELETE FROM PELANGGAN WHERE PelangganID = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("i", $pelanggan_id);

            if ($stmt->execute()) {
                 $_SESSION['success_message'] = "Data pelanggan berhasil dihapus.";
            } else {
                $_SESSION['error_message'] = "Gagal menghapus data: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
         $_SESSION['error_message'] = "ID pelanggan untuk dihapus tidak ditemukan.";
    }
}

$koneksi->close();

// 5. REDIRECT AKHIR
header("Location: " . $redirect_url);
exit;
?>