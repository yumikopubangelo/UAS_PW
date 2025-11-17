<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login
// PERBAIKAN: Menggunakan variabel session 'is_logged_in' dan 'role' (lowercase)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Simpan halaman tujuan agar login bisa kembali ke sini
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// 3. AMBIL DATA USER DARI SESSION
// PERBAIKAN: Menggunakan 'nama_karyawan' dan 'role' (lowercase)
$nama_karyawan = $_SESSION['nama_karyawan'];
$role = $_SESSION['role']; 

// 4. Buat variabel boolean untuk kemudahan pengecekan
$is_admin_or_pemilik = ($role == 'admin' || $role == 'pemilik'); // PERBAIKAN NAMA VARIABEL

// Tentukan teks role dengan lebih detail
if ($role == 'pemilik') {
    $role_text = 'Pemilik';
} elseif ($role == 'admin') {
    $role_text = 'Admin';
} else {
    $role_text = 'Kasir'; // PERBAIKAN: 'Karyawan' menjadi 'Kasir' agar konsisten
}
$logout_url = "logout.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Baru - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* (CSS Modal Anda) */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.show { display: flex; }
        .modal-content { background-color: #fff; border-radius: 10px; width: 90%; max-width: 800px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); overflow: hidden; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #eee; }
        .modal-header h3 { margin: 0; }
        .modal-close { font-size: 1.8rem; font-weight: bold; color: #888; background: none; border: none; cursor: pointer; }
        .modal-body { padding: 1.5rem; }
        #stok-modal-search { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; margin-bottom: 1rem; }
        .modal-list-container { max-height: 50vh; overflow-y: auto; border: 1px solid #eee; border-radius: 6px; }
        #stok-modal-table { width: 100%; border-collapse: collapse; }
        #stok-modal-table th, #stok-modal-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; text-align: left; }
        #stok-modal-table thead { background-color: #f8f9fa; position: sticky; top: 0; }
        #stok-modal-table tbody tr { cursor: pointer; }
        #stok-modal-table tbody tr:hover { background-color: #f5f5f5; }
        
        /* (CSS POS Anda) */
        .pos-layout { display: flex; gap: 1.5rem; }
        .pos-form { flex: 2; }
        .pos-summary { flex: 1; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); padding: 1.5rem; position: sticky; top: 2rem; height: fit-content; }
        fieldset { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        legend { font-weight: 600; color: #007bff; padding: 0 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .form-row { display: flex; gap: 1rem; }
        .form-row .form-group { flex: 1; }
        .action-button { padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #28a745; color: white; font-weight: 500; cursor: pointer; margin-top: 10px; }
        .action-button:hover { background-color: #218838; }
        #cart-items { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        #cart-items th, #cart-items td { padding: 0.75rem 0.5rem; text-align: left; border-bottom: 1px solid #eee; }
        #cart-items th { font-size: 0.9rem; color: #555; }
        #cart-items .item-name { font-weight: 600; }
        .summary-totals { margin-top: 1rem; border-top: 2px dashed #ccc; padding-top: 1rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 1rem; }
        .summary-row.grand-total { font-size: 1.5rem; font-weight: 700; color: #333; margin-top: 1rem; }
        .submit-button { width: 100%; padding: 1rem; font-size: 1.1rem; font-weight: 700; background-color: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .submit-button:hover { background-color: #0056b3; }
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
                <li class="active"><a href="transaksi_baru.php">Transaksi Baru (POS)</a></li>
                <li><a href="stok.php">Manajemen Stok</a></li>
                
                <!-- PERBAIKAN: Menggunakan $is_admin_or_pemilik -->
                <?php if ($is_admin_or_pemilik): ?>
                    <li><a href="laporan.php">Laporan Penjualan</a></li>
                    <li>
                        <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                        <ul class="submenu" id="masterSubmenu" style="display: none;"> <!-- Mulai tertutup -->
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li><a href="supplier.php">Supplier</a></li>
                            <li><a href="katalog_produk.php">Katalog Produk</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li><a href="harga.php">Update Harga Harian</a></li>
                <!-- PERBAIKAN: Menggunakan $is_admin_or_pemilik -->
                <?php if ($is_admin_or_pemilik): ?>
                    <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="main-content">
            
            <header class="header">
                <div class="header-title">
                    <h1>Transaksi Baru (Point of Sale)</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <form id="pos-form" action="proses_transaksi.php" method="POST">
                <div class="content-wrapper">
                    <div class="pos-layout">
                        
                        <div class="pos-form">
                            <fieldset>
                                <legend>Informasi Transaksi</legend>
                                <div class="form-row">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="tipe_transaksi">Tipe Transaksi</label>
                                        <select id="tipe_transaksi" name="tipe_transaksi">
                                            <option value="penjualan">Penjualan</option>
                                            <option value="buyback">BuyBack (Beli dari pelanggan)</option>
                                            <option value="jasa">Jasa (Cuci/Patri)</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex: 2;">
                                        <label for="pelanggan_nohp">Cari Pelanggan (via No. HP)</label>
                                        <div style="display: flex; gap: 5px;">
                                            <input type="text" id="pelanggan_nohp" placeholder="Masukkan No. HP...">
                                            <button type="button" id="btn_cari_pelanggan" style="padding: 0 1rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">Cari</button>
                                        </div>
                                        <input type="hidden" id="pelanggan_id_hidden" name="pelanggan_id">
                                        <span id="pelanggan_nama_display" style="font-weight: bold; color: green; margin-top: 5px; display: block;"></span>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset id="section-barang">
                                <legend>Input Barang</legend>
                                <div class="form-group">
                                    <label for="barang_id">Cari Barang (Kode/Scan Barcode)</label>
                                    <div style="display: flex; gap: 5px;">
                                        <input type="text" id="barang_id" placeholder="Masukkan Kode Barang..." style="flex: 1;">
                                        <button type="button" id="btn_browse_stok" style="padding: 0 1rem; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                            Cari...
                                        </button>
                                    </div>
                                    <input type="hidden" id="barang_id_int"> 
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><label>Nama Barang:</label><input type="text" id="barang_nama" readonly></div>
                                    <div class="form-group"><label>Kadar:</label><input type="text" id="barang_kadar" readonly></div>
                                    <div class="form-group"><label>Berat (gr):</label><input type="number" id="barang_berat" readonly></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="barang_harga">Harga Jual / Beli (Deal)</label>
                                        <input type="number" id="barang_harga" placeholder="Rp 0">
                                    </div>
                                    <div class="form-group">
                                        <label for="barang_ongkos">Ongkos Pembuatan</label>
                                        <input type="number" id="barang_ongkos" placeholder="Rp 0" value="0">
                                    </div>
                                </div>
                                <button type="button" id="btn_tambah_barang" class="action-button">Tambah Barang ke Nota</button>
                            </fieldset>

                            <fieldset id="section-jasa" style="display: none;">
                                <legend>Input Jasa</legend>
                                <div class="form-row">
                                    <div class="form-group" style="flex: 2;">
                                        <label for="jenis_jasa">Jenis Jasa</label>
                                        <select id="jenis_jasa">
                                            <option value="Cuci Emas">Cuci Emas</option>
                                            <option value="Patri/Solder">Patri/Solder</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label for="biaya_jasa">Biaya Jasa</label>
                                        <input type="number" id="biaya_jasa" placeholder="Rp 0" value="30000">
                                    </div>
                                </div>
                                <button type="button" id="btn_tambah_jasa" class="action-button">Tambah Jasa ke Nota</button>
                            </fieldset>
                        </div>

                        <div class="pos-summary">
                            <h3>Detail Transaksi (Nota)</h3>
                            <table id="cart-items">
                                <thead>
                                    <tr>
                                        <th>Deskripsi</th>
                                        <th>Info</th>
                                        <th>Harga</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-tbody">
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 20px; color: #888;">Belum ada barang</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="summary-row grand-total">
                                <span>GRAND TOTAL</span>
                                <span id="grand_total_span">Rp 0</span>
                            </div>
                            <fieldset style="border: none; padding: 0; margin-top: 1.5rem;">
                                <div class="form-group">
                                    <label for="metode_bayar">Metode Pembayaran</label>
                                    <select id="metode_bayar" name="metode_bayar">
                                        <option value="Tunai">Tunai</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="jumlah_bayar">Jumlah Bayar</label>
                                    <input type="number" id="jumlah_bayar" name="jumlah_bayar" placeholder="Rp 0">
                                </div>
                                <div class="summary-row" style="font-weight: 600;">
                                    <span>Kembalian</span>
                                    <span id="kembalian_span">Rp 0</span>
                                </div>
                            </fieldset>
                            <input type="hidden" id="detail_items_json" name="detail_items_json">
                            <button type="submit" class="submit-button">Selesaikan Transaksi</button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <!-- Modal Stok -->
    <div class="modal-overlay" id="stok-modal-overlay">
        <div class="modal-content">
            <header class="modal-header">
                <h3>Daftar Stok Tersedia</h3>
                <button type="button" class="modal-close" id="stok-modal-close">&times;</button>
            </header>
            <div class="modal-body">
                <input type="text" id="stok-modal-search" placeholder="Ketik untuk mencari (Nama, Kode, Kadar)...">
                <div class="modal-list-container">
                    <table id="stok-modal-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kadar</th>
                                <th>Berat (gr)</th>
                            </tr>
                        </thead>
                        <tbody id="stok-modal-list">
                            <tr><td colspan="4">Memuat data stok...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // === (A) Variabel Global & Fungsi Helper ===
    let cartItems = [];
    
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    };

    // === (B) LOGIKA PENCARIAN (Cari Barang & Pelanggan) ===

    // --- Pencarian Barang (via Enter) ---
    document.getElementById('barang_id').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault(); 
            const kodeBarang = this.value;
            if (!kodeBarang) return; 
            
            cariBarangDenganKode(kodeBarang);
        }
    });
    
    // --- Fungsi Helper: Cari Barang (DIPAKAI OLEH ENTER & MODAL) ---
    function cariBarangDenganKode(kodeBarang) {
        const tipe = document.getElementById('tipe_transaksi').value; 
        
        fetch(`api_cari_barang.php?kode=${kodeBarang}&tipe=${tipe}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    kosongkanFieldBarang();
                } else {
                    isiFieldBarangInfo(data);
                    
                    let hargaPerGram = 0;
                    if (tipe === 'penjualan') {
                        hargaPerGram = parseFloat(data.HargaJualPerGram) || 0;
                    } else if (tipe === 'buyback') {
                        hargaPerGram = parseFloat(data.HargaBeliPerGram) || 0;
                    }

                    const berat = parseFloat(data.BeratGram);
                    if (hargaPerGram === 0 || !hargaPerGram) {
                        alert(`PERINGATAN: Harga harian (${tipe}) untuk kadar ${data.Kadar} belum di-set. Harap isi harga deal manual.`);
                        document.getElementById('barang_harga').value = '';
                        document.getElementById('barang_harga').focus(); 
                    } else {
                        const hargaDeal = Math.round(hargaPerGram * berat);
                        document.getElementById('barang_harga').value = hargaDeal;
                        document.getElementById('barang_ongkos').focus(); 
                    }
                }
            })
            .catch(err => alert('Terjadi kesalahan jaringan saat mencari barang.'));
    }
    
    // --- Fungsi Helper: HANYA Mengisi Info Barang ---
    function isiFieldBarangInfo(data) {
        document.getElementById('barang_id').value = data.KodeBarang; 
        document.getElementById('barang_id_int').value = data.BarangID; 
        document.getElementById('barang_nama').value = data.NamaProduk;
        document.getElementById('barang_kadar').value = data.Kadar;
        document.getElementById('barang_berat').value = data.BeratGram;
    }

    // --- Fungsi Helper: Kosongkan Field Barang ---
    function kosongkanFieldBarang() {
        document.getElementById('barang_id').value = '';
        document.getElementById('barang_id_int').value = ''; 
        document.getElementById('barang_nama').value = '';
        document.getElementById('barang_kadar').value = '';
        document.getElementById('barang_berat').value = '';
        document.getElementById('barang_harga').value = '';
        document.getElementById('barang_ongkos').value = '0';
    }

    // --- Pencarian Pelanggan ---
    document.getElementById('btn_cari_pelanggan').addEventListener('click', function() {
        const noHp = document.getElementById('pelanggan_nohp').value;
        if (!noHp) return;
        fetch(`api_cari_pelanggan.php?no_hp=${noHp}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    document.getElementById('pelanggan_nama_display').innerText = '';
                    document.getElementById('pelanggan_id_hidden').value = '';
                } else {
                    document.getElementById('pelanggan_nama_display').innerText = `Pelanggan: ${data.NamaPelanggan}`;
                    document.getElementById('pelanggan_id_hidden').value = data.PelangganID;
                }
            })
            .catch(err => alert('Terjadi kesalahan jaringan saat mencari pelanggan.'));
    });

    // === (C) LOGIKA NOTA (Tambah ke Nota & Render) ===
    function renderCart() {
        const cartTbody = document.getElementById('cart-tbody');
        const grandTotalSpan = document.getElementById('grand_total_span');
        cartTbody.innerHTML = '';
        if (cartItems.length === 0) {
            cartTbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #888;">Belum ada barang</td></tr>';
        }
        let grandTotal = 0;
        cartItems.forEach((item, index) => {
            let itemHtml = '';
            if (item.tipe === 'barang') {
                itemHtml = `
                    <tr>
                        <td class="item-name">${item.nama}<br><small>+ Ongkos</small></td>
                        <td>${item.berat} gr</td>
                        <td>${formatRupiah(item.harga)}<br><small>${formatRupiah(item.ongkos)}</small></td>
                    </tr>
                `;
                grandTotal += item.harga + item.ongkos;
            } else if (item.tipe === 'jasa') {
                itemHtml = `
                    <tr>
                        <td class="item-name">${item.nama}</td>
                        <td>-</td>
                        <td>${formatRupiah(item.harga)}</td>
                    </tr>
                `;
                grandTotal += item.harga;
            }
            cartTbody.innerHTML += itemHtml;
        });
        grandTotalSpan.innerText = formatRupiah(grandTotal);
        grandTotalSpan.dataset.total = grandTotal; 
    }
    document.getElementById('btn_tambah_barang').addEventListener('click', function() {
        const item = {
            tipe: 'barang',
            barang_id_int: document.getElementById('barang_id_int').value,
            kode_barang: document.getElementById('barang_id').value,
            nama: document.getElementById('barang_nama').value,
            kadar: document.getElementById('barang_kadar').value,
            berat: parseFloat(document.getElementById('barang_berat').value) || 0,
            harga: parseFloat(document.getElementById('barang_harga').value) || 0,
            ongkos: parseFloat(document.getElementById('barang_ongkos').value) || 0
        };
        if (!item.barang_id_int) {
            alert('Barang tidak valid! Harap cari barang berdasarkan KODE dan tekan ENTER terlebih dahulu.');
            return;
        }
        if (!item.nama || item.harga === 0) {
            alert('Nama Barang dan Harga Deal wajib diisi!');
            return;
        }
        cartItems.push(item);
        renderCart();
        kosongkanFieldBarang(); 
    });
    document.getElementById('btn_tambah_jasa').addEventListener('click', function() {
        const item = {
            tipe: 'jasa',
            nama: document.getElementById('jenis_jasa').value,
            harga: parseFloat(document.getElementById('biaya_jasa').value) || 0
        };
        if (item.harga === 0) {
            alert('Biaya Jasa harus diisi!');
            return;
        }
        cartItems.push(item);
        renderCart();
        document.getElementById('biaya_jasa').value = '30000'; // Reset ke default
    });

    // === (D) LOGIKA MODAL STOK ===
    const modalOverlay = document.getElementById('stok-modal-overlay');
    const modalListBody = document.getElementById('stok-modal-list');
    let allStokItems = []; 
    document.getElementById('btn_browse_stok').addEventListener('click', function() {
        modalOverlay.classList.add('show');
        // Selalu fetch data baru setiap kali modal dibuka
        modalListBody.innerHTML = '<tr><td colspan="4">Memuat data stok...</td></tr>';
        fetch('api_get_stok_tersedia.php')
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    return;
                }
                allStokItems = data; 
                renderModalList(allStokItems);
            })
            .catch(err => {
                modalListBody.innerHTML = '<tr><td colspan="4" style="color: red;">Gagal memuat data...</td></tr>';
            });
    });
    function renderModalList(items) {
        modalListBody.innerHTML = ''; 
        if (items.length === 0) {
            modalListBody.innerHTML = '<tr><td colspan="4">Tidak ada stok tersedia.</td></tr>';
            return;
        }
        items.forEach(item => {
            const row = document.createElement('tr');
            row.dataset.kode = item.KodeBarang; 
            
            row.innerHTML = `
                <td>${item.KodeBarang}</td>
                <td>${item.NamaProduk} (${item.Tipe})</td>
                <td>${item.Kadar}</td>
                <td>${item.BeratGram} gr</td>
            `;
            modalListBody.appendChild(row);
        });
    }
    document.getElementById('stok-modal-close').addEventListener('click', function() {
        modalOverlay.classList.remove('show');
    });
    document.getElementById('stok-modal-search').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const filteredItems = allStokItems.filter(item => {
            return item.NamaProduk.toLowerCase().includes(searchText) ||
                   item.KodeBarang.toLowerCase().includes(searchText) ||
                   item.Kadar.toLowerCase().includes(searchText);
        });
        renderModalList(filteredItems);
    });
    modalListBody.addEventListener('click', function(e) {
        const clickedRow = e.target.closest('tr'); 
        if (!clickedRow || !clickedRow.dataset.kode) return; 
        
        const kodeBarang = clickedRow.dataset.kode;
        
        cariBarangDenganKode(kodeBarang);
        
        modalOverlay.classList.remove('show');
    });

    // === (E) LOGIKA LAINNYA (Pembayaran, Submit, Toggle Menu) ===
    document.getElementById('jumlah_bayar').addEventListener('input', function() {
        const jumlahBayar = parseFloat(this.value) || 0;
        const grandTotal = parseFloat(document.getElementById('grand_total_span').dataset.total) || 0;
        const kembalianSpan = document.getElementById('kembalian_span');
        const kembalian = jumlahBayar - grandTotal;
        kembalianSpan.innerText = (kembalian >= 0) ? formatRupiah(kembalian) : 'Rp 0';
    });
    document.getElementById('pos-form').addEventListener('submit', function(e) {
        if (cartItems.length === 0) {
            alert('Nota masih kosong! Silakan tambah barang atau jasa terlebih dahulu.');
            e.preventDefault(); 
            return;
        }
        document.getElementById('detail_items_json').value = JSON.stringify(cartItems);
    });
    document.getElementById('tipe_transaksi').addEventListener('change', function() {
        const tipe = this.value;
        const sectionBarang = document.getElementById('section-barang');
        const sectionJasa = document.getElementById('section-jasa');
        sectionBarang.style.display = (tipe === 'penjualan' || tipe === 'buyback') ? 'block' : 'none';
        sectionJasa.style.display = (tipe === 'jasa') ? 'block' : 'none';
    });
    var submenuToggle = document.querySelector('.has-submenu');
    if (submenuToggle) {
        submenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            let submenu = this.nextElementSibling;
            submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
        });
    }
</script>

</body>
</html>
