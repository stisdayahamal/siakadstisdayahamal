<?php
$current_page = basename($_SERVER['PHP_SELF']);
$id_dosen_sidebar = $_SESSION['user']['id_dosen'] ?? $_SESSION['user']['id'];

require_once __DIR__ . '/../../../config/db.php';
$stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM tugas_kumpul k JOIN tugas_akademik t ON k.id_tugas = t.id_tugas JOIN jadwal_kuliah jk ON t.id_jadwal = jk.id_jadwal WHERE jk.id_dosen = ? AND k.nilai IS NULL");
$stmt_badge->execute([$id_dosen_sidebar]);
$badge_tugas = $stmt_badge->fetchColumn();

// Badge KRS perlu disetujui
$badge_perwalian = $pdo->query("SELECT COUNT(DISTINCT id_mhs) FROM krs WHERE status_krs = 'draf'")->fetchColumn();
?>
<nav id="sidebarNav" class="col-md-2 d-none d-md-block bg-white sidebar shadow-sm">
  <div class="pt-3">
    <div class="text-center mb-3 px-3 pt-2">
        <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="40" class="mb-2 rounded shadow-sm">
        <h5 class="mt-2 fw-bold text-uppercase text-success" style="letter-spacing:1px;"><?= htmlspecialchars($sys['nama_kampus']) ?></h5>
        <small class="opacity-75 text-muted">Panel Dosen Akademik</small>
    </div>
    <hr class="text-secondary mx-3">
    <ul class="nav flex-column gap-1 px-2">
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard Utama
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'profil.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="profil.php">
            <i class="bi bi-person-circle me-2"></i>Profil Saya
          </a>
      </li>
      
      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Perkuliahan & Mengajar</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'jadwal_mengajar.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="jadwal_mengajar.php">
            <i class="bi bi-calendar-week me-2"></i>Jadwal Mengajar
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'presensi.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="presensi.php">
            <i class="bi bi-person-check me-2"></i>Manajemen Absensi
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'tugas.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="tugas.php">
            <i class="bi bi-journal-check me-2"></i>Penilaian Tugas
            <?php if($badge_tugas > 0): ?>
              <span class="badge bg-danger rounded-pill float-end" style="font-size:0.65rem"><?= $badge_tugas ?></span>
            <?php endif; ?>
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'input_nilai.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="input_nilai.php">
            <i class="bi bi-award me-2"></i>Input Nilai Akhir
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'feedback.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="feedback.php">
            <i class="bi bi-star-half me-2"></i>Feedback Mahasiswa
          </a>
      </li>

      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Bimbingan & Layanan</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'perwalian.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="perwalian.php">
            <i class="bi bi-people me-2"></i>Perwalian (KRS)
            <?php if($badge_perwalian > 0): ?>
              <span class="badge bg-warning text-dark rounded-pill float-end" style="font-size:0.65rem"><?= $badge_perwalian ?></span>
            <?php endif; ?>
          </a>
      </li>
    </ul>
  </div>
</nav>
<script>
(function(){
    var k = 'sidebar_scroll_dosen';
    function restore() {
        var s = document.getElementById('sidebarNav');
        if (!s) return;
        var saved = localStorage.getItem(k);
        if (saved) {
            s.style.scrollBehavior = 'auto';
            s.scrollTop = parseInt(saved, 10);
        }
        s.addEventListener('scroll', function(){
            localStorage.setItem(k, s.scrollTop);
        }, { passive: true });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restore);
    } else {
        requestAnimationFrame(restore);
    }
})();
</script>
