<?php
// tools/update_db_fase4.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Tabel tugas_akademik (dibuat oleh dosen untuk kelas tertentu)
    $sql_tugas = "
    CREATE TABLE IF NOT EXISTS tugas_akademik (
        id_tugas INT AUTO_INCREMENT PRIMARY KEY,
        id_jadwal INT NOT NULL,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT NOT NULL,
        lampiran VARCHAR(255) DEFAULT NULL,
        batas_waktu DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_jadwal) REFERENCES jadwal_kuliah(id_jadwal) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_tugas);
    echo "Tabel tugas_akademik berhasil dibuat/ada.\n";

    // 2. Tabel tugas_kumpul (jawaban/tugas dari mahasiswa)
    $sql_kumpul = "
    CREATE TABLE IF NOT EXISTS tugas_kumpul (
        id_kumpul INT AUTO_INCREMENT PRIMARY KEY,
        id_tugas INT NOT NULL,
        id_mhs INT NOT NULL,
        file_jawaban VARCHAR(255) NOT NULL,
        waktu_kumpul DATETIME NOT NULL,
        nilai INT DEFAULT NULL,
        catatan_dosen TEXT DEFAULT NULL,
        FOREIGN KEY (id_tugas) REFERENCES tugas_akademik(id_tugas) ON DELETE CASCADE,
        FOREIGN KEY (id_mhs) REFERENCES mahasiswa(id_mhs) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_kumpul);
    echo "Tabel tugas_kumpul berhasil dibuat/ada.\n";

    // 3. Tabel kuesioner_dosen (feedback dari mahasiswa untuk dosen di kelas terkait)
    // Sengaja anonim sehingga nama mahasiswa tidak bisa dilacak langsung melalui UI biasa
    $sql_kuesioner = "
    CREATE TABLE IF NOT EXISTS kuesioner_dosen (
        id_kuesioner INT AUTO_INCREMENT PRIMARY KEY,
        id_jadwal INT NOT NULL,
        id_mhs INT NOT NULL,
        rating TINYINT(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
        komentar TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_jadwal) REFERENCES jadwal_kuliah(id_jadwal) ON DELETE CASCADE,
        FOREIGN KEY (id_mhs) REFERENCES mahasiswa(id_mhs) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_kuesioner);
    echo "Tabel kuesioner_dosen berhasil dibuat/ada.\n";

    // Modifikasi tabel users jika belum ada kolom 'foto'
    $hasFoto = $pdo->query("SHOW COLUMNS FROM users LIKE 'foto'")->rowCount();
    if (!$hasFoto) {
        $pdo->exec("ALTER TABLE users ADD COLUMN foto VARCHAR(255) DEFAULT NULL");
        echo "Kolom 'foto' ditambahkan ke tabel users.\n";
    }

    $pdo->commit();
    echo "\n=== MIGRATION FASE 4 SELESAI ===\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Gagal: " . $e->getMessage() . "\n";
}
