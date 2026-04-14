<?php
// views/mahasiswa/tugas.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
if ($_SESSION['user']['role'] !== 'mahasiswa') { header("Location: dashboard.php"); exit; }

$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
$sukses = $error = '';

// Proses Unggah Berkas Jawaban/Tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unggah_tugas'])) {
    $id_tugas = $_POST['id_tugas'];
    
    // Validasi Tenggat Waktu & Tugas
    $cek_tugas = $pdo->prepare("SELECT batas_waktu FROM tugas_akademik WHERE id_tugas = ?");
    $cek_tugas->execute([$id_tugas]);
    $tugas = $cek_tugas->fetch();
    
    if($tugas && strtotime($tugas['batas_waktu']) >= time()) {
        if (!empty($_FILES['file_jawaban']['name'])) {
            $dir = '../../public/uploads/tugas/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $ext = strtolower(pathinfo($_FILES['file_jawaban']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['pdf', 'doc', 'docx', 'zip'])) {
                $filename = 'jawaban_' . $id_mhs . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($_FILES['file_jawaban']['tmp_name'], $dir . $filename)) {
                    // Cek apakah sudah pernah kumpul
                    $cek_kumpul = $pdo->prepare("SELECT id_kumpul, file_jawaban FROM tugas_kumpul WHERE id_tugas=? AND id_mhs=?");
                    $cek_kumpul->execute([$id_tugas, $id_mhs]);
                    $sudah = $cek_kumpul->fetch();
                    
                    if($sudah) {
                        // Tumpuk (Ganti) File jika belum dinilai
                        @unlink($dir . $sudah['file_jawaban']);
                        $pdo->prepare("UPDATE tugas_kumpul SET file_jawaban=?, waktu_kumpul=NOW() WHERE id_kumpul=?")
                            ->execute([$filename, $sudah['id_kumpul']]);
                        $sukses = "Berkas tugas berhasil diperbarui (Re-upload).";
                    } else {
                        // Insert Baru
                        $pdo->prepare("INSERT INTO tugas_kumpul (id_tugas, id_mhs, file_jawaban, waktu_kumpul) VALUES (?, ?, ?, NOW())")
                            ->execute([$id_tugas, $id_mhs, $filename]);
                        $sukses = "Berkas tugas berhasil diserahkan ke Dosen!";
                    }
                } else {
                    $error = "Terjadi kegagalan memindahkan file upload.";
                }
            } else {
                $error = "Hanya izinkan ekstensi: PDF, DOCX, atau ZIP.";
            }
        } else {
            $error = "Tidak ada file yang dilampirkan.";
        }
    } else {
        $error = "Penyerahan ditolak. Waktu/Deadline tugas ini sudah berakhir.";
    }
}

// Ambil daftar Tugas untuk Mahasiswa (berdasarkan kelas yang disetujui di KRS)
$sql = "SELECT t.*, mk.nama_mk, d.nama AS nama_dosen, kumpul.waktu_kumpul, kumpul.nilai, kumpul.catatan_dosen, kumpul.file_jawaban
        FROM tugas_akademik t
        JOIN jadwal_kuliah jk ON t.id_jadwal = jk.id_jadwal
        JOIN krs k ON k.id_jadwal = jk.id_jadwal
        JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
        JOIN dosen d ON jk.id_dosen = d.id_dosen
        LEFT JOIN tugas_kumpul kumpul ON t.id_tugas = kumpul.id_tugas AND kumpul.id_mhs = ?
        WHERE k.id_mhs = ? AND k.status_krs = 'setuju'
        ORDER BY t.batas_waktu ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_mhs, $id_mhs]);
$tugas_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>E-Learning Tugas - <?= htmlspecialchars($sys['nama_kampus']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body{background:#f4f6f9}
        .file-upload-box { border: 2px dashed #0d6efd; border-radius: 10px; padding: 20px; text-align: center; background: #e9f2ff; transition: 0.3s; }
        .file-upload-box:hover { background: #d3e5ff; border-color: #0b5ed7; }
    </style>
    <!-- Global UI Perfection -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
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
        
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <h2 class="fw-bold mb-4 text-dark"><i class="bi bi-journal-album text-primary me-2"></i>E-Learning: Tugas & Materi</h2>
            
            <?php if($sukses): ?><script>Swal.fire({icon:'success',title:'Sukses',text:'<?= addslashes($sukses) ?>'});</script><?php endif; ?>
            <?php if($error): ?><script>Swal.fire({icon:'error',title:'Gagal',text:'<?= addslashes($error) ?>'});</script><?php endif; ?>

            <div class="row g-4">
                <?php if(count($tugas_list) > 0): ?>
                    <?php foreach($tugas_list as $t): 
                        $is_past_deadline = strtotime($t['batas_waktu']) < time();
                        $is_submitted = !empty($t['waktu_kumpul']);
                        $is_graded = isset($t['nilai']) && $t['nilai'] !== null;
                        
                        $card_border = 'border-primary';
                        if($is_graded) $card_border = 'border-success';
                        elseif($is_past_deadline && !$is_submitted) $card_border = 'border-danger';
                        elseif($is_submitted) $card_border = 'border-info';
                    ?>
                    <div class="col-md-6">
                        <div class="card h-100 border-top-0 border-end-0 border-bottom-0 border-start border-4 <?= $card_border ?> shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['judul']) ?></h5>
                                    <?php if($is_graded): ?>
                                        <span class="badge bg-success rounded-pill d-flex align-items-center px-3 fs-6">Skor: <?= $t['nilai'] ?>/100</span>
                                    <?php elseif($is_submitted): ?>
                                        <span class="badge bg-info text-dark rounded-pill d-flex align-items-center px-3">Sudah Dinilai? Tunggu Dosen</span>
                                    <?php elseif($is_past_deadline): ?>
                                        <span class="badge bg-danger rounded-pill d-flex align-items-center px-3">Terlambat (Ditutup)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary rounded-pill d-flex align-items-center px-3">Aktif (Tenggat: <?= date('d M', strtotime($t['batas_waktu'])) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="text-primary fw-bold mb-3"><i class="bi bi-book me-1"></i> <?= htmlspecialchars($t['nama_mk']) ?> &mdash; <span class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($t['nama_dosen']) ?></span></h6>
                                
                                <div class="alert alert-light border small text-dark p-3 mb-3">
                                    <strong><i class="bi bi-info-circle me-1"></i> Instruksi:</strong><br>
                                    <?= nl2br(htmlspecialchars($t['deskripsi'])) ?>
                                </div>

                                <?php if($t['lampiran']): ?>
                                    <a href="../../public/uploads/tugas/<?= $t['lampiran'] ?>" target="_blank" class="btn btn-sm btn-outline-danger mb-3 fw-bold"><i class="bi bi-file-earmark-pdf-fill me-1"></i>Unduh Referensi Soal Dosen</a>
                                <?php endif; ?>

                                <!-- Bagian Pengumpulan -->
                                <?php if(!$is_graded && !$is_past_deadline): ?>
                                    <form method="post" enctype="multipart/form-data" class="mt-2">
                                        <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                                        
                                        <?php if($is_submitted): ?>
                                            <div class="alert alert-info py-2 small d-flex align-items-center">
                                                <i class="bi bi-check-circle-fill fs-4 me-2"></i> 
                                                <div>Berkas terkirim pada <?= date('d M Y H:i', strtotime($t['waktu_kumpul'])) ?>.<br>Dosen belum mengunci nilai. Anda bisa unggah ulang (revisi) di bawah ini.</div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="input-group input-group-sm mb-2">
                                            <input type="file" name="file_jawaban" class="form-control" accept=".pdf,.doc,.docx,.zip" required>
                                            <button type="submit" name="unggah_tugas" class="btn btn-primary fw-bold"><i class="bi bi-cloud-arrow-up-fill me-1"></i>Kirim Jawaban</button>
                                        </div>
                                        <small class="text-danger d-block fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Batas Waktu: <?= date('d M Y, H:i', strtotime($t['batas_waktu'])) ?></small>
                                    </form>
                                <?php endif; ?>

                                <!-- Hasil Penilaian -->
                                <?php if($is_graded): ?>
                                    <div class="alert alert-success border-0 px-3 py-2 mt-2">
                                        <strong><i class="bi bi-award-fill text-warning me-1"></i>Tugas Telah Diperiksa!</strong><br>
                                        <small>File yang dikirim: <a href="../../public/uploads/tugas/<?= $t['file_jawaban'] ?>" target="_blank" class="text-decoration-underline text-success fw-bold">Lihat Berkas Saya</a></small>
                                        <?php if(!empty($t['catatan_dosen'])): ?>
                                            <hr class="my-2 border-success border-opacity-25">
                                            <small class="d-block"><i>"<?= htmlspecialchars($t['catatan_dosen']) ?>"</i> &mdash; Dosen Pengampu</small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif($is_past_deadline && !$is_submitted): ?>
                                    <div class="alert alert-danger px-3 py-2 mt-2">
                                        <strong><i class="bi bi-x-circle-fill me-1"></i>Anda Melewatkan Tugas Ini.</strong><br>
                                        <small>Batas waktu habis pada <?= date('d M Y, H:i', strtotime($t['batas_waktu'])) ?>.</small>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-balloon fs-1 text-muted mb-2 d-block"></i>
                        <h4 class="text-muted mb-0">Hore! Belum ada tugas untukmu saat ini.</h4>
                        <p class="text-secondary small">Pastikan KRS-mu sudah disetujui agar dapat mengecek tugas mata kuliah.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
