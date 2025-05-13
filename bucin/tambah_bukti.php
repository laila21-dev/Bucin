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

// Check if promise ID is provided
if (!isset($_GET['id_janji']) || empty($_GET['id_janji'])) {
    header("Location: bukti_cinta.php");
    exit;
}

$janji_id = $_GET['id_janji'];

// Get the promise details
$stmt = $koneksi->prepare("SELECT * FROM janji_manis WHERE id = ?");
$stmt->execute([$janji_id]);
$janji = $stmt->fetch();

if (!$janji) {
    // Promise not found
    header("Location: bukti_cinta.php");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_bukti = $_POST['jenis_bukti'];
    $teks_bukti = isset($_POST['teks_bukti']) ? trim($_POST['teks_bukti']) : null;
    $file_url = null;
    $error_message = null;
    
    // Validate required fields
    if (empty($jenis_bukti)) {
        $error_message = "Silakan pilih jenis bukti.";
    } 
    // Handle file uploads for image or audio
    elseif (in_array($jenis_bukti, ['gambar', 'audio'])) {
        if (isset($_FILES['file_bukti']) && $_FILES['file_bukti']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = [
                'gambar' => ['image/jpeg', 'image/png', 'image/gif'],
                'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg']
            ];
            
            $file_type = $_FILES['file_bukti']['type'];
            $file_size = $_FILES['file_bukti']['size'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            // Validate file type
            if (!in_array($file_type, $allowed_types[$jenis_bukti])) {
                $error_message = "Jenis file tidak diizinkan. Harap unggah " . 
                                 ($jenis_bukti === 'gambar' ? "gambar (JPG, PNG, GIF)" : "audio (MP3, WAV, OGG)");
            }
            // Validate file size
            elseif ($file_size > $max_file_size) {
                $error_message = "Ukuran file terlalu besar. Maksimal 5MB.";
            }
            else {
                // Use 'foto' directory for all uploads
                $upload_dir = 'foto/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = strtolower(pathinfo($_FILES['file_bukti']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid('bukti_') . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['file_bukti']['tmp_name'], $file_path)) {
                    $file_url = $file_path;
                } else {
                    $error_message = "Gagal mengunggah file. Silakan coba lagi.";
                }
            }
        } else {
            $error_message = "Harap pilih file untuk diunggah.";
        }
    }
    // Validate text input for text or location
    elseif (in_array($jenis_bukti, ['teks', 'lokasi']) && empty($teks_bukti)) {
        $error_message = "Harap isi teks " . ($jenis_bukti === 'lokasi' ? 'lokasi' : 'bukti') . ".";
    }
    
    // If there's no error, insert the proof
    if (!$error_message) {
        $stmt = $koneksi->prepare("
            INSERT INTO bukti_janji (id_janji, jenis_bukti, file_url, teks_bukti, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$janji_id, $jenis_bukti, $file_url, $teks_bukti])) {
            // If adding proof, update promise status to fulfilled
            $update_stmt = $koneksi->prepare("UPDATE janji_manis SET status = 'terpenuhi' WHERE id = ?");
            $update_stmt->execute([$janji_id]);
            
            // Set success message in session
            $_SESSION['pesan_sukses'] = "Bukti janji berhasil ditambahkan!";
            header("Location: bukti_cinta.php");
            exit;
        } else {
            $error_message = "Terjadi kesalahan saat menyimpan bukti janji.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Bukti Janji - My Love App</title>
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
        
        .promise-summary {
            background-color: #FFF5F5;
            border: 1px solid #FFCBA4;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .promise-title {
            color: #C70039;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .promise-text {
            color: #444;
            font-size: 16px;
            line-height: 1.6;
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
        
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .radio-card {
            flex: 1;
            min-width: 120px;
            position: relative;
        }
        
        .radio-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .radio-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background-color: #FFFBFB;
            border: 2px solid #FFCBA4;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .radio-card input[type="radio"]:checked + label {
            background-color: #FFF0F5;
            border-color: #FF4F81;
            color: #C70039;
        }
        
        .radio-card i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #FF4F81;
        }
        
        .radio-card span {
            font-weight: 600;
            font-size: 14px;
        }
        
        .proof-option {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #FFF5F5;
            border-radius: 10px;
            border: 1px dashed #FFCBA4;
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
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-button {
            background-color: #FFF0F5;
            color: #FF4F81;
            border: 1px dashed #FF4F81;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .file-input-button i {
            font-size: 30px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        
        .file-preview {
            margin-top: 15px;
            text-align: center;
        }
        
        .img-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .radio-group {
                flex-direction: column;
            }
            
            .radio-card {
                min-width: 100%;
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
            <h1 class="page-title"><i class="fas fa-camera-retro" style="color: #FF4F81; margin-right: 10px;"></i>Tambah Bukti Janji</h1>
        </div>
        
        <div class="promise-summary">
            <h2 class="promise-title">Janji yang akan dibuktikan:</h2>
            <p class="promise-text"><?php echo htmlspecialchars($janji['isi_janji']); ?></p>
        </div>
        
        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Jenis Bukti <span style="color: #FF4F81;">*</span></label>
                    
                    <div class="radio-group">
                        <div class="radio-card">
                            <input type="radio" id="jenis_gambar" name="jenis_bukti" value="gambar" required>
                            <label for="jenis_gambar">
                                <i class="fas fa-image"></i>
                                <span>Gambar</span>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="jenis_audio" name="jenis_bukti" value="audio">
                            <label for="jenis_audio">
                                <i class="fas fa-microphone"></i>
                                <span>Audio</span>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="jenis_teks" name="jenis_bukti" value="teks">
                            <label for="jenis_teks">
                                <i class="fas fa-file-alt"></i>
                                <span>Teks</span>
                            </label>
                        </div>
                        
                        <div class="radio-card">
                            <input type="radio" id="jenis_lokasi" name="jenis_bukti" value="lokasi">
                            <label for="jenis_lokasi">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Lokasi</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Gambar Option -->
                <div class="proof-option" id="gambar_option">
                    <div class="file-input-wrapper">
                        <div class="file-input-button">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Pilih Gambar</span>
                            <small>(JPG, PNG, GIF - Max 5MB)</small>
                        </div>
                        <input type="file" name="file_bukti" id="gambar_input" accept="image/jpeg, image/png, image/gif">
                    </div>
                    <div class="file-preview" id="image_preview"></div>
                </div>
                
                <!-- Audio Option -->
                <div class="proof-option" id="audio_option">
                    <div class="file-input-wrapper">
                        <div class="file-input-button">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Pilih Audio</span>
                            <small>(MP3, WAV, OGG - Max 5MB)</small>
                        </div>
                        <input type="file" name="file_bukti" id="audio_input" accept="audio/mpeg, audio/wav, audio/ogg">
                    </div>
                    <div class="file-preview" id="audio_preview"></div>
                </div>
                
                <!-- Teks Option -->
                <div class="proof-option" id="teks_option">
                    <div class="form-group">
                        <label for="teks_bukti" class="form-label">Ceritakan Bukti <span style="color: #FF4F81;">*</span></label>
                        <textarea id="teks_bukti" name="teks_bukti" class="form-input form-textarea" placeholder="Ceritakan bukti janji Anda telah terpenuhi..."></textarea>
                    </div>
                </div>
                
                <!-- Lokasi Option -->
                <div class="proof-option" id="lokasi_option">
                    <div class="form-group">
                        <label for="lokasi_bukti" class="form-label">Tambahkan Nama Lokasi <span style="color: #FF4F81;">*</span></label>
                        <input type="text" id="lokasi_bukti" name="teks_bukti" class="form-input" placeholder="Masukkan nama lokasi...">
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" class="cancel-button" onclick="window.location.href='bukti_cinta.php'">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="submit-button">
                        <i class="fas fa-save"></i> Simpan Bukti
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const radioButtons = document.querySelectorAll('input[name="jenis_bukti"]');
            const fileInputs = {
                'gambar': document.getElementById('gambar_input'),
                'audio': document.getElementById('audio_input')
            };

            // Add form validation
            form.addEventListener('submit', function(e) {
                const selectedType = document.querySelector('input[name="jenis_bukti"]:checked').value;
                
                // Check if file is required but not selected
                if (['gambar', 'audio'].includes(selectedType)) {
                    const fileInput = fileInputs[selectedType];
                    if (!fileInput.files || fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Harap pilih file untuk diunggah.');
                        return false;
                    }
                }
                // Check if text is required but empty
                else if (['teks', 'lokasi'].includes(selectedType)) {
                    const textInput = document.querySelector('#teks_bukti');
                    if (!textInput || !textInput.value.trim()) {
                        e.preventDefault();
                        alert('Harap isi teks ' + (selectedType === 'lokasi' ? 'lokasi' : 'bukti') + '.');
                        return false;
                    }
                }
                return true;
            });

            // Show/hide options based on selected radio button
            radioButtons.forEach(button => {
                button.addEventListener('change', function() {
                    const jenisBukti = this.value;
                    
                    // Hide all options first
                    document.querySelectorAll('.proof-option').forEach(option => {
                        option.style.display = 'none';
                    });
                    
                    // Show selected option
                    const proofOption = document.getElementById(`${jenisBukti}_option`);
                    if (proofOption) {
                        proofOption.style.display = 'block';
                    }
                });
            });

            // Handle image preview
            if (fileInputs['gambar']) {
                fileInputs['gambar'].addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('image_preview');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 200px; margin-top: 10px; border-radius: 5px;">`;
                        }
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = '';
                    }
                });
            }


            // Handle audio preview
            if (fileInputs['audio']) {
                fileInputs['audio'].addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('audio_preview');
                    
                    if (file) {
                        const url = URL.createObjectURL(file);
                        preview.innerHTML = `
                            <audio controls style="width: 100%; margin-top: 10px;">
                                <source src="${url}" type="${file.type}">
                                Browser Anda tidak mendukung pemutaran audio.
                            </audio>`;
                    } else {
                        preview.innerHTML = '';
                    }
                });
            }


            // Trigger file input when clicking on the button
            document.querySelectorAll('.file-input-button').forEach(button => {
                button.addEventListener('click', function() {
                    const jenisBukti = document.querySelector('input[name="jenis_bukti"]:checked').value;
                    if (fileInputs[jenisBukti]) {
                        fileInputs[jenisBukti].click();
                    }
                });
            });

            // Show the first option by default if none is selected
            if (document.querySelector('input[name="jenis_bukti"]:checked') === null && radioButtons.length > 0) {
                radioButtons[0].checked = true;
                radioButtons[0].dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>