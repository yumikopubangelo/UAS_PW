<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Sesuai standar baru: is_logged_in & role)
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
    $barang_id = $_POST['barang_id']; // Primary Key
    
    $produk_katalog_id = $_POST['produk_katalog_id'];
    $kode_barang = trim($_POST['kode_barang']);
    $berat_gram = $_POST['berat_gram'];
    $harga_beli_modal = $_POST['harga_beli_modal'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $asal_barang = $_POST['asal_barang'];
    
    // Handle SupplierID (bisa jadi NULL)
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : NULL;
    
    // 5. Validasi
    if (empty($barang_id) || empty($produk_katalog_id) || empty($kode_barang) || empty($berat_gram) || empty($harga_beli_modal) || empty($tanggal_masuk)) {
        $_SESSION['error_message'] = "Semua field yang wajib diisi harus diisi.";
        $redirect = !empty($kode_barang) ? "stok_edit.php?kode=" . $kode_barang : "stok.php";
        header("Location: " . $redirect);
        exit;
    }

    // 6. Siapkan Kueri SQL (Prepared Statement)
    $sql_update = "UPDATE BARANG_STOK SET 
                        ProdukKatalogID = ?, 
                        SupplierID = ?, 
                        KodeBarang = ?, 
                        BeratGram = ?, 
                        HargaBeliModal = ?, 
                        TanggalMasuk = ?, 
                        AsalBarang = ?
                    WHERE 
                        BarangID = ?"; 
    
    $stmt = $koneksi->prepare($sql_update);
    
    if (!$stmt) {
        $_SESSION['error_message'] = "Gagal prepare statement: " . $koneksi->error;
        header("Location: stok.php");
        exit;
    }

    // 7. Bind Parameter (PERBAIKAN UTAMA DI SINI)
    // Urutan Tipe Data yang BENAR:
    // i (ProdukID)
    // i (SupplierID)
    // s (KodeBarang) -> WAJIB 's', kalau 'd' jadi 0 dan error duplicate!
    // d (Berat)
    // d (Harga)
    // s (Tanggal)
    // s (Asal)
    // i (BarangID)
    
    $tipe_data = "iisddssi"; // Default (jika supplier ada)

    if ($supplier_id === NULL) {
        // Jika supplier NULL, kita tetap pakai 'i' atau 's' tidak masalah, tapi pastikan urutannya benar
        // Tips: bind_param bisa menerima null jika variabelnya null
        $stmt->bind_param("iisddssi", 
            $produk_katalog_id, 
            $supplier_id, 
            $kode_barang,      // s (string)
            $berat_gram,       // d (double)
            $harga_beli_modal, // d (double)
            $tanggal_masuk,    // s (string)
            $asal_barang,      // s (string)
            $barang_id         // i (integer)
        );
    } else {
        $stmt->bind_param("iisddssi", 
            $produk_katalog_id, 
            $supplier_id, 
            $kode_barang, 
            $berat_gram, 
            $harga_beli_modal, 
            $tanggal_masuk, 
            $asal_barang, 
            $barang_id
        );
    }
    
    // 8. Eksekusi
    try {
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Stok ($kode_barang) berhasil diperbarui.";
            header("Location: stok.php");
            exit;
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        // Cek jika error karena KodeBarang duplikat
        if ($koneksi->errno == 1062) { 
             $_SESSION['error_message'] = "Gagal: Kode Barang '$kode_barang' sudah digunakan oleh barang lain.";
        } else {
             $_SESSION['error_message'] = "Gagal memperbarui stok: " . $e->getMessage();
        }
        
        header("Location: stok_edit.php?kode=" . $kode_barang);
        exit;
    } finally {
        $stmt->close();
        $koneksi->close();
    }

} else {
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
    header("Location: stok.php");
    exit;
}
?>