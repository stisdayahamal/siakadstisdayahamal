<?php
/*
|==========================================================================
| views/pmb/daftar.php — Formulir PMB Standar DIKTI (Versi Final)
| Memuat: Data Pribadi, Data Orangtua, Alamat Rinci, Berkas, Konfirmasi
|==========================================================================
*/
require_once '../../includes/purify.php';
define('ACCESS', true);
require_once '../../config/db.php';
require_once '../../includes/load_settings.php';
require_once '../../includes/csrf.php';

$success = $error = '';
csrf_validate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ---- 1. Sanitasi Data ---- */
    $nama        = purify(trim($_POST['nama'] ?? ''));
    $nik         = trim($_POST['nik'] ?? '');
    $nisn        = trim($_POST['nisn'] ?? '');
    $tempat_lahir= purify(trim($_POST['tempat_lahir'] ?? ''));
    $tgl_lahir   = $_POST['tgl_lahir'] ?? '';
    $jk          = $_POST['jk'] ?? '';
    $agama       = purify(trim($_POST['agama'] ?? ''));
    $email       = trim($_POST['email'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');
    $alamat      = purify(trim($_POST['alamat'] ?? ''));
    $rt          = trim($_POST['rt'] ?? '');
    $rw          = trim($_POST['rw'] ?? '');
    $kelurahan   = purify(trim($_POST['kelurahan'] ?? ''));
    $kecamatan   = purify(trim($_POST['kecamatan'] ?? ''));
    $kode_pos    = trim($_POST['kode_pos'] ?? '');
    $jenis_tinggal      = $_POST['jenis_tinggal'] ?? '';
    $alat_transportasi  = $_POST['alat_transportasi'] ?? '';
    $nama_ibu    = purify(trim($_POST['nama_ibu'] ?? ''));
    $nama_ayah   = purify(trim($_POST['nama_ayah'] ?? ''));
    $nik_ibu     = trim($_POST['nik_ibu'] ?? '');
    $nik_ayah    = trim($_POST['nik_ayah'] ?? '');
    $pekerjaan_ayah     = purify(trim($_POST['pekerjaan_ayah'] ?? ''));
    $pekerjaan_ibu      = purify(trim($_POST['pekerjaan_ibu'] ?? ''));
    $pendidikan_ayah    = $_POST['pendidikan_ayah'] ?? '';
    $pendidikan_ibu     = $_POST['pendidikan_ibu'] ?? '';
    $penghasilan_ortu   = $_POST['penghasilan_ortu'] ?? '';
    $asal_sekolah = purify(trim($_POST['asal_sekolah'] ?? ''));
    $tahun_lulus  = trim($_POST['tahun_lulus'] ?? '');
    $id_prodi    = (int)($_POST['id_prodi'] ?? 0);
    $jalur_masuk = purify(trim($_POST['jalur_masuk'] ?? 'Reguler'));
    $id_gelombang = (int)($_POST['id_gelombang'] ?? 0);

    /* ---- 2. Validasi ---- */
    if (!preg_match("/^[a-zA-Z .'\-]{3,100}$/u", $nama))
        $error = 'Nama lengkap tidak valid (hanya huruf, spasi, dan tanda baca).';
    elseif (!preg_match('/^[0-9]{16}$/', $nik))
        $error = 'NIK harus tepat 16 digit angka.';
    elseif (!preg_match('/^[0-9]{5,15}$/', $nisn))
        $error = 'NISN tidak valid (5-15 digit).';
    elseif (empty($tempat_lahir))
        $error = 'Tempat lahir wajib diisi.';
    elseif (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $tgl_lahir))
        $error = 'Tanggal lahir tidak valid.';
    elseif (!in_array($jk, ['L', 'P']))
        $error = 'Jenis kelamin tidak valid.';
    elseif (empty($agama))
        $error = 'Agama wajib dipilih.';
    elseif (empty($alamat))
        $error = 'Alamat lengkap wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = 'Format email tidak valid.';
    elseif (!preg_match('/^[0-9+() -]{8,20}$/', $no_hp))
        $error = 'Nomor HP tidak valid.';
    elseif (empty($nama_ibu))
        $error = 'Nama Ibu Kandung wajib diisi (kewajiban DIKTI).';
    elseif (empty($nama_ayah))
        $error = 'Nama Ayah Kandung wajib diisi.';
    elseif (empty($asal_sekolah))
        $error = 'Asal sekolah wajib diisi.';
    elseif (!preg_match('/^[0-9]{4}$/', $tahun_lulus) || (int)$tahun_lulus < 1990 || (int)$tahun_lulus > (int)(date('Y')+1))
        $error = 'Tahun lulus tidak valid.';
    elseif ($id_prodi < 1)
        $error = 'Silakan pilih Program Studi.';
    else {
        /* ---- 3. Cek NIK Duplikat ---- */
        $cek = $pdo->prepare("SELECT id_calon FROM calon_mhs WHERE nik = ?");
        $cek->execute([$nik]);
        if ($cek->fetchColumn()) {
            $error = 'NIK tersebut sudah pernah terdaftar di sistem kami.';
        }
    }

    if (empty($error)) {
        /* ---- 4. Upload Berkas ---- */
        $berkas_ijazah = $berkas_ktp = $berkas_kk = $berkas_foto = $berkas_akte = '';
        $files_map = [
            'ijazah' => ['field' => 'berkas_ijazah', 'allowed_mime' => ['application/pdf','image/jpeg','image/png']],
            'ktp'    => ['field' => 'berkas_ktp',    'allowed_mime' => ['application/pdf','image/jpeg','image/png']],
            'kk'     => ['field' => 'berkas_kk',     'allowed_mime' => ['application/pdf','image/jpeg','image/png']],
            'foto'   => ['field' => 'berkas_foto',   'allowed_mime' => ['image/jpeg','image/png']],
            'akte'   => ['field' => 'berkas_akte',   'allowed_mime' => ['application/pdf','image/jpeg','image/png'], 'optional' => true],
            'kip_pkh'=> ['field' => 'berkas_kip_pkh', 'allowed_mime' => ['application/pdf','image/jpeg','image/png'], 'optional' => true],
        ];

        $upload_dir = '../../uploads/pmb/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $uploaded = [];
        foreach ($files_map as $key => $conf) {
            $file = $_FILES[$conf['field']] ?? null;
            $optional = $conf['optional'] ?? false;
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                if (!$optional) { $error = "Berkas " . strtoupper($key) . " wajib diupload."; break; }
                continue;
            }
            if ($file['error'] !== UPLOAD_ERR_OK) { $error = "Error upload berkas " . strtoupper($key) . "."; break; }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $conf['allowed_mime'])) { $error = "Format berkas " . strtoupper($key) . " tidak didukung."; break; }
            if ($file['size'] > 3*1024*1024) { $error = "Berkas " . strtoupper($key) . " maks 3MB."; break; }
            $filename = 'pmb_' . $key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) { $error = "Gagal menyimpan berkas " . strtoupper($key) . "."; break; }
            $uploaded[$key] = 'uploads/pmb/' . $filename;
        }

        if (empty($error)) {
            /* ---- 5. Generate No Pendaftaran ---- */
            $no_daftar = 'PMB-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            /* ---- 6. Simpan ke DB ---- */
            $stmt = $pdo->prepare("
                INSERT INTO calon_mhs (
                    nik, nisn, nama, tempat_lahir, tgl_lahir, jk, agama, alamat,
                    alamat_detail, rt, rw, kelurahan, kecamatan, kode_pos,
                    jenis_tinggal, alat_transportasi,
                    nama_ibu, nama_ayah, nik_ibu, nik_ayah,
                    pekerjaan_ayah, pekerjaan_ibu, pendidikan_ayah, pendidikan_ibu, penghasilan_ortu,
                    asal_sekolah, tahun_lulus, email, no_hp,
                    id_prodi, jalur_masuk, id_gelombang, no_pendaftaran,
                    berkas_ijazah, berkas_ktp, berkas_kk, berkas_foto, berkas_akte, berkas_kip_pkh,
                    status, sudah_bayar
                ) VALUES (
                    ?,?,?,?,?,?,?,?,
                    ?,?,?,?,?,?,
                    ?,?,
                    ?,?,?,?,
                    ?,?,?,?,?,
                    ?,?,?,?,
                    ?,?,?,?,
                    ?,?,?,?,?,?,
                    'Proses', 0
                )
            ");
            $stmt->execute([
                $nik, $nisn, $nama, $tempat_lahir, $tgl_lahir, $jk, $agama, $alamat,
                $alamat, $rt, $rw, $kelurahan, $kecamatan, $kode_pos,
                $jenis_tinggal, $alat_transportasi,
                $nama_ibu, $nama_ayah, $nik_ibu, $nik_ayah,
                $pekerjaan_ayah, $pekerjaan_ibu, $pendidikan_ayah, $pendidikan_ibu, $penghasilan_ortu,
                $asal_sekolah, $tahun_lulus, $email, $no_hp,
                $id_prodi, $jalur_masuk, $id_gelombang ?: null, $no_daftar,
                $uploaded['ijazah'] ?? '', $uploaded['ktp'] ?? '', $uploaded['kk'] ?? '',
                $uploaded['foto'] ?? '', $uploaded['akte'] ?? '', $uploaded['kip_pkh'] ?? '',
            ]);
            $success = "Pendaftaran Anda berhasil dikirim! Nomor Pendaftaran Anda: <strong>{$no_daftar}</strong>.<br>Simpan nomor ini untuk pelacakan status. Cek email Anda secara berkala.";
        }
    }
}

$prodi    = $pdo->query('SELECT id_prodi, nama_prodi FROM prodi ORDER BY nama_prodi ASC')->fetchAll();
$jalur_list = $pdo->query('SELECT * FROM pmb_jalur ORDER BY nama_jalur ASC')->fetchAll();
$aktif_gel = $pdo->query("
    SELECT g.*, p.nama_periode FROM pmb_gelombang g 
    JOIN pmb_periode p ON g.id_periode=p.id_periode 
    WHERE p.status_aktif=1 AND g.tgl_mulai <= CURDATE() AND g.tgl_selesai >= CURDATE()
    ORDER BY g.tgl_selesai ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran PMB - <?= htmlspecialchars($sys['nama_kampus']) ?></title>
    <meta name="description" content="Formulir Penerimaan Mahasiswa Baru <?= htmlspecialchars($sys['nama_kampus']) ?> - Sesuai Standar DIKTI">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        :root { --pmb-primary: #059669; --pmb-gold: #d4af37; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #fefce8 100%); }
        .hero-pmb { background: linear-gradient(135deg, var(--pmb-primary), #034b3a); color: white; padding: 2.5rem 0; }
        .step-badge { background: var(--pmb-gold); color: white; border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; flex-shrink: 0; }
        .section-divider { display: flex; align-items: center; gap: 1rem; font-weight: 700; color: var(--pmb-primary); font-size: 1rem; margin: 2rem 0 1.5rem; }
        .section-divider::after { content: ''; flex: 1; height: 2px; background: linear-gradient(to right, rgba(5,150,105,0.2), transparent); }
        .upload-zone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.25rem; cursor: pointer; transition: all 0.2s; background: #fafafa; }
        .upload-zone:hover { border-color: var(--pmb-primary); background: #f0fdf4; }
        .upload-zone input[type=file] { cursor: pointer; }
        .required-mark { color: #ef4444; font-size: 0.8rem; }
        .badge-gelombang { background: linear-gradient(135deg,#059669,#047857); padding: 0.4em 1em; font-size: 0.8rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white shadow-sm px-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="../../index.php">
            <img src="../../<?= $sys['logo_kampus'] ?>" alt="Logo" height="36" class="rounded">
            <span class="text-success">PMB <?= htmlspecialchars($sys['nama_kampus']) ?></span>
        </a>
        <a href="../../auth/login.php" class="btn btn-outline-success btn-sm ms-auto px-4 fw-bold rounded-pill">Login</a>
    </div>
</nav>

<!-- Hero -->
<div class="hero-pmb">
    <div class="container text-center">
        <span class="badge rounded-pill px-3 py-2 mb-3" style="background:rgba(255,255,255,0.15);">
            <i class="bi bi-mortarboard-fill me-1"></i> Penerimaan Mahasiswa Baru
        </span>
        <h1 class="fw-bold fs-2 mb-2">Formulir Pendaftaran Terpadu DIKTI</h1>
        <p class="opacity-75 mb-0">Lengkapi seluruh data dengan benar. Data ini akan digunakan untuk pelaporan resmi ke PDDikti.</p>
        <?php if (!empty($aktif_gel)): ?>
        <div class="mt-3">
            <?php foreach($aktif_gel as $g): ?>
            <span class="badge badge-gelombang rounded-pill me-2">
                <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($g['nama_gelombang']) ?> — Sampai <?= date('d M Y', strtotime($g['tgl_selesai'])) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">

            <?php if ($success): ?>
            <div class="card border-0 shadow-lg rounded-4 p-5 text-center mb-4" style="background: linear-gradient(135deg, #f0fdf4, #d1fae5);">
                <i class="bi bi-patch-check-fill text-success" style="font-size: 4rem;"></i>
                <h3 class="fw-bold mt-3 text-success">Pendaftaran Berhasil Terkirim!</h3>
                <p class="text-muted mb-3"><?= $success ?></p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="daftar.php" class="btn btn-outline-success rounded-pill px-4"><i class="bi bi-person-plus me-2"></i>Daftarkan Lain</a>
                    <a href="../../index.php" class="btn btn-success rounded-pill px-4"><i class="bi bi-house me-2"></i>Kembali Beranda</a>
                </div>
            </div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-start gap-3 rounded-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill fs-4 text-danger flex-shrink-0 mt-1"></i>
                <div><strong>Pendaftaran Gagal:</strong><br><?= htmlspecialchars($error) ?></div>
            </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" novalidate id="frmPMB">
                <?= csrf_input() ?>

                <!-- =============================== BAGIAN 1: KEPENDUDUKAN =============================== -->
                <div class="section-divider"><span class="step-badge">1</span> Data Kependudukan & Pribadi</div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">NIK (16 Digit) <span class="required-mark">*wajib DIKTI</span></label>
                                <input type="text" name="nik" class="form-control" placeholder="Masukkan 16 digit NIK KTP" pattern="\d{16}" maxlength="16" required value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>">
                                <div class="form-text text-danger small">Pastikan NIK sesuai Dukcapil. Salah NIK = gagal sinkron PDDikti.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">NISN <span class="required-mark">*</span></label>
                                <input type="text" name="nisn" class="form-control" placeholder="Nomor Induk Siswa Nasional" required value="<?= htmlspecialchars($_POST['nisn'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Nama Lengkap (sesuai Ijazah) <span class="required-mark">*</span></label>
                                <input type="text" name="nama" class="form-control form-control-lg" placeholder="Cth: Ahmad Fulan Al-Mahdi" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Tempat Lahir <span class="required-mark">*</span></label>
                                <input type="text" name="tempat_lahir" class="form-control" required value="<?= htmlspecialchars($_POST['tempat_lahir'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Tanggal Lahir <span class="required-mark">*</span></label>
                                <input type="date" name="tgl_lahir" class="form-control" required value="<?= htmlspecialchars($_POST['tgl_lahir'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Jenis Kelamin <span class="required-mark">*</span></label>
                                <select name="jk" class="form-select" required>
                                    <option value="" disabled selected>Pilih</option>
                                    <option value="L" <?= ($_POST['jk']??'')==='L'?'selected':'' ?>>Laki-laki</option>
                                    <option value="P" <?= ($_POST['jk']??'')==='P'?'selected':'' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Agama <span class="required-mark">*</span></label>
                                <select name="agama" class="form-select" required>
                                    <?php foreach(['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                                    <option value="<?= $ag ?>" <?= ($_POST['agama']??'')===$ag?'selected':'' ?>><?= $ag ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Jenis Tinggal <span class="required-mark">*Wajib DIKTI</span></label>
                                <select name="jenis_tinggal" class="form-select">
                                    <option value="">- Pilih -</option>
                                    <option value="Bersama Orang Tua">Bersama Orang Tua</option>
                                    <option value="Kontrak/Kos">Kontrak / Kos</option>
                                    <option value="Asrama">Asrama Kampus</option>
                                    <option value="Rumah Sendiri">Rumah Sendiri</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Alat Transportasi ke Kampus <span class="required-mark">*Wajib DIKTI</span></label>
                                <select name="alat_transportasi" class="form-select">
                                    <option value="">- Pilih -</option>
                                    <option value="Jalan Kaki">Jalan Kaki</option>
                                    <option value="Sepeda">Sepeda</option>
                                    <option value="Sepeda Motor">Sepeda Motor</option>
                                    <option value="Mobil Pribadi">Mobil Pribadi</option>
                                    <option value="Angkutan Umum">Angkutan Umum</option>
                                    <option value="Ojek Online">Ojek / Ride Sharing</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =============================== BAGIAN 2: ALAMAT =============================== -->
                <div class="section-divider"><span class="step-badge">2</span> Alamat Domisili (Rinci)</div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Alamat Lengkap (Jalan/Dusun) <span class="required-mark">*</span></label>
                                <input type="text" name="alamat" class="form-control" placeholder="Jl. Raya No. 10, Dusun Tani" required value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>">
                            </div>
                            <div class="col-md-2"><label class="form-label fw-semibold small">RT</label><input type="text" name="rt" class="form-control" maxlength="4" placeholder="001" value="<?= htmlspecialchars($_POST['rt'] ?? '') ?>"></div>
                            <div class="col-md-2"><label class="form-label fw-semibold small">RW</label><input type="text" name="rw" class="form-control" maxlength="4" placeholder="002" value="<?= htmlspecialchars($_POST['rw'] ?? '') ?>"></div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Kelurahan/Desa</label>
                                <input type="text" name="kelurahan" class="form-control" value="<?= htmlspecialchars($_POST['kelurahan'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Kecamatan</label>
                                <input type="text" name="kecamatan" class="form-control" value="<?= htmlspecialchars($_POST['kecamatan'] ?? '') ?>">
                            </div>
                            <div class="col-md-3"><label class="form-label fw-semibold small">Kode Pos</label><input type="text" name="kode_pos" class="form-control" maxlength="5" placeholder="23000" value="<?= htmlspecialchars($_POST['kode_pos'] ?? '') ?>"></div>
                        </div>
                    </div>
                </div>

                <!-- =============================== BAGIAN 3: ORANG TUA =============================== -->
                <div class="section-divider"><span class="step-badge">3</span> Data Orang Tua / Wali (Standar PDDikti)</div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nama Lengkap Ayah Kandung <span class="required-mark">*</span></label>
                                <input type="text" name="nama_ayah" class="form-control" required value="<?= htmlspecialchars($_POST['nama_ayah'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nama Lengkap Ibu Kandung <span class="required-mark">*</span></label>
                                <input type="text" name="nama_ibu" class="form-control" required value="<?= htmlspecialchars($_POST['nama_ibu'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">NIK Ayah (jika ada)</label>
                                <input type="text" name="nik_ayah" class="form-control" maxlength="16" placeholder="Opsional" value="<?= htmlspecialchars($_POST['nik_ayah'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">NIK Ibu (jika ada)</label>
                                <input type="text" name="nik_ibu" class="form-control" maxlength="16" placeholder="Opsional" value="<?= htmlspecialchars($_POST['nik_ibu'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Pend. Terakhir Ayah <span class="required-mark">*DIKTI</span></label>
                                <select name="pendidikan_ayah" class="form-select">
                                    <option value="">-</option>
                                    <?php foreach(['SD','SMP','SMA/SMK','D3','S1','S2','S3','Tidak Sekolah'] as $pd): ?>
                                    <option value="<?= $pd ?>"><?= $pd ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Pend. Terakhir Ibu <span class="required-mark">*DIKTI</span></label>
                                <select name="pendidikan_ibu" class="form-select">
                                    <option value="">-</option>
                                    <?php foreach(['SD','SMP','SMA/SMK','D3','S1','S2','S3','Tidak Sekolah'] as $pd): ?>
                                    <option value="<?= $pd ?>"><?= $pd ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Pekerjaan Ayah</label>
                                <input type="text" name="pekerjaan_ayah" class="form-control" placeholder="PNS/Petani/Wiraswasta..." value="<?= htmlspecialchars($_POST['pekerjaan_ayah'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Pekerjaan Ibu</label>
                                <input type="text" name="pekerjaan_ibu" class="form-control" placeholder="IRT/PNS/Wiraswasta..." value="<?= htmlspecialchars($_POST['pekerjaan_ibu'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Penghasilan Orang Tua / Bulan <span class="required-mark">*DIKTI</span></label>
                                <select name="penghasilan_ortu" class="form-select">
                                    <option value="">- Pilih Kisaran -</option>
                                    <option value="< 500.000">Kurang dari Rp 500.000</option>
                                    <option value="500.000 - 999.999">Rp 500.000 – Rp 999.999</option>
                                    <option value="1.000.000 - 1.999.999">Rp 1.000.000 – Rp 1.999.999</option>
                                    <option value="2.000.000 - 4.999.999">Rp 2.000.000 – Rp 4.999.999</option>
                                    <option value="5.000.000 - 9.999.999">Rp 5.000.000 – Rp 9.999.999</option>
                                    <option value="> 10.000.000">Lebih dari Rp 10.000.000</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =============================== BAGIAN 4: PENDIDIKAN & PILIHAN PRODI =============================== -->
                <div class="section-divider"><span class="step-badge">4</span> Riwayat Pendidikan & Pilihan Prodi</div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email Aktif <span class="required-mark">*</span></label>
                                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">No. HP / WhatsApp Aktif <span class="required-mark">*</span></label>
                                <input type="text" name="no_hp" class="form-control" required value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small">Asal Sekolah (SMA/SMK/MA) <span class="required-mark">*</span></label>
                                <input type="text" name="asal_sekolah" class="form-control" placeholder="Cth: SMAN 1 Lhokseumawe" required value="<?= htmlspecialchars($_POST['asal_sekolah'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Tahun Lulus <span class="required-mark">*</span></label>
                                <input type="number" name="tahun_lulus" class="form-control" min="2000" max="<?= date('Y')+1 ?>" placeholder="<?= date('Y') ?>" required value="<?= htmlspecialchars($_POST['tahun_lulus'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small">Program Studi Tujuan <span class="required-mark">*</span></label>
                                <select name="id_prodi" class="form-select form-select-lg" required>
                                    <option value="" disabled selected>— Pilih Program Studi —</option>
                                    <?php foreach ($prodi as $p): ?>
                                    <option value="<?= $p['id_prodi'] ?>" <?= ($_POST['id_prodi']??'')==$p['id_prodi']?'selected':'' ?>><?= htmlspecialchars($p['nama_prodi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Jalur Pendaftaran</label>
                                <select name="jalur_masuk" class="form-select">
                                    <?php if(count($jalur_list)): foreach ($jalur_list as $jl): ?>
                                    <option value="<?= htmlspecialchars($jl['nama_jalur']) ?>"><?= htmlspecialchars($jl['nama_jalur']) ?></option>
                                    <?php endforeach; else: ?>
                                    <option value="Reguler">Reguler</option>
                                    <option value="Prestasi">Prestasi</option>
                                    <option value="Beasiswa">Beasiswa</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php if(count($aktif_gel)): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Gelombang Pendaftaran</label>
                                <select name="id_gelombang" class="form-select">
                                    <option value="">- Pilih Gelombang -</option>
                                    <?php foreach($aktif_gel as $g): ?>
                                    <option value="<?= $g['id_gelombang'] ?>"><?= htmlspecialchars($g['nama_gelombang']) ?> (s.d. <?= date('d M Y', strtotime($g['tgl_selesai'])) ?>) — Rp <?= number_format($g['biaya'],0,',','.') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- =============================== BAGIAN 5: UPLOAD BERKAS =============================== -->
                <div class="section-divider"><span class="step-badge">5</span> Unggah Berkas Persyaratan (Maks 3MB/Berkas)</div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <p class="text-muted small mb-4"><i class="bi bi-info-circle me-1"></i> Format yang diterima: PDF, JPG, PNG. Pastikan dokumen terbaca jelas.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-text text-primary me-1"></i>1. Ijazah / SKL <span class="required-mark">*</span></h6>
                                    <p class="small text-muted mb-2">Scan asli Ijazah atau SKL terakhir.</p>
                                    <input type="file" name="berkas_ijazah" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-person-vcard text-success me-1"></i>2. KTP Calon Mahasiswa <span class="required-mark">*</span></h6>
                                    <p class="small text-muted mb-2">Bagi belum punya KTP, gunakan Surat Keterangan dari Desa.</p>
                                    <input type="file" name="berkas_ktp" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-people text-warning me-1"></i>3. Kartu Keluarga (KK) <span class="required-mark">*</span></h6>
                                    <p class="small text-muted mb-2">Scan asli Kartu Keluarga terbaru.</p>
                                    <input type="file" name="berkas_kk" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-camera text-danger me-1"></i>4. Pas Foto Formal <span class="required-mark">*</span></h6>
                                    <p class="small text-muted mb-2">Background merah/biru, berpakaian rapi, tampak wajah jelas.</p>
                                    <input type="file" name="berkas_foto" class="form-control form-control-sm" accept=".jpg,.jpeg,.png" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-file-earmark-text text-success me-1"></i>5. Akta Kelahiran <small class="text-muted fw-normal">(Opsional)</small></h6>
                                    <p class="small text-muted mb-2">Dianjurkan untuk kelengkapan data PDDikti.</p>
                                    <input type="file" name="berkas_akte" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="upload-zone">
                                    <h6 class="fw-bold mb-1"><i class="bi bi-patch-check text-info me-1"></i>6. Kartu KIP / KKS / PKH <small class="text-muted fw-normal">(Opsional)</small></h6>
                                    <p class="small text-muted mb-2">Upload bagi keluarga penerima bantuan sosial.</p>
                                    <input type="file" name="berkas_kip_pkh" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =============================== DEKLARASI & SUBMIT =============================== -->
                <div class="alert alert-info border-0 rounded-4 d-flex gap-3 mb-4">
                    <i class="bi bi-shield-check-fill fs-4 text-primary flex-shrink-0"></i>
                    <div class="small">
                        <strong>Deklarasi Integritas:</strong> Dengan menekan tombol kirim, saya menyatakan bahwa seluruh data yang saya isikan adalah benar, valid, dan dapat dipertanggungjawabkan. Pemalsuan data merupakan pelanggaran yang dapat berakibat pembatalan status kemahasiswaan.
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg fw-bold rounded-pill shadow py-3">
                        <i class="bi bi-send-fill me-2"></i> Kirim Formulir Pendaftaran
                    </button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<footer class="bg-white border-top py-4 mt-5">
    <div class="container text-center text-muted small">
        <p class="mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($sys['nama_kampus']) ?> — Formulir PMB Standar PDDikti | Data Anda dilindungi sesuai kebijakan privasi kampus.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Client-side NIK validation feedback
document.querySelector('[name="nik"]')?.addEventListener('input', function() {
    const ok = /^\d{16}$/.test(this.value);
    this.classList.toggle('is-invalid', this.value.length === 16 && !ok);
    this.classList.toggle('is-valid', ok);
});
// Upload preview filenames & size validation
const MAX_SIZE = 3 * 1024 * 1024; // 3MB
document.querySelectorAll('input[type=file]').forEach(function(inp){
    inp.addEventListener('change', function(){
        const zone = this.closest('.upload-zone');
        let info = zone.querySelector('.file-info');
        if(!info){ 
            info = document.createElement('small'); 
            info.className='file-info d-block mt-1 fw-semibold'; 
            zone.appendChild(info); 
        }

        if(this.files[0]){
            const fileSize = this.files[0].size;
            if (fileSize > MAX_SIZE) {
                // Warning state
                info.className = 'file-info text-danger d-block mt-1 fw-semibold';
                info.textContent = '⚠️ Berkas terlalu besar: ' + (fileSize/(1024*1024)).toFixed(2) + ' MB (Maks 3MB)';
                this.value = ""; // Reset input
                zone.style.borderColor = "#ef4444";
                zone.style.background = "#fef2f2";
            } else {
                // Success state
                info.className = 'file-info text-success d-block mt-1 fw-semibold';
                info.textContent = '✅ ' + this.files[0].name + ' (' + (fileSize/1024).toFixed(0) + ' KB)';
                zone.style.borderColor = "var(--pmb-primary)";
                zone.style.background = "#f0fdf4";
            }
        } else {
            info.textContent = '';
            zone.style.borderColor = "#cbd5e1";
            zone.style.background = "#fafafa";
        }
    });
});
</script>
</body>
</html>
