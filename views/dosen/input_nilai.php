<?php
// views/dosen/input_nilai.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/audit_log.php';

if ($_SESSION['user']['role'] !== 'dosen') {
    header('Location: dashboard.php');
    exit;
}

$id_dosen  = $_SESSION['user']['id_dosen'] ?? $_SESSION['user']['id'];
$id_jadwal = isset($_GET['id_jadwal']) ? intval($_GET['id_jadwal']) : 0;

// 1. Ambil Tahun Akademik Aktif
$ta_aktif = get_tahun_aktif($pdo);
$kode_tahun = $ta_aktif['kode_tahun'] ?? '';

// 2. Cek Batas Penginputan Nilai (Locking Admin)
$stmt_batas = $pdo->prepare("SELECT tanggal_batas FROM pengaturan_nilai WHERE kode_tahun = ?");
$stmt_batas->execute([$kode_tahun]);
$tgl_batas = $stmt_batas->fetchColumn();

// 3. Validasi DIKTI: Minimal 14 pertemuan untuk input nilai final
$stmt_meet = $pdo->prepare("SELECT COUNT(DISTINCT tanggal) FROM presensi WHERE id_jadwal = ?");
$stmt_meet->execute([$id_jadwal]);
$jml_pertemuan = $stmt_meet->fetchColumn();
$min_pertemuan = 14; 
$pertemuan_cukup = ($jml_pertemuan >= $min_pertemuan);

// Tentukan status READONLY
$is_locked_by_date = ($tgl_batas && date('Y-m-d') > $tgl_batas);
$readonly = ($is_locked_by_date || !$pertemuan_cukup) ? 'readonly style="background:#f8f9fa; color:#6c757d"' : '';

// 4. Cek Kepemilikan Kelas
$stmt = $pdo->prepare('SELECT jk.*, mk.nama_mk FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_jadwal = ? AND jk.id_dosen = ?');
$stmt->execute([$id_jadwal, $id_dosen]);
$kelas = $stmt->fetch();

if (!$kelas) {
    header('Location: jadwal_mengajar.php?err=kelas_tidak_saat_ini');
    exit;
}

// 5. Ambil Daftar Mahasiswa & Nilai
$stmt = $pdo->prepare('
    SELECT krs.id_krs, mhs.id_mhs, mhs.nim, mhs.nama, n.kehadiran, n.tugas, n.uts, n.uas 
    FROM krs 
    JOIN mahasiswa mhs ON krs.id_mhs = mhs.id_mhs 
    LEFT JOIN nilai_akhir n ON n.id_krs = krs.id_krs 
    WHERE krs.id_jadwal = ? AND krs.status_krs = "setuju"
');
$stmt->execute([$id_jadwal]);
$peserta = $stmt->fetchAll();

// 6. Handle Simpan Nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked_by_date && $pertemuan_cukup) {
    if (!csrf_validate(false)) {
        $error = "Gagal validasi keamanan (CSRF).";
    } else {
        try {
            $pdo->beginTransaction();
            foreach ($_POST['nilai'] as $id_krs => $v) {
                // Kehadiran diambil otomatis dari persentase presensi (Safety)
                $stmt_p = $pdo->prepare("
                    SELECT 100 * SUM(CASE WHEN status_hadir='H' THEN 1 ELSE 0 END) / COUNT(*) 
                    FROM presensi WHERE id_jadwal = ? AND id_mhs = (SELECT id_mhs FROM krs WHERE id_krs = ?)
                ");
                $stmt_p->execute([$id_jadwal, $id_krs]);
                $presence_pct = round($stmt_p->fetchColumn() ?: 0, 2);

                $tugas = floatval($v['tugas']);
                $uts   = floatval($v['uts']);
                $uas   = isset($v['uas']) ? floatval($v['uas']) : 0;

                // Bobot: 10% Hadir, 20% Tugas, 30% UTS, 40% UAS
                $na = ($presence_pct * 0.1) + ($tugas * 0.2) + ($uts * 0.3) + ($uas * 0.4);

                // Huruf Mutu
                if ($na >= 85) { $huruf = 'A'; $bobot = 4.0; }
                elseif ($na >= 75) { $huruf = 'B'; $bobot = 3.0; }
                elseif ($na >= 65) { $huruf = 'C'; $bobot = 2.0; }
                elseif ($na >= 50) { $huruf = 'D'; $bobot = 1.0; }
                else { $huruf = 'E'; $bobot = 0.0; }

                $stmt_save = $pdo->prepare("
                    INSERT INTO nilai_akhir (id_krs, tugas, uts, uas, kehadiran, nilai_angka, nilai_huruf, bobot_4_0) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE tugas=VALUES(tugas), uts=VALUES(uts), uas=VALUES(uas), kehadiran=VALUES(kehadiran), nilai_angka=VALUES(nilai_angka), nilai_huruf=VALUES(nilai_huruf), bobot_4_0=VALUES(bobot_4_0)
                ");
                $stmt_save->execute([$id_krs, $tugas, $uts, $uas, $presence_pct, $na, $huruf, $bobot]);
            }
            $pdo->commit();
            header("Location: input_nilai.php?id_jadwal=$id_jadwal&sukses=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Input Nilai - <?= htmlspecialchars($kelas['nama_mk']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="ms-auto small fw-medium text-muted">Semester Aktif: <?= $kode_tahun ?></span>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Input Nilai Akhir</h2>
                    <p class="text-secondary"><?= htmlspecialchars($kelas['nama_mk']) ?> (Kelas <?= $kelas['id_jadwal'] ?>)</p>
                </div>
                <a href="jadwal_mengajar.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>

            <?php if (isset($_GET['sukses'])): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-check-circle-fill me-2"></i>Nilai mahasiswa berhasil disimpan dan disinkronkan ke transkrip.</div>
            <?php endif; ?>

            <?php if (!$pertemuan_cukup): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-1 me-3"></i>
                        <div>
                            <h5 class="fw-bold mb-1">Input Nilai Terkunci</h5>
                            <p class="mb-0">Standar akademik mewajibkan minimal <strong>14 pertemuan</strong> sebelum nilai dapat diinput. Saat ini baru tercatat <strong><?= $jml_pertemuan ?> pertemuan</strong> untuk kelas ini.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <form method="post">
                        <?= csrf_input() ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Mahasiswa</th>
                                        <th width="12%">Hadir (%)</th>
                                        <th width="12%">Tugas (20%)</th>
                                        <th width="12%">UTS (30%)</th>
                                        <th width="12%">UAS (40%)</th>
                                        <th width="10%">Nilai Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($peserta)): ?>
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada mahasiswa terdaftar di kelas ini.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($peserta as $p): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?= htmlspecialchars($p['nama']) ?></div>
                                                <div class="small text-muted font-monospace"><?= $p['nim'] ?></div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm bg-light text-center fw-bold" value="<?= $p['kehadiran'] ?? '0' ?>%" readonly>
                                            </td>
                                            <td><input type="number" name="nilai[<?= $p['id_krs'] ?>][tugas]" value="<?= $p['tugas'] ?>" class="form-control form-control-sm" step="0.1" required <?= $readonly ?>></td>
                                            <td><input type="number" name="nilai[<?= $p['id_krs'] ?>][uts]" value="<?= $p['uts'] ?>" class="form-control form-control-sm" step="0.1" required <?= $readonly ?>></td>
                                            <td><input type="number" name="nilai[<?= $p['id_krs'] ?>][uas]" value="<?= $p['uas'] ?>" class="form-control form-control-sm" step="0.1" required <?= $readonly ?>></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary fs-6"><?= $p['nilai_huruf'] ?? '-' ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($peserta)): ?>
                        <div class="p-4 bg-light border-top text-end">
                            <button type="submit" class="btn btn-success px-5 rounded-pill fw-bold shadow" <?= $readonly ?>>
                                <i class="bi bi-save-fill me-2"></i>Simpan Perubahan Nilai
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
