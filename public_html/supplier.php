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
$is_admin_or_pemilik = ($role == 'admin' || $role == 'pemilik');

// 3. AMBIL DATA USER
// PERBAIKAN: Ambil 'nama_karyawan', bukan 'NamaKaryawan'
$nama_karyawan = $_SESSION['nama_karyawan'];
$role_text = ($role == 'pemilik') ? 'Pemilik' : ucfirst($role); // Dibuat dinamis
$logout_url = "logout.php";

// 4. AMBIL SEMUA DATA SUPPLIER DARI DATABASE
$supplier_list = [];
$sql = "SELECT SupplierID, NamaSupplier, Kontak, Alamat 
        FROM SUPPLIER 
        ORDER BY NamaSupplier ASC";

$result = $koneksi->query($sql);
if ($result && $result->num_rows > 0) { // Ditambah cek $result
    while ($row = $result->fetch_assoc()) {
        $supplier_list[] = $row;
    }
}

// Ambil pesan notifikasi jika ada
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$koneksi->close();

// Hitung colspan (untuk baris 'no results')
$colspan = 5; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Supplier - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="supplier.css">
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
                
                <?php if ($is_admin_or_pemilik): ?>
                    <li><a href="laporan.php">Laporan Penjualan</a></li>
                    <li>
                        <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                        <ul class="submenu" id="masterSubmenu" style="display: block;"> 
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li class="active-sub"><a href="supplier.php">Supplier</a></li> 
                            <li><a href="katalog_produk.php">Katalog Produk</a></li>
                        </ul>
                    </li>
                    <li><a href="harga.php">Update Harga Harian</a></li>
                    <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                <?php endif; ?>
                
            </ul>
        </nav>

        <div class="sidebar-overlay"></div>

        <main class="main-content">
            
            <header class="header">
                <div class="header-title">
                    <h1>Data Master Supplier</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                
                <?php if ($success_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <div class="page-actions">
                    <div class="filter-group">
                        <input type="text" id="searchInput" placeholder="Cari berdasarkan Nama Supplier...">
                    </div>
                    <a href="supplier_baru.php" class="btn-primary">+ Tambah Supplier Baru</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Supplier</th>
                            <th>Nama Supplier</th>
                            <th>Kontak (No. HP)</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <?php if (empty($supplier_list)): ?>
                            <tr>
                                <td colspan="<?php echo $colspan; ?>" style="text-align: center;">Belum ada data supplier.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($supplier_list as $supplier): ?>
                            <tr>
                                <td>SUP-<?php echo $supplier['SupplierID']; ?></td>
                                <td><?php echo htmlspecialchars($supplier['NamaSupplier']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['Kontak'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['Alamat'] ?? '-'); ?></td>
                                <td class="action-buttons">
                                    <a href="supplier_edit.php?id=<?php echo $supplier['SupplierID']; ?>" class="btn-edit">Edit</a>
                                    
                                    <a href="proses_supplier.php?action=delete&id=<?php echo $supplier['SupplierID']; ?>" 
                                       class="btn-delete" 
                                       onclick="showDeleteModal(event, '<?php echo addslashes($supplier['NamaSupplier']); ?>')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <tr id="noResultsRow" class="no-results">
                            <td colspan="<?php echo $colspan; ?>">
                                Tidak ada supplier yang cocok dengan pencarian Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div> 
        </main> 
    </div> 

    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Konfirmasi Hapus</h2>
            <p>Anda yakin ingin menghapus supplier <strong id="modal-item-name"></strong>?</p>
            <p style="color: #555; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirm-delete-button">Ya, Hapus</button>
            </div>
        </div>
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

        // Script untuk toggle submenu
        document.querySelector('.has-submenu').addEventListener('click', function(e) {
            e.preventDefault();
            let submenu = this.nextElementSibling;

            // Toggle hanya jika bukan halaman aktif
            if (!this.parentElement.querySelector('.submenu li.active-sub')) {
                submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
            }
        });

        // --- (BARU) Logika Search Filter ---
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('supplierTableBody');
        const dataRows = tableBody.querySelectorAll('tr:not(#noResultsRow)'); // Ambil semua baris data
        const noResultsRow = document.getElementById('noResultsRow');

        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toUpperCase();
            let matchesFound = 0;

            dataRows.forEach(row => {
                // Ambil semua teks dari baris
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


        // --- Logika Modal Hapus (Sudah ada) ---
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

    </script>
    </body>
</html>
