<?php
// views/admin/kurikulum.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// Handle Create/Update Kurikulum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_kurikulum'])) {
    $nama_kurikulum = trim($_POST['nama_kurikulum'] ?? '');
    $tahun_mulai = trim($_POST['tahun_mulai'] ?? '');
    $id_prodi = intval($_POST['id_prodi'] ?? 0);

    if ($_POST['action_kurikulum'] === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO kurikulum (nama_kurikulum, tahun_mulai, id_prodi) VALUES (?, ?, ?)');
            $stmt->execute([$nama_kurikulum, $tahun_mulai, $id_prodi]);
            $success = 'Kurikulum berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambah kurikulum: ' . $e->getMessage();
        }
    }
}

// Handle Create/Update Mata Kuliah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_mk'])) {
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    $nama_mk = trim($_POST['nama_mk'] ?? '');
    $sks = intval($_POST['sks'] ?? 0);
    $semester = intval($_POST['semester'] ?? 0);
    $id_prasyarat = !empty($_POST['id_prasyarat']) ? intval($_POST['id_prasyarat']) : null;
    $id_kurikulum = intval($_POST['id_kurikulum'] ?? 0);

    if ($_POST['action_mk'] === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester, id_prasyarat, id_kurikulum) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$kode_mk, $nama_mk, $sks, $semester, $id_prasyarat, $id_kurikulum]);
            $success = 'Mata kuliah berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambah mata kuliah: ' . $e->getMessage();
        }
    } elseif ($_POST['action_mk'] === 'update') {
        $id_mk = intval($_POST['id_mk']);
        try {
            $stmt = $pdo->prepare('UPDATE mata_kuliah SET kode_mk=?, nama_mk=?, sks=?, semester=?, id_prasyarat=?, id_kurikulum=? WHERE id_mk=?');
            $stmt->execute([$kode_mk, $nama_mk, $sks, $semester, $id_prasyarat, $id_kurikulum, $id_mk]);
            $success = 'Mata kuliah berhasil diperbarui.';
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui mata kuliah: ' . $e->getMessage();
        }
    }
}

// Data List
$kurikulumList = $pdo->query('SELECT k.*, p.nama_prodi FROM kurikulum k JOIN prodi p ON k.id_prodi = p.id_prodi ORDER BY k.tahun_mulai DESC')->fetchAll();
$mkList = $pdo->query('SELECT mk.*, pr.nama_mk AS nama_prasyarat, k.nama_kurikulum FROM mata_kuliah mk LEFT JOIN mata_kuliah pr ON mk.id_prasyarat = pr.id_mk JOIN kurikulum k ON mk.id_kurikulum = k.id_kurikulum ORDER BY mk.semester ASC')->fetchAll();
$prodi = $pdo->query('SELECT * FROM prodi')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Kelola Kurikulum & Mata Kuliah - SIAKAD</title>
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
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- KURIKULUM -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Data Kurikulum</h2>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#kurikulumModal">Tambah Kurikulum</button>
    </div>
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <table class="table table-hover">
                <thead><tr><th>Nama Kurikulum</th><th>Tahun Mulai</th><th>Prodi</th></tr></thead>
                <tbody>
                    <?php foreach ($kurikulumList as $k): ?>
                    <tr>
                        <td><?= htmlspecialchars($k['nama_kurikulum']) ?></td>
                        <td><?= htmlspecialchars($k['tahun_mulai']) ?></td>
                        <td><?= htmlspecialchars($k['nama_prodi']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MATA KULIAH -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Data Mata Kuliah</h2>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#mkModal" onclick="resetMkForm()">Tambah Mata Kuliah</button>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>SMT</th>
                        <th>Prasyarat</th>
                        <th>Kurikulum</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mkList as $mk): ?>
                    <tr>
                        <td><?= htmlspecialchars($mk['kode_mk']) ?></td>
                        <td><?= htmlspecialchars($mk['nama_mk']) ?></td>
                        <td><?= htmlspecialchars($mk['sks']) ?></td>
                        <td><?= htmlspecialchars($mk['semester']) ?></td>
                        <td><?= $mk['nama_prasyarat'] ? htmlspecialchars($mk['nama_prasyarat']) : '-' ?></td>
                        <td><?= htmlspecialchars($mk['nama_kurikulum']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editMk(<?= json_encode($mk) ?>)'>Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Kurikulum -->
<div class="modal fade" id="kurikulumModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_input() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kurikulum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action_kurikulum" value="create">
                <div class="mb-3"><label>Nama Kurikulum</label><input type="text" name="nama_kurikulum" class="form-control" required></div>
                <div class="mb-3"><label>Tahun Mulai (YYYY)</label><input type="number" name="tahun_mulai" class="form-control" value="<?= date('Y') ?>" required></div>
                <div class="mb-3"><label>Prodi</label>
                    <select name="id_prodi" class="form-select" required>
                        <?php foreach ($prodi as $p): ?><option value="<?= $p['id_prodi'] ?>"><?= htmlspecialchars($p['nama_prodi']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<!-- Modal MK -->
<div class="modal fade" id="mkModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_input() ?>
            <div class="modal-header">
                <h5 class="modal-title" id="mkModalTitle">Tambah Mata Kuliah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action_mk" id="formActionMk" value="create">
                <input type="hidden" name="id_mk" id="formIdMk">
                <div class="mb-3"><label>Kode MK</label><input type="text" name="kode_mk" id="formKodeMk" class="form-control" required></div>
                <div class="mb-3"><label>Nama MK</label><input type="text" name="nama_mk" id="formNamaMk" class="form-control" required></div>
                <div class="mb-3"><label>SKS</label><input type="number" name="sks" id="formSks" class="form-control" required></div>
                <div class="mb-3"><label>Semester</label><input type="number" name="semester" id="formSemester" class="form-control" required></div>
                
                <div class="mb-3"><label>Mata Kuliah Prasyarat</label>
                    <select name="id_prasyarat" id="formPrasyarat" class="form-select">
                        <option value="">-- Tidak ada --</option>
                        <?php foreach ($mkList as $mk): ?><option value="<?= $mk['id_mk'] ?>"><?= htmlspecialchars($mk['kode_mk'] . ' - ' . $mk['nama_mk']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3"><label>Dalam Kurikulum</label>
                    <select name="id_kurikulum" id="formIdKurikulum" class="form-select" required>
                        <?php foreach ($kurikulumList as $k): ?><option value="<?= $k['id_kurikulum'] ?>"><?= htmlspecialchars($k['nama_kurikulum']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </main>
  </div>
</div>
<script>
function resetMkForm() {
    document.getElementById('mkModalTitle').innerText = 'Tambah Mata Kuliah';
    document.getElementById('formActionMk').value = 'create';
    document.getElementById('formIdMk').value = '';
    document.getElementById('formKodeMk').value = '';
    document.getElementById('formNamaMk').value = '';
    document.getElementById('formSks').value = '';
    document.getElementById('formSemester').value = '';
    document.getElementById('formPrasyarat').value = '';
}
function editMk(mk) {
    document.getElementById('mkModalTitle').innerText = 'Edit Mata Kuliah';
    document.getElementById('formActionMk').value = 'update';
    document.getElementById('formIdMk').value = mk.id_mk;
    document.getElementById('formKodeMk').value = mk.kode_mk;
    document.getElementById('formNamaMk').value = mk.nama_mk;
    document.getElementById('formSks').value = mk.sks;
    document.getElementById('formSemester').value = mk.semester;
    document.getElementById('formPrasyarat').value = mk.id_prasyarat || '';
    document.getElementById('formIdKurikulum').value = mk.id_kurikulum;
    new bootstrap.Modal(document.getElementById('mkModal')).show();
}
</script>
</body>
</html>
