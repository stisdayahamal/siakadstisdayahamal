<?php
// views/admin/ijazah.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
// Cukup gunakan role_check dari auth middleware atau validasi session
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak.');
}
$success = $error = '';

if (isset($_POST['generate'])) {
    $id_mhs = intval($_POST['id_mhs']);
    $tahun = date('Y');
    
    // Pakai prepare untuk menghindari SQLI
    $stmt = $pdo->prepare('SELECT nim FROM mahasiswa WHERE id_mhs = ?');
    $stmt->execute([$id_mhs]);
    $nim = $stmt->fetchColumn();
    
    if ($nim) {
        $no_ijazah = $tahun . $nim . rand(1000,9999);
        $stmt_up = $pdo->prepare('UPDATE mahasiswa SET no_ijazah=? WHERE id_mhs=?');
        $stmt_up->execute([$no_ijazah, $id_mhs]);
        
        // Catat log
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas, nilai_baru) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user']['id'], 'GENERATE', 'Nomor Ijazah Mahasiswa', $no_ijazah]);
            
        $success = 'Nomor ijazah berhasil digenerate.';
    } else {
        $error = 'Mahasiswa tidak ditemukan.';
    }
}

// Menampilkan data mahasiswa. (Untuk simplifikasi, tampilkan semua mahasiswa yang aktif)
$mhs = $pdo->query("SELECT id_mhs, nim, nama, no_ijazah FROM mahasiswa WHERE status_kuliah IN ('Aktif', 'Lulus') ORDER BY nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Ijazah - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f8f9fa;}</style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Manajemen Nomor Ijazah</h2>
            
            <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i><?= $error ?></div><?php endif; ?>
            
            <div class="card border-0 shadow-sm mt-3 rounded-4">
                <div class="card-body p-4">
                    <p class="text-muted">Generate nomor ijazah untuk mahasiswa aktif atau yang diklasifikasi lulus. Sistem akan mencatat ke histori aktivitas.</p>
                    <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>NIM</th>
                                <th>Nama Lengkap Mahasiswa</th>
                                <th>Seri Nomor Ijazah</th>
                                <th>Tindakan Operasional</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(count($mhs) > 0): ?>
                            <?php foreach ($mhs as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nim']) ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <?php if($row['no_ijazah']): ?>
                                        <span class="badge bg-success py-2 px-3"><?= htmlspecialchars($row['no_ijazah']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary py-2 px-3">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Generate Nomor Seri Ijazah baru untuk <?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>?')">
                                        <input type="hidden" name="id_mhs" value="<?= $row['id_mhs'] ?>">
                                        <button type="submit" name="generate" class="btn btn-sm <?= $row['no_ijazah'] ? 'btn-outline-danger' : 'btn-primary' ?> fw-bold">
                                            <?= $row['no_ijazah'] ? '<i class="bi bi-arrow-clockwise"></i> Generate Ulang' : '<i class="bi bi-magic"></i> Generate Ijazah' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-muted py-4">Belum ada data mahasiswa untuk dikelola.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
