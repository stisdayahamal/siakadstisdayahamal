<?php
// views/mahasiswa/proses_krs.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

if ($_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: dashboard.php');
    exit;
}

$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
$th_aktif = get_tahun_aktif($pdo);
$kode_tahun = $th_aktif['kode_tahun'] ?? '20251';

// CSRF Protection
if (!csrf_validate(false)) { // false means it returns bool instead of redirecting
     header('Location: krs_input.php?err=csrf');
     exit;
}

if (!isset($_POST['id_jadwal']) || !is_array($_POST['id_jadwal'])) {
    header('Location: krs_input.php?err=nodata');
    exit;
}

$jadwal_ids = array_map('intval', $_POST['id_jadwal']);

// 1. Ambil jatah SKS via Helper (DIKTI Standard)
$jatah_sks = get_jatah_sks_mahasiswa($pdo, $id_mhs);

// 2. Ambil data jadwal yang dipilih
$in = str_repeat('?,', count($jadwal_ids) - 1) . '?';
$sql = "SELECT jk.*, mk.sks, mk.nama_mk FROM jadwal_kuliah jk JOIN mata_kuliah mk ON jk.id_mk = mk.id_mk WHERE jk.id_jadwal IN ($in)";
$stmt = $pdo->prepare($sql);
$stmt->execute($jadwal_ids);
$selected_jadwal = $stmt->fetchAll();

// 3. Validasi Total SKS
$total_sks_dipilih = array_sum(array_column($selected_jadwal, 'sks'));
if ($total_sks_dipilih > $jatah_sks) {
    header('Location: krs_input.php?err=sks');
    exit;
}

// 4. Proses Simpan dengan Transaksi
try {
    $pdo->beginTransaction();

    // Hapus draf KRS lama untuk semester ini (re-fill)
    // Kita anggap krs yang statusnya 'draf' bisa ditimpa. krs 'setuju' tidak boleh.
    // Namun untuk simplifikasi, kita bersihkan dulu yang draf.
    $pdo->prepare("DELETE FROM krs WHERE id_mhs = ? AND kode_tahun = ? AND status_krs = 'draf'")->execute([$id_mhs, $kode_tahun]);

    foreach ($selected_jadwal as $row) {
        // Cek Kuota lagi (Server-side check)
        $stmt_kuota = $pdo->prepare('SELECT COUNT(*) FROM krs WHERE id_jadwal = ? AND status_krs IN ("draf", "setuju")');
        $stmt_kuota->execute([$row['id_jadwal']]);
        $terpakai = $stmt_kuota->fetchColumn();

        if ($terpakai >= $row['kuota']) {
            throw new Exception("Kuota mata kuliah " . $row['nama_mk'] . " sudah penuh.");
        }

        // Insert draf baru
        $pdo->prepare("INSERT INTO krs (id_mhs, id_jadwal, status_krs, status_approve, kode_tahun) VALUES (?, ?, 'draf', 0, ?)")
            ->execute([$id_mhs, $row['id_jadwal'], $kode_tahun]);
    }

    $pdo->commit();
    header('Location: krs_input.php?sukses=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $error_msg = urlencode($e->getMessage());
    header("Location: krs_input.php?err={$error_msg}");
    exit;
}
