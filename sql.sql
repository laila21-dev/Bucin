CREATE TABLE pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengguna VARCHAR(50) NOT NULL,
    surel VARCHAR(100) UNIQUE NOT NULL,
    kata_sandi VARCHAR(255) NOT NULL,
    ingat_aku BOOLEAN DEFAULT FALSE,
    waktu_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_diubah TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE janji_manis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna INT NOT NULL,
    isi_janji TEXT NOT NULL,
    janji_akan_terpenuhi BOOLEAN DEFAULT FALSE,
    kategori ENUM('serius', 'lucu', 'random', 'bohong') DEFAULT 'serius',
    status ENUM('belum_terpenuhi', 'terpenuhi') DEFAULT 'belum_terpenuhi',
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_terpenuhi TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id)
);

CREATE TABLE bukti_janji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_janji INT NOT NULL,
    jenis_bukti ENUM('gambar', 'audio', 'teks', 'lokasi') NOT NULL,
    file_url VARCHAR(255), -- URL ke file bukti (gambar/audio)
    teks_bukti TEXT,        -- Untuk jenis 'teks' atau deskripsi bukti
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_janji) REFERENCES janji_manis(id)
);
