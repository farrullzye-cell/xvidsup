<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$code = $_GET['code'] ?? '';
$video = getVideoByCode($code);
if (!$video) {
    $_SESSION['flash'] = 'Video tidak ditemukan!';
    header('Location: videos.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?: 'Uncategorized');
    $tags     = trim($_POST['tags'] ?? '');

    if (!$title) { $error = 'Judul wajib diisi!'; }
    else {
        // Update lokal
        updateVideo($code, ['title' => $title, 'description' => $desc, 'category' => $category, 'tags' => $tags]);

        $isTerabox = ($video['source'] ?? 'lulustream') === 'terabox';
        if (!$isTerabox) {
            // Update di LuluStream via API
            $editData = ['title' => $title, 'description' => $desc, 'tags' => $tags];
            luluEditVideo($code, $editData);

            // Refresh dari API
            $info = luluGetVideoInfo($code);
            if ($info && isset($info['result'][0])) {
                $d = $info['result'][0];
                $db = getDB();
                $db->prepare("UPDATE videos SET views = ?, duration = ?, thumbnail = ?, updated_at = CURRENT_TIMESTAMP WHERE file_code = ?")
                   ->execute([$d['file_views'] ?? $d['file_views_full'] ?? 0, $d['file_length'] ?? '', $d['player_img'] ?? '', $code]);
            }
        }

        $success = 'Video berhasil diperbarui!';
        $video = getVideoByCode($code);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Video - <?= $site_title ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<nav class="navbar admin-nav" id="navbar">
    <div class="container">
        <a href="../index.php" class="brand"><?= $site_title ?></a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="videos.php">Kelola Video</a>
            <a href="terabox.php">Terabox</a>
            <a href="add.php">Tambah Video</a>
            <a href="sync.php">Sync</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Edit Video</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:30px">
        <div style="flex:1;min-width:300px">
            <form method="POST" class="form">
                <div class="form-group">
                    <label>File Code</label>
                    <input type="text" value="<?= htmlspecialchars($video['file_code']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Judul Video <span style="color:#f44336">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($video['title']) ?>" required>
                    <small>Juga akan rename di LuluStream</small>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($video['category']) ?>">
                </div>
                <div class="form-group">
                    <label>Tags (pisahkan dengan koma)</label>
                    <input type="text" name="tags" value="<?= htmlspecialchars($video['tags'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="4"><?= htmlspecialchars($video['description']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="videos.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
        <div style="flex:0 0 300px">
            <div style="background:rgba(255,255,255,.04);padding:15px;border-radius:4px;border:1px solid #2a2a2a">
                <h3 style="margin-bottom:10px">Info Video</h3>
                <?php if ($video['thumbnail']): ?>
                    <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="" style="width:100%;border-radius:2px;margin-bottom:10px">
                <?php endif; ?>
                <table style="width:100%;font-size:0.85rem">
                    <tr><td style="padding:4px 0;color:#888">Sumber</td><td><?= ($video['source'] ?? 'lulustream') === 'terabox' ? 'Terabox' : 'LuluStream' ?></td></tr>
                    <tr><td style="padding:4px 0;color:#888">Views</td><td><?= number_format((int)$video['views']) ?></td></tr>
                    <tr><td style="padding:4px 0;color:#888">Durasi</td><td><?= formatDuration($video['duration']) ?></td></tr>
                    <tr><td style="padding:4px 0;color:#888">Ditambahkan</td><td><?= date('d/m/Y', strtotime($video['created_at'])) ?></td></tr>
                </table>
                <div style="margin-top:10px;display:flex;gap:6px">
                    <a href="../video.php?code=<?= urlencode($video['file_code']) ?>&source=<?= $video['source'] ?? 'lulustream' ?>" target="_blank" class="btn btn-sm btn-secondary">Lihat</a>
                    <?php if (($video['source'] ?? 'lulustream') === 'terabox'): ?>
                        <a href="https://www.terabox.com/play/video?fs_id=<?= urlencode($video['file_code']) ?>" target="_blank" class="btn btn-sm btn-secondary">Buka di Terabox</a>
                    <?php else: ?>
                        <a href="https://lulustream.com/d/<?= urlencode($video['file_code']) ?>" target="_blank" class="btn btn-sm btn-secondary">Buka di LuluStream</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
