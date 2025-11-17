<?php
session_start();
include 'koneksi.php'; // Hubungkan ke database

// 1. Keamanan: Cek apakah user sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login untuk melakukan aksi ini.";
    header("Location: index.php");
    exit;
}

// 2. Keamanan: Hanya proses jika via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Ambil data dari form (Gunakan nama kolom yang BENAR)
    $kadar = $_POST['kadar'];
    $harga_jual = $_POST['HargaJualPerGram']; // Sesuaikan dengan 'name' di form
    $harga_beli = $_POST['HargaBeliPerGram']; // Sesuaikan dengan 'name' di form

    // 4. Validasi Sederhana
    if (empty($kadar) || empty($harga_jual) || empty($harga_beli)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: harga.php");
        exit;
    }

    // 5. LOGIKA BARU UNTUK TABEL RIWAYAT
    // Cek apakah sudah ada data untuk KADAR ini & TANGGAL HARI INI
    
    $today = date("Y-m-d"); // Dapatkan tanggal hari ini (misal: 2025-11-07)
    
    $sql_check = "SELECT HargaID FROM RIWAYAT_HARGA 
                  WHERE Kadar = ? AND Tanggal = ?";
    
    $stmt_check = $koneksi->prepare($sql_check);
    $stmt_check->bind_param("ss", $kadar, $today);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // === SUDAH ADA: Lakukan UPDATE ===
        // Ambil ID baris yang ada
        $row = $result_check->fetch_assoc();
        $hargaID = $row['HargaID'];

        $sql_update = "UPDATE RIWAYAT_HARGA 
                       SET HargaJualPerGram = ?, HargaBeliPerGram = ? 
                       WHERE HargaID = ?";
        
        $stmt_update = $koneksi->prepare($sql_update);
        // "ddi" = decimal, decimal, integer
        $stmt_update->bind_param("ddi", $harga_jual, $harga_beli, $hargaID);
        
        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Harga $kadar hari ini berhasil di-UPDATE.";
        } else {
            $_SESSION['error_message'] = "Gagal meng-update harga: " . $koneksi->error;
        }
        $stmt_update->close();

    } else {
        // === BELUM ADA: Lakukan INSERT ===
        
        $sql_insert = "INSERT INTO RIWAYAT_HARGA 
                       (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) 
                       VALUES (?, ?, ?, ?)";
        
        $stmt_insert = $koneksi->prepare($sql_insert);
        // "ssdd" = string, string, decimal, decimal
        $stmt_insert->bind_param("ssdd", $today, $kadar, $harga_jual, $harga_beli);

        if ($stmt_insert->execute()) {
            $_SESSION['success_message'] = "Harga $kadar hari ini berhasil di-SIMPAN.";
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan harga: " . $koneksi->error;
        }
        $stmt_insert->close();
    }
    
    $stmt_check->close();
    $koneksi->close();

} else {
    // Jika diakses langsung, tendang
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
}

// 7. Kembalikan ke halaman harga.php
header("Location: harga.php");
exit;
?>