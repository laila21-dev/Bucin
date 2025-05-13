<?php
// Start the session
session_start();

// Hapus semua data sesi
$_SESSION = array();

// Hapus cookie sesi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hapus cookie ingat saya
setcookie('id_pengguna', '', time() - 3600, '/');
setcookie('surel', '', time() - 3600, '/');

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login
header("Location: masuk.php");
exit();
?>