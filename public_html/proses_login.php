<?php
// proses_login.php
// 1. Mulai Session
session_start();

// 2. Hubungkan ke database
include 'koneksi.php';

// 3. Pastikan form disubmit via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Ambil data dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['error_message'] = "Username dan password wajib diisi.";
        header("Location: index.php");
        exit;
    }

    // 5. Buat Kueri SQL
    $sql = "SELECT KaryawanID, NamaKaryawan, Username, Password, Role
            FROM KARYAWAN
            WHERE Username = ?";

    $stmt = $koneksi->prepare($sql);
    if ($stmt === false) {
        die("Error saat mempersiapkan statement: " . $koneksi->error);
    }

    $stmt->bind_param("s", $username);

    // 6. Eksekusi Kueri
    $stmt->execute();
    $result = $stmt->get_result();

    // 7. Cek apakah user-nya ada
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // 8. VERIFIKASI PASSWORD dengan bcrypt
        // PERBAIKAN: Cek juga hash lama (jika Anda masih punya data lama)
        if (password_verify($password, $user['Password']) || $user['Password'] === $password) { // Hapus $user['Password'] === $password jika semua sudah di-hash

            // === LOGIN BERHASIL ===
            session_regenerate_id(true);

            // --- PERBAIKAN UTAMA: GUNAKAN snake_case (HURUF KECIL) ---
            // Ini agar konsisten dengan file lain (stok.php, laporan.php, dll.)
            $_SESSION['is_logged_in'] = true;
            $_SESSION['karyawan_id'] = $user['KaryawanID'];
            $_SESSION['nama_karyawan'] = $user['NamaKaryawan'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            // --- AKHIR PERBAIKAN ---

            // Tutup koneksi SEBELUM redirect
            $stmt->close();
            $koneksi->close();

            // Logika Redirect "Pintar"
            if (isset($_SESSION['redirect_url'])) {
                $target_url = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
                header("Location: " . $target_url);
            } else {
                header("Location: home.php");
            }
            exit;

        } else {
            // === LOGIN GAGAL (Password Salah) ===
            $_SESSION['error_message'] = "Username atau password salah.";
            // (Statement dan koneksi sudah ditutup di bawah)
        }

    } else {
        // === LOGIN GAGAL (Username tidak ditemukan) ===
        $_SESSION['error_message'] = "Username atau password salah.";
        // (Statement dan koneksi sudah ditutup di bawah)
    }

    // Tutup statement dan koneksi jika GAGAL
    $stmt->close();
    $koneksi->close();

    header("Location: index.php");
    exit;

} else {
    // Jika ada yang mengakses file ini langsung tanpa form
    $koneksi->close();

    header("Location: index.php");
    exit;
}
?>