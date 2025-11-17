<?php
// index.php
session_start();

// --- PERBAIKAN: TAMBAHKAN BLOK INI ---
// Jika user sudah login, jangan tampilkan halaman login lagi.
// Langsung lempar ke home.
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: home.php");
    exit;
}
// --- AKHIR PERBAIKAN ---

$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // hapus setelah ditampilkan agar tidak muncul lagi
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Toko Emas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="login-container">
        <h2>Sistem Manajemen</h2>
        <h1>Toko Emas UMKM</h1>
        <p>Silakan login untuk melanjutkan</p>

        <form id="login-form" action="proses_login.php" method="POST" autocomplete="off">
            
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="login-button">Masuk</button>

            <!-- Tampilkan pesan error (jika ada) -->
            <?php if (!empty($error_message)) : ?>
                <p id="error-message" style="color: red; margin-top: 10px;">
                    <?= htmlspecialchars($error_message) ?>
                </p>
            <?php endif; ?>
        </form>
    </div>

</body>
</html>