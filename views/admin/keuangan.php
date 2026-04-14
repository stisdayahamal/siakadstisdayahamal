<?php
// views/admin/keuangan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$msg = '';
$status = '';

// 1. Handle Update Status Lunas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_status') {
    csrf_validate();
    $id_tagihan = intval($_POST['id_tagihan']);
    $status_lunas = $_POST['status_lunas'];
    
    if (in_array($status_lunas, ['lunas', 'belum'])) {
        $stmt = $pdo->prepare("UPDATE tagihan SET status_lunas = ? WHERE id_tagihan = ?");
        $stmt->execute([$status_lunas, $id_tagihan]);
        $msg = "Status tagihan berhasil diperbarui.";
        $status = "success";
        
        // Sinkronisasi status_pembayaran di profil mahasiswa jika ini adalah tagihan SPP
        $tagihan = $pdo->prepare("SELECT id_mhs, id_calon, jenis FROM tagihan WHERE id_tagihan = ?");
        $tagihan->execute([$id_tagihan]);
        $t = $tagihan->fetch();
        if ($t && $t['id_mhs'] && $t['jenis'] === 'SPP') {
            $val_mhs = ($status_lunas === 'lunas') ? '1' : '0';
            $pdo->prepare("UPDATE mahasiswa SET status_pembayaran = ? WHERE id_mhs = ?")->execute([$val_mhs, $t['id_mhs']]);
        }
        // Jika pendaftar (calon_mhs) bayar pendaftaran, update kolom sudah_bayar
        if ($t && $t['id_calon'] && $t['jenis'] === 'Pendaftaran') {
            $val_pmb = ($status_lunas === 'lunas') ? 1 : 0;
            $pdo->prepare("UPDATE calon_mhs SET sudah_bayar = ? WHERE id_calon = ?")->execute([$val_pmb, $t['id_calon']]);
        }
    }
}

// 2. Handle Add Manual Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah_tagihan') {
    csrf_validate();
    $target_type = $_POST['target_type']; // 'mhs' atau 'calon'
    $target_id = intval($_POST['target_id']);
    $jenis = $_POST['jenis_manual'] ?: $_POST['jenis_select'];
    $nominal = floatval(str_replace(['.', ','], ['', '.'], $_POST['nominal']));
    $kode_tahun = $_POST['kode_tahun'];

    if ($target_id && $jenis && $nominal > 0) {
        $sql = "INSERT INTO tagihan (id_mhs, id_calon, kode_tahun, jenis, nominal, status_lunas) VALUES (?, ?, ?, ?, ?, 'belum')";
        $stmt = $pdo->prepare($sql);
        if ($target_type === 'mhs') {
            $stmt->execute([$target_id, null, $kode_tahun, $jenis, $nominal]);
        } else {
            $stmt->execute([null, $target_id, $kode_tahun, $jenis, $nominal]);
        }
        $msg = "Tagihan baru berhasil ditambahkan.";
        $status = "success";
    } else {
        $msg = "Gagal: Data tidak lengkap.";
        $status = "danger";
    }
}

// 3. Handle Generate Massal SPP (Keep as legacy but user prefers manual)
if (isset($_POST['generate_tagihan_massal'])) {
    csrf_validate();
    $tahun_aktif = $pdo->query("SELECT kode_tahun FROM tahun_akademik WHERE status_aktif=1 LIMIT 1")->fetchColumn();
    if ($tahun_aktif) {
        $mhs = $pdo->query("SELECT id_mhs FROM mahasiswa WHERE status_kuliah='Aktif'")->fetchAll(PDO::FETCH_COLUMN);
        $count = 0;
        foreach ($mhs as $id) {
            $cek = $pdo->prepare("SELECT id_tagihan FROM tagihan WHERE id_mhs=? AND kode_tahun=? AND jenis='SPP'");
            $cek->execute([$id, $tahun_aktif]);
            if (!$cek->fetch()) {
                $pdo->prepare("INSERT INTO tagihan (id_mhs, kode_tahun, jenis, nominal) VALUES (?, ?, 'SPP', 2500000)")->execute([$id, $tahun_aktif]);
                $count++;
            }
        }
        $msg = "Berhasil generate $count tagihan SPP massal.";
        $status = "success";
    }
}

// 4. Fetch All Transactions
$sql_transactions = "
    SELECT t.*, 
           m.nama as nama_mhs, m.nim,
           c.nama as nama_calon, c.id_calon as id_reg
    FROM tagihan t
    LEFT JOIN mahasiswa m ON t.id_mhs = m.id_mhs
    LEFT JOIN calon_mhs c ON t.id_calon = c.id_calon
    ORDER BY t.id_tagihan DESC
";
$transactions = $pdo->query($sql_transactions)->fetchAll();

// Data for Modal selects
$mhs_list = $pdo->query("SELECT id_mhs, nim, nama FROM mahasiswa ORDER BY nama")->fetchAll();
$calon_list = $pdo->query("SELECT id_calon, nama FROM calon_mhs ORDER BY nama")->fetchAll();
$tahun_list = $pdo->query("SELECT kode_tahun FROM tahun_akademik ORDER BY kode_tahun DESC")->fetchAll();
$tahun_aktif = $pdo->query("SELECT kode_tahun FROM tahun_akademik WHERE status_aktif=1 LIMIT 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Keuangan - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm px-3">
    <a class="navbar-brand fw-bold text-primary" href="dashboard.php"><i class="bi bi-wallet2 me-2"></i>Keuangan STIS</a>
</nav>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Daftar Transaksi Tagihan</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahTagihan">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Tagihan
                    </button>
                    <form method="post" onsubmit="return confirm('Generate SPP Semester Aktif?')">
                        <?= csrf_input() ?>
                        <button type="submit" name="generate_tagihan_massal" class="btn btn-outline-warning shadow-sm fw-bold">
                            <i class="bi bi-lightning-fill"></i> Generate SPP Massal
                        </button>
                    </form>
                </div>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?= $status ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>Pembayar</th>
                                    <th>Jenis Tagihan</th>
                                    <th>Nominal</th>
                                    <th>Tahun/Sem</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transactions as $t): ?>
                                <tr>
                                    <td><small class="text-muted">#<?= $t['id_tagihan'] ?></small></td>
                                    <td>
                                        <?php if($t['id_mhs']): ?>
                                            <div class="fw-bold text-primary"><?= htmlspecialchars($t['nama_mhs']) ?></div>
                                            <small class="text-muted">NIM: <?= $t['nim'] ?> (Mahasiswa)</small>
                                        <?php else: ?>
                                            <div class="fw-bold text-success"><?= htmlspecialchars($t['nama_calon'] ?? 'Unknown Applicant') ?></div>
                                            <small class="text-muted">ID: CM-<?= str_pad($t['id_calon'], 4, '0', STR_PAD_LEFT) ?> (Calon)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($t['jenis']) ?></span></td>
                                    <td class="fw-bold">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></td>
                                    <td><?= $t['kode_tahun'] ?></td>
                                    <td>
                                        <?php if($t['status_lunas'] === 'lunas'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">Lunas</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-3 py-2 rounded-pill">Belum Bayar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex gap-1 align-items-center">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="aksi" value="update_status">
                                            <input type="hidden" name="id_tagihan" value="<?= $t['id_tagihan'] ?>">
                                            <select name="status_lunas" class="form-select form-select-sm" style="width: 110px;">
                                                <option value="belum" <?= $t['status_lunas']=='belum'?'selected':'' ?>>Belum</option>
                                                <option value="lunas" <?= $t['status_lunas']=='lunas'?'selected':'' ?>>Lunas</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary py-1"><i class="bi bi-save"></i></button>
                                        </form>
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

<!-- Modal Tambah Tagihan -->
<div class="modal fade" id="modalTambahTagihan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_input() ?>
            <input type="hidden" name="aksi" value="tambah_tagihan">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Tagihan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Tipe Target</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="target_type" id="typeMhs" value="mhs" checked>
                            <label class="btn btn-outline-primary" for="typeMhs">Mahasiswa</label>
                            
                            <input type="radio" class="btn-check" name="target_type" id="typeCalon" value="calon">
                            <label class="btn btn-outline-primary" for="typeCalon">Calon Mhs</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pilih Nama / Target</label>
                        <select name="target_id" id="selectTarget" class="form-select" required>
                            <!-- Updated by JS -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Jenis Tagihan</label>
                        <div class="input-group">
                            <select name="jenis_select" class="form-select" id="jenis_select">
                                <option value="SPP">SPP</option>
                                <option value="Pendaftaran">Pendaftaran</option>
                                <option value="Bangunan">Uang Bangunan</option>
                                <option value="Jas Almamater">Jas Almamater</option>
                                <option value="">- Lainnya (Ketik Manual) -</option>
                            </select>
                            <input type="text" name="jenis_manual" class="form-control" id="jenis_manual" placeholder="Ketik jenis tagihan..." style="display:none;">
                        </div>
                        <small class="text-muted" id="btnTukarInput" style="cursor:pointer; text-decoration:underline;">atau ketik manual</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tahun Akademik</label>
                        <select name="kode_tahun" class="form-select" required>
                            <?php foreach($tahun_list as $th): ?>
                                <option value="<?= $th['kode_tahun'] ?>" <?= $th['kode_tahun'] == $tahun_aktif ? 'selected' : '' ?>><?= $th['kode_tahun'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nominal (Rupiah)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="nominal" id="nominal_mask" class="form-control fw-bold" placeholder="0" required>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Buat Tagihan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.datatable').DataTable({
        language: { search: "Cari Transaksi:", lengthMenu: "Tampil _MENU_ data" },
        order: [[0, "desc"]]
    });

    // Toggle Jenis Tagihan
    $('#btnTukarInput').click(function(){
        if($('#jenis_select').is(':visible')){
            $('#jenis_select').hide().val('');
            $('#jenis_manual').show().attr('required', true);
            $(this).text('kembali ke daftar');
        } else {
            $('#jenis_select').show();
            $('#jenis_manual').hide().val('').removeAttr('required');
            $(this).text('atau ketik manual');
        }
    });

    // Update Target List
    const mhs_data = <?= json_encode($mhs_list) ?>;
    const calon_data = <?= json_encode($calon_list) ?>;

    function updateTargets() {
        const type = $('input[name="target_type"]:checked').val();
        let html = '<option value="">-- Pilih --</option>';
        if (type === 'mhs') {
            mhs_data.forEach(x => { html += `<option value="${x.id_mhs}">${x.nim} - ${x.nama}</option>`; });
        } else {
            calon_data.forEach(x => { html += `<option value="${x.id_calon}">CM-${x.id_calon} - ${x.nama}</option>`; });
        }
        $('#selectTarget').html(html);
    }

    $('input[name="target_type"]').change(updateTargets);
    updateTargets();

    // Mask Nomimal
    $('#nominal_mask').on('input', function(){
        let v = $(this).val().replace(/\D/g, "");
        if(v) {
            $(this).val(new Intl.NumberFormat('id-ID').format(v));
        }
    });

    $('form').submit(function(){
        // Remove masking before submit
        let n = $('#nominal_mask').val().replace(/\./g, "");
        $('#nominal_mask').val(n);
    });
});
</script>
</body>
</html>
