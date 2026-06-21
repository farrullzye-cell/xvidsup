<?php
error_reporting(0);
ini_set('display_errors', '0');

// Router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve app.php for everything except static files
if (preg_match('/\.(css|js|png|jpg|gif|ico|svg|json|webp)$/', $uri) || $uri === '/sw.js') {
    return false;
}

require __DIR__ . '/app.php';
