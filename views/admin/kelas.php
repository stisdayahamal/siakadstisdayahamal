<?php
// views/admin/kelas.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah_jenis'])) {
        $nama = trim($_POST['nama_jenis']);
        $pdo->prepare("INSERT INTO jenis_kelas (nama_jenis) VALUES (?)")->execute([$nama]);
        $_SESSION['sukses'] = "Jenis kelas berhasil ditambahkan.";
        header("Location: kelas.php"); exit;
    }
    if (isset($_POST['hapus_jenis'])) {
        $id = $_POST['id_jenis'];
        $pdo->prepare("DELETE FROM jenis_kelas WHERE id_jenis = ?")->execute([$id]);
        $_SESSION['sukses'] = "Jenis kelas dihapus.";
        header("Location: kelas.php"); exit;
    }
    if (isset($_POST['tambah_waktu'])) {
        $ket = trim($_POST['keterangan']);
        $pdo->prepare("INSERT INTO waktu_kuliah (keterangan) VALUES (?)")->execute([$ket]);
        $_SESSION['sukses'] = "Sesi waktu kuliah berhasil ditambahkan.";
        header("Location: kelas.php"); exit;
    }
    if (isset($_POST['hapus_waktu'])) {
        $id = $_POST['id_waktu'];
        $pdo->prepare("DELETE FROM waktu_kuliah WHERE id_waktu = ?")->execute([$id]);
        $_SESSION['sukses'] = "Sesi waktu kuliah dihapus.";
        header("Location: kelas.php"); exit;
    }
}

$jenis_kelas = $pdo->query("SELECT * FROM jenis_kelas ORDER BY id_jenis ASC")->fetchAll();
$waktu_kuliah = $pdo->query("SELECT * FROM waktu_kuliah ORDER BY id_waktu ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Parameter Kelas & Waktu - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Setup Parameter Kelas</h2>
            
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="row g-4">
                <!-- Jenis Kelas -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-tags-fill me-2"></i>Kategori / Program Kelas</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-4">
                                <input type="text" class="form-control" name="nama_jenis" placeholder="Tambah Cth: Kelas Ekstensi" required>
                                <button type="submit" name="tambah_jenis" class="btn btn-primary"><i class="bi bi-plus-lg"></i></button>
                            </form>
                            
                            <ul class="list-group list-group-flush border-top">
                                <?php foreach($jenis_kelas as $j): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                                    <span class="fw-bold text-dark"><i class="bi bi-caret-right text-primary me-2"></i><?= htmlspecialchars($j['nama_jenis']) ?></span>
                                    <form method="post" onsubmit="return confirm('Hapus program jenis kelas ini?');">
                                        <input type="hidden" name="id_jenis" value="<?= $j['id_jenis'] ?>">
                                        <button type="submit" name="hapus_jenis" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if(count($jenis_kelas)===0): ?><li class="list-group-item text-muted text-center py-3">Belum ada setup</li><?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Waktu Kuliah -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0 text-success"><i class="bi bi-clock-history me-2"></i>Sesi Waktu Kuliah</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-4">
                                <input type="text" class="form-control" name="keterangan" placeholder="Tambah Cth: Pagi (08:00 - 12:00)" required>
                                <button type="submit" name="tambah_waktu" class="btn btn-success"><i class="bi bi-plus-lg"></i></button>
                            </form>
                            
                            <ul class="list-group list-group-flush border-top">
                                <?php foreach($waktu_kuliah as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                                    <span class="fw-bold text-dark"><i class="bi bi-caret-right text-success me-2"></i><?= htmlspecialchars($w['keterangan']) ?></span>
                                    <form method="post" onsubmit="return confirm('Hapus sesi waktu ini?');">
                                        <input type="hidden" name="id_waktu" value="<?= $w['id_waktu'] ?>">
                                        <button type="submit" name="hapus_waktu" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                                <?php if(count($waktu_kuliah)===0): ?><li class="list-group-item text-muted text-center py-3">Belum ada setup</li><?php endif; ?>
                            </ul>
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
