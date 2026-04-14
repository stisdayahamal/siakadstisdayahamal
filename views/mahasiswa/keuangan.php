<?php
// views/mahasiswa/keuangan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'mahasiswa') { header('Location: dashboard.php'); exit; }
$id_mhs = $_SESSION['user']['id_mhs'] ?? 0;

$msg = '';
$status_msg = '';

// Handle Konfirmasi Pembayaran (Upload Bukti)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'konfirmasi_bayar') {
    csrf_validate();
    $id_tagihan = intval($_POST['id_tagihan']);
    
    // Verifikasi bahwa tagihan ini milik mahasiswa tersebut
    $cek = $pdo->prepare("SELECT id_tagihan FROM tagihan WHERE id_tagihan = ? AND id_mhs = ?");
    $cek->execute([$id_tagihan, $id_mhs]);
    if ($cek->fetch()) {
        if (!empty($_FILES['bukti_bayar']['name'])) {
            $file = $_FILES['bukti_bayar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($ext, $allowed)) {
                if ($file['size'] < 2*1024*1024) { // Max 2MB
                    $filename = 'bukti_' . $id_tagihan . '_' . time() . '.' . $ext;
                    $target = '../../uploads/bukti_bayar/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        // Simpan ke konfirmasi_pembayaran
                        // Hapus dulu kalau pernah ada yang ditolak sebelumnya agar fresh
                        $pdo->prepare("DELETE FROM konfirmasi_pembayaran WHERE id_tagihan = ? AND status = 'Ditolak'")->execute([$id_tagihan]);
                        
                        $stmt = $pdo->prepare("INSERT INTO konfirmasi_pembayaran (id_tagihan, bukti_bayar, status) VALUES (?, ?, 'Menunggu')");
                        $stmt->execute([$id_tagihan, 'uploads/bukti_bayar/' . $filename]);
                        
                        $msg = "Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.";
                        $status_msg = "success";
                    } else {
                        $msg = "Gagal mengunggah file."; $status_msg = "danger";
                    }
                } else {
                    $msg = "Ukuran file terlalu besar (Max 2MB)."; $status_msg = "danger";
                }
            } else {
                $msg = "Format file tidak didukung (Gunakan JPG, PNG, atau PDF)."; $status_msg = "danger";
            }
        }
    }
}

// Ambil semua tagihan mahasiswa + status konfirmasinya
$sql = "
    SELECT t.*, k.status as status_konfirmasi, k.catatan_admin, k.bukti_bayar as link_bukti
    FROM tagihan t 
    LEFT JOIN konfirmasi_pembayaran k ON t.id_tagihan = k.id_tagihan
    WHERE t.id_mhs = ? 
    ORDER BY t.kode_tahun DESC, t.id_tagihan DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_mhs]);
$tagihan_list = $stmt->fetchAll();

// Hitung Ringkasan
$total_tunggakan = 0;
$total_bayar = 0;
foreach($tagihan_list as $t) {
    if ($t['status_lunas'] === 'lunas') $total_bayar += $t['nominal'];
    else $total_tunggakan += $t['nominal'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Keuangan - SIAKAD Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa</a>
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

        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
            <h1 class="h2 text-dark fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i>Informasi Keuangan</h1>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?= $status_msg ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i><?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Keuangan -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-4">
                    <div class="card-body py-1">
                        <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 opacity-50"></i>
                        <p class="mb-0 small opacity-75 fw-medium">Total Tunggakan</p>
                        <h3 class="fw-bold mb-0">Rp <?= number_format($total_tunggakan, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-4">
                    <div class="card-body py-1">
                        <i class="bi bi-patch-check-fill fs-3 mb-2 opacity-50"></i>
                        <p class="mb-0 small opacity-75 fw-medium">Total Sudah Dibayar</p>
                        <h3 class="fw-bold mb-0">Rp <?= number_format($total_bayar, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-info text-white p-3 rounded-4">
                    <div class="card-body py-1">
                        <i class="bi bi-invoice fs-3 mb-2 opacity-50"></i>
                        <p class="mb-0 small opacity-75 fw-medium">Total Tagihan</p>
                        <h3 class="fw-bold mb-0"><?= count($tagihan_list) ?> Invoice</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Tagihan -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white fw-bold border-0 pt-4 px-4 h5">
                <i class="bi bi-list-check me-2 text-primary"></i>Riwayat Tagihan & Pembayaran
            </div>
            <div class="card-body p-4">
                <?php if (empty($tagihan_list)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-light"></i>
                        Belum ada data tagihan. Hubungi bagian keuangan kampus.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-light">
                        <thead class="table-light">
                            <tr>
                                <th>Semester</th>
                                <th>Jenis Tagihan</th>
                                <th>Nominal</th>
                                <th>Status Tagihan</th>
                                <th>Verifikasi Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tagihan_list as $t): 
                            $is_lunas = strtolower($t['status_lunas']) === 'lunas';
                            $conf_status = $t['status_konfirmasi'];
                        ?>
                            <tr>
                                <td class="fw-semibold text-muted font-monospace"><?= htmlspecialchars($t['kode_tahun']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= htmlspecialchars($t['jenis']) ?></span></td>
                                <td class="fw-bold text-dark h6 mb-0">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($is_lunas): ?>
                                        <span class="badge bg-success-subtle text-success py-2 px-3 rounded-pill"><i class="bi bi-check-circle me-1"></i>Lunas</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger py-2 px-3 rounded-pill"><i class="bi bi-clock-history me-1"></i>Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($conf_status === 'Menunggu'): ?>
                                        <span class="badge bg-warning-subtle text-warning border px-2">Menunggu Verifikasi</span>
                                    <?php elseif ($conf_status === 'Disetujui'): ?>
                                        <span class="badge bg-success-subtle text-success border px-2">Data Valid</span>
                                    <?php elseif ($conf_status === 'Ditolak'): ?>
                                        <span class="badge bg-danger-subtle text-danger border px-2" title="<?= $t['catatan_admin'] ?>">Ditolak <i class="bi bi-info-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Konfirmasi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$is_lunas && $conf_status !== 'Menunggu'): ?>
                                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3 btn-konfirmasi" 
                                                data-id="<?= $t['id_tagihan'] ?>" 
                                                data-jenis="<?= $t['jenis'] ?>" 
                                                data-nominal="<?= number_format($t['nominal'],0,',','.') ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalKonfirmasi">
                                            <i class="bi bi-cloud-upload me-1"></i> Konfirmasi Pembayaran
                                        </button>
                                    <?php elseif ($conf_status === 'Menunggu'): ?>
                                        <button class="btn btn-outline-secondary btn-sm disabled"><i class="bi bi-hourglass-split"></i> Diproses...</button>
                                    <?php else: ?>
                                        <span class="text-success small fw-bold"><i class="bi bi-shield-check me-1"></i>Transaksi Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Rekening -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-4 bg-primary text-white p-5 d-flex flex-column justify-content-center align-items-center">
                            <i class="bi bi-bank2 fs-1 mb-3"></i>
                            <h4 class="fw-bold mb-1 text-center font-inter"><?= htmlspecialchars($sys['narek_bank']) ?></h4>
                            <h2 class="fw-bold mb-2 text-center"><?= htmlspecialchars($sys['rekening_bank']) ?></h2>
                            <p class="mb-0 opacity-75 text-center"><?= htmlspecialchars($sys['alamat_kampus']) ?></p>
                        </div>
                        <div class="col-md-8 p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Panduan Pembayaran</h5>
                            <div class="alert alert-info border-0 shadow-sm bg-info-subtle text-info-emphasis p-4 rounded-4 mb-0">
                                <div class="small lh-lg">
                                    <?= nl2br(htmlspecialchars($sys['instruksi_pembayaran'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </main>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_input() ?>
            <input type="hidden" name="aksi" value="konfirmasi_bayar">
            <input type="hidden" name="id_tagihan" id="input_id_tagihan">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Konfirmasi Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <div class="alert alert-light border small text-muted">
                    <p class="mb-1">Tagihan: <strong id="txt_jenis"></strong></p>
                    <p class="mb-0">Nominal: <strong class="text-primary" id="txt_nominal"></strong></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Unggah Bukti Pembayaran</label>
                    <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="form-text text-danger">* Format: JPG, PNG, atau PDF (Maks 2MB)</div>
                </div>
                <div class="form-check small text-muted">
                    <input class="form-check-input" type="checkbox" required id="checkVerify">
                    <label class="form-check-label" for="checkVerify">
                        Saya menyatakan data yang saya unggah adalah bukti pembayaran yang sah dan benar.
                    </label>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Kirim Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('.btn-konfirmasi').click(function() {
        $('#input_id_tagihan').val($(this).data('id'));
        $('#txt_jenis').text($(this).data('jenis'));
        $('#txt_nominal').text('Rp ' + $(this).data('nominal'));
    });
});
</script>
</body>
</html>
