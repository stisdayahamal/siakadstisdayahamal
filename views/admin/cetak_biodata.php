<?php
// views/admin/cetak_biodata.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php'; // DomPDF
require_once '../../vendor/phpqrcode/qrlib.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$id_mhs = intval($_GET['id'] ?? 0);
if (!$id_mhs) die('ID Mahasiswa tidak valid.');

// Fetch Full Data
$sql = "SELECT m.*, p.nama_prodi, f.nama_fakultas 
        FROM mahasiswa m 
        LEFT JOIN prodi p ON m.id_prodi = p.id_prodi 
        LEFT JOIN fakultas f ON p.id_fakultas = f.id_fakultas
        WHERE m.id_mhs = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_mhs]);
$m = $stmt->fetch();

if (!$m) die('Data Mahasiswa tidak ditemukan.');

// Generate QR Code for Verification (with Cloud Fallback)
$verif_url = 'https://siakad.stisdayahamal.ac.id/verifikasi_biodata.php?id=' . $m['id_mhs'];
$qr_img = '';

// Step 1: Try local library if available
if (class_exists('QRcode')) {
    try {
        $qr_temp = sys_get_temp_dir() . '/qr_' . md5($verif_url) . '.png';
        call_user_func(['QRcode', 'png'], $verif_url, $qr_temp, 'L', 4);
        if (file_exists($qr_temp)) {
            $qr_img = base64_encode(file_get_contents($qr_temp));
            @unlink($qr_temp);
        }
    } catch (Exception $e) {
        // Silently fail to fallback
    }
}

// Step 2: Fallback to Cloud QR API (Secure & Reliable)
if (empty($qr_img)) {
    $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verif_url);
    $api_content = @file_get_contents($api_url);
    if ($api_content) {
        $qr_img = base64_encode($api_content);
    }
}

// HTML Construction
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-title { font-size: 16px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .kop-subtitle { font-size: 18px; font-weight: bold; color: #0d6efd; margin: 2px 0; }
        .kop-info { font-size: 9px; margin: 2px 0; }
        
        .title-doc { text-align: center; font-size: 14px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; text-transform: uppercase; }
        
        .section-title { background: #f0f0f0; padding: 5px 10px; font-weight: bold; margin-top: 15px; margin-bottom: 8px; border-left: 4px solid #0d6efd; }
        
        table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 4px 0; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .separator { width: 20px; text-align: center; }
        
        .footer { margin-top: 30px; }
        .qrcode { float: left; width: 80px; text-align: center; font-size: 8px; }
        .signature { float: right; width: 200px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="kop-title">Yayasan Dayah Al-Madinatul Munawwarah Al-waliyyah</p>
        <p class="kop-subtitle">SEKOLAH TINGGI ILMU SYARI`AH (STIS) DAYAH AMAL</p>
        <p class="kop-info">Izin Operasional: Dj.I/Dt.I.IV/PP.00.9/1224/2014 | Akreditasi Institusi: BAIK</p>
        <p class="kop-info">Jln. Medan – Banda Aceh Km. 371, Desa Beusa Seubrang, Peureulak Barat, Aceh Timur 24453 | Telp: 0852 2225 6200</p>
    </div>

    <div class="title-doc">Formulir Biodata Mahasiswa Terintegrasi</div>

    <div class="section-title">I. Identitas Pribadi</div>
    <table class="data-table">
        <tr><td class="label">NIM (Nomor Induk Mahasiswa)</td><td class="separator">:</td><td><b>'.($m['nim'] ?: '-').'</b></td></tr>
        <tr><td class="label">NIK (Sesuai KTP)</td><td class="separator">:</td><td>'.($m['nik'] ?: '-').'</td></tr>
        <tr><td class="label">Nama Lengkap</td><td class="separator">:</td><td style="text-transform:uppercase"><b>'.htmlspecialchars($m['nama']).'</b></td></tr>
        <tr><td class="label">Tempat, Tanggal Lahir</td><td class="separator">:</td><td>'.htmlspecialchars($m['tempat_lahir']).', '.date('d-m-Y', strtotime($m['tgl_lahir'])).'</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="separator">:</td><td>'.($m['jk'] == 'L' ? 'Laki-laki' : 'Perempuan').'</td></tr>
        <tr><td class="label">Agama</td><td class="separator">:</td><td>'.htmlspecialchars($m['agama']).'</td></tr>
        <tr><td class="label">Email</td><td class="separator">:</td><td>'.htmlspecialchars($m['email'] ?: '-').'</td></tr>
        <tr><td class="label">No. WhatsApp / HP</td><td class="separator">:</td><td>'.htmlspecialchars($m['no_hp'] ?: '-').'</td></tr>
    </table>

    <div class="section-title">II. Alamat & Tempat Tinggal</div>
    <table class="data-table">
        <tr><td class="label">Alamat Lengkap</td><td class="separator">:</td><td>'.htmlspecialchars($m['alamat'] ?: '-').'</td></tr>
        <tr><td class="label">RT / RW</td><td class="separator">:</td><td>'.htmlspecialchars($m['rt'] ?: '-').' / '.htmlspecialchars($m['rw'] ?: '-').'</td></tr>
        <tr><td class="label">Kelurahan</td><td class="separator">:</td><td>'.htmlspecialchars($m['kelurahan'] ?: '-').'</td></tr>
        <tr><td class="label">Kecamatan</td><td class="separator">:</td><td>'.htmlspecialchars($m['kecamatan'] ?: '-').'</td></tr>
        <tr><td class="label">Kode Pos</td><td class="separator">:</td><td>'.htmlspecialchars($m['kode_pos'] ?: '-').'</td></tr>
        <tr><td class="label">Jenis Tinggal</td><td class="separator">:</td><td>'.htmlspecialchars($m['jenis_tinggal'] ?: '-').'</td></tr>
        <tr><td class="label">Alat Transportasi</td><td class="separator">:</td><td>'.htmlspecialchars($m['alat_transportasi'] ?: '-').'</td></tr>
    </table>

    <div class="section-title">III. Data Orang Tua / Wali</div>
    <table class="data-table">
        <tr><td class="label">Nama Ayah Kandung</td><td class="separator">:</td><td>'.htmlspecialchars($m['nama_ayah'] ?: '-').'</td></tr>
        <tr><td class="label">NIK Ayah</td><td class="separator">:</td><td>'.htmlspecialchars($m['nik_ayah'] ?: '-').'</td></tr>
        <tr><td class="label">Pekerjaan Ayah</td><td class="separator">:</td><td>'.htmlspecialchars($m['pekerjaan_ayah'] ?: '-').'</td></tr>
        <tr><td class="label">Nama Ibu Kandung</td><td class="separator">:</td><td>'.htmlspecialchars($m['nama_ibu'] ?: '-').'</td></tr>
        <tr><td class="label">NIK Ibu</td><td class="separator">:</td><td>'.htmlspecialchars($m['nik_ibu'] ?: '-').'</td></tr>
        <tr><td class="label">Pekerjaan Ibu</td><td class="separator">:</td><td>'.htmlspecialchars($m['pekerjaan_ibu'] ?: '-').'</td></tr>
        <tr><td class="label">Penghasilan Orang Tua</td><td class="separator">:</td><td>'.htmlspecialchars($m['penghasilan_ortu'] ?: '-').'</td></tr>
    </table>

    <div class="section-title">IV. Riwayat Pendidikan & Akademik</div>
    <table class="data-table">
        <tr><td class="label">Asal Sekolah</td><td class="separator">:</td><td>'.htmlspecialchars($m['asal_sekolah'] ?: '-').'</td></tr>
        <tr><td class="label">Tahun Lulus Sekolah</td><td class="separator">:</td><td>'.($m['tahun_lulus'] ?: '-').'</td></tr>
        <tr><td class="label">Program Studi</td><td class="separator">:</td><td><b>'.htmlspecialchars($m['nama_prodi']).'</b></td></tr>
        <tr><td class="label">Jalur Masuk</td><td class="separator">:</td><td>'.($m['jalur_masuk'] ?: '-').'</td></tr>
        <tr><td class="label">Tahun Masuk Kampus</td><td class="separator">:</td><td>'.($m['tahun_masuk'] ?: '-').'</td></tr>
        <tr><td class="label">Status Perkuliahan Saat Ini</td><td class="separator">:</td><td><b>'.($m['status_kuliah'] ?: '-').'</b></td></tr>
    </table>

    <div class="footer">
        <div class="qrcode">
            <img src="data:image/png;base64,'.$qr_img.'" width="70"><br>
            Verifikasi Digital
        </div>
        <div class="signature">
            Peureulak Barat, '.date('d F Y').'<br>
            Ketua STIS Dayah Amal,<br><br><br><br>
            <b><u>Muhammad Juanis, Lc., M.Pd</u></b><br>
            NIP/NIDN. -
        </div>
        <div style="clear:both"></div>
    </div>

    <div style="margin-top: 30px; font-size: 9px; text-align: center; color: #888; border-top: 1px solid #ddd; padding-top: 5px;">
        Dicetak secara otomatis melalui Sistem Informasi Akademik Terpadu (SIAKAD) STIS Dayah Amal pada '.date('d/m/Y H:i').'
    </div>
</body>
</html>';

// Dompdf Setup
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Biodata_Mahasiswa_" . ($m['nim'] ?: $m['id_mhs']) . ".pdf";
$dompdf->stream($filename, ["Attachment" => 0]);
exit;
