<?php
header('Content-Type: application/json');

// API harga emas (GoldAPI)
$apiKey = 'goldapi-4o7hj1tmhlnygf3-io';  
$url = "https://www.goldapi.io/api/XAU/USD";

// Ambil data emas dari GoldAPI
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-access-token: $apiKey"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);



$data = json_decode($response, true);

// Validasi data dari API
if (!$data || !isset($data['price_gram_24k'])) {
    $normalizedData = [["jual" => 950000, "beli" => 930000]];
    echo json_encode($normalizedData);
    exit;
}

// === Ambil kurs USD → IDR ===
$rateData = @file_get_contents("https://www.floatrates.com/daily/usd.json");
if ($rateData) {
    $rateJSON = json_decode($rateData, true);
    $usd_to_idr = $rateJSON["idr"]["rate"] ?? 16000;
} else {
    $usd_to_idr = 16000; // fallback kalau API gagal
}

// === Konversi harga emas dari USD ke IDR ===
$hargaJualUSD = (float) $data['price_gram_24k'];
$hargaJualIDR = $hargaJualUSD * $usd_to_idr;

// Tambahkan margin 2% untuk harga beli
$margin = 0.02;  
$hargaBeliIDR = $hargaJualIDR * (1 - $margin);

// === Format hasil dan kirim JSON ===
$normalizedData = [[
    "jual" => round($hargaJualIDR),
    "beli" => round($hargaBeliIDR),
    "kurs" => round($usd_to_idr, 2) // tambahan info kurs biar jelas
]];

echo json_encode($normalizedData);
?>
