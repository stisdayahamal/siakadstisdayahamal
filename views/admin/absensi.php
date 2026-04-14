<?php
// views/admin/absensi.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$user_id = $user['id'];

// Timezone harus di set ke WIB jika belum
date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('Y-m-d');
$waktu_sekarang = date('H:i:s');

// Notifikasi flash
$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

// Cek absensi hari ini untuk user
$stmt = $pdo->prepare("SELECT * FROM absensi_pegawai WHERE user_id = ? AND tanggal = ?");
$stmt->execute([$user_id, $hari_ini]);
$absen_hari_ini = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        if (!$absen_hari_ini) {
            $pdo->prepare("INSERT INTO absensi_pegawai (user_id, tanggal, jam_in, status) VALUES (?, ?, ?, 'Hadir')")
                ->execute([$user_id, $hari_ini, $waktu_sekarang]);
                
            $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
                ->execute([$user_id, 'CREATE', 'Check-In Absensi']);
            
            $_SESSION['sukses'] = "Berhasil Check-In pada pukul $waktu_sekarang";
        } else {
            $_SESSION['error'] = "Anda sudah melakukan Check-In hari ini.";
        }
        header("Location: absensi.php"); exit;
    }
    
    if (isset($_POST['check_out'])) {
        if ($absen_hari_ini && empty($absen_hari_ini['jam_out'])) {
            $pdo->prepare("UPDATE absensi_pegawai SET jam_out = ? WHERE id = ?")
                ->execute([$waktu_sekarang, $absen_hari_ini['id']]);
                
            $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
                ->execute([$user_id, 'UPDATE', 'Check-Out Absensi']);

            $_SESSION['sukses'] = "Berhasil Check-Out pada pukul $waktu_sekarang. Terima kasih telah bekerja hari ini.";
        } else {
            $_SESSION['error'] = "Gagal Check-Out. Pastikan Anda sudah Check-In dan belum Check-Out.";
        }
        header("Location: absensi.php"); exit;
    }
}

// Rekap Semua pegawai untuk admin
$rekap = $pdo->query("
    SELECT a.*, u.nama, u.role, u.username 
    FROM absensi_pegawai a 
    JOIN users u ON a.user_id = u.id_user 
    ORDER BY a.tanggal DESC, a.jam_in DESC 
    LIMIT 100
")->fetchAll();

// Statistik Absen Hari Ini
$stat = [
    'hadir' => $pdo->query("SELECT COUNT(*) FROM absensi_pegawai WHERE tanggal = '$hari_ini'")->fetchColumn(),
    'belum_out' => $pdo->query("SELECT COUNT(*) FROM absensi_pegawai WHERE tanggal = '$hari_ini' AND jam_out IS NULL")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Absensi Harian - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<!-- Top Navbar Minimal -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Absensi Harian Karyawan</h2>

            <?php if($sukses): ?>
                <script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script>
            <?php endif; ?>
            <?php if($error): ?>
                <script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- Panel Absen Pribadi -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-5">
                            <h5 class="text-muted mb-3"><i class="bi bi-calendar-event me-2"></i><?= date('d F Y') ?></h5>
                            <h1 class="display-1 fw-bold text-primary mb-4" id="jamLive"><?= date('H:i:s') ?></h1>
                            
                            <form method="post" class="d-flex justify-content-center gap-3">
                                <?php if(!$absen_hari_ini): ?>
                                    <button type="submit" name="check_in" class="btn btn-success btn-lg px-5 rounded-pill shadow-sm fw-bold">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Check-In
                                    </button>
                                <?php elseif(empty($absen_hari_ini['jam_out'])): ?>
                                    <!-- Sudah checkin, tampilkan jam in -->
                                    <div class="text-start p-3 bg-light rounded-4 me-3">
                                        <small class="text-muted d-block">Jam Masuk</small>
                                        <strong class="fs-4 text-success"><?= substr($absen_hari_ini['jam_in'], 0, 5) ?></strong>
                                    </div>
                                    <button type="submit" name="check_out" class="btn btn-danger btn-lg px-5 rounded-pill shadow-sm fw-bold" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan waktu kerja hari ini?');">
                                        <i class="bi bi-box-arrow-left me-2"></i>Check-Out
                                    </button>
                                <?php else: ?>
                                    <div class="p-4 bg-success bg-opacity-10 text-success rounded-4 border border-success border-opacity-25 w-100">
                                        <i class="bi bi-check-circle-fill fs-2 mb-2"></i>
                                        <h5 class="fw-bold mb-0">Tugas Hari Ini Selesai</h5>
                                        <p class="small mb-0 mt-1">Check-in: <?= substr($absen_hari_ini['jam_in'],0,5) ?> &nbsp;|&nbsp; Check-out: <?= substr($absen_hari_ini['jam_out'],0,5) ?></p>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Rekap Data Cepat -->
                <div class="col-md-7">
                    <div class="row g-3 h-100">
                        <div class="col-6">
                            <div class="card bg-primary text-white border-0 shadow-sm rounded-4 h-100 p-3">
                                <h6 class="opacity-75"><i class="bi bi-people-fill me-2"></i>Pegawai Check-In Hari Ini</h6>
                                <h1 class="display-4 fw-bold mt-auto"><?= $stat['hadir'] ?></h1>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-warning text-dark border-0 shadow-sm rounded-4 h-100 p-3">
                                <h6 class="opacity-75"><i class="bi bi-hourglass-split me-2"></i>Masih di Kantor</h6>
                                <h1 class="display-4 fw-bold mt-auto"><?= $stat['belum_out'] ?></h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Log Absensi Keseluruhan -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-columns-reverse text-primary me-2"></i>Log Absensi Pegawai Terakhir</h5>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Ekspor Rekap</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Tanggal</th>
                                    <th>Nama Pegawai</th>
                                    <th>Role</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($rekap) > 0): ?>
                                    <?php foreach($rekap as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($r['nama']) ?> <br><small class="fw-normal text-muted">@<?= htmlspecialchars($r['username']) ?></small></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['role']) ?></span></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success fs-6"><i class="bi bi-arrow-down-right me-1"></i><?= $r['jam_in'] ? substr($r['jam_in'],0,5) : '-' ?></span></td>
                                        <td>
                                            <?php if($r['jam_out']): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger fs-6"><i class="bi bi-arrow-up-right me-1"></i><?= substr($r['jam_out'],0,5) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark"><i class="bi bi-clock me-1"></i>Belum Pulang</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-primary rounded-pill"><?= htmlspecialchars($r['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data absensi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
    // Live Clock Update
    setInterval(function() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jamLive').innerText = h + ":" + m + ":" + s;
    }, 1000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
