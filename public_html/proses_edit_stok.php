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

// 3. Pastikan form disubmit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 4. Ambil data dari form
    $barang_id = $_POST['barang_id']; // Ini adalah Primary Key (INT)
    
    $produk_katalog_id = $_POST['produk_katalog_id'];
    $kode_barang = trim($_POST['kode_barang']);
    $berat_gram = $_POST['berat_gram'];
    $harga_beli_modal = $_POST['harga_beli_modal'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $asal_barang = $_POST['asal_barang'];
    
    // Handle SupplierID (bisa jadi NULL/kosong)
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : NULL;
    
    // 5. Validasi
    if (empty($barang_id) || empty($produk_katalog_id) || empty($kode_barang) || empty($berat_gram) || empty($harga_beli_modal) || empty($tanggal_masuk)) {
        $_SESSION['error_message'] = "Semua field yang wajib diisi (Katalog, Kode, Berat, Modal, Tanggal) harus diisi.";
        header("Location: stok_edit.php?kode=" . $kode_barang); // Kembali ke form edit
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
                       BarangID = ?"; // Update berdasarkan Primary Key
    
    $stmt = $koneksi->prepare($sql_update);
    
    // 7. Bind Parameter
    // i = integer, s = string, d = double/decimal
    if ($supplier_id === NULL) {
        $stmt->bind_param("isddsssi", $produk_katalog_id, $supplier_id, $kode_barang, $berat_gram, $harga_beli_modal, $tanggal_masuk, $asal_barang, $barang_id);
    } else {
        $stmt->bind_param("iisddssi", $produk_katalog_id, $supplier_id, $kode_barang, $berat_gram, $harga_beli_modal, $tanggal_masuk, $asal_barang, $barang_id);
    }
    
    // 8. Eksekusi
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Stok ($kode_barang) berhasil diperbarui.";
    } else {
        // Cek jika error karena KodeBarang duplikat
        if ($koneksi->errno == 1062) { // 1062 = Error duplikat
             $_SESSION['error_message'] = "Gagal: Kode Barang '$kode_barang' sudah ada di database.";
        } else {
             $_SESSION['error_message'] = "Gagal memperbarui stok: " . $stmt->error;
        }
        
        // Jika gagal, kembalikan ke halaman edit, BUKAN ke stok.php
        $stmt->close();
        $koneksi->close();
        header("Location: stok_edit.php?kode=" . $kode_barang); // Kembali ke form edit
        exit;
    }

    $stmt->close();
    $koneksi->close();

} else {
    // Jika diakses langsung
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
}

// Redirect kembali ke halaman stok utama
header("Location: stok.php");
exit;
?>