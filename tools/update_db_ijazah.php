<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM mahasiswa LIKE 'no_ijazah'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE mahasiswa ADD COLUMN no_ijazah VARCHAR(50) DEFAULT NULL");
        echo "Kolom no_ijazah berhasil ditambahkan.\n";
    } else {
        echo "Kolom no_ijazah sudah ada.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
