<?php
// tools/refactor_admin_layout.php
$dir = __DIR__ . '/../views/admin/';
$files = glob($dir . '*.php');

$newHeader = <<<'HTML'
<nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white px-3 mb-4 shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-shield-fill-check text-warning me-2"></i>SIAKAD Admin
        </a>
        <span class="navbar-text ms-auto text-dark fw-medium">
            Halo, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?> | 
            <a href="../../auth/logout.php" class="text-danger fw-bold text-decoration-none ms-2"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </span>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
HTML;

foreach ($files as $file) {
    if (basename($file) === 'dashboard.php') continue; // dashboard already has correct layout
    
    $content = file_get_contents($file);
    
    // Jika belum punya sidebar dan punya tag nav
    if (strpos($content, 'includes/sidebar.php') === false && strpos($content, '<nav') !== false) {
        
        // Regex untuk menemukan Nav sampai ke pembuka Container
        $pattern = '/<nav.*?<\/nav>\s*<div class="container(?:[^"]*)">/s';
        
        $newContent = preg_replace($pattern, $newHeader, $content, 1, $count);
        
        if ($count > 0) {
            // Tutup container main sebelum tag script Bootstrap/Javascript paling akhir (atau sebelum body)
            // Cari tag script terakhir untuk menaruh div penutup
            $scrPos = strrpos($newContent, '<script');
            if ($scrPos !== false) {
                // Sisipkan tag penutup sebelum script terakhir
                $newContent = substr_replace($newContent, "\n    </main>\n  </div>\n</div>\n", $scrPos, 0);
            } else {
                // Fallback jika tidak ada script, taruh sebelum </body>
                $bodyPos = strpos($newContent, '</body>');
                if ($bodyPos !== false) {
                    $newContent = substr_replace($newContent, "\n    </main>\n  </div>\n</div>\n", $bodyPos, 0);
                }
            }
            
            // Perbaiki include auth dan db jika masih ada auth yang kelewatan
            file_put_contents($file, $newContent);
            echo "SUCCESS: " . basename($file) . " layout refactored!\n";
        } else {
            echo "SKIP: " . basename($file) . " (Regex tidak cocok)\n";
        }
    } else {
        echo "SKIP: " . basename($file) . " (Sidebar sudah ada atau bukan layout utuh)\n";
    }
}
?>
