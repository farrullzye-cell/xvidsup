<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$result = getVideos('', $search, $page, 20);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Video - <?= $site_title ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php
$sourceFilter = $_GET['source'] ?? '';
$result = getVideos('', $search, $page, 20, $sourceFilter);
?>

<nav class="navbar admin-nav" id="navbar">
    <div class="container">
        <a href="../index.php" class="brand"><?= $site_title ?></a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="videos.php" class="active">Kelola Video</a>
            <a href="terabox.php">Terabox</a>
            <a href="add.php">Tambah Video</a>
            <a href="sync.php">Sync</a>
            <a href="remote.php">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Kelola Video</h1>

    <form class="search-bar" method="GET">
        <input type="text" name="search" placeholder="Cari video..." value="<?= htmlspecialchars($search) ?>">
        <select name="source">
            <option value="">Semua Sumber</option>
            <option value="lulustream" <?= $sourceFilter === 'lulustream' ? 'selected' : '' ?>>LuluStream</option>
            <option value="videy" <?= $sourceFilter === 'videy' ? 'selected' : '' ?>>Videy</option>
            <option value="terabox" <?= $sourceFilter === 'terabox' ? 'selected' : '' ?>>Terabox</option>
        </select>
        <button type="submit">Cari</button>
        <a href="videos.php" class="btn btn-secondary">Reset</a>
    </form>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-success"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Thumb</th>
                <th>File Code</th>
                <th>Judul</th>
                <th>Sumber</th>
                <th>Kategori</th>
                <th>Views</th>
                <th>Durasi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($result['videos'])): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#666">Belum ada video. <a href="sync.php">Sinkronisasi dari LuluStream</a> atau <a href="terabox.php">Import dari Terabox</a></td></tr>
            <?php else: ?>
                <?php foreach ($result['videos'] as $video): ?>
                <tr>
                    <td>
                        <img src="<?= htmlspecialchars($video['thumbnail'] ?: '../assets/no-thumb.svg') ?>"
                             alt="" width="80" height="45" style="object-fit:cover;border-radius:2px"
                             onerror="this.src='../assets/no-thumb.svg'">
                    </td>
                    <td><code><?= htmlspecialchars($video['file_code']) ?></code></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($video['title']) ?>
                    </td>
                    <td><span class="source-badge <?= $video['source'] ?? 'lulustream' ?>"><?= ($src = $video['source'] ?? 'lulustream') === 'terabox' ? 'Terabox' : ($src === 'videy' ? 'Videy' : 'LuluStream') ?></span></td>
                    <td><?= htmlspecialchars($video['category']) ?></td>
                    <td><?= number_format((int)$video['views']) ?></td>
                    <td><?= formatDuration($video['duration']) ?></td>
                    <td class="actions">
                        <a href="edit.php?code=<?= urlencode($video['file_code']) ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <a href="delete.php?code=<?= urlencode($video['file_code']) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Hapus dari database lokal?')">Hapus</a>
                        <a href="../video.php?code=<?= urlencode($video['file_code']) ?>&source=<?= $video['source'] ?? 'lulustream' ?>" class="btn btn-sm btn-secondary" target="_blank">Lihat</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php if (($result['pages'] ?? 0) > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&source=<?= urlencode($sourceFilter) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
