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

// Get user data
$stmt = $koneksi->prepare("SELECT id, nama_pengguna, surel, waktu_dibuat FROM pengguna WHERE id = ?");
$stmt->execute([$_SESSION['id_pengguna']]);
$user = $stmt->fetch();

// Initialize variables for form
$isi_janji = '';
$kategori = '';
$tanggal_target = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi_janji = trim($_POST['isi_janji'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $tanggal_target = trim($_POST['tanggal_target'] ?? '');
    
    // Validate inputs
    if (empty($isi_janji)) {
        $errors[] = "Isi janji tidak boleh kosong";
    } elseif (strlen($isi_janji) > 500) {
        $errors[] = "Isi janji tidak boleh lebih dari 500 karakter";
    }
    
    if (empty($kategori)) {
        $errors[] = "Kategori harus dipilih";
    }
    
    // Validate target date if provided
    if (!empty($tanggal_target)) {
        $target_timestamp = strtotime($tanggal_target);
        if ($target_timestamp === false || $target_timestamp < time()) {
            $errors[] = "Tanggal target tidak valid atau sudah lewat";
        }
    }
    
    // If no errors, save the promise
    if (empty($errors)) {
        try {
            // Insert into janji_manis table with target date
            $stmt = $koneksi->prepare("
                INSERT INTO janji_manis 
                (id_pengguna, isi_janji, kategori, status, tanggal_dibuat, tanggal_target) 
                VALUES (?, ?, ?, 'belum_terpenuhi', NOW(), ?)
            
            ");
            $stmt->execute([
                $_SESSION['id_pengguna'], 
                $isi_janji, 
                $kategori,
                !empty($tanggal_target) ? date('Y-m-d H:i:s', strtotime($tanggal_target)) : null
            ]);
            
            // Set success message and redirect
            $_SESSION['pesan_sukses'] = "Janji manis berhasil ditambahkan! Jangan lupa untuk menepatinya ya!";
            header("Location: bukti_cinta.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Janji Manis - My Love App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Main Content Styles */
        .main-content {
            padding: 30px;
            background-color: #FFFBFB;
            min-height: calc(100vh - 70px);
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }
        
        .page-title {
            color: #C70039;
            font-size: 28px;
            font-weight: 600;
        }
        
        .back-button {
            margin-right: 15px;
            background-color: #FFCBA4;
            color: #C70039;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background-color: #FF4F81;
            color: white;
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.1);
            border: 1px solid #FFCBA4;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-header-icon {
            font-size: 40px;
            color: #FF4F81;
            margin-bottom: 15px;
        }
        
        .form-header-title {
            font-size: 24px;
            color: #C70039;
            margin-bottom: 10px;
        }
        
        .form-header-subtitle {
            font-size: 14px;
            color: #666;
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
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #FF4F81;
            box-shadow: 0 0 0 2px rgba(255, 79, 129, 0.2);
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #FFCBA4;
            border-radius: 10px;
            font-size: 16px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23C70039' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #FF4F81;
            box-shadow: 0 0 0 2px rgba(255, 79, 129, 0.2);
        }
        
        .char-counter {
            display: block;
            text-align: right;
            margin-top: 5px;
            font-size: 13px;
            color: #888;
        }
        
        .submit-button {
            background: linear-gradient(45deg, #FF4F81, #C70039);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(255, 79, 129, 0.3);
        }
        
        .submit-button:hover {
            background: linear-gradient(45deg, #FF3972, #A50030);
            transform: translateY(-2px);
        }
        
        /* Error Messages */
        .error-container {
            background-color: #FFF0F0;
            border: 1px solid #FF4F81;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .error-title {
            display: flex;
            align-items: center;
            color: #C70039;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .error-title i {
            margin-right: 8px;
        }
        
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .error-item {
            color: #C70039;
            font-size: 14px;
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .error-item:before {
            content: '•';
            position: absolute;
            left: 5px;
            color: #FF4F81;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .page-title {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            
            .form-container {
                padding: 15px;
                border-radius: 10px;
            }
            
            .form-header-title {
                font-size: 20px;
            }
            
            .submit-button {
                padding: 12px 20px;
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
            <button class="back-button" onclick="window.location.href='bukti_cinta.php'">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="page-title">Tambah Janji Manis</h1>
        </div>
        
        <div class="form-container">
            <div class="form-header">
                <div class="form-header-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h2 class="form-header-title">Buat Janji Manis Baru</h2>
                <p class="form-header-subtitle">Tuliskan janji manis Anda untuk menunjukkan cinta dan komitmen</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-container">
                    <div class="error-title">
                        <i class="fas fa-exclamation-circle"></i> Terdapat kesalahan:
                    </div>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li class="error-item"><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori Janji</label>
                    <select class="form-select" id="kategori" name="kategori" required>
                        <option value="" <?php echo empty($kategori) ? 'selected' : ''; ?> disabled>Pilih kategori</option>
                        <option value="romantis" <?php echo $kategori === 'romantis' ? 'selected' : ''; ?>>Romantis</option>
                        <option value="kejutan" <?php echo $kategori === 'kejutan' ? 'selected' : ''; ?>>Kejutan</option>
                        <option value="hadiah" <?php echo $kategori === 'hadiah' ? 'selected' : ''; ?>>Hadiah</option>
                        <option value="kencan" <?php echo $kategori === 'kencan' ? 'selected' : ''; ?>>Kencan</option>
                        <option value="perhatian" <?php echo $kategori === 'perhatian' ? 'selected' : ''; ?>>Perhatian</option>
                        <option value="lainnya" <?php echo $kategori === 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="isi_janji">Isi Janji Manis</label>
                    <textarea class="form-input form-textarea" id="isi_janji" name="isi_janji" maxlength="500" placeholder="Tuliskan janji manis Anda di sini..." required><?php echo htmlspecialchars($isi_janji); ?></textarea>
                    <span class="char-counter"><span id="charCount">0</span>/500 karakter</span>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_target" class="form-label">Target Penepatan (Opsional)</label>
                    <input 
                        type="datetime-local" 
                        id="tanggal_target" 
                        name="tanggal_target" 
                        class="form-input"
                        value="<?php echo htmlspecialchars($tanggal_target); ?>"
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                    >
                    <small class="form-hint">Pilih tanggal dan waktu target penepatan janji (jika ada)</small>
                </div>
                
                <button type="submit" class="submit-button">
                    <i class="fas fa-heart" style="margin-right: 8px;"></i> Simpan Janji Manis
                </button>
            </form>
        </div>
    </main>
    
    <script>
        // Character counter for textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textArea = document.getElementById('isi_janji');
            const charCount = document.getElementById('charCount');
            
            // Initial count
            charCount.textContent = textArea.value.length;
            
            // Update count on input
            textArea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                
                // Change color if approaching limit
                if (this.value.length > 450) {
                    charCount.style.color = '#C70039';
                } else {
                    charCount.style.color = '#888';
                }
            });
        });
        
        // Add floating hearts animation
        document.addEventListener('DOMContentLoaded', function() {
            function createHearts() {
                const container = document.createElement('div');
                container.id = 'hearts-container';
                container.style.position = 'fixed';
                container.style.top = '0';
                container.style.left = '0';
                container.style.width = '100%';
                container.style.height = '100%';
                container.style.pointerEvents = 'none';
                container.style.zIndex = '-1';
                document.body.appendChild(container);
                
                const colors = ['#FF4F81', '#C70039', '#FFCBA4'];
                
                setInterval(() => {
                    const heart = document.createElement('div');
                    heart.classList.add('heart-animation');
                    
                    // Random size
                    const size = Math.random() * 20 + 10;
                    
                    // Random position
                    const xPos = Math.random() * window.innerWidth;
                    
                    // Set properties
                    heart.innerHTML = '❤️';
                    heart.style.left = xPos + 'px';
                    heart.style.bottom = '0px';
                    heart.style.fontSize = size + 'px';
                    heart.style.color = colors[Math.floor(Math.random() * colors.length)];
                    heart.style.position = 'absolute';
                    heart.style.opacity = '0';
                    heart.style.animation = `floatHeart ${Math.random() * 4 + 2}s ease-in-out infinite`;
                    
                    // Add to DOM
                    container.appendChild(heart);
                    
                    // Remove element after animation completes
                    setTimeout(() => {
                        heart.remove();
                    }, 6000);
                    
                }, 500);
            }
            
            // Define keyframes for heart animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes floatHeart {
                    0% {
                        transform: translateY(0) rotate(0deg);
                        opacity: 0;
                    }
                    10% {
                        opacity: 0.8;
                    }
                    90% {
                        opacity: 0;
                    }
                    100% {
                        transform: translateY(-100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Start heart animation
            createHearts();
        });
    </script>
</body>
</html>