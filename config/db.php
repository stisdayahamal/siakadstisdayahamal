<?php
// config/db.php

require_once __DIR__ . '/../includes/load_env.php';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'siakadstisdayahamal';
$db_user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => true,
];

if (!defined('ACCESS')) die('Direct access not permitted.');
try {
    $pdo = new PDO($dsn, $db_user, $pass, $options);
} catch (PDOException $e) {
    // Log error ke file, tampilkan pesan umum ke user
    error_log('DB ERROR: ' . $e->getMessage());
    die('Terjadi kesalahan koneksi database. Silakan hubungi admin.');
}
