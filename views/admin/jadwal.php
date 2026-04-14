<?php
// views/admin/jadwal.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

// Pastikan hanya admin
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
// Ambil tahun akademik aktif untuk tagging jadwal baru
$th_aktif = get_tahun_aktif($pdo);
$active_year = $th_aktif['kode_tahun'] ?? '20251';

csrf_validate();

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_mk    = intval($_POST['id_mk']);
    $id_dosen = intval($_POST['id_dosen']);
    $hari     = trim($_POST['hari']);
    $jam      = trim($_POST['jam']);
    $ruang    = trim($_POST['ruang']);
    $kuota    = intval($_POST['kuota']);

    if ($_POST['action'] === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO jadwal_kuliah (id_mk, id_dosen, hari, jam, ruang, kuota, kode_tahun) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$id_mk, $id_dosen, $hari, $jam, $ruang, $kuota, $active_year]);
            $success = 'Jadwal berhasil ditambahkan.';
        } catch (PDOException $e) {
            $error = 'Gagal menambah jadwal: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update') {
        $id_jadwal = intval($_POST['id_jadwal']);
        try {
            $stmt = $pdo->prepare('UPDATE jadwal_kuliah SET id_mk=?, id_dosen=?, hari=?, jam=?, ruang=?, kuota=? WHERE id_jadwal=?');
            $stmt->execute([$id_mk, $id_dosen, $hari, $jam, $ruang, $kuota, $id_jadwal]);
            $success = 'Jadwal berhasil diperbarui.';
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui jadwal: ' . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id_jadwal = intval($_GET['delete']);
    try {
        $pdo->prepare('DELETE FROM jadwal_kuliah WHERE id_jadwal=?')->execute([$id_jadwal]);
        $success = 'Jadwal berhasil dihapus.';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus jadwal (mungkin sudah ada KRS yang terkait).';
    }
}

// Data Lists (Filter per tahun aktif bisa ditambahkan jika perlu, namun untuk sementara tampilkan semua)
$jadwal = $pdo->query('
    SELECT jk.*, mk.kode_mk, mk.nama_mk, d.nama as nama_dosen 
    FROM jadwal_kuliah jk 
    JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk 
    JOIN dosen d ON jk.id_dosen = d.id_dosen
    ORDER BY mk.semester, jk.hari
')->fetchAll();

$mata_kuliah = $pdo->query('SELECT id_mk, kode_mk, nama_mk FROM mata_kuliah')->fetchAll();
$dosen = $pdo->query('SELECT id_dosen, nidn, nama FROM dosen')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Kelola Jadwal - SIAKAD</title>
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
        <h2>Kelola Jadwal Kuliah</h2>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-calendar-event me-1"></i> Terpilih: Semester <?= $active_year ?>
            </span>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#jadwalModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
            </button>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable-jadwal mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Kode/Mata Kuliah</th>
                            <th>Dosen Pengampu</th>
                            <th>Waktu & Ruang</th>
                            <th>Kuota</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jadwal as $j): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($j['nama_mk']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($j['kode_mk']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($j['nama_dosen']) ?></td>
                            <td>
                                <span class="d-block"><i class="bi bi-clock me-1 text-muted"></i> <?= htmlspecialchars($j['hari'] . ', ' . $j['jam']) ?></span>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i> R.<?= htmlspecialchars($j['ruang']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= $j['kuota'] ?> Mhs</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick='editJadwal(<?= json_encode($j) ?>)'>Edit</button>
                                <a href="?delete=<?= $j['id_jadwal'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-1" onclick="return confirm('Hapus jadwal ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Jadwal -->
<div class="modal fade" id="jadwalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_input() ?>
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_jadwal" id="formIdJadwal">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Mata Kuliah</label>
                    <select name="id_mk" id="formIdMk" class="form-select bg-light border-0" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php foreach ($mata_kuliah as $mk): ?>
                        <option value="<?= $mk['id_mk'] ?>"><?= $mk['kode_mk'] ?> - <?= $mk['nama_mk'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Dosen Pengampu</label>
                    <select name="id_dosen" id="formIdDosen" class="form-select bg-light border-0" required>
                        <option value="">-- Pilih Dosen --</option>
                        <?php foreach ($dosen as $d): ?>
                        <option value="<?= $d['id_dosen'] ?>"><?= $d['nidn'] ?> - <?= $d['nama'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Hari</label>
                        <select name="hari" id="formHari" class="form-select bg-light border-0" required>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Jam (08:00-10:00)</label>
                        <input type="text" name="jam" id="formJam" class="form-control bg-light border-0" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Ruang</label>
                        <input type="text" name="ruang" id="formRuang" class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted">Kuota Mahasiswa</label>
                        <input type="number" name="kuota" id="formKuota" class="form-control bg-light border-0" min="1" value="40" required>
                    </div>
                </div>
                
                <div class="alert alert-info py-2 small border-0 mb-0">
                    <i class="bi bi-info-circle me-1"></i> Jadwal akan otomatis terdaftar untuk semester <strong><?= $active_year ?></strong>.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 p-4">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </main>
  </div>
</div>
<script>
$(document).ready(function() {
    $('.datatable-jadwal').DataTable({
        pageLength: 25,
        language: {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ jadwal"
        }
    });
});

function resetForm() {
    document.getElementById('modalTitle').innerText = 'Tambah Jadwal';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formIdJadwal').value = '';
    document.getElementById('formIdMk').value = '';
    document.getElementById('formIdDosen').value = '';
    document.getElementById('formHari').value = 'Senin';
    document.getElementById('formJam').value = '';
    document.getElementById('formRuang').value = '';
    document.getElementById('formKuota').value = '40';
}

function editJadwal(j) {
    document.getElementById('modalTitle').innerText = 'Edit Jadwal';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdJadwal').value = j.id_jadwal;
    document.getElementById('formIdMk').value = j.id_mk;
    document.getElementById('formIdDosen').value = j.id_dosen;
    document.getElementById('formHari').value = j.hari;
    document.getElementById('formJam').value = j.jam;
    document.getElementById('formRuang').value = j.ruang;
    document.getElementById('formKuota').value = j.kuota;
    new bootstrap.Modal(document.getElementById('jadwalModal')).show();
}
</script>
</body>
</html>
