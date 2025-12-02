<?php
// API untuk mencari barang di POS dan mengambil harga harian yang relevan
header('Content-Type: application/json');
session_start();
include 'koneksi.php';

// 1. KEAMANAN: Cek Login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$kode = $_GET['kode'] ?? '';
$tipe_transaksi = $_GET['tipe'] ?? 'penjualan';

if (empty($kode)) {
    echo json_encode(['error' => 'Kode barang tidak boleh kosong']);
    exit;
}

// 2. CARI BARANG DI STOK
$sql = "SELECT 
            b.BarangID, 
            b.KodeBarang, 
            b.BeratGram, 
            b.Status, 
            b.HargaBeliModal,
            p.NamaProduk, 
            p.Kadar, 
            p.Tipe
        FROM BARANG_STOK b
        JOIN PRODUK_KATALOG p ON b.ProdukKatalogID = p.ProdukKatalogID
        WHERE b.KodeBarang = ?";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $kode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Barang tidak ditemukan.']);
    $koneksi->close();
    exit;
}

$barang = $result->fetch_assoc();

// Cek Status Barang
if ($tipe_transaksi == 'penjualan' && $barang['Status'] != 'Tersedia') {
    echo json_encode(['error' => 'Barang ini statusnya ' . $barang['Status'] . ' (Tidak bisa dijual).']);
    $koneksi->close();
    exit;
}

// 3. LOGIKA PENCARIAN HARGA HARIAN (SISTEM ALIAS)
$kadar_barang = strtoupper(trim($barang['Kadar']));

// Fungsi Helper untuk Alias (Sama seperti di api_get_harga_rekomendasi.php)
function getPossibleKadars($input) {
    $groups = [
        ['24K', '999', 'LM', 'LOGAM MULIA'],
        ['23K', '958'],
        ['22K', '916', '900'],
        ['18K', '750', '17K', '700'],
        ['9K', '420', '375', '8K', '333', '10K', '416'], // Grup Emas Muda
        ['6K', '250', 'EMAS MUDA']
    ];

    foreach ($groups as $group) {
        if (in_array($input, $group)) {
            return $group;
        }
    }
    return [$input];
}

$search_kadars = getPossibleKadars($kadar_barang);
$kadar_placeholders = "'" . implode("','", $search_kadars) . "'";

// Cari harga hari ini berdasarkan salah satu alias yang cocok
$sql_harga = "SELECT HargaJualPerGram, HargaBeliPerGram 
              FROM RIWAYAT_HARGA 
              WHERE Kadar IN ($kadar_placeholders) AND Tanggal = CURDATE()
              ORDER BY HargaJualPerGram DESC LIMIT 1";

$res_harga = $koneksi->query($sql_harga);

$harga_jual_per_gram = 0;
$harga_beli_per_gram = 0;

if ($res_harga && $res_harga->num_rows > 0) {
    $data_harga = $res_harga->fetch_assoc();
    $harga_jual_per_gram = floatval($data_harga['HargaJualPerGram']);
    $harga_beli_per_gram = floatval($data_harga['HargaBeliPerGram']);
} else {
    // --- FALLBACK (JIKA HARGA SPESIFIK TIDAK ADA) ---
    // Coba hitung dari harga 24K
    $sql_24k = "SELECT HargaJualPerGram, HargaBeliPerGram FROM RIWAYAT_HARGA 
                WHERE (Kadar = '24K' OR Kadar = '999') AND Tanggal = CURDATE() LIMIT 1";
    $res_24k = $koneksi->query($sql_24k);
    
    if ($res_24k && $res_24k->num_rows > 0) {
        $data_24k = $res_24k->fetch_assoc();
        
        // Estimasi persentase kasar
        $persen = 0.5; // Default
        if (in_array($kadar_barang, ['9K', '8K', '375', '420', '10K', '416'])) $persen = 0.40;
        elseif (in_array($kadar_barang, ['6K', '250'])) $persen = 0.25;
        elseif (in_array($kadar_barang, ['17K', '700', '750', '18K'])) $persen = 0.75;
        
        $harga_jual_per_gram = floatval($data_24k['HargaJualPerGram']) * $persen;
        $harga_beli_per_gram = floatval($data_24k['HargaBeliPerGram']) * $persen;
    }
}

// 4. MENYUSUN RESPONSE
// Masukkan harga yang ditemukan ke dalam array barang
$barang['HargaJualPerGram'] = $harga_jual_per_gram;
$barang['HargaBeliPerGram'] = $harga_beli_per_gram;

// Tambahkan info tambahan untuk debugging di console browser
$barang['Debug_KadarAsli'] = $kadar_barang;
$barang['Debug_AliasDicari'] = $search_kadars;
$barang['Debug_HargaDitemukan'] = ($res_harga && $res_harga->num_rows > 0);

echo json_encode($barang);

$koneksi->close();
?>