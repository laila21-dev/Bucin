<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: masuk.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: masuk.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - My Love</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #FFF9F5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            padding: 30px;
            margin-top: 50px;
            text-align: center;
            position: relative;
        }
        
        .welcome-message {
            color: #D291BC;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .logout-btn {
            background-color: #FF6B81;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .logout-btn:hover {
            background-color: #ff4757;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .content {
            margin: 30px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="welcome-message">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_pengguna']); ?>! ❤️</h1>
        
        <div class="content">
            <p>Ini adalah halaman dashboard Anda.</p>
            <p>Anda dapat menambahkan konten khusus pengguna di sini.</p>
        </div>
        
        <a href="?logout=1" class="logout-btn">Keluar</a>
    </div>
</body>
</html>