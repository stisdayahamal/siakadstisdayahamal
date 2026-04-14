<?php
$wrapper = <<<HTML
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">
HTML;

function restoreSidebar($folder) {
    echo "Inspecting $folder...\n";
    foreach (glob(__DIR__ . "/../views/$folder/*.php") as $file) {
        $content = file_get_contents($file);
        
        // Temukan file yang TIDAK memiliki sidebar sama sekali !!
        if (strpos($content, 'includes/sidebar.php') === false && strpos($content, '<body') !== false) {
            
            // Kita harus membungkus konten utama. Karena navbar ada di atas, konten utama biasanya dimulai dengan <div class="container-fluid">
            // Karena ini adalah regex, kita hanya mengganti kemunculan pertama SETELAH navbar
            
            // Cara teraman: split berdasarkan </nav>, lalu ganti <div class="container-fluid"> pertama di bagian bawah
            $parts = explode('</nav>', $content, 2);
            
            if (count($parts) === 2) {
                // Di bagian setelah </nav>, replace kemunculan pertama <div class="container-fluid">
                $parts[1] = preg_replace('/<div class="container-fluid">/', $wrapper, $parts[1], 1, $count);
                
                if ($count > 0) {
                    $content = implode('</nav>', $parts);
                    
                    // Pastikan penutup terpasang
                    if (strpos($content, '</main>') === false) {
                        $scrPos = strrpos($content, '<script');
                        if ($scrPos !== false) {
                            $content = substr_replace($content, "\n    </main>\n  </div>\n</div>\n", $scrPos, 0);
                        } else {
                            $bodyPos = strpos($content, '</body>');
                            if ($bodyPos !== false) {
                                $content = substr_replace($content, "\n    </main>\n  </div>\n</div>\n", $bodyPos, 0);
                            }
                        }
                    }
                    
                    file_put_contents($file, $content);
                    echo "RESTORED SIDEBAR: " . basename($file) . "\n";
                }
            } else {
                // Jika tidak ada nav, coba replace <div class="container-fluid"> pertama setelah body
                $parts = explode('<body', $content, 2);
                if (count($parts) === 2) {
                    $parts[1] = preg_replace('/<div class="container-fluid">/', $wrapper, $parts[1], 1, $count);
                    if ($count > 0) {
                        $content = implode('<body', $parts);
                        
                        if (strpos($content, '</main>') === false) {
                            $bodyPos = strpos($content, '</body>');
                            if ($bodyPos !== false) {
                                $content = substr_replace($content, "\n    </main>\n  </div>\n</div>\n", $bodyPos, 0);
                            }
                        }
                        
                        file_put_contents($file, $content);
                        echo "RESTORED SIDEBAR (No Nav): " . basename($file) . "\n";
                    }
                }
            }
        }
    }
}

restoreSidebar('dosen');
restoreSidebar('mahasiswa');
echo "Tuntas!\n";
?>
