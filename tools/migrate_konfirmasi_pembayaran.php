<?php
// tools/migrate_konfirmasi_pembayaran.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Buat tabel konfirmasi_pembayaran
    $pdo->exec("CREATE TABLE IF NOT EXISTS konfirmasi_pembayaran (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_tagihan INT NOT NULL,
        bukti_bayar VARCHAR(255) NOT NULL,
        status ENUM('Menunggu', 'Disetujui', 'Ditolak') DEFAULT 'Menunggu',
        catatan_admin TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_tagihan) REFERENCES tagihan(id_tagihan) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Perbaiki data tagihan yang id_mhs nya kosong (link ke id_mhs berdasarkan id_calon)
    // Mencari tagihan yang punya id_calon tapi id_mhs NULL, dan menyambungkannya ke id_mhs yang NIM-nya berasal dari calon tersebut
    // Mengingat pmb_calon.php meng-generate NIM dan menyimpan id_prodi dsb, kita bisa join.
    $sql_fix = "
        UPDATE tagihan t
        JOIN calon_mhs c ON t.id_calon = c.id_calon
        JOIN mahasiswa m ON m.nik = c.nik
        SET t.id_mhs = m.id_mhs
        WHERE t.id_mhs IS NULL AND t.id_calon IS NOT NULL
    ";
    $pdo->exec($sql_fix);

    echo "Migrasi Konfirmasi Pembayaran dan perbaikan data berhasil!\n";
} catch (Exception $e) {
    echo "Migrasi gagal: " . $e->getMessage() . "\n";
}
