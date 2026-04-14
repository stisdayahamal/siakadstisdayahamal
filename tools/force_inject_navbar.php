<?php
$dosenHeader = <<<'HTML'
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-person-video3 text-warning me-2"></i>SIAKAD Dosen
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>
HTML;

$mhsHeader = <<<'HTML'
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-mortarboard-fill text-warning me-2"></i>SIAKAD Mahasiswa
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? $_SESSION['user']['nama']) ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>
HTML;

function forceInjectNav($folder, $header) {
    echo "Scanning $folder...\n";
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        // Jika tidak ada tag nav, tapi ada body
        if (strpos($content, '<nav') === false && strpos($content, '<body') !== false) {
            $content = preg_replace('/<body([^>]*)>/i', "<body$1>\n" . $header, $content, 1);
            file_put_contents($file, $content);
            echo "INJECTED NAV: " . basename($file) . "\n";
        }
    }
}

forceInjectNav('dosen', $dosenHeader);
forceInjectNav('mahasiswa', $mhsHeader);
echo "Selesai!\n";
?>
