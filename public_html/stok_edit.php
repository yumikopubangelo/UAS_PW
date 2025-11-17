<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Hanya Admin/Pemilik)
if (!isset($_SESSION['index']) || $_SESSION['index'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}
$role = $_SESSION['Role'];
if ($role != 'admin' && $role != 'pemilik') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
    header("Location: home.php");
    exit;
}

// 3. VALIDASI GET PARAMETER
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    $_SESSION['error_message'] = "Kode barang tidak valid atau tidak ditemukan.";
    header("Location: stok.php");
    exit;
}
$kode_barang_edit = $_GET['kode'];

// 4. AMBIL DATA BARANG YANG AKAN DI-EDIT
$sql_item = "SELECT * FROM BARANG_STOK WHERE KodeBarang = ?";
$stmt_item = $koneksi->prepare($sql_item);
$stmt_item->bind_param("s", $kode_barang_edit);
$stmt_item->execute();
$result_item = $stmt_item->get_result();

if ($result_item->num_rows == 0) {
    $_SESSION['error_message'] = "Data stok dengan kode $kode_barang_edit tidak ditemukan.";
    $stmt_item->close();
    $koneksi->close();
    header("Location: stok.php");
    exit;
}
$item = $result_item->fetch_assoc();
$stmt_item->close();

// 5. AMBIL DATA UNTUK DROPDOWN (Katalog & Supplier)
$katalog_list = [];
$supplier_list = [];

// Ambil data Katalog
$sql_katalog = "SELECT ProdukKatalogID, NamaProduk, Tipe, Kadar FROM PRODUK_KATALOG ORDER BY NamaProduk ASC";
$result_katalog = $koneksi->query($sql_katalog);
if ($result_katalog->num_rows > 0) {
    while ($row = $result_katalog->fetch_assoc()) {
        $katalog_list[] = $row;
    }
}
// Ambil data Supplier
$sql_supplier = "SELECT SupplierID, NamaSupplier FROM SUPPLIER ORDER BY NamaSupplier ASC";
$result_supplier = $koneksi->query($sql_supplier);
if ($result_supplier->num_rows > 0) {
    while ($row = $result_supplier->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

$koneksi->close();

// Ambil data user
$nama_karyawan = $_SESSION['NamaKaryawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : 'Admin';
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stok - <?php echo htmlspecialchars($kode_barang_edit); ?></title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-card { background-color: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); max-width: 700px; margin: 0 auto; }
        .form-card h3 { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .form-row { display: flex; gap: 1rem; }
        .form-row .form-group { flex: 1; }
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
                <li class="active"><a href="stok.php">Manajemen Stok</a></li>
             </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1>Edit Stok Barang: <?php echo htmlspecialchars($kode_barang_edit); ?></h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3>Detail Barang Stok</h3>
                    <form action="proses_stok_edit.php" method="POST">
                        
                        <input type="hidden" name="barang_id" value="<?php echo $item['BarangID']; ?>">
                        
                        <div class="form-group">
                            <label for="produk_katalog_id">Produk dari Katalog (Wajib)</label>
                            <select id="produk_katalog_id" name="produk_katalog_id" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($katalog_list as $produk): ?>
                                    <option value="<?php echo $produk['ProdukKatalogID']; ?>"
                                        <?php if ($produk['ProdukKatalogID'] == $item['ProdukKatalogID']) echo 'selected'; ?>
                                    >
                                        <?php echo htmlspecialchars($produk['NamaProduk'] . ' (' . $produk['Tipe'] . ' - ' . $produk['Kadar'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="kode_barang">Kode Barang / Barcode (Wajib)</label>
                            <input type="text" id="kode_barang" name="kode_barang" value="<?php echo htmlspecialchars($item['KodeBarang']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="berat_gram">Berat (Gram) (Wajib)</label>
                                <input type="number" step="0.01" id="berat_gram" name="berat_gram" value="<?php echo htmlspecialchars($item['BeratGram']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="harga_beli_modal">Harga Beli / Modal (Wajib)</label>
                                <input type="number" id="harga_beli_modal" name="harga_beli_modal" value="<?php echo htmlspecialchars($item['HargaBeliModal']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tanggal_masuk">Tanggal Masuk (Wajib)</label>
                            <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo htmlspecialchars($item['TanggalMasuk']); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="asal_barang">Asal Barang</label>
                                <select id="asal_barang" name="asal_barang">
                                    <option value="Supplier" <?php if ($item['AsalBarang'] == 'Supplier') echo 'selected'; ?>>Supplier (Baru)</option>
                                    <option value="BuyBack" <?php if ($item['AsalBarang'] == 'BuyBack') echo 'selected'; ?>>BuyBack (Bekas)</option>
                                    <option value="Produksi" <?php if ($item['AsalBarang'] == 'Produksi') echo 'selected'; ?>>Produksi (Internal)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="supplier_id">Supplier (Jika Asal = Supplier)</label>
                                <select id="supplier_id" name="supplier_id">
                                    <option value="">-- Tidak Ada --</option>
                                     <?php foreach ($supplier_list as $supplier): ?>
                                        <option value="<?php echo $supplier['SupplierID']; ?>"
                                            <?php if ($supplier['SupplierID'] == $item['SupplierID']) echo 'selected'; ?>
                                        >
                                            <?php echo htmlspecialchars($supplier['NamaSupplier']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                        <a href="stok.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>

</body>
</html>