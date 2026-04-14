<?php
// views/admin/konfirmasi_keuangan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$msg = ''; $status_msg = '';

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    csrf_validate();
    $id_konf = intval($_POST['id_konfirmasi']);
    $id_tagihan = intval($_POST['id_tagihan']);
    $aksi = $_POST['aksi'];
    $catatan = $_POST['catatan'] ?? '';

    if ($aksi === 'setujui') {
        try {
            $pdo->beginTransaction();
            // 1. Update Tabel Konfirmasi
            $pdo->prepare("UPDATE konfirmasi_pembayaran SET status = 'Disetujui', catatan_admin = ? WHERE id = ?")
                ->execute([$catatan, $id_konf]);
            
            // 2. Update Tabel Tagihan
            $pdo->prepare("UPDATE tagihan SET status_lunas = 'lunas', tanggal_bayar = CURDATE() WHERE id_tagihan = ?")
                ->execute([$id_tagihan]);
            
            // 3. Sinkronisasi Profil Mahasiwa jika SPP
            $tagihan = $pdo->prepare("SELECT id_mhs, jenis FROM tagihan WHERE id_tagihan = ?");
            $tagihan->execute([$id_tagihan]);
            $t = $tagihan->fetch();
            if ($t && $t['id_mhs'] && $t['jenis'] === 'SPP') {
                $pdo->prepare("UPDATE mahasiswa SET status_pembayaran = '1' WHERE id_mhs = ?")->execute([$t['id_mhs']]);
            }
            if ($t && $t['id_calon'] && $t['jenis'] === 'Pendaftaran') {
                $pdo->prepare("UPDATE calon_mhs SET sudah_bayar = 1 WHERE id_calon = ?")->execute([$t['id_calon']]);
            }

            $pdo->commit();
            $msg = "Konfirmasi pembayaran disetujui. Tagihan otomatis lunas."; $status_msg = "success";
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "Gagal memproses approval: " . $e->getMessage(); $status_msg = "danger";
        }
    } elseif ($aksi === 'tolak') {
        $pdo->prepare("UPDATE konfirmasi_pembayaran SET status = 'Ditolak', catatan_admin = ? WHERE id = ?")
            ->execute([$catatan, $id_konf]);
        $msg = "Konfirmasi pembayaran ditolak."; $status_msg = "warning";
    }
}

// Fetch Pending Confirmations
$sql = "
    SELECT k.*, t.id_mhs, t.id_calon, t.jenis, t.nominal, t.kode_tahun,
           m.nama as nama_mhs, m.nim,
           c.nama as nama_calon
    FROM konfirmasi_pembayaran k
    JOIN tagihan t ON k.id_tagihan = t.id_tagihan
    LEFT JOIN mahasiswa m ON t.id_mhs = m.id_mhs
    LEFT JOIN calon_mhs c ON t.id_calon = c.id_calon
    WHERE k.status = 'Menunggu'
    ORDER BY k.created_at ASC
";
$list_konfirmasi = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Verifikasi Keuangan - Admin ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-patch-check-fill me-2"></i>Verifikasi Keuangan</a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4">Pengajuan Konfirmasi Pembayaran</h2>

            <?php if($msg): ?>
                <div class="alert alert-<?= $status_msg ?> shadow-sm border-0 alert-dismissible fade show" role="alert">
                    <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if (empty($list_konfirmasi)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle fs-1 text-success opacity-25 d-block mb-3"></i>
                            <h5 class="text-muted">Tidak ada pengajuan pembayaran baru.</h5>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu Pengajuan</th>
                                    <th>Si Pembayar</th>
                                    <th>Tagihan (Item)</th>
                                    <th>Bukti Bayar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($list_konfirmasi as $k): ?>
                                <tr>
                                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($k['created_at'])) ?></small></td>
                                    <td>
                                        <?php if($k['id_mhs']): ?>
                                            <strong class="text-primary d-block"><?= htmlspecialchars($k['nama_mhs']) ?></strong>
                                            <small class="text-muted">NIM: <?= $k['nim'] ?></small>
                                        <?php else: ?>
                                            <strong class="text-success d-block"><?= htmlspecialchars($k['nama_calon']) ?></strong>
                                            <small class="text-muted">ID: CM-<?= $k['id_calon'] ?> (Calon)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">Rp <?= number_format($k['nominal'], 0, ',', '.') ?></div>
                                        <small class="badge bg-secondary-subtle text-secondary border"><?= $k['jenis'] ?> (Smt: <?= $k['kode_tahun'] ?>)</small>
                                    </td>
                                    <td>
                                        <a href="../../<?= $k['bukti_bayar'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i> Lihat Bukti
                                        </a>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="id_konfirmasi" value="<?= $k['id'] ?>">
                                            <input type="hidden" name="id_tagihan" value="<?= $k['id_tagihan'] ?>">
                                            <button type="button" class="btn btn-sm btn-success btn-approve" data-id="<?= $k['id'] ?>" data-tagihan="<?= $k['id_tagihan'] ?>" data-bs-toggle="modal" data-bs-target="#modalApproval">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger btn-reject" data-id="<?= $k['id'] ?>" data-tagihan="<?= $k['id_tagihan'] ?>" data-bs-toggle="modal" data-bs-target="#modalApproval">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Approval/Rejection -->
<div class="modal fade" id="modalApproval" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_input() ?>
            <input type="hidden" name="id_konfirmasi" id="modal_id_konf">
            <input type="hidden" name="id_tagihan" id="modal_id_tagihan">
            <input type="hidden" name="aksi" id="modal_aksi">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="title_aksi">Proses Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-0">
                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan Admin (Opsional)</label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Masukkan alasan penolakan atau catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 mt-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn px-4 fw-bold" id="btn_submit_aksi">Proses</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.btn-approve').click(function() {
        $('#modal_id_konf').val($(this).data('id'));
        $('#modal_id_tagihan').val($(this).data('tagihan'));
        $('#modal_aksi').val('setujui');
        $('#title_aksi').text('Setujui Pembayaran');
        $('#btn_submit_aksi').removeClass('btn-danger').addClass('btn-success').text('Ya, Setujui');
    });

    $('.btn-reject').click(function() {
        $('#modal_id_konf').val($(this).data('id'));
        $('#modal_id_tagihan').val($(this).data('tagihan'));
        $('#modal_aksi').val('tolak');
        $('#title_aksi').text('Tolak Pembayaran');
        $('#btn_submit_aksi').removeClass('btn-success').addClass('btn-danger').text('Ya, Tolak');
    });
});
</script>
</body>
</html>
