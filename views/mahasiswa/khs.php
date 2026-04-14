<?php
// views/mahasiswa/khs.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: dashboard.php');
    exit;
}
$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
// Ambil semester yang pernah diambil
$stmt_sem = $pdo->prepare("SELECT DISTINCT mk.semester FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk WHERE k.id_mhs=? ORDER BY mk.semester");
$stmt_sem->execute([$id_mhs]);
$semesters = $stmt_sem->fetchAll(PDO::FETCH_COLUMN);
function getKHS($pdo, $id_mhs, $semester) {
    $sql = "SELECT mk.kode_mk, mk.nama_mk, mk.sks, n.nilai_angka, n.nilai_huruf, n.bobot_4_0 FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE k.id_mhs=? AND mk.semester=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_mhs, $semester]);
    return $stmt->fetchAll();
}
function hitungIPS($khs) {
    $total_sks = $total_bobot = 0;
    foreach ($khs as $row) {
        $total_sks += $row['sks'];
        $total_bobot += $row['bobot_4_0'] * $row['sks'];
    }
    return $total_sks ? round($total_bobot / $total_sks, 2) : 0;
}
function hitungIPK($pdo, $id_mhs) {
    $sql = "SELECT mk.sks, n.bobot_4_0 FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE k.id_mhs=? AND n.bobot_4_0 IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_mhs]);
    $total_sks = $total_bobot = 0;
    foreach ($stmt as $row) {
        $total_sks += $row['sks'];
        $total_bobot += $row['bobot_4_0'] * $row['sks'];
    }
    return $total_sks ? round($total_bobot / $total_sks, 2) : 0;
}
$ipk = hitungIPK($pdo, $id_mhs);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>KHS Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
    <h2>Kartu Hasil Studi (KHS)</h2>
    <p><b>IPK:</b> <?= $ipk ?></p>
    <?php foreach ($semesters as $semester): $khs = getKHS($pdo, $id_mhs, $semester); $ips = hitungIPS($khs); ?>
        <h4>Semester <?= $semester ?> (IPS: <?= $ips ?>)</h4>
        <table class="table table-bordered datatable-khs">
            <thead><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai Angka</th><th>Nilai Huruf</th></tr></thead>
            <tbody>
            <?php foreach ($khs as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                    <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                    <td><?= $row['sks'] ?></td>
                    <td><?= $row['nilai_angka'] ?></td>
                    <td><?= $row['nilai_huruf'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
    
    </main>
  </div>
</div>
<script>
    $(document).ready(function() {
        $('.datatable-khs').DataTable({
            dom: 'Bfrtip',
            buttons: ['excelHtml5','pdfHtml5','print']
        });
    });
    </script>
    <a href="cetak_transkrip.php" class="btn btn-danger">Cetak Transkrip PDF</a>
</div>
</body>
</html>
