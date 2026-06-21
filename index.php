<?php require_once 'config.php'; ?>
<?php include 'config_ads.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site_title ?> - <?= $site_desc ?></title>
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

<div class="hero">
    <h1><?= $site_title ?></h1>
    <p><?= $site_desc ?></p>
    <form class="search-bar" method="GET">
        <input type="text" name="search" placeholder="Cari film, series, atau video..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <select name="category">
            <option value="">Semua</option>
            <?php foreach (getCategories() as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Cari</button>
    </form>
</div>

<div class="container">
    <?php
    $page = max(1, (int)($_GET['page'] ?? 1));
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $result = getVideos($category, $search, $page);
    ?>

    <?php if ($search || $category): ?>
        <h2 class="section-title">Hasil Pencarian</h2>
    <?php else: ?>
        <h2 class="section-title">Semua Video</h2>
    <?php endif; ?>

    <?= $ad_top_banner ?? '' ?>

    <div class="video-grid">
        <?php if (empty($result['videos'])): ?>
            <p class="no-videos">
                Belum ada video.
                <?php if (isAdmin()): ?>
                    <a href="admin/sync.php">Sinkronisasi dari LuluStream</a> atau <a href="admin/terabox.php">Import dari Terabox</a>
                <?php else: ?>
                    Hubungi admin untuk menambahkan video.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <?php $ad_placed = false; ?>
            <?php foreach ($result['videos'] as $i => $video): ?>
                <?php if ($i > 0 && $i % 4 === 0 && !$ad_placed): ?>
                    <?= $ad_mid_banner ?? '' ?>
                    <?php $ad_placed = true; ?>
                <?php endif; ?>
                <div class="video-card">
                    <a href="video.php?code=<?= htmlspecialchars($video['file_code']) ?>&source=<?= $video['source'] ?? 'lulustream' ?>">
                        <div class="thumb-wrap">
                            <img src="<?= htmlspecialchars($video['thumbnail'] ?: 'assets/no-thumb.svg') ?>"
                                 alt="<?= htmlspecialchars($video['title']) ?>"
                                 loading="lazy"
                                 onerror="this.src='assets/no-thumb.svg'">
                            <?php if (!empty($video['duration'])): ?>
                                <span class="duration-badge"><?= formatDuration($video['duration']) ?></span>
                            <?php endif; ?>
                            <?php if (($video['source'] ?? 'lulustream') === 'terabox'): ?>
                                <span class="source-badge terabox">Terabox</span>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <h3><?= htmlspecialchars($video['title']) ?></h3>
                            <div class="video-meta">
                                <span class="category"><?= htmlspecialchars($video['category']) ?></span>
                                <span class="views"><?= number_format((int)$video['views']) ?>x ditonton</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (($result['pages'] ?? 0) > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"
               class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
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
