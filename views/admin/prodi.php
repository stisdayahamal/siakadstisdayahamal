<?php
// views/admin/prodi.php - Manajemen Program Studi dengan Kode Prodi (NIM Support)
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
csrf_validate();

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $nama_prodi = trim($_POST['nama_prodi'] ?? '');
    $kode_prodi = trim($_POST['kode_prodi'] ?? '');
    $id_fakultas = !empty($_POST['id_fakultas']) ? intval($_POST['id_fakultas']) : null;

    if ($_POST['action'] === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO prodi (nama_prodi, kode_prodi, id_fakultas) VALUES (?, ?, ?)');
            $stmt->execute([$nama_prodi, $kode_prodi, $id_fakultas]);
            $success = 'Program Studi berhasil ditambahkan dengan kode ' . $kode_prodi;
        } catch (PDOException $e) {
            $error = 'Gagal menambah program studi: ' . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update') {
        $id_prodi = intval($_POST['id_prodi']);
        try {
            $stmt = $pdo->prepare('UPDATE prodi SET nama_prodi=?, kode_prodi=?, id_fakultas=? WHERE id_prodi=?');
            $stmt->execute([$nama_prodi, $kode_prodi, $id_fakultas, $id_prodi]);
            $success = 'Data Program Studi berhasil diperbarui.';
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui program studi: ' . $e->getMessage();
        }
    }
}

// Handle Delete (POST for Security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id_prodi = intval($_POST['id_prodi']);
    try {
        $pdo->prepare('DELETE FROM prodi WHERE id_prodi=?')->execute([$id_prodi]);
        $success = 'Program Studi berhasil dihapus.';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus program studi (mungkin sedang digunakan).';
    }
}

$prodiList = $pdo->query('
    SELECT p.*, f.nama_fakultas, 
           (SELECT COUNT(id_mhs) FROM mahasiswa WHERE id_prodi=p.id_prodi) AS jumlah_mhs 
    FROM prodi p 
    LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas 
    ORDER BY p.id_prodi DESC
')->fetchAll();

$fakultasList = $pdo->query("SELECT * FROM fakultas")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Kelola Program Studi - SIAKAD Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-mortarboard text-warning me-2"></i>SIAKAD Prodi Hub
        </a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Dashboard</a>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Manajemen Program Studi</h2>
                    <p class="text-muted small">Update Kode Prodi untuk format NIM (01.KodeProdi.Thn.NoUrut)</p>
                </div>
                <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#prodiModal" onclick="resetForm()">
                    <i class="bi bi-plus-lg me-2"></i>Prodi Baru
                </button>
            </div>

            <?php if ($success): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($success) ?>'});</script><?php endif; ?>
            <?php if ($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle datatable-prodi">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode Prodi</th>
                                    <th>Nama Program Studi</th>
                                    <th>Fakultas</th>
                                    <th>Mhs Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prodiList as $p): ?>
                                <tr>
                                    <td class="fw-bold text-primary fs-5"><?= htmlspecialchars($p['kode_prodi'] ?: '??') ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($p['nama_prodi']) ?></div>
                                        <span class="small text-muted">ID Internal: #<?= htmlspecialchars($p['id_prodi']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($p['nama_fakultas'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($p['jumlah_mhs']) ?> Student</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info rounded-pill" onclick='editData(<?= json_encode($p) ?>)'><i class="bi bi-pencil-square me-1"></i>Edit</button>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="confirmDelete(<?= $p['id_prodi'] ?>, '<?= htmlspecialchars($p['nama_prodi'], ENT_QUOTES) ?>')"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="prodiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="post">
                <?= csrf_input() ?>
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Konfigurasi Program Studi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id_prodi" id="formIdProdi">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Fakultas Induk</label>
                        <select name="id_fakultas" id="formIdFakultas" class="form-select">
                            <option value="">-- Tanpa Fakultas --</option>
                            <?php foreach($fakultasList as $f): ?>
                                <option value="<?= $f['id_fakultas'] ?>"><?= htmlspecialchars($f['nama_fakultas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Kode Prodi (NIM)</label>
                            <input type="text" name="kode_prodi" id="formKodeProdi" class="form-control fw-bold border-primary" maxlength="10" placeholder="Contoh: 01" required>
                            <div class="form-text small">Gunakan 2 digit untuk sinkronisasi NIM.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Nama Lengkap Prodi</label>
                            <input type="text" name="nama_prodi" id="formNamaProdi" class="form-control" placeholder="Cth: Hukum Pidana Islam" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 pe-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-bold">Simpan Prodi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable-prodi').DataTable({ language: { search: "Cari Prodi:", lengthMenu: "Tampil _MENU_" }});
});

function resetForm() {
    document.getElementById('modalTitle').innerText = 'Tambah Program Studi Baru';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formIdProdi').value = '';
    document.getElementById('formIdFakultas').value = '';
    document.getElementById('formNamaProdi').value = '';
    document.getElementById('formKodeProdi').value = '';
}

function editData(data) {
    document.getElementById('modalTitle').innerText = 'Sunting Program Studi';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdProdi').value = data.id_prodi;
    document.getElementById('formIdFakultas').value = data.id_fakultas || '';
    document.getElementById('formNamaProdi').value = data.nama_prodi;
    document.getElementById('formKodeProdi').value = data.kode_prodi;
    new bootstrap.Modal(document.getElementById('prodiModal')).show();
}

function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Program Studi?',
        text: "Menghapus prodi '" + nama + "' akan berdampak pada data mahasiswa terkait. Aksi ini tidak dapat dibatalkan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Permanen',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            
            const actionInp = document.createElement('input');
            actionInp.type = 'hidden';
            actionInp.name = 'action';
            actionInp.value = 'delete';
            form.appendChild(actionInp);
            
            const idInp = document.createElement('input');
            idInp.type = 'hidden';
            idInp.name = 'id_prodi';
            idInp.value = id;
            form.appendChild(idInp);

            const token = document.querySelector('input[name="csrf_token"]');
            if (token) {
                const csrfInp = document.createElement('input');
                csrfInp.type = 'hidden';
                csrfInp.name = 'csrf_token';
                csrfInp.value = token.value;
                form.appendChild(csrfInp);
            }

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</body>
</html>
