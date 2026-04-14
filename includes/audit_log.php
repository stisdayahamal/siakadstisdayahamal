<?php
// includes/audit_log.php
function audit_log($aksi, $detail = '') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = $_SESSION['user']['id'] ?? null;
    $username = $_SESSION['user']['username'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    require __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare('INSERT INTO audit_trail (user_id, username, aksi, detail, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $username, $aksi, $detail, $ip]);
}
