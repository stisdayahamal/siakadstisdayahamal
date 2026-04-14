<?php
define('ACCESS', true);
require 'config/db.php';
$tables = ['jadwal_kuliah', 'krs', 'mata_kuliah'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $cols = $pdo->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo " - " . $c['Field'] . "\n";
    }
}
