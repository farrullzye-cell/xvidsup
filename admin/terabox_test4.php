<?php
$surl = '16YoEcsw_IDu0d3ogHjNfLA';
$ndus = 'YfkvlXPpeHuiN8AQF4sING36R-dQKzB-_WdjtwRc';

$ckfile = __DIR__ . '/cookie_jar.txt';

// First request - get cookies
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://1024terabox.com/s/{$surl}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Cookie: ndus=' . $ndus,
]);
$r1 = curl_exec($ch);
$info1 = curl_getinfo($ch);
curl_close($ch);
echo "Req1: HTTP={$info1['http_code']} Redirect={$info1['redirect_url']}\n";

// Get redirect URL
$loc = '';
if (preg_match('~Location:\s*([^\s\r\n]+)~i', $r1, $m)) $loc = trim($m[1]);
echo "Location: $loc\n";

// Second request - follow redirect
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $loc);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_COOKIEJAR, $ckfile);
curl_setopt($ch2, CURLOPT_COOKIEFILE, $ckfile);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Cookie: ndus=' . $ndus,
]);
$r2 = curl_exec($ch2);
$info2 = curl_getinfo($ch2);
curl_close($ch2);
echo "Req2: HTTP={$info2['http_code']} URL={$info2['url']}\n";
echo "HTML len: " . strlen($r2) . "\n";
if (preg_match('~"uk"\s*:\s*(\d+)~', $r2, $m)) echo "uk: {$m[1]}\n";
if (preg_match('~"bdstoken"\s*:\s*"([^"]+)"~', $r2, $m)) echo "bdstoken: {$m[1]}\n";
if (preg_match('~fn%28%22([^%]+)%22%29~', $r2, $m)) echo "jsToken: " . urldecode($m[1]) . "\n";

@unlink($ckfile);
