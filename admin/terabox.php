<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$msg = '';

$cfg = teraboxLoadShareConfig();
$shareUrlCfg = $cfg['url'];
$sharePwdCfg = $cfg['pwd'];

// Simpan cookie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cookie'])) {
    $file = __DIR__ . '/../config.php';
    $content = file_get_contents($file);
    $ndus = $_POST['ndus'] ?? '';
    $jsToken = $_POST['js_token'] ?? '';
    $content = preg_replace("/define\('TERABOX_COOKIE_NDUS',\s*'[^']*'\)/", "define('TERABOX_COOKIE_NDUS', '" . addslashes($ndus) . "')", $content);
    $content = preg_replace("/define\('TERABOX_COOKIE_JS_TOKEN',\s*'[^']*'\)/", "define('TERABOX_COOKIE_JS_TOKEN', '" . addslashes($jsToken) . "')", $content);
    file_put_contents($file, $content);
    $msg = '<div class="alert success">Cookie Terabox berhasil disimpan.</div>';
}

// Simpan share URL config (untuk semi-auto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_share_config'])) {
    $url = trim($_POST['share_url'] ?? '');
    $pwd = trim($_POST['share_pwd'] ?? '');
    if ($url) {
        teraboxSaveShareConfig($url, $pwd);
        $_SESSION['terabox_last_share'] = $url;
        $_SESSION['terabox_last_pwd'] = $pwd;
        $msg = '<div class="alert success">Share URL disimpan.</div>';
    }
}

// Import share link (dengan PWD / extraction code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_share'])) {
    $shareUrl = trim($_POST['share_url'] ?? '');
    $pwd = trim($_POST['pwd'] ?? '');
    if ($shareUrl) {
        $result = teraboxImportVideos($shareUrl, $pwd);
        if (isset($result['error'])) {
            $msg = '<div class="alert error">' . htmlspecialchars($result['error']) . '</div>';
        } else {
            $msg = '<div class="alert success">' . htmlspecialchars($result['success']) . '</div>';
        }
    } else {
        $msg = '<div class="alert error">Masukkan URL share Terabox.</div>';
    }
}

// Transfer satu video ke LuluStream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_auto'])) {
    $fsId = $_POST['fs_id'] ?? '';
    $title = $_POST['title'] ?? '';
    if ($fsId) {
        $result = teraboxTransferToLulu($fsId, $title);
        if (isset($result['error'])) {
            $msg = '<div class="alert error">' . htmlspecialchars($result['error']) . '</div>';
        } else {
            $msg = '<div class="alert success">' . htmlspecialchars($result['success']) . '</div>';
        }
    }
}

// Transfer dengan manual URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_manual'])) {
    $fsId = $_POST['fs_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $dlink = trim($_POST['dlink'] ?? '');
    if ($fsId && $dlink) {
        $result = luluRemoteUpload($dlink);
        if ($result && isset($result['result']['filecode'])) {
            $fc = $result['result']['filecode'];
            $db = getDB();
            $db->prepare("UPDATE videos SET file_code = ?, source = 'lulustream', status = 'active', terabox_fs_id = '' WHERE file_code = ?")->execute([$fc, $fsId]);
            if ($title) updateVideo($fc, ['title' => $title]);
            $msg = '<div class="alert success">Berhasil transfer ke LuluStream! File Code: ' . htmlspecialchars($fc) . '</div>';
        } else {
            $msg = '<div class="alert error">Gagal upload ke LuluStream: ' . ($result['msg'] ?? 'Unknown error') . '</div>';
        }
    }
}

// Manual upload URL ke LuluStream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_manual_url'])) {
    $url = trim($_POST['manual_url'] ?? '');
    $title = trim($_POST['manual_title'] ?? '');
    if ($url) {
        $result = luluRemoteUpload($url);
        if ($result && isset($result['result']['filecode'])) {
            $fc = $result['result']['filecode'];
            $db = getDB();
            $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_code, title, source, status, created_at) VALUES (?, ?, 'lulustream', 'active', ?)");
            $stmt->execute([$fc, $title ?: basename($url), date('Y-m-d H:i:s')]);
            if ($title) updateVideo($fc, ['title' => $title]);
            $msg = '<div class="alert success">Berhasil upload ke LuluStream! File Code: ' . htmlspecialchars($fc) . '</div>';
        } else {
            $msg = '<div class="alert error">Gagal upload: ' . htmlspecialchars($result['msg'] ?? $result['error'] ?? 'Unknown error') . '</div>';
        }
    } else {
        $msg = '<div class="alert error">Masukkan URL video.</div>';
    }
}

// Ambil dlink via AJAX (semi-auto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_dlink_ajax'])) {
    header('Content-Type: application/json');
    $fsId = $_POST['fs_id'] ?? '';
    if (!$fsId) { echo json_encode(['error' => 'fs_id required']); exit; }
    $dlink = teraboxGetDlink($fsId);
    if ($dlink) {
        echo json_encode(['dlink' => $dlink]);
    } else {
        echo json_encode(['error' => 'Gagal mendapatkan link. Coba refresh share atau gunakan Manual URL.']);
    }
    exit;
}

// Hapus video Terabox dari DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_terabox'])) {
    $fsId = $_POST['fs_id'] ?? '';
    if ($fsId) {
        deleteVideo($fsId);
        $msg = '<div class="alert success">Video dihapus dari database.</div>';
    }
}

// Batch transfer ke LuluStream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_transfer'])) {
    $fsIds = $_POST['fs_ids'] ?? [];
    if (empty($fsIds)) {
        $msg = '<div class="alert error">Pilih video yang akan ditransfer.</div>';
    } else {
        $success = 0;
        $errors = [];
        foreach ($fsIds as $fsId) {
            $result = teraboxTransferToLulu($fsId);
            if (isset($result['error'])) {
                $errors[] = htmlspecialchars($fsId) . ': ' . htmlspecialchars($result['error']);
            } else {
                $success++;
            }
        }
        $msg = '<div class="alert success">Batch transfer selesai: ' . $success . ' berhasil';
        if (!empty($errors)) {
            $msg .= ', ' . count($errors) . ' gagal.<br>' . implode('<br>', array_slice($errors, 0, 10));
        }
        $msg .= '</div>';
    }
}

$db = getDB();

// Filter: tampilkan pending (default) atau semua
$showAll = isset($_GET['all']) ? true : false;
if ($showAll) {
    $teraboxVideos = $db->prepare("SELECT * FROM videos WHERE source = 'terabox' ORDER BY created_at DESC");
} else {
    $teraboxVideos = $db->prepare("SELECT * FROM videos WHERE source = 'terabox' AND status = 'pending' ORDER BY created_at DESC");
}
$teraboxVideos->execute();
$teraboxVideos = $teraboxVideos->fetchAll(PDO::FETCH_ASSOC);
$count = count($teraboxVideos);
$totalTerabox = $db->query("SELECT COUNT(*) FROM videos WHERE source = 'terabox'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Terabox - Admin <?= $site_title ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .terabox-file-list { margin-top: 20px; }
        .terabox-file-list table { width: 100%; border-collapse: collapse; }
        .terabox-file-list td, .terabox-file-list th { padding: 10px; border-bottom: 1px solid #2a2a2a; text-align: left; font-size: .85rem; }
        .terabox-file-list th { color: #888; font-weight: 600; }
        .inline-form { display: inline; }
        .btn-success { background: #28a745; color: #fff; border: none; padding: 4px 10px; border-radius: 3px; cursor: pointer; font-size: .78rem; }
        .btn-success:hover { background: #218838; }
        .btn-success:disabled { opacity: .6; cursor: wait; }
        .semi-auto-loading { background: #218838; }
        .dlink-input { background: #1a1a2e; color: #ccc; border: 1px solid #333; border-radius: 3px; }
    </style>
</head>
<body>

<nav class="navbar admin-nav" id="navbar">
    <div class="container">
        <a href="../index.php" class="brand"><?= $site_title ?></a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="videos.php">Kelola Video</a>
            <a href="terabox.php" class="active">Terabox</a>
            <a href="add.php">Tambah Video</a>
            <a href="sync.php">Sync</a>
            <a href="remote.php">Remote Upload</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <h1>Terabox Import</h1>
    <?= $msg ?>

    <div style="max-width:700px;margin-top:20px">
        <div class="admin-card">
            <h3>Cookie Terabox</h3>
            <p style="font-size:.85rem;color:#999;margin-bottom:15px">
                Buka terabox.com, login, lalu ambil cookie <code>ndus</code> dari DevTools (Application &rarr; Cookies).
            </p>
            <form method="POST">
                <div class="form-group">
                    <label>Cookie ndus</label>
                    <input type="text" name="ndus" value="<?= htmlspecialchars(TERABOX_COOKIE_NDUS) ?>" placeholder="Isi cookie ndus" style="width:100%">
                </div>
                <button type="submit" name="save_cookie" class="btn">Simpan Cookie</button>
            </form>
        </div>

        <div class="admin-card">
            <h3>Import dari Share Link</h3>
            <p style="font-size:.85rem;color:#999;margin-bottom:15px">
                Paste URL share Terabox. Extraction code (<code>pwd</code>) otomatis dideteksi atau isi manual.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label>URL Share Terabox</label>
                    <input type="text" name="share_url" placeholder="https://videyyy.com/s/1PFUcb2GBTgDdRyxx1_y6iw" style="width:100%">
                </div>
                <div class="form-group">
                    <label>Extraction Code (pwd) <small style="color:#666">- kosongi untuk auto</small></label>
                    <input type="text" name="pwd" placeholder="1" style="width:100%">
                </div>
                <button type="submit" name="import_share" class="btn">Import Video (Pending)</button>
            </form>
        </div>

        <div class="admin-card">
            <h3>Share URL untuk Semi-Auto</h3>
            <p style="font-size:.85rem;color:#999;margin-bottom:15px">
                URL share untuk mengambil direct link otomatis. Disimpan permanent.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label>URL Share</label>
                    <input type="text" name="share_url" value="<?= htmlspecialchars($shareUrlCfg) ?>" placeholder="https://videyyy.com/s/..." style="width:100%">
                </div>
                <div class="form-group">
                    <label>Extraction Code (pwd)</label>
                    <input type="text" name="share_pwd" value="<?= htmlspecialchars($sharePwdCfg) ?>" placeholder="1" style="width:100%">
                </div>
                <button type="submit" name="save_share_config" class="btn">Simpan</button>
                <?php if ($shareUrlCfg): ?>
                    <small style="color:#5cb85c;margin-left:10px">&#10003; Tersimpan</small>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h3>Manual Upload URL ke LuluStream</h3>
            <p style="font-size:.85rem;color:#999;margin-bottom:15px">
                Paste URL video langsung (dari mana saja) untuk diupload ke LuluStream.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label>URL Video</label>
                    <input type="text" name="manual_url" placeholder="https://example.com/video.mp4" style="width:100%" required>
                </div>
                <div class="form-group">
                    <label>Judul (opsional)</label>
                    <input type="text" name="manual_title" placeholder="Judul video" style="width:100%">
                </div>
                <button type="submit" name="upload_manual_url" class="btn">Upload ke LuluStream</button>
            </form>
        </div>
    </div>

    <?php if ($count > 0): ?>
    <div class="admin-card terabox-file-list">
        <h3>Video Terabox di Database (<?= $count ?> / total <?= $totalTerabox ?>)</h3>
        <p style="font-size:.85rem;color:#999;margin-bottom:15px">
            Video status <strong>pending</strong> belum tampil di publik. Transfer ke LuluStream untuk menayangkan.
            <?php if (!$showAll): ?>
                <a href="?all=1" style="color:#1da1f2">Tampilkan semua (<?= $totalTerabox ?>)</a>
            <?php else: ?>
                <a href="?" style="color:#1da1f2">Tampilkan pending saja</a>
            <?php endif; ?>
        </p>

        <!-- Batch transfer form -->
        <form method="POST" id="batchForm" onsubmit="return confirm('Transfer <?= $count ?> video ke LuluStream? Proses bisa memakan waktu lama.')">
            <button type="submit" name="batch_transfer" class="btn" style="margin-bottom:15px">Batch Transfer Semua ke LuluStream</button>
        </form>

        <table>
            <tr>
                <th style="width:30px"><input type="checkbox" id="selectAll" onchange="document.querySelectorAll('.video-select').forEach(c=>c.checked=this.checked)"></th>
                <th>File</th>
                <th>Ukuran</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Transfer</th>
                <th>Hapus</th>
            </tr>
            <?php foreach ($teraboxVideos as $v): ?>
            <tr>
                <td><input type="checkbox" class="video-select" form="batchForm" name="fs_ids[]" value="<?= htmlspecialchars($v['file_code']) ?>"></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?php if ($v['thumbnail']): ?>
                        <img src="<?= htmlspecialchars($v['thumbnail']) ?>" alt="" style="width:60px;height:34px;object-fit:cover;border-radius:2px;vertical-align:middle;margin-right:8px">
                    <?php endif; ?>
                    <?= htmlspecialchars($v['title']) ?>
                    <br><small style="color:#666"><?= htmlspecialchars($v['file_code']) ?></small>
                </td>
                <td><?= formatFileSize($v['size']) ?></td>
                <td><?= htmlspecialchars($v['category'] ?: '-') ?></td>
                <td>
                    <?php if ($v['status'] === 'pending'): ?>
                        <span style="color:#f0ad4e">Pending</span>
                    <?php elseif ($v['status'] === 'active'): ?>
                        <span style="color:#5cb85c">Active</span>
                    <?php else: ?>
                        <?= htmlspecialchars($v['status']) ?>
                    <?php endif; ?>
                </td>
                <td style="min-width:200px">
                    <div style="display:flex;gap:4px;margin-bottom:4px">
                        <form method="POST" class="inline-form" onsubmit="return confirm('Transfer video ini ke LuluStream?')">
                            <input type="hidden" name="fs_id" value="<?= htmlspecialchars($v['file_code']) ?>">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($v['title']) ?>">
                            <button type="submit" name="transfer_auto" class="btn btn-sm btn-primary">Auto</button>
                        </form>
                        <button class="btn btn-sm btn-success semi-auto-btn" data-fsid="<?= htmlspecialchars($v['file_code']) ?>" data-title="<?= htmlspecialchars($v['title']) ?>">Semi Auto</button>
                        <button class="btn btn-sm btn-secondary" onclick="var f=this.closest('td').querySelector('.manual-dlink');f.style.display=f.style.display=='none'?'flex':'none'">Manual</button>
                    </div>
                    <div class="manual-dlink" style="display:none;flex-direction:column;gap:4px">
                        <form method="POST" style="display:flex;gap:4px;width:100%">
                            <input type="hidden" name="fs_id" value="<?= htmlspecialchars($v['file_code']) ?>">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($v['title']) ?>">
                            <input type="text" name="dlink" class="dlink-input" placeholder="Paste atau auto-fetch" style="flex:1;font-size:.75rem;padding:4px 6px" readonly>
                            <button type="submit" name="transfer_manual" class="btn btn-sm btn-primary" style="white-space:nowrap">Upload</button>
                        </form>
                    </div>
                </td>
                <td>
                    <form method="POST" class="inline-form" onsubmit="return confirm('Hapus dari database?')">
                        <input type="hidden" name="fs_id" value="<?= htmlspecialchars($v['file_code']) ?>">
                        <button type="submit" name="delete_terabox" class="btn btn-sm btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</div>
<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});

document.querySelectorAll('.semi-auto-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var fsId = this.dataset.fsid;
        var title = this.dataset.title;
        var td = this.closest('td');
        var container = td.querySelector('.manual-dlink');
        var input = container.querySelector('.dlink-input');
        var uploadBtn = container.querySelector('[name="transfer_manual"]');

        btn.disabled = true;
        btn.textContent = 'Fetching...';
        input.value = 'Mengambil link...';
        container.style.display = 'flex';

        var formData = new FormData();
        formData.append('get_dlink_ajax', '1');
        formData.append('fs_id', fsId);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.dlink) {
                    input.value = data.dlink;
                    input.readOnly = false;
                    input.select();
                    btn.textContent = 'Semi Auto';
                    btn.disabled = false;
                } else {
                    input.value = data.error || 'Gagal mengambil link';
                    btn.textContent = 'Coba Lagi';
                    btn.disabled = false;
                }
            })
            .catch(function(err) {
                input.value = 'Error: ' + err.message;
                btn.textContent = 'Coba Lagi';
                btn.disabled = false;
            });
    });
});
</script>
</body>
</html>
