<?php
// views/mahasiswa/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$id_mhs_sidebar = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];

require_once __DIR__ . '/../../../config/db.php';
$stmt_badge_mhs = $pdo->prepare("
    SELECT COUNT(t.id_tugas) FROM tugas_akademik t
    JOIN krs k ON t.id_jadwal = k.id_jadwal
    LEFT JOIN tugas_kumpul tk ON t.id_tugas = tk.id_tugas AND tk.id_mhs = ?
    WHERE k.id_mhs = ? AND k.status_krs = 'setuju' AND t.batas_waktu >= NOW() AND tk.id_kumpul IS NULL
");
$stmt_badge_mhs->execute([$id_mhs_sidebar, $id_mhs_sidebar]);
$badge_tugas_mhs = $stmt_badge_mhs->fetchColumn();
?>
<nav id="sidebarNav" class="col-md-2 d-none d-md-block bg-white sidebar shadow-sm">
  <div class="pt-3">
    <div class="text-center mb-3 px-3 pt-2">
        <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="40" class="mb-2 rounded shadow-sm">
        <h5 class="mt-2 fw-bold text-uppercase text-success" style="letter-spacing:1px;"><?= htmlspecialchars($sys['nama_kampus']) ?></h5>
        <small class="opacity-75 text-muted">Portal Mahasiswa</small>
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
      
      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Perkuliahan Aktif</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'jadwal.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="jadwal.php">
            <i class="bi bi-calendar-week me-2"></i>Jadwal Kuliah
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'krs_input.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="krs_input.php">
            <i class="bi bi-card-list me-2"></i>Pengisian KRS
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'tugas.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="tugas.php">
            <i class="bi bi-journal-text me-2"></i>Tugas & E-Learning <?php if($badge_tugas_mhs > 0): ?><span class="badge bg-danger rounded-pill float-end" style="font-size:0.65rem"><?= $badge_tugas_mhs ?></span><?php endif; ?>
          </a>
      </li>
      
      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Laporan Akademik</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'khs.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="khs.php">
            <i class="bi bi-card-checklist me-2"></i>Kartu Hasil Studi (KHS)
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'kuesioner.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="kuesioner.php">
            <i class="bi bi-ui-radios-grid me-2"></i>Kuesioner (EDOM)
          </a>
      </li>
      <li class="nav-item">
          <a class="nav-link text-dark rounded" href="cetak_transkrip.php" target="_blank">
            <i class="bi bi-printer me-2"></i>Cetak Transkrip
          </a>
      </li>
      
      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Finansial</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'keuangan.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="keuangan.php">
            <i class="bi bi-wallet2 me-2"></i>Tagihan & Keuangan
          </a>
      </li>

      <li class="nav-item mt-3 mb-1"><small class="text-muted fw-bold px-3 text-uppercase" style="font-size:0.75rem;">Bantuan</small></li>
      <li class="nav-item">
          <a class="nav-link <?= $current_page == 'bantuan.php' ? 'active bg-success text-white rounded shadow-sm' : 'text-dark rounded' ?>" href="bantuan.php">
            <i class="bi bi-headset me-2"></i>Pusat Bantuan
          </a>
      </li>
    </ul>
  </div>
</nav>
<script>
(function(){
    var k = 'sidebar_scroll_mhs';
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
