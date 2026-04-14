<?php
define('ACCESS', true);
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../views'));
$count = 0;
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'SIAKAD Enterprise') !== false) {
            $content = str_replace('SIAKAD Enterprise', 'STIS Dayah Amal', $content);
            file_put_contents($file->getPathname(), $content);
            $count++;
            echo "Branding updated on: " . $file->getFilename() . "\n";
        }
    }
}
echo "Total updated: $count files.\n";
