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

// 4. AMBIL SEMUA DATA PELANGGAN DARI DATABASE
$pelanggan_list = [];
$sql = "SELECT PelangganID, NamaPelanggan, NoHP, Alamat 
        FROM PELANGGAN 
        ORDER BY NamaPelanggan ASC";

$result = $koneksi->query($sql);
if ($result && $result->num_rows > 0) { // Ditambah cek $result
    while ($row = $result->fetch_assoc()) {
        $pelanggan_list[] = $row;
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
    <title>Data Pelanggan - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">

    <style>
        .page-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .filter-group input { padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; min-width: 250px; }
        .btn-primary { padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #007bff; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; }
        .btn-primary:hover { background-color: #0056b3; }
        .data-table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
        .data-table th, .data-table td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .data-table thead { background-color: #f8f9fa; }
        .data-table th { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; }
        .data-table tbody tr:hover { background-color: #f5f5f5; }
        .data-table td { color: #333; }
        
        /* PERBAIKAN: CSS Tombol Aksi dirapikan */
        .data-table td.action-buttons { 
            display: flex; 
            align-items: center; 
            gap: 8px;
        }
        .action-buttons .btn-edit, .action-buttons .btn-delete { 
            padding: 0.4rem 0.8rem; 
            margin-right: 0; /* Hapus margin-right: 5px */
            text-decoration: none; color: white; border: none; 
            border-radius: 5px; cursor: pointer; 
        }
        .btn-edit { background-color: #ffc107; } 
        .btn-delete { background-color: #dc3545; }
        
        /* Feedback Message Style */
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

        /* === CSS MODAL POP-UP (Sudah ada) === */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); 
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 450px;
            text-align: center;
            animation: fadeIn 0.3s;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #dc3545; 
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .modal-buttons button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-modal-cancel {
            background-color: #6c757d; 
            color: white;
        }
        .btn-modal-cancel:hover {
            background-color: #5a6268;
        }
        .btn-modal-confirm {
            background-color: #dc3545; 
            color: white;
        }
        .btn-modal-confirm:hover {
            background-color: #c82333;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: scale(0.9);}
            to {opacity: 1; transform: scale(1);}
        }
        /* === AKHIR DARI CSS MODAL === */
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
                        <ul class="submenu" id="masterSubmenu" style="display: block;"> <li class="active-sub"><a href="pelanggan.php">Pelanggan</a></li>
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
                    <h1>Data Master Pelanggan</h1>
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
                        <input type="text" id="searchInput" placeholder="Cari berdasarkan Nama atau No. HP...">
                    </div>
                    <a href="pelanggan_baru.php" class="btn-primary">+ Tambah Pelanggan Baru</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Pelanggan</th>
                            <th>Nama Pelanggan</th>
                            <th>No. HP</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pelangganTableBody">
                        <?php if (empty($pelanggan_list)): ?>
                            <tr>
                                <td colspan="<?php echo $colspan; ?>" style="text-align: center;">Belum ada data pelanggan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pelanggan_list as $pelanggan): ?>
                            <tr>
                                <td>PEL-<?php echo $pelanggan['PelangganID']; ?></td>
                                <td><?php echo htmlspecialchars($pelanggan['NamaPelanggan']); ?></td>
                                <td><?php echo htmlspecialchars($pelanggan['NoHP'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($pelanggan['Alamat'] ?? '-'); ?></td>
                                <td class="action-buttons">
                                    <a href="pelanggan_edit.php?id=<?php echo $pelanggan['PelangganID']; ?>" class="btn-edit">Edit</a>
                                    
                                    <a href="proses_pelanggan.php?action=delete&id=<?php echo $pelanggan['PelangganID']; ?>" 
                                       class="btn-delete" 
                                       onclick="showDeleteModal(event, '<?php echo addslashes($pelanggan['NamaPelanggan']); ?>')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <tr id="noResultsRow" class="no-results">
                            <td colspan="<?php echo $colspan; ?>">
                                Tidak ada pelanggan yang cocok dengan pencarian Anda.
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
            <p>Anda yakin ingin menghapus pelanggan <strong id="modal-customer-name"></strong>?</p>
            <p style="color: #555; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirm-delete-button">Ya, Hapus</button>
            </div>
        </div>
    </div>
    <script>
        // Script untuk toggle submenu (kode Anda sebelumnya)
        document.querySelector('.has-submenu').addEventListener('click', function(e) {
            e.preventDefault();
            let submenu = this.nextElementSibling;
            
            // Perbaikan: Buka/Tutup submenu Data Master
            if (submenu.style.display === 'block') {
                submenu.style.display = 'none';
            } else {
                submenu.style.display = 'block';
            }
        });

        // --- (BARU) Logika Search Filter ---
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('pelangganTableBody');
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
        const customerNameSpan = document.getElementById('modal-customer-name');
        const confirmDeleteButton = document.getElementById('confirm-delete-button');
        let deleteUrl = ''; 

        function showDeleteModal(event, customerName) {
            event.preventDefault(); 
            deleteUrl = event.currentTarget.href;
            customerNameSpan.textContent = customerName;
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
