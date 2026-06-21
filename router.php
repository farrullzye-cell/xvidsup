<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/config.php';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static files — serve directly
if (preg_match('/\.(css|js|png|jpg|gif|ico|svg|json|webp)$/', $uri) || $uri === '/sw.js') {
    return false;
}

// Hidden admin panel via secret path
$secretAdmin = '/' . ADMIN_SECRET_PATH;
if ($uri === $secretAdmin || strpos($uri, $secretAdmin . '/') === 0) {
    $relPath = substr($uri, strlen($secretAdmin));
    if (!$relPath || $relPath === '/') $relPath = '/index.php';
    $file = __DIR__ . '/admin' . $relPath;
    if (file_exists($file)) {
        require $file;
        return true;
    }
    require __DIR__ . '/admin/index.php';
    return true;
}

// Old /admin/ path still works for local convenience
if (strpos($uri, '/admin/') === 0) {
    $file = __DIR__ . $uri;
    if (file_exists($file)) {
        require $file;
        return true;
    }
    require __DIR__ . '/admin/index.php';
    return true;
}

// API endpoints (from app.php)
if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/app.php';
    return true;
}

// Uploader GUI (for admin, at /uploader)
if ($uri === '/uploader') {
    require __DIR__ . '/app.php';
    return true;
}

// Video page
if ($uri === '/video.php' || strpos($uri, '/video') === 0) {
    require __DIR__ . '/video.php';
    return true;
}

// Main site
require __DIR__ . '/index.php';
