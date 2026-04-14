<?php
// views/mahasiswa/krs_input.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: dashboard.php');
    exit;
}

$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];

// Ambil semester aktif menggunakan helper
$tahun_aktif = get_tahun_aktif($pdo);
$kode_tahun = $tahun_aktif['kode_tahun'] ?? '';

$no_semester_aktif = !$kode_tahun;
$matkul = [];
$jatah_sks = get_jatah_sks_mahasiswa($pdo, $id_mhs);

if (!$no_semester_aktif) {
    // Cek Pembayaran
    $cek_lunas = $pdo->prepare("SELECT status_lunas FROM tagihan WHERE id_mhs = ? AND kode_tahun = ? AND jenis = 'SPP'");
    $cek_lunas->execute([$id_mhs, $kode_tahun]);
    $status_bayar = $cek_lunas->fetchColumn();

    // Ambil Jadwal Kuliah (Real Data)
    $stmt = $pdo->prepare('
        SELECT jk.*, mk.nama_mk, mk.sks, d.nama as nama_dosen 
        FROM jadwal_kuliah jk 
        JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk 
        JOIN dosen d ON jk.id_dosen = d.id_dosen
        ORDER BY jk.hari, jk.jam
    ');
    $stmt->execute();
    $matkul = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Input KRS - SIAKAD Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-3">
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 fw-bold">Pengisian KRS Online</h1>
            <p class="text-muted">Jatah SKS Anda: <span class="badge bg-success"><?= $jatah_sks ?> SKS</span> (Berdasarkan Standar DIKTI)</p>
        </div>
        <?php if ($kode_tahun): ?>
            <span class="badge bg-info text-dark p-2 fs-6 shadow-sm"><i class="bi bi-calendar-check me-1"></i> Semester: <?= htmlspecialchars($kode_tahun) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($no_semester_aktif): ?>
        <div class="alert alert-warning shadow-sm text-center rounded-4 border-0 p-5">
            <i class="bi bi-hourglass-split text-warning mb-3" style="font-size: 3rem;"></i>
            <h5 class="fw-bold">Semester Aktif Belum Ditetapkan</h5>
            <p class="text-secondary">Administrator belum mengaktifkan Tahun Akademik baru. Silakan kembali lagi nanti.</p>
        </div>
    <?php elseif ($status_bayar !== 'lunas'): ?>
        <div class="alert alert-danger shadow-sm rounded-4 border-0 p-5 text-center">
            <i class="bi bi-lock-fill text-danger mb-3" style="font-size: 3rem;"></i>
            <h5 class="fw-bold">Akses Terkunci: Status Keuangan Belum Lunas</h5>
            <p class="text-secondary">Anda harus melunasi tagihan SPP semester <?= $kode_tahun ?> untuk melakukan pengisian KRS.</p>
            <a href="keuangan.php" class="btn btn-primary px-4 rounded-pill mt-3">Cek Detail Tagihan</a>
        </div>
    <?php else: ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
        <div class="card-body p-0">
            <form action="proses_krs.php" method="POST" id="krsForm">
                <?= csrf_input() ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="ps-4">
                                    <input type="checkbox" id="checkAll" class="form-check-input">
                                </th>
                                <th width="30%">Mata Kuliah</th>
                                <th width="15%">Jadwal</th>
                                <th width="10%">Ruang</th>
                                <th width="10%">SKS</th>
                                <th width="20%">Dosen</th>
                                <th width="10%">Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($matkul)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada mata kuliah yang dibuka untuk semester ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($matkul as $mk): ?>
                                <tr>
                                    <td class="ps-4">
                                        <input type="checkbox" name="id_jadwal[]" class="mk-checkbox form-check-input" 
                                               value="<?= $mk['id_jadwal'] ?>" 
                                               data-sks="<?= $mk['sks'] ?>">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($mk['nama_mk']) ?></div>
                                        <div class="small text-muted font-monospace"><?= $mk['id_mk'] ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($mk['hari']) ?>, <?= substr($mk['jam'],0,5) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($mk['ruang']) ?></span></td>
                                    <td class="fw-bold"><?= $mk['sks'] ?></td>
                                    <td class="small"><?= htmlspecialchars($mk['nama_dosen']) ?></td>
                                    <td><?= $mk['kuota'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white border-top p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">Terpilih: </span>
                        <span id="totalSKS" class="h4 fw-bold text-primary mb-0">0</span>
                        <span class="h4 text-muted fw-light mb-0"> / <?= $jatah_sks ?> SKS</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg shadow px-5 rounded-pill fw-bold">
                        <i class="bi bi-save2-fill me-2"></i>Ajukan KRS Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>
    </main>
  </div>
</div>

<script>
    const maxSKS = <?= $jatah_sks ?>;

    function calculateTotalSKS() {
        let total = 0;
        $('.mk-checkbox:checked').each(function() {
            total += parseInt($(this).data('sks'));
        });
        return total;
    }

    $(document).ready(function() {
        $('#checkAll').on('change', function() {
            $('.mk-checkbox').prop('checked', this.checked);
            let total = calculateTotalSKS();
            if (total > maxSKS && this.checked) {
                Swal.fire('Peringatan', 'Total SKS melebihi jatah jatah Anda (' + maxSKS + '). Mohon sesuaikan kembali.', 'warning');
            }
            $('#totalSKS').text(total).toggleClass('text-danger', total > maxSKS);
        });

        $('.mk-checkbox').on('change', function() {
            let total = calculateTotalSKS();
            if (total > maxSKS) {
                Swal.fire('Limit Tercapai', 'Anda hanya diperbolehkan mengambil maksimal ' + maxSKS + ' SKS semester ini.', 'error');
                $(this).prop('checked', false);
                total = calculateTotalSKS();
            }
            $('#totalSKS').text(total).toggleClass('text-danger', total > maxSKS);
            $('#checkAll').prop('checked', $('.mk-checkbox:checked').length == $('.mk-checkbox').length);
        });

        $('#krsForm').on('submit', function(e) {
            if ($('.mk-checkbox:checked').length === 0) {
                e.preventDefault();
                Swal.fire('Oops!', 'Pilih minimal satu mata kuliah untuk diajukan.', 'info');
            }
        });
    });
</script>
</body>
</html>
