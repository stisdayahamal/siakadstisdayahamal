<?php
function replaceNav($folder, $replacementHeader) {
    echo "Processing $folder...\n";
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        
        // Replace Nav
        $newContent = preg_replace('/<nav.*?<\/nav>/s', $replacementHeader, $content, 1, $count);
        
        // Consistency: ensure main has mt-2 like Admin pages
        $newContent = preg_replace('/<main class="([^"]*)mt-4([^"]*)">/', '<main class="$1mt-2$2">', $newContent);
        $newContent = preg_replace('/<main class="([^"]*)pt-3([^"]*)">/', '<main class="$1mt-2$2">', $newContent);

        file_put_contents($file, $newContent);
        if ($count) {
            echo "- Navbar diganti pada: " . basename($file) . "\n";
        }
    }
}

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

replaceNav('dosen', $dosenHeader);
replaceNav('mahasiswa', $mhsHeader);

echo "Selesai!\n";
?>
