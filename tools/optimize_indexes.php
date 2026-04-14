<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    echo "Starting Database Optimization with Correct Schema...\n";

    $optimizations = [
        "ALTER TABLE users ADD INDEX idx_role (role)",
        "ALTER TABLE users ADD INDEX idx_username (username)",
        "ALTER TABLE mahasiswa ADD INDEX idx_nim (nim)",
        "ALTER TABLE mahasiswa ADD INDEX idx_prodi (id_prodi)",
        "ALTER TABLE dosen ADD INDEX idx_nidn (nidn)",
        "ALTER TABLE krs ADD INDEX idx_id_mhs (id_mhs)",
        "ALTER TABLE krs ADD INDEX idx_id_jadwal (id_jadwal)",
        "ALTER TABLE presensi ADD INDEX idx_jadwal_pertemuan (id_jadwal, pertemuan_ke)",
        "ALTER TABLE tagihan ADD INDEX idx_mhs_status (id_mhs, status_lunas)",
        "ALTER TABLE jadwal_kuliah ADD INDEX idx_id_dosen (id_dosen)",
        "ALTER TABLE jadwal_kuliah ADD INDEX idx_id_mk (id_mk)",
        "ALTER TABLE audit_trail ADD INDEX idx_user_waktu (user_id, waktu)",
        "ALTER TABLE sistem_log_aktivitas ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE nilai_akhir ADD INDEX idx_krs (id_krs)"
    ];

    foreach ($optimizations as $sql) {
        try {
            $pdo->exec($sql);
            echo "SUCCESS: " . $sql . "\n";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] !== 1061) {
                echo "FAILED: " . $sql . " - " . $e->getMessage() . "\n";
            } else {
                echo "SKIPPED (Already exists): " . $sql . "\n";
            }
        }
    }
    echo "Optimization completed.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
