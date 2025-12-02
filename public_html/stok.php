<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login
// PERBAIKAN: Menggunakan variabel session 'is_logged_in' dan 'role' (lowercase)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role']; // Menggunakan 'role' (lowercase)

// 3. AMBIL DATA USER
// PERBAIKAN: Menggunakan 'nama_karyawan' (lowercase)
$nama_karyawan = $_SESSION['nama_karyawan']; 

// 4. LOGIKA ROLE (SANGAT PENTING)
$is_admin_or_pemilik = ($role == 'admin' || $role == 'pemilik');

// Tentukan teks role
if ($role == 'pemilik') {
    $role_text = 'Pemilik';
} elseif ($role == 'admin') {
    $role_text = 'Admin';
} else {
    $role_text = 'Kasir';
}

$logout_url = "logout.php";

// 5. AMBIL DATA STOK DARI DATABASE (DENGAN JOIN)
$stok_list = [];

// Kueri JOIN Anda sudah benar dan bagus
$sql = "SELECT 
            bs.BarangID, bs.KodeBarang, bs.BeratGram, bs.HargaBeliModal, bs.TanggalMasuk, bs.AsalBarang, bs.Status,
            pk.NamaProduk, pk.Kadar, pk.Tipe
        FROM BARANG_STOK AS bs
        JOIN PRODUK_KATALOG AS pk ON bs.ProdukKatalogID = pk.ProdukKatalogID
        ORDER BY bs.TanggalMasuk DESC";

// PERBAIKAN: Tambah cek jika $result false
$result = $koneksi->query($sql);
if ($result && $result->num_rows > 0) { 
    while ($row = $result->fetch_assoc()) {
        $stok_list[] = $row;
    }
}

// Ambil pesan notifikasi jika ada
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$koneksi->close();

// Hitung colspan untuk tabel (diletakkan di sini agar rapi)
$colspan = 8; // Kolom dasar (Kode, Nama, Tipe, Kadar, Berat, Tgl Masuk, Asal, Status)
if ($is_admin_or_pemilik) {
    $colspan += 2; // Tambah 2 (Modal HPP dan Aksi)
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="stok.css">
    <style>
        .page-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .filter-group input { padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; min-width: 250px; }
        .btn-primary { padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #007bff; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; }
        .btn-primary:hover { background-color: #0056b3; }
        .stock-table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
        .stock-table th, .stock-table td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .stock-table thead { background-color: #f8f9fa; }
        .stock-table th { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; }
        .stock-table tbody tr:hover { background-color: #f5f5f5; }
        .stock-table td { color: #333; }
        .col-modal { color: #e74c3c; font-weight: 600; }
        .status { padding: 0.25rem 0.6rem; border-radius: 15px; font-size: 0.85rem; font-weight: 600; text-align: center; }
        .status-tersedia { background-color: #e4f8e9; color: #28a745; }
        .status-terjual { background-color: #fdeeee; color: #dc3545; }
        
        /* === PERBAIKAN CSS TOMBOL AKSI === */
        .stock-table td.action-buttons { 
            display: flex; 
            align-items: center; 
            gap: 8px; /* Memberi jarak modern */
            padding-top: 0.8rem; /* Sesuaikan padding agar rapi */
            padding-bottom: 0.8rem;
        }
        .action-buttons .btn-edit, .action-buttons .btn-delete { 
            padding: 0.4rem 0.8rem; 
            margin-right: 0; /* Hapus margin lama */
            text-decoration: none; color: white; border: none; 
            border-radius: 5px; cursor: pointer; 
        }
        .btn-edit { background-color: #ffc107; }
        .btn-delete { background-color: #dc3545; }
        /* === AKHIR PERBAIKAN CSS === */

        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        
        /* === CSS BARU UNTUK SEARCH === */
        .no-results {
            display: none; /* Sembunyi by default */
            text-align: center;
            font-style: italic;
            color: #777;
        }
        /* === AKHIR CSS SEARCH === */

        /* === CSS MODAL POP-UP === */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.3s;
        }
        .modal-content h2 { margin-top: 0; color: #dc3545; }
        .modal-buttons { display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
        .modal-buttons button { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .btn-modal-cancel { background-color: #6c757d; color: white; }
        .btn-modal-cancel:hover { background-color: #5a6268; }
        .btn-modal-confirm { background-color: #dc3545; color: white; }
        .btn-modal-confirm:hover { background-color: #c82333; }
        @keyframes fadeIn {
            from {opacity: 0; transform: scale(0.9);}
            to {opacity: 1; transform: scale(1);}
        }
        /* === AKHIR CSS MODAL === */
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
                <li class="active"><a href="stok.php">Manajemen Stok</a></li>
                
                <?php if ($is_admin_or_pemilik): ?>
                    <li><a href="laporan.php">Laporan Penjualan</a></li>
                    
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

        <main class="main-content">
            
            <header class="header">
                <div class="header-title">
                    <h1>Manajemen Stok</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan ?? ''); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                
                <?php
                if ($success_message) {
                    echo '<div class="message success">' . htmlspecialchars($success_message ?? '') . '</div>';
                }
                if ($error_message) {
                    echo '<div class="message error">' . htmlspecialchars($error_message ?? '') . '</div>';
                }
                ?>

                <div class="page-actions">
                    <div class="filter-group">
                        <input type="text" id="searchInput" placeholder="Cari berdasarkan Nama, Kode, atau Kadar...">
                    </div>
                    <?php if ($is_admin_or_pemilik): ?>
                        <a href="stok_baru.php" class="btn-primary">+ Tambah Stok Baru</a>
                    <?php endif; ?>
                </div>

                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Tipe</th>
                            <th>Kadar</th>
                            <th>Berat (gr)</th>
                            
                            <?php if ($is_admin_or_pemilik): ?>
                                <th class="col-modal">Modal (HPP)</th>
                            <?php endif; ?>
                            
                            <th>Tgl. Masuk</th>
                            <th>Asal Barang</th>
                            <th>Status</th>
                            
                            <?php if ($is_admin_or_pemilik): ?>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="stokTableBody">
                        <?php if (empty($stok_list)): ?>
                            <tr>
                                <td colspan="<?php echo $colspan; ?>" style="text-align: center;">Belum ada data stok.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stok_list as $item): ?>
                            <tr>
    <td data-label="Kode"><?php echo htmlspecialchars($item['KodeBarang']?? ''); ?></td>
    <td data-label="Nama Barang"><?php echo htmlspecialchars($item['NamaProduk']?? ''); ?></td>
    <td data-label="Tipe"><?php echo htmlspecialchars($item['Tipe']?? ''); ?></td>
    <td data-label="Kadar"><?php echo htmlspecialchars($item['Kadar']?? ''); ?></td>
    <td data-label="Berat"><?php echo number_format($item['BeratGram'], 2, ',', '.' ?? ''); ?> gr</td>
    
    <?php if ($is_admin_or_pemilik): ?>
        <td data-label="Modal (HPP)" class="col-modal">
            <?php echo 'Rp ' . number_format($item['HargaBeliModal'], 0, ',', '.'); ?>
        </td>
    <?php endif; ?>
    
    <td data-label="Tgl. Masuk"><?php echo date("d-m-Y", strtotime($item['TanggalMasuk'])); ?></td>
    <td data-label="Asal Barang"><?php echo htmlspecialchars($item['AsalBarang']); ?></td>
    <td data-label="Status">
        <?php if ($item['Status'] == 'Tersedia'): ?>
            <span class="status status-tersedia">Tersedia</span>
        <?php else: ?>
            <span class="status status-terjual">Terjual</span>
        <?php endif; ?>
    </td>
    
    <?php if ($is_admin_or_pemilik): ?>
        <td class="action-buttons">
            <?php if ($item['Status'] == 'Tersedia'): ?>
                <a href="stok_edit.php?kode=<?php echo $item['KodeBarang']; ?>" class="btn-edit">Edit</a>
                <a href="stok_hapus.php?kode=<?php echo $item['KodeBarang']; ?>" 
                   class="btn-delete" 
                   onclick="showDeleteModal(event, '<?php echo addslashes($item['NamaProduk'] . ' (' . $item['KodeBarang'] . ')'); ?>')">
                    Hapus
                </a>
            <?php endif; ?>
        </td>
    <?php endif; ?>
</tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <tr id="noResultsRow" class="no-results">
                            <td colspan="<?php echo $colspan; ?>">
                                Tidak ada data stok yang cocok dengan pencarian Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </main>
    </div>

    <!-- === HTML MODAL POP-UP === -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Konfirmasi Hapus</h2>
            <p>Anda yakin ingin menghapus barang <strong id="modal-item-name"></strong>?</p>
            <p style="color: #555; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirm-delete-button">Ya, Hapus</button>
            </div>
        </div>
    </div>
    <!-- === AKHIR DARI HTML MODAL === -->


    <!-- === JAVASCRIPT (Search + Modal) === -->
    <script>
        // --- Logika Submenu ---
        var submenuToggle = document.querySelector('.has-submenu');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
            });
        }

        // --- Logika Search Filter ---
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('stokTableBody');
        const dataRows = tableBody.querySelectorAll('tr:not(#noResultsRow)'); // Ambil semua baris data
        const noResultsRow = document.getElementById('noResultsRow');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toUpperCase();
            let matchesFound = 0;

            dataRows.forEach(row => {
                const rowText = row.textContent || row.innerText;
                if (rowText.toUpperCase().indexOf(searchTerm) > -1) {
                    row.style.display = ""; // Tampilkan baris
                    matchesFound++;
                } else {
                    row.style.display = "none"; // Sembunyikan baris
                }
            });

            // Tampilkan/sembunyikan pesan "Tidak Ditemukan"
            if (matchesFound === 0 && dataRows.length > 0) {
                noResultsRow.style.display = "table-row";
            } else {
                noResultsRow.style.display = "none";
            }
        });


        // --- Logika Modal Hapus ---
        const deleteModal = document.getElementById('delete-modal');
        const itemNameSpan = document.getElementById('modal-item-name');
        const confirmDeleteButton = document.getElementById('confirm-delete-button');
        let deleteUrl = ''; 

        function showDeleteModal(event, itemName) {
            event.preventDefault(); 
            deleteUrl = event.currentTarget.href;
            itemNameSpan.textContent = itemName;
            deleteModal.style.display = 'flex';
        }

        function closeModal() {
            deleteModal.style.display = 'none';
            deleteUrl = ''; 
        }

        confirmDeleteButton.addEventListener('click', function() {
            if (deleteUrl) {
                window.location.href = deleteUrl;
            }
        });

        window.addEventListener('click', function(event) {
            if (event.target == deleteModal) {
                closeModal();
            }
        });
        // === Mobile Menu Handler ===
document.addEventListener('DOMContentLoaded', function() {
    // Buat tombol menu mobile
    const menuBtn = document.createElement('button');
    menuBtn.className = 'mobile-menu-btn';
    menuBtn.innerHTML = '<span></span><span></span><span></span>';
    document.body.appendChild(menuBtn);

    // Buat overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    const sidebar = document.querySelector('.sidebar');

    // Toggle menu
    menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        menuBtn.style.display = sidebar.classList.contains('active') ? 'none' : 'block';
    });

    // Tutup menu saat overlay diklik
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        menuBtn.style.display = 'block';
    });

    // Tutup menu saat link diklik
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a:not(.has-submenu)');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                menuBtn.style.display = 'block';
            }
        });
    });

    // Handle resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                menuBtn.style.display = 'block';
            }
        }, 250);
    });
});
    </script>
    <!-- === AKHIR DARI JAVASCRIPT === -->

</body>
</html>