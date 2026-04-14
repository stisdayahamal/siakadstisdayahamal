<?php
// views/dosen/dashboard.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
$user = $_SESSION['user'];
$id_dosen = $user['id_dosen'] ?? $user['id'];
$id_user  = $user['id'];

// Ambil foto profil dari tabel users
$user_data = $pdo->prepare("SELECT nama, foto FROM users WHERE id_user = ?");
$user_data->execute([$id_user]);
$user_data = $user_data->fetch();
$foto_url = !empty($user_data['foto'])
    ? '../../public/uploads/avatar/' . $user_data['foto']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user_data['nama'] ?? 'D') . '&background=198754&color=fff&size=80';

// Hitung metrik
$total_kelas = $pdo->prepare('SELECT COUNT(*) FROM jadwal_kuliah WHERE id_dosen = ?');
$total_kelas->execute([$id_dosen]);
$total_kelas = $total_kelas->fetchColumn();

$total_perwalian = $pdo->query("SELECT COUNT(DISTINCT m.id_mhs) FROM krs k JOIN mahasiswa m ON k.id_mhs = m.id_mhs WHERE k.status_krs = 'draf'")->fetchColumn();

$total_tugas = $pdo->prepare("SELECT COUNT(*) FROM tugas_akademik t JOIN jadwal_kuliah jk ON t.id_jadwal = jk.id_jadwal WHERE jk.id_dosen = ?");
$total_tugas->execute([$id_dosen]);
$total_tugas = $total_tugas->fetchColumn();

$avg_rating = $pdo->prepare("SELECT IFNULL(AVG(k.rating), 0) FROM kuesioner_dosen k JOIN jadwal_kuliah jk ON k.id_jadwal = jk.id_jadwal WHERE jk.id_dosen = ?");
$avg_rating->execute([$id_dosen]);
$avg_rating = round($avg_rating->fetchColumn(), 1);

// Statistik Kehadiran (persentase hadir dari semua presensi dosen ini)
$stat_hadir = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_hadir = 'H' THEN 1 ELSE 0 END) as hadir
    FROM presensi p
    JOIN jadwal_kuliah jk ON p.id_jadwal = jk.id_jadwal
    WHERE jk.id_dosen = ?
");
$stat_hadir->execute([$id_dosen]);
$kehadiran_raw = $stat_hadir->fetch();
$pct_hadir = ($kehadiran_raw['total'] > 0)
    ? round(($kehadiran_raw['hadir'] / $kehadiran_raw['total']) * 100, 1)
    : 0;
$total_pertemuan = $kehadiran_raw['total'] ?? 0;

// Tugas Belum Dinilai
$tugas_belum_dinilai = $pdo->prepare("
    SELECT t.judul, mk.nama_mk, COUNT(tk.id_kumpul) as jml
    FROM tugas_kumpul tk
    JOIN tugas_akademik t ON tk.id_tugas = t.id_tugas
    JOIN jadwal_kuliah jk ON t.id_jadwal = jk.id_jadwal
    JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk
    WHERE jk.id_dosen = ? AND tk.nilai IS NULL
    GROUP BY t.id_tugas
    ORDER BY t.batas_waktu ASC
    LIMIT 6
");
$tugas_belum_dinilai->execute([$id_dosen]);
$tugas_belum_dinilai = $tugas_belum_dinilai->fetchAll();
$total_belum_nilai = array_sum(array_column($tugas_belum_dinilai, 'jml'));

// Jadwal Hari Ini
$hari_ini = match(date('w')) {
    1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Minggu'
};
$stmt_hari_ini = $pdo->prepare('SELECT jk.*, mk.nama_mk FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_dosen = ? AND jk.hari = ? ORDER BY jk.jam ASC');
$stmt_hari_ini->execute([$id_dosen, $hari_ini]);
$kelas_hari_ini = $stmt_hari_ini->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Dashboard Dosen - SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        body { 
            background: #f8f9fa url('data:image/svg+xml,%3Csvg width="100" height="100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M10 10h80v80H10z" fill="none" stroke="rgba(0,0,0,0.02)" stroke-width="1"/%3E%3C/svg%3E'); 
        }
        .stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 16px; overflow: hidden; position: relative; z-index: 1; }
        .stat-card::before { content: ""; position: absolute; top:0; left:0; width:100%; height:100%; z-index: -1; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        .tugas-notif { transition: all 0.2s; border-left: 5px solid #dc3545; }
        .tugas-notif:hover { background-color: rgba(220,53,69,0.1) !important; transform: translateX(5px); }
        .jadwal-item { transition: all 0.2s; border-left: 5px solid #198754; }
        .jadwal-item:hover { background-color: rgba(25,135,84,0.15) !important; transform: translateX(5px); }
        .bg-premium-gradient { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); }
        .nav-link.active { background: linear-gradient(135deg, #0d6efd, #0dcaf0) !important; border-radius: 12px; color: white !important;}
        .card-header h6 { font-size: 1.05rem; }
        .btn-outline-custom { border-radius: 14px; border: 1.5px solid; transition: all 0.2s; }
        .btn-outline-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($user['username'] ?? $user['nama']) ?> |
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">

      <?php if (isset($_GET['sukses'])): ?>
      <script>Swal.fire({icon:'success',title:'Berhasil',text:'<?= addslashes($_GET['sukses']) ?>'});</script>
      <?php endif; ?>

      <!-- Header Profil -->
      <div class="d-flex align-items-center justify-content-between pt-3 pb-2 mb-4 border-bottom">
          <div class="d-flex align-items-center gap-3">
              <img src="<?= $foto_url ?>" class="rounded-circle border border-2 border-success shadow-sm" width="55" height="55" style="object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=D&background=198754&color=fff'">
              <div>
                  <h4 class="fw-bold mb-0"><?= htmlspecialchars($user_data['nama'] ?? $user['nama']) ?></h4>
                  <small class="text-muted"><i class="bi bi-calendar-day me-1"></i><?= $hari_ini ?>, <?= date('d M Y') ?></small>
              </div>
          </div>
          <a href="profil.php" class="btn btn-outline-success btn-sm"><i class="bi bi-pencil me-1"></i>Edit Profil</a>
      </div>

      <!-- Kartu Metrik -->
      <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
              <div class="card stat-card bg-primary text-white shadow-sm h-100 border-0 rounded-4">
                  <div class="card-body d-flex align-items-center gap-3">
                      <div class="p-2 bg-white bg-opacity-25 rounded-circle"><i class="bi bi-journal-bookmark-fill fs-3"></i></div>
                      <div>
                          <p class="text-white-50 mb-0 small text-uppercase">Kelas Aktif</p>
                          <h3 class="mb-0 fw-bold"><?= $total_kelas ?></h3>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-6 col-md-3">
              <div class="card stat-card bg-info text-dark shadow-sm h-100 border-0 rounded-4">
                  <div class="card-body d-flex align-items-center gap-3">
                      <div class="p-2 bg-white bg-opacity-50 rounded-circle"><i class="bi bi-people-fill fs-3"></i></div>
                      <div>
                          <p class="text-dark text-opacity-75 mb-0 small text-uppercase">KRS Draf</p>
                          <h3 class="mb-0 fw-bold"><?= $total_perwalian ?></h3>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-6 col-md-3">
              <div class="card stat-card bg-success text-white shadow-sm h-100 border-0 rounded-4">
                  <div class="card-body d-flex align-items-center gap-3">
                      <div class="p-2 bg-white bg-opacity-25 rounded-circle"><i class="bi bi-graph-up fs-3"></i></div>
                      <div>
                          <p class="text-white-50 mb-0 small text-uppercase">% Kehadiran</p>
                          <h3 class="mb-0 fw-bold"><?= $pct_hadir ?>%</h3>
                      </div>
                  </div>
              </div>
          </div>
          <div class="col-6 col-md-3">
              <div class="card stat-card bg-warning text-dark shadow-sm h-100 border-0 rounded-4">
                  <div class="card-body d-flex align-items-center gap-3">
                      <div class="p-2 bg-white bg-opacity-50 rounded-circle"><i class="bi bi-star-fill text-warning fs-3"></i></div>
                      <div>
                          <p class="text-dark text-opacity-75 mb-0 small text-uppercase">Rating EDOM</p>
                          <h3 class="mb-0 fw-bold"><?= $avg_rating > 0 ? $avg_rating : 'N/A' ?></h3>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div class="row g-4 mb-4">
          <!-- Jadwal Hari Ini -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-white border-0 pt-3 pb-0">
                      <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-success me-2"></i>Jadwal Mengajar — <span class="badge bg-success"><?= $hari_ini ?></span></h6>
                  </div>
                  <div class="card-body pb-1">
                      <?php if (empty($kelas_hari_ini)): ?>
                          <div class="text-center py-4 text-muted">
                              <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                              Tidak ada jadwal mengajar hari ini 🎉
                          </div>
                      <?php else: ?>
                          <?php foreach ($kelas_hari_ini as $k): ?>
                          <div class="jadwal-item bg-success bg-opacity-10 rounded-3 p-3 mb-2">
                              <div class="d-flex justify-content-between align-items-start">
                                  <div>
                                      <span class="fw-bold text-dark"><?= htmlspecialchars($k['nama_mk']) ?></span>
                                      <p class="text-muted mb-0 small"><i class="bi bi-door-open me-1"></i><?= htmlspecialchars($k['ruang']) ?></p>
                                  </div>
                                  <div class="text-end">
                                      <span class="badge bg-success mb-1 d-block"><?= htmlspecialchars($k['jam']) ?></span>
                                      <a href="presensi.php?id_jadwal=<?= $k['id_jadwal'] ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-journal-check me-1"></i>Presensi</a>
                                  </div>
                              </div>
                          </div>
                          <?php endforeach; ?>
                      <?php endif; ?>
                  </div>
                  <div class="card-footer bg-light border-0 py-2 text-center">
                      <a href="jadwal_mengajar.php" class="text-decoration-none small">Lihat Semua Jadwal <i class="bi bi-chevron-right"></i></a>
                  </div>
              </div>
          </div>

          <!-- Notifikasi Tugas Belum Dinilai -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-white border-0 pt-3 pb-0">
                      <h6 class="fw-bold mb-0">
                          <i class="bi bi-bell-fill text-danger me-2"></i>Tugas Belum Dinilai
                          <?php if ($total_belum_nilai > 0): ?>
                              <span class="badge bg-danger ms-1"><?= $total_belum_nilai ?> kumpulan</span>
                          <?php endif; ?>
                      </h6>
                  </div>
                  <div class="card-body pb-1">
                      <?php if (empty($tugas_belum_dinilai)): ?>
                          <div class="text-center py-4 text-muted">
                              <i class="bi bi-check-circle fs-1 d-block mb-2 text-success opacity-50"></i>
                              Semua tugas sudah dinilai! ✅
                          </div>
                      <?php else: ?>
                          <?php foreach ($tugas_belum_dinilai as $t): ?>
                          <div class="tugas-notif bg-danger bg-opacity-5 rounded-3 p-3 mb-2">
                              <div class="d-flex justify-content-between align-items-center">
                                  <div>
                                      <span class="fw-bold text-dark d-block small"><?= htmlspecialchars($t['judul']) ?></span>
                                      <span class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($t['nama_mk']) ?></span>
                                  </div>
                                  <div class="text-end">
                                      <span class="badge bg-danger"><?= $t['jml'] ?> file</span>
                                  </div>
                              </div>
                          </div>
                          <?php endforeach; ?>
                      <?php endif; ?>
                  </div>
                  <div class="card-footer bg-light border-0 py-2 text-center">
                      <a href="tugas.php" class="text-decoration-none small">Buka Halaman Tugas <i class="bi bi-chevron-right"></i></a>
                  </div>
              </div>
          </div>
      </div>

      <!-- Statistik Kehadiran & Akses Cepat -->
      <div class="row g-4">
          <!-- Stat Kehadiran -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0">
                  <div class="card-header bg-white border-0 pt-3 pb-0">
                      <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Statistik Kehadiran Mahasiswa</h6>
                  </div>
                  <div class="card-body">
                      <div class="d-flex align-items-center mb-2">
                          <div class="fs-1 fw-bold text-success me-3"><?= $pct_hadir ?>%</div>
                          <div>
                              <p class="mb-0 text-muted small">Rata-rata kehadiran dari <?= $total_pertemuan ?> rekaman presensi</p>
                              <div class="progress mt-1" style="height:10px;width:200px;">
                                  <div class="progress-bar bg-success" style="width:<?= $pct_hadir ?>%"></div>
                              </div>
                          </div>
                      </div>
                      <a href="presensi.php" class="btn btn-outline-primary btn-sm w-100 mt-2"><i class="bi bi-clipboard-check me-1"></i>Kelola Absensi</a>
                  </div>
              </div>
          </div>

          <!-- Akses Cepat -->
          <div class="col-md-6">
              <div class="card shadow-sm border-0">
                  <div class="card-header bg-white border-0 pt-3 pb-0">
                      <h6 class="fw-bold mb-0"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Akses Cepat</h6>
                  </div>
                  <div class="card-body">
                      <div class="row g-2">
                          <?php $shortcuts = [
                              ['presensi.php','bi-journal-check','Presensi','success'],
                              ['tugas.php','bi-pencil-square','Nilai Tugas','primary'],
                              ['input_nilai.php','bi-award','Input Nilai','warning'],
                              ['jadwal_mengajar.php','bi-calendar-week','Jadwal','info'],
                              ['perwalian.php','bi-people','Perwalian','danger'],
                              ['feedback.php','bi-star-half','Feedback','secondary'],
                          ]; ?>
                          <?php foreach ($shortcuts as $sc): ?>
                          <div class="col-4">
                              <a href="<?= $sc[0] ?>" class="btn btn-outline-<?= $sc[3] ?> w-100 py-2 d-flex flex-column align-items-center gap-1 text-decoration-none">
                                  <i class="bi <?= $sc[1] ?> fs-5"></i>
                                  <span style="font-size:0.72rem;"><?= $sc[2] ?></span>
                              </a>
                          </div>
                          <?php endforeach; ?>
                      </div>
                  </div>
              </div>
          </div>
      </div>

    </main>
  </div>
</div>
<!-- Announcement Modal -->
<?php if (!empty($sys['pesan_dashboard'])): ?>
<div class="modal fade" id="modalAnnouncement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-megaphone-fill me-2"></i>Pengumuman Kampus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="60" class="mb-3">
                <h5 class="fw-bold text-dark mb-3"><?= htmlspecialchars($sys['nama_kampus']) ?></h5>
                <div class="px-2 py-3 bg-light rounded-3 mb-0 border border-light-subtle">
                    <p class="mb-0 text-secondary lh-lg italic">
                        "<?= nl2br(htmlspecialchars($sys['pesan_dashboard'])) ?>"
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm" data-bs-dismiss="modal">Dimengerti, Terima Kasih</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($sys['pesan_dashboard'])): ?>
    if (!sessionStorage.getItem('announcementShown')) {
        const myModal = new bootstrap.Modal(document.getElementById('modalAnnouncement'));
        myModal.show();
        sessionStorage.setItem('announcementShown', 'true');
    }
    <?php endif; ?>
});
</script>
</body>
</html>
