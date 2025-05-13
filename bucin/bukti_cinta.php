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

// Handle marking promise as fulfilled
if (isset($_POST['mark_fulfilled']) && isset($_POST['janji_id'])) {
    $janji_id = $_POST['janji_id'];
    $stmt = $koneksi->prepare("UPDATE janji_manis SET status = 'terpenuhi', tanggal_terpenuhi = NOW() WHERE id = ?");
    $stmt->execute([$janji_id]);
    
    // Refresh the page to show updated status
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get user data
$stmt = $koneksi->prepare("SELECT id, nama_pengguna, surel, waktu_dibuat FROM pengguna WHERE id = ?");
$stmt->execute([$_SESSION['id_pengguna']]);
$user = $stmt->fetch();

// Get all sweet promises with their proofs for this user
$stmt = $koneksi->prepare("
    SELECT jm.id, jm.isi_janji, jm.kategori, jm.status, jm.janji_akan_terpenuhi, 
           jm.tanggal_dibuat, jm.tanggal_terpenuhi,
           jm.id_pengguna, p.nama_pengguna,
           bj.id AS bukti_id, bj.jenis_bukti, bj.file_url, bj.teks_bukti, bj.created_at
    FROM janji_manis jm
    JOIN pengguna p ON jm.id_pengguna = p.id
    LEFT JOIN bukti_janji bj ON jm.id = bj.id_janji
    WHERE jm.id_pengguna != ?
    ORDER BY jm.tanggal_dibuat DESC, bj.created_at DESC
");
$stmt->execute([$_SESSION['id_pengguna']]);
$janji_manis = $stmt->fetchAll();

// Group promises with their proofs
$grouped_janji = [];
foreach ($janji_manis as $janji) {
    $janji_id = $janji['id'];
    if (!isset($grouped_janji[$janji_id])) {
        $grouped_janji[$janji_id] = [
            'isi_janji' => $janji['isi_janji'],
            'kategori' => $janji['kategori'],
            'status' => $janji['status'],
            'janji_akan_terpenuhi' => $janji['janji_akan_terpenuhi'],
            'tanggal_dibuat' => $janji['tanggal_dibuat'],
            'tanggal_terpenuhi' => $janji['tanggal_terpenuhi'],
            'pembuat_id' => $janji['id_pengguna'],
            'pembuat_nama' => $janji['nama_pengguna'],
            'bukti' => []
        ];
    }
    
    if ($janji['bukti_id']) {
        $grouped_janji[$janji_id]['bukti'][] = [
            'id' => $janji['bukti_id'],
            'jenis_bukti' => $janji['jenis_bukti'],
            'file_url' => $janji['file_url'],
            'teks_bukti' => $janji['teks_bukti'],
            'created_at' => $janji['created_at']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Cinta - My Love App</title>
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
        
        /* Promise Cards */
        .promise-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .promise-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(199, 0, 57, 0.1);
            transition: all 0.3s;
            border: 1px solid #FFCBA4;
            position: relative;
        }
        
        .promise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(199, 0, 57, 0.15);
        }
        
        .promise-header {
            padding: 20px;
            background: linear-gradient(45deg, #FF4F81, #C70039);
            color: white;
            position: relative;
        }
        
        .promise-category {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .promise-content {
            padding: 20px;
        }
        
        .promise-text {
            font-size: 16px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 15px;
        }
        
        .promise-date {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
        }
        
        .promise-date i {
            margin-right: 5px;
            color: #FF4F81;
        }
        
        .fulfillment-date {
            font-size: 13px;
            color: #2E8B57;
            margin-left: 10px;
        }
        
        .promise-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .status-belum_terpenuhi {
            background-color: #FFF0F0;
            color: #FF4F81;
            border: 1px solid #FF4F81;
        }
        
        .status-terpenuhi {
            background-color: #F0FFF0;
            color: #2E8B57;
            border: 1px solid #2E8B57;
        }
        
        /* Proof Section */
        .proof-section {
            margin-top: 20px;
            border-top: 1px dashed #FFCBA4;
            padding-top: 15px;
        }
        
        .proof-title {
            font-size: 16px;
            font-weight: 600;
            color: #C70039;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .proof-title i {
            margin-right: 8px;
        }
        
        .proof-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .proof-item {
            background-color: #FFF9F9;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #FFE0E0;
        }
        
        .proof-text {
            font-size: 14px;
            line-height: 1.5;
            color: #555;
        }
        
        .proof-image {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .proof-audio {
            width: 100%;
            margin-top: 10px;
        }
        
        .proof-location {
            display: flex;
            align-items: center;
            color: #555;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .proof-location i {
            color: #FF4F81;
            margin-right: 8px;
        }
        
        .proof-date {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
            text-align: right;
        }
        
        .no-proof {
            font-size: 14px;
            color: #888;
            text-align: center;
            padding: 15px;
            background-color: #FFF9F9;
            border-radius: 10px;
            border: 1px dashed #FFCBA4;
        }
        
        /* Card Action Buttons */
        .card-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px dashed #FFCBA4;
        }
        
        .action-button {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .action-button i {
            margin-right: 5px;
        }
        
        .edit-button {
            background-color: #FFF5F5;
            color: #FF4F81;
            border: 1px solid #FF4F81;
        }
        
        .edit-button:hover {
            background-color: #FF4F81;
            color: white;
        }
        
        .delete-button {
            background-color: #FFF0F0;
            color: #C70039;
            border: 1px solid #C70039;
        }
        
        .delete-button:hover {
            background-color: #C70039;
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
        
        .fulfill-button {
            background-color: #F0FFF0;
            color: #2E8B57;
            border: 1px solid #2E8B57;
        }
        
        .fulfill-button:hover {
            background-color: #2E8B57;
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
        
        .modal-text {
            color: #444;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .modal-button {
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
            text-align: center;
            transition: all 0.3s;
        }
        
        .confirm-button {
            background-color: #C70039;
            color: white;
            border: none;
        }
        
        .confirm-button:hover {
            background-color: #A50030;
        }
        
        .cancel-button {
            background-color: #f8f8f8;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .cancel-button:hover {
            background-color: #eee;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .promise-container {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .card-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-button {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .promise-card {
                border-radius: 10px;
            }
            
            .promise-header, .promise-content {
                padding: 15px;
            }
            
            .modal-content {
                margin: 20% auto;
                padding: 20px;
            }
        }
        
        /* Promise Creator's Name Styles */
        .promise-pembuat {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            font-style: italic;
            display: flex;
            align-items: center;
        }
        
        .promise-pembuat:before {
            content: '👤';
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Include navigation -->
    <?php include 'navigasi.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-heart" style="color: #FF4F81; margin-right: 10px;"></i>Bukti Cinta</h1>
            <button class="add-button" onclick="window.location.href='tambah_janji.php'">
                <i class="fas fa-plus"></i> Tambah Janji Manis
            </button>
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
        
        <?php if (empty($grouped_janji)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <h2 class="empty-title">Belum Ada Janji Manis</h2>
                <p class="empty-text">Mulailah dengan membuat janji manis pertama Anda untuk menunjukkan cinta dan komitmen Anda.</p>
                </div>
        <?php else: ?>
            <div class="promise-container">
                <?php foreach ($grouped_janji as $id => $janji): ?>
                    <div class="promise-card">
                        <div class="promise-header">
                            <span class="promise-category"><?php echo ucfirst($janji['kategori']); ?></span>
                            <h3>Janji Manis</h3>
                        </div>
                        
                        <div class="promise-content">
                            <p class="promise-text"><?php echo htmlspecialchars($janji['isi_janji']); ?></p>
                            
                            <div class="promise-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d M Y, H:i', strtotime($janji['tanggal_dibuat'])); ?>
                                <?php if ($janji['status'] === 'terpenuhi' && !empty($janji['tanggal_terpenuhi'])): ?>
                                    <span class="fulfillment-date">
                                        • Dipenuhi pada: <?php echo date('d M Y, H:i', strtotime($janji['tanggal_terpenuhi'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <span class="promise-status status-<?php echo $janji['status']; ?>">
                                <?php 
                                    echo ($janji['status'] == 'terpenuhi') 
                                        ? '<i class="fas fa-check-circle"></i> Terpenuhi' 
                                        : '<i class="fas fa-hourglass-half"></i> Belum Terpenuhi';
                                ?>
                            </span>
                            
                            <p class="promise-pembuat">Dibuat oleh: <?php echo $janji['pembuat_nama']; ?></p>
                            
                            <div class="proof-section">
                                <h4 class="proof-title">
                                    <i class="fas fa-camera-retro"></i> Bukti Janji
                                </h4>
                                
                                <?php if (empty($janji['bukti'])): ?>
                                    <div class="no-proof">
                                        <i class="far fa-frown" style="margin-right: 5px;"></i> Belum ada bukti untuk janji ini
                                    </div>
                                <?php else: ?>
                                    <div class="proof-items">
                                        <?php foreach ($janji['bukti'] as $bukti): ?>
                                            <div class="proof-item">
                                                <?php if ($bukti['jenis_bukti'] == 'gambar'): ?>
                                                    <p class="proof-text">Bukti gambar:</p>
                                                    <img src="<?php echo htmlspecialchars($bukti['file_url']); ?>" alt="Bukti Janji" class="proof-image">
                                                <?php elseif ($bukti['jenis_bukti'] == 'audio'): ?>
                                                    <p class="proof-text">Bukti audio:</p>
                                                    <audio controls class="proof-audio">
                                                        <source src="<?php echo htmlspecialchars($bukti['file_url']); ?>" type="audio/mpeg">
                                                        Browser Anda tidak mendukung elemen audio.
                                                    </audio>
                                                <?php elseif ($bukti['jenis_bukti'] == 'teks'): ?>
                                                    <p class="proof-text"><?php echo nl2br(htmlspecialchars($bukti['teks_bukti'])); ?></p>
                                                <?php elseif ($bukti['jenis_bukti'] == 'lokasi'): ?>
                                                    <p class="proof-text">Bukti lokasi:</p>
                                                    <div class="proof-location">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php echo htmlspecialchars($bukti['teks_bukti']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="proof-date">
                                                    <i class="far fa-clock"></i> 
                                                    <?php echo date('d M Y, H:i', strtotime($bukti['created_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Card Actions -->
                            <div class="card-actions">
                                <?php if ($janji['pembuat_id'] != $_SESSION['id_pengguna']): ?>
                                <button class="action-button add-proof-button" onclick="window.location.href='tambah_bukti.php?id_janji=<?php echo $id; ?>'">
                                    <i class="fas fa-plus-circle"></i> Tambah Bukti
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($janji['status'] === 'belum_terpenuhi'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="janji_id" value="<?php echo $id; ?>">
                                    <button type="submit" name="mark_fulfilled" class="action-button fulfill-button">
                                        <i class="fas fa-check-circle"></i> Tandai Terpenuhi
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($janji['pembuat_id'] == $_SESSION['id_pengguna']): ?>
                                <button class="action-button delete-button" onclick="showDeleteModal(<?php echo $id; ?>)">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <h3 class="modal-title"><i class="fas fa-exclamation-triangle" style="color: #C70039; margin-right: 10px;"></i>Konfirmasi Hapus</h3>
                    <p class="modal-text">Apakah Anda yakin ingin menghapus janji manis ini? Semua bukti terkait juga akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="modal-buttons">
                        <div class="modal-button cancel-button" onclick="hideDeleteModal()">
                            <i class="fas fa-times"></i> Batal
                        </div>
                        <form id="deleteForm" method="POST" action="hapus_janji.php" style="flex: 1;">
                            <input type="hidden" id="deleteJanjiId" name="janji_id" value="">
                            <button type="submit" class="modal-button confirm-button" style="width: 100%;">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
                    <?php endif; ?>
                </main>
                
                <script>
                    // Function to show delete confirmation modal
                    function showDeleteModal(janjiId) {
                        document.getElementById('deleteJanjiId').value = janjiId;
                        document.getElementById('deleteModal').style.display = 'block';
                    }
                    
                    // Function to hide delete confirmation modal
                    function hideDeleteModal() {
                        document.getElementById('deleteModal').style.display = 'none';
                    }
                    
                    // Close modal when clicking outside of it
                    window.onclick = function(event) {
                        const modal = document.getElementById('deleteModal');
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    }
                    
                    // Add floating hearts to the bukti_cinta page
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
                        
                        // Start heart animation
                        createHearts();
                    });
                </script>
            </body>
            </html>