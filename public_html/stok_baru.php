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

// Ambil data Katalog (TERMASUK KADAR DAN SATUAN UNTUK JS)
$sql_katalog = "SELECT ProdukKatalogID, NamaProduk, Tipe, Kadar, Satuan FROM PRODUK_KATALOG ORDER BY NamaProduk ASC";
$result_katalog = $koneksi->query($sql_katalog);
if ($result_katalog && $result_katalog->num_rows > 0) {
    while ($row = $result_katalog->fetch_assoc()) {
        $katalog_list[] = $row;
    }
}

// Ambil data Supplier
$sql_supplier = "SELECT SupplierID, NamaSupplier FROM SUPPLIER ORDER BY NamaSupplier ASC";
$result_supplier = $koneksi->query($sql_supplier);
if ($result_supplier && $result_supplier->num_rows > 0) {
    while ($row = $result_supplier->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

// Ambil pesan notifikasi
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
        .form-card { 
            background-color: #fff; 
            padding: 1.5rem; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.08); 
            max-width: 700px; 
            margin: 2rem auto; 
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
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 0.75rem; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            box-sizing: border-box; 
        }
        .form-row { 
            display: flex; 
            gap: 1rem; 
        }
        .form-row .form-group { 
            flex: 1; 
        }
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
            transition: background-color 0.2s ease; 
            text-align: center; 
            font-size: 1rem; 
        }
        .btn-primary:hover { 
            background-color: #0056b3; 
        }
        .btn-secondary { 
            display: inline-block; 
            text-align: center; 
            width: 100%; 
            box-sizing: border-box; 
            padding: 0.75rem; 
            background-color: #6c757d; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            margin-top: 1rem; 
        }
        .message { 
            padding: 10px; 
            margin-bottom: 15px; 
            border-radius: 4px; 
            text-align: center; 
            font-weight: bold; 
        }
        .error { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
        
        /* CSS UNTUK REKOMENDASI HARGA */
        .harga-rekomendasi {
            font-size: 0.9rem;
            margin-top: 8px;
            padding: 10px;
            border-radius: 5px;
            display: none;
            transition: all 0.3s ease;
        }
        .harga-rekomendasi.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .harga-rekomendasi.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .harga-rekomendasi.loading {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .harga-rekomendasi.info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .harga-detail {
            margin-top: 5px;
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        /* Badge untuk menampilkan satuan */
        .satuan-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 5px;
        }
        .satuan-gram {
            background-color: #ffc107;
            color: #000;
        }
        .satuan-pcs {
            background-color: #17a2b8;
            color: #fff;
        }
        
        /* Info konversi kadar */
        .konversi-info {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin-top: 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            display: none;
        }
        .konversi-info strong {
            color: #1976D2;
        }
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

                        <div class="form-row">
                            <div class="form-group">
                                <label for="berat_gram" id="label_berat">Berat (Gram) (Wajib)</label>
                                <input type="number" step="0.01" id="berat_gram" name="berat_gram" placeholder="Contoh: 5.25" required>
                                <small style="color: #666; font-size: 0.85rem;" id="berat_hint">Masukkan berat dalam gram</small>
                                <div id="konversi_info" class="konversi-info"></div>
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

                        <div class="form-group">
                            <label for="harga_beli_modal">Harga Beli / Modal (Wajib)</label>
                            <input type="number" id="harga_beli_modal" name="harga_beli_modal" placeholder="Harga modal barang" required>
                            <div id="rekomendasi_harga" class="harga-rekomendasi"></div>
                        </div>

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

                        <button type="submit" class="btn-primary">Simpan Stok Baru</button>
                        <a href="stok.php" class="btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Ambil elemen-elemen yang diperlukan
        const produkSelect = document.getElementById('produk_katalog_id');
        const beratInput = document.getElementById('berat_gram');
        const labelBerat = document.getElementById('label_berat');
        const beratHint = document.getElementById('berat_hint');
        const rekomendasiDiv = document.getElementById('rekomendasi_harga');
        const asalBarangSelect = document.getElementById('asal_barang');
        const hargaModalInput = document.getElementById('harga_beli_modal');
        const satuanIndicator = document.getElementById('satuan_indicator');
        const konversiInfo = document.getElementById('konversi_info');

        let dataKadar = '';
        let dataSatuan = '';
        let dataBerat = 0;
        let isLoading = false;

        // Mapping kadar ke persentase kemurnian
        const kadarMapping = {
            '24K': 99.9,
            '23K': 95.8,
            '22K': 91.6,
            '21K': 87.5,
            '20K': 83.3,
            '18K': 75.0,
            '17K': 70.8,
            '16K': 66.6,
            '14K': 58.3,
            '10K': 41.6,
            '9K': 37.5,
            '8K': 33.3,
            'Emas Muda': 50.0
        };

        // Fungsi format Rupiah
        const formatRupiah = (number) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency', 
                currency: 'IDR', 
                minimumFractionDigits: 0
            }).format(number);
        };

        // Fungsi untuk menghitung berat emas murni
        function hitungBeratEmasMurni(beratTotal, kadar) {
            const persenKemurnian = kadarMapping[kadar] || 0;
            return (beratTotal * persenKemurnian) / 100;
        }

        // Fungsi untuk menampilkan loading
        function showLoading() {
            isLoading = true;
            rekomendasiDiv.className = 'harga-rekomendasi loading';
            rekomendasiDiv.style.display = 'block';
            rekomendasiDiv.innerHTML = '⏳ Menghitung rekomendasi harga...';
        }

        // Fungsi untuk menyembunyikan rekomendasi
        function hideRekomendasi() {
            rekomendasiDiv.style.display = 'none';
            rekomendasiDiv.innerHTML = '';
        }

        // Fungsi untuk update label dan placeholder berdasarkan satuan
        function updateLabelBySatuan(satuan) {
            if (satuan.toLowerCase() === 'pcs' || satuan.toLowerCase() === 'piece') {
                // Untuk produk per pieces
                labelBerat.innerHTML = 'Berat per Pieces (Gram) (Wajib)';
                beratInput.placeholder = 'Contoh: 2.5 (berat 1 pcs)';
                beratInput.required = true;
                beratHint.textContent = 'Berat untuk 1 pieces (akan dihitung berdasarkan kadar emas)';
                
                // Tampilkan badge
                satuanIndicator.innerHTML = '<span class="satuan-badge satuan-pcs">Satuan: PCS</span>';
            } else {
                // Untuk produk per gram
                labelBerat.innerHTML = 'Berat (Gram) (Wajib)';
                beratInput.placeholder = 'Contoh: 5.25';
                beratInput.required = true;
                beratHint.textContent = 'Masukkan berat dalam gram';
                
                // Tampilkan badge
                satuanIndicator.innerHTML = '<span class="satuan-badge satuan-gram">Satuan: GRAM</span>';
            }
        }

        // Fungsi untuk menampilkan info konversi kadar
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
            
            konversiInfo.style.display = 'block';
            konversiInfo.innerHTML = `
                <strong>🔍 Konversi Kadar:</strong><br>
                Berat Total: ${dataBerat} gram × Kemurnian ${dataKadar} (${persenKemurnian}%) 
                = <strong>${beratEmasMurni.toFixed(2)} gram emas murni (24K)</strong>
            `;
        }

        // Fungsi utama untuk mengambil rekomendasi harga
        function getRekomendasiHarga() {
            const asal = asalBarangSelect.value;
            
            // Validasi: harus ada kadar dan berat
            if (!dataKadar || dataBerat <= 0) {
                hideRekomendasi();
                konversiInfo.style.display = 'none';
                return;
            }

            // Tampilkan info konversi kadar
            tampilkanKonversiKadar();

            // Tampilkan loading
            showLoading();

            // Untuk produk PCS: gunakan berat emas murni (konversi)
            // Untuk produk GRAM: gunakan berat asli
            let beratUntukAPI = dataBerat;
            let keteranganBerat = '';
            
            if (dataSatuan.toLowerCase() === 'pcs' || dataSatuan.toLowerCase() === 'piece') {
                const beratEmasMurni = hitungBeratEmasMurni(dataBerat, dataKadar);
                beratUntukAPI = beratEmasMurni;
                keteranganBerat = ` (${beratEmasMurni.toFixed(2)}g emas murni dari ${dataBerat}g total)`;
            }

            // Panggil API dengan kadar 24K untuk produk PCS (karena sudah dikonversi)
            const kadarAPI = (dataSatuan.toLowerCase() === 'pcs' || dataSatuan.toLowerCase() === 'piece') ? '24K' : dataKadar;

            fetch(`api_get_harga_rekomendasi.php?kadar=${encodeURIComponent(kadarAPI)}&asal=${encodeURIComponent(asal)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    isLoading = false;
                    
                    if (data.error) {
                        // Tampilkan error
                        rekomendasiDiv.className = 'harga-rekomendasi error';
                        rekomendasiDiv.style.display = 'block';
                        rekomendasiDiv.innerHTML = `
                            <strong>⚠️ ${data.error}</strong>
                            ${data.hint ? '<div class="harga-detail">' + data.hint + '</div>' : ''}
                        `;
                    } else if (data.success) {
                        // Hitung total rekomendasi berdasarkan berat yang sudah dikonversi
                        const hargaPerGram = parseFloat(data.harga_per_gram);
                        const rekomendasiTotal = hargaPerGram * beratUntukAPI;
                        
                        // Tampilkan rekomendasi sukses
                        rekomendasiDiv.className = 'harga-rekomendasi success';
                        rekomendasiDiv.style.display = 'block';
                        
                        let infoTambahan = '';
                        if (dataSatuan.toLowerCase() === 'pcs' || dataSatuan.toLowerCase() === 'piece') {
                            infoTambahan = `<br><small>💎 Dihitung dari kandungan emas murni: ${beratUntukAPI.toFixed(2)} gram</small>`;
                        }
                        
                        rekomendasiDiv.innerHTML = `
                            <strong>💡 Rekomendasi Modal: ${formatRupiah(rekomendasiTotal)}</strong>
                            <div class="harga-detail">
                                Harga per gram emas 24K: ${formatRupiah(hargaPerGram)} | 
                                Berat: ${dataBerat} gram ${dataKadar} | 
                                ${data.keterangan_faktor}
                                ${infoTambahan}
                            </div>
                        `;
                        
                        // OPSIONAL: Isi otomatis input harga modal
                        // Uncomment baris di bawah jika ingin auto-fill
                        // hargaModalInput.value = Math.round(rekomendasiTotal);
                    }
                })
                .catch(err => {
                    isLoading = false;
                    console.error('Fetch error:', err);
                    rekomendasiDiv.className = 'harga-rekomendasi error';
                    rekomendasiDiv.style.display = 'block';
                    rekomendasiDiv.innerHTML = '<strong>❌ Gagal mengambil data harga. Silakan coba lagi.</strong>';
                });
        }

        // Event Listener: Saat produk diganti
        produkSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            dataKadar = selectedOption.dataset.kadar || '';
            dataSatuan = selectedOption.dataset.satuan || '';
            
            // Update label berdasarkan satuan
            if (dataSatuan) {
                updateLabelBySatuan(dataSatuan);
            }
            
            // Reset info konversi
            konversiInfo.style.display = 'none';
            
            // Panggil fungsi rekomendasi jika berat sudah diisi
            if (dataKadar && dataBerat > 0) {
                getRekomendasiHarga();
            } else {
                hideRekomendasi();
            }
        });

        // Event Listener: Saat berat diketik
        beratInput.addEventListener('input', function() {
            dataBerat = parseFloat(this.value) || 0;
            
            if (dataBerat > 0 && dataKadar) {
                getRekomendasiHarga();
            } else {
                hideRekomendasi();
                konversiInfo.style.display = 'none';
            }
        });
        
        // Event Listener: Saat asal barang diganti
        asalBarangSelect.addEventListener('change', function() {
            if (dataKadar && dataBerat > 0) {
                getRekomendasiHarga();
            }
        });

        // Script untuk toggle submenu
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