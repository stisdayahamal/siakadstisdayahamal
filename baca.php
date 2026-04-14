<?php
// baca.php
session_start();
define('ACCESS', true);
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT a.*, k.nama_kategori FROM artikel_publikasi a LEFT JOIN kategori_publikasi k ON a.id_kategori = k.id_kategori WHERE a.id_artikel = ?");
$stmt->execute([$id]);
$pub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pub) {
    die('Artikel tidak ditemukan atau telah dihapus.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pub['judul']) ?> - SIAKAD STIS Dayah Amal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f9; color: #1e293b; }
        .hero-article { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 60px 0 80px 0; border-radius: 0 0 40px 40px; }
        .article-card { margin-top: -60px; z-index: 10; position: relative; }
        .article-content img { max-width: 100%; height: auto; border-radius: 12px; margin: 20px 0; }
        .article-content { line-height: 1.8; font-size: 1.05rem; color: #475569; }
        .article-content h2, .article-content h3, .article-content h4 { color: #1e293b; font-weight: bold; margin-top: 30px; }
        .navbar-brand { font-weight: 700; color: white !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-absolute w-100 mt-3" style="z-index: 20;">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="public/img/stis_logo.png" alt="SIAKAD STIS" style="height: 50px;">
        </a>
    </div>
</nav>

<header class="hero-article text-center position-relative">
    <div class="container">
        <div class="mb-4 pt-5 pt-lg-4">
            <span class="badge bg-white text-success px-3 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($pub['tipe']) ?></span>
            <span class="badge text-white px-3 py-2 ms-2 opacity-75"><?= htmlspecialchars($pub['nama_kategori'] ?? 'Uncategorized') ?></span>
        </div>
        <h1 class="display-5 fw-bold mb-4 px-lg-5"><?= htmlspecialchars($pub['judul']) ?></h1>
        <p class="opacity-75 mb-0"><i class="bi bi-person me-1"></i><?= htmlspecialchars($pub['penulis']) ?> &nbsp;&bull;&nbsp; <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($pub['created_at'])) ?></p>
    </div>
</header>

<main class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden article-card bg-white">
                <?php if ($pub['gambar']): ?>
                    <img src="public/uploads/publikasi/<?= htmlspecialchars($pub['gambar']) ?>" class="card-img-top w-100" alt="<?= htmlspecialchars($pub['judul']) ?>" style="max-height: 500px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body p-4 p-md-5 article-content">
                    <?= $pub['isi'] ?>
                </div>
            </div>
            
            <div class="mt-5 text-center">
                <a href="index.php#publikasi" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold"><i class="bi bi-arrow-left me-2"></i>Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</main>

<footer class="bg-white py-4 mt-5 border-top">
    <div class="container text-center text-muted">
        <p class="mb-0">&copy; <?= date('Y') ?> STIS Dayah Amal. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
