<?php
// router.php for PHP built-in server to add CORS and MIME headers for static assets
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$fullPath = __DIR__ . $uri;

// Serve static files with CORS and correct MIME for GLB/GLTF
if (preg_match('/\.(glb|gltf)$/i', $uri) && file_exists($fullPath)) {
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    if ($ext === 'glb') {
        header('Content-Type: model/gltf-binary');
    } elseif ($ext === 'gltf') {
        header('Content-Type: model/gltf+json');
    }
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    readfile($fullPath);
    exit;
}

// Let PHP handle everything else as usual
return false;