<?php
// Konfigurasi database
$host = 'localhost'; // Host database
$db   = 'bucin'; // Nama database
$user = 'root';      // Username database
$pass = '';          // Password database
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Mencoba koneksi ke database
    $koneksi = new PDO($dsn, $user, $pass, $options);
    // Set karakter set ke UTF-8
    $koneksi->exec("SET NAMES 'utf8mb4'");
    $koneksi->exec("SET CHARACTER SET 'utf8mb4'");
} catch (PDOException $e) {
    // Menampilkan pesan error jika koneksi gagal
    die('Koneksi database gagal: ' . $e->getMessage());
}
?>