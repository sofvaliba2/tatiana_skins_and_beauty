<?php
header('Content-Type: text/plain; charset=utf-8');
$files = [];
foreach ($_FILES as $k => $f) {
    $files[$k] = ['name' => $f['name'], 'error' => $f['error'], 'size' => $f['size'], 'type' => $f['type']];
}
echo "METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n";
echo "CTYPE=" . ($_SERVER['CONTENT_TYPE'] ?? '') . "\n";
echo "POST=" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "\n";
echo "FILES=" . json_encode($files, JSON_UNESCAPED_UNICODE) . "\n";
