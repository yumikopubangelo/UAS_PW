<?php
// 1. MULAI SESSION (INI YANG HILANG!)
session_start();
include 'koneksi.php'; // Hubungkan ke DB (opsional untuk home, tapi bagus)

// 2. KEAMANAN: Cek apakah user sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Jika belum, lempar kembali ke index.php
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
    header("Location: index.php");
    exit;
}

// 3. AMBIL DATA USER DARI SESSION (Nama yang BENAR)
$nama_karyawan = $_SESSION['nama_karyawan'];
$role = $_SESSION['role'];
// INI VARIABEL YANG BENAR
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

// (Anda bisa tambahkan kueri untuk kartu dashboard di sini, misal COUNT(*) stok)

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Toko Emas</title>
    <link rel="stylesheet" href="dashboard.css">
    <!-- (CSS tambahan untuk kartu dashboard jika ada) -->
    <style>
        .welcome-message {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 2rem;
        }
        .quick-access {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsif */
            gap: 1.5rem;
        }
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        .card h3 {
            margin: 0 0 0.75rem 0;
            color: #007bff; /* Biru */
        }
        .card p {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #666;
            margin-bottom: 1.5rem;
        }
        .card-button {
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        .card-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <!-- === SIDEBAR (NAVIGASI UTAMA) === -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Toko Emas UMKM</h3>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="home.php">Dashboard</a></li>
                <li><a href="transaksi_baru.php">Transaksi Baru (POS)</a></li>
                <li><a href="stok.php">Manajemen Stok</a></li>
                
                <!-- PASTIKAN MENGGUNAKAN VARIABEL YANG BENAR DI SINI -->
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
                    <li><a href="harga.php">Update Harga Harian</a></li>
                    <li class="admin-menu"><a href="karyawan.php">Manajemen Karyawan</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- === KONTEN UTAMA (HEADER + ISI HALAMAN) === -->
        <main class="main-content">
            
            <!-- Header di dalam konten utama -->
            <header class="header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                </div>
                <div class="user-info">
                    <span>Halo, <strong><?php echo htmlspecialchars($nama_karyawan); ?> (<?php echo htmlspecialchars($role_text); ?>)</strong></span>
                    <a href="<?php echo $logout_url; ?>" class="logout-button">Logout</a>
                </div>
            </header>

            <!-- Isi konten halaman -->
            <div class="content-wrapper">
                
                <p class="welcome-message">Selamat datang di Sistem Manajemen Toko Emas!</p>

                <!-- Kartu Akses Cepat -->
                <div class="quick-access">
                    
                    <div class="card">
                        <h3>Transaksi Baru</h3>
                        <p>Mulai transaksi penjualan, buy-back, atau servis.</p>
                        <a href="transaksi_baru.php" class="card-button">Mulai</a>
                    </div>
                    
                    <div class="card">
                        <h3>Lihat Stok</h3>
                        <p>Cek ketersediaan barang, berat, dan HPP (jika admin).</p>
                        <a href="stok.php" class="card-button">Lihat Stok</a>
                    </div>

                    <!-- DAN PASTI MENGGUNAKAN VARIABEL YANG BENAR DI SINI (SEKITAR BARIS 106) -->
                    <?php if ($is_admin_or_pemilik): ?>
                    <div class="card">
                        <h3>Laporan Hari Ini</h3>
                        <p>Lihat total omset dan laba-rugi untuk hari ini.</p>
                        <a href="laporan.php" class="card-button">Lihat Laporan</a>
                    </div>
                    <?php endif; ?>

                </div>
            </div> <!-- .content-wrapper -->

        </main> <!-- .main-content -->

    </div> <!-- .dashboard-container -->

    <script>
        // ===== MOBILE MENU TOGGLE =====
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

    // Toggle menu saat tombol diklik
    menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        // Hide hamburger button when menu is open
        menuBtn.style.display = sidebar.classList.contains('active') ? 'none' : 'block';
    });

    // Tutup menu saat overlay diklik
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        // Show hamburger button when menu is closed
        menuBtn.style.display = 'block';
    });

    // Tutup menu saat link diklik (untuk mobile)
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a:not(.has-submenu)');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                // Show hamburger button when menu is closed
                menuBtn.style.display = 'block';
            }
        });
    });

    // Tutup menu saat submenu link diklik
    const submenuLinks = document.querySelectorAll('.submenu a');
    submenuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                // Show hamburger button when menu is closed
                menuBtn.style.display = 'block';
            }
        });
    });

    // ===== SUBMENU TOGGLE =====
    const submenuToggle = document.querySelector('.has-submenu');
    if (submenuToggle) {
        submenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const isVisible = submenu.style.display === 'block';
            
            submenu.style.display = isVisible ? 'none' : 'block';
            
            // Animasi icon
            if (this.classList.contains('active')) {
                this.classList.remove('active');
            } else {
                this.classList.add('active');
            }
        });
    }

    // ===== RESPONSIVE BEHAVIOR =====
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Tutup menu mobile saat layar diperbesar
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                // Show hamburger button when menu is closed
                menuBtn.style.display = 'block';
            }
        }, 250);
    });
});
    </script>

</body>
</html>