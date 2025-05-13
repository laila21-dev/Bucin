<?php
session_start();
require_once 'konfigurasi/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_pengguna'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Anda tidak memiliki akses");
}

// Tangani aksi yang dikirim dari form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_pengguna = $_SESSION['id_pengguna'];
    
    try {
        switch ($action) {
            case 'add':
                // Tambah janji baru
                $isi_janji = $_POST['promise_content'] ?? '';
                $kategori = $_POST['promise_category'] ?? 'serius';
                
                if (empty($isi_janji)) {
                    throw new Exception("Isi janji tidak boleh kosong");
                }
                
                $stmt = $koneksi->prepare("INSERT INTO janji_manis (id_pengguna, isi_janji, kategori) VALUES (?, ?, ?)");
                $stmt->execute([$id_pengguna, $isi_janji, $kategori]);
                
                $_SESSION['success_message'] = "Janji manis berhasil ditambahkan!";
                break;
                
            case 'edit':
                // Edit janji yang sudah ada
                $id = $_POST['promise_id'] ?? 0;
                $isi_janji = $_POST['promise_content'] ?? '';
                $kategori = $_POST['promise_category'] ?? 'serius';
                
                if (empty($isi_janji)) {
                    throw new Exception("Isi janji tidak boleh kosong");
                }
                
                // Pastikan janji ini milik user yang login
                $stmt = $koneksi->prepare("UPDATE janji_manis SET isi_janji = ?, kategori = ? WHERE id = ? AND id_pengguna = ?");
                $stmt->execute([$isi_janji, $kategori, $id, $id_pengguna]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Janji tidak ditemukan atau Anda tidak memiliki izin");
                }
                
                $_SESSION['success_message'] = "Janji manis berhasil diperbarui!";
                break;
                
            case 'delete':
                // Hapus janji
                $id = $_POST['promise_id'] ?? 0;
                
                // Pastikan janji ini milik user yang login
                $stmt = $koneksi->prepare("DELETE FROM janji_manis WHERE id = ? AND id_pengguna = ?");
                $stmt->execute([$id, $id_pengguna]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Janji tidak ditemukan atau Anda tidak memiliki izin");
                }
                
                $_SESSION['success_message'] = "Janji manis berhasil dihapus!";
                break;
                
            case 'fulfill':
                // Tandai janji sebagai terpenuhi
                $id = $_POST['promise_id'] ?? 0;
                
                // Pastikan janji ini milik user yang login
                $stmt = $koneksi->prepare("UPDATE janji_manis SET status = 'terpenuhi' WHERE id = ? AND id_pengguna = ?");
                $stmt->execute([$id, $id_pengguna]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Janji tidak ditemukan atau Anda tidak memiliki izin");
                }
                
                $_SESSION['success_message'] = "Janji manis berhasil ditandai sebagai terpenuhi!";
                break;
                
            default:
                throw new Exception("Aksi tidak valid");
        }
        
        // Redirect kembali ke halaman bukti cinta
        header("Location: bukti_cinta.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: bukti_cinta.php");
        exit;
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Metode tidak diizinkan");
}
?>