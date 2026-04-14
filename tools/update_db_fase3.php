<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

$queries = [
    // 1. Tabel Modul Rutinitas
    "CREATE TABLE IF NOT EXISTS absensi_pegawai (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tanggal DATE NOT NULL,
        jam_in TIME DEFAULT NULL,
        jam_out TIME DEFAULT NULL,
        status ENUM('Hadir','Sakit','Izin','Alpa') DEFAULT 'Hadir'
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS izin_cuti (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tipe ENUM('Cuti','Izin','Sakit') NOT NULL,
        tgl_mulai DATE NOT NULL,
        tgl_selesai DATE NOT NULL,
        alasan TEXT NOT NULL,
        status ENUM('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS support_ticket (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subjek VARCHAR(255) NOT NULL,
        pesan TEXT NOT NULL,
        balasan TEXT DEFAULT NULL,
        status ENUM('Open','In Progress','Closed') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    // 2. Tabel Akademik Tambahan
    "CREATE TABLE IF NOT EXISTS fakultas (
        id_fakultas INT AUTO_INCREMENT PRIMARY KEY,
        nama_fakultas VARCHAR(150) NOT NULL UNIQUE
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS jenis_kelas (
        id_jenis INT AUTO_INCREMENT PRIMARY KEY,
        nama_jenis VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS waktu_kuliah (
        id_waktu INT AUTO_INCREMENT PRIMARY KEY,
        keterangan VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    // 3. Tabel Publikasi (CMS)
    "CREATE TABLE IF NOT EXISTS kategori_publikasi (
        id_kategori INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS artikel_publikasi (
        id_artikel INT AUTO_INCREMENT PRIMARY KEY,
        id_kategori INT DEFAULT NULL,
        tipe ENUM('Berita','Pengumuman','Galeri') NOT NULL,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        gambar VARCHAR(255) DEFAULT NULL,
        penulis VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_kategori) REFERENCES kategori_publikasi(id_kategori) ON DELETE SET NULL
    ) ENGINE=InnoDB;",

    // 4. Tabel PMB Lengkap
    "CREATE TABLE IF NOT EXISTS pmb_periode (
        id_periode INT AUTO_INCREMENT PRIMARY KEY,
        nama_periode VARCHAR(50) NOT NULL,
        tahun_ajaran VARCHAR(20) NOT NULL,
        status_aktif TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS pmb_jalur (
        id_jalur INT AUTO_INCREMENT PRIMARY KEY,
        nama_jalur VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS pmb_gelombang (
        id_gelombang INT AUTO_INCREMENT PRIMARY KEY,
        id_periode INT NOT NULL,
        nama_gelombang VARCHAR(100) NOT NULL,
        tgl_mulai DATE NOT NULL,
        tgl_selesai DATE NOT NULL,
        biaya BIGINT NOT NULL,
        FOREIGN KEY (id_periode) REFERENCES pmb_periode(id_periode) ON DELETE CASCADE
    ) ENGINE=InnoDB;",

    // 5. Tabel Sistem Audit dan Notifikasi
    "CREATE TABLE IF NOT EXISTS sistem_log_aktivitas (
        id_log INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        aksi VARCHAR(50) NOT NULL,
        entitas VARCHAR(100) NOT NULL,
        entitas_id VARCHAR(50) DEFAULT NULL,
        nilai_lama TEXT DEFAULT NULL,
        nilai_baru TEXT DEFAULT NULL,
        ip_address VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS notifikasi (
        id_notif INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        judul VARCHAR(255) NOT NULL,
        pesan TEXT NOT NULL,
        link VARCHAR(255) DEFAULT '#',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;"
];

$errors = 0;
foreach ($queries as $i => $q) {
    try {
        $pdo->exec($q);
        echo "Query " . ($i+1) . " berhasil.\n";
    } catch (PDOException $e) {
        $errors++;
        echo "Query " . ($i+1) . " gagal: " . $e->getMessage() . "\n";
    }
}

// Alter column terpisah
try {
    $check_fakultas = $pdo->query("SHOW COLUMNS FROM prodi LIKE 'id_fakultas'")->rowCount();
    if ($check_fakultas == 0) {
        $pdo->exec("ALTER TABLE prodi ADD COLUMN id_fakultas INT DEFAULT NULL");
        echo "Alter prodi berhasil.\n";
    }
} catch (PDOException $e) {
    $errors++;
    echo "Alter prodi gagal: " . $e->getMessage() . "\n";
}

if ($errors === 0) {
    echo "\nBERHASIL: Semua tabel Fase 3 siap digunakan.";
} else {
    echo "\nSELESAI DENGAN $errors ERROR.";
}
