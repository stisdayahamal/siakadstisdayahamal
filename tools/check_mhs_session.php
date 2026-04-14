<?php
// tools/check_mhs_session.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

echo "--- USERS ---\n";
$users = $pdo->query("SELECT id_user, username, role, id_mhs FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\n--- TAGIHAN ---\n";
$tagihan = $pdo->query("SELECT * FROM tagihan")->fetchAll(PDO::FETCH_ASSOC);
print_r($tagihan);

echo "\n--- MAHASISWA ---\n";
$mhs = $pdo->query("SELECT id_mhs, nim, nama FROM mahasiswa")->fetchAll(PDO::FETCH_ASSOC);
print_r($mhs);
