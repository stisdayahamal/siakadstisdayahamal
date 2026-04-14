<?php
// tools/verify_cleanup.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

$tables = ['users', 'mahasiswa', 'dosen', 'mata_kuliah', 'prodi'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    echo "$table: " . $stmt->fetchColumn() . "\n";
}

if (!is_dir(__DIR__ . '/../uploads')) {
    echo "uploads/ directory does not exist\n";
} else {
    $files = glob(__DIR__ . '/../uploads/*');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.htaccess') $count++;
    }
    echo "uploads files count: $count\n";
}
