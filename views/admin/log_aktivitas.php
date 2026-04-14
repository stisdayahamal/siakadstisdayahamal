<?php
// views/admin/log_aktivitas.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'superadmin') {
    header("Location: dashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bersihkan_log'])) {
    if ($_SESSION['user']['role'] === 'superadmin') {
        $pdo->query("TRUNCATE TABLE sistem_log_aktivitas");
        $_SESSION['sukses'] = "Semua history log berhasil dibersihkan secara permanen.";
    } else {
        $_SESSION['error'] = "Hanya Superadmin yang berhak membersihkan Master Logs.";
    }
    header("Location: log_aktivitas.php"); exit;
}

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

// Ambil 500 log terakhir demi performa
$logs = $pdo->query("
    SELECT l.*, u.nama, u.role, u.username 
    FROM sistem_log_aktivitas l 
    JOIN users u ON l.user_id = u.id_user 
    ORDER BY l.created_at DESC 
    LIMIT 500
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Global System Logs - ERP Admin</title>
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
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-dark" href="dashboard.php"><i class="bi bi-shield-lock-fill text-danger me-2"></i><?= htmlspecialchars($sys['nama_kampus']) ?> <span class="badge bg-danger ms-2">Security Console</span></a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                <h2 class="fw-bold"><i class="bi bi-activity text-primary me-2"></i>Global Activity Logs</h2>
                <form method="post" onsubmit="return confirm('SANGAT BERBAHAYA! Tindakan ini akan melenyapkan jejak audit seluruh pengguna aplikasi. Lanjutkan?');">
                    <button type="submit" name="bersihkan_log" class="btn btn-outline-danger shadow-sm fw-bold"><i class="bi bi-trash3-fill me-2"></i>Clear All Logs (Superadmin)</button>
                </form>
            </div>

            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Executed',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Access Denied',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="p-3 bg-dark text-white rounded-top-4 d-flex justify-content-between align-items-center">
                        <span class="font-monospace small"><i class="bi bi-terminal"></i> Menampilkan 500 Record Jejak Audit Terakhir</span>
                        <button class="btn btn-sm btn-light fw-bold px-3">Export JSON / CSV</button>
                    </div>
                    <div class="table-responsive p-3">
                        <table class="table table-hover align-middle datatable font-monospace" style="font-size:0.85rem;">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Timestamp / IP</th>
                                    <th>User / Actor</th>
                                    <th>Action</th>
                                    <th>Target Entity</th>
                                    <th width="30%">Change Details (Old ➔ New)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $l): ?>
                                <tr>
                                    <td class="text-muted">
                                        <?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?><br>
                                        <small><i class="bi bi-hdd-network"></i> <?= $l['ip_address'] ?? 'Local/Unknown' ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($l['nama']) ?></strong><br>
                                        <small class="text-muted">[<?= strtoupper($l['role']) ?>] @<?= htmlspecialchars($l['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $bg = $l['aksi'] == 'UPDATE' ? 'warning' : ($l['aksi'] == 'DELETE' ? 'danger' : ($l['aksi'] == 'CREATE' ? 'success' : 'info'));
                                            $ico = $l['aksi'] == 'UPDATE' ? 'pencil' : ($l['aksi'] == 'DELETE' ? 'trash' : 'plus-lg');
                                        ?>
                                        <span class="badge bg-<?= $bg ?> text-<?= $bg == 'warning' ? 'dark' : 'white' ?> rounded-pill px-3">
                                            <i class="bi bi-<?= $ico ?> me-1"></i> <?= $l['aksi'] ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($l['entitas']) ?> <?= $l['entitas_id'] ? '#'.$l['entitas_id'] : '' ?></td>
                                    <td>
                                        <?php if($l['nilai_lama'] || $l['nilai_baru']): ?>
                                            <div class="bg-light p-2 rounded border" style="max-height:80px; overflow-y:auto; line-height:1.2;">
                                                <?php if($l['nilai_lama']): ?>
                                                    <span class="text-danger"><del><?= htmlspecialchars($l['nilai_lama']) ?></del></span>
                                                <?php endif; ?>
                                                <?php if($l['nilai_lama'] && $l['nilai_baru']): ?> <i class="bi bi-arrow-right mx-1 text-muted"></i> <?php endif; ?>
                                                <?php if($l['nilai_baru']): ?>
                                                    <span class="text-success"><?= htmlspecialchars($l['nilai_baru']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No payload / Non-structural change</span>
                                        <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({ 
        language: { search: "Filter Event/Actor:", lengthMenu: "Display _MENU_ events" },
        order: [[0, "desc"]],
        pageLength: 25
    });
});
</script>
</body>
</html>
