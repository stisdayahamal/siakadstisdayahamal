<?php
// views/admin/kelola_user.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'superadmin') {
    header("Location: dashboard.php"); exit;
}

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = $_POST['id'] ?? 0;

    if ($aksi === 'tambah') {
        $username = trim($_POST['username']);
        $nama = trim($_POST['nama']);
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)")->execute([$username, $password, $nama, $role]);
            $_SESSION['sukses'] = "Pengguna $nama ($role) berhasil ditambahkan.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal menambah user. Username mungkin sudah dipakai.";
        }
        header("Location: kelola_user.php"); exit;
    }

    if ($aksi === 'edit') {
        $username = trim($_POST['username']);
        $nama = trim($_POST['nama']);
        $role = $_POST['role'];
        
        $sql = "UPDATE users SET username=?, nama=?, role=? WHERE id_user=?";
        $params = [$username, $nama, $role, $id];

        if (!empty($_POST['password'])) {
            $sql = "UPDATE users SET username=?, nama=?, role=?, password=? WHERE id_user=?";
            $params = [$username, $nama, $role, password_hash($_POST['password'], PASSWORD_DEFAULT), $id];
        }

        try {
            $pdo->prepare($sql)->execute($params);
            $_SESSION['sukses'] = "Data pengguna berhasil diperbarui.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal update. Username duplikat.";
        }
        header("Location: kelola_user.php"); exit;
    }

    if ($aksi === 'hapus') {
        if ($id != $_SESSION['user']['id']) {
            $pdo->prepare("DELETE FROM users WHERE id_user=?")->execute([$id]);
            $_SESSION['sukses'] = "Pengguna dihapus.";
        } else {
            $_SESSION['error'] = "Tidak dapat menghapus akun Anda sendiri!";
        }
        header("Location: kelola_user.php"); exit;
    }
}

// Ambil semua pengguna
$staff = $pdo->query("SELECT * FROM users WHERE role IN ('admin','superadmin') ORDER BY nama ASC")->fetchAll();
$dosen = $pdo->query("SELECT * FROM users WHERE role = 'dosen' ORDER BY nama ASC")->fetchAll();
$mahasiswa = $pdo->query("SELECT * FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Pengguna Sentral - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .nav-tabs .nav-link { background: #f8f9fa; color: #6c757d; }
        .nav-tabs .nav-link.active { background: var(--bs-primary) !important; color: white !important; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-briefcase-fill me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Manajemen Akses & Pengguna</h2>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="resetForm()"><i class="bi bi-person-plus-fill me-2"></i>Tambah User</button>
            </div>

            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <ul class="nav nav-tabs border-bottom-0 gap-2" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold px-4 border-0 rounded-top" data-bs-toggle="tab" data-bs-target="#tab-staff">Admin & Staff</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-4 border-0 rounded-top text-secondary" data-bs-toggle="tab" data-bs-target="#tab-dosen">Dosen</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-4 border-0 rounded-top text-secondary" data-bs-toggle="tab" data-bs-target="#tab-mhs">Mahasiswa</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4 bg-white border border-light">
                    <div class="tab-content">
                        <!-- STAFF -->
                        <div class="tab-pane fade show active" id="tab-staff">
                            <table class="table table-hover align-middle datatable w-100">
                                <thead class="table-light"><tr><th>Avatar</th><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($staff as $u): ?>
                                    <tr>
                                        <td><img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nama']) ?>&background=random" class="rounded-circle" width="35"></td>
                                        <td class="fw-bold"><?= htmlspecialchars($u['nama']) ?></td>
                                        <td>@<?= htmlspecialchars($u['username']) ?></td>
                                        <td><span class="badge bg-danger rounded-pill"><?= strtoupper($u['role']) ?></span></td>
                                        <td><?= buildActions($u) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- DOSEN -->
                        <div class="tab-pane fade" id="tab-dosen">
                            <table class="table table-hover align-middle datatable w-100">
                                <thead class="table-light"><tr><th>Avatar</th><th>Nama Dosen</th><th>NIDN / Username</th><th>Role</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($dosen as $u): ?>
                                    <tr>
                                        <td><img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nama']) ?>&background=random" class="rounded-circle" width="35"></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($u['nama']) ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><span class="badge bg-info text-dark rounded-pill"><?= strtoupper($u['role']) ?></span></td>
                                        <td><?= buildActions($u) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- MHS -->
                        <div class="tab-pane fade" id="tab-mhs">
                            <table class="table table-hover align-middle datatable w-100">
                                <thead class="table-light"><tr><th>Avatar</th><th>Nama Mahasiswa</th><th>NIM (Username)</th><th>Role</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($mahasiswa as $u): ?>
                                    <tr>
                                        <td><img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nama']) ?>&background=random" class="rounded-circle" width="35"></td>
                                        <td class="fw-bold text-success"><?= htmlspecialchars($u['nama']) ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><span class="badge bg-secondary rounded-pill"><?= strtoupper($u['role']) ?></span></td>
                                        <td><?= buildActions($u) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Akun Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="aksi" id="formAksi" value="tambah">
                    <input type="hidden" name="id" id="formId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Nama Lengkap</label>
                        <input type="text" name="nama" id="formNama" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Username / ID</label>
                            <input type="text" name="username" id="formUsername" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-muted">Hak Akses</label>
                            <select name="role" id="formRole" class="form-select" required>
                                <option value="admin">Admin / Staff</option>
                                <option value="dosen">Dosen</option>
                                <option value="mahasiswa">Mahasiswa</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Password</label>
                        <input type="password" name="password" id="formPassword" class="form-control">
                        <small class="text-secondary" id="passHelp">Wajib diisi untuk tambah akun.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
function buildActions($u) {
    if($_SESSION['user']['id'] == $u['id_user']) return '<span class="badge bg-light text-dark border">It\'s You</span>';
    $json = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
    return "
        <button class='btn btn-sm btn-outline-primary border-0 me-1' onclick='editUser($json)'><i class='bi bi-pencil'></i></button>
        <form method='post' class='d-inline' onsubmit=\"return confirm('Hapus akses login pengguna ini?');\">
            <input type='hidden' name='aksi' value='hapus'>
            <input type='hidden' name='id' value='{$u['id_user']}'>
            <button type='submit' class='btn btn-sm btn-outline-danger border-0'><i class='bi bi-trash'></i></button>
        </form>
    ";
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({ language: { search: "Cari:", lengthMenu: "Tampil _MENU_ data" }});
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});

function resetForm() {
    $('#modalTitle').text('Buat Akun Baru');
    $('#formAksi').val('tambah');
    $('#formId').val('');
    $('#formNama').val('');
    $('#formUsername').val('');
    $('#formRole').val('mahasiswa');
    $('#formPassword').attr('required', true);
    $('#passHelp').text('Wajib diisi minimal 6 karakter.');
}

function editUser(data) {
    $('#modalTitle').text('Edit Akun & Reset Password');
    $('#formAksi').val('edit');
    $('#formId').val(data.id_user);
    $('#formNama').val(data.nama);
    $('#formUsername').val(data.username);
    $('#formRole').val(data.role);
    $('#formPassword').removeAttr('required').val('');
    $('#passHelp').text('Kosongkan jika TIDAK ingin mengubah password.');
    new bootstrap.Modal('#modalUser').show();
}
</script>
</body>
</html>
