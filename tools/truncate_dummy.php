<?php
// tools/truncate_dummy.php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin_utama') {
    http_response_code(403);
    die('Akses ditolak. Hanya Admin Utama yang dapat truncate data.');
}
// Daftar tabel yang boleh di-TRUNCATE (kecuali master)
$exclude = ['prodi', 'mata_kuliah'];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    if (!in_array($table, $exclude)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec("TRUNCATE TABLE `$table`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        echo "Tabel $table dikosongkan.<br>";
    }
}
echo "<b>SELESAI.</b>";
