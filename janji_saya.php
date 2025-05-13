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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'semua';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Base query
$query = "
    SELECT jm.id, jm.isi_janji, jm.kategori, jm.status, jm.tanggal_dibuat,
           COUNT(bj.id) AS jumlah_bukti
    FROM janji_manis jm
    LEFT JOIN bukti_janji bj ON jm.id = bj.id_janji
    WHERE jm.id_pengguna = ?
";

// Add filters
$params = [$_SESSION['id_pengguna']];

if ($status_filter !== 'semua') {
    $query .= " AND jm.status = ?";
    $params[] = $status_filter;
}

if ($kategori_filter !== 'semua') {
    $query .= " AND jm.kategori = ?";
    $params[] = $kategori_filter;
}

// Group by
$query .= " GROUP BY jm.id";

// Add sorting
if ($sort_by === 'terbaru') {
    $query .= " ORDER BY jm.tanggal_dibuat DESC";
} elseif ($sort_by === 'terlama') {
    $query .= " ORDER BY jm.tanggal_dibuat ASC";
} elseif ($sort_by === 'kategori') {
    $query .= " ORDER BY jm.kategori ASC, jm.tanggal_dibuat DESC";
}

// Execute query
$stmt = $koneksi->prepare($query);
$stmt->execute($params);
$janji_list = $stmt->fetchAll();

// Get statistics
$stmt = $koneksi->prepare("
    SELECT 
        COUNT(*) AS total_janji,
        SUM(CASE WHEN status = 'terpenuhi' THEN 1 ELSE 0 END) AS janji_terpenuhi,
        SUM(CASE WHEN status = 'belum_terpenuhi' THEN 1 ELSE 0 END) AS janji_belum
    FROM janji_manis
    WHERE id_pengguna = ?
");
$stmt->execute([$_SESSION['id_pengguna']]);
$stats = $stmt->fetch();

// Get kategori count
$stmt = $koneksi->prepare("
    SELECT kategori, COUNT(*) as jumlah
    FROM janji_manis
    WHERE id_pengguna = ?
    GROUP BY kategori
    ORDER BY jumlah DESC
");
$stmt->execute([$_SESSION['id_pengguna']]);
$kategori_stats = $stmt->fetchAll();

// Calculate percentage
$completion_percentage = ($stats['total_janji'] > 0) 
    ? round(($stats['janji_terpenuhi'] / $stats['total_janji']) * 100) 
    : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Janji Saya - My Love App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Main Content Styles */
        .main-content {
            padding: 30px;
            background-color: #FFFBFB;
        }
        
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: #C70039;
            font-size: 28px;
            font-weight: 600;
        }
        
        .add-button {
            background-color: #FF4F81;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 79, 129, 0.3);
        }
        
        .add-button:hover {
            background-color: #C70039;
            transform: translateY(-2px);
        }
        
        .add-button i {
            margin-right: 8px;
        }
        
        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(45deg, #FF4F81, #C70039);
            border-radius: 15px;
            padding: 20px;
            color: white;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            opacity: 0.3;
        }
        
        .stat-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-footer {
            font-size: 13px;
            opacity: 0.8;
        }
        
        /* Progress Bar */
        .progress-bar-container {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            height: 10px;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: white;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        /* Filter Section */
        .filter-section {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.1);
            border: 1px solid #FFCBA4;
        }
        
        .filter-title {
            font-size: 18px;
            color: #C70039;
            margin-bottom: 15px;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #FFCBA4;
            border-radius: 10px;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23C70039' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #FF4F81;
            box-shadow: 0 0 0 2px rgba(255, 79, 129, 0.2);
        }
        
        .filter-button {
            background-color: #C70039;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            height: 41px;
            margin-top: 24px;
            transition: all 0.3s;
        }
        
        .filter-button:hover {
            background-color: #FF4F81;
        }
        
        .filter-reset {
            background-color: #f8f8f8;
            color: #666;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            height: 41px;
            margin-top: 24px;
            transition: all 0.3s;
        }
        
        .filter-reset:hover {
            background-color: #eee;
        }
        
        /* Message Alerts */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: #F0FFF0;
            color: #2E8B57;
            border: 1px solid #2E8B57;
        }
        
        .alert-error {
            background-color: #FFF0F0;
            color: #C70039;
            border: 1px solid #C70039;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Promise List */
        .promise-list {
            margin-top: 20px;
        }
        
        .promise-item {
            background-color: white;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.1);
            border: 1px solid #FFCBA4;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .promise-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(199, 0, 57, 0.15);
        }
        
        .promise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(45deg, #FF4F81, #C70039);
            color: white;
        }
        
        .promise-header-left {
            display: flex;
            align-items: center;
        }
        
        .promise-header-icon {
            margin-right: 15px;
            font-size: 20px;
        }
        
        .promise-category-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.2);
            margin-left: 10px;
        }
        
        .promise-category-badge i {
            margin-right: 5px;
        }
        
        .promise-header-right {
            display: flex;
            align-items: center;
        }
        
        .promise-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-terpenuhi {
            background-color: rgba(46, 139, 87, 0.9);
        }
        
        .status-belum_terpenuhi {
            background-color: rgba(255, 193, 7, 0.9);
            color: #333;
        }
        
        .promise-body {
            padding: 20px;
        }
        
        .promise-content {
            font-size: 16px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 15px;
        }
        
        .promise-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px dashed #FFCBA4;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .promise-date {
            display: flex;
            align-items: center;
            color: #888;
            font-size: 13px;
        }
        
        .promise-date i {
            margin-right: 8px;
            color: #FF4F81;
        }
        
        .proof-count {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #888;
        }
        
        .proof-count i {
            margin-right: 8px;
            color: #FF4F81;
        }
        
        .promise-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .promise-button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .promise-button i {
            margin-right: 8px;
        }
        
        .view-button {
            background-color: #F0F8FF;
            color: #4682B4;
            border: 1px solid #4682B4;
        }
        
        .view-button:hover {
            background-color: #4682B4;
            color: white;
        }
        
        .add-proof-button {
            background-color: #F0FFF0;
            color: #2E8B57;
            border: 1px solid #2E8B57;
        }
        
        .add-proof-button:hover {
            background-color: #2E8B57;
            color: white;
        }
        
        .complete-button {
            background-color: #FFF8E0;
            color: #DAA520;
            border: 1px solid #DAA520;
        }
        
        .complete-button:hover {
            background-color: #DAA520;
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #FFCBA4;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 22px;
            color: #C70039;
            margin-bottom: 15px;
        }
        
        .empty-text {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #FFFBFB;
            margin: 10% auto;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            position: relative;
            border: 1px solid #FFCBA4;
        }
        
        .modal-title {
            color: #C70039;
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .modal-form-group {
            margin-bottom: 20px;
        }
        
        .modal-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .modal-select {
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
        
        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
        }
        
        .modal-button {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .modal-confirm {
            background-color: #2E8B57;
            color: white;
            border: none;
        }
        
        .modal-confirm:hover {
            background-color: #228B22;
        }
        
        .modal-cancel {
            background-color: #f8f8f8;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .modal-cancel:hover {
            background-color: #eee;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pagination-item.active {
            background-color: #FF4F81;
            color: white;
        }
        
        .pagination-item:not(.active) {
            background-color: white;
            color: #333;
            border: 1px solid #FFCBA4;
        }
        
        .pagination-item:hover:not(.active) {
            background-color: #FFF0F0;
        }
        
        .pagination-arrow {
            background-color: white;
            color: #FF4F81;
            border: 1px solid #FFCBA4;
        }
        
        .pagination-arrow:hover {
            background-color: #FF4F81;
            color: white;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-button, .filter-reset {
                margin-top: 0;
            }
            
            .promise-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .promise-header-right {
                align-self: flex-end;
            }
            
            .promise-actions {
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
            <h1 class="page-title"><i class="fas fa-list-alt" style="color: #FF4F81; margin-right: 10px;"></i>Janji Saya</h1>
            <button class="add-button" onclick="window.location.href='tambah_janji.php'">
                <i class="fas fa-plus"></i> Tambah Janji Manis
            </button>
        </div>
        
        <!-- Stats Dashboard -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-title">Total Janji</div>
                <div class="stat-value"><?php echo $stats['total_janji']; ?></div>
                <div class="stat-footer">Janji yang telah dibuat</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-title">Janji Terpenuhi</div>
                <div class="stat-value"><?php echo $stats['janji_terpenuhi']; ?></div>
                <div class="stat-footer">
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $completion_percentage; ?>%;"></div>
                    </div>
                    <?php echo $completion_percentage; ?>% dari total janji
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-title">Belum Terpenuhi</div>
                <div class="stat-value"><?php echo $stats['janji_belum']; ?></div>
                <div class="stat-footer">Janji yang masih perlu dipenuhi</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="stat-title">Kategori Terbanyak</div>
                <div class="stat-value">
                    <?php 
                    if (!empty($kategori_stats)) {
                        echo ucfirst($kategori_stats[0]['kategori']);
                    } else {
                        echo "-";
                    }
                    ?>
                </div>
                <div class="stat-footer">
                    <?php 
                    if (!empty($kategori_stats)) {
                        echo $kategori_stats[0]['jumlah'] . " janji dalam kategori ini";
                    } else {
                        echo "Belum ada janji";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <h3 class="filter-title"><i class="fas fa-filter" style="margin-right: 8px;"></i>Filter Janji</h3>
            <form class="filter-form" method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="filter-group">
                    <label class="filter-label" for="status">Status</label>
                    <select class="filter-select" id="status" name="status">
                        <option value="semua" <?php echo $status_filter === 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                        <option value="terpenuhi" <?php echo $status_filter === 'terpenuhi' ? 'selected' : ''; ?>>Terpenuhi</option>
                        <option value="belum_terpenuhi" <?php echo $status_filter === 'belum_terpenuhi' ? 'selected' : ''; ?>>Belum Terpenuhi</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label" for="kategori">Kategori</label>
                    <select class="filter-select" id="kategori" name="kategori">
                        <option value="semua" <?php echo $kategori_filter === 'semua' ? 'selected' : ''; ?>>Semua Kategori</option>
                        <option value="romantis" <?php echo $kategori_filter === 'romantis' ? 'selected' : ''; ?>>Romantis</option>
                        <option value="kejutan" <?php echo $kategori_filter === 'kejutan' ? 'selected' : ''; ?>>Kejutan</option>
                        <option value="hadiah" <?php echo $kategori_filter === 'hadiah' ? 'selected' : ''; ?>>Hadiah</option>
                        <option value="kencan" <?php echo $kategori_filter === 'kencan' ? 'selected' : ''; ?>>Kencan</option>
                        <option value="perhatian" <?php echo $kategori_filter === 'perhatian' ? 'selected' : ''; ?>>Perhatian</option>
                        <option value="lainnya" <?php echo $kategori_filter === 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label" for="sort">Urutkan</label>
                    <select class="filter-select" id="sort" name="sort">
                        <option value="terbaru" <?php echo $sort_by === 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="terlama" <?php echo $sort_by === 'terlama' ? 'selected' : ''; ?>>Terlama</option>
                        <option value="kategori" <?php echo $sort_by === 'kategori' ? 'selected' : ''; ?>>Kategori</option>
                    </select>
                </div>
                
                <button type="submit" class="filter-button">
                    <i class="fas fa-search" style="margin-right: 8px;"></i>Terapkan
                </button>
                
                <a href="janji_saya.php" class="filter-reset">
                    <i class="fas fa-undo" style="margin-right: 8px;"></i>Reset
                </a>
            </form>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['pesan_sukses'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['pesan_sukses']; ?>
            </div>
            <?php unset($_SESSION['pesan_sukses']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['pesan_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['pesan_error']; ?>
            </div>
            <?php unset($_SESSION['pesan_error']); ?>
        <?php endif; ?>
        
        <!-- Promises List -->
        <?php if (empty($janji_list)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <h2 class="empty-title">Belum Ada Janji Manis</h2>
                <p class="empty-text">Mulailah dengan membuat janji manis pertama Anda untuk menunjukkan cinta dan komitmen Anda.</p>
                <button class="add-button" onclick="window.location.href='tambah_janji.php'" style="margin: 0 auto;">
                    <i class="fas fa-plus"></i> Tambah Janji Manis
                </button>
            </div>
        <?php else: ?>
            <div class="promise-list">
                <?php foreach ($janji_list as $janji): ?>
                    <div class="promise-item">
                        <div class="promise-header">
                            <div class="promise-header-left">
                                <div class="promise-header-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h3>Janji Manis</h3>
                                <span class="promise-category-badge">
                                    <?php
                                    $icon = 'tag';
                                    switch($janji['kategori']) {
                                        case 'romantis': $icon = 'heart'; break;
                                        case 'kejutan': $icon = 'gift'; break;
                                        case 'hadiah': $icon = 'gift'; break;
                                        case 'kencan': $icon = 'calendar'; break;
                                        case 'perhatian': $icon = 'hand-holding-heart'; break;
                                        case 'lainnya': $icon = 'tag'; break;
                                    }
                                    ?>
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                    <?php echo ucfirst($janji['kategori']); ?>
                                </span>
                            </div>
                            <div class="promise-header-right">
                                <span class="promise-status-badge status-<?php echo $janji['status']; ?>">
                                    <?php if ($janji['status'] === 'terpenuhi'): ?>
                                        <i class="fas fa-check-circle" style="margin-right: 5px;"></i>Terpenuhi
                                    <?php else: ?>
                                        <i class="fas fa-hourglass-half" style="margin-right: 5px;"></i>Belum Terpenuhi
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="promise-body">
                            <div class="promise-content">
                                <?php echo htmlspecialchars($janji['isi_janji']); ?>
                            </div>
                            
                            <div class="promise-meta">
                                <div class="promise-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d F Y', strtotime($janji['tanggal_dibuat'])); ?>
                                </div>
                                <div class="proof-count">
                                    <i class="fas fa-camera-retro"></i>
                                    <?php echo $janji['jumlah_bukti']; ?> Bukti
                                </div>
                            </div>
                            
                            <div class="promise-actions">
                                <a href="detail_janji.php?id=<?php echo $janji['id']; ?>" class="promise-button view-button">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                                <a href="tambah_bukti.php?id_janji=<?php echo $janji['id']; ?>" class="promise-button add-proof-button">
                                    <i class="fas fa-camera"></i> Tambah Bukti
                                </a>
                                <?php if ($janji['status'] === 'belum_terpenuhi'): ?>
                                    <div class="promise-button complete-button" onclick="showCompleteModal(<?php echo $janji['id']; ?>)">
                                        <i class="fas fa-check-circle"></i> Tandai Terpenuhi
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination Example -->
            <div class="pagination">
                <a href="#" class="pagination-item pagination-arrow"><i class="fas fa-chevron-left"></i></a>
                <a href="#" class="pagination-item active">1</a>
                <a href="#" class="pagination-item">2</a>
                <a href="#" class="pagination-item">3</a>
                <a href="#" class="pagination-item pagination-arrow"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Complete Promise Modal -->
    <div id="completeModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title"><i class="fas fa-check-circle" style="color: #2E8B57; margin-right: 10px;"></i>Tandai Janji Terpenuhi</h2>
            <p style="text-align: center; margin-bottom: 20px;">Apakah Anda yakin ingin menandai janji ini sebagai terpenuhi?</p>
            
            <form id="completeForm" method="POST" action="proses_janji_terpenuhi.php">
                <input type="hidden" id="janji_id" name="janji_id" value="">
                
                <div class="modal-buttons">
                    <button type="button" class="modal-button modal-cancel" onclick="hideCompleteModal()">Batal</button>
                    <button type="submit" class="modal-button modal-confirm">Ya, Tandai Terpenuhi</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function showCompleteModal(id) {
            document.getElementById('janji_id').value = id;
            document.getElementById('completeModal').style.display = 'block';
        }
        
        function hideCompleteModal() {
            document.getElementById('completeModal').style.display = 'none';
        }
        
        // Close modal if clicked outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('completeModal')) {
                hideCompleteModal();
            }
        }
    </script>
</body>
</html>