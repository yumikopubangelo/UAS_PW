<?php
session_start();
include 'koneksi.php';

// Validasi login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// Validasi role
$role = $_SESSION['role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses.";
    header("Location: home.php");
    exit;
}

// Ambil data dari form
$produk_katalog_id = $_POST['produk_katalog_id'] ?? null;
$berat_gram = $_POST['berat_gram'] ?? null;
$harga_beli_modal = $_POST['harga_beli_modal'] ?? null;
$tanggal_masuk = $_POST['tanggal_masuk'] ?? null;
$asal_barang = $_POST['asal_barang'] ?? 'Supplier';
$supplier_id = $_POST['supplier_id'] ?? null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Bersihkan input harga dari karakter non-numerik (jaga-jaga)
$harga_beli_modal = preg_replace('/[^0-9.]/', '', $harga_beli_modal);
$harga_beli_modal = floatval($harga_beli_modal);

// Validasi input wajib
if (!$produk_katalog_id || !$berat_gram || !$harga_beli_modal || !$tanggal_masuk || $quantity < 1) {
    $_SESSION['error_message'] = "Semua field wajib harus diisi dengan benar!";
    header("Location: stok_baru.php");
    exit;
}

// Validasi nilai numerik
if ($berat_gram <= 0 || $harga_beli_modal <= 0) {
    $_SESSION['error_message'] = "Berat dan harga modal harus berupa angka positif yang valid!";
    header("Location: stok_baru.php");
    exit;
}

// Validasi harga wajar (opsional, sesuaikan batas)
if ($harga_beli_modal > 1000000000) { // Lebih dari 1 miliar
    $_SESSION['error_message'] = "Harga modal terlalu besar. Periksa kembali input Anda!";
    header("Location: stok_baru.php");
    exit;
}

// Handle supplier_id kosong
if (empty($supplier_id)) {
    $supplier_id = null;
}

// Mulai transaksi database
$koneksi->begin_transaction();

try {
    // Siapkan query INSERT
    $sql = "INSERT INTO BARANG_STOK 
            (ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, AsalBarang, Status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Tersedia')";
    
    $stmt = $koneksi->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query: " . $koneksi->error);
    }

    // Array untuk menyimpan kode barang yang berhasil dibuat
    $kode_barang_list = [];
    $success_count = 0;

    // Loop untuk insert sebanyak quantity
    for ($i = 0; $i < $quantity; $i++) {
        // Bind parameter dan execute
        $stmt->bind_param(
            "iiddss",
            $produk_katalog_id,
            $supplier_id,
            $berat_gram,
            $harga_beli_modal,
            $tanggal_masuk,
            $asal_barang
        );

        if ($stmt->execute()) {
            $last_id = $koneksi->insert_id;
            
            // Ambil kode barang yang baru dibuat (dari trigger)
            $sql_get_kode = "SELECT KodeBarang FROM BARANG_STOK WHERE BarangID = ?";
            $stmt_kode = $koneksi->prepare($sql_get_kode);
            $stmt_kode->bind_param("i", $last_id);
            $stmt_kode->execute();
            $result_kode = $stmt_kode->get_result();
            
            if ($row_kode = $result_kode->fetch_assoc()) {
                $kode_barang_list[] = $row_kode['KodeBarang'];
            }
            $stmt_kode->close();
            
            $success_count++;
        } else {
            throw new Exception("Gagal insert item ke-" . ($i + 1) . ": " . $stmt->error);
        }
    }

    $stmt->close();

    // Commit transaksi
    $koneksi->commit();

    // Ambil info produk untuk pesan sukses
    $sql_produk = "SELECT NamaProduk, Tipe, Kadar FROM PRODUK_KATALOG WHERE ProdukKatalogID = ?";
    $stmt_produk = $koneksi->prepare($sql_produk);
    $stmt_produk->bind_param("i", $produk_katalog_id);
    $stmt_produk->execute();
    $result_produk = $stmt_produk->get_result();
    $produk_info = $result_produk->fetch_assoc();
    $stmt_produk->close();

    // Format pesan sukses
    $total_berat = $berat_gram * $quantity;
    $total_modal = $harga_beli_modal * $quantity;
    
    $kode_display = count($kode_barang_list) > 3 
        ? implode(', ', array_slice($kode_barang_list, 0, 3)) . ', ...' 
        : implode(', ', $kode_barang_list);

    $_SESSION['success_message'] = 
        "Berhasil menambahkan {$success_count} item stok baru. " .
        "Produk: {$produk_info['NamaProduk']} ({$produk_info['Tipe']} - {$produk_info['Kadar']}). " .
        "Total Berat: " . number_format($total_berat, 2, ',', '.') . " gram. " .
        "Total Modal: Rp " . number_format($total_modal, 0, ',', '.') . ". " .
        "Kode Barang: {$kode_display}";

    $koneksi->close();
    header("Location: stok.php");
    exit;

} catch (Exception $e) {
    // Rollback jika terjadi error
    $koneksi->rollback();
    $_SESSION['error_message'] = "Gagal menambahkan stok: " . $e->getMessage();
    $koneksi->close();
    header("Location: stok_baru.php");
    exit;
}
?>