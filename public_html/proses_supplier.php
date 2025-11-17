<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role
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

// Redirect URL
$redirect_url = 'supplier.php';

// Tentukan aksi (Create, Update, atau Delete)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- Logika CREATE dan UPDATE (dari Form POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action == 'create' || $action == 'update')) {
    
    // Ambil data utama
    $supplier_id = $_POST['supplier_id'] ?? null; // Hanya ada saat UPDATE
    $nama_supplier = trim($_POST['nama_supplier']);
    $kontak = trim($_POST['kontak']);
    $alamat = trim($_POST['alamat']);

    // 3. Validasi Server-Side
    if (empty($nama_supplier)) {
        $_SESSION['error_message'] = "Nama Supplier wajib diisi.";
        $back_url = ($action == 'update' && $supplier_id) ? "supplier_edit.php?id=" . $supplier_id : "supplier_baru.php";
        header("Location: " . $back_url);
        exit;
    }
    
    // Set field opsional ke NULL jika kosong
    $kontak_db = !empty($kontak) ? $kontak : NULL;
    $alamat_db = !empty($alamat) ? $alamat : NULL;


    if ($action == 'create') {
        // === CREATE (INSERT) ===
        $sql = "INSERT INTO SUPPLIER (NamaSupplier, Kontak, Alamat) VALUES (?, ?, ?)";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sss", $nama_supplier, $kontak_db, $alamat_db);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Supplier baru ($nama_supplier) berhasil ditambahkan.";
        } else {
             $_SESSION['error_message'] = "Gagal menyimpan data: " . $stmt->error;
        }
        $stmt->close();

    } elseif ($action == 'update') {
        // === UPDATE ===
        $sql = "UPDATE SUPPLIER SET NamaSupplier = ?, Kontak = ?, Alamat = ? WHERE SupplierID = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("sssi", $nama_supplier, $kontak_db, $alamat_db, $supplier_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Data supplier ($nama_supplier) berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui data: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Logika DELETE (dari Link GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action == 'delete') {
    $supplier_id = $_GET['id'] ?? null;

    if ($supplier_id) {
        // PENGAMAN: Cek apakah supplier ini sedang dipakai di BARANG_STOK
        $sql_check = "SELECT BarangID FROM BARANG_STOK WHERE SupplierID = ? LIMIT 1";
        $stmt_check = $koneksi->prepare($sql_check);
        $stmt_check->bind_param("i", $supplier_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // JIKA DIPAKAI: GAGALKAN HAPUS
            $_SESSION['error_message'] = "Gagal hapus: Supplier ini terikat pada data di BARANG_STOK. Hapus/ubah dulu data stok yang terkait.";
        } else {
            // HAPUS (DELETE)
            $sql = "DELETE FROM SUPPLIER WHERE SupplierID = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("i", $supplier_id);

            if ($stmt->execute()) {
                 $_SESSION['success_message'] = "Data supplier berhasil dihapus.";
            } else {
                $_SESSION['error_message'] = "Gagal menghapus data: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
         $_SESSION['error_message'] = "ID supplier untuk dihapus tidak ditemukan.";
    }
}

$koneksi->close();

// 5. REDIRECT AKHIR
header("Location: " . $redirect_url);
exit;
?>