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
    <link rel="stylesheet" href="karyawan.css">
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

        <div class="sidebar-overlay"></div>

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