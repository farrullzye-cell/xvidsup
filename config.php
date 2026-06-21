<?php
session_start();

$site_title = "XVIDSUP";
$site_desc  = "Koleksi Video Terlengkap";

// ========== LULUSTREAM API ==========
define('LULU_API_KEY', getenv('LULU_API_KEY') ?: '294910gde0nqjw8ng4a9bx');
define('LULU_BASE_URL', 'https://api.lulustream.com/api');

// ========== VIDEY.CO API ==========
define('VIDEY_UPLOAD_URL', 'https://videy.co/api/upload');

// ========== TERABOX CONFIG ==========
define('TERABOX_COOKIE_NDUS', getenv('TERABOX_COOKIE_NDUS') ?: 'YfkvlXPpeHuiN8AQF4sING36R-dQKzB-_WdjtwRc');
define('TERABOX_COOKIE_JS_TOKEN', getenv('TERABOX_COOKIE_JS_TOKEN') ?: '');
define('TERABOX_APP_ID', '250528');
define('TERABOX_COOKIE_BROWSERID', getenv('TERABOX_COOKIE_BROWSERID') ?: 'oQPOWtflE7yiMQzN3X9y2B7wVZuyb-5UyAPsCpGM00nm3pwyDGMO7xpJ0Dw9zNkCOEM3KPLJbY1reEIv');
define('TERABOX_COOKIE_CSRFTOKEN', getenv('TERABOX_COOKIE_CSRFTOKEN') ?: '829XeygDP-hMNYD7_XHe6-7j');
define('TERABOX_COOKIE_NDUT_FMT', getenv('TERABOX_COOKIE_NDUT_FMT') ?: 'F6F88FA7D70F89307C47ECD41AB16B944640B17F95EE9C1580103B26EDA24081');
define('TERABOX_COOKIE_NDUT_FMV', getenv('TERABOX_COOKIE_NDUT_FMV') ?: 'f6f88fa7d70f89307c47ecd41ab16b94e0082e9f4c3e8a520f59347be6170de241ae26320dc7b3bd32de0dbc51f53f7e6813fd8f87c18938a49c89d70cfda1be3053369aef1334dbef7fa84e8f8fbeb2d3e8b4249c5fa26c29ce1aa3d8a32f23f96982868d2228603c8b2201f1679a20');

// ========== ADMIN LOGIN ==========
define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
define('ADMIN_PASS', getenv('ADMIN_PASS') ?: 'admin123');
define('ADMIN_SECRET_PATH', getenv('ADMIN_SECRET_PATH') ?: 'manage');

define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/database.sqlite');
date_default_timezone_set('Asia/Jakarta');

// ========== HTTP HELPER ==========
function apiRequest($url, $headers = [], $method = 'GET', $postData = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return false;
    return json_decode($response, true);
}

function luluRequest($endpoint, $params = []) {
    $params['key'] = LULU_API_KEY;
    return apiRequest(LULU_BASE_URL . $endpoint . '?' . http_build_query($params));
}

// ========== LULUSTREAM API FUNCTIONS ==========

// GET /account/info - Info akun
function luluAccountInfo() {
    return luluRequest('/account/info');
}

// GET /account/stats - Statistik (last = hari)
function luluAccountStats($last = 7) {
    return luluRequest('/account/stats', ['last' => $last]);
}

// GET /file/list - Daftar file
function luluGetVideos($page = 1, $perPage = 50, $fldId = '') {
    $params = ['page' => $page, 'per_page' => $perPage];
    if ($fldId) $params['fld_id'] = $fldId;
    return luluRequest('/file/list', $params);
}

// GET /file/info - Info file
function luluGetVideoInfo($fileCode) {
    return luluRequest('/file/info', ['file_code' => $fileCode]);
}

// GET /file/edit - Edit file (rename, ganti deskripsi, dll)
function luluEditVideo($fileCode, $data) {
    $params = ['file_code' => $fileCode];
    if (isset($data['title'])) $params['file_title'] = $data['title'];
    if (isset($data['description'])) $params['file_descr'] = $data['description'];
    if (isset($data['cat_id'])) $params['cat_id'] = $data['cat_id'];
    if (isset($data['fld_id'])) $params['file_fld_id'] = $data['fld_id'];
    if (isset($data['public'])) $params['file_public'] = $data['public'];
    if (isset($data['tags'])) $params['tags'] = $data['tags'];
    return luluRequest('/file/edit', $params);
}

// GET /upload/url - Upload via URL
function luluRemoteUpload($url, $fldId = '', $catId = '') {
    $params = ['url' => $url];
    if ($fldId) $params['fld_id'] = $fldId;
    if ($catId) $params['cat_id'] = $catId;
    return luluRequest('/upload/url', $params);
}

function luluGetUploadServer() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://lulustream.com/api/upload/server?key=' . LULU_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    if (!$data || !isset($data['result'])) return false;
    return rtrim($data['result'], '/');
}

function luluUploadFile($filePath, $fileName = '', $fldId = '') {
    if (!file_exists($filePath)) return ['msg' => 'File not found'];

    $serverUrl = luluGetUploadServer();
    if (!$serverUrl) return ['msg' => 'Failed to get upload server'];

    $post = [];
    $post['key'] = LULU_API_KEY;
    $post['file'] = new CURLFile($filePath, mime_content_type($filePath) ?: 'application/octet-stream', $fileName ?: basename($filePath));
    if ($fldId) $post['fld_id'] = $fldId;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $serverUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    $result = @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($result === false || $result === '') {
        return ['msg' => 'cURL error: ' . ($curlError ?: 'Empty response')];
    }

    $data = json_decode($result, true);
    if (!$data) {
        $preview = substr(preg_replace('/\s+/', ' ', $result), 0, 200);
        return ['msg' => 'Invalid response: ' . $preview];
    }

    return $data;
}

// ========== VIDEY.CO UPLOAD ==========
function videyUploadFile($filePath) {
    if (!file_exists($filePath)) return ['msg' => 'File not found'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'mov'])) return ['msg' => 'Only MP4 and MOV files are supported by Videy'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, VIDEY_UPLOAD_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($filePath, $mime ?: 'video/mp4', basename($filePath))]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    $result = @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($result === false || $result === '') {
        return ['msg' => 'cURL error: ' . ($curlError ?: 'Empty response')];
    }

    $data = json_decode($result, true);
    if (!$data) {
        $preview = substr(preg_replace('/\s+/', ' ', $result), 0, 200);
        return ['msg' => 'Invalid response: ' . $preview];
    }

    if (isset($data['error'])) return ['msg' => $data['error']];
    if (isset($data['id'])) return $data;
    return ['msg' => 'Unexpected response: ' . json_encode($data)];
}

// GET /folder/list - Daftar folder
function luluGetFolders($fldId = '0') {
    return luluRequest('/folder/list', ['fld_id' => $fldId, 'files' => 1]);
}

// GET /folder/create - Buat folder
function luluCreateFolder($name, $parentId = '0', $descr = '') {
    $params = ['name' => $name, 'parent_id' => $parentId];
    if ($descr) $params['descr'] = $descr;
    return luluRequest('/folder/create', $params);
}

// GET /folder/edit - Edit folder
function luluEditFolder($fldId, $name = '', $parentId = '', $descr = '') {
    $params = ['fld_id' => $fldId];
    if ($name) $params['name'] = $name;
    if ($parentId) $params['parent_id'] = $parentId;
    if ($descr) $params['descr'] = $descr;
    return luluRequest('/folder/edit', $params);
}

// GET /file/url_uploads - Daftar remote upload
function luluUrlUploads($fileCode = '') {
    $params = [];
    if ($fileCode) $params['file_code'] = $fileCode;
    return luluRequest('/file/url_uploads', $params);
}

// GET /file/url_actions - Aksi remote upload
function luluUrlActions($action, $value = '') {
    $params = [];
    if ($action === 'restart_errors') $params['restart_errors'] = 1;
    if ($action === 'delete_errors') $params['delete_errors'] = 1;
    if ($action === 'delete_all') $params['delete_all'] = 1;
    if ($action === 'delete_code' && $value) $params['delete_code'] = $value;
    return luluRequest('/file/url_actions', $params);
}

// ========== TERABOX FUNCTIONS ==========

// Cari curl.exe
function teraboxFindCurl() {
    $paths = [
        'C:\Windows\System32\curl.exe',
        'C:\Windows\SysWOW64\curl.exe',
        'curl.exe',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) return $p;
    }
    $which = trim(shell_exec('where curl.exe 2>nul'));
    if ($which) return explode("\n", $which)[0];
    return 'curl.exe';
}

const TERABOX_PROXY = 'https://tbx-proxy.shakir-ansarii075.workers.dev/';
const TERABOX_PROXY_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0';

function teraboxBuildCookieString() {
    $cookies = [
        'ndus' => TERABOX_COOKIE_NDUS,
        'browserid' => TERABOX_COOKIE_BROWSERID,
        'csrfToken' => TERABOX_COOKIE_CSRFTOKEN,
        'ndut_fmt' => TERABOX_COOKIE_NDUT_FMT,
        'ndut_fmv' => TERABOX_COOKIE_NDUT_FMV,
    ];
    $parts = [];
    foreach ($cookies as $k => $v) {
        if ($v) $parts[] = "$k=$v";
    }
    return implode('; ', $parts);
}

function teraboxProxyResolve($surl, $pwd = '') {
    $curlBin = teraboxFindCurl();
    $url = TERABOX_PROXY . '?mode=resolve&surl=' . urlencode($surl) . '&raw=1';
    if ($pwd) $url .= '&pwd=' . urlencode($pwd);
    $ua = TERABOX_PROXY_UA;
    $cookies = teraboxBuildCookieString();
    $json = shell_exec("\"{$curlBin}\" -s -L --cookie \"{$cookies}\" -H \"User-Agent: {$ua}\" \"{$url}\" 2>nul");
    $data = json_decode($json, true);
    if (!$data) return false;
    if (isset($data['upstream'])) return $data['upstream'];
    if (isset($data['data'])) return $data['data'];
    return false;
}

function teraboxProxyPage($surl, $pwd = '') {
    $curlBin = teraboxFindCurl();
    $url = TERABOX_PROXY . '?mode=page&surl=' . urlencode($surl);
    if ($pwd) $url .= '&pwd=' . urlencode($pwd);
    $ua = TERABOX_PROXY_UA;
    $cookies = teraboxBuildCookieString();
    return shell_exec("\"{$curlBin}\" -s -L --cookie \"{$cookies}\" -H \"User-Agent: {$ua}\" \"{$url}\" 2>nul");
}

function teraboxProxyApi($surl, $jsToken, $dir, $pwd = '') {
    $curlBin = teraboxFindCurl();
    $url = TERABOX_PROXY . '?mode=api&shorturl=' . urlencode($surl) . '&jsToken=' . urlencode($jsToken) . '&dir=' . urlencode($dir) . '&page=1&num=100';
    if ($pwd) $url .= '&pwd=' . urlencode($pwd);
    $ua = TERABOX_PROXY_UA;
    $cookies = teraboxBuildCookieString();
    $json = shell_exec("\"{$curlBin}\" -s -L --cookie \"{$cookies}\" -H \"User-Agent: {$ua}\" \"{$url}\" 2>nul");
    $data = json_decode($json, true);
    if (!$data) return false;
    if (isset($data['upstream'])) return $data['upstream'];
    if (isset($data['list'])) return $data;
    return false;
}

function teraboxExtractSurl($shareUrl) {
    if (preg_match('~/s/([^/?#]+)~', $shareUrl, $m)) return $m[1];
    if (preg_match('~surl=([^&\s]+)~', $shareUrl, $m)) return $m[1];
    return '';
}

function teraboxExtractPwd($surl) {
    // Coba ambil digit pertama sebagai extraction code
    if (preg_match('/^(\d)/', $surl, $m)) return $m[1];
    return '';
}

function teraboxExtractJsToken($html) {
    if (preg_match('~fn%28%22([^%]+)%22%29~', $html, $m)) return urldecode($m[1]);
    if (preg_match('~jsToken\s*=\s*"([^"]+)"~', $html, $m)) return $m[1];
    return '';
}

// Fetch all files from a share recursively via proxy
function teraboxFetchShare($shareUrl, $pwd = '') {
    $surl = teraboxExtractSurl($shareUrl);

    // Extract pwd from surl if not specified
    if (!$pwd) $pwd = teraboxExtractPwd($surl);
    // Remove leading digit (extraction code) from surl
    $surl = preg_replace('/^\d/', '', $surl);
    if (!$surl) return false;

    // Try resolve via proxy first
    $data = teraboxProxyResolve($surl, $pwd);
    if ($data && isset($data['list'])) {
        $allFiles = [];
        $dirNames = [];
        $dirPaths = [];

        foreach ($data['list'] as $item) {
            if ((string)($item['isdir'] ?? '0') === '1') {
                $dirNames[] = $item['server_filename'];
                $dirPaths[] = $item['path'];
            } else {
                // Try to extract category from path if not root
                $path = $item['path'] ?? '/';
                $dirName = 'Uncategorized';
                if (preg_match('#^/([^/]+)/#', $path, $m)) {
                    $dirName = $m[1];
                }
                $item['_category'] = $dirName;
                $allFiles[] = $item;
            }
        }

        return ['list' => $allFiles, 'dirs' => $dirNames, 'dir_paths' => $dirPaths, 'note' => 'Proxy API dir parameter not supported; directory contents not available via proxy'];
    }

    return false;
}

// Import video dari Terabox ke database (pending, tidak publish)
function teraboxImportVideos($shareUrl, $pwd = '') {
    $data = teraboxFetchShare($shareUrl, $pwd);
    if (!$data) return ['error' => 'Gagal mengambil data dari Terabox. Periksa URL share.'];

    $db = getDB();
    $imported = 0;
    $videoExts = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'm4v', '3gp'];

    foreach ($data['list'] as $item) {
        $ext = strtolower(pathinfo($item['server_filename'], PATHINFO_EXTENSION));
        if (!in_array($ext, $videoExts)) continue;

        $fsId = (string)$item['fs_id'];
        $category = $item['_category'] ?? 'Uncategorized';

        // Ambil thumbnail dari proxy jika ada
        $thumb = '';
        if (isset($item['thumbs'])) {
            $thumb = $item['thumbs']['url3'] ?? $item['thumbs']['url2'] ?? $item['thumbs']['url1'] ?? $item['thumbs']['icon'] ?? '';
        }

        $stmt = $db->prepare("INSERT OR IGNORE INTO videos 
            (file_code, title, thumbnail, size, category, source, terabox_fs_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'terabox', ?, 'pending', ?)");
        $stmt->execute([
            $fsId,
            $item['server_filename'],
            $thumb,
            $item['size'] ?? 0,
            $category,
            $fsId,
            date('Y-m-d H:i:s', ($item['server_ctime'] ?? time()))
        ]);
        if ($stmt->rowCount() > 0) $imported++;
    }

    // Simpan share URL persistent
    $_SESSION['terabox_last_share'] = $shareUrl;
    $_SESSION['terabox_last_pwd'] = $pwd;
    teraboxSaveShareConfig($shareUrl, $pwd);

    return ['success' => "Berhasil mengimport $imported video dari Terabox!", 'total' => $imported];
}

function teraboxShareConfigPath() {
    return __DIR__ . '/admin/terabox_share.json';
}

function teraboxSaveShareConfig($url, $pwd) {
    file_put_contents(teraboxShareConfigPath(), json_encode(['url' => $url, 'pwd' => $pwd]));
}

function teraboxLoadShareConfig() {
    $path = teraboxShareConfigPath();
    if (!file_exists($path)) return ['url' => '', 'pwd' => ''];
    $data = json_decode(file_get_contents($path), true);
    return ['url' => $data['url'] ?? '', 'pwd' => $data['pwd'] ?? ''];
}

// Dapatkan direct download link dari Terabox via proxy (re-fetch share & find by fs_id)
function teraboxGetDlink($fsId) {
    $shareUrl = $_SESSION['terabox_last_share'] ?? '';
    $pwd = $_SESSION['terabox_last_pwd'] ?? '';
    if (!$shareUrl) {
        $cfg = teraboxLoadShareConfig();
        $shareUrl = $cfg['url'];
        $pwd = $cfg['pwd'];
    }
    if (!$shareUrl) return '';

    $data = teraboxFetchShare($shareUrl, $pwd);
    if (!$data || !isset($data['list'])) return '';

    foreach ($data['list'] as $item) {
        if ((string)($item['fs_id'] ?? '') === $fsId) {
            return $item['dlink'] ?? '';
        }
    }
    return '';
}

// Transfer video dari Terabox ke LuluStream (download via cookie + upload langsung)
function teraboxTransferToLulu($fsId, $title = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM videos WHERE file_code = ? AND source = 'terabox'");
    $stmt->execute([$fsId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$video) return ['error' => 'Video tidak ditemukan'];

    $dlink = teraboxGetDlink($fsId);
    if (!$dlink) return ['error' => 'Gagal mendapatkan direct link.'];

    $fileName = $video['title'] ?: 'video.mp4';
    $tmpFile = sys_get_temp_dir() . '/tb_upload_' . $fsId . '.mp4';

    // Download dari dlink dengan full cookie
    $fp = fopen($tmpFile, 'w');
    $ch = curl_init($dlink);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_COOKIE, teraboxBuildCookieString());
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.terabox.com/');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($httpCode !== 200) {
        @unlink($tmpFile);
        return ['error' => "Gagal download dari Terabox (HTTP $httpCode)."];
    }

    $fileSize = filesize($tmpFile);
    if ($fileSize < 1000) {
        @unlink($tmpFile);
        return ['error' => 'File terlalu kecil, kemungkinan error.'];
    }

    // Upload ke LuluStream via file upload API
    $result = luluUploadFile($tmpFile, $fileName);
    @unlink($tmpFile);

    if ($result && isset($result['file']['code'])) {
        $fc = $result['file']['code'];
        $stmt = $db->prepare("UPDATE videos SET file_code = ?, source = 'lulustream', status = 'active' WHERE file_code = ?");
        $stmt->execute([$fc, $fsId]);
        if ($title) updateVideo($fc, ['title' => $title]);
        return ['success' => "Berhasil transfer ke LuluStream! File Code: {$fc}", 'file_code' => $fc];
    }
    return ['error' => 'Gagal upload ke LuluStream: ' . ($result['msg'] ?? json_encode($result))];
}

// ========== DATABASE ==========
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            file_code TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT '',
            category TEXT DEFAULT 'Uncategorized',
            thumbnail TEXT DEFAULT '',
            duration TEXT DEFAULT '',
            size TEXT DEFAULT '',
            views INTEGER DEFAULT 0,
            fld_id TEXT DEFAULT '0',
            cat_id TEXT DEFAULT '',
            public INTEGER DEFAULT 1,
            tags TEXT DEFAULT '',
            source TEXT DEFAULT 'lulustream',
            terabox_fs_id TEXT DEFAULT '',
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS folders (
            fld_id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            parent_id TEXT DEFAULT '0',
            description TEXT DEFAULT ''
        )");

        // Migrasi kolom baru
        try { $db->exec("ALTER TABLE videos ADD COLUMN source TEXT DEFAULT 'lulustream'"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE videos ADD COLUMN terabox_fs_id TEXT DEFAULT ''"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE videos ADD COLUMN videy_id TEXT DEFAULT ''"); } catch (Exception $e) {}
    }
    return $db;
}

// ========== SYNC ==========
function syncFromLulustream() {
    $db = getDB();
    $page = 1;
    $synced = 0;

    while (true) {
        $data = luluGetVideos($page, 200);
        if (!$data || !isset($data['result']['files'])) break;
        $files = $data['result']['files'];
        if (empty($files)) break;

        $stmt = $db->prepare("INSERT OR IGNORE INTO videos 
            (file_code, title, thumbnail, views, duration, fld_id, public, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($files as $f) {
            $stmt->execute([
                $f['file_code'], $f['title'], $f['thumbnail'] ?? '',
                (int)($f['views'] ?? 0), $f['length'] ?? '',
                $f['fld_id'] ?? '0', $f['public'] ?? 1,
                $f['uploaded'] ?? date('Y-m-d H:i:s')
            ]);
            $synced++;
        }

        $totalPages = (int)($data['result']['pages'] ?? 1);
        $page++;
        if ($page > $totalPages) break;
    }

    // Sync folders
    $folders = luluGetFolders('0');
    if ($folders && isset($folders['result']['folders'])) {
        $fStmt = $db->prepare("INSERT OR IGNORE INTO folders (fld_id, name) VALUES (?, ?)");
        foreach ($folders['result']['folders'] as $folder) {
            $fStmt->execute([$folder['fld_id'], $folder['name']]);
        }
    }

    return $synced;
}

// ========== QUERY HELPERS ==========
function getVideos($category = '', $search = '', $page = 1, $perPage = 20, $source = '') {
    $db = getDB();
    $where = ["status = 'active'"];
    $params = [];
    if ($category) { $where[] = "category = ?"; $params[] = $category; }
    if ($search) { $where[] = "(title LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($source) { $where[] = "source = ?"; $params[] = $source; }

    $wc = 'WHERE ' . implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    $total = $db->prepare("SELECT COUNT(*) FROM videos $wc");
    $total->execute($params);
    $totalCount = $total->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM videos $wc ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    return ['videos' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $totalCount, 'pages' => ceil($totalCount / $perPage)];
}

function getVideoByCode($fileCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM videos WHERE file_code = ?");
    $stmt->execute([$fileCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateVideo($fileCode, $data) {
    $db = getDB();
    $fields = []; $params = [];
    foreach (['title', 'description', 'category', 'thumbnail', 'status', 'tags', 'cat_id'] as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (empty($fields)) return false;
    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $fileCode;
    $stmt = $db->prepare("UPDATE videos SET " . implode(', ', $fields) . " WHERE file_code = ?");
    return $stmt->execute($params);
}

function deleteVideo($fileCode) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM videos WHERE file_code = ?");
    return $stmt->execute([$fileCode]);
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) { header('Location: ../index.php'); exit; }
}

function getCategories() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT category FROM videos WHERE status = 'active' ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getEmbedUrl($fileCode, $source = 'lulustream') {
    if ($source === 'terabox') {
        return "https://www.terabox.com/play/video?fs_id={$fileCode}";
    }
    if ($source === 'videy') {
        return "https://videy.co/v?id={$fileCode}";
    }
    return "https://lulustream.com/e/{$fileCode}";
}

function formatDuration($seconds) {
    if (!$seconds || $seconds == 0) return '-';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}

function formatFileSize($bytes) {
    if (!$bytes || $bytes == 0) return '-';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}
