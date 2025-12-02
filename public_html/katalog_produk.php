<?php
// 1. MULAI SESSION & KONEKSI
session_start();
include 'koneksi.php';

// 2. KEAMANAN: Cek login & Role (Menggunakan nama session yang BENAR)
// PERBAIKAN: Cek 'is_logged_in', bukan 'index'
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    
    // Tambahan: Simpan halaman tujuan agar login bisa kembali ke sini
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu untuk mengakses halaman ini.";
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
$role_text = ($role == 'pemilik') ? 'Pemilik' : ucfirst($role); // Dibuat dinamis (Pemilik/Admin/Kasir)
$logout_url = "logout.php";

// 4. AMBIL SEMUA DATA KATALOG DARI DATABASE
$katalog_list = [];
$sql = "SELECT ProdukKatalogID, NamaProduk, Tipe, Kadar, Satuan 
        FROM PRODUK_KATALOG 
        ORDER BY NamaProduk ASC";

$result = $koneksi->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $katalog_list[] = $row;
    }
}

// Ambil pesan notifikasi jika ada
$success_message = $_SESSION['success_message'] ?? null;
$error_message_from_session = $_SESSION['error_message'] ?? null; // Ganti nama agar tidak konflik
unset($_SESSION['success_message'], $_SESSION['error_message']);

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Katalog Produk</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="katalog.css">
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
                        <ul class="submenu" id="masterSubmenu" style="display: block;">
                            <li><a href="pelanggan.php">Pelanggan</a></li>
                            <li><a href="supplier.php">Supplier</a></li>
                            <li class="active"><a href="katalog_produk.php">Katalog Produk</a></li> 
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
                    <h1>Manajemen Katalog Produk</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <div class="content-wrapper">
                
                <?php
                // Tampilkan pesan notifikasi
                if ($success_message) {
                    echo '<div class="message success">' . htmlspecialchars($success_message) . '</div>';
                }
                if ($error_message_from_session) {
                    echo '<div class="message error">' . htmlspecialchars($error_message_from_session) . '</div>';
                }
                ?>

                <div class="page-actions">
                    <div></div> <!-- Ini untuk 'Search' jika nanti Anda tambahkan -->
                    <a href="katalog_baru.php" class="btn-primary">+ Tambah Katalog Baru</a>
                </div>

                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Tipe</th>
                            <th>Kadar</th>
                            <th>Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($katalog_list)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Belum ada data katalog produk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($katalog_list as $produk): ?>
                            <tr>
                                <td><?php echo $produk['ProdukKatalogID']; ?></td>
                                <td><?php echo htmlspecialchars($produk['NamaProduk']); ?></td>
                                <td><?php echo htmlspecialchars($produk['Tipe']); ?></td>
                                <td><?php echo htmlspecialchars($produk['Kadar']); ?></td>
                                <td><?php echo htmlspecialchars($produk['Satuan']); ?></td>
                                <td class="action-buttons">
                                    <a href="katalog_edit.php?id=<?php echo $produk['ProdukKatalogID']; ?>" class="btn-edit">Edit</a>
                                    
                                    <!-- === MODIFIKASI TOMBOL HAPUS === -->
                                    <a href="katalog_hapus.php?id=<?php echo $produk['ProdukKatalogID']; ?>" 
                                       class="btn-delete" 
                                       onclick="showDeleteModal(event, '<?php echo addslashes($produk['NamaProduk']); ?>')">
                                        Hapus
                                    </a>
                                    <!-- === AKHIR MODIFIKASI === -->

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </main>
    </div>

    <!-- === HTML BARU UNTUK MODAL POP-UP === -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <h2>Konfirmasi Hapus</h2>
            <p>Anda yakin ingin menghapus katalog <strong id="modal-item-name"></strong>?</p>
            <p style="color: #555; font-size: 0.9rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-modal-confirm" id="confirm-delete-button">Ya, Hapus</button>
            </div>
        </div>
    </div>
    <!-- === AKHIR DARI HTML MODAL === -->


    <!-- === JAVASCRIPT BARU UNTUK MODAL === -->
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

                // Toggle hanya jika bukan halaman aktif
                if (!this.parentElement.querySelector('.submenu li.active')) {
                    submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
                }
            });
        }

        // --- Logika Modal Hapus ---
        
        const deleteModal = document.getElementById('delete-modal');
        const itemNameSpan = document.getElementById('modal-item-name');
        const confirmDeleteButton = document.getElementById('confirm-delete-button');
        let deleteUrl = ''; // Variabel untuk menyimpan URL hapus

        /**
         * Fungsi ini dipanggil saat tombol "Hapus" di tabel diklik
         */
        function showDeleteModal(event, itemName) {
            // 1. Mencegah link pindah halaman secara langsung
            event.preventDefault(); 
            
            // 2. Ambil URL hapus dari link yang diklik
            deleteUrl = event.currentTarget.href;
            
            // 3. Masukkan nama item ke modal
            itemNameSpan.textContent = itemName;
            
            // 4. Tampilkan modal
            deleteModal.style.display = 'flex';
        }

        /**
         * Fungsi untuk tombol "Batal"
         */
        function closeModal() {
            deleteModal.style.display = 'none';
            deleteUrl = ''; // Kosongkan URL saat modal ditutup
        }

        /**
         * Fungsi untuk tombol "Ya, Hapus"
         */
        confirmDeleteButton.addEventListener('click', function() {
            // Jika URL sudah tersimpan, arahkan browser ke URL hapus
            if (deleteUrl) {
                window.location.href = deleteUrl;
            }
        });

        // (Opsional) Tutup modal jika user klik di luar area modal
        window.addEventListener('click', function(event) {
            if (event.target == deleteModal) {
                closeModal();
            }
        });

    </script>
    <!-- === AKHIR DARI JAVASCRIPT === -->

</body>
</html>