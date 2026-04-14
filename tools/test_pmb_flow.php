<?php
// tools/test_pmb_flow.php
define('ACCESS', true);
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();
    echo "1. Mengisi form pendaftaran dummy...\n";
    
    // Simulate daftar.php
    $nik = '1234567890123456';
    $nisn = '0987654321';
    $nama = 'Dummy Tester PMB';
    $tempat_lahir = 'Banda Aceh';
    $tgl_lahir = '2005-01-01';
    $jk = 'L';
    $agama = 'Islam';
    $alamat = 'Jalan Dummy No 10';
    $nama_ibu = 'Ibu Dummy';
    $asal_sekolah = 'SMA Dummy';
    $tahun_lulus = '2023';
    $email = 'dummy.tester@pmb.com';
    $no_hp = '081234567899';
    $id_prodi = 1;
    $berkas_ijazah = 'uploads/dummy_ijazah.pdf';
    $berkas_ktp = 'uploads/dummy_ktp.pdf';
    $berkas_kk = 'uploads/dummy_kk.pdf';
    $berkas_foto = 'uploads/dummy_foto.jpg';

    $stmt = $pdo->prepare('INSERT INTO calon_mhs (nik, nisn, nama, tempat_lahir, tgl_lahir, jk, agama, alamat, nama_ibu, asal_sekolah, tahun_lulus, email, no_hp, id_prodi, berkas_ijazah, berkas_ktp, berkas_kk, berkas_foto, status, sudah_bayar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Proses", 0)');
    $stmt->execute([$nik, $nisn, $nama, $tempat_lahir, $tgl_lahir, $jk, $agama, $alamat, $nama_ibu, $asal_sekolah, $tahun_lulus, $email, $no_hp, $id_prodi, $berkas_ijazah, $berkas_ktp, $berkas_kk, $berkas_foto]);
    $id_calon = $pdo->lastInsertId();
    echo "Pendaftaran berhasil, ID Calon: $id_calon\n";

    echo "2. Memverifikasi dan Muluskan via Admin...\n";
    // Simulate pmb_calon.php
    $pdo->prepare("UPDATE calon_mhs SET status = 'Lulus' WHERE id_calon = ?")->execute([$id_calon]);
    
    $calon = $pdo->prepare("SELECT * FROM calon_mhs WHERE id_calon = ?");
    $calon->execute([$id_calon]);
    $c = $calon->fetch();

    $tahun = date('Y');
    $nim = $tahun . str_pad($c['id_prodi'], 2, '0', STR_PAD_LEFT) . str_pad($id_calon, 3, '0', STR_PAD_LEFT);
    $pass_default = password_hash($nim, PASSWORD_DEFAULT);

    $stmt_mhs = $pdo->prepare("INSERT INTO mahasiswa (nim, nik, nama, tempat_lahir, tanggal_lahir, tgl_lahir, jenis_kelamin, jk, agama, alamat, no_hp, email, nama_ibu, asal_sekolah, tahun_lulus, id_prodi, tahun_masuk, password, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0')");
    $stmt_mhs->execute([
        $nim, $c['nik'], $c['nama'], $c['tempat_lahir'], $c['tgl_lahir'], $c['tgl_lahir'], 
        $c['jk'], $c['jk'], $c['agama'], $c['alamat'], $c['no_hp'], $c['email'], 
        $c['nama_ibu'], $c['asal_sekolah'], $c['tahun_lulus'], $c['id_prodi'], $tahun, $pass_default
    ]);
    
    $id_mhs = $pdo->lastInsertId();
    echo "Migrasi ke tabel mahasiswa berhasil. NIM: $nim, ID Mahasiswa: $id_mhs\n";

    // Verify it was inserted
    $mhs_check = $pdo->query("SELECT * FROM mahasiswa WHERE id_mhs = $id_mhs")->fetch();
    if ($mhs_check['nama'] === 'Dummy Tester PMB' && $mhs_check['agama'] === 'Islam' && $mhs_check['tempat_lahir'] === 'Banda Aceh') {
        echo "Validasi data mahasiswa sempurna! Kolom-kolom berhasil dipetakan.\n";
    } else {
        echo "Warning: Data mahasiswa tidak sinkron.\n";
    }

    $pdo->rollBack();
    echo "\nTest sukses! Database di-rollback ke state awal agar tetap bersih.\n";

} catch (Exception $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n";
    $pdo->rollBack();
}
