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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bukti_cinta.php");
    exit;
}

$janji_id = $_GET['id'];

// Verify that the promise belongs to the current user
$stmt = $koneksi->prepare("SELECT * FROM janji_manis WHERE id = ? AND id_pengguna = ?");
$stmt->execute([$janji_id, $_SESSION['id_pengguna']]);
$janji = $stmt->fetch();

if (!$janji) {
    // Promise not found or doesn't belong to the user
    header("Location: bukti_cinta.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get updated promise data
    $isi_janji = $_POST['isi_janji'];
    $kategori = $_POST['kategori'];
    $status = $_POST['status'];
    
    // Update the promise
    $update_stmt = $koneksi->prepare("UPDATE janji_manis SET isi_janji = ?, kategori = ?, status = ? WHERE id = ?");
    
    if ($update_stmt->execute([$isi_janji, $kategori, $status, $janji_id])) {
        // Set success message in session
        $_SESSION['pesan_sukses'] = "Janji manis berhasil diperbarui!";
        header("Location: bukti_cinta.php");
        exit;
    } else {
        $error_message = "Terjadi kesalahan saat memperbarui janji manis.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Janji Manis - My Love App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Form Styles */
        .main-content {
            padding: 30px;
            background-color: #FFFBFB;
            min-height: 100vh;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            color: #C70039;
            font-size: 28px;
            font-weight: 600;
        }
        
        .form-container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.1);
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #FFCBA4;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #FFCBA4;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #FFFBFB;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #FF4F81;
            box-shadow: 0 0 0 3px rgba(255, 79, 129, 0.2);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23FF4F81' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .submit-button {
            background-color: #FF4F81;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 79, 129, 0.3);
        }
        
        .submit-button:hover {
            background-color: #C70039;
            transform: translateY(-2px);
        }
        
        .cancel-button {
            background-color: #f8f8f8;
            color: #333;
            border: 1px solid #ddd;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
            transition: all 0.3s;
        }
        
        .cancel-button:hover {
            background-color: #eee;
        }
        
        .error-message {
            color: #C70039;
            padding: 10px;
            background-color: #FFF0F0;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #FFCBA4;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Include navigation -->
    <?php include 'navigasi.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-edit" style="color: #FF4F81; margin-right: 10px;"></i>Edit Janji Manis</h1>
        </div>
        
        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="isi_janji" class="form-label">Isi Janji <span style="color: #FF4F81;">*</span></label>
                    <textarea id="isi_janji" name="isi_janji" class="form-input form-textarea" required><?php echo htmlspecialchars($janji['isi_janji']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kategori" class="form-label">Kategori <span style="color: #FF4F81;">*</span></label>
                    <select id="kategori" name="kategori" class="form-input form-select" required>
                        <option value="romantis" <?php echo ($janji['kategori'] == 'romantis' ? 'selected' : ''); ?>>Romantis</option>
                        <option value="keluarga" <?php echo ($janji['kategori'] == 'keluarga' ? 'selected' : ''); ?>>Keluarga</option>
                        <option value="pertemanan" <?php echo ($janji['kategori'] == 'pertemanan' ? 'selected' : ''); ?>>Pertemanan</option>
                        <option value="karir" <?php echo ($janji['kategori'] == 'karir' ? 'selected' : ''); ?>>Karir</option>
                        <option value="pendidikan" <?php echo ($janji['kategori'] == 'pendidikan' ? 'selected' : ''); ?>>Pendidikan</option>
                        <option value="kesehatan" <?php echo ($janji['kategori'] == 'kesehatan' ? 'selected' : ''); ?>>Kesehatan</option>
                        <option value="lainnya" <?php echo ($janji['kategori'] == 'lainnya' ? 'selected' : ''); ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status <span style="color: #FF4F81;">*</span></label>
                    <select id="status" name="status" class="form-input form-select" required>
                        <option value="belum_terpenuhi" <?php echo ($janji['status'] == 'belum_terpenuhi' ? 'selected' : ''); ?>>Belum Terpenuhi</option>
                        <option value="terpenuhi" <?php echo ($janji['status'] == 'terpenuhi' ? 'selected' : ''); ?>>Terpenuhi</option>
                    </select>
                </div>
                
                <div class="button-group">
                    <button type="button" class="cancel-button" onclick="window.location.href='bukti_cinta.php'">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="submit-button">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>