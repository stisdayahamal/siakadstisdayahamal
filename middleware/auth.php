<?php
// middleware/auth.php
if (!defined('ACCESS')) define('ACCESS', true);
require_once dirname(__DIR__) . '/includes/load_settings.php';
require_once dirname(__DIR__) . '/includes/academic_helper.php';
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    // Force logout jika session tidak valid
    session_unset();
    session_destroy();
    header('Location: /siakadstisdayahamal/auth/login.php?force=1');
    exit;
}

// Otomatis RBAC berdasarkan direktori URL
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
$role = $_SESSION['user']['role'];

function show_403($pesan) {
    header('HTTP/1.0 403 Forbidden');
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>403 Akses Ditolak</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="/siakadstisdayahamal/public/css/style.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
        <div class="text-center p-5 card border-0 shadow-lg rounded-4" style="max-width: 500px;">
            <i class="bi bi-shield-lock-fill text-danger mb-3" style="font-size: 5rem;"></i>
            <h2 class="fw-bold text-dark">Akses Ditolak!</h2>
            <p class="text-muted mb-4">' . $pesan . '</p>
            <button onclick="window.history.back()" class="btn btn-primary px-4 rounded-pill fw-bold"><i class="bi bi-arrow-left me-2"></i>Kembali ke Jalur Aman</button>
        </div>
    </body>
    </html>';
    exit;
}

if (strpos($script_path, '/views/admin/') !== false && $role !== 'admin') {
    show_403("Maaf, ruangan ini khusus untuk Administrator. Izin otorisasi sesi Anda tidak valid untuk area ini.");
}
if (strpos($script_path, '/views/dosen/') !== false && $role !== 'dosen') {
    show_403("Akses halaman ini sangat dilarang. Area ini eksklusif bagi staf Pengajar (Dosen) teregistrasi.");
}
if (strpos($script_path, '/views/mahasiswa/') !== false && $role !== 'mahasiswa') {
    show_403("Hanya Mahasiswa aktif yang berhak memproses navigasi di ruangan virtual ini.");
}
