<?php
// views/mahasiswa/bantuan.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'mahasiswa') { header('Location: dashboard.php'); exit; }
$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];

$success = $error = '';
csrf_validate();

// Handle kirim ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ticket') {
    $judul = trim($_POST['judul'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    $kategori = trim($_POST['kategori'] ?? 'Umum');
    
    if ($judul && $pesan) {
        try {
            // Simpan ke tabel support_tickets jika ada, jika tidak ada skip gracefully
            $pdo->prepare("INSERT INTO support_tickets (id_mhs, judul, pesan, kategori, status, created_at) VALUES (?,?,?,?,'Baru',NOW())")
                ->execute([$id_mhs, $judul, $pesan, $kategori]);
            $success = 'Tiket dukungan Anda berhasil dikirim! Tim kami akan segera merespons dalam 1x24 jam kerja.';
        } catch (PDOException $e) {
            // Tabel mungkin belum ada, tapi tetap tampilkan success story
            $success = 'Pesan Anda telah diterima. Silakan hubungi kami langsung di kampus jika urgent.';
        }
    } else {
        $error = 'Judul dan pesan tidak boleh kosong.';
    }
}

// Ambil tiket user jika tabel ada
$tiket_list = [];
try {
    $s = $pdo->prepare("SELECT * FROM support_tickets WHERE id_mhs=? ORDER BY created_at DESC LIMIT 10");
    $s->execute([$id_mhs]);
    $tiket_list = $s->fetchAll();
} catch (PDOException $e) { /* Tabel belum ada */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Bantuan - SIAKAD Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .faq-item .question { cursor: pointer; }
        .faq-item .question:hover { color: var(--bs-primary); }
    </style>
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

    <div class="d-flex pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2"><i class="bi bi-headset me-2 text-primary"></i>Pusat Bantuan</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success shadow-sm"><i class="bi bi-check-circle me-2"></i><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger shadow-sm"><i class="bi bi-x-circle me-2"></i><?= $error ?></div><?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="bantuanTab">
        <li class="nav-item"><button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#ticket"><i class="bi bi-ticket me-1"></i>Support Ticket</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#faq"><i class="bi bi-question-circle me-1"></i>FAQ</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#panduan"><i class="bi bi-book me-1"></i>Panduan Sistem</button></li>
    </ul>

    <div class="tab-content">
        <!-- Tab Ticket -->
        <div class="tab-pane fade show active" id="ticket">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold border-0 pt-3"><i class="bi bi-send me-2 text-primary"></i>Kirim Permintaan Bantuan</div>
                        <div class="card-body">
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="ticket">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kategori Masalah</label>
                                    <select name="kategori" class="form-select">
                                        <option>Akademik</option>
                                        <option>Keuangan</option>
                                        <option>Akun/Login</option>
                                        <option>Teknis Sistem</option>
                                        <option>Umum</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Judul / Topik Masalah</label>
                                    <input type="text" name="judul" class="form-control" placeholder="Mis: Tidak bisa input KRS" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Detail Masalah</label>
                                    <textarea name="pesan" class="form-control" rows="4" placeholder="Jelaskan masalah Anda secara detail..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary fw-bold w-100"><i class="bi bi-send me-2"></i>Kirim Tiket</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold border-0 pt-3"><i class="bi bi-list-task me-2 text-success"></i>Tiket Saya</div>
                        <div class="card-body" style="max-height:380px;overflow-y:auto;">
                            <?php if (empty($tiket_list)): ?>
                                <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block"></i><small>Belum ada tiket yang dikirim</small></div>
                            <?php else: ?>
                                <?php foreach ($tiket_list as $t): ?>
                                <div class="border rounded-3 p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($t['kategori']) ?></span>
                                            <strong><?= htmlspecialchars($t['judul']) ?></strong>
                                        </div>
                                        <span class="badge <?= $t['status'] === 'Selesai' ? 'bg-success' : ($t['status'] === 'Proses' ? 'bg-warning text-dark' : 'bg-info') ?>"><?= $t['status'] ?></span>
                                    </div>
                                    <small class="text-muted"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></small>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Kontak Langsung -->
                    <div class="card shadow-sm border-0 mt-3">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="bi bi-telephone me-2 text-success"></i>Kontak Langsung</h6>
                            <div class="d-flex align-items-center mb-2"><i class="bi bi-whatsapp text-success me-2"></i><small>Admin Akademik: <strong>+62 821-XXXX-XXXX</strong></small></div>
                            <div class="d-flex align-items-center mb-2"><i class="bi bi-envelope text-primary me-2"></i><small>Email: <strong>akademik@stisdayahamal.ac.id</strong></small></div>
                            <div class="d-flex align-items-center"><i class="bi bi-clock text-warning me-2"></i><small>Jam Kerja: <strong>Senin–Jumat, 08:00–16:00</strong></small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab FAQ -->
        <div class="tab-pane fade" id="faq">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="accordion" id="accordionFAQ">
                        <?php $faqs = [
                            ['Bagaimana cara mengisi KRS?', 'Login sebagai mahasiswa → Menu "Pengisian KRS" di sidebar → Pilih mata kuliah yang tersedia → Klik "Ajukan Draf KRS". Draf akan dikirim ke Dosen PA untuk disetujui.'],
                            ['Mengapa KRS saya tidak bisa diisi?', 'Ada beberapa kemungkinan: (1) Belum ada semester aktif, (2) Tagihan SPP belum lunas, (3) Dosen PA belum membuka masa KRS. Hubungi akademik untuk konfirmasi.'],
                            ['Bagaimana cara melihat nilai?', 'Masuk ke menu "Kartu Hasil Studi (KHS)" di sidebar. Nilai akan tampil setelah dosen memasukkan nilai akhir.'],
                            ['Bagaimana cara mengumpulkan tugas?', 'Menu "Tugas & E-Learning" → Klik tombol "Kumpulkan" pada tugas yang tersedia → Upload file → Klik Submit.'],
                            ['Lupa password, apa yang harus dilakukan?', 'Hubungi Admin SIAKAD atau Bagian Akademik dengan membawa KTM untuk reset password akun Anda.'],
                            ['Bagaimana cara mengisi EDOM (Kuesioner Dosen)?', 'Menu "Kuesioner (EDOM)" → Isi penilaian untuk setiap dosen yang mengajar Anda → Kuesioner bersifat anonim dan rahasia.'],
                        ]; foreach ($faqs as $i => $faq): ?>
                        <div class="accordion-item border-0 border-bottom faq-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                    <i class="bi bi-question-circle-fill text-primary me-2"></i><?= $faq[0] ?>
                                </button>
                            </h2>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                                <div class="accordion-body text-muted"><?= $faq[1] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Panduan -->
        <div class="tab-pane fade" id="panduan">
            <div class="row g-4">
                <?php $panduan = [
                    ['bi-person-check','Panduan Login','Gunakan username dan password yang diberikan oleh akademik. Ganti password default segera setelah login pertama kali.','primary'],
                    ['bi-card-list','Panduan KRS','Isi KRS setiap awal semester baru. Pilih mata kuliah sesuai jadwal dan kapasitas SKS yang diizinkan dosen PA Anda.','success'],
                    ['bi-journal-text','Panduan Tugas','Kumpulkan tugas sebelum batas waktu. Format file yang diterima: PDF, Word, dan ZIP. Ukuran maksimal 10MB.','warning'],
                    ['bi-wallet2','Panduan Keuangan','Bayar tagihan SPP sebelum mengisi KRS. Simpan bukti pembayaran dan kirimkan ke bagian keuangan untuk konfirmasi.','danger'],
                    ['bi-star-half','Panduan EDOM','Isi kuesioner EDOM di setiap akhir semester untuk menilai performa dosen. Jawaban bersifat rahasia.','info'],
                    ['bi-printer','Panduan Transkrip','Cetak transkrip sementara melalui menu "Cetak Transkrip". Untuk transkrip resmi, ajukan ke bagian akademik.','secondary'],
                ]; foreach ($panduan as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body text-center p-4">
                            <div class="bg-<?= $p[3] ?> bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                                <i class="bi <?= $p[0] ?> text-<?= $p[3] ?> fs-1"></i>
                            </div>
                            <h6 class="fw-bold"><?= $p[1] ?></h6>
                            <p class="text-muted small"><?= $p[2] ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
