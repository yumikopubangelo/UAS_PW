<?php
// 1. MULAI SESSION & KEAMANAN
session_start();
include 'koneksi.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Akses ditolak. Silakan login.";
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
    header("Location: home.php");
    exit;
}

// 2. AMBIL DAN VALIDASI ID
$pelanggan_id = $_GET['id'] ?? null;
if (!$pelanggan_id) {
    $_SESSION['error_message'] = "ID pelanggan tidak ditemukan.";
    header("Location: pelanggan.php");
    exit;
}

// 3. AMBIL DATA LAMA
$sql_item = "SELECT PelangganID, NamaPelanggan, NoHP, Alamat FROM PELANGGAN WHERE PelangganID = ?";
$stmt_item = $koneksi->prepare($sql_item);
$stmt_item->bind_param("i", $pelanggan_id);
$stmt_item->execute();
$result_item = $stmt_item->get_result();

if ($result_item->num_rows == 0) {
    $_SESSION['error_message'] = "Data pelanggan tidak ditemukan.";
    $stmt_item->close();
    $koneksi->close();
    header("Location: pelanggan.php");
    exit;
}
$item = $result_item->fetch_assoc();
$stmt_item->close();
$koneksi->close();

$nama_karyawan = $_SESSION['nama_karyawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : 'Admin';
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pelanggan</title>
    <link rel="stylesheet" href="dashboard.css">
   <style>
    .form-card { 
        background-color: #fff; 
        padding: 1.5rem; 
        border-radius: 10px; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.08); 
        max-width: 600px; 
        margin: 0 auto; 
    }
    .form-card h3 { 
        margin-top: 0; 
        border-bottom: 2px solid #f0f0f0; 
        padding-bottom: 1rem; 
    }
    .form-group { 
        margin-bottom: 1rem; 
    }
    .form-group label { 
        display: block; 
        margin-bottom: 0.5rem; 
        font-weight: 500; 
    }
    .form-group input { 
        width: 100%; 
        padding: 0.75rem; 
        border: 1px solid #ccc; 
        border-radius: 6px; 
        box-sizing: border-box; 
    }
    
    /* === BLOK PERBAIKAN TOMBOL === */
    .btn-primary {
        width: 100%;
        padding: 0.75rem 1.2rem;
        border: none;
        border-radius: 6px;
        background-color: #007bff;
        color: white;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease; /* Efek hover halus */
        text-align: center;
        font-size: 1rem; /* Ukuran font yang konsisten */
        box-sizing: border-box;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    
    .btn-secondary {
        display: block;
        text-align: center;
        width: 100%;
        padding: 0.75rem;
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        margin-top: 1rem;
        box-sizing: border-box;
        transition: background-color 0.2s ease; /* Efek hover halus */
    }
    .btn-secondary:hover {
        background-color: #5a6268;
    }
    /* === AKHIR BLOK PERBAIKAN === */
</style>
</head>
<body>

    <div class="dashboard-container">
        <nav class="sidebar">
             <div class="sidebar-header"><h3>Toko Emas UMKM</h3></div>
             <ul class="sidebar-menu">
                <li><a href="home.php">Dashboard</a></li>
                <li class="active-sub"><a href="pelanggan.php">Data Pelanggan</a></li>
             </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Edit Pelanggan</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3>Edit Detail: <?php echo htmlspecialchars($item['NamaPelanggan']); ?></h3>
                    <form action="proses_pelanggan.php" method="POST">
                        <input type="hidden" name="action" value="update"> 
                        <input type="hidden" name="pelanggan_id" value="<?php echo htmlspecialchars($item['PelangganID']); ?>"> 
                        
                        <div class="form-group">
                            <label for="nama_pelanggan">Nama Pelanggan (Wajib)</label>
                            <input type="text" id="nama_pelanggan" name="nama_pelanggan" value="<?php echo htmlspecialchars($item['NamaPelanggan']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_hp">Nomor HP (Wajib, Unique)</label>
                            <input type="text" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($item['NoHP']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat">Alamat (Opsional)</label>
                            <input type="text" id="alamat" name="alamat" value="<?php echo htmlspecialchars($item['Alamat']); ?>" placeholder="Jalan, No. Rumah, Kota...">
                        </div>
                        
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        <a href="pelanggan.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>