<?php
define('ACCESS', true);
require 'config/db.php';
$pdo->exec("UPDATE users u JOIN mahasiswa m ON u.username = m.nim SET u.id_mhs = m.id_mhs WHERE u.role = 'mahasiswa' AND u.id_mhs IS NULL");
echo "Database cleaned up!";
