<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$code = $_GET['code'] ?? '';
if ($code) {
    deleteVideo($code);
    $_SESSION['flash'] = 'Video berhasil dihapus dari database lokal.';
}
header('Location: ' . adminUrl('videos.php'));
exit;
