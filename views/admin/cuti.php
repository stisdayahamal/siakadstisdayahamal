<?php
// views/admin/cuti.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$user_id = $user['id'];

$sukses = $_SESSION['sukses'] ?? '';
unset($_SESSION['sukses']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajukan_cuti'])) {
        $tipe = $_POST['tipe'];
        $mulai = $_POST['tgl_mulai'];
        $selesai = $_POST['tgl_selesai'];
        $alasan = $_POST['alasan'];
        
        $pdo->prepare("INSERT INTO izin_cuti (user_id, tipe, tgl_mulai, tgl_selesai, alasan) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user_id, $tipe, $mulai, $selesai, $alasan]);
            
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
            ->execute([$user_id, 'CREATE', "Pengajuan $tipe"]);

        $_SESSION['sukses'] = "Pengajuan berhasil dikirim dan menunggu persetujuan.";
        header("Location: cuti.php"); exit;
    }
    
    if (isset($_POST['aksi_cuti']) && ($user['role'] === 'admin' || $user['role'] === 'superadmin')) {
        $id = $_POST['id_cuti'];
        $status = $_POST['status']; 
        $pdo->prepare("UPDATE izin_cuti SET status = ? WHERE id = ?")->execute([$status, $id]);
        
        $pdo->prepare("INSERT INTO sistem_log_aktivitas (user_id, aksi, entitas) VALUES (?, ?, ?)")
            ->execute([$user_id, 'UPDATE', "Status Pengajuan #$id diset ke $status"]);

        $_SESSION['sukses'] = "Status pengajuan berhasil diperbarui.";
        header("Location: cuti.php"); exit;
    }
}

$riwayat_saya = $pdo->prepare("SELECT * FROM izin_cuti WHERE user_id = ? ORDER BY created_at DESC");
$riwayat_saya->execute([$user_id]);
$riwayat_saya = $riwayat_saya->fetchAll();

$butuh_persetujuan = [];
if ($user['role'] === 'admin') {
    $butuh_persetujuan = $pdo->query("SELECT c.*, u.nama, u.role FROM izin_cuti c JOIN users u ON c.user_id = u.id_user WHERE c.status = 'Menunggu' ORDER BY c.created_at ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Izin & Cuti - ERP Admin</title>
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
            <h2 class="fw-bold mb-4">Layanan Cuti & Perizinan</h2>
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-primary text-white border-0 pt-3 pb-2 rounded-top-4">
                            <h5 class="fw-bold mb-0"><i class="bi bi-calendar-plus me-2"></i>Buat Pengajuan Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tipe Pengajuan</label>
                                    <select name="tipe" class="form-select" required>
                                        <option value="Cuti">Cuti Tahunan</option>
                                        <option value="Izin">Izin Tidak Masuk</option>
                                        <option value="Sakit">Sakit (Sertakan Surat Dokter nantinya)</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Dari Tanggal</label>
                                        <input type="date" name="tgl_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Sampai Tanggal</label>
                                        <input type="date" name="tgl_selesai" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Alasan Lengkap</label>
                                    <textarea name="alasan" class="form-control" rows="3" required placeholder="Jelaskan alasan pengajuan Anda..."></textarea>
                                </div>
                                <button type="submit" name="ajukan_cuti" class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-send-fill me-2"></i>Kirim Pengajuan</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php if($user['role'] === 'admin' && count($butuh_persetujuan) > 0): ?>
                    <!-- Kotak Persetujuan untuk Admin -->
                    <div class="card border-0 shadow-sm rounded-4 border-start border-warning border-4 mb-4 h-50">
                        <div class="card-header bg-white border-0 pt-3 pb-0">
                            <h5 class="fw-bold text-warning mb-0"><i class="bi bi-exclamation-circle-fill me-2"></i>Butuh Persetujuan Anda</h5>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Pemohon</th>
                                            <th>Tipe & Waktu</th>
                                            <th>Alasan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($butuh_persetujuan as $b): ?>
                                        <tr>
                                            <td class="ps-4"><strong><?= htmlspecialchars($b['nama']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($b['role']) ?></small></td>
                                            <td><span class="badge bg-secondary mb-1"><?= $b['tipe'] ?></span><br><small><?= date('d M', strtotime($b['tgl_mulai'])) ?> s/d <?= date('d M Y', strtotime($b['tgl_selesai'])) ?></small></td>
                                            <td><small><?= htmlspecialchars($b['alasan']) ?></small></td>
                                            <td>
                                                <form method="post" class="d-inline-flex gap-1" onsubmit="return confirm('Apakah Anda yakin mengambil tindakan ini?');">
                                                    <input type="hidden" name="id_cuti" value="<?= $b['id'] ?>">
                                                    <button type="submit" name="aksi_cuti" class="btn btn-sm btn-success" value="Disetujui" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                                    <button type="submit" name="aksi_cuti" class="btn btn-sm btn-danger" value="Ditolak" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                                    <input type="hidden" name="status" value="" id="status_<?= $b['id'] ?>">
                                                </form>
                                            </td>
                                        </tr>
                                        <script>
                                            // Intersep form submit agar value tombol yang diklik masuk ke input hidden
                                            document.querySelectorAll('button[name="aksi_cuti"]').forEach(btn => {
                                                btn.addEventListener('click', function() { this.closest('form').querySelector('input[name="status"]').value = this.value; });
                                            });
                                        </script>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Histori Pengajuan Sendiri -->
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0"><i class="bi bi-card-checklist text-primary me-2"></i>Riwayat Pengajuan Saya</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Tipe</th>
                                            <th>Dari</th>
                                            <th>Sampai</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($riwayat_saya) > 0): ?>
                                            <?php foreach($riwayat_saya as $r): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold"><?= $r['tipe'] ?></td>
                                                <td><?= date('d M Y', strtotime($r['tgl_mulai'])) ?></td>
                                                <td><?= date('d M Y', strtotime($r['tgl_selesai'])) ?></td>
                                                <td>
                                                    <?php 
                                                        $badge = $r['status'] == 'Disetujui' ? 'success' : ($r['status'] == 'Ditolak' ? 'danger' : 'warning text-dark'); 
                                                    ?>
                                                    <span class="badge bg-<?= $badge ?> rounded-pill px-3"><?= $r['status'] ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat pengajuan.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
