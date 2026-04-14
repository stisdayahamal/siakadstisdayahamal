<?php
// tools/migrate_tagihan.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    // MySQL DDL (ALTER TABLE) ignores transactions.
    
    echo "Modifikasi kolom id_mhs agar boleh NULL...\n";
    $pdo->exec("ALTER TABLE tagihan MODIFY id_mhs INT(11) NULL;");

    echo "Tambah kolom id_calon...\n";
    // Check if column exists first to avoid error
    $check = $pdo->query("SHOW COLUMNS FROM tagihan LIKE 'id_calon'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tagihan ADD COLUMN id_calon INT(11) NULL AFTER id_mhs;");
        echo "Tambah Foreign Key untuk id_calon...\n";
        $pdo->exec("ALTER TABLE tagihan ADD CONSTRAINT fk_tagihan_calon FOREIGN KEY (id_calon) REFERENCES calon_mhs(id_calon) ON DELETE SET NULL;");
    } else {
        echo "Kolom id_calon sudah ada.\n";
    }

    echo "Migrasi database tagihan berhasil!\n";
} catch (Exception $e) {
    echo "Migrasi gagal: " . $e->getMessage() . "\n";
}
