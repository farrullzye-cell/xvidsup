<?php
require_once __DIR__ . '/../config.php';

$url = 'https://1024terabox.com/s/16YoEcsw_IDu0d3ogHjNfLA';

echo "1. Finding curl...\n";
echo "   curl path: " . teraboxFindCurl() . "\n";

echo "2. Fetching HTML...\n";
$surl = '';
$ndus = TERABOX_COOKIE_NDUS;
if (preg_match('~/s/([^/?#]+)~', $url, $m)) $surl = $m[1];
$curlBin = teraboxFindCurl();
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
$targetUrl = "https://1024terabox.com/s/{$surl}";
$cmd = "\"{$curlBin}\" -s -L --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" \"{$targetUrl}\" 2>nul";
echo "   cmd: $cmd\n";

$html = shell_exec($cmd);
echo "3. HTML length: " . strlen($html) . "\n";
if (strlen($html) > 0 && strlen($html) < 200) {
    echo "   Content: " . substr($html, 0, 200) . "\n";
}

if (!$html) {
    echo "   FAILED - HTML is empty\n";
    exit;
}

// Check tokens
if (preg_match('~"uk"\s*:\s*(\d+)~', $html, $m)) echo "4. uk: {$m[1]}\n"; else echo "4. uk NOT FOUND\n";
if (preg_match('~"bdstoken"\s*:\s*"([^"]+)"~', $html, $m)) echo "5. bdstoken: {$m[1]}\n"; else echo "5. bdstoken NOT FOUND\n";
if (preg_match('~fn%28%22([^%]+)%22%29~', $html, $m)) echo "6. jsToken: " . urldecode($m[1]) . "\n"; else echo "6. jsToken NOT FOUND\n";

// Now call teraboxFetchShare
echo "\n7. Calling teraboxFetchShare...\n";
$data = teraboxFetchShare($url);
if ($data) {
    echo "SUCCESS\n";
    echo "Files found: " . count($data['list']) . "\n";
    foreach ($data['list'] as $f) {
        echo " - " . $f['server_filename'] . " (" . $f['size'] . " bytes)\n";
    }
} else {
    echo "FAILED\n";
}
