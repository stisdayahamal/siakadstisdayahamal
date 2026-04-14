<?php
// tools/total_cleanup.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

echo "Memulai proses pembersihan total...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Daftar semua tabel
    $tables = [
        'absensi_pegawai', 'artikel_publikasi', 'audit_trail', 'calon_mhs',
        'dosen', 'fakultas', 'izin_cuti', 'jadwal_kuliah', 'jenis_kelas',
        'kategori_publikasi', 'krs', 'kuesioner_dosen', 'kurikulum',
        'mahasiswa', 'mata_kuliah', 'nilai_akhir', 'notifikasi',
        'pengaturan_nilai', 'pmb_gelombang', 'pmb_jalur', 'pmb_periode',
        'presensi', 'prodi', 'sistem_log_aktivitas', 'support_ticket',
        'tagihan', 'tahun_akademik', 'tugas_akademik', 'tugas_kumpul',
        'users', 'waktu_kuliah'
    ];

    foreach ($tables as $table) {
        if ($table === 'users') {
            // Keep the admin user
            echo "Membersihkan tabel $table (menyisakan admin)...\n";
            $pdo->exec("DELETE FROM $table WHERE username != 'admin'");
            $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        } else {
            echo "Truncating $table...\n";
            $pdo->exec("TRUNCATE TABLE $table");
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Pembersihan database selesai.\n";

    // Bersihkan file uploads
    echo "Membersihkan file di folder uploads/...\n";
    $uploadDir = __DIR__ . '/../uploads/';
    $files = glob($uploadDir . '*'); 
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.htaccess') {
            unlink($file);
            echo "Dihapus: " . basename($file) . "\n";
        }
    }
    echo "Pembersihan file selesai.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
}
