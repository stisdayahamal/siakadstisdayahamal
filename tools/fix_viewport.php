<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../views'));
$added = 0;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());
        if (strpos($content, '<head>') !== false && stripos($content, 'name="viewport"') === false) {
            $content = str_replace('<head>', '<head>' . "\n" . '    <meta name="viewport" content="width=device-width, initial-scale=1.0">', $content);
            file_put_contents($file->getRealPath(), $content);
            $added++;
        }
    }
}
echo "Added viewport to $added files.\n";
