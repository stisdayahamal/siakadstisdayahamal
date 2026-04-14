<?php
define('ACCESS', true);
require 'config/db.php';
file_put_contents('test_db.json', json_encode([
    'calon' => $pdo->query('EXPLAIN calon_mhs')->fetchAll(PDO::FETCH_ASSOC),
    'mhs' => $pdo->query('EXPLAIN mahasiswa')->fetchAll(PDO::FETCH_ASSOC)
], JSON_PRETTY_PRINT));
