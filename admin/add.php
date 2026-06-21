<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileCode = trim($_POST['file_code'] ?? '');
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?: 'Uncategorized');
    $tags     = trim($_POST['tags'] ?? '');

    if (!$fileCode || !$title) {
        $error = 'File Code dan Judul wajib diisi!';
    } else {
        $info = luluGetVideoInfo($fileCode);
        if ($info && isset($info['result'][0])) {
            $d = $info['result'][0];
            $db = getDB();
            $stmt = $db->prepare("INSERT OR REPLACE INTO videos 
                (file_code, title, description, category, thumbnail, views, duration, fld_id, cat_id, public, tags, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fileCode, $title, $desc, $category,
                $d['player_img'] ?? '', (int)($d['file_views'] ?? $d['file_views_full'] ?? 0),
                $d['file_length'] ?? '', $d['file_fld_id'] ?? '0',
                $d['cat_id'] ?? '', $d['file_public'] ?? 1,
                $tags ?: ($d['tags'] ?? ''),
                $d['file_created'] ?? date('Y-m-d H:i:s')
            ]);
            $success = "Video <strong>" . htmlspecialchars($title) . "</strong> berhasil ditambahkan!";
        } else {
            $error = 'File code tidak ditemukan di LuluStream. Periksa kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Video - <?= $site_title ?></title>
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
            <a href="add.php" class="active">Tambah Video</a>
            <a href="sync.php">Sync</a>
            <a href="remote.php">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Tambah Video</h1>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST" class="form">
        <div class="form-group">
            <label>File Code <span style="color:#f44336">*</span></label>
            <input type="text" name="file_code" required placeholder="Contoh: fb5asfuj2snh">
            <small>Dari URL: <code>https://lulustream.com/d/fb5asfuj2snh</code></small>
        </div>
        <div class="form-group">
            <label>Judul Video <span style="color:#f44336">*</span></label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <input type="text" name="category" placeholder="Film, Seri, Tutorial, Music, dll">
        </div>
        <div class="form-group">
            <label>Tags (pisahkan dengan koma)</label>
            <input type="text" name="tags" placeholder="action, subtitle, 2024">
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="videos.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
