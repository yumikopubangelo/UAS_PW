<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Hanya Admin/Pemilik)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
    header("Location: home.php");
    exit;
}

// 3. VALIDASI GET PARAMETER
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID Katalog tidak valid.";
    header("Location: katalog_produk.php");
    exit;
}
$katalog_id = $_GET['id'];

// 4. AMBIL DATA KATALOG YANG AKAN DI-EDIT
$sql_item = "SELECT * FROM PRODUK_KATALOG WHERE ProdukKatalogID = ?";
$stmt_item = $koneksi->prepare($sql_item);
$stmt_item->bind_param("i", $katalog_id);
$stmt_item->execute();
$result_item = $stmt_item->get_result();

if ($result_item->num_rows == 0) {
    $_SESSION['error_message'] = "Data katalog tidak ditemukan.";
    $stmt_item->close();
    $koneksi->close();
    header("Location: katalog_produk.php");
    exit;
}
$item = $result_item->fetch_assoc();
$stmt_item->close();
$koneksi->close();

// Ambil data user
$nama_karyawan = $_SESSION['nama_karyawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : 'Admin';
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Katalog Produk</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-card { background-color: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); max-width: 600px; margin: 0 auto; }
        .form-card h3 { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .btn-primary { width: 100%; padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #007bff; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; text-align: center; font-size: 1rem; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { display: inline-block; text-align: center; width: 100%; box-sizing: border-box; padding: 0.75rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
             <div class="sidebar-header"><h3>Toko Emas UMKM</h3></div>
             <ul class="sidebar-menu">
                <li><a href="home.php">Dashboard</a></li>
                <li class="active"><a href="katalog_produk.php">Manajemen Katalog</a></li>
             </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Edit Katalog Produk</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3>Edit Detail: <?php echo htmlspecialchars($item['NamaProduk']); ?></h3>
                    <form action="proses_katalog_edit.php" method="POST">
                        
                        <input type="hidden" name="produkkatalogid" value="<?php echo $item['ProdukKatalogID']; ?>">
                        
                        <div class="form-group">
                            <label for="nama_produk">Nama Produk (Wajib)</label>
                            <input type="text" id="nama_produk" name="nama_produk" value="<?php echo htmlspecialchars($item['NamaProduk']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="tipe">Tipe (Wajib)</label>
                            <input type="text" id="tipe" name="tipe" value="<?php echo htmlspecialchars($item['Tipe']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="kadar">Kadar (Wajib)</label>
                            <input type="text" id="kadar" name="kadar" value="<?php echo htmlspecialchars($item['Kadar']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="satuan">Satuan (Wajib)</label>
                            <select id="satuan" name="satuan" required>
                                <option value="gr" <?php if ($item['Satuan'] == 'gr') echo 'selected'; ?>>Gram (gr)</option>
                                <option value="pcs" <?php if ($item['Satuan'] == 'pcs') echo 'selected'; ?>>Pcs (pcs)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        <a href="katalog_produk.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>