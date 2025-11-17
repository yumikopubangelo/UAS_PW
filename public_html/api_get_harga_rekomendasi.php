<?php
// API untuk mendapatkan harga rekomendasi berdasarkan kadar dan asal barang
header('Content-Type: application/json');
session_start();
include 'koneksi.php';

// Validasi parameter
if (!isset($_GET['kadar']) || !isset($_GET['asal'])) {
    echo json_encode(['error' => 'Parameter kadar dan asal wajib diisi']);
    exit;
}

$kadar = trim($_GET['kadar']);
$asal = trim($_GET['asal']);

// Mapping kadar ke nilai kemurnian (per 1000)
$kadar_mapping = [
    '24K' => 999,   // 99.9%
    '23K' => 958,   // 95.8%
    '22K' => 916,   // 91.6%
    '21K' => 875,   // 87.5%
    '20K' => 833,   // 83.3%
    '18K' => 750,   // 75%
    '17K' => 708,   // 70.8%
    '16K' => 666,   // 66.6%
    '14K' => 583,   // 58.3%
    '10K' => 416,   // 41.6%
    '9K' => 375,    // 37.5%
    '8K' => 333,    // 33.3%
    'Emas Muda' => 500, // 50% (sesuai fn_KonversiKadarKePersen)
];

// Cek apakah kadar valid
if (!isset($kadar_mapping[$kadar])) {
    echo json_encode(['error' => 'Kadar tidak valid: ' . $kadar]);
    exit;
}

$nilai_kemurnian = $kadar_mapping[$kadar];

// Ambil harga emas 24K terbaru dari tabel RIWAYAT_HARGA
// Menggunakan HargaBeliPerGram sebagai acuan harga modal
$sql = "SELECT HargaBeliPerGram FROM RIWAYAT_HARGA 
        WHERE (Kadar = '24K' OR Kadar = '999') AND Tanggal = CURDATE() 
        ORDER BY HargaID DESC LIMIT 1";
$result = $koneksi->query($sql);

if (!$result || $result->num_rows == 0) {
    echo json_encode([
        'error' => 'Harga emas 24K hari ini belum diinput. Silakan update di menu Harga Harian.',
        'hint' => 'Gunakan menu "Update Harga Harian" untuk input harga emas hari ini'
    ]);
    exit;
}

$row = $result->fetch_assoc();
$harga_emas_24k = floatval($row['HargaBeliPerGram']);

// Hitung harga per gram sesuai kadar
// Formula: Harga Kadar = (Kemurnian/1000) × Harga 24K
$harga_per_gram_kadar = ($nilai_kemurnian / 1000) * $harga_emas_24k;

// Faktor penyesuaian berdasarkan asal barang
$faktor = 1.0;
$keterangan_faktor = '';

switch ($asal) {
    case 'Supplier':
        $faktor = 1.0; // 100% dari harga pasar
        $keterangan_faktor = 'Harga penuh (barang baru)';
        break;
    case 'BuyBack':
    case 'Buyback':
        $faktor = 0.85; // 85% (diskon karena barang bekas)
        $keterangan_faktor = 'Diskon 15% (barang bekas)';
        break;
    case 'Produksi':
        $faktor = 0.90; // 90% (biaya produksi lebih rendah)
        $keterangan_faktor = 'Biaya produksi 90%';
        break;
    default:
        $faktor = 1.0;
        $keterangan_faktor = 'Harga standar';
}

$harga_rekomendasi_per_gram = $harga_per_gram_kadar * $faktor;

// Kembalikan hasil
echo json_encode([
    'success' => true,
    'kadar' => $kadar,
    'nilai_kemurnian' => $nilai_kemurnian,
    'persen_kemurnian' => ($nilai_kemurnian / 10), // untuk tampilan %
    'asal_barang' => $asal,
    'harga_emas_24k' => $harga_emas_24k,
    'harga_per_gram' => round($harga_rekomendasi_per_gram, 0),
    'faktor_penyesuaian' => $faktor,
    'keterangan_faktor' => $keterangan_faktor
]);

$koneksi->close();
?>