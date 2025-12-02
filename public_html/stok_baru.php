<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role
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
$is_admin_or_pemilik = true;

// 3. AMBIL DATA UNTUK DROPDOWN
$katalog_list = [];
$supplier_list = [];

$sql_katalog = "SELECT ProdukKatalogID, NamaProduk, Tipe, Kadar, Satuan FROM PRODUK_KATALOG ORDER BY NamaProduk ASC";
$result_katalog = $koneksi->query($sql_katalog);
if ($result_katalog && $result_katalog->num_rows > 0) {
    while ($row = $result_katalog->fetch_assoc()) {
        $katalog_list[] = $row;
    }
}

$sql_supplier = "SELECT SupplierID, NamaSupplier FROM SUPPLIER ORDER BY NamaSupplier ASC";
$result_supplier = $koneksi->query($sql_supplier);
if ($result_supplier && $result_supplier->num_rows > 0) {
    while ($row = $result_supplier->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

$koneksi->close();

// 4. AMBIL DATA USER
$nama_karyawan = $_SESSION['nama_karyawan'];
if ($role == 'pemilik') {
    $role_text = 'Pemilik';
} elseif ($role == 'admin') {
    $role_text = 'Admin';
} else {
    $role_text = 'Kasir';
}
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Stok Baru - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-card { background-color: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); max-width: 700px; margin: 2rem auto; }
        .form-card h3 { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .form-row { display: flex; gap: 1rem; }
        .form-row .form-group { flex: 1; }
        .btn-primary { width: 100%; padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #007bff; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; text-align: center; font-size: 1rem; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { display: inline-block; text-align: center; width: 100%; box-sizing: border-box; padding: 0.75rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-top: 1rem; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .error { background-color: #f8d7da; color: #721c24; }
        
        .harga-rekomendasi { font-size: 0.9rem; margin-top: 8px; padding: 10px; border-radius: 5px; display: none; transition: all 0.3s ease; }
        .harga-rekomendasi.success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .harga-rekomendasi.error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .harga-rekomendasi.loading { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .harga-detail { margin-top: 5px; font-size: 0.85rem; opacity: 0.8; }
        .satuan-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 0.75rem; font-weight: bold; margin-left: 5px; }
        .satuan-gram { background-color: #ffc107; color: #000; }
        .satuan-pcs { background-color: #17a2b8; color: #fff; }
        .konversi-info { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 10px; margin-top: 8px; border-radius: 4px; font-size: 0.9rem; display: none; }
        
        /* STYLE BARU UNTUK QUANTITY */
        .quantity-info { background-color: #fff9e6; border-left: 4px solid #ff9800; padding: 12px; margin-top: 10px; border-radius: 4px; display: none; }
        .quantity-info strong { color: #e65100; }
        .batch-summary { background-color: #e8f5e9; border: 2px solid #4caf50; padding: 15px; border-radius: 8px; margin-top: 15px; display: none; }
        .batch-summary h4 { margin: 0 0 10px 0; color: #2e7d32; }
        .batch-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #c8e6c9; }
        .qty-badge { background-color: #ff9800; color: white; padding: 3px 10px; border-radius: 12px; font-weight: bold; font-size: 0.9rem; }
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
                <?php if ($is_admin_or_pemilik): ?>
                    <li><a href="laporan.php">Laporan Penjualan</a></li>
                    <li>
                        <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                        <ul class="submenu" id="masterSubmenu" style="display: block;">
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li><a href="supplier.php">Supplier</a></li>
                            <li><a href="katalog_produk.php">Katalog Produk</a></li>
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
                    <h1>Tambah Stok Baru</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3>Detail Barang Stok (Kode Otomatis)</h3>
                    <?php if ($error_message): ?>
                        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <form action="proses_stok_baru.php" method="POST">
                        
                        <div class="form-group">
                            <label for="produk_katalog_id">
                                Pilih Produk dari Katalog (Wajib)
                                <span id="satuan_indicator"></span>
                            </label>
                            <select id="produk_katalog_id" name="produk_katalog_id" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($katalog_list as $produk): ?>
                                    <option value="<?php echo $produk['ProdukKatalogID']; ?>" 
                                            data-kadar="<?php echo htmlspecialchars($produk['Kadar']); ?>"
                                            data-satuan="<?php echo htmlspecialchars($produk['Satuan']); ?>">
                                        <?php echo htmlspecialchars($produk['NamaProduk'] . ' (' . $produk['Tipe'] . ' - ' . $produk['Kadar'] . ') [' . $produk['Satuan'] . ']'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- FIELD QUANTITY BARU -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="quantity">Jumlah Item (Qty)</label>
                                <input type="number" step="1" min="1" id="quantity" name="quantity" value="1" required>
                                <small style="color: #666; font-size: 0.85rem;">Berapa banyak item yang sama?</small>
                            </div>
                            <div class="form-group">
                                <label for="asal_barang">Asal Barang</label>
                                <select id="asal_barang" name="asal_barang">
                                    <option value="Supplier">Supplier (Baru)</option>
                                    <option value="BuyBack">BuyBack (Bekas)</option>
                                    <option value="Produksi">Produksi (Internal)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="berat_gram" id="label_berat">Berat per Item (Gram) (Wajib)</label>
                                <input type="number" step="0.01" id="berat_gram" name="berat_gram" placeholder="Contoh: 5.25" required>
                                <small style="color: #666; font-size: 0.85rem;" id="berat_hint">Berat untuk 1 item</small>
                                <div id="konversi_info" class="konversi-info"></div>
                            </div>
                            <div class="form-group">
                                <label for="harga_beli_modal">Harga Modal per Item (Wajib)</label>
                                <input type="number" step="1" min="0" id="harga_beli_modal" name="harga_beli_modal" placeholder="Contoh: 1750000" required>
                                <small style="color: #666; font-size: 0.85rem;">Masukkan angka tanpa titik/koma (Contoh: 1750000 untuk 1,75jt)</small>
                            </div>
                        </div>

                        <!-- INFO QUANTITY -->
                        <div id="quantity_info" class="quantity-info">
                            <strong>📦 Batch Input:</strong> Sistem akan membuat <span id="qty_display" class="qty-badge">1</span> item dengan kode berbeda secara otomatis
                        </div>

                        <!-- SUMMARY BATCH -->
                        <div id="batch_summary" class="batch-summary">
                            <h4>📋 Ringkasan Batch Input</h4>
                            <div class="batch-item">
                                <span>Total Item:</span>
                                <strong id="summary_qty">1 pcs</strong>
                            </div>
                            <div class="batch-item">
                                <span>Berat per Item:</span>
                                <strong id="summary_berat">0 gram</strong>
                            </div>
                            <div class="batch-item">
                                <span>Total Berat Keseluruhan:</span>
                                <strong id="summary_total_berat">0 gram</strong>
                            </div>
                            <div class="batch-item">
                                <span>Modal per Item:</span>
                                <strong id="summary_modal">Rp 0</strong>
                            </div>
                            <div class="batch-item" style="border-bottom: none; padding-top: 10px; border-top: 2px solid #4caf50;">
                                <span>TOTAL MODAL KESELURUHAN:</span>
                                <strong id="summary_total_modal" style="color: #2e7d32; font-size: 1.1rem;">Rp 0</strong>
                            </div>
                        </div>

                        <div id="rekomendasi_harga" class="harga-rekomendasi"></div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tanggal_masuk">Tanggal Masuk (Wajib)</label>
                                <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="supplier_id">Supplier (Jika Asal = Supplier)</label>
                                <select id="supplier_id" name="supplier_id">
                                    <option value="">-- Tidak Ada --</option>
                                     <?php foreach ($supplier_list as $supplier): ?>
                                        <option value="<?php echo $supplier['SupplierID']; ?>">
                                            <?php echo htmlspecialchars($supplier['NamaSupplier']); ?>
                                        </option>
                                     <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">Simpan Stok Baru</button>
                        <a href="stok.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const produkSelect = document.getElementById('produk_katalog_id');
        const beratInput = document.getElementById('berat_gram');
        const labelBerat = document.getElementById('label_berat');
        const beratHint = document.getElementById('berat_hint');
        const rekomendasiDiv = document.getElementById('rekomendasi_harga');
        const asalBarangSelect = document.getElementById('asal_barang');
        const satuanIndicator = document.getElementById('satuan_indicator');
        const konversiInfo = document.getElementById('konversi_info');
        const quantityInput = document.getElementById('quantity');
        const quantityInfo = document.getElementById('quantity_info');
        const qtyDisplay = document.getElementById('qty_display');
        const batchSummary = document.getElementById('batch_summary');
        const hargaModalInput = document.getElementById('harga_beli_modal');

        let dataKadar = '';
        let dataSatuan = '';
        let dataBerat = 0;
        let dataQuantity = 1;
        let dataModal = 0;
        let isLoading = false;

        const kadarMapping = {
            '24K': 99.9, '23K': 95.8, '22K': 91.6, '21K': 87.5, '20K': 83.3,
            '18K': 75.0, '17K': 70.8, '16K': 66.6, '14K': 58.3, '10K': 41.6,
            '9K': 37.5, '8K': 33.3, '6K': 25.0, 'Emas Muda': 50.0
        };

        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
        };

        function hitungBeratEmasMurni(beratTotal, kadar) {
            const persenKemurnian = kadarMapping[kadar] || 0;
            return (beratTotal * persenKemurnian) / 100;
        }

        function updateBatchSummary() {
            if (dataQuantity > 1) {
                quantityInfo.style.display = 'block';
                batchSummary.style.display = 'block';
                qtyDisplay.textContent = dataQuantity;
                
                const totalBerat = dataBerat * dataQuantity;
                const totalModal = dataModal * dataQuantity;
                
                document.getElementById('summary_qty').textContent = dataQuantity + ' pcs';
                document.getElementById('summary_berat').textContent = dataBerat > 0 ? dataBerat.toFixed(2) + ' gram' : '0 gram';
                document.getElementById('summary_total_berat').textContent = totalBerat > 0 ? totalBerat.toFixed(2) + ' gram' : '0 gram';
                document.getElementById('summary_modal').textContent = dataModal > 0 ? formatRupiah(dataModal) : 'Rp 0';
                document.getElementById('summary_total_modal').textContent = totalModal > 0 ? formatRupiah(totalModal) : 'Rp 0';
            } else {
                quantityInfo.style.display = 'none';
                batchSummary.style.display = 'none';
            }
        }

        function showLoading() {
            isLoading = true;
            rekomendasiDiv.className = 'harga-rekomendasi loading';
            rekomendasiDiv.style.display = 'block';
            rekomendasiDiv.innerHTML = '⏳ Menghitung rekomendasi harga...';
        }

        function hideRekomendasi() {
            rekomendasiDiv.style.display = 'none';
            rekomendasiDiv.innerHTML = '';
        }

        function updateLabelBySatuan(satuan) {
            if (satuan.toLowerCase() === 'pcs' || satuan.toLowerCase() === 'piece') {
                labelBerat.innerHTML = 'Berat per Item (Gram) (Wajib)';
                beratInput.placeholder = 'Contoh: 2.5 (berat 1 pcs)';
                beratHint.textContent = 'Berat untuk 1 item';
                satuanIndicator.innerHTML = '<span class="satuan-badge satuan-pcs">Satuan: PCS</span>';
            } else {
                labelBerat.innerHTML = 'Berat per Item (Gram) (Wajib)';
                beratInput.placeholder = 'Contoh: 5.25';
                beratHint.textContent = 'Berat untuk 1 item';
                satuanIndicator.innerHTML = '<span class="satuan-badge satuan-gram">Satuan: GRAM</span>';
            }
        }

        function tampilkanKonversiKadar() {
            if (!dataKadar || dataBerat <= 0) {
                konversiInfo.style.display = 'none';
                return;
            }
            const persenKemurnian = kadarMapping[dataKadar];
            if (!persenKemurnian) {
                konversiInfo.style.display = 'none';
                return;
            }

            const beratEmasMurni = hitungBeratEmasMurni(dataBerat, dataKadar);
            const totalBeratMurni = beratEmasMurni * dataQuantity;
            
            konversiInfo.style.display = 'block';
            konversiInfo.innerHTML = `
                <strong>🔍 Konversi Kadar per Item:</strong><br>
                ${dataBerat} gram × ${dataKadar} (${persenKemurnian}%) = <strong>${beratEmasMurni.toFixed(2)} gram emas murni</strong><br>
                ${dataQuantity > 1 ? `<small>Total ${dataQuantity} item = <strong>${totalBeratMurni.toFixed(2)} gram emas murni</strong></small>` : ''}
            `;
        }

        function getRekomendasiHarga() {
            const asal = asalBarangSelect.value;
            if (!dataKadar || dataBerat <= 0) {
                hideRekomendasi();
                konversiInfo.style.display = 'none';
                return;
            }
            tampilkanKonversiKadar();
            showLoading();

            let beratUntukAPI = dataBerat;
            if (dataSatuan.toLowerCase() === 'pcs' || dataSatuan.toLowerCase() === 'piece') {
                const beratEmasMurni = hitungBeratEmasMurni(dataBerat, dataKadar);
                beratUntukAPI = beratEmasMurni;
            }

            const kadarAPI = (dataSatuan.toLowerCase() === 'pcs' || dataSatuan.toLowerCase() === 'piece') ? '24K' : dataKadar;

            fetch(`api_get_harga_rekomendasi.php?kadar=${encodeURIComponent(kadarAPI)}&asal=${encodeURIComponent(asal)}`)
                .then(response => response.json())
                .then(data => {
                    isLoading = false;
                    if (data.error) {
                        rekomendasiDiv.className = 'harga-rekomendasi error';
                        rekomendasiDiv.style.display = 'block';
                        rekomendasiDiv.innerHTML = `<strong>⚠️ ${data.error}</strong>`;
                    } else if (data.success) {
                        const hargaPerGram = parseFloat(data.harga_per_gram);
                        const rekomendasiPerItem = hargaPerGram * beratUntukAPI;
                        const rekomendasiTotal = rekomendasiPerItem * dataQuantity;
                        
                        rekomendasiDiv.className = 'harga-rekomendasi success';
                        rekomendasiDiv.style.display = 'block';
                        
                        let infoTambahan = '';
                        if (dataSatuan.toLowerCase() === 'pcs') {
                            infoTambahan = `<br><small>💎 Dihitung dari kandungan emas murni: ${beratUntukAPI.toFixed(2)} gram</small>`;
                        }
                        
                        rekomendasiDiv.innerHTML = `
                            <strong>💡 Rekomendasi Modal per Item: ${formatRupiah(rekomendasiPerItem)}</strong>
                            ${dataQuantity > 1 ? `<br><strong style="color: #2e7d32;">Total ${dataQuantity} item: ${formatRupiah(rekomendasiTotal)}</strong>` : ''}
                            <div class="harga-detail">
                                Harga per gram emas 24K: ${formatRupiah(hargaPerGram)} | 
                                Berat: ${dataBerat} gram ${dataKadar} | 
                                ${data.keterangan_faktor}
                                ${infoTambahan}
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    isLoading = false;
                    rekomendasiDiv.className = 'harga-rekomendasi error';
                    rekomendasiDiv.style.display = 'block';
                    rekomendasiDiv.innerHTML = '<strong>❌ Gagal mengambil data harga.</strong>';
                });
        }

        produkSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            dataKadar = selectedOption.dataset.kadar || '';
            dataSatuan = selectedOption.dataset.satuan || '';
            if (dataSatuan) updateLabelBySatuan(dataSatuan);
            konversiInfo.style.display = 'none';
            if (dataKadar && dataBerat > 0) getRekomendasiHarga(); else hideRekomendasi();
            updateBatchSummary();
        });

        beratInput.addEventListener('input', function() {
            dataBerat = parseFloat(this.value) || 0;
            if (dataBerat > 0 && dataKadar) getRekomendasiHarga(); else { hideRekomendasi(); konversiInfo.style.display = 'none'; }
            updateBatchSummary();
        });
        
        quantityInput.addEventListener('input', function() {
            dataQuantity = parseInt(this.value) || 1;
            if (dataQuantity < 1) {
                dataQuantity = 1;
                this.value = 1;
            }
            updateBatchSummary();
            if (dataKadar && dataBerat > 0) getRekomendasiHarga();
        });

        hargaModalInput.addEventListener('input', function() {
            dataModal = parseFloat(this.value) || 0;
            updateBatchSummary();
        });
        
        asalBarangSelect.addEventListener('change', function() {
            if (dataKadar && dataBerat > 0) getRekomendasiHarga();
        });

        var submenuToggle = document.querySelector('.has-submenu');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                if (!this.parentElement.querySelector('.submenu li.active-sub')) { 
                    submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
                }
            });
        }
    </script>
</body>
</html>