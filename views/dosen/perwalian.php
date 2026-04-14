<?php
// views/dosen/perwalian.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'dosen') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// Handle Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mhs'])) {
    $id_mhs = intval($_POST['id_mhs']);
    try {
        $stmt = $pdo->prepare('UPDATE krs SET status_krs = "setuju", status_approve = 1 WHERE id_mhs = ? AND status_krs = "draf"');
        $stmt->execute([$id_mhs]);
        $success = 'KRS Mahasiswa berhasil disetujui.';
    } catch (PDOException $e) {
        $error = 'Gagal menyetujui KRS: ' . $e->getMessage();
    }
}

// Ambil daftar mahasiswa yang punya KRS 'draf'
$sql = "
    SELECT m.id_mhs, m.nim, m.nama, p.nama_prodi, COUNT(k.id_krs) as jumlah_mk
    FROM krs k
    JOIN mahasiswa m ON k.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE k.status_krs = 'draf'
    GROUP BY m.id_mhs
";
$mahasiswa_krs = $pdo->query($sql)->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Perwalian & Persetujuan KRS - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-3">
    <h2>Persetujuan KRS Mahasiswa (Perwalian)</h2>
    <p class="text-muted">Daftar mahasiswa yang sedang mengajukan draf KRS dan menunggu persetujuan dosen wali.</p>
    
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover datatable-perwalian">
                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Jumlah MK (Draf)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mahasiswa_krs as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['nim']) ?></td>
                        <td><?= htmlspecialchars($m['nama']) ?></td>
                        <td><?= htmlspecialchars($m['nama_prodi']) ?></td>
                        <td><?= $m['jumlah_mk'] ?> MK</td>
                        <td>
                            <form method="post" onsubmit="return confirm('Setujui KRS Mahasiswa ini?')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id_mhs" value="<?= $m['id_mhs'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Setujui KRS</button>
                                <a href="detail_krs.php?id_mhs=<?= $m['id_mhs'] ?>" class="btn btn-sm btn-info">Lihat Detail</a>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </main>
  </div>
</div>
<script>
$(document).ready(function() {
    $('.datatable-perwalian').DataTable();
});
</script>
</body>
</html>
