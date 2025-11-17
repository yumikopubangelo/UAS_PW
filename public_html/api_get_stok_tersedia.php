<?php
// File: api_get_stok_tersedia.php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

// Keamanan: Cek login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Kueri JOIN untuk mengambil semua info barang yang TERSEDIA
$sql = "SELECT 
            bs.BarangID, 
            bs.KodeBarang, 
            bs.BeratGram,
            pk.NamaProduk, 
            pk.Kadar, 
            pk.Tipe
        FROM BARANG_STOK AS bs
        JOIN PRODUK_KATALOG AS pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
        WHERE bs.Status = 'Tersedia'
        ORDER BY pk.NamaProduk ASC, bs.BeratGram ASC";

$stmt = $koneksi->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$items = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode($items); // Kirim semua data barang sebagai array JSON

$stmt->close();
$koneksi->close();
exit;
?>