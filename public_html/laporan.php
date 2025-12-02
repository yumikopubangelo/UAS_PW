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

// 3. AMBIL DATA USER
// PERBAIKAN: Ambil 'nama_karyawan', bukan 'NamaKaryawan'
$nama_karyawan = $_SESSION['nama_karyawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : ucfirst($role); // Dibuat dinamis
$logout_url = "logout.php";

// 4. PENGATURAN FILTER TANGGAL
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$end_date_sql = $end_date . ' 23:59:59';


// 5. AMBIL DATA TRANSAKSI
$transaksi_list = [];
$modal_lookup = [];

// --- PERBAIKAN KUERI MODAL ---
// Kita ambil modal untuk SEMUA barang yang terjual dalam transaksi di rentang tanggal
// Kita tidak lagi memfilter berdasarkan TipeTransaksi di sini.
$sql_modal = "SELECT 
                dtb.TransaksiID, SUM(bs.HargaBeliModal) AS total_modal
              FROM DETAIL_TRANSAKSI_BARANG AS dtb
              JOIN BARANG_STOK AS bs ON dtb.BarangID = bs.BarangID
              WHERE dtb.TransaksiID IN (
                  -- Ambil semua ID Transaksi yang ada di rentang tanggal
                  SELECT TransaksiID 
                  FROM TRANSAKSI
                  WHERE TanggalWaktu BETWEEN ? AND ?
              )
              GROUP BY dtb.TransaksiID"; // Group berdasarkan TransaksiID

$stmt_modal = $koneksi->prepare($sql_modal);
$stmt_modal->bind_param("ss", $start_date, $end_date_sql);
$stmt_modal->execute();
$result_modal = $stmt_modal->get_result();
if ($result_modal) {
    while ($row = $result_modal->fetch_assoc()) {
        $modal_lookup[$row['TransaksiID']] = $row['total_modal'];
    }
}
$stmt_modal->close();
// --- AKHIR PERBAIKAN ---

// -- KUERI 2: Ambil daftar Transaksi utama (Sudah Benar)
$sql_trans = "SELECT 
                t.TransaksiID, t.TanggalWaktu, t.TipeTransaksi, t.TotalTransaksi, t.TotalOngkos,
                k.NamaKaryawan,
                p.NamaPelanggan
              FROM TRANSAKSI AS t
              JOIN KARYAWAN AS k ON t.KaryawanID = k.KaryawanID
              LEFT JOIN PELANGGAN AS p ON t.PelangganID = p.PelangganID
              WHERE t.TanggalWaktu BETWEEN ? AND ?
              ORDER BY t.TanggalWaktu DESC";

$stmt_trans = $koneksi->prepare($sql_trans);
$stmt_trans->bind_param("ss", $start_date, $end_date_sql);
$stmt_trans->execute();
$result_trans = $stmt_trans->get_result();
if ($result_trans) {
    while ($row = $result_trans->fetch_assoc()) {
        $transaksi_list[] = $row;
    }
}
$stmt_trans->close();
$koneksi->close();


// 6. HITUNG SEMUA KARTU RINGKASAN (SUMMARY CARDS)
// (Logika perhitungan Anda sudah benar)
$total_omset_bruto = 0;
$total_modal_hpp = 0;
$total_laba_kotor = 0;
$total_transaksi_count = count($transaksi_list);

foreach ($transaksi_list as $row) {
    $id = $row['TransaksiID'];
    $total_nota = $row['TotalTransaksi'] + $row['TotalOngkos'];
    
    $tipe_transaksi_lower = strtolower($row['TipeTransaksi']);

    if ($tipe_transaksi_lower == 'penjualan') {
        $total_omset_bruto += $total_nota;
        // SEKARANG $modal_lookup[$id] AKAN ADA ISINYA
        $total_modal_hpp += $modal_lookup[$id] ?? 0;
    } 
    elseif ($tipe_transaksi_lower == 'jasa') {
        $total_omset_bruto += $row['TotalTransaksi']; 
        // Jasa tidak punya modal HPP, jadi 0
    }
    // Tipe 'buyback' diabaikan dari omset karena itu pengeluaran
}

$total_laba_kotor = $total_omset_bruto - $total_modal_hpp;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="laporan.css">
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
                    <li class="active"><a href="laporan.php">Laporan Penjualan</a></li>
                    <li>
                        <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                        <ul class="submenu" id="masterSubmenu" style="display: none;">
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li><a href="supplier.php">Supplier</a></li>
                            <li><a href="katalog_produk.php">Katalog Produk</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li><a href="harga.php">Update Harga Harian</a></li>
                <?php if ($is_admin_or_pemilik): ?>
                    <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="sidebar-overlay"></div>

        <main class="main-content">
            
            <header class="header">
                 <div class="header-title">
                    <h1>Laporan Penjualan</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                
                <form action="laporan.php" method="GET" class="report-filters">
                       <div class="filter-inputs">
                            <label for="start_date">Dari:</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            <label for="end_date">Sampai:</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            
                            <button type="submit">Terapkan Filter</button>
                       </div>
                       <div class="print-actions">
                            <button type="button" class="print-button" onclick="window.print()">Cetak Laporan</button>
                       </div>
                </form>

                <div class="report-summary">
                    <div class="summary-card">
                        <h3>Total Omset (Penjualan + Jasa)</h3>
                        <div class="value"><?php echo 'Rp ' . number_format($total_omset_bruto, 0, ',', '.'); ?></div> 
                    </div>
                    <div class="summary-card">
                        <h3>Total Modal (HPP)</h3>
                        <div class="value expense"><?php echo 'Rp ' . number_format($total_modal_hpp, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Laba Kotor</h3>
                        <div class="value profit"><?php echo 'Rp ' . number_format($total_laba_kotor, 0, ',', '.'); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Transaksi</h3>
                        <div class="value"><?php echo $total_transaksi_count; ?></div>
                    </div>
                </div>

                <div class="report-table-container">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th>Nota (ID)</th>
                                <th>Tipe</th>
                                <th>Pelanggan</th>
                                <th>Kasir</th>
                                <th>Total Nota</th>
                                <th>Laba / Rugi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksi_list)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Tidak ada data transaksi untuk rentang tanggal ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_list as $row): ?>
                                    <?php
                                        // (Logika perhitungan Anda di sini sudah benar)
                                        $total_nota = $row['TotalTransaksi'] + $row['TotalOngkos'];
                                        $laba = 0;
                                        $laba_class = '';
                                        $tipe_transaksi_lower_row = strtolower($row['TipeTransaksi']);

                                        if ($tipe_transaksi_lower_row == 'penjualan') {
                                            // DENGAN KUERI MODAL YANG BARU, INI AKAN SELALU BENAR
                                            $modal = $modal_lookup[$row['TransaksiID']] ?? 0;
                                            $laba = $total_nota - $modal;
                                            $laba_class = ($laba >= 0) ? 'col-profit' : 'col-loss';
                                        } 
                                        elseif ($tipe_transaksi_lower_row == 'jasa') {
                                            $laba = $total_nota; 
                                            $laba_class = 'col-profit';
                                        }
                                        elseif ($tipe_transaksi_lower_row == 'buyback') {
                                            // Buyback adalah pengeluaran, jadi laba-nya negatif
                                            $laba = -$total_nota; 
                                            $laba_class = 'col-loss';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo date("d-m-Y H:i", strtotime($row['TanggalWaktu'])); ?></td>
                                        <td>TRX-<?php echo $row['TransaksiID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['TipeTransaksi']); ?></td>
                                        <td><?php echo htmlspecialchars($row['NamaPelanggan'] ?? '(Umum)'); ?></td>
                                        <td><?php echo htmlspecialchars($row['NamaKaryawan']); ?></td>
                                        <td><?php echo 'Rp ' . number_format($total_nota, 0, ',', '.'); ?></td>
                                        <td class="<?php echo $laba_class; ?>"><?php echo 'Rp ' . number_format($laba, 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

<script>
    // Mobile menu button
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    // Buat tombol menu mobile
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-btn';
    menuBtn.innerHTML = '<span></span><span></span><span></span>';
    document.body.appendChild(menuBtn);

    // Toggle menu saat tombol diklik
    menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        // Hide hamburger button when menu is open
        menuBtn.style.display = sidebar.classList.contains('active') ? 'none' : 'block';
    });

    // Close menu when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            // Show hamburger button when menu is closed
            menuBtn.style.display = 'block';
        });
    }

    // Close menu on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
            // Show hamburger button when menu is closed
            menuBtn.style.display = 'block';
        }
    });

    // Submenu toggle
    var submenuToggle = document.querySelector('.has-submenu');
    if (submenuToggle) {
        submenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            let submenu = this.nextElementSibling;

            // Perbaikan: Buka/Tutup submenu Data Master
            if (submenu.style.display === 'block') {
                submenu.style.display = 'none';
            } else {
                submenu.style.display = 'block';
            }
        });
    }
</script>

</body>
</html>
