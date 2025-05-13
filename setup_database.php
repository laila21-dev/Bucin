<?php
require_once 'konfigurasi/koneksi.php';

try {
    // Membuat database bucin
    $sql = "CREATE DATABASE IF NOT EXISTS bucin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    // Menggunakan database bucin
    $sql = "USE bucin";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    // Membuat tabel janji
    $sql = "CREATE TABLE IF NOT EXISTS janji (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        tanggal DATE NOT NULL,
        waktu TIME NOT NULL,
        status ENUM('pending', 'selesai', 'dibatalkan') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    // Membuat tabel bukti_cinta
    $sql = "CREATE TABLE IF NOT EXISTS bukti_cinta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_janji INT,
        foto VARCHAR(255) NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_janji) REFERENCES janji(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    // Menambahkan data contoh
    $sql = "INSERT INTO janji (judul, deskripsi, tanggal, waktu, status) VALUES
        ('Makan Malam Romantis', 'Makan malam bersama di restoran favorit', '2025-05-15', '19:00:00', 'pending'),
        ('Pertemuan di Taman', 'Berkencan di taman kota', '2025-05-16', '16:30:00', 'pending')";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    echo "Database dan tabel berhasil dibuat!\n";
    echo "Anda dapat mengakses aplikasi sekarang.\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
