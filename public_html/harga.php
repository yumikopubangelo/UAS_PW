<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// 3. SIAPKAN DATA USER DARI SESSION
$nama_karyawan = $_SESSION['nama_karyawan'];
$role = $_SESSION['role']; // isinya "kasir", "admin", atau "pemilik"

// 4. Buat variabel boolean untuk kemudahan pengecekan
$is_admin = ($role == 'admin' || $role == 'pemilik');

// Tentukan teks role dengan lebih detail
if ($role == 'pemilik') {
    $role_text = 'Pemilik';
} elseif ($role == 'admin') {
    $role_text = 'Admin';
} else {
    $role_text = 'Karyawan'; 
}
$logout_url = "logout.php";

// 5. AMBIL DATA HARGA DARI DATABASE (untuk tabel)
$list_harga = [];

// 1. Dapatkan tanggal HARI INI menurut PHP (WIB)
$today_php = date("Y-m-d"); 

// 2. Gunakan tanggal PHP itu dalam kueri
$sql_select = "SELECT Kadar, HargaJualPerGram, HargaBeliPerGram, Tanggal 
               FROM RIWAYAT_HARGA 
               WHERE Tanggal = ? 
               ORDER BY Kadar ASC";

$stmt_select = $koneksi->prepare($sql_select);
$stmt_select->bind_param("s", $today_php); // "s" untuk string tanggal
$stmt_select->execute();
$result = $stmt_select->get_result(); 

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $list_harga[] = $row;
    }
}

// 3. Tutup statement select
$stmt_select->close(); 
$koneksi->close(); // Tutup koneksi setelah selesai ambil data
?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Update Harga Harian - Sistem Toko Emas</title>
        <link rel="stylesheet" href="dashboard.css">

        <style>
            /* (CSS Anda sudah benar, tidak diubah) */
            .page-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; }
            .form-card, .table-card { background-color: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
            .form-card h3 { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
            .form-group { margin-bottom: 1rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
            .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
            .btn-primary, .btn-secondary { width: 100%; padding: 0.75rem 1.2rem; border: none; border-radius: 6px; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; text-align: center; }
            .btn-primary { background-color: #007bff; margin-top: 1rem; }
            .btn-primary:hover { background-color: #0056b3; }
            .btn-secondary { background-color: #6c757d; margin-bottom: 1rem; }
            .btn-secondary:hover { background-color: #5a6268; }
            .api-note { font-size: 0.85rem; color: #666; text-align: center; margin-bottom: 1rem; }
            .data-table { width: 100%; border-collapse: collapse; }
            .data-table th, .data-table td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
            .data-table thead { background-color: #f8f9fa; }
            .data-table th { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; }
            .data-table td { color: #333; }
            .col-jual { font-weight: 600; color: #28a745; }
            .col-beli { font-weight: 600; color: #dc3545; }
            .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
            .error { background-color: #f8d7da; color: #721c24; }
            .success { background-color: #d4edda; color: #155724; }
        </style>
    </head>
    <body>

        <div class="dashboard-container">
            
            <nav class="sidebar">
                <div class="sidebar-header">
                    <h3>Toko Emas UMKM</h3>
                </div>
                
                <ul class="sidebar-menu">
                    
                    <li><a href="home.php">Dashboard</a></li>
                    <li><a href="transaksi_baru.php">Transaksi Baru (POS)</a></li>
                    <li><a href="stok.php">Manajemen Stok</a></li>
                    
                    <?php if ($is_admin): ?>
                        <li><a href="laporan.php">Laporan Penjualan</a></li>
                        
                        <li>
                            <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                            <ul class="submenu" id="masterSubmenu">
                                <li><a href="pelanggan.php">Pelanggan</a></li>
                                <li><a href="supplier.php">Supplier</a></li>
                                <li><a href="katalog_produk.php">Katalog Produk</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Tandai halaman ini sebagai aktif -->
                    <li class="active"><a href="harga.php">Update Harga Harian</a></li>

                    <?php if ($is_admin): ?>
                        <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                    <?php endif; ?>
                    
                </ul>
            </nav>

            <main class="main-content">
                
                <header class="header">
                    <div class="header-title">
                        <h1>Update Harga Harian</h1>
                    </div>
                    <div class="user-info">
                        <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                        <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                    </div>
                </header>

                <div class="content-wrapper">
                    
                    <?php
                    // Tampilkan notifikasi (jika ada)
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    ?>

                    <div class="page-grid">
                        
                        <div class="form-card">
                            <h3>Set Harga Acuan Hari Ini</h3>
                            <p class="api-note">Klik tombol di bawah untuk mengambil sugesti harga 24K (999) dari API Antam (LM).</p>
                            
                            <button type="button" class="btn-secondary" id="fetch-api-btn">
                                Ambil Harga Sugesti 24K (via API)
                            </button>

                            <form action="proses_update_harga.php" method="POST">
                                <div class="form-group">
                                    <label for="kadar">Kadar Emas</label>
                                    <input type="text" id="kadar" name="kadar" list="kadar-list" 
                                        placeholder="Ketik kadar (misal: 375, 700, 999)" required>
                                    
                                    <datalist id="kadar-list">
                                        <option value="375">375 (8K)</option>
                                        <option value="420">420 (9K)</option>
                                        <option value="750">750 (18K)</option>
                                        <option value="999">999 (24K/LM)</option>
                                    </datalist>
                                </div>
                                <div class="form-group">
                                    <label for="HargaJualPerGram">Harga Jual per Gram (Rp)</label>
                                    <input type="number" id="HargaJualPerGram" name="HargaJualPerGram" placeholder="Harga toko menjual ke pelanggan" required>
                                    <span id="preview_jual" class="harga-preview"></span>
                                </div>
                                <div class="form-group">
                                    <label for="HargaBeliPerGram">Harga Beli per Gram (Rp)</label>
                                    <input type="number" id="HargaBeliPerGram" name="HargaBeliPerGram" placeholder="Harga toko membeli (buyback)" required>
                                    <span id="preview_beli" class="harga-preview"></span>
                                </div>

                                <button type="submit" class="btn-primary">Simpan/Update Harga</button>
                            </form>
                        </div>

                        <div class="table-card">
                            <h3>Harga Acuan - <span id="tanggal-hari-ini"></span></h3>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Kadar</th>
                                        <th class="col-jual">Harga Jual (per Gram)</th>
                                        <th class="col-beli">Harga Beli (per Gram)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($list_harga)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center;">Belum ada data harga hari ini.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($list_harga as $harga): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($harga['Kadar']); ?></td>
                                            <td class="col-jual">Rp <?php echo number_format($harga['HargaJualPerGram'], 0, ',', '.'); ?></td>
                                            <td class="col-beli">Rp <?php echo number_format($harga['HargaBeliPerGram'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </main>
        </div>

  <script>
    // === Menampilkan tanggal hari ini ===
     var submenuToggle = document.querySelector('.has-submenu');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
            });
        }
    const today = new Date().toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
    document.getElementById('tanggal-hari-ini').innerText = today;

    // === Variabel Global & Pengaturan Cache ===
    let globalBaseJual999 = 0;
    let globalBaseBeli999 = 0;
    const CACHE_DURATION_MS = 10 * 60 * 1000; // 10 menit

    // === FUNGSI: Format angka ke Rupiah ===
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    };

    // --- FUNGSI 1: Ambil data dari API & SIMPAN KE CACHE ---
    function ambilHargaDariAPI(kadarUntukDitampilkan, showAlert = true) {
        const btn = document.getElementById('fetch-api-btn');
        btn.innerText = 'Mengambil data...';
        btn.disabled = true;

        fetch('api_proxy.php') // Pastikan file ini ada
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengambil data API. Kode: ' + response.status);
            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0)
                throw new Error('Data API kosong atau tidak valid.');

            const latestPrice = data[0];
            if (!latestPrice.jual || !latestPrice.beli)
                throw new Error('Struktur data API salah.');

            globalBaseJual999 = parseFloat(latestPrice.jual);
            globalBaseBeli999 = parseFloat(latestPrice.beli);

            const cacheData = {
                jual: latestPrice.jual,
                beli: latestPrice.beli,
                timestamp: Date.now()
            };
            sessionStorage.setItem('apiHargaCache', JSON.stringify(cacheData));

            kalkulasiUlangHarga(kadarUntukDitampilkan || "999");
            
            if (showAlert) {
                alert(`Harga 24K (999) berhasil di-update dari API!\nJual: ${formatRupiah(globalBaseJual999)}\nBeli: ${formatRupiah(globalBaseBeli999)}`);
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan API: ' + err.message);
        })
        .finally(() => {
            btn.innerText = 'Ambil Harga Sugesti 24K (via API)';
            btn.disabled = false;
        });
    }

    // --- FUNGSI 2: Hanya kalkulasi & update tampilan (TANPA API) ---
    function kalkulasiUlangHarga(kadarDipilih) {
        
        // Ganti ID input agar sesuai dengan HTML
        const inputJual = document.getElementById('HargaJualPerGram');
        const inputBeli = document.getElementById('HargaBeliPerGram');

        const previewJual = document.getElementById('preview_jual');
        const previewBeli = document.getElementById('preview_beli');

        if (!inputJual || !inputBeli) {
             console.error("Error: Input field 'HargaJualPerGram' or 'HargaBeliPerGram' tidak ditemukan. Cek ID HTML Anda.");
             return; 
        }

        if (globalBaseJual999 === 0) {
            console.warn("Harga dasar 999 belum ada.");
            return;
        }

        const kadarAngka = parseFloat(kadarDipilih);
        let finalHargaJual, finalHargaBeli;

        if (isNaN(kadarAngka) || kadarAngka === 0) {
            inputJual.value = "";
            inputBeli.value = "";
            previewJual.innerText = "";
            previewBeli.innerText = "";
            return;
        }

        finalHargaJual = Math.round((kadarAngka / 999) * globalBaseJual999);
        finalHargaBeli = Math.round((kadarAngka / 999) * globalBaseBeli999);

        inputJual.value = finalHargaJual;
        inputBeli.value = finalHargaBeli;
        
        // Tampilkan preview (opsional, tambahkan style jika perlu)
        previewJual.innerText = formatRupiah(finalHargaJual);
        previewBeli.innerText = formatRupiah(finalHargaBeli);
    }

    // === PENGATUR EVENT (EVENT LISTENERS) ===

    // 1. Saat input diketik: HANYA kalkulasi ulang
    document.getElementById('kadar').addEventListener('input', function() {
        const kadarDipilih = this.value;
        kalkulasiUlangHarga(kadarDipilih); 
    });

    // 2. Saat tombol manual ditekan: PAKSA AMBIL API BARU
    document.getElementById('fetch-api-btn').addEventListener('click', function() {
        const kadarSaatIni = document.getElementById('kadar').value;
        ambilHargaDariAPI(kadarSaatIni, true); 
    });

    // 3. Saat halaman pertama kali dibuka: CEK CACHE DULU!
    window.addEventListener('DOMContentLoaded', () => {
        let cachedData = null;
        const now = Date.now();

        try {
            cachedData = JSON.parse(sessionStorage.getItem('apiHargaCache'));
        } catch (e) {
            cachedData = null; 
        }

        if (cachedData && (now - cachedData.timestamp < CACHE_DURATION_MS)) {
            console.log("Menggunakan data harga dari cache (sessionStorage).");
            
            globalBaseJual999 = parseFloat(cachedData.jual);
            globalBaseBeli999 = parseFloat(cachedData.beli);
            
            kalkulasiUlangHarga("999"); // Panggil dengan kadar default saat load

        } else {
            console.log("Cache kosong atau kadaluarsa. Mengambil API baru.");
            ambilHargaDariAPI("999", false); 
        }
    });
</script>   
    </body>
    </html>