<?php require_once 'config.php';
include 'config_ads.php';
$code = $_GET['code'] ?? '';
$source = $_GET['source'] ?? 'lulustream';
if (!$code) { header('Location: index.php'); exit; }

$video = getVideoByCode($code);

// Pending videos only accessible to admin
if ($video && $video['status'] === 'pending' && !isAdmin()) {
    header('Location: index.php'); exit;
}

if (!$video) {
    if ($source === 'terabox') {
        $video = ['file_code' => $code, 'source' => 'terabox', 'title' => 'Terabox Video', 'thumbnail' => '', 'views' => 0, 'duration' => 0, 'description' => '', 'category' => 'Uncategorized', 'created_at' => date('Y-m-d H:i:s')];
    } else {
        $info = luluGetVideoInfo($code);
        if ($info && isset($info['result'][0])) {
            $d = $info['result'][0];
            $video = ['file_code' => $d['file_code'], 'source' => 'lulustream', 'title' => $d['file_title'], 'thumbnail' => $d['player_img'] ?? '', 'views' => $d['file_views'] ?? 0, 'duration' => $d['file_length'] ?? '', 'description' => '', 'category' => 'Uncategorized', 'tags' => $d['tags'] ?? '', 'created_at' => $d['file_created'] ?? date('Y-m-d H:i:s')];
        } else {
            header('Location: index.php'); exit;
        }
    }
}
$source = $video['source'] ?? 'lulustream';
$embedUrl = getEmbedUrl($code, $source);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title'] ?? 'Video') ?> - <?= $site_title ?></title>
    <link rel="stylesheet" href="style.css">
    <?= $ad_header ?? '' ?>
</head>
<body>
<?= $ad_popunder ?? '' ?>

<nav class="navbar" id="navbar">
    <div class="container">
        <a href="index.php" class="brand"><?= $site_title ?></a>
        <div class="nav-links">
            <a href="index.php">Beranda</a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php">Admin Panel</a>
                <a href="admin/logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container video-page">
    <?= $ad_video_top ?? '' ?>
    
    <div class="video-player">
        <?php if ($source === 'terabox'): ?>
            <div style="background:rgba(255,255,255,.04);border:1px solid #2a2a2a;border-radius:4px;padding:40px;text-align:center">
                <p style="font-size:1.2rem;color:#999;margin-bottom:15px">Video ini disimpan di Terabox.</p>
                <p style="color:#666;margin-bottom:20px">Untuk menonton, buka link berikut di browser Anda (perlu login Terabox):</p>
                <a href="https://www.terabox.com/play/video?fs_id=<?= urlencode($video['file_code']) ?>" target="_blank" class="btn" style="background:#1da1f2;color:#fff;padding:12px 30px;text-decoration:none;border-radius:4px">Buka di Terabox</a>
                <p style="margin-top:15px;font-size:.8rem;color:#555">atau minta admin untuk transfer video ke LuluStream agar bisa diputar langsung.</p>
            </div>
        <?php else: ?>
            <iframe src="<?= htmlspecialchars($embedUrl) ?>"
                    allowfullscreen
                    frameborder="0"
                    width="100%"
                    height="500"
                    allow="autoplay"></iframe>
        <?php endif; ?>
    </div>

    <?= $ad_video_bottom ?? '' ?>
    
    <div class="video-details">
        <h1><?= htmlspecialchars($video['title']) ?></h1>
        <div class="meta">
            <span>Sumber: <?= $source === 'terabox' ? 'Terabox' : 'LuluStream' ?></span>
            <span>Kategori: <?= htmlspecialchars($video['category'] ?? 'Uncategorized') ?></span>
            <span>Ditonton: <?= number_format((int)($video['views'] ?? 0)) ?>x</span>
            <?php if (!empty($video['duration'])): ?>
            <span>Durasi: <?= formatDuration($video['duration']) ?></span>
            <?php endif; ?>
            <?php if (!empty($video['size'])): ?>
            <span>Ukuran: <?= formatFileSize($video['size']) ?></span>
            <?php endif; ?>
            <span>Ditambahkan: <?= date('d M Y', strtotime($video['created_at'] ?? 'now')) ?></span>
        </div>
        <?php if (!empty($video['tags'])): ?>
            <div style="margin-bottom:15px">
                <?php foreach (explode(',', $video['tags']) as $tag): $tag = trim($tag); if ($tag): ?>
                    <span style="background:rgba(255,255,255,.08);padding:3px 10px;border-radius:20px;font-size:0.8rem;margin-right:5px"><?= htmlspecialchars($tag) ?></span>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($video['description'])): ?>
        <div class="description"><?= nl2br(htmlspecialchars($video['description'])) ?></div>
        <?php endif; ?>
    </div>

    <?= $ad_video_sidebar ?? '' ?>
</div>

<?= $ad_bottom_banner ?? '' ?>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= $site_title ?>. All rights reserved.</p>
    </div>
</footer>

<script>
window.addEventListener('scroll', function() {
    var nav = document.getElementById('navbar');
    if (window.scrollY > 50) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
});
</script>
</body>
</html>
