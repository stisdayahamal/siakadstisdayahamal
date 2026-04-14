<?php
// views/dosen/detail_krs.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

if ($_SESSION['user']['role'] !== 'dosen') {
    header('Location: dashboard.php');
    exit;
}

$id_mhs = isset($_GET['id_mhs']) ? intval($_GET['id_mhs']) : 0;
if (!$id_mhs) {
    header('Location: perwalian.php');
    exit;
}

// Ambil data mahasiswa
$stmt = $pdo->prepare('SELECT nim, nama FROM mahasiswa WHERE id_mhs = ?');
$stmt->execute([$id_mhs]);
$mhs = $stmt->fetch();

if (!$mhs) {
    die('Mahasiswa tidak ditemukan.');
}

// Ambil detail KRS draf
$sql = "
    SELECT mk.kode_mk, mk.nama_mk, mk.sks, mk.hari, mk.jam, d.nama_dosen
    FROM krs k
    JOIN jadwal_kuliah jk ON k.id_jadwal = jk.id_jadwal
    JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
    JOIN dosen d ON jk.id_dosen = d.id_dosen
    WHERE k.id_mhs = ? AND k.status_krs = 'draf'
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_mhs]);
$krs_items = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Detail KRS Mahasiswa - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
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
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Detail Rencana Studi (KRS)</h2>
        <a href="perwalian.php" class="btn btn-secondary btn-sm">Kembali</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informasi Mahasiswa</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><b>Nama:</b> <?= htmlspecialchars($mhs['nama']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><b>NIM:</b> <?= htmlspecialchars($mhs['nim']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Jadwal</th>
                        <th>Dosen Pengampu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($krs_items)): ?>
                        <tr><td colspan="5" class="text-center">Tidak ada mata kuliah dalam draf.</td></tr>
                    <?php else: ?>
                        <?php $total_sks = 0; foreach ($krs_items as $k): $total_sks += $k['sks']; ?>
                        <tr>
                            <td><?= htmlspecialchars($k['kode_mk']) ?></td>
                            <td><?= htmlspecialchars($k['nama_mk']) ?></td>
                            <td><?= $k['sks'] ?></td>
                            <td><?= htmlspecialchars($k['hari']) ?>, <?= htmlspecialchars($k['jam']) ?></td>
                            <td><?= htmlspecialchars($k['nama_dosen']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold table-info">
                            <td colspan="2" class="text-end">Total SKS yang diajukan:</td>
                            <td><?= $total_sks ?></td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    </main>
  </div>
</div>
</body>
</html>
