<?php
// views/dosen/feedback.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'dosen') { header("Location: dashboard.php"); exit; }

$id_dosen = $_SESSION['user']['id_dosen'] ?? $_SESSION['user']['id'];

// Ambil Rekap Skor Kuesioner per Mata Kuliah (hanya dari MK yang diajar)
$query_rekap = "SELECT jk.id_jadwal, mk.nama_mk, mk.semester,
                COUNT(k.id_kuesioner) as total_responden, 
                IFNULL(AVG(k.rating), 0) as rata_rating
                FROM jadwal_kuliah jk
                JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
                LEFT JOIN kuesioner_dosen k ON jk.id_jadwal = k.id_jadwal
                WHERE jk.id_dosen = ?
                GROUP BY jk.id_jadwal, mk.nama_mk, mk.semester";

$stmt = $pdo->prepare($query_rekap);
$stmt->execute([$id_dosen]);
$rekap = $stmt->fetchAll();

// Jika ada parameter view untuk melihat komentar detail dari MK spesifik
$view_detail = $_GET['view'] ?? 0;
$komentar_detail = [];
if ($view_detail) {
    // Validasi dosen memiliki hak pada id_jadwal ini
    $validasi = $pdo->prepare("SELECT mk.nama_mk FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_jadwal=? AND jk.id_dosen=?");
    $validasi->execute([$view_detail, $id_dosen]);
    $mk_detail = $validasi->fetchColumn();
    
    if($mk_detail) {
        $stmt_komentar = $pdo->prepare("SELECT rating, komentar, created_at FROM kuesioner_dosen WHERE id_jadwal = ? ORDER BY created_at DESC");
        $stmt_komentar->execute([$view_detail]);
        $komentar_detail = $stmt_komentar->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Feedback Mahasiswa - SIAKAD Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f8f9fa}</style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4"><i class="bi bi-star-half text-warning me-2"></i>Laporan Umpan Balik Mahasiswa (EDOM)</h2>
            <p class="text-muted">Hasil rekap penilaian performa mengajar Anda dari mahasiswa diakhir semester secara mandiri dan anonim.</p>

            <div class="row g-4 mb-4">
                <?php if(count($rekap) > 0): ?>
                    <?php foreach($rekap as $r): 
                        $rata = round($r['rata_rating'], 1);
                        $bg = 'bg-white';
                        if($rata >= 4) $kualitas = '<span class="text-success"><i class="bi bi-emoji-smile-fill"></i> Sangat Baik</span>';
                        elseif($rata >= 3) $kualitas = '<span class="text-info"><i class="bi bi-emoji-neutral-fill"></i> Cukup</span>';
                        elseif($rata > 0) $kualitas = '<span class="text-danger"><i class="bi bi-emoji-frown-fill"></i> Kurang</span>';
                        else $kualitas = '<span class="text-muted"><i class="bi bi-clock"></i> Belum Dievaluasi</span>';
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 text-center p-3">
                            <h5 class="fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($r['nama_mk']) ?>"><?= htmlspecialchars($r['nama_mk']) ?></h5>
                            <small class="text-muted d-block mb-3">Semester <?= htmlspecialchars($r['semester']) ?></small>
                            
                            <h1 class="display-3 fw-bold text-warning mb-0">
                                <?= $rata > 0 ? $rata : '-' ?>
                            </h1>
                            <div class="fs-5 text-warning mb-2">
                                <?php 
                                    if($rata==0) echo '⭐⭐⭐⭐⭐ (Kosong)';
                                    else {
                                        for($i=1; $i<=5; $i++) echo $i <= round($rata) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; 
                                    }
                                ?>
                            </div>
                            
                            <p class="fw-bold mb-3"><?= $kualitas ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-3">
                                <span class="badge bg-light text-dark border"><i class="bi bi-people-fill me-1"></i><?= $r['total_responden'] ?> Mahasiswa</span>
                                <a href="?view=<?= $r['id_jadwal'] ?>" class="btn btn-sm btn-outline-primary fw-bold">Baca Komentar</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-emoji-neutral fs-1 d-block mb-2"></i> Belum ada rekapan kelas Anda untuk dinilai.
                    </div>
                <?php endif; ?>
            </div>

            <?php if($view_detail): ?>
            <!-- Laman Komentar Detail -->
            <div class="card border-0 shadow-sm rounded-4 border-top border-4 border-warning mb-5">
                <div class="card-header bg-white pt-4 pb-2 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-chat-quote-fill text-warning me-2"></i>Review Anonim: <?= htmlspecialchars($mk_detail) ?></h5>
                        <a href="feedback.php" class="btn btn-sm btn-light border"><i class="bi bi-x-lg"></i> Tutup Ulasan</a>
                    </div>
                </div>
                <div class="card-body p-4 bg-light">
                    <div class="row g-3">
                        <?php if(count($komentar_detail) > 0): ?>
                            <?php foreach($komentar_detail as $k): ?>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <div class="text-warning small fs-6">
                                                <?php for($i=1; $i<=5; $i++) echo $i <= $k['rating'] ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; ?>
                                            </div>
                                            <small class="text-muted"><i class="bi bi-incognito me-1"></i>Student &bull; <?= date('d/m/y', strtotime($k['created_at'])) ?></small>
                                        </div>
                                        <p class="text-dark fst-italic mb-0 fs-6">"<?= htmlspecialchars($k['komentar']) ?>"</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center text-muted py-4">Belum ada mahasiswa yang mensubmit review untuk mata kuliah ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
