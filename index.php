<?php
// index.php
session_start();
define('ACCESS', true);
require_once 'config/db.php';

// Fetch latest publications
try {
    $stmt = $pdo->query("SELECT a.*, k.nama_kategori FROM artikel_publikasi a LEFT JOIN kategori_publikasi k ON a.id_kategori = k.id_kategori ORDER BY a.created_at DESC LIMIT 3");
    $publikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $publikasi = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD - STIS Dayah Amal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f9; color: #1e293b; }
        /* Green & Gold Gradient */
        .hero { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 100px 0; border-radius: 0 0 50px 50px; }
        .card-feature { border: none; border-radius: 20px; transition: transform 0.3s ease; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .card-feature:hover { transform: translateY(-10px); }
        .btn-premium { background: #d4af37; color: white; border-radius: 12px; padding: 12px 30px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: background 0.3s; line-height: 1; }
        .btn-premium:hover { background: #b5952f; color: white; }
        .nav-link { font-weight: 600; color: white !important; margin-left: 20px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-absolute w-100 mt-3">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="public/img/stis_logo.png" alt="SIAKAD STIS" style="height: 50px;">
        </a>
        <div class="ms-auto">
            <?php if (isset($_SESSION['user'])): ?>
                <a href="views/admin/dashboard.php" class="btn btn-outline-light rounded-pill">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-outline-light rounded-pill px-4">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="hero text-center">
    <div class="container">
        <h1 class="display-3 fw-bold mb-4">Sistem Informasi Akademik</h1>
        <p class="lead mb-5 opacity-75">Solusi terintegrasi untuk manajemen pendidikan Perguruan Tinggi yang transparan, aman, dan efisien.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="views/pmb/daftar.php" class="btn-premium"><i class="bi bi-person-plus-fill me-2"></i>Pendaftaran Mahasiswa Baru</a>
            <a href="#fitur" class="btn btn-light rounded-pill px-4 py-3 fw-bold text-success">Pelajari Modul</a>
        </div>
    </div>
</header>

<section id="fitur" class="container my-5 py-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-feature p-4 text-center">
                <div class="display-4 text-primary mb-3">🎓</div>
                <h3 class="fw-bold">Manajemen Mahasiswa</h3>
                <p class="text-muted">Data mahasiswa terintegrasi dengan standar PDDIKTI untuk pelaporan yang akurat.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-feature p-4 text-center">
                <div class="display-4 text-success mb-3">📚</div>
                <h3 class="fw-bold">Kurikulum & KRS</h3>
                <p class="text-muted">Pengaturan mata kuliah dan pengisian KRS online yang mudah dan cepat.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-feature p-4 text-center">
                <div class="display-4 text-warning mb-3">💰</div>
                <h3 class="fw-bold">Monitoring Keuangan</h3>
                <p class="text-muted">Pantau status pendaftaran dan SPP mahasiswa secara real-time.</p>
            </div>
        </div>
    </div>
</section>

<section id="publikasi" class="container my-5 py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Berita & Pengumuman</h2>
        <p class="text-muted">Informasi terbaru seputar kampus STIS Dayah Amal</p>
    </div>
    <div class="row g-4 justify-content-center">
        <?php if (!empty($publikasi)): ?>
            <?php foreach ($publikasi as $pub): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-feature">
                        <?php if ($pub['gambar']): ?>
                            <img src="public/uploads/publikasi/<?= htmlspecialchars($pub['gambar']) ?>" class="card-img-top" alt="<?= htmlspecialchars($pub['judul']) ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-newspaper text-secondary opacity-50" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><?= htmlspecialchars($pub['tipe']) ?></span>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($pub['created_at'])) ?></small>
                            </div>
                            <h5 class="card-title fw-bold mb-3"><a href="baca.php?id=<?= $pub['id_artikel'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($pub['judul']) ?></a></h5>
                            <p class="card-text text-secondary" style="display: -webkit-box; -webkit-line-clamp: 3; line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.95rem;">
                                <?= strip_tags($pub['isi']) ?>
                            </p>
                            <div class="mt-auto pt-3">
                                <a href="baca.php?id=<?= $pub['id_artikel'] ?>" class="text-primary text-decoration-none fw-semibold">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted">
                <p>Belum ada publikasi saat ini.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer class="bg-white py-4 mt-5 border-top">
    <div class="container text-center text-muted">
        <p>&copy; <?= date('Y') ?> STIS Dayah Amal. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
