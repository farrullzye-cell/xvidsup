<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$accInfo = luluAccountInfo();
$accStats = luluAccountStats(7);
$db = getDB();
$totalVideos = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(DISTINCT category) FROM videos")->fetchColumn();
$totalViews = $db->query("SELECT SUM(views) FROM videos")->fetchColumn();
$totalFolders = $db->query("SELECT COUNT(*) FROM folders")->fetchColumn();
$latestSync = $db->query("SELECT MAX(updated_at) FROM videos")->fetchColumn();
$teraboxCount = $db->query("SELECT COUNT(*) FROM videos WHERE source='terabox'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?= $site_title ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<nav class="navbar admin-nav" id="navbar">
    <div class="container">
        <a href="../index.php" class="brand"><?= $site_title ?></a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="videos.php">Kelola Video</a>
            <a href="terabox.php">Terabox</a>
            <a href="add.php">Tambah Video</a>
            <a href="sync.php">Sync</a>
            <a href="remote.php">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Dashboard Admin</h1>

    <div class="stats">
        <div class="stat-card">
            <h3>Total Video</h3>
            <p class="stat-number"><?= $totalVideos ?></p>
        </div>
        <div class="stat-card">
            <h3>Kategori</h3>
            <p class="stat-number"><?= $totalCategories ?></p>
        </div>
        <div class="stat-card">
            <h3>Folder</h3>
            <p class="stat-number"><?= $totalFolders ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Views</h3>
            <p class="stat-number"><?= number_format((int)$totalViews) ?></p>
        </div>
        <div class="stat-card" style="border-color:#1da1f2">
            <h3>Terabox</h3>
            <p class="stat-number"><?= $teraboxCount ?></p>
        </div>
    </div>

    <?php if ($accInfo && isset($accInfo['result'])): $acc = $accInfo['result']; ?>
    <div class="stats">
        <div class="stat-card">
            <h3>Akun</h3>
            <p style="color:#e0e0e0;font-size:1.1rem"><?= htmlspecialchars($acc['login'] ?? '-') ?></p>
            <small style="color:#888"><?= htmlspecialchars($acc['email'] ?? '-') ?></small>
        </div>
        <div class="stat-card">
            <h3>Saldo</h3>
            <p style="color:#46d369;font-size:1.5rem">$<?= htmlspecialchars($acc['balance'] ?? '0') ?></p>
        </div>
        <div class="stat-card">
            <h3>Total File</h3>
            <p class="stat-number"><?= $acc['files_total'] ?? '-' ?></p>
        </div>
        <div class="stat-card">
            <h3>Storage</h3>
            <p style="font-size:1rem"><?= formatFileSize($acc['storage_used'] ?? 0) ?></p>
            <small style="color:#888">Sisa: <?= formatFileSize($acc['storage_left'] ?? 0) ?></small>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($accStats && isset($accStats['result']) && count($accStats['result']) > 0): $today = $accStats['result'][0]; ?>
    <div style="background:rgba(255,255,255,.04);padding:20px;border-radius:4px;border:1px solid #2a2a2a;margin-bottom:20px">
        <h3 style="margin-bottom:10px">Statistik Hari Ini</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px">
            <div><small style="color:#888">Views</small><br><?= number_format((int)($today['views'] ?? 0)) ?></div>
            <div><small style="color:#888">Downloads</small><br><?= number_format((int)($today['downloads'] ?? 0)) ?></div>
            <div><small style="color:#888">Penghasilan</small><br><span style="color:#46d369">$<?= htmlspecialchars($today['profit_total'] ?? '0') ?></span></div>
            <div><small style="color:#888">Views Premium</small><br><?= number_format((int)($today['views_prem'] ?? 0)) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="quick-actions">
        <a href="videos.php" class="btn btn-secondary">Kelola Video</a>
        <a href="add.php" class="btn btn-primary">Tambah Video</a>
        <a href="terabox.php" class="btn btn-secondary" style="background:#1da1f2;color:#fff">Import Terabox</a>
        <a href="sync.php" class="btn btn-secondary">Sinkronisasi</a>
        <a href="remote.php" class="btn btn-secondary">Remote Upload</a>
    </div>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
