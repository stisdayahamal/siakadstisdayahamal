<?php
// views/admin/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebarNav" class="col-md-2 d-none d-md-block bg-dark sidebar shadow">
    <div class="pt-3">
        <div class="text-center text-white mb-3 px-3 pt-2">
            <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="40" class="mb-2 rounded">
            <h5 class="mt-2 fw-bold text-uppercase text-warning" style="letter-spacing:1px;"><?= htmlspecialchars($sys['nama_kampus']) ?></h5>
            <small class="opacity-75">Sistem ERP Terpadu</small>
        </div>
        <hr class="text-secondary">
        <ul class="nav flex-column gap-1 px-2">
            <li class="nav-item">
                <a class="nav-link text-white rounded <?= $current_page == 'dashboard.php' ? 'bg-primary' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard Eksekutif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white rounded <?= $current_page == 'profil.php' ? 'bg-primary' : '' ?>" href="profil.php">
                    <i class="bi bi-person-badge me-2"></i> Profil & Akun Saya
                </a>
            </li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Rutinitas</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="absensi.php"><i class="bi bi-fingerprint me-2"></i>Absensi Karyawan</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="cuti.php"><i class="bi bi-calendar2-minus me-2"></i>Izin & Cuti</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="support.php"><i class="bi bi-headset me-2"></i>Support Ticket</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Akademik Terpusat</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="fakultas.php"><i class="bi bi-building me-2"></i>Fakultas & Prodi</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="kurikulum.php"><i class="bi bi-journal-check me-2"></i>Kurikulum & MK</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="kelas.php"><i class="bi bi-door-open me-2"></i>Plotting Kelas</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="jadwal.php"><i class="bi bi-calendar-week me-2"></i>Jadwal Kuliah</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded" href="tahun_akademik.php"><i class="bi bi-hourglass-split me-2"></i>SMT Aktif</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Penerimaan (PMB)</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'pmb_setup.php' ? 'bg-primary' : '' ?>" href="pmb_setup.php"><i class="bi bi-sliders me-2"></i>Setup Gelombang</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'pmb_calon.php' ? 'bg-primary' : '' ?>" href="pmb_calon.php"><i class="bi bi-people me-2"></i>Calon Mahasiswa</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Portal Kampus</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'publikasi.php' ? 'bg-primary' : '' ?>" href="publikasi.php"><i class="bi bi-megaphone me-2"></i>Publikasi CMS</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Keuangan</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'keuangan.php' ? 'bg-primary' : '' ?>" href="keuangan.php"><i class="bi bi-receipt me-2"></i>Tagihan & SPP</a></li>
            <li class="nav-item">
                <a class="nav-link text-light rounded <?= $current_page == 'konfirmasi_keuangan.php' ? 'bg-primary' : '' ?>" href="konfirmasi_keuangan.php">
                    <i class="bi bi-patch-check me-2"></i> Konfirmasi Bayar
                    <?php 
                        $pending_conf = $pdo->query("SELECT COUNT(*) FROM konfirmasi_pembayaran WHERE status = 'Menunggu'")->fetchColumn();
                        if ($pending_conf > 0): 
                    ?>
                        <span class="badge bg-danger rounded-pill float-end"><?= $pending_conf ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'saldo_keuangan.php' ? 'bg-primary' : '' ?>" href="saldo_keuangan.php"><i class="bi bi-wallet2 me-2"></i>Saldo & Rekap</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Sentralisasi User</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'kelola_user.php' ? 'bg-primary' : '' ?>" href="kelola_user.php"><i class="bi bi-person-gear me-2"></i>Kelola Pengguna</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'mahasiswa.php' ? 'bg-primary' : '' ?>" href="mahasiswa.php"><i class="bi bi-mortarboard me-2"></i>Data Mahasiswa</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'prodi.php' ? 'bg-primary' : '' ?>" href="prodi.php"><i class="bi bi-diagram-3 me-2"></i>Prodi & Jurusan</a></li>

            <li class="nav-item fw-bold mt-3 ps-2 text-warning fs-7 text-uppercase"><small>Keamanan Sistem</small></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'log_aktivitas.php' ? 'bg-primary' : '' ?>" href="log_aktivitas.php"><i class="bi bi-activity me-2"></i>Log Aktivitas</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'backup_db.php' ? 'bg-primary' : '' ?>" href="backup_db.php"><i class="bi bi-database-down me-2"></i>Backup DB</a></li>
            <li class="nav-item"><a class="nav-link text-light rounded <?= $current_page == 'pengaturan.php' ? 'bg-primary' : '' ?>" href="pengaturan.php"><i class="bi bi-gear-wide-connected me-2"></i>Pengaturan Sistem</a></li>
            
            <li class="nav-item mt-4 mb-5">
                <a class="nav-link text-danger fw-bold rounded" href="../../auth/logout.php">
                    <i class="bi bi-power me-2"></i> Keluar / Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
<script>
(function(){
    var k = 'sidebar_scroll_admin';
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
