<?php
/**
 * Konfigurasi Iklan XVIDSUP
 * 
 * Cara pake: tinggal paste kode iklan dari network (ExoClick, Adsterra, PopAds, dll)
 * ke variabel yang sesuai. Kosongin kalo belum pake.
 */

// ====== HEADER (antara <head> dan </head>) ======
// Buat script tracking, analytics, dll
$ad_header = <<<HTML
<meta name="popads-verification-3659440" value="fab45f50c15717a289a1f3ec08ddae20" />
HTML;

// ====== POPUNDER (muncul di background) ======
// Biasanya dari PopAds, PropellerAds, etc.
// Letakkan kode popunder di sini
$ad_popunder = <<<HTML

HTML;

// ====== BANNER ATAS (di atas grid video) ======
$ad_top_banner = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:90px">
<!-- Paste banner 728x90 atau 320x50 di sini -->

</div>
HTML;

// ====== BANNER TENGAH (di antara grid video) ======
$ad_mid_banner = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:90px">
<!-- Paste banner 728x90 atau 320x50 di sini -->

</div>
HTML;

// ====== BANNER BAWAH (sebelum footer) ======
$ad_bottom_banner = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:90px">
<!-- Paste banner 728x90 atau 320x50 di sini -->

</div>
HTML;

// ====== HALAMAN VIDEO ======

// Iklan di ATAS player video
$ad_video_top = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:90px">
<!-- Paste banner 728x90 di sini -->

</div>
HTML;

// Iklan di BAWAH player video (sebelum judul)
$ad_video_bottom = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:90px">
<!-- Paste banner 728x90 atau 320x50 di sini -->

</div>
HTML;

// Iklan di SAMPING video (sidebar)
$ad_video_sidebar = <<<HTML
<div class="ad-label">ADVERTISEMENT</div>
<div style="text-align:center;margin:10px 0;min-height:250px">
<!-- Paste banner 300x250 di sini -->

</div>
HTML;
