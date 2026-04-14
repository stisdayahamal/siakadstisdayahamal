<?php
// includes/load_settings.php
require_once __DIR__ . '/../config/db.php';

$sys = [];
try {
    $res = $pdo->query("SELECT _key, _value FROM pengaturan")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $row) {
        $sys[$row['_key']] = $row['_value'];
    }
} catch (Exception $e) {
    // Fail silently or use defaults
}

// Global defaults if table empty
if (!isset($sys['nama_kampus'])) $sys['nama_kampus'] = 'SIAKAD ERP';
if (!isset($sys['logo_kampus'])) $sys['logo_kampus'] = 'public/img/logo_default.png';
