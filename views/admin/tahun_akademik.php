<?php
// views/admin/tahun_akademik.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ta'])) {
    $kode_tahun = trim($_POST['kode_tahun'] ?? '');
    $nama_tahun = trim($_POST['nama_tahun'] ?? '');

    if ($_POST['action_ta'] === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO tahun_akademik (kode_tahun, nama_tahun, status_aktif) VALUES (?, ?, 0)');
            $stmt->execute([$kode_tahun, $nama_tahun]);
            $success = 'Tahun akademik berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambah tahun akademik: ' . $e->getMessage();
        }
    }
}

// Handle Set Aktif
if (isset($_GET['set_aktif'])) {
    $id_tahun = intval($_GET['set_aktif']);
    try {
        $pdo->beginTransaction();
        $pdo->exec('UPDATE tahun_akademik SET status_aktif = 0');
        $stmt = $pdo->prepare('UPDATE tahun_akademik SET status_aktif = 1 WHERE id_tahun = ?');
        $stmt->execute([$id_tahun]);
        $pdo->commit();
        $success = 'Semester aktif berhasil diubah.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Gagal mengaktifkan: ' . $e->getMessage();
    }
}

$tahunList = $pdo->query('SELECT * FROM tahun_akademik ORDER BY kode_tahun DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Tahun Akademik - SIAKAD</title>
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
            <i class="bi bi-shield-fill-check text-warning me-2"></i>SIAKAD Admin
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Data Tahun Akademik</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taModal">Tambah Tahun Akademik</button>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Kode Tahun</th>
                        <th>Keterangan Semester</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tahunList as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['kode_tahun']) ?></td>
                        <td><?= htmlspecialchars($t['nama_tahun']) ?></td>
                        <td>
                            <?php if (!empty($t['status_aktif'])): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tidak Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (empty($t['status_aktif'])): ?>
                                <a href="?set_aktif=<?= $t['id_tahun'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Aktifkan semester ini?')">Set Aktif</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="taModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_input() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Tahun Akademik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action_ta" value="create">
                <div class="mb-3">
                    <label>Kode Tahun (Misal: 20251 untuk Ganjil 2025)</label>
                    <input type="text" name="kode_tahun" class="form-control" placeholder="20251" required>
                </div>
                <div class="mb-3">
                    <label>Nama Tahun / Semester (Misal: T.A 2025/2026 Ganjil)</label>
                    <input type="text" name="nama_tahun" class="form-control" placeholder="T.A 2025/2026 Ganjil" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>


    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
