<?php
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';
$fsId = $_GET['fs_id'] ?? '';

if ($action === 'player' && $fsId) {
    proxyTeraboxPlayer($fsId);
} elseif ($action === 'asset') {
    $url = $_GET['url'] ?? '';
    if ($url) proxyAsset($url);
} elseif ($action === 'stream' && $fsId) {
    proxyTeraboxStream($fsId);
} else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid request';
}

function getTeraboxTokens() {
    $curlBin = teraboxFindCurl();
    $ndus = TERABOX_COOKIE_NDUS;
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    $shareUrl = 'https://1024terabox.com/s/16YoEcsw_IDu0d3ogHjNfLA';
    $html = shell_exec("\"{$curlBin}\" -s -L --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" \"{$shareUrl}\" 2>nul");

    if (!$html) return null;

    $tokens = [];
    if (preg_match('~fn%28%22([^%]+)%22%29~', $html, $m)) $tokens['jsToken'] = urldecode($m[1]);
    if (preg_match('~"bdstoken"\s*:\s*"([^"]+)"~', $html, $m)) $tokens['bdstoken'] = $m[1];
    if (preg_match('~"uk"\s*:\s*"(\d+)"~', $html, $m)) $tokens['uk'] = $m[1];
    if (preg_match('~"csrf"\s*:\s*"([^"]+)"~', $html, $m)) $tokens['csrf'] = $m[1];
    $tokens['app_id'] = TERABOX_APP_ID;
    return $tokens;
}

function proxyTeraboxPlayer($fsId) {
    $tokens = getTeraboxTokens();
    if (!$tokens || empty($tokens['jsToken'])) {
        echo '<html><body><h2>Gagal获取 token Terabox. Cookie mungkin expired.</h2></body></html>';
        return;
    }

    $curlBin = teraboxFindCurl();
    $ndus = TERABOX_COOKIE_NDUS;
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    $playUrl = "https://dm.terabox.app/play/video?fs_id={$fsId}&jsToken={$tokens['jsToken']}&bdstoken={$tokens['bdstoken']}&uk={$tokens['uk']}&app_id={$tokens['app_id']}";
    $html = shell_exec("\"{$curlBin}\" -s -L --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" -H \"Referer: https://1024terabox.com/\" \"{$playUrl}\" 2>nul");

    if (!$html) {
        echo '<html><body><h2>Gagal memuat player Terabox</h2></body></html>';
        return;
    }

    // Rewrite asset URLs to go through our proxy
    $base = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $html = preg_replace(
        '~(href|src)=["\'](https?://s\d+\.teraboxcdn\.com[^"\']+)~',
        '$1="' . $base . '/tb_proxy.php?action=asset&url=$2',
        $html
    );

    // Rewrite canonical URLs
    $html = preg_replace(
        '~https?://dm\.terabox\.app/~',
        $base . '/',
        $html
    );

    echo $html;
}

function proxyAsset($url) {
    $curlBin = teraboxFindCurl();
    $ndus = TERABOX_COOKIE_NDUS;
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: {$ua}", "Cookie: ndus={$ndus}"]);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($httpCode !== 200 || $content === false) {
        header('HTTP/1.0 502 Bad Gateway');
        return;
    }

    header("Content-Type: {$contentType}");
    header('Cache-Control: public, max-age=86400');
    echo $content;
}

function proxyTeraboxStream($fsId) {
    // Coba proxy download dari Terabox
    $curlBin = teraboxFindCurl();
    $ndus = TERABOX_COOKIE_NDUS;
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    // Coba call streaming API
    $apiUrl = "https://www.terabox.app/api/streaming?app_id=" . TERABOX_APP_ID . "&web=1&channel=dubox&clienttype=0&jsToken=&uk=&fs_id={$fsId}";
    $json = shell_exec("\"{$curlBin}\" -s -L --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" -H \"Content-Type: application/x-www-form-urlencoded\" -X POST -d \"fs_id={$fsId}\" \"{$apiUrl}\" 2>nul");
    $data = json_decode($json, true);

    if ($data && !empty($data['dlink'])) {
        // Proxy the actual video file
        $dlink = $data['dlink'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $dlink);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: {$ua}"]);
        header('Content-Type: video/mp4');
        header('Content-Disposition: inline');
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    // Fallback: redirect langsung ke Terabox player
    $tokens = getTeraboxTokens();
    if ($tokens && !empty($tokens['jsToken'])) {
        $redirectUrl = "https://dm.terabox.app/play/video?fs_id={$fsId}";
        header("Location: {$redirectUrl}");
        return;
    }

    echo json_encode(['error' => 'Gagal streaming', 'response' => $json ?? 'no response']);
}
