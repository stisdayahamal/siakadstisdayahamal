<?php
// views/mahasiswa/kuesioner.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'mahasiswa') { header("Location: dashboard.php"); exit; }

$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
$sukses = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_kuesioner'])) {
    $id_jadwal = $_POST['id_jadwal'];
    $rating = $_POST['rating'];
    $komentar = trim($_POST['komentar']);
    
    // Validasi apakah jadwal ini benar ada di KRS dan belum dinilai
    $cek = $pdo->prepare("SELECT k.id_krs FROM krs k WHERE k.id_jadwal = ? AND k.id_mhs = ? AND k.status_krs = 'setuju'");
    $cek->execute([$id_jadwal, $id_mhs]);
    if ($cek->fetch()) {
        $cek_sudah = $pdo->prepare("SELECT COUNT(*) FROM kuesioner_dosen WHERE id_jadwal = ? AND id_mhs = ?");
        $cek_sudah->execute([$id_jadwal, $id_mhs]);
        if($cek_sudah->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO kuesioner_dosen (id_jadwal, id_mhs, rating, komentar) VALUES (?, ?, ?, ?)")
                ->execute([$id_jadwal, $id_mhs, $rating, $komentar]);
            $sukses = "Terima kasih atas partisipasi Anda. Umpan balik disetujui.";
        } else {
            $error = "Anda sudah pernah menilai mata kuliah ini.";
        }
    } else {
        $error = "Akses evaluasi ditolak.";
    }
}

// Ambil list Dosen/Jadwal yg diregistrasi Mahasiswa
$sql = "SELECT jk.id_jadwal, mk.nama_mk, d.nama AS nama_dosen, kues.rating, kues.komentar
        FROM krs k
        JOIN jadwal_kuliah jk ON k.id_jadwal = jk.id_jadwal
        JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
        JOIN dosen d ON jk.id_dosen = d.id_dosen
        LEFT JOIN kuesioner_dosen kues ON jk.id_jadwal = kues.id_jadwal AND kues.id_mhs = ?
        WHERE k.id_mhs = ? AND k.status_krs = 'setuju'
        ORDER BY mk.nama_mk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_mhs, $id_mhs]);
$kuesioner_list = $stmt->fetchAll();

// Hitung rekap
$total_mk = count($kuesioner_list);
$total_dinilai = 0;
foreach($kuesioner_list as $kl) { if($kl['rating'] !== null) $total_dinilai++; }
$progress = $total_mk > 0 ? round(($total_dinilai / $total_mk) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Kuesioner EDOM - <?= htmlspecialchars($sys['nama_kampus']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body{background:#f4f6f9}
        .star-rating { flex-direction: row-reverse; display: inline-flex; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2rem; color: #ccc; cursor: pointer; transition: 0.2s; padding: 0 5px; }
        .star-rating input:checked ~ label, 
        .star-rating label:hover, 
        .star-rating label:hover ~ label { color: #ffca28; }
    </style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-ui-radios-grid text-primary me-2"></i>Evaluasi Dosen Oleh Mahasiswa (EDOM)</h2>
            </div>
            <p class="text-muted">Partisipasi Anda sangat berharga. Berikan penilaian terhadap dosen untuk meningkatkan mutu pengajaran.</p>
            
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Progres Pengisian Kuesioner (<?= $total_dinilai ?>/<?= $total_mk ?> Mata Kuliah)</h6>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar <?= $progress == 100 ? 'bg-success' : 'bg-primary' ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                            <span class="fw-bold"><?= $progress ?>% Selesai</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php if(count($kuesioner_list) > 0): ?>
                    <?php foreach($kuesioner_list as $k): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-body">
                                <h5 class="fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($k['nama_mk']) ?>"><?= htmlspecialchars($k['nama_mk']) ?></h5>
                                <div class="mb-3 text-secondary fw-bold small"><i class="bi bi-person-workspace me-1"></i><?= htmlspecialchars($k['nama_dosen']) ?></div>
                                
                                <?php if($k['rating'] !== null): ?>
                                    <div class="text-center p-3 rounded-4" style="background: #f8f9fa;">
                                        <div class="fs-1 text-warning mb-2">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $k['rating'] ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>'; ?>
                                        </div>
                                        <h6 class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Sudah Dievaluasi</h6>
                                        <small class="text-muted d-block mt-2 fst-italic">"<?= htmlspecialchars($k['komentar']) ?>"</small>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-primary w-100 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEvaluasi" 
                                        onclick="setEval('<?= $k['id_jadwal'] ?>', '<?= htmlspecialchars($k['nama_mk'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_dosen'], ENT_QUOTES) ?>')">
                                        Isi EDOM Sekarang
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox-fill fs-1 text-muted mb-2 d-block"></i>
                        <h4 class="text-muted mb-0">Wah! Kosong.</h4>
                        <p class="text-secondary small">KRS kamu kosong atau belum ada mata kuliah yang disetujui semester ini.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </main>
    </div>
</div>

<!-- Modal Kuesioner -->
<div class="modal fade" id="modalEvaluasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-white border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body text-center pt-0 px-4">
                    <input type="hidden" name="id_jadwal" id="formIdJadwal">
                    <i class="bi bi-patch-question text-primary" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold mt-2 mb-1" id="labelMk">NAMA MK</h5>
                    <p class="text-muted mb-4">Pengajar: <strong id="labelDosen" class="text-dark">DOSEN</strong></p>
                    
                    <h6 class="fw-bold text-dark">Berapa tingkat kepuasan Anda?</h6>
                    <div class="star-rating mb-2">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5" title="5 Bintang - Sangat Baik">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 Bintang - Baik">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 Bintang - Cukup">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 Bintang - Buruk">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 Bintang - Sangat Buruk">&#9733;</label>
                    </div>
                    
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small">Kritik & Saran Membangun (Anonim):</label>
                        <textarea name="komentar" class="form-control" rows="3" placeholder="Sampaikan komentar positif atau perbaikan (misal: Kurang diskus, Terlalu cepat)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center pb-4">
                    <button type="submit" name="submit_kuesioner" class="btn btn-primary px-5 fw-bold rounded-pill">Kirim Penilaian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setEval(id_jadwal, nama_mk, nama_dosen) {
        document.getElementById('formIdJadwal').value = id_jadwal;
        document.getElementById('labelMk').innerText = nama_mk;
        document.getElementById('labelDosen').innerText = nama_dosen;
    }
</script>
</body>
</html>
