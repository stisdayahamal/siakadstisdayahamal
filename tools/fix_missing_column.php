<?php
// tools/fix_missing_column.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    echo "Checking tagihan columns...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM tagihan LIKE 'tanggal_bayar'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tagihan ADD COLUMN tanggal_bayar DATE NULL AFTER status_lunas");
        echo "Column 'tanggal_bayar' added successfully.\n";
    } else {
        echo "Column 'tanggal_bayar' already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
