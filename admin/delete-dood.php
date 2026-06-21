<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$code = $_GET['code'] ?? '';
if ($code) {
    // Hapus dari database lokal
    deleteVideo($code);
    $_SESSION['flash'] = 'Video dihapus dari database lokal. Untuk hapus dari LuluStream, lakukan via dashboard LuluStream.';
}
header('Location: videos.php');
exit;
