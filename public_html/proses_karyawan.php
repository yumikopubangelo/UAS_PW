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
$logged_in_karyawanID = $_SESSION['KaryawanID']; // ID user yang sedang login

if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses untuk aksi ini.";
    header("Location: home.php");
    exit;
}

// Redirect URL
$redirect_url = 'karyawan.php';

// Tentukan aksi (Update atau Delete)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Logika UPDATE (dari Form POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action == 'update') {
    
    // Ambil data utama
    $karyawan_id = $_POST['karyawan_id'] ?? null;
    $nama_karyawan = trim($_POST['nama_karyawan']);
    $username = trim($_POST['username']);
    $new_role = trim($_POST['role']);
    $password = $_POST['password']; // Jangan di-trim

    // 3. Validasi Server-Side
    if (empty($karyawan_id) || empty($nama_karyawan) || empty($username) || empty($new_role)) {
        $_SESSION['error_message'] = "Nama, Username, dan Role wajib diisi.";
        header("Location: karyawan_edit.php?id=" . $karyawan_id);
        exit;
    }
    
    // 4. Logika Update
    try {
        if (!empty($password)) {
            // === KASUS 1: UPDATE SEMUA + PASSWORD BARU ===
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE KARYAWAN SET NamaKaryawan = ?, Username = ?, Role = ?, Password = ? WHERE KaryawanID = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("ssssi", $nama_karyawan, $username, $new_role, $hashed_password, $karyawan_id);
        
        } else {
            // === KASUS 2: UPDATE DATA SAJA (Tanpa Password) ===
            $sql = "UPDATE KARYAWAN SET NamaKaryawan = ?, Username = ?, Role = ? WHERE KaryawanID = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("sssi", $nama_karyawan, $username, $new_role, $karyawan_id);
        }

        // Eksekusi
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Data karyawan ($nama_karyawan) berhasil diperbarui.";
        } else {
            throw new Exception($koneksi->error, $koneksi->errno);
        }
        $stmt->close();
    
    } catch (Exception $e) {
        if ($e->getCode() == 1062) { // Kode error MySQL untuk UNIQUE constraint
            $_SESSION['error_message'] = "Gagal: Username ($username) sudah terdaftar.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data: " . $e->getMessage();
        }
        // Kembalikan ke form edit jika gagal
        $koneksi->close();
        header("Location: karyawan_edit.php?id=" . $karyawan_id);
        exit;
    }
}

// --- Logika DELETE (dari Link GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action == 'delete') {
    $karyawan_id = $_GET['id'] ?? null;

    if ($karyawan_id) {
        
        // PENGAMAN: Cek agar user tidak bisa hapus diri sendiri
        if ($karyawan_id == $logged_in_karyawanID) {
            $_SESSION['error_message'] = "Gagal: Anda tidak dapat menghapus akun Anda sendiri.";
        
        } else {
            // PENGAMAN: Cek apakah karyawan memiliki transaksi terkait
            $sql_check = "SELECT TransaksiID FROM TRANSAKSI WHERE KaryawanID = ? LIMIT 1";
            $stmt_check = $koneksi->prepare($sql_check);
            $stmt_check->bind_param("i", $karyawan_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $_SESSION['error_message'] = "Gagal hapus: Karyawan ini memiliki riwayat transaksi dan tidak dapat dihapus.";
            } else {
                // HAPUS (DELETE)
                $sql = "DELETE FROM KARYAWAN WHERE KaryawanID = ?";
                $stmt = $koneksi->prepare($sql);
                $stmt->bind_param("i", $karyawan_id);

                if ($stmt->execute()) {
                     $_SESSION['success_message'] = "Data karyawan berhasil dihapus.";
                } else {
                    $_SESSION['error_message'] = "Gagal menghapus data: " . $stmt->error;
                }
                $stmt->close();
            }
            $stmt_check->close();
        }
    } else {
         $_SESSION['error_message'] = "ID karyawan untuk dihapus tidak ditemukan.";
    }
}

$koneksi->close();

// 5. REDIRECT AKHIR
header("Location: " . $redirect_url);
exit;
?>