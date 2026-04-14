<?php
// tools/migrate_settings.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        _key VARCHAR(50) UNIQUE NOT NULL,
        _value TEXT,
        label VARCHAR(100),
        type ENUM('text', 'textarea', 'file') DEFAULT 'text',
        category VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $settings = [
        ['nama_kampus', 'STIS Dayah Amal', 'Nama Kampus', 'text', 'Identity'],
        ['alamat_kampus', 'Lhokseumawe, Aceh', 'Alamat Kampus', 'textarea', 'Identity'],
        ['email_kampus', 'info@stisdayahamal.ac.id', 'Email Kampus', 'text', 'Identity'],
        ['kontak_kampus', '0812-3456-7890', 'Kontak Kampus', 'text', 'Identity'],
        ['rekening_bank', '0123-456-789-0 (Bank Aceh Syariah)', 'Nomor Rekening Kampus', 'text', 'Finance'],
        ['narek_bank', 'STIS Dayah Amal', 'Nama Rekening', 'text', 'Finance'],
        ['instruksi_pembayaran', 'Silakan melakukan transfer ke rekening resmi kampus dan unggah bukti bayar melalui menu Keuangan.', 'Instruksi Pembayaran', 'textarea', 'Finance'],
        ['pesan_dashboard', 'Selamat datang di Sistem Informasi Akademik Terpadu. Pastikan data profil Anda sudah benar.', 'Pesan Welcome Dashboard', 'textarea', 'Dashboard'],
        ['logo_kampus', 'public/img/logo_default.png', 'Logo Kampus', 'file', 'Identity']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO pengaturan (_key, _value, label, type, category) VALUES (?, ?, ?, ?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }

    echo "Migrasi tabel pengaturan berhasil diselesaikan!\n";
} catch (Exception $e) {
    echo "Migrasi gagal: " . $e->getMessage() . "\n";
}
