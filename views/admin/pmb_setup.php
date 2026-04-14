<?php
// views/admin/pmb_setup.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

$sukses = $_SESSION['sukses'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['sukses'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. PERIODE
    if (isset($_POST['tambah_periode'])) {
        $pdo->prepare("INSERT INTO pmb_periode (nama_periode, tahun_ajaran, status_aktif) VALUES (?, ?, ?)")
            ->execute([$_POST['nama_periode'], $_POST['tahun_ajaran'], $_POST['status_aktif']]);
        $_SESSION['sukses'] = "Periode PMB berhasil ditambah.";
        header("Location: pmb_setup.php"); exit;
    }
    
    // 2. JALUR
    if (isset($_POST['tambah_jalur'])) {
        $pdo->prepare("INSERT INTO pmb_jalur (nama_jalur) VALUES (?)")->execute([$_POST['nama_jalur']]);
        $_SESSION['sukses'] = "Jalur pendaftaran PMB ditambah.";
        header("Location: pmb_setup.php"); exit;
    }

    // 3. GELOMBANG
    if (isset($_POST['tambah_gelombang'])) {
        $pdo->prepare("INSERT INTO pmb_gelombang (id_periode, nama_gelombang, tgl_mulai, tgl_selesai, biaya) VALUES (?, ?, ?, ?, ?)")
            ->execute([$_POST['id_periode'], $_POST['nama_gelombang'], $_POST['tgl_mulai'], $_POST['tgl_selesai'], $_POST['biaya']]);
        $_SESSION['sukses'] = "Gelombang pendaftaran ditambahkan.";
        header("Location: pmb_setup.php"); exit;
    }

    // Hapus Generik
    if (isset($_POST['hapus'])) {
        $tabel = $_POST['table'];
        $pk = $_POST['pk'];
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM $tabel WHERE $pk = ?")->execute([$id]);
        $_SESSION['sukses'] = "Data berhasil dihapus.";
        header("Location: pmb_setup.php"); exit;
    }
}

$periode = $pdo->query("SELECT * FROM pmb_periode ORDER BY id_periode DESC")->fetchAll();
$jalur = $pdo->query("SELECT * FROM pmb_jalur ORDER BY id_jalur DESC")->fetchAll();
$gelombang = $pdo->query("SELECT g.*, p.nama_periode FROM pmb_gelombang g JOIN pmb_periode p ON g.id_periode = p.id_periode ORDER BY g.id_gelombang DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Setup PMB - ERP Admin</title>
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
            <h2 class="fw-bold mb-4">Master Konfigurasi PMB</h2>
            
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="row g-4 mb-4">
                <!-- Data Periode -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between">
                            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-calendar3 me-2"></i>Periode Penerimaan</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="row g-2 mb-3">
                                <div class="col-8">
                                    <input type="text" name="nama_periode" class="form-control" placeholder="Cth: PMB Ganjil" required>
                                </div>
                                <div class="col-4">
                                    <input type="text" name="tahun_ajaran" class="form-control" placeholder="Tahun" required>
                                </div>
                                <div class="col-8">
                                    <select name="status_aktif" class="form-select">
                                        <option value="1">Aktif dibuka</option><option value="0">Tutup</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <button type="submit" name="tambah_periode" class="btn btn-primary w-100 fw-bold">Tambah</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <tbody>
                                        <?php foreach($periode as $p): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($p['nama_periode']) ?> (<?= htmlspecialchars($p['tahun_ajaran']) ?>)</td>
                                            <td><?= $p['status_aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Tutup</span>' ?></td>
                                            <td class="text-end">
                                                <form method="post" onsubmit="return confirm('Hapus periode ini?');">
                                                    <input type="hidden" name="table" value="pmb_periode"><input type="hidden" name="pk" value="id_periode"><input type="hidden" name="id" value="<?= $p['id_periode'] ?>">
                                                    <button type="submit" name="hapus" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(count($periode)===0): ?><tr><td colspan="3" class="text-center text-muted">Data kosong</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jalur Penerimaan -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2">
                            <h5 class="fw-bold mb-0 text-warning"><i class="bi bi-signpost-split me-2"></i>Jalur Pendaftaran</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-3">
                                <input type="text" name="nama_jalur" class="form-control" placeholder="Hafidz / Prestasi / Reguler" required>
                                <button type="submit" name="tambah_jalur" class="btn btn-warning text-dark fw-bold">Tambah</button>
                            </form>
                            <ul class="list-group list-group-flush border-top">
                                <?php foreach($jalur as $j): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2">
                                    <span class="fw-bold"><?= htmlspecialchars($j['nama_jalur']) ?></span>
                                    <form method="post" onsubmit="return confirm('Hapus jalur?');">
                                        <input type="hidden" name="table" value="pmb_jalur"><input type="hidden" name="pk" value="id_jalur"><input type="hidden" name="id" value="<?= $j['id_jalur'] ?>">
                                        <button type="submit" name="hapus" class="btn btn-sm text-danger border-0"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gelombang Pendaftaran -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-success"><i class="bi bi-water me-2"></i>Pengaturan Gelombang & Biaya</h5>
                    <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalGelombang"><i class="bi bi-plus me-1"></i>Buat Gelombang</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Periode</th>
                                    <th>Gelombang</th>
                                    <th>Tanggal Berlaku</th>
                                    <th>Biaya Formulir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($gelombang as $g): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($g['nama_periode']) ?></td>
                                    <td class="fw-bold text-success"><?= htmlspecialchars($g['nama_gelombang']) ?></td>
                                    <td><?= date('d M', strtotime($g['tgl_mulai'])) ?> - <?= date('d M Y', strtotime($g['tgl_selesai'])) ?></td>
                                    <td><span class="badge bg-secondary rounded-pill">Rp <?= number_format($g['biaya'], 0, ',', '.') ?></span></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus data gelombang ini?');">
                                            <input type="hidden" name="table" value="pmb_gelombang"><input type="hidden" name="pk" value="id_gelombang"><input type="hidden" name="id" value="<?= $g['id_gelombang'] ?>">
                                            <button type="submit" name="hapus" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($gelombang)===0): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada setup gelombang pendaftaran.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal Gelombang -->
<div class="modal fade" id="modalGelombang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold">Tambah Gelombang</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Periode</label>
                    <select name="id_periode" class="form-select" required>
                        <?php foreach($periode as $p): ?>
                            <option value="<?= $p['id_periode'] ?>"><?= htmlspecialchars($p['nama_periode']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Gelombang</label>
                    <input type="text" name="nama_gelombang" class="form-control" placeholder="Cth: Gelombang 1" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Biaya Pendaftaran (Rp)</label>
                    <input type="number" name="biaya" class="form-control" placeholder="150000" required>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_gelombang" class="btn btn-success fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
