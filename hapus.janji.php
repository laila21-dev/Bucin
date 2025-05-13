<?php
// Start session
session_start();

// Include database connection
require_once 'konfigurasi/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: masuk.php");
    exit;
}

// Check if the request is POST and the ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['janji_id'])) {
    $janji_id = $_POST['janji_id'];
    
    // Verify that the promise belongs to the current user
    $stmt = $koneksi->prepare("SELECT id FROM janji_manis WHERE id = ? AND id_pengguna = ?");
    $stmt->execute([$janji_id, $_SESSION['id_pengguna']]);
    $janji = $stmt->fetch();
    
    if ($janji) {
        // Begin transaction
        $koneksi->beginTransaction();
        
        try {
            // First delete associated proofs
            $delete_bukti = $koneksi->prepare("DELETE FROM bukti_janji WHERE id_janji = ?");
            $delete_bukti->execute([$janji_id]);
            
            // Then delete the promise
            $delete_janji = $koneksi->prepare("DELETE FROM janji_manis WHERE id = ?");
            $delete_janji->execute([$janji_id]);
            
            // Commit transaction
            $koneksi->commit();
            
            // Set success message
            $_SESSION['pesan_sukses'] = "Janji manis berhasil dihapus!";
        } catch (Exception $e) {
            // Rollback transaction on error
            $koneksi->rollBack();
            $_SESSION['pesan_error'] = "Terjadi kesalahan saat menghapus janji manis.";
        }
    } else {
        $_SESSION['pesan_error'] = "Janji manis tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.";
    }
}

// Redirect back to the main page
header("Location: bukti_cinta.php");
exit;
?>