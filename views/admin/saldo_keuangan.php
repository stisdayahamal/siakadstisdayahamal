<?php
// views/admin/saldo_keuangan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';

if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php"); exit;
}

// Rekapitulasi Global
$total_masuk = $pdo->query("SELECT SUM(nominal) FROM tagihan WHERE status_lunas = 'lunas'")->fetchColumn() ?: 0;
$total_tunggakan = $pdo->query("SELECT SUM(nominal) FROM tagihan WHERE status_lunas = 'belum'")->fetchColumn() ?: 0;

// Total mahasiswa lunas vs belum lunas (SPP Semester ini)
$mhs_lunas = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status_pembayaran = 1")->fetchColumn();
$mhs_tunggak = $pdo->query("SELECT COUNT(*) FROM mahasiswa WHERE status_pembayaran = 0 AND id_prodi IS NOT NULL")->fetchColumn();

// Distribusi Pemasukan Berdasarkan Program Studi (Anggap tagihan punya id_mahasiswa)
$distribusi_prodi = $pdo->query("
    SELECT p.nama_prodi, SUM(t.nominal) as total_bayar
    FROM tagihan t
    JOIN mahasiswa m ON t.id_mhs = m.id_mhs
    JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE t.status_lunas = 'lunas'
    GROUP BY p.id_prodi
    ORDER BY total_bayar DESC
")->fetchAll();

// History Tagihan Terbaru (10 Transaksi Terakhir yang Lunas)
$history_lunas = $pdo->query("
    SELECT t.*, m.nama, m.nim, p.nama_prodi 
    FROM tagihan t 
    JOIN mahasiswa m ON t.id_mhs = m.id_mhs 
    LEFT JOIN prodi p ON m.id_prodi = p.id_prodi
    WHERE t.status_lunas = 'lunas' 
    ORDER BY t.id_tagihan DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Saldo Keuangan Kampus - ERP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h2 class="fw-bold mb-4">Neraca Saldo Rekapitulasi</h2>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary text-white p-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-wallet2 fs-2 me-3 opacity-75"></i>
                            <h5 class="mb-0 fw-bold opacity-75">Total Kas Masuk (Lunas)</h5>
                        </div>
                        <h1 class="display-5 fw-bold mb-0">Rp <?= number_format($total_masuk, 0, ',', '.') ?></h1>
                        <p class="mt-auto mb-0 pt-3 opacity-75"><i class="bi bi-person-check-fill me-1"></i> Dari total <?= $mhs_lunas ?> mahasiswa lunas</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-danger text-white p-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-exclamation-triangle fs-2 me-3 opacity-75"></i>
                            <h5 class="mb-0 fw-bold opacity-75">Potensi Tunggakan Aktif</h5>
                        </div>
                        <h1 class="display-5 fw-bold mb-0">Rp <?= number_format($total_tunggakan, 0, ',', '.') ?></h1>
                        <p class="mt-auto mb-0 pt-3 opacity-75"><i class="bi bi-person-exclamation me-1"></i> Tersebar pada <?= $mhs_tunggak ?> mahasiswa tertunggak</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Grafik Distribusi Prodi -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <h5 class="fw-bold"><i class="bi bi-pie-chart-fill text-info me-2"></i>Kontribusi per Program Studi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="keuanganProdiChart" style="max-height: 280px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- 10 Transaksi Terakhir -->
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="bi bi-receipt text-success me-2"></i>Histori SPP Masuk Terbaru</h5>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Ekspor XLS</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No. Ref</th>
                                            <th>Mahasiswa</th>
                                            <th>Semester</th>
                                            <th>Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($history_lunas as $h): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small fw-bold">#INV-<?= str_pad($h['id_tagihan'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td>
                                                <strong class="d-block text-primary"><?= htmlspecialchars($h['nama']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($h['nim']) ?> • <?= htmlspecialchars($h['nama_prodi']) ?></small>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($h['kode_tahun']) ?></span></td>
                                            <td class="text-success fw-bold">Rp <?= number_format($h['nominal'], 0, ',', '.') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(count($history_lunas) === 0): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada transaksi sukses.</td></tr><?php endif; ?>
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
<script>
    // Inisialisasi Chart Kontribusi Prodi
    const prodiData = <?= json_encode($distribusi_prodi) ?>;
    const labels = prodiData.map(d => d.nama_prodi);
    const amounts = prodiData.map(d => d.total_bayar);

    new Chart(document.getElementById('keuanganProdiChart'), {
        type: 'doughnut',
        data: {
            labels: labels.length > 0 ? labels : ['Belum Ada Data'],
            datasets: [{
                data: amounts.length > 0 ? amounts : [1],
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6f42c1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            },
            cutout: '70%'
        }
    });
</script>
</body>
</html>
