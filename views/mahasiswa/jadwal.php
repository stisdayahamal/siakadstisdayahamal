<?php
// views/mahasiswa/jadwal.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$id_mhs = $user['id_mhs'] ?? $user['id'];

// Ambil semester aktif
$tahun_aktif = $pdo->query("SELECT id_tahun, kode_tahun FROM tahun_akademik WHERE status_aktif=1 ORDER BY id_tahun DESC LIMIT 1")->fetch();
if (!$tahun_aktif) {
    $tahun_aktif = $pdo->query("SELECT id_tahun, kode_tahun FROM tahun_akademik ORDER BY id_tahun DESC LIMIT 1")->fetch();
}
$kode_tahun = $tahun_aktif ? $tahun_aktif['kode_tahun'] : '-';

$stmt = $pdo->prepare('SELECT jk.hari, jk.jam, jk.ruang, mk.nama_mk, mk.sks, d.nama AS nama_dosen, k.status_krs FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal = jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk JOIN dosen d ON jk.id_dosen = d.id_dosen WHERE k.id_mhs = ? ORDER BY jk.hari, jk.jam');
$stmt->execute([$id_mhs]);
$jadwal = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Jadwal Kuliah - SIAKAD Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">
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
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2">Jadwal Perkuliahan</h1>
        <span class="badge bg-info text-dark p-2 fs-6">Semester Aktif: <?= htmlspecialchars($kode_tahun) ?></span>
      </div>

      <div class="card shadow-sm border-0">
          <div class="card-body">
              <table class="table table-hover align-middle">
                  <thead class="table-info text-dark">
                      <tr>
                          <th>Hari</th>
                          <th>Jam</th>
                          <th>Mata Kuliah</th>
                          <th>SKS</th>
                          <th>Dosen Pengampu</th>
                          <th>Ruangan</th>
                          <th>Status KRS</th>
                      </tr>
                  </thead>
                  <tbody>
                  <?php if(count($jadwal) > 0): ?>
                      <?php foreach ($jadwal as $j): ?>
                          <tr>
                              <td><span class="badge bg-secondary"><?= htmlspecialchars($j['hari']) ?></span></td>
                              <td><i class="bi bi-clock"></i> <?= htmlspecialchars($j['jam']) ?></td>
                              <td class="fw-bold"><?= htmlspecialchars($j['nama_mk']) ?></td>
                              <td><?= htmlspecialchars($j['sks']) ?> SKS</td>
                              <td><?= htmlspecialchars($j['nama_dosen']) ?></td>
                              <td><i class="bi bi-geo-alt"></i> Ruang <?= htmlspecialchars($j['ruang']) ?></td>
                              <td>
                                  <?php if($j['status_krs'] == 'setuju'): ?>
                                      <span class="badge bg-success">Disetujui</span>
                                  <?php elseif($j['status_krs'] == 'draf'): ?>
                                      <span class="badge bg-warning text-dark">Draf / Menunggu</span>
                                  <?php else: ?>
                                      <span class="badge bg-danger">Ditolak</span>
                                  <?php endif; ?>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr><td colspan="7" class="text-center text-muted py-4">Kamu belum memiliki jadwal kuliah pada semester ini. Pastikan KRS sudah disetujui.</td></tr>
                  <?php endif; ?>
                  </tbody>
              </table>
          </div>
      </div>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
