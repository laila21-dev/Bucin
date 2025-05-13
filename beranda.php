<?php
// Start the session
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

if (!$user) {
    session_destroy();
    header("Location: masuk.php");
    exit;
}

// Include navigation
require_once 'navigasi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - My Love</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Main Content Adjustments */
        .main-content-wrapper {
            margin-left: 250px;
            padding-top: 70px;
            transition: all 0.3s ease;
            min-height: calc(100vh - 70px);
            position: relative;
        }

        .main-content-expanded {
            margin-left: 70px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }

        /* Welcome Card */
        .welcome-card {
            background-color: rgba(255, 249, 245, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(210, 145, 188, 0.1);
            border: 1px solid #FADADD;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background-color: rgba(250, 218, 221, 0.2);
            border-radius: 50%;
            z-index: -1;
        }

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Quote Section */
        .quote-section {
            background-color: rgba(255, 249, 245, 0.95);
            backdrop-filter: blur(5px);
        }

        /* Memory Section */
        .memory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        /* Floating Elements */
        .floating-hearts {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .main-content-wrapper {
                margin-left: 70px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .memory-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .welcome-card::before {
                display: none;
            }
        }

        @media (min-width: 1200px) {
            .main-content {
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Floating decorative elements -->
    <div class="floating-hearts" id="hearts-container"></div>
    <div class="butterfly" id="butterfly1"></div>
    <div class="butterfly" id="butterfly2"></div>
    <div class="floating-flower" id="flower1"></div>
    <div class="floating-flower" id="flower2"></div>
    
    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper" id="main-content-wrapper">
        <div class="main-content">
            <div class="welcome-card">
                <h1 class="welcome-title">Selamat Datang, <?php echo htmlspecialchars($user['nama_pengguna']); ?>!</h1>
                <p class="welcome-subtitle">Ini adalah dunia kecil kita berdua</p>
                <p class="welcome-message">
                    "Setiap hari bersamamu adalah halaman baru dalam buku kehidupan kita. 
                    Mari kita isi dengan cerita-cerita indah yang suatu hari nanti akan kita kenang dengan senyuman."
                </p>
            </div>
            
            <div class="stats-container">
                <!-- Stat cards here -->
            </div>
            
            <div class="quote-section">
                <!-- Quote content here -->
            </div>
            
            <div class="memory-section">
                <!-- Memory grid here -->
            </div>
            
            
        </div>
    </div>

    <script>
        // Adjust layout based on sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const mainWrapper = document.getElementById('main-content-wrapper');
            
            // Check initial sidebar state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                mainWrapper.classList.add('main-content-expanded');
            }
            
            // Create floating hearts
            const heartsContainer = document.getElementById('hearts-container');
            // ... (existing heart creation code)
            
            // Handle memory item clicks
            const memoryItems = document.querySelectorAll('.memory-item');
            // ... (existing memory item code)
        });
        
        // Make sure the content area fills available space
        function adjustContentHeight() {
            const navbarHeight = document.querySelector('.navbar').offsetHeight;
            const mainWrapper = document.getElementById('main-content-wrapper');
            mainWrapper.style.minHeight = `calc(100vh - ${navbarHeight}px)`;
        }
        
        window.addEventListener('load', adjustContentHeight);
        window.addEventListener('resize', adjustContentHeight);
    </script>
</body>
</html>