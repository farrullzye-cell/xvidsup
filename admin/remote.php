<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$error = '';
$success = '';
$resultData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');
    $fldId = trim($_POST['fld_id'] ?? '');

    if (!$url) { $error = 'URL file wajib diisi!'; }
    elseif (!filter_var($url, FILTER_VALIDATE_URL)) { $error = 'URL tidak valid!'; }
    else {
        $result = luluRemoteUpload($url, $fldId);
        if ($result && isset($result['result']['filecode'])) {
            $fileCode = $result['result']['filecode'];
            $resultData = $result;
            $db = getDB();
            $title = basename(parse_url($url, PHP_URL_PATH));
            $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_code, title) VALUES (?, ?)");
            $stmt->execute([$fileCode, $title]);
            $success = "Upload remote berhasil! File Code: <strong>" . htmlspecialchars($fileCode) . "</strong>";
        } else {
            $error = 'Gagal upload: ' . ($result['msg'] ?? 'Unknown error');
        }
    }
}

$urlUploads = luluUrlUploads();
$available = $urlUploads['requests_available'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Upload - <?= $site_title ?></title>
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
            <a href="remote.php" class="active">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Remote Upload</h1>
    <p style="color:#888;margin-bottom:20px">Upload video ke LuluStream langsung dari URL file via <code>/upload/url</code></p>

    <div class="stats" style="margin-bottom:20px">
        <div class="stat-card">
            <h3>Remote Slots Tersedia</h3>
            <p class="stat-number"><?= htmlspecialchars($available) ?></p>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <?php if ($resultData): ?>
    <div style="background:rgba(70,211,105,.1);border:1px solid rgba(70,211,105,.2);padding:20px;border-radius:4px;margin-bottom:20px">
        <h3 style="color:#46d369;margin-bottom:10px">Hasil Upload</h3>
        <p>File Code: <code><?= htmlspecialchars($resultData['result']['filecode']) ?></code></p>
        <p>Status: <?= htmlspecialchars($resultData['msg']) ?></p>
        <div style="margin-top:10px">
            <a href="../video.php?code=<?= urlencode($resultData['result']['filecode']) ?>" class="btn btn-sm btn-secondary" target="_blank">Lihat Video</a>
            <a href="edit.php?code=<?= urlencode($resultData['result']['filecode']) ?>" class="btn btn-sm btn-secondary">Edit</a>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="form">
        <div class="form-group">
            <label>URL File <span style="color:#f44336">*</span></label>
            <input type="url" name="url" required placeholder="https://example.com/video.mp4">
            <small>Direct link ke file video (mp4, avi, mkv, dll)</small>
        </div>
        <div class="form-group">
            <label>Folder ID (opsional)</label>
            <input type="text" name="fld_id" placeholder="Kosongkan untuk root">
        </div>
        <button type="submit" class="btn btn-primary">Upload ke LuluStream</button>
        <a href="videos.php" class="btn btn-secondary">Batal</a>
    </form>

    <?php if ($urlUploads && isset($urlUploads['result'])): ?>
    <hr style="border-color:#2a2a2a;margin:30px 0">
    <h3 style="margin-bottom:15px">Daftar Remote Upload</h3>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>URL</th><th>Status</th><th>Progress</th><th>File Code</th></tr></thead>
        <tbody>
            <?php foreach ($urlUploads['result'] as $item): ?>
            <tr>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($item['remote_url']) ?></td>
                <td><span class="badge badge-<?= $item['status'] === 'PENDING' ? 'working' : ($item['status'] === 'DONE' ? 'active' : 'inactive') ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                <td><?= $item['progress'] ?>%</td>
                <td><code><?= htmlspecialchars($item['file_code'] ?: '-') ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
