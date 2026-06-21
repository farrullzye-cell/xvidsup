<?php
$url = 'https://1024terabox.com/s/16YoEcsw_IDu0d3ogHjNfLA';
$ndus = 'YfkvlXPpeHuiN8AQF4sING36R-dQKzB-_WdjtwRc';

// Request 1: JANGAN follow redirect, dapatkan response pertama
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Jangan follow
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_COOKIE, 'ndus=' . $ndus);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "Request 1 (no follow):\n";
echo "  HTTP: $httpCode\n";
echo "  FinalUrl: $finalUrl\n";
echo "  RedirectUrl: $redirectUrl\n";
echo "  HTML len: " . strlen($html) . "\n";
echo "  HTML start: " . substr($html, 0, 200) . "\n\n";

// Request 2: Pake follow, ke URL asli
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
curl_setopt($ch2, CURLOPT_COOKIE, 'ndus=' . $ndus);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
$html2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$finalUrl2 = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
curl_close($ch2);

echo "Request 2 (with follow):\n";
echo "  HTTP: $httpCode2\n";
echo "  FinalUrl: $finalUrl2\n";
echo "  HTML len: " . strlen($html2) . "\n";
echo "  HTML: " . substr($html2, 0, 300) . "\n";
