<?php
function injectSidebar($folder) {
    echo "Inspecting $folder...\n";
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        
        // Cek jika tidak ada sidebar.php
        if (strpos($content, 'includes/sidebar.php') === false && strpos($content, '<div class="container') !== false) {
            
            $wrapper = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-2">
HTML;
            
            $newContent = preg_replace('/<div class="container(?:[^"]*)">/', $wrapper, $content, 1, $count);
            
            if ($count > 0) {
                // Tambahkan penutup
                $scrPos = strrpos($newContent, '<script');
                if ($scrPos !== false) {
                    $newContent = substr_replace($newContent, "\n    </main>\n  </div>\n</div>\n", $scrPos, 0);
                } else {
                    $bodyPos = strpos($newContent, '</body>');
                    if ($bodyPos !== false) {
                        $newContent = substr_replace($newContent, "\n    </main>\n  </div>\n</div>\n", $bodyPos, 0);
                    }
                }
                
                file_put_contents($file, $newContent);
                echo "SUCCESS: Sidebar disuntikkan ke " . basename($file) . "\n";
            }
        }
    }
}

injectSidebar('dosen');
injectSidebar('mahasiswa');
echo "Selesai!\n";
?>
