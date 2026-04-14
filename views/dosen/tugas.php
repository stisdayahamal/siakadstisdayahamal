<?php
// views/dosen/tugas.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'dosen') { header("Location: dashboard.php"); exit; }

$id_dosen = $_SESSION['user']['id_dosen'] ?? $_SESSION['user']['id'];
$sukses = $error = '';

// Handling action dari Form Tambah Tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tugas'])) {
    $id_jadwal = $_POST['id_jadwal'];
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $batas_waktu = $_POST['batas_waktu'];
    
    // Validasi id_jadwal milik dosen
    $cek = $pdo->prepare("SELECT COUNT(*) FROM jadwal_kuliah WHERE id_jadwal=? AND id_dosen=?");
    $cek->execute([$id_jadwal, $id_dosen]);
    if ($cek->fetchColumn() > 0) {
        $lampiran = null;
        if (!empty($_FILES['lampiran']['name'])) {
            $dir = '../../public/uploads/tugas/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
            $lampiran = 'soal_' . time() . '_' . rand(10,99) . '.' . $ext;
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $dir . $lampiran);
        }
        $pdo->prepare("INSERT INTO tugas_akademik (id_jadwal, judul, deskripsi, lampiran, batas_waktu) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id_jadwal, $judul, $deskripsi, $lampiran, $batas_waktu]);
        $sukses = "Tugas kelas berhasil diterbitkan!";
    } else {
        $error = "Akses jadwal ditolak.";
    }
}

// Handling action hapus tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_tugas'])) {
    $id_tugas = $_POST['id_tugas'];
    $file_lama = $pdo->query("SELECT lampiran FROM tugas_akademik WHERE id_tugas = $id_tugas")->fetchColumn();
    if ($file_lama && file_exists("../../public/uploads/tugas/" . $file_lama)) {
        @unlink("../../public/uploads/tugas/" . $file_lama);
    }
    $pdo->prepare("DELETE FROM tugas_akademik WHERE id_tugas = ?")->execute([$id_tugas]);
    $sukses = "Tugas kelas dihapus seutuhnya.";
}

// Handling action simpan nilai (pemeriksaan tugas mahasiswa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $id_kumpul = $_POST['id_kumpul'];
    $nilai = $_POST['nilai'];
    $catatan = trim($_POST['catatan_dosen']);
    $pdo->prepare("UPDATE tugas_kumpul SET nilai=?, catatan_dosen=? WHERE id_kumpul=?")->execute([$nilai, $catatan, $id_kumpul]);
    $sukses = "Nilai mahasiswa berhasil disimpan.";
}

// Ambil data Mata Kuliah yang diampu dosen
$stmt_mk = $pdo->prepare("SELECT jk.id_jadwal, mk.nama_mk, mk.semester FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_dosen = ?");
$stmt_mk->execute([$id_dosen]);
$list_jadwal = $stmt_mk->fetchAll();

// Ambil parameter filter (default all kls)
$filter_jadwal = $_GET['id_jadwal'] ?? '';

// Ambil list tugas yang pernah dibuat dosen (opsional filter jadwal)
$query_tugas = "SELECT t.*, mk.nama_mk, mk.semester,
                (SELECT COUNT(*) FROM tugas_kumpul k WHERE k.id_tugas = t.id_tugas) as total_kumpul
                FROM tugas_akademik t 
                JOIN jadwal_kuliah jk ON t.id_jadwal = jk.id_jadwal 
                JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk 
                WHERE jk.id_dosen = ?";
$params_tugas = [$id_dosen];
if ($filter_jadwal) {
    $query_tugas .= " AND jk.id_jadwal = ?";
    $params_tugas[] = $filter_jadwal;
}
$query_tugas .= " ORDER BY t.batas_waktu DESC";

$stmt_tugas = $pdo->prepare($query_tugas);
$stmt_tugas->execute($params_tugas);
$tugas_list = $stmt_tugas->fetchAll();

// View Pengumpulan Mahasiswa jika id_tugas diset
$view_tugas = $_GET['view'] ?? 0;
$kumpul_list = [];
if ($view_tugas) {
    $stmt_kumpul = $pdo->prepare("SELECT k.*, m.nama, m.nim FROM tugas_kumpul k JOIN mahasiswa m ON k.id_mhs = m.id_mhs WHERE k.id_tugas = ? ORDER BY k.waktu_kumpul DESC");
    $stmt_kumpul->execute([$view_tugas]);
    $kumpul_list = $stmt_kumpul->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manajemen Penugasan - SIAKAD Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body{background:#f8f9fa}</style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
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

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Manajemen Penugasan Kelas</h2>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahTugas">
                    <i class="bi bi-plus-circle me-1"></i> Buat Tugas Baru
                </button>
            </div>

            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Berhasil',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-list-task text-primary me-2"></i>Daftar Modul Tugas Dirilis</h5>
                    <form method="get" class="d-flex w-25">
                        <select name="id_jadwal" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas Mata Kuliah --</option>
                            <?php foreach($list_jadwal as $j): ?>
                            <option value="<?= $j['id_jadwal'] ?>" <?= $filter_jadwal == $j['id_jadwal'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['nama_mk']) ?> (SMT <?= $j['semester'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <?php if(count($tugas_list) > 0): ?>
                            <?php foreach($tugas_list as $t): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border shadow-sm border-start border-4 border-success">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h5 class="fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($t['judul']) ?>"><?= htmlspecialchars($t['judul']) ?></h5>
                                            <span class="badge bg-light text-dark border"><?= date('d/m/y', strtotime($t['created_at'])) ?></span>
                                        </div>
                                        <div class="mb-2 text-primary fw-bold small"><i class="bi bi-book me-1"></i><?= htmlspecialchars($t['nama_mk']) ?></div>
                                        <p class="text-muted small mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars(strip_tags($t['deskripsi'])) ?></p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i>Deadline: <?= date('d M Y H:i', strtotime($t['batas_waktu'])) ?></small>
                                            <div class="d-flex gap-2">
                                                <a href="?view=<?= $t['id_tugas'] ?>" class="btn btn-sm btn-outline-primary fw-bold border-0"><i class="bi bi-cloud-download me-1"></i>Terkumpul: <?= $t['total_kumpul'] ?></a>
                                                <form method="post" onsubmit="return confirm('Hapus seluruh instruksi dan jawaban mahasiswa?')">
                                                    <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                                                    <button type="submit" name="hapus_tugas" class="btn btn-sm text-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="bi bi-emoji-neutral fs-1 d-block mb-2"></i> Belum ada instrumen tugas yang diberikan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if($view_tugas): ?>
            <!-- Laman Koreksi Tugas -->
            <div class="card border-0 shadow-sm rounded-4 border-top border-4 border-primary">
                <div class="card-header bg-white pt-4 pb-2 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-check2-square me-2"></i>Panel Koreksi & Penilaian Berkas</h5>
                        <a href="tugas.php" class="btn btn-sm btn-light border"><i class="bi bi-x-lg"></i> Tutup Form</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">NIM</th><th>Nama Mahasiswa</th><th>Waktu Kirim</th><th>Berkas / File</th><th>Nilai & Catatan</th></tr></thead>
                        <tbody>
                            <?php if(count($kumpul_list) > 0): ?>
                                <?php foreach($kumpul_list as $k): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($k['nim']) ?></td>
                                    <td><?= htmlspecialchars($k['nama']) ?></td>
                                    <td><small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($k['waktu_kumpul'])) ?></small></td>
                                    <td>
                                        <a href="../../public/uploads/tugas/<?= $k['file_jawaban'] ?>" target="_blank" class="btn btn-sm btn-secondary fw-bold rounded-pill"><i class="bi bi-file-earmark-pdf me-1"></i>Buka File</a>
                                    </td>
                                    <td width="30%">
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="id_kumpul" value="<?= $k['id_kumpul'] ?>">
                                            <input type="number" name="nilai" class="form-control form-control-sm" placeholder="Skor (0-100)" min="0" max="100" value="<?= $k['nilai'] ?>" style="width:100px;">
                                            <input type="text" name="catatan_dosen" class="form-control form-control-sm" placeholder="Komentar Dosen (Opsional)" value="<?= htmlspecialchars($k['catatan_dosen'] ?? '') ?>">
                                            <button type="submit" name="simpan_nilai" class="btn btn-sm btn-success"><i class="bi bi-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Batas rilis tugas belum disusul pengumpulan dari mahasiswa.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- Modal Tambah Tugas -->
<div class="modal fade" id="modalTambahTugas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Rilis Tugas Akademik</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                            <label class="form-label fw-bold">Pilih Kelas / Mata Kuliah</label>
                            <select name="id_jadwal" class="form-select" required>
                                <option value="" disabled selected>-- Tentukan target mahasiswa --</option>
                                <?php foreach($list_jadwal as $j): ?>
                                <option value="<?= $j['id_jadwal'] ?>"><?= htmlspecialchars($j['nama_mk']) ?> (SMT <?= $j['semester'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-2">
                            <label class="form-label fw-bold">Judul/Topik Penugasan</label>
                            <input type="text" name="judul" class="form-control" placeholder="Cth. Resume Materi Bab 1" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-bold text-danger">Batas Waktu / Deadline</label>
                            <input type="datetime-local" name="batas_waktu" class="form-control text-danger fw-bold" required>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label fw-bold">Deskripsi & Petunjuk Pengerjaan</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Tulis rincian cara pengerjaan tugas oleh mahasiswa di sini..." required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Modul File/Referensi Pendukung (Opsional)</label>
                            <input type="file" name="lampiran" class="form-control">
                            <small class="text-muted">Akan diunduh oleh mahasiswa. Dapat berupa PDF referensi tugas.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_tugas" class="btn btn-primary px-4 fw-bold">Distribusikan Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
