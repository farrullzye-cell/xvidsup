<?php require_once __DIR__ . '/../config.php'; requireAdmin();

$message = '';
$error = '';
$syncedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test = luluAccountInfo();
    if (!$test) {
        $error = 'Gagal terhubung ke LuluStream API. Periksa API Key di config.php!';
    } elseif (!isset($test['result'])) {
        $error = 'Error: ' . ($test['msg'] ?? 'Invalid API Key');
    } else {
        $syncedCount = syncFromLulustream();
        $message = "Sinkronisasi berhasil! $syncedCount video & folder telah diimpor dari LuluStream.";
    }
}

$db = getDB();
$totalDb = $db->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$totalFolders = $db->query("SELECT COUNT(*) FROM folders")->fetchColumn();
$lastVideo = $db->query("SELECT title, updated_at FROM videos ORDER BY updated_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$apiCheck = luluAccountInfo();
$apiConnected = $apiCheck && isset($apiCheck['result']);
$apiLogin = $apiConnected ? ($apiCheck['result']['login'] ?? '-') : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi LuluStream - <?= $site_title ?></title>
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
            <a href="sync.php" class="active">Sync</a>
            <a href="remote.php">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Sinkronisasi LuluStream</h1>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <h3>Status API</h3>
            <p class="stat-number" style="font-size:1.2rem;color:<?= $apiConnected ? '#46d369' : '#f44336' ?>">
                <?= $apiConnected ? 'Terhubung' : 'Gagal' ?>
            </p>
            <small style="color:#888"><?= htmlspecialchars($apiLogin) ?></small>
        </div>
        <div class="stat-card">
            <h3>Video di DB</h3>
            <p class="stat-number"><?= $totalDb ?></p>
        </div>
        <div class="stat-card">
            <h3>Folder di DB</h3>
            <p class="stat-number"><?= $totalFolders ?></p>
        </div>
        <div class="stat-card">
            <h3>Video Terakhir</h3>
            <p class="stat-number" style="font-size:1rem"><?= $lastVideo ? htmlspecialchars(mb_substr($lastVideo['title'], 0, 30)) : '-' ?></p>
        </div>
    </div>

    <form method="POST" style="background:rgba(255,255,255,.04);padding:24px;border-radius:4px;border:1px solid #2a2a2a;margin-top:20px">
        <h3 style="margin-bottom:10px">Proses Sinkronisasi</h3>
        <p style="color:#888;font-size:0.9rem;margin-bottom:15px">
            Mengambil semua daftar video & folder dari akun LuluStream via API dan menyimpannya ke database lokal.
        </p>
        <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:1.1rem">Mulai Sinkronisasi</button>
    </form>
</div>

<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
