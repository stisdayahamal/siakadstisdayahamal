<?php
// views/admin/fakultas.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tambah'])) {
        $nama = trim($_POST['nama_fakultas']);
        try {
            $pdo->prepare("INSERT INTO fakultas (nama_fakultas) VALUES (?)")->execute([$nama]);
            $_SESSION['sukses'] = "Fakultas $nama berhasil ditambahkan.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal. Pastikan nama tidak duplikat.";
        }
        header("Location: fakultas.php"); exit;
    }
    
    if (isset($_POST['edit'])) {
        $id = $_POST['id_fakultas'];
        $nama = trim($_POST['nama_fakultas']);
        $pdo->prepare("UPDATE fakultas SET nama_fakultas = ? WHERE id_fakultas = ?")->execute([$nama, $id]);
        $_SESSION['sukses'] = "Fakultas berhasil diperbarui.";
        header("Location: fakultas.php"); exit;
    }
    
    if (isset($_POST['hapus'])) {
        $id = $_POST['id_fakultas'];
        try {
            $pdo->prepare("DELETE FROM fakultas WHERE id_fakultas = ?")->execute([$id]);
            $_SESSION['sukses'] = "Fakultas dihapus.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal menghapus! Fakultas ini mungkin sudah dipakai di Program Studi.";
        }
        header("Location: fakultas.php"); exit;
    }
}

// Ambil data
$fakultas = $pdo->query("SELECT f.*, (SELECT COUNT(*) FROM prodi p WHERE p.id_fakultas = f.id_fakultas) as jumlah_prodi FROM fakultas f ORDER BY f.nama_fakultas ASC")->fetchAll();
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Fakultas - ERP Admin</title>
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h2 class="fw-bold">Manajemen Fakultas</h2>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg me-2"></i>Tambah Fakultas</button>
            </div>
            
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Fakultas</th>
                                    <th>Jumlah Prodi Beraliansi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($fakultas as $index => $f): ?>
                                <tr>
                                    <td class="ps-4"><?= $index + 1 ?></td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($f['nama_fakultas']) ?></td>
                                    <td><span class="badge bg-secondary rounded-pill px-3"><?= $f['jumlah_prodi'] ?> Prodi Terdaftar</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning text-dark me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $f['id_fakultas'] ?>"><i class="bi bi-pencil"></i></button>
                                        <form method="post" class="d-inline-flex" onsubmit="return confirm('Hapus fakultas secara permanen?');">
                                            <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                                            <button type="submit" name="hapus" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit<?= $f['id_fakultas'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-light border-0">
                                                <h5 class="modal-title fw-bold">Edit Fakultas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body p-4">
                                                    <input type="hidden" name="id_fakultas" value="<?= $f['id_fakultas'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Nama Fakultas</label>
                                                        <input type="text" class="form-control" name="nama_fakultas" value="<?= htmlspecialchars($f['nama_fakultas']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(count($fakultas) === 0): ?><tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data fakultas.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah -->
            <div class="modal fade" id="modalTambah" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title fw-bold">Tambah Fakultas Baru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Fakultas</label>
                                    <input type="text" class="form-control" name="nama_fakultas" required placeholder="Contoh: Fakultas Tarbiyah">
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="tambah" class="btn btn-primary fw-bold">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
