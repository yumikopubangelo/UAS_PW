<?php
// Selalu mulai session untuk bisa mengakses dan menghancurkannya
session_start();

// 1. Hapus semua variabel session
$_SESSION = array();

// 2. Hancurkan cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session-nya
session_destroy();

// 4. Arahkan kembali ke halaman login
header("Location: index.php");
exit;
?>