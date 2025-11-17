<?php
// File: api_cari_pelanggan.php
session_start();
include 'koneksi.php';

// Keamanan: Cek login
if (!isset($_SESSION['index']) || $_SESSION['index'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Ambil no_hp dari request JavaScript
$no_hp = $_GET['no_hp'] ?? '';

if (empty($no_hp)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No HP tidak boleh kosong']);
    exit;
}

// Cari pelanggan berdasarkan NoHP
$sql = "SELECT PelangganID, NamaPelanggan FROM PELANGGAN WHERE NoHP = ?";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $no_hp);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');

if ($result->num_rows > 0) {
    $pelanggan = $result->fetch_assoc();
    echo json_encode($pelanggan); // Kirim data pelanggan
} else {
    echo json_encode(['error' => 'Pelanggan tidak ditemukan.']);
}

$stmt->close();
$koneksi->close();
exit;
?>