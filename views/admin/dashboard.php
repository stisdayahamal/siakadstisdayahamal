<?php
// views/admin/dashboard.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];

// Real-time metrics
$stats = [
    'mhs_aktif' => $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status_kuliah = 'Aktif'")->fetchColumn(),
    'dosen' => $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn(),
    'ticket_open' => $pdo->query("SELECT COUNT(*) FROM support_ticket WHERE status = 'Open'")->fetchColumn(),
    'pmb_baru' => $pdo->query("SELECT COUNT(*) FROM calon_mhs")->fetchColumn()
];

// Recent Logs
$logs = $pdo->query("SELECT * FROM sistem_log_aktivitas ORDER BY id_log DESC LIMIT 6")->fetchAll();

// Unread Notifications
$stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
$stmt_notif->execute([$user['id']]);
$notif_count = $stmt_notif->fetchColumn();

// Data for Chart: Mahasiswa per Prodi
$prodi_stats = $pdo->query("SELECT p.nama_prodi, COUNT(m.id_mhs) as total FROM prodi p LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi GROUP BY p.id_prodi")->fetchAll();
$prodi_labels = array_column($prodi_stats, 'nama_prodi');
$prodi_data = array_column($prodi_stats, 'total');

// Active Semester
$ta_aktif = get_tahun_aktif($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>SIAKAD ERP - Dashboard Eksekutif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .icon-box { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .bg-light-primary { background: #e0ecff; color: #0d6efd;}
        .bg-light-success { background: #d1e7dd; color: #198754;}
        .bg-light-warning { background: #fff3cd; color: #ffc107;}
        .bg-light-danger { background: #f8d7da; color: #dc3545;}
        .card-hover:hover { transform: translateY(-5px); transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    </style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="#"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus'] ?? 'SIAKAD ERP') ?></a>
    <div class="ms-auto d-flex align-items-center">
        <!-- Notification Bell -->
        <div class="dropdown me-3">
            <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <?php if($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $notif_count ?>
                </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="width: 300px;">
                <li><h6 class="dropdown-header">Notifikasi Terbaru</h6></li>
                <li><a class="dropdown-item py-2" href="#">Tidak ada notifikasi baru</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center text-primary" href="#">Lihat Semua</a></li>
            </ul>
        </div>
        <!-- User Profile -->
        <div class="dropdown">
            <button class="btn btn-white dropdown-toggle fw-bold d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username'] ?? 'A') ?>&background=random" class="rounded-circle me-2" width="32">
                <?= htmlspecialchars($user['username'] ?? $user['nama']) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="log_aktivitas.php"><i class="bi bi-activity me-2"></i>Aktivitas Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Dashboard Eksekutif</h2>
                    <p class="text-secondary mb-0">Tahun Akademik: <span class="badge bg-primary"><?= htmlspecialchars($ta_aktif['nama_tahun'] ?? 'Belum Set') ?></span></p>
                </div>
                <button class="btn btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-2"></i>Cetak Laporan</button>
            </div>

            <!-- 4 Metric Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <a href="mahasiswa.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2 card-hover">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-light-primary me-3"><i class="bi bi-mortarboard fs-3"></i></div>
                                <div>
                                    <h6 class="text-muted mb-1">Mahasiswa Aktif</h6>
                                    <h3 class="fw-bold mb-0 text-dark"><?= number_format($stats['mhs_aktif']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="dosen.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2 card-hover">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-light-success me-3"><i class="bi bi-person-video3 fs-3"></i></div>
                                <div>
                                    <h6 class="text-muted mb-1">Total Dosen</h6>
                                    <h3 class="fw-bold mb-0 text-dark"><?= number_format($stats['dosen']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="pmb_calon.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2 card-hover">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-light-warning me-3"><i class="bi bi-envelope-open fs-3"></i></div>
                                <div>
                                    <h6 class="text-muted mb-1">Pendaftar PMB</h6>
                                    <h3 class="fw-bold mb-0 text-dark"><?= number_format($stats['pmb_baru']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="support.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2 card-hover">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-light-danger me-3"><i class="bi bi-headset fs-3"></i></div>
                                <div>
                                    <h6 class="text-muted mb-1">Tiket Terbuka</h6>
                                    <h3 class="fw-bold mb-0 text-dark"><?= number_format($stats['ticket_open']) ?></h3>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Sebaran Mahasiswa Chart -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-bar-chart-line text-primary me-2"></i>Sebaran Mahasiswa per Program Studi</h5>
                            <div style="height: 350px;">
                                <canvas id="prodiChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mini Tasks / Keuangan -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100 p-4" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-check me-2"></i>Status Sistem ERP</h5>
                        <p class="mb-1">Modul Aktif: <strong>14/14</strong></p>
                        <p class="mb-4">Database: <strong>Stable / DIKTI Confirmed</strong></p>
                        <hr class="border-white opacity-50">
                        <h6>Jalan Pintas:</h6>
                        <div class="d-grid gap-2">
                            <a href="mahasiswa_tambah.php" class="btn btn-light text-primary fw-bold text-start"><i class="bi bi-person-plus me-2"></i> Tambah Mahasiswa Manual</a>
                            <a href="feeder_export.php" class="btn btn-outline-light text-start"><i class="bi bi-cloud-download me-2"></i> Export Neo Feeder</a>
                            <a href="pmb_setup.php" class="btn btn-outline-light text-start"><i class="bi bi-door-open me-2"></i> Pengaturan Gelombang</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Activity Logs -->
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between">
                            <h5 class="fw-bold mb-0"><i class="bi bi-activity text-primary me-2"></i>Aktivitas Sistem Terbaru</h5>
                            <a href="log_aktivitas.php" class="text-decoration-none small">Lihat Seluruh Log</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($logs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Aksi</th>
                                            <th>Entitas</th>
                                            <th>Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($logs as $log): ?>
                                        <tr>
                                            <td class="small text-muted"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                                            <td><span class="badge bg-<?= $log['aksi'] == 'DELETE' ? 'danger' : ($log['aksi'] == 'CREATE' ? 'success' : 'info') ?>"><?= $log['aksi'] ?></span></td>
                                            <td class="fw-medium"><?= htmlspecialchars($log['entitas']) ?></td>
                                            <td class="small"><?= htmlspecialchars(substr($log['nilai_baru'] ?? 'Tidak ada detail', 0, 100)) ?>...</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Belum ada aktivitas tercatat.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('prodiChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($prodi_labels) ?>,
            datasets: [{
                label: 'Jumlah Mahasiswa',
                data: <?= json_encode($prodi_data) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
</body>
</html>
