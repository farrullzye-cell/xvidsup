<?php
// Public router for Render - ONLY video pages
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static files
if (preg_match('/\.(css|js|png|jpg|gif|ico|svg|json|webp)$/', $uri)) {
    return false;
}

// Block admin, uploader, API
if (strpos($uri, '/admin') === 0 || strpos($uri, '/api/') === 0 || $uri === '/uploader' || $uri === '/app.php') {
    http_response_code(404);
    echo '404 Not Found';
    return true;
}

// Video page
if ($uri === '/video.php') {
    require __DIR__ . '/video.php';
    return true;
}

// Main page
require __DIR__ . '/index.php';
