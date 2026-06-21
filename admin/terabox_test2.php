<?php
require_once __DIR__ . '/../config.php';

$url = 'https://1024terabox.com/s/16YoEcsw_IDu0d3ogHjNfLA';

$parsed = parse_url($url);
$host = $parsed['host'] ?? 'www.terabox.com';

$surl = '';
if (preg_match('~/s/([^/?#]+)~', $url, $m)) $surl = $m[1];
echo "1. surl from URL: $surl\n";

$pageUrl = $surl ? "https://{$host}/s/{$surl}" : $url;
echo "2. pageUrl: $pageUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $pageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_COOKIE, 'ndus=' . TERABOX_COOKIE_NDUS);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36']);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$err = curl_error($ch);
curl_close($ch);

echo "3. HTTP: $httpCode, Error: " . ($err ?: 'none') . "\n";
echo "4. FinalUrl: $finalUrl\n";
echo "5. HTML length: " . strlen($html) . "\n";

if ($httpCode !== 200 || !$html) {
    echo "FAILED at fetch\n";
    exit;
}

// Extract data
if (preg_match('~surl=([^"&\s]+)~', $html, $m)) {
    $surl = $m[1];
    echo "6. surl from page: $surl\n";
}

$jsToken = '';
if (preg_match('~fn%28%22([^%]+)%22%29~', $html, $m)) {
    $jsToken = urldecode($m[1]);
    echo "7. jsToken: $jsToken\n";
} else {
    echo "7. jsToken NOT FOUND\n";
}

$bdstoken = '';
if (preg_match('~"bdstoken"\s*:\s*"([^"]+)"~', $html, $m)) {
    $bdstoken = $m[1];
    echo "8. bdstoken: $bdstoken\n";
} else {
    echo "8. bdstoken NOT FOUND\n";
}

$uk = '';
if (preg_match('~"uk"\s*:\s*(\d+)~', $html, $m)) {
    $uk = $m[1];
    echo "9. uk: $uk\n";
} else {
    echo "9. uk NOT FOUND\n";
}

$apiHost = $host;
if (preg_match('~https?://([^/]+)~', $finalUrl, $m)) $apiHost = $m[1];
echo "10. apiHost: $apiHost\n";

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
echo "11. Post data: $postData\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "https://{$apiHost}/share/list");
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
curl_setopt($ch2, CURLOPT_COOKIE, 'ndus=' . TERABOX_COOKIE_NDUS);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0',
    'Content-Type: application/x-www-form-urlencoded',
]);
$json = curl_exec($ch2);
$apiHttp = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$apiErr = curl_error($ch2);
curl_close($ch2);

echo "12. API HTTP: $apiHttp\n";
if ($apiErr) echo "12b. API Error: $apiErr\n";
echo "13. Response:\n" . $json . "\n";
