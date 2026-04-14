<?php
// views/dosen/presensi.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'dosen') {
    header('Location: dashboard.php');
    exit;
}

$id_dosen = $_SESSION['user']['id_dosen'] ?? $_SESSION['user']['id'];
$id_jadwal = isset($_GET['id_jadwal']) ? intval($_GET['id_jadwal']) : 0;
$pertemuan_ke = isset($_GET['pertemuan']) ? intval($_GET['pertemuan']) : 1;

if ($pertemuan_ke < 1) $pertemuan_ke = 1;
if ($pertemuan_ke > 16) $pertemuan_ke = 16;

$success = $error = '';

// Cek kelas milik dosen
$stmt = $pdo->prepare('SELECT jk.*, mk.nama_mk FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_jadwal = ? AND jk.id_dosen = ?');
$stmt->execute([$id_jadwal, $id_dosen]);
$kelas = $stmt->fetch();
if (!$kelas) {
    header('Location: jadwal_mengajar.php?err=kelas_tidak_ditemukan');
    exit;
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $tgl = $_POST['tanggal'] ?? date('Y-m-d');
    if (!empty($_POST['status'])) {
        $pdo->beginTransaction();
        try {
            foreach ($_POST['status'] as $id_mhs => $status_hadir) {
                // Insert or Update Presensi
                $stmt = $pdo->prepare('INSERT INTO presensi (id_jadwal, id_mhs, pertemuan_ke, status_hadir, tanggal) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status_hadir=?, tanggal=?');
                $stmt->execute([$id_jadwal, $id_mhs, $pertemuan_ke, $status_hadir, $tgl, $status_hadir, $tgl]);
            }
            // Update persentase kehadiran di tabel nilai_akhir untuk setiap mahasiswa
            $id_mhs_list = array_keys($_POST['status']);
            foreach ($id_mhs_list as $im) {
                $jml_hadir = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE id_jadwal=? AND id_mhs=? AND status_hadir IN ('H','S','I')");
                $jml_hadir->execute([$id_jadwal, $im]);
                $hadir = $jml_hadir->fetchColumn();
                $persentase = round(($hadir / 16) * 100, 2);

                $cek_krs = $pdo->prepare("SELECT id_krs FROM krs WHERE id_mhs=? AND id_jadwal=? AND status_krs='setuju'");
                $cek_krs->execute([$im, $id_jadwal]);
                $id_krs = $cek_krs->fetchColumn();
                if ($id_krs) {
                    $pdo->prepare("INSERT INTO nilai_akhir (id_krs, kehadiran) VALUES (?, ?) ON DUPLICATE KEY UPDATE kehadiran=?")->execute([$id_krs, $persentase, $persentase]);
                }
            }
            $pdo->commit();
            $success = "Presensi pertemuan ke-$pertemuan_ke berhasil disimpan.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil mahasiswa yang mengambil kelas ini dan disetujui KRS-nya
$stmt = $pdo->prepare('SELECT mhs.id_mhs, mhs.nim, mhs.nama FROM krs JOIN mahasiswa mhs ON krs.id_mhs = mhs.id_mhs WHERE krs.id_jadwal = ? AND krs.status_krs = "setuju" ORDER BY mhs.nim');
$stmt->execute([$id_jadwal]);
$peserta = $stmt->fetchAll();

// Ambil data presensi untuk pertemuan ini jika sudah ada
$stmt = $pdo->prepare('SELECT id_mhs, status_hadir, tanggal FROM presensi WHERE id_jadwal = ? AND pertemuan_ke = ?');
$stmt->execute([$id_jadwal, $pertemuan_ke]);
$presensi_exist = [];
$tanggal_exist = date('Y-m-d');
foreach ($stmt->fetchAll() as $row) {
    $presensi_exist[$row['id_mhs']] = $row['status_hadir'];
    $tanggal_exist = $row['tanggal'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Presensi Digital - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container pb-5">
    <h2>Presensi Kelas: <?= htmlspecialchars($kelas['nama_mk']) ?></h2>
    
    <!-- Pilihan Pertemuan -->
    <div class="mb-4 mt-3">
        Tampilkan Pertemuan Ke:
        <div class="d-flex flex-wrap gap-1 mt-2">
            <?php for ($i=1; $i<=16; $i++): ?>
                <a href="?id_jadwal=<?= $id_jadwal ?>&pertemuan=<?= $i ?>" class="btn <?= $pertemuan_ke == $i ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white mt-1">
            <h5 class="mb-0">Daftar Kehadiran - Pertemuan <?= $pertemuan_ke ?></h5>
        </div>
        <div class="card-body">
            <form method="post">
                <?= csrf_input() ?>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Tanggal Pertemuan</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal_exist) ?>" required>
                    </div>
                </div>
                
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Sakit</th>
                            <th class="text-center">Izin</th>
                            <th class="text-center">Alpa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peserta as $p): 
                            $st = $presensi_exist[$p['id_mhs']] ?? 'A'; // default Alpa jika belum ada
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nim']) ?></td>
                            <td><?= htmlspecialchars($p['nama']) ?></td>
                            <td class="text-center"><input type="radio" name="status[<?= $p['id_mhs'] ?>]" value="H" <?= $st=='H'?'checked':'' ?>></td>
                            <td class="text-center"><input type="radio" name="status[<?= $p['id_mhs'] ?>]" value="S" <?= $st=='S'?'checked':'' ?>></td>
                            <td class="text-center"><input type="radio" name="status[<?= $p['id_mhs'] ?>]" value="I" <?= $st=='I'?'checked':'' ?>></td>
                            <td class="text-center"><input type="radio" name="status[<?= $p['id_mhs'] ?>]" value="A" <?= $st=='A'?'checked':'' ?>></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-success mt-3 px-4">Simpan Presensi</button>
            </form>
        </div>
    </div>
</div>

    </main>
  </div>
</div>
</body>
</html>
