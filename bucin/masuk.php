<?php
// Memulai sesi
session_start();

// Jika sudah login, redirect ke beranda
if (isset($_SESSION['id_pengguna'])) {
    header("Location: beranda.php");
    exit();
}

// Include file koneksi database
require_once 'konfigurasi/koneksi.php';

$error = '';
$success = '';

// Proses login saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $surel = trim($_POST['surel']);
    $kata_sandi = trim($_POST['kata_sandi']);
    $ingat_aku = isset($_POST['ingat_aku']) ? true : false;
    
    // Validasi input
    if (empty($surel) || empty($kata_sandi)) {
        $error = "Silakan isi semua field yang diperlukan";
    } else {
        // Cek email di database
        $sql = "SELECT id, nama_pengguna, surel, kata_sandi FROM pengguna WHERE surel = :surel LIMIT 1";
        
        $stmt = $koneksi->prepare($sql);
        $stmt->bindParam(':surel', $surel, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cek apakah email ditemukan
            if ($user) {
                // Verifikasi password
                if (password_verify($kata_sandi, $user['kata_sandi'])) {
                    // Password valid, mulai sesi baru
                    session_start();
                    
                    // Simpan data ke sesi
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id_pengguna"] = $user['id'];
                    $_SESSION["nama_pengguna"] = $user['nama_pengguna'];
                    $_SESSION["surel"] = $user['surel'];
                    
                    // Jika ingat_aku dicentang
                    if ($ingat_aku) {
                        // Update database
                        $update_sql = "UPDATE pengguna SET ingat_aku = 1 WHERE id = :id";
                        $update_stmt = $koneksi->prepare($update_sql);
                        $update_stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                        
                        if ($update_stmt->execute()) {
                            // Set cookie (bertahan 30 hari)
                            setcookie("id_pengguna", $user['id'], [
                                'expires' => time() + (86400 * 30),
                                'path' => '/',
                                'secure' => isset($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                            
                            setcookie("surel", $user['surel'], [
                                'expires' => time() + (86400 * 30),
                                'path' => '/',
                                'secure' => isset($_SERVER['HTTPS']),
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        }
                    }
                    
                    // Redirect ke halaman beranda
                    header("Location: beranda.php");
                    exit();
                } else {
                    $error = "Kata sandi yang dimasukkan tidak valid.";
                }
            } else {
                $error = "Akun dengan email tersebut tidak ditemukan.";
            }
        } else {
            $error = "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Bucin App</title>
    <style>
        :root {
            --primary: #FF4F81;
            --secondary: #FFCBA4;
            --accent: #C70039;
            --background: #FFFFFF;
            --text: #444444;
            --subtle: #F0F0F0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--background);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 400px;
            padding: 40px 30px;
            background-color: var(--background);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(to right, var(--primary), var(--accent));
        }
        
        h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 14px;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--subtle);
            border-radius: 8px;
            background-color: var(--background);
            color: var(--text);
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 79, 129, 0.2);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
            accent-color: var(--primary);
        }
        
        .checkbox-group label {
            margin: 0;
            font-size: 14px;
            color: var(--text);
        }
        
        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(199, 0, 57, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.2);
            color: #721c24;
        }
        
        .alert-success {
            background-color: rgba(0, 128, 0, 0.1);
            border: 1px solid rgba(0, 128, 0, 0.2);
            color: #155724;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            color: var(--accent);
        }
        
        .heart-animation {
            position: absolute;
            opacity: 0;
            animation: floatHeart 4s ease-in-out infinite;
        }
        
        @keyframes floatHeart {
            0% {
                transform: translateY(0) scale(0.8);
                opacity: 0;
            }
            50% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) scale(1);
                opacity: 0;
            }
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: var(--text);
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--subtle);
        }

        .divider::before {
            margin-right: 10px;
        }

        .divider::after {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login <span style="color: var(--accent);">❤️</span></h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="surel">Email</label>
                <input type="email" id="surel" name="surel" required>
            </div>
            
            <div class="form-group">
                <label for="kata_sandi">Kata Sandi</label>
                <input type="password" id="kata_sandi" name="kata_sandi" required>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="ingat_aku" name="ingat_aku">
                    <label for="ingat_aku">Ingat saya</label>
                </div>
            </div>
            
            <button type="submit">Masuk</button>
            
            <div class="register-link">
                Belum punya akun? <a href="daftar.php">Daftar sekarang</a>
            </div>
        </form>
    </div>
    
    <script>
        // Fungsi untuk membuat animasi hati
        function createHearts() {
            const container = document.querySelector('body');
            const colors = ['#FF4F81', '#C70039', '#FFCBA4'];
            
            setInterval(() => {
                const heart = document.createElement('div');
                heart.classList.add('heart-animation');
                
                // Ukuran acak
                const size = Math.random() * 20 + 10;
                
                // Posisi acak
                const xPos = Math.random() * window.innerWidth;
                
                // Set properti
                heart.innerHTML = '❤️';
                heart.style.left = xPos + 'px';
                heart.style.bottom = '0px';
                heart.style.fontSize = size + 'px';
                heart.style.color = colors[Math.floor(Math.random() * colors.length)];
                
                // Animasi duration acak
                const duration = Math.random() * 4 + 2;
                heart.style.animationDuration = duration + 's';
                
                // Tambahkan ke DOM
                container.appendChild(heart);
                
                // Hapus elemen setelah animasi selesai
                setTimeout(() => {
                    heart.remove();
                }, duration * 1000);
                
            }, 500);
        }
        
        // Jalankan animasi
        createHearts();
    </script>
</body>
</html>