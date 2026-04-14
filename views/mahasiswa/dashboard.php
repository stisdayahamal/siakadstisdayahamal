<?php
// views/mahasiswa/dashboard.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$id_mhs = $user['id_mhs'] ?? 0;

// Ambil Profil Mahasiswa
$stmt = $pdo->prepare('SELECT m.*, p.nama_prodi FROM mahasiswa m LEFT JOIN prodi p ON m.id_prodi = p.id_prodi WHERE m.id_mhs = ?');
$stmt->execute([$id_mhs]);
$mhs_info = $stmt->fetch();

if (!$mhs_info) {
    echo "<div style='padding:20px; border:1px solid red; background:#fee; border-radius:10px; margin:20px;'>";
    echo "<h3 style='margin:0 0 10px 0;'>Profil Mahasiswa Tidak Ditemukan</h3>";
    echo "<p>Akun login Anda (ID: {$user['id']}) belum terhubung dengan data Master Mahasiswa.</p>";
    echo "<p>ID Mahasiswa di Sesi: " . ($user['id_mhs'] ?: 'KOSONG') . "</p>";
    echo "<hr><p style='font-size:0.8em;'>Saran: Coba Logout dan Login kembali. Jika masih muncul, hubungi Administrator untuk sinkronisasi ulang NIM.</p>";
    echo "</div>";
    die();
}

// Foto profil
$foto_url = !empty($mhs_info['foto_profil']) 
    ? '../../public/uploads/profil/' . $mhs_info['foto_profil'] 
    : 'https://ui-avatars.com/api/?name=' . urlencode($mhs_info['nama'] ?? 'MHS') . '&background=198754&color=fff&size=80';

// Ambil SKS Lulus & IPK (Menggunakan Helper DIKTI)
$ipk = get_ipk_mahasiswa($pdo, $id_mhs);
$jatah_sks = get_jatah_sks_by_ipk($ipk);

// SKS Lulus
$stmt_sks = $pdo->prepare('SELECT SUM(mk.sks) FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE k.id_mhs = ? AND k.status_krs="setuju" AND n.bobot_4_0 >= 1.0');
$stmt_sks->execute([$id_mhs]);
$total_sks_lulus = $stmt_sks->fetchColumn() ?: 0;
$target_sks = 144; // Standard Bachelor
$progress_sks = min(100, round(($total_sks_lulus / $target_sks) * 100));

// Ambil Status Tagihan Semester Aktif
$ta_aktif = get_tahun_aktif($pdo);
$kode_tahun_aktif = $ta_aktif['kode_tahun'] ?? '';
$is_lunas = false;
if ($kode_tahun_aktif) {
    $t_stmt = $pdo->prepare("SELECT status_lunas FROM tagihan WHERE id_mhs=? AND kode_tahun=? AND jenis='SPP'");
    $t_stmt->execute([$id_mhs, $kode_tahun_aktif]);
    $is_lunas = (strtolower($t_stmt->fetchColumn()) === 'lunas');
}

// Status KRS
$status_krs = 'Belum Isi';
if ($kode_tahun_aktif) {
    $krs_check = $pdo->prepare("SELECT status_krs FROM krs k WHERE k.id_mhs=? AND k.kode_tahun=? LIMIT 1");
    $krs_check->execute([$id_mhs, $kode_tahun_aktif]);
    $krs_row = $krs_check->fetchColumn();
    if ($krs_row) $status_krs = ucfirst($krs_row);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>SIAKAD - Beranda Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f0f2f5; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); }
        .gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; }
        .stat-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .progress { height: 8px; border-radius: 4px; }
        .nav-custom .nav-link { color: #6c757d; font-weight: 500; border-radius: 10px; margin-bottom: 5px; }
        .nav-custom .nav-link.active { background: #0d6efd; color: white; }
        .card-hover:hover { transform: translateY(-3px); transition: all 0.3s ease; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-warning fs-3 me-2"></i>SIAKAD
        </a>
        <div class="ms-auto d-flex align-items-center">
            <div class="text-end me-3 d-none d-md-block">
                <div class="fw-bold mb-0"><?= htmlspecialchars($mhs_info['nama']) ?></div>
                <small class="text-muted"><?= $mhs_info['nim'] ?></small>
            </div>
            <div class="dropdown">
                <img src="<?= $foto_url ?>" class="rounded-circle border border-2 border-primary cursor-pointer" width="40" height="40" data-bs-toggle="dropdown">
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Welcome Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="glass-card gradient-primary p-4 shadow-sm position-relative overflow-hidden">
                        <div class="row align-items-center">
                            <div class="col-md-8 position-relative" style="z-index:2">
                                <h1 class="fw-bold">Selamat Datang, <?= explode(' ', $mhs_info['nama'])[0] ?>! 👋</h1>
                                <p class="mb-0 opacity-75">Ini adalah portal akademik mandiri Anda. Pantau progress Anda semester ini.</p>
                            </div>
                            <div class="col-md-4 text-end d-none d-md-block">
                                <i class="bi bi-speedometer2" style="font-size: 80px; opacity: 0.1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card glass-card border-0 shadow-sm card-hover h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-star-fill fs-4"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success align-self-start">+0.1 vs Lalu</span>
                            </div>
                            <h3 class="fw-bold mb-1"><?= number_format($ipk, 2) ?></h3>
                            <p class="text-muted small mb-0">Indeks Prestasi Kumulatif</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card glass-card border-0 shadow-sm card-hover h-100">
                        <div class="card-body text-decoration-none">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-journal-check fs-4"></i>
                                </div>
                                <span class="badge bg-info bg-opacity-10 text-info align-self-start"><?= $jatah_sks ?> Max SKS</span>
                            </div>
                            <h3 class="fw-bold mb-1"><?= $total_sks_lulus ?></h3>
                            <p class="text-muted small mb-0">Total SKS Lulus</p>
                            <div class="progress mt-3">
                                <div class="progress-bar bg-info" style="width: <?= $progress_sks ?>%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block font-monospace"><?= $progress_sks ?>% dari Target</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="keuangan.php" class="text-decoration-none shadow-none">
                        <div class="card glass-card border-0 shadow-sm card-hover h-100 border-start border-5 border-<?= $is_lunas ? 'success' : 'danger' ?>">
                            <div class="card-body">
                                <div class="stat-icon bg-<?= $is_lunas ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $is_lunas ? 'success' : 'danger' ?> mb-3">
                                    <i class="bi bi-wallet2 fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-1 fs-4"><?= $is_lunas ? 'LUNAS' : 'Tunggakan' ?></h3>
                                <p class="text-muted small mb-0">Status Keuangan (<?= $kode_tahun_aktif ?>)</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="krs_input.php" class="text-decoration-none">
                        <div class="card glass-card border-0 shadow-sm card-hover h-100">
                            <div class="card-body">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-3">
                                    <i class="bi bi-file-earmark-text fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-1 fs-4 text-dark"><?= $status_krs ?></h3>
                                <p class="text-muted small mb-0">Pengisian KRS Semester Ini</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row g-4 mb-5">
                <div class="col-md-8">
                    <div class="card glass-card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between">
                            <h5 class="fw-bold mb-0">Jajak Progress Semester</h5>
                            <a href="khs.php" class="text-primary small text-decoration-none">Lihat Lengkap</a>
                        </div>
                        <div class="card-body p-4">
                            <!-- Placeholder Chart / Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Semester</th>
                                            <th>KRS Ambil</th>
                                            <th>IPS (Indeks Semester)</th>
                                            <th>Predikat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Aktif (<?= $kode_tahun_aktif ?>)</td>
                                            <td><?= $status_krs === 'Setuju' ? 'Terverifikasi' : $status_krs ?></td>
                                            <td>-</td>
                                            <td><span class="badge bg-light text-dark">Ongoing</span></td>
                                        </tr>
                                        <tr class="opacity-50">
                                            <td colspan="4" class="text-center py-4">Data histori semester sebelumnya sedang diolah...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card glass-card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 p-4 pb-0">
                            <h5 class="fw-bold mb-0">Aksi Cepat</h5>
                        </div>
                        <div class="card-body p-4 pt-3">
                            <div class="list-group list-group-flush">
                                <a href="krs_input.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center py-3">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3" style="width:40px; height:40px;">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Isi KRS Online</div>
                                        <small class="text-muted">Pilih mata kuliah semester ini</small>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto"></i>
                                </a>
                                <a href="keuangan.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center py-3">
                                    <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3" style="width:40px; height:40px;">
                                        <i class="bi bi-bank fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Pembayaran Kuliah</div>
                                        <small class="text-muted">Pantau rincian tagihan & virtual account</small>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto"></i>
                                </a>
                                <a href="tugas.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center py-3">
                                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3" style="width:40px; height:40px;">
                                        <i class="bi bi-cloud-upload fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Upload Tugas</div>
                                        <small class="text-muted">Pengumpulan file kuliah & materi</small>
                                    </div>
                                    <i class="bi bi-chevron-right ms-auto"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
