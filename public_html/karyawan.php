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

// 3. AMBIL DATA USER DARI SESSION (Nama yang BENAR)
// PERBAIKAN: Ambil 'nama_karyawan' dan 'karyawan_id'
$nama_karyawan = $_SESSION['nama_karyawan'];
$karyawan_id_login = $_SESSION['karyawan_id']; // <-- Ini adalah perbaikan untuk baris 19
$role_text = ($role == 'pemilik') ? 'Pemilik' : ucfirst($role); // Dibuat dinamis
$logout_url = "logout.php";

// 5. AMBIL SEMUA DATA KARYAWAN DARI DATABASE (untuk tabel)
$karyawan_list = [];
// Ambil semua kolom kecuali password
$sql = "SELECT KaryawanID, NamaKaryawan, Username, Role FROM KARYAWAN ORDER BY NamaKaryawan ASC"; 
$result = $koneksi->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $karyawan_list[] = $row;
    }
}

// Ambil pesan notifikasi jika ada
$success_message = $_SESSION['success_message'] ?? null;
$error_message_session = $_SESSION['error_message'] ?? null; // Ganti nama agar tidak konflik
unset($_SESSION['success_message'], $_SESSION['error_message']);

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Karyawan - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* (CSS Anda dari file lain sudah benar, saya salin) */
        .page-actions { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 1.5rem; }
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
        .btn-disabled { background-color: #6c757d; cursor: not-allowed; }
        
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        
        /* Style untuk Role */
        .role-text { padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
        .role-pemilik { background-color: #fdeeee; color: #dc3545; }
        .role-admin { background-color: #fff3cd; color: #856404; }
        .role-kasir { background-color: #e2e3e5; color: #383d41; }
        .role-karyawan { background-color: #e2e3e5; color: #383d41; }

        /* === CSS BARU UNTUK MODAL POP-UP === */
        .modal-overlay {
            display: none; /* Sembunyi secara default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Latar belakang gelap transparan */
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
            color: #dc3545; /* Merah */
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
            background-color: #6c757d; /* Abu-abu */
            color: white;
        }
        .btn-modal-cancel:hover {
            background-color: #5a6268;
        }
        .btn-modal-confirm {
            background-color: #dc3545; /* Merah */
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
                <li><a href="laporan.php">Laporan Penjualan</a></li>
                <li>
                    <a href="#masterSubmenu" class="has-submenu">Data Master</a>
                    <ul class="submenu" id="masterSubmenu" style="display: none;">
                        <li><a href="pelanggan.php">Pelanggan</a></li>
                        <li><a href="supplier.php">Supplier</a></li>
                        <li><a href="katalog_produk.php">Katalog Produk</a></li>
                    </ul>
                </li>
                <li class="admin-menu"><a href="harga.php">Update Harga Harian</a></li>
                <li class="admin-menu active"><a href="karyawan.php">Manajemen Karyawan</a></li>
            </ul>
        </nav>

        <main class="main-content">
            
            <header class="header">
                <div class="header-title">
                    <h1>Manajemen Karyawan</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo $role_text; ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                
                <?php
                // Tampilkan notifikasi
                if ($success_message) {
                    echo '<div class="message success">' . htmlspecialchars($success_message) . '</div>';
                }
                if ($error_message_session) {
                    echo '<div class="message error">' . htmlspecialchars($error_message_session) . '</div>';
                }
                ?>

                <div class="page-actions">
                    <a href="register.php?source=admin" class="btn-primary">+ Tambah Karyawan Baru</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Karyawan</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($karyawan_list)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Belum ada data karyawan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($karyawan_list as $k): ?>
                            <tr>
                                <td><?php echo $k['KaryawanID']; ?></td>
                                <td><?php echo htmlspecialchars($k['NamaKaryawan']); ?></td>
                                <td><?php echo htmlspecialchars($k['Username']); ?></td>
                                <td>
                                    <span class="role-text role-<?php echo strtolower(htmlspecialchars($k['Role'])); ?>">
                                        <?php echo ucfirst(htmlspecialchars($k['Role'])); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="karyawan_edit.php?id=<?php echo $k['KaryawanID']; ?>" class="btn-edit">Edit</a>
                                    
                                    <?php if ($k['KaryawanID'] == $karyawan_id_login): ?>
                                        <a href="#" class="btn-delete btn-disabled" onclick="alert('Anda tidak bisa menghapus akun Anda sendiri.'); return false;">Hapus</a>
                                    <?php else: ?>
                                        <a href="karyawan_hapus.php?id=<?php echo $k['KaryawanID']; ?>" 
                                           class="btn-delete" 
                                           onclick="showDeleteModal(event, '<?php echo addslashes($k['NamaKaryawan']); ?>')">
                                            Hapus
                                        </a>
                                        <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div> 
        </main> 
    </div> 

    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Konfirmasi Hapus</h2>
            <p>Anda yakin ingin menghapus karyawan <strong id="modal-item-name"></strong>?</p>
            <p style="color: #555; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirm-delete-button">Ya, Hapus</button>
            </div>
        </div>
    </div>
    <script>
        // Script untuk toggle submenu
        var submenuToggle = document.querySelector('.has-submenu');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                let submenu = this.nextElementSibling;
                submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
            });
        }

        // --- Logika Modal Hapus ---
        
        const deleteModal = document.getElementById('delete-modal');
        const itemNameSpan = document.getElementById('modal-item-name');
        const confirmDeleteButton = document.getElementById('confirm-delete-button');
        let deleteUrl = ''; // Variabel untuk menyimpan URL hapus

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