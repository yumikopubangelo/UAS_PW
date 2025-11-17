<?php
// 1. MULAI SESSION & KONEKSI (WAJIB DI ATAS!)
session_start();
include 'koneksi.php';

// --- MODIFIKASI 1: KEAMANAN KONDISIONAL ---
// Cek sumbernya DULU
$source = $_GET['source'] ?? 'login';

// 2. KEAMANAN: Cek login & Role HANYA JIKA source=admin
if ($source == 'admin') {
    // Cek login (Menggunakan session yang benar)
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        $_SESSION['error_message'] = "Anda harus login terlebih dahulu.";
        header("Location: index.php");
        exit;
    }
    // Cek role
    $role = $_SESSION['role']; 
    if ($role != 'admin' && $role != 'pemilik') {
        $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman ini.";
        header("Location: home.php"); // Tendang ke home jika bukan admin
        exit;
    }
}
// Jika $source == 'login', blok keamanan ini dilewati

// 3. Siapkan variabel untuk pesan
$error_message = '';

// 4. Proses HANYA JIKA form disubmit (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. Ambil data dari form
    $nama_karyawan = trim($_POST['nama_karyawan']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role_baru = trim($_POST['role']); 
    $source_page = $_POST['source'] ?? 'login';

    // === VALIDASI INPUT ===
    if (empty($nama_karyawan) || empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field wajib diisi!";
    }
    elseif ($password !== $confirm_password) {
        $error_message = "Password dan Konfirmasi Password tidak cocok!";
    }
    elseif (strlen($password) < 6) {
        $error_message = "Password minimal harus 6 karakter.";
    }
    elseif ($source_page == 'login' && ($role_baru == 'admin' || $role_baru == 'pemilik')) {
        $error_message = "Pendaftaran publik tidak diizinkan untuk Role tersebut.";
    }
    elseif (!in_array($role_baru, ['karyawan', 'kasir', 'admin', 'pemilik'])) {
        $error_message = "Role yang dipilih tidak valid.";
    }
    else {
        // === VALIDASI KE DATABASE ===
        $sql_check = "SELECT KaryawanID FROM KARYAWAN WHERE Username = ?";
        $stmt_check = $koneksi->prepare($sql_check);
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "Username '<strong>" . htmlspecialchars($username) . "</strong>' sudah terdaftar.";
        } else {
            // === SEMUA AMAN, PROSES PENDAFTARAN ===
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // --- PERBAIKAN DI SINI (Baris 77) ---
            // Menggunakan nama kolom 'NamaKaryawan' (huruf besar) agar sesuai DB
            $sql_insert = "INSERT INTO KARYAWAN (NamaKaryawan, Username, Password, Role) VALUES (?, ?, ?, ?)";
            // --- AKHIR PERBAIKAN ---

            $stmt_insert = $koneksi->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nama_karyawan, $username, $hashed_password, $role_baru);

            if ($stmt_insert->execute()) {
                // === Logika Redirect "Pintar" ===
                $stmt_insert->close();
                $stmt_check->close();
                $koneksi->close();

                if ($source_page == 'admin') {
                    $_SESSION['success_message'] = "Karyawan baru ($username) berhasil ditambahkan.";
                    header("Location: karyawan.php");
                } else {
                    $_SESSION['success_message'] = "Berhasil mendaftarkan user baru. Silakan login.";
                    header("Location: index.php");
                }
                exit; 

            } else {
                $error_message = "Terjadi kesalahan saat mendaftarkan user: " . $koneksi->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    
    $koneksi->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Karyawan Baru</title>
    
    <link rel="stylesheet" href="dashboard.css">
    
    <style>
        /* (CSS Anda - tidak saya ubah) */
        .form-card { background-color: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); max-width: 600px; margin: 2rem auto; }
        .form-card h3 { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        .btn-primary { width: 100%; padding: 0.75rem 1.2rem; border: none; border-radius: 6px; background-color: #007bff; color: white; font-weight: 500; cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; text-align: center; font-size: 1rem; box-sizing: border-box; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { display: block; text-align: center; width: 100%; padding: 0.75rem; background-color: #6c757d; color: white; text-decoration: none; border-radius: 6px; margin-top: 1rem; box-sizing: border-box; transition: background-color 0.2s ease; }
        .btn-secondary:hover { background-color: #5a6268; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Jika ini halaman publik, jangan pakai layout dashboard */
        <?php if ($source == 'login'): ?>
            .dashboard-container { display: block; }
            .sidebar { display: none; }
            .main-content { width: 100%; }
            .header { display: none; }
        <?php endif; ?>
    </style>
</head>
<body>
    
    <div class="dashboard-container">
        
        <?php if ($source == 'admin'): ?>
            <nav class="sidebar">
                 <div class="sidebar-header"><h3>Toko Emas UMKM</h3></div>
                 <ul class="sidebar-menu">
                     <li><a href="home.php">Dashboard</a></li>
                     <!-- (Pastikan session Anda benar di sini) -->
                     <li class="active"><a href="karyawan.php">Manajemen Karyawan</a></li>
                 </ul>
            </nav>

            <main class="main-content">
                <header class="header">
                    <div class="header-title">
                        <h1>Tambah Karyawan Baru</h1>
                    </div>
                    <div class="user-info">
                        <!-- (Pastikan session Anda benar di sini) -->
                        <span>Halo, <strong><?php echo htmlspecialchars($_SESSION['nama_karyawan']); ?> (<?php echo $_SESSION['role']; ?>)</strong></span>
                        <a href="logout.php" class="logout-button">Logout</a>
                    </div>
                </header>
        <?php else: ?>
            <main class="main-content">
        <?php endif; ?>

            <div class="content-wrapper">
                <div class="form-card">
                    <h3><?php echo ($source == 'admin') ? 'Detail Karyawan Baru' : 'Buat Akun Baru'; ?></h3>

                    <?php
                    if (!empty($error_message)) {
                        echo '<div class="message error">' . $error_message . '</div>';
                    }
                    ?>

                    <form action="register.php" method="POST">
                        
                        <input type="hidden" name="source" value="<?php echo htmlspecialchars($source); ?>">

                        <div class="form-group">
                            <label for="nama_karyawan">Nama Lengkap:</label>
                            <input type="text" id="nama_karyawan" name="nama_karyawan" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="karyawan">Karyawan</option>
                                <option value="kasir">Kasir</option>
                                
                                <!-- (Logika PHP Anda di sini sudah benar) -->
                                <?php if ($source == 'admin' || (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'pemilik'))): ?>
                                    <option value="admin">Admin</option>
                                    <option value="pemilik">Pemilik</option>
                                <?php endif; ?>
                                
                            </select>
                        </div>
                        
                        <div class="form-group"> 
                            <button type="submit" class="btn-primary">
                                <?php echo ($source == 'admin') ? 'Daftarkan User' : 'Buat Akun'; ?>
                            </button>
                            
                            <?php if ($source == 'admin'): ?>
                                <a href="karyawan.php" class="btn-secondary">Batal</a>
                            <?php else: ?>
                                <a href="index.php" class="btn-secondary">Kembali ke Login</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>