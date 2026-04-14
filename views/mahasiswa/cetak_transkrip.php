<?php
// views/mahasiswa/cetak_transkrip.php
require_once '../../middleware/auth.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php'; // DomPDF
require_once '../../vendor/phpqrcode/qrlib.php';
use Dompdf\Dompdf;
if ($_SESSION['user']['role'] !== 'mahasiswa') {
    header('Location: dashboard.php');
    exit;
}
$id_mhs = $_SESSION['user']['id_mhs'] ?? $_SESSION['user']['id'];
$nama = '';
$stmt = $pdo->prepare('SELECT nama FROM mahasiswa WHERE id_mhs=?');
$stmt->execute([$id_mhs]);
if ($row = $stmt->fetch()) $nama = $row['nama'];
function getTranskrip($pdo, $id_mhs) {
    $sql = "SELECT mk.kode_mk, mk.nama_mk, mk.sks, n.nilai_angka, n.nilai_huruf, n.bobot_4_0 FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE k.id_mhs=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_mhs]);
    return $stmt->fetchAll();
}
function hitungIPK($pdo, $id_mhs) {
    $sql = "SELECT mk.sks, n.bobot_4_0 FROM krs k JOIN jadwal_kuliah jk ON k.id_jadwal=jk.id_jadwal JOIN mata_kuliah mk ON jk.id_mk=mk.id_mk LEFT JOIN nilai_akhir n ON n.id_krs=k.id_krs WHERE k.id_mhs=? AND n.bobot_4_0 IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_mhs]);
    $total_sks = $total_bobot = 0;
    foreach ($stmt as $row) {
        $total_sks += $row['sks'];
        $total_bobot += $row['bobot_4_0'] * $row['sks'];
    }
    return $total_sks ? round($total_bobot / $total_sks, 2) : 0;
}
$transkrip = getTranskrip($pdo, $id_mhs);
$ipk = hitungIPK($pdo, $id_mhs);

// Generate QR Code (with Cloud Fallback)
$verif_url = 'https://siakadstisdayahamal/verifikasi_transkrip.php?nim=' . urlencode($_SESSION['user']['username'] ?? '');
$qr_img = '';

if (class_exists('QRcode')) {
    $qr_temp = sys_get_temp_dir() . '/qrcode_' . md5($verif_url) . '.png';
    call_user_func(['QRcode', 'png'], $verif_url, $qr_temp, 'L', 4);
    if (file_exists($qr_temp)) {
        $qr_img = base64_encode(file_get_contents($qr_temp));
        @unlink($qr_temp);
    }
}

// Fallback to Cloud QR API if local library missing
if (empty($qr_img)) {
    $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verif_url);
    $api_content = @file_get_contents($api_url);
    if ($api_content) {
        $qr_img = base64_encode($api_content);
    }
}

$html = '<style>
body { font-family: Arial, sans-serif; }
.header { background: #0d6efd; color: #fff; padding: 16px; border-radius: 8px 8px 0 0; }
.transkrip-table th { background: #e3f0ff; color: #0d6efd; }
.transkrip-table td, .transkrip-table th { border: 1px solid #0d6efd; padding: 6px; }
.footer { margin-top: 24px; font-size: 12px; color: #555; }
</style>';
$html .= '
<div style="text-align:center; border-bottom:3px solid #000; padding-bottom:10px; margin-bottom:20px;">
    <h3 style="margin:0; font-size:16px; text-transform:uppercase;">Yayasan Dayah Al-Madinatul Munawwarah Al-waliyyah</h3>
    <h2 style="margin:5px 0; font-size:22px; color:#0d6efd;">SEKOLAH TINGGI ILMU SYARI`AH (STIS) DAYAH AMAL</h2>
    <p style="margin:0; font-size:12px;">Izin Operasional: Dj.I/Dt.I.IV/PP.00.9/1224/2014 | Akreditasi Institusi: BAIK</p>
    <p style="margin:2px 0 0 0; font-size:11px;">Jln. Medan – Banda Aceh Km. 371, Desa Beusa Seubrang, Peureulak Barat, Aceh Timur 24453 | Telp: 0852 2225 6200</p>
</div>
<div style="text-align:center; margin-bottom:20px;">
    <h3 style="margin:0; text-decoration:underline;">TRANSKRIP AKADEMIK SEMENTARA</h3>
</div>';
$qr_tag = $qr_img ? '<img src="data:image/png;base64,'.$qr_img.'" width="80"><br><span style="font-size:10px">Scan QR untuk verifikasi</span>' : '';
$html .= '<table width="100%" style="margin-bottom:16px"><tr><td><b>Nama:</b> '.htmlspecialchars($nama).'<br><b>NPM (NIM):</b> '.htmlspecialchars($_SESSION['user']['username'] ?? '').'</td><td align="right">'.$qr_tag.'</td></tr></table>';
$html .= '<table width="100%" class="transkrip-table" style="border-collapse:collapse; margin-bottom:12px"><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai Angka</th><th>Nilai Huruf</th></tr>';
foreach ($transkrip as $row) {
    $html .= '<tr><td>'.htmlspecialchars($row['kode_mk']).'</td><td>'.htmlspecialchars($row['nama_mk']).'</td><td>'.$row['sks'].'</td><td>'.$row['nilai_angka'].'</td><td>'.$row['nilai_huruf'].'</td></tr>';
}
$html .= '</table>';
$html .= '<b>IPK: <span style="color:#0d6efd">'.$ipk.'</span></b>';
$html .= '<table width="100%" style="margin-top:30px; font-size:14px;">
    <tr>
        <td width="60%"></td>
        <td width="40%" style="text-align:center;">
            Peureulak Barat, '.date('d F Y').'<br>
            Ketua STIS Dayah Amal,<br><br><br><br>
            <strong><u>Muhammad Juanis, Lc., M.Pd</u></strong>
        </td>
    </tr>
</table>';
$html .= '<div class="footer" style="text-align:center; margin-top:30px; line-height:1.4;"><i>Sistem Informasi Akademik Terpadu<br>Sekolah Tinggi Ilmu Syari`ah (STIS) Dayah Amal<br>Dokumen ini dicetak otomatis dari sistem dan dinyatakan sah.</i></div>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('transkrip_nilai.pdf', ['Attachment' => 0]);
exit;
