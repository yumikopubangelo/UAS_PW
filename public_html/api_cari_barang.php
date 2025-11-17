<?php
// DEBUG: Cek data yang diterima
file_put_contents('debug_api.txt', "GET DATA: " . print_r($_GET, true) . "\n", FILE_APPEND);

// (sisanya kode Anda)
session_start();
include 'koneksi.php';

// (sisanya kode Anda)

header('Content-Type: application/json');

// Keamanan: Cek login
if (!isset($_SESSION['index']) || $_SESSION['index'] !== true) {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Ambil data dari request JavaScript
$kode_barang = $_GET['kode'] ?? '';
$tipe_transaksi = $_GET['tipe'] ?? 'penjualan'; 

// ✅ INI YANG BENAR
// Ambil kode, default ke null jika tidak ada
$kode_barang = $_GET['kode'] ?? null; 

// Cek jika parameternya HILANG (null) ATAU jika nilainya adalah string KOSONG
// Ini akan memperbolehkan string "0" untuk lolos
if ($kode_barang === null || $kode_barang === '') {
    header('Content-Type: application/json');   
     echo json_encode(['error' => 'Kode barang tidak boleh kosong']);
     exit;
}

// === PERBAIKAN DI SINI ===
// 1. Dapatkan tanggal HARI INI menurut PHP (WIB), sama seperti di file lain
$today_php = date("Y-m-d"); 

// 2. Kueri JOIN yang canggih
// GANTI 'rh.Tanggal = CURDATE()' MENJADI 'rh.Tanggal = ?'
$sql = "SELECT 
            bs.BarangID, 
            bs.KodeBarang,
            bs.BeratGram,
            bs.HargaBeliModal,
            pk.NamaProduk, 
            pk.Kadar, 
            pk.Tipe,
            rh.HargaJualPerGram,
            rh.HargaBeliPerGram
        FROM BARANG_STOK AS bs
        JOIN PRODUK_KATALOG AS pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
        LEFT JOIN RIWAYAT_HARGA AS rh ON pk.Kadar = rh.Kadar AND rh.Tanggal = ?
        WHERE bs.KodeBarang = ? AND bs.Status = 'Tersedia'";

$stmt = $koneksi->prepare($sql);
// 3. Bind 2 parameter: tanggal (s) dan kode (s)
$stmt->bind_param("ss", $today_php, $kode_barang); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    
    // Kirim data lengkap
    echo json_encode($item);
} else {
    // Tidak ditemukan, kirim error
    echo json_encode(['error' => 'Barang tidak ditemukan atau sudah terjual.']);
}

$stmt->close();
$koneksi->close();
exit;
?>