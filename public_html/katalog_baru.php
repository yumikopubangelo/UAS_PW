<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Menggunakan nama session yang BENAR)
// PERBAIKAN: Cek 'is_logged_in', bukan 'index'
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// PERBAIKAN: Cek 'role', bukan 'Role'
$role = $_SESSION['role']; 
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
    header("Location: home.php");
    exit;
}
$is_admin_or_pemilik = true;

// 3. AMBIL DATA UNTUK DROPDOWN
$katalog_list = [];
$supplier_list = [];

// Ambil data Katalog
$sql_katalog = "SELECT ProdukKatalogID, NamaProduk, Tipe, Kadar FROM PRODUK_KATALOG ORDER BY NamaProduk ASC";
$result_katalog = $koneksi->query($sql_katalog);
if ($result_katalog && $result_katalog->num_rows > 0) { // Cek $result
    while ($row = $result_katalog->fetch_assoc()) {
        $katalog_list[] = $row;
    }
}

// Ambil data Supplier
$sql_supplier = "SELECT SupplierID, NamaSupplier FROM SUPPLIER ORDER BY NamaSupplier ASC";
$result_supplier = $koneksi->query($sql_supplier);
if ($result_supplier && $result_supplier->num_rows > 0) { // Cek $result
    while ($row = $result_supplier->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

$koneksi->close();

// 3. AMBIL DATA USER
// PERBAIKAN: Ambil 'nama_karyawan', bukan 'NamaKaryawan'
$nama_karyawan = $_SESSION['nama_karyawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : ucfirst($role);
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Katalog Produk Baru</title>
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
                <li><a href="transaksi_baru.php">Transaksi Baru (POS)</a></li>
                <li><a href="stok.php">Manajemen Stok</a></li>
                <?php if ($is_admin_or_pemilik): ?>
                    <li><a href="laporan.php">Laporan Penjualan</a></li>
                    <li>
                        <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                        <!-- Tandai halaman induk (katalog_produk.php) sebagai aktif -->
                        <ul class="submenu" id="masterSubmenu" style="display: block;">
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li><a href="supplier.php">Supplier</a></li>
                            <li class="active-sub"><a href="katalog_produk.php">Katalog Produk</a></li>
                        </ul>
                    </li>
                    <li><a href="harga.php">Update Harga Harian</a></li>
                    <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                <?php endif; ?>
             </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Tambah Katalog Produk Baru</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3>Detail Katalog</h3>
                    <form action="katalog_proses_baru.php" method="POST">
                        
                        <div class="form-group">
                            <label for="nama_produk">Nama Produk (Wajib)</label>
                            <input type="text" id="nama_produk" name="nama_produk" placeholder="Contoh: Cincin Polos, Gelang Rantai" required>
                        </div>

                        <div class="form-group">
                            <label for="tipe">Tipe (Wajib)</label>
                            <input type="text" id="tipe" name="tipe" placeholder="Contoh: Cincin, Gelang, Liontin" required>
                        </div>

                        <div class="form-group">
                            <label for="kadar">Kadar (Wajib)</label>
                            <input type="text" id="kadar" name="kadar" placeholder="Contoh: 375, 750, 999" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="satuan">Satuan (Wajib)</label>
                            <select id="satuan" name="satuan" required>
                                <option value="gr">Gram (gr)</option>
                                <option value="pcs">Pcs (pcs)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">Simpan Katalog Baru</button>
                        <a href="katalog_produk.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Script untuk toggle submenu
        var submenuToggle = document.querySelector('.has-submenu');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                
                // Toggle hanya jika bukan halaman aktif
                if (!this.parentElement.querySelector('.submenu li.active-sub')) {
                    submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
                }
            });
        }
    </script>
</body>
</html>