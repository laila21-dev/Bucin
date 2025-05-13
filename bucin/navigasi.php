<?php


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

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Define menu items with their corresponding pages and categories
$menu_items = [
    'Menu Utama' => [
        ['page' => 'beranda.php', 'icon' => 'home', 'text' => 'Beranda'],
        ['page' => 'janji_saya.php', 'icon' => 'clipboard', 'text' => 'Janji Saya'],
        ['page' => 'bukti_cinta.php', 'icon' => 'heart', 'text' => 'Cinta Pasangan'],
        ['page' => 'pesan.php', 'icon' => 'envelope', 'text' => 'Pesan Rahasia']
    ],
    'Fitur Spesial' => [
        ['page' => 'kalender.php', 'icon' => 'calendar-alt', 'text' => 'Kalender Kita'],
        ['page' => 'bucket-list.php', 'icon' => 'list', 'text' => 'Bucket List'],
        ['page' => 'lokasi.php', 'icon' => 'map-marker-alt', 'text' => 'Tempat Favorit']
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Love App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #FF4F81;
            --secondary: #FFCBA4;
            --accent: #C70039;
            --background: #FFFFFF;
            --text: #444444;
            --subtle: #F0F0F0;
        }
        
        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            overflow-x: hidden;
            min-width: 320px;
            background-color: var(--background);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar Container */
        .navbar-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1001;
        }

        .navbar.scrolled {
            padding: 10px 30px;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .logo {
            display: flex;
            align-items: center;
            color: var(--primary);
            font-weight: bold;
            font-size: clamp(16px, 4vw, 20px);
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .logo i {
            margin-right: 10px;
            font-size: 24px;
            flex-shrink: 0;
            color: var(--accent);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 24px;
            cursor: pointer;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .user-menu {
            position: relative;
            margin-left: auto;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .user-dropdown {
            position: fixed;
            top: 75px;
            right: 20px;
            width: 250px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1100;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .user-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary);
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 12px;
            color: var(--primary);
            text-align: center;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #f0f0f0;
            margin: 5px 0;
        }
        
        /* Dropdown Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .user-dropdown.active {
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            background-color: rgba(255, 251, 250, 0.98);
            border-right: 1px solid var(--secondary);
            padding: 20px 0;
            z-index: 999;
            transition: all 0.3s ease;
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }

        .sidebar-collapsed {
            width: 70px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-title {
            color: var(--primary);
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px 20px;
            margin-top: 20px;
            display: block;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            position: relative;
            padding-right: 30px;
        }

        .menu-link:not(.active):hover {
            background: rgba(0, 0, 0, 0.03);
            transform: translateX(5px);
        }

        .menu-link.active {
            background: linear-gradient(90deg, rgba(255, 79, 129, 0.1), transparent);
            color: var(--primary);
            border-right: 3px solid var(--primary);
        }
        
        .menu-link.active i {
            color: var(--primary);
        }
        
        .active-indicator {
            position: absolute;
            right: 10px;
            width: 6px;
            height: 6px;
            background-color: var(--primary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(255, 79, 129, 0.7);
            }
            70% {
                opacity: 0.6;
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(255, 79, 129, 0);
            }
        }

        .menu-link i {
            font-size: 18px;
            margin-right: 15px;
            width: 20px;
            text-align: center;
            color: var(--accent);
        }

        .menu-text {
            transition: opacity 0.3s;
        }

        .sidebar-collapsed .menu-text {
            opacity: 0;
            width: 0;
            display: none;
        }

        .sidebar-collapsed .menu-title {
            display: none;
        }

        .sidebar-collapsed .menu-link {
            justify-content: center;
            padding: 12px 0;
        }

        .sidebar-collapsed .menu-link i {
            margin-right: 0;
        }

        .toggle-sidebar {
            position: absolute;
            top: 10px;
            right: -15px;
            background-color: var(--background);
            border: 1px solid var(--secondary);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .toggle-sidebar i {
            color: var(--primary);
            transition: all 0.3s;
        }

        .sidebar-collapsed .toggle-sidebar i {
            transform: rotate(180deg);
        }

        /* Main Content Adjustment */
        .main-content {
            margin-left: 250px;
            padding-top: 70px;
            transition: all 0.3s;
            min-height: calc(100vh - 70px);
        }

        .main-content-expanded {
            margin-left: 70px;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background-color: rgba(0,0,0,0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Heart Animation */
        .heart-animation {
            position: fixed;
            opacity: 0;
            animation: floatHeart 4s ease-in-out infinite;
            z-index: -1;
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

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .logo {
                max-width: 150px;
            }
            
            .user-menu {
                margin-left: 10px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 10px 15px;
            }
            
            .logo {
                max-width: 120px;
                font-size: 16px;
            }
            
            .logo i {
                font-size: 20px;
                margin-right: 8px;
            }
            
            .mobile-menu-btn {
                margin-right: 10px;
                font-size: 20px;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
                min-width: 35px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Hearts -->
    <div id="hearts-container"></div>
    
    <!-- Navbar Container -->
    <div class="navbar-container">
        <!-- Navbar -->
        <nav class="navbar" id="navbar">
            <div class="navbar-left">
                <a href="beranda.php" class="logo">
                    <i class="fas fa-heart"></i>
                    <span>My Love</span>
                </a>
            </div>
            
            <div class="user-menu">
                <div class="user-avatar" id="user-avatar" title="<?php echo htmlspecialchars($user['nama_pengguna']); ?>">
                    <?php echo strtoupper(substr($user['nama_pengguna'], 0, 1)); ?>
                    <span class="online-status"></span>
                </div>
                
                <div class="user-dropdown" id="user-dropdown">
                    <div class="dropdown-header">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['nama_pengguna']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['surel']); ?></div>
                        </div>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="profil.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Profil Saya</span>
                    </a>
                    <a href="pengaturan.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                    <a href="bantuan.php" class="dropdown-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Bantuan</span>
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="keluar.php" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="toggle-sidebar" id="toggle-sidebar">
            <i class="fas fa-chevron-left"></i>
        </div>
        
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $category => $items): ?>
                <?php if (!empty($items)): ?>
                    <li class="menu-title"><?php echo $category; ?></li>
                    <?php foreach ($items as $item): 
                        $is_active = ($current_page === $item['page']);
                        $active_class = $is_active ? 'active' : '';
                    ?>
                        <li class="menu-item">
                            <a href="<?php echo $item['page']; ?>" class="menu-link <?php echo $active_class; ?>">
                                <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                                <span class="menu-text"><?php echo $item['text']; ?></span>
                                <?php if ($is_active): ?>
                                    <span class="active-indicator"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </aside>
    
    <script>
        // Navigation JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced dropdown toggle
            const userAvatar = document.getElementById('user-avatar');
            const userDropdown = document.getElementById('user-dropdown');
            let dropdownTimeout;
            
            // Show dropdown on click
            userAvatar.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
                
                // Close after delay if clicking outside
                if (userDropdown.classList.contains('active')) {
                    clearTimeout(dropdownTimeout);
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (userDropdown.classList.contains('active')) {
                    userDropdown.classList.remove('active');
                }
            });
            
            // Keep dropdown open when hovering over it
            userDropdown.addEventListener('mouseenter', function() {
                clearTimeout(dropdownTimeout);
            });
            
            userDropdown.addEventListener('mouseleave', function() {
                dropdownTimeout = setTimeout(() => {
                    userDropdown.classList.remove('active');
                }, 300);
            });
            
            // Prevent dropdown from closing when clicking inside
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Toggle sidebar (desktop)
            const toggleSidebar = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('main-content-expanded');
                
                // Save state to localStorage
                const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
            
            // Mobile menu toggle
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
            
            // Close sidebar when clicking overlay
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
            
            // Check saved state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('main-content-expanded');
            }
            
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.getElementById('navbar');
                if (window.scrollY > 10) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
            
            // Adjust layout on resize
            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                }
            }
            
            window.addEventListener('resize', handleResize);
            
            // Heart animation
            function createHearts() {
                const container = document.getElementById('hearts-container');
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
                    
                    // Random animation duration
                    const duration = Math.random() * 4 + 2;
                    heart.style.animationDuration = duration + 's';
                    
                    // Add to DOM
                    container.appendChild(heart);
                    
                    // Remove element after animation completes
                    setTimeout(() => {
                        heart.remove();
                    }, duration * 1000);
                    
                }, 500);
            }
            
            // Start heart animation
            createHearts();
        });
    </script>
</body>
</html>