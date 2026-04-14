<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Tambah status_kuliah ke tabel mahasiswa jika belum ada
    $stmt = $pdo->query("SHOW COLUMNS FROM mahasiswa LIKE 'status_kuliah'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE mahasiswa ADD COLUMN status_kuliah ENUM('Aktif','Cuti','Lulus','Keluar') NOT NULL DEFAULT 'Aktif'");
        echo "Kolom status_kuliah ditambahkan.\n";
    }

    // 2. Tambah status_aktif ke tabel tahun_akademik jika belum ada
    $stmt = $pdo->query("SHOW COLUMNS FROM tahun_akademik LIKE 'status_aktif'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tahun_akademik ADD COLUMN status_aktif TINYINT(1) NOT NULL DEFAULT 0");
        echo "Kolom status_aktif ditambahkan.\n";
    }

    // 3. Buat tabel presensi jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS presensi (
        id_presensi INT AUTO_INCREMENT PRIMARY KEY,
        id_jadwal INT NOT NULL,
        id_mhs INT NOT NULL,
        pertemuan_ke INT NOT NULL,
        status_hadir ENUM('H','S','I','A') NOT NULL DEFAULT 'A',
        tanggal DATE NOT NULL,
        UNIQUE KEY uq_presensi (id_jadwal, id_mhs, pertemuan_ke),
        FOREIGN KEY (id_jadwal) REFERENCES jadwal_kuliah(id_jadwal) ON DELETE CASCADE,
        FOREIGN KEY (id_mhs) REFERENCES mahasiswa(id_mhs) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Tabel presensi dipastikan ada.\n";

    $pdo->commit();
    echo "Update database selesai.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Gagal: " . $e->getMessage() . "\n";
}
