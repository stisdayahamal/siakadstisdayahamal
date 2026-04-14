<?php
// views/dosen/jadwal_mengajar.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$id_dosen = $user['id_dosen'] ?? $user['id'];

// Ambil semester aktif
$kode_tahun = $pdo->query("SELECT kode_tahun FROM tahun_akademik WHERE status_aktif=1 ORDER BY id_tahun DESC LIMIT 1")->fetchColumn();
if (!$kode_tahun) {
    $kode_tahun = $pdo->query("SELECT kode_tahun FROM tahun_akademik ORDER BY id_tahun DESC LIMIT 1")->fetchColumn();
}

$stmt = $pdo->prepare('SELECT jk.*, mk.nama_mk, mk.sks, mk.semester FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_dosen = ? ORDER BY jk.hari, jk.jam');
$stmt->execute([$id_dosen]);
$kelas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Jadwal Mengajar - SIAKAD Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($user['username'] ?? $user['nama']) ?> |
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2">Seluruh Jadwal Mengajar</h1>
        <span class="badge bg-success p-2 fs-6">Semester Aktif: <?= htmlspecialchars($kode_tahun ?: '-') ?></span>
      </div>

      <?php if (isset($_GET['err'])): ?>
      <div class="alert alert-warning alert-dismissible fade show">
          <i class="bi bi-exclamation-triangle me-2"></i>Pilih kelas terlebih dahulu dari daftar di bawah.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
          <div class="card-body p-0">
              <table class="table table-hover align-middle mb-0">
                  <thead class="table-success text-dark">
                      <tr>
                          <th class="ps-3">Mata Kuliah</th>
                          <th>SKS</th>
                          <th>Semester</th>
                          <th>Hari</th>
                          <th>Jam</th>
                          <th>Ruangan</th>
                          <th>Aksi</th>
                      </tr>
                  </thead>
                  <tbody>
                  <?php if (count($kelas) > 0): ?>
                      <?php foreach ($kelas as $k): ?>
                          <tr>
                              <td class="fw-bold ps-3"><?= htmlspecialchars($k['nama_mk']) ?></td>
                              <td><?= htmlspecialchars($k['sks']) ?> SKS</td>
                              <td>Semester <?= htmlspecialchars($k['semester']) ?></td>
                              <td><span class="badge bg-secondary"><?= htmlspecialchars($k['hari']) ?></span></td>
                              <td><i class="bi bi-clock text-muted me-1"></i><?= htmlspecialchars($k['jam']) ?></td>
                              <td><i class="bi bi-geo-alt text-muted me-1"></i>Ruang <?= htmlspecialchars($k['ruang']) ?></td>
                              <td>
                                  <a href="presensi.php?id_jadwal=<?= $k['id_jadwal'] ?>" class="btn btn-outline-success btn-sm me-1" title="Isi Presensi"><i class="bi bi-journal-check"></i> Absen</a>
                                  <a href="input_nilai.php?id_jadwal=<?= $k['id_jadwal'] ?>" class="btn btn-success btn-sm" title="Input Nilai"><i class="bi bi-pencil-square"></i> Nilai</a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr><td colspan="7" class="text-center text-muted py-5">
                          <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                          Belum ada jadwal mengajar pada semester ini.
                      </td></tr>
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
