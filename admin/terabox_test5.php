<?php
require_once __DIR__ . '/../config.php';

$url = 'https://1024terabox.com/s/16YoEcsw_IDu0d3ogHjNfLA';

$surl = '';
$ndus = TERABOX_COOKIE_NDUS;
if (preg_match('~/s/([^/?#]+)~', $url, $m)) $surl = $m[1];

$curlBin = teraboxFindCurl();
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
$targetUrl = "https://1024terabox.com/s/{$surl}";

$cmd = "\"{$curlBin}\" -s -L --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" \"{$targetUrl}\" 2>nul";
$html = shell_exec($cmd);
echo "HTML length: " . strlen($html) . "\n";

if (preg_match('~surl=([^"&\s]+)~', $html, $m)) $surl = $m[1];
$jsToken = '';
if (preg_match('~fn%28%22([^%]+)%22%29~', $html, $m)) $jsToken = urldecode($m[1]);
$bdstoken = '';
if (preg_match('~"bdstoken"\s*:\s*"([^"]+)"~', $html, $m)) $bdstoken = $m[1];
$uk = '';
if (preg_match('~"uk"\s*:\s*"(\d+)"~', $html, $m)) $uk = $m[1];

echo "surl: $surl\n";
echo "jsToken: " . substr($jsToken, 0, 30) . "...\n";
echo "bdstoken: $bdstoken\n";
echo "uk: $uk\n";

// POST request
$postData = http_build_query([
    'app_id' => TERABOX_APP_ID,
    'channel' => '0',
    'clienttype' => '0',
    'web' => '1',
    'shorturl' => $surl,
    'jsToken' => $jsToken,
    'bdstoken' => $bdstoken,
    'uk' => $uk,
    'dir' => '/',
    'page' => '1',
    'num' => '100',
]);

echo "\nPOST data: $postData\n";

$apiUrl = "https://www.terabox.app/share/list";
$cmd2 = "\"{$curlBin}\" -s --cookie \"ndus={$ndus}\" -H \"User-Agent: {$ua}\" -H \"Content-Type: application/x-www-form-urlencoded\" -X POST -d \"{$postData}\" \"{$apiUrl}\" 2>nul";
echo "CMD: $cmd2\n\n";

$json = shell_exec($cmd2);
echo "Response: $json\n";

$data = json_decode($json, true);
if ($data) {
    echo "errno: " . ($data['errno'] ?? 'N/A') . "\n";
    echo "List count: " . count($data['list'] ?? []) . "\n";
    if (isset($data['list'])) {
        foreach ($data['list'] as $f) {
            echo "File: {$f['server_filename']} (isdir={$f['isdir']})\n";
        }
    }
}
