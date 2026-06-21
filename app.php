<?php
/**
 * XVIDSUP Uploader — GUI Application
 * Single file: serves UI + handles all API requests
 */

define('VIDEO_EXTS', ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'm4v', '3gp', 'mpeg']);
define('UPLOAD_DIR', __DIR__ . '/temp_uploads');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ====== ROUTING ======
if ($uri === '/api/scan')      { handleScan(); exit; }
if ($uri === '/api/upload')    { handleUpload(); exit; }
if ($uri === '/api/upload-phone') { handlePhoneUpload(); exit; }
if ($uri === '/api/status')    { handleStatus(); exit; }

// ====== GUI ======
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>XVIDSUP Uploader</title>
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#6c5ce7">
<link rel="icon" href="/icon.svg" type="image/svg+xml">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f0f11;color:#e0e0e0;min-height:100vh;padding:0 0 60px}
.container{max-width:920px;margin:0 auto;padding:12px}
.header{background:linear-gradient(135deg,#6c5ce7,#a855f7);border-radius:12px;padding:18px;margin-bottom:14px;text-align:center}
.header h1{font-size:22px;color:#fff}
.header p{color:rgba(255,255,255,.8);font-size:12px;margin-top:2px}
.tabs{display:flex;gap:4px;margin-bottom:14px;background:#1a1a1e;border-radius:10px;padding:4px;border:1px solid #2a2a2e}
.tab{flex:1;text-align:center;padding:10px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#666;transition:all .2s;border:none;background:none}
.tab.active{background:#6c5ce7;color:#fff}
.tab:hover:not(.active){color:#999}
.card{background:#1a1a1e;border-radius:12px;padding:16px;margin-bottom:12px;border:1px solid #2a2a2e}
.card-title{font-size:14px;font-weight:600;margin-bottom:12px;color:#a78bfa;display:flex;align-items:center;gap:6px}
.row{display:flex;gap:8px;margin-bottom:10px;align-items:center;flex-wrap:wrap}
.row label{min-width:80px;color:#999;font-size:12px;font-weight:500}
.row input,.row select{flex:1;background:#252529;border:1px solid #333;border-radius:8px;padding:10px 12px;color:#e0e0e0;font-size:14px;outline:none;min-width:0;width:100%}
.row input:focus,.row select:focus{border-color:#6c5ce7}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 18px;border-radius:8px;border:none;font-size:13px;font-weight:500;cursor:pointer;transition:all .2s;touch-action:manipulation}
.btn-primary{background:#6c5ce7;color:#fff}
.btn-primary:hover{background:#5b4dce}
.btn-primary:disabled{background:#444;cursor:not-allowed}
.btn-success{background:#10b981;color:#fff}
.btn-success:hover{background:#059669}
.btn-secondary{background:#252529;color:#e0e0e0;border:1px solid #333}
.btn-secondary:hover{background:#333}
.btn-block{width:100%}
.file-grid{display:grid;grid-template-columns:1fr;gap:6px;margin-top:10px}
.file-item{background:#252529;border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:8px;font-size:13px;border:1px solid #333}
.file-item .name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-item .size{color:#666;font-size:11px;white-space:nowrap}
.file-item .status{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.file-item .status.pending{background:#666}
.file-item .status.uploading{background:#fbbf24;animation:pulse 1s infinite}
.file-item .status.done{background:#10b981}
.file-item .status.failed{background:#ef4444}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.progress-bar{height:5px;background:#333;border-radius:3px;margin:10px 0;overflow:hidden}
.progress-bar .fill{height:100%;background:linear-gradient(90deg,#6c5ce7,#a855f7);border-radius:3px;transition:width .3s;width:0}
.stats{display:flex;gap:16px;margin:10px 0;justify-content:center;flex-wrap:wrap}
.stat{text-align:center}
.stat .num{font-size:20px;font-weight:700;color:#a78bfa}
.stat .label{font-size:11px;color:#666;margin-top:1px}
.log{border-radius:8px;padding:10px;margin-top:10px;max-height:150px;overflow-y:auto;font-family:monospace;font-size:11px;line-height:1.5;color:#888;background:#0a0a0c}
.log .info{color:#60a5fa}
.log .success{color:#34d399}
.log .error{color:#f87171}
.log .warn{color:#fbbf24}
.hidden{display:none!important}
.settings-toggle{color:#666;font-size:12px;cursor:pointer;user-select:none;margin-bottom:6px;display:inline-block}
.settings-toggle:hover{color:#999}
.settings-panel.hidden{display:none}
.drop-zone{border:2px dashed #333;border-radius:12px;padding:30px 20px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:10px}
.drop-zone:hover,.drop-zone.dragover{border-color:#6c5ce7;background:rgba(108,92,231,.05)}
.drop-zone-icon{font-size:36px;margin-bottom:8px}
.drop-zone-text{color:#666;font-size:13px}
.drop-zone-text strong{color:#999}
.drop-zone input[type=file]{display:none}
.file-preview{background:#252529;border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:10px;font-size:13px;border:1px solid #333}
.file-preview .name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-preview .size{color:#666;font-size:11px}
.group-badge{background:rgba(108,92,231,.15);color:#a78bfa;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:500}
@media(min-width:600px){
  .container{padding:20px}
  .header{padding:24px;margin-bottom:20px}
  .header h1{font-size:28px}
  .header p{font-size:14px}
  .file-grid{grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}
}
@media(max-width:480px){
  .row{flex-direction:column;align-items:stretch}
  .row label{min-width:auto;margin-bottom:2px}
  .btn{padding:14px 18px;font-size:15px}
  .drop-zone{padding:24px 16px}
  .drop-zone-icon{font-size:28px}
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>XVIDSUP Uploader</h1>
    <p>Upload video ke LuluStream + Database</p>
  </div>

  <!-- Tabs: PC / HP -->
  <div class="tabs">
    <button class="tab active" data-tab="pc" onclick="switchTab('pc')">💻 Dari PC</button>
    <button class="tab" data-tab="phone" onclick="switchTab('phone')">📱 Dari HP</button>
  </div>

  <!-- ===== TAB PC ===== -->
  <div id="tab-pc">
    <div class="card">
      <div class="card-title">📁 Pilih Folder Video</div>
      <div class="row">
        <label>Folder</label>
        <input type="text" id="folderPath" placeholder="C:\Users\...\Videos">
      </div>
      <div class="row">
        <label>Kategori</label>
        <input type="text" id="category" placeholder="(opsional)">
      </div>
      <div class="settings-toggle" onclick="toggleSettings()">⚙ Lanjutan</div>
      <div class="settings-panel hidden" id="settingsPanel">
        <div class="row"><label>Folder ID</label><input type="text" id="fldId" placeholder="(opsional)"></div>
        <div class="row"><label>Cat ID</label><input type="text" id="catId" placeholder="(opsional)"></div>
        <div class="row"><label></label><label style="min-width:auto;cursor:pointer"><input type="checkbox" id="deleteAfter" checked> Hapus file lokal setelah upload</label></div>
      </div>
      <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary" id="scanBtn" onclick="scanFolder()">🔍 Scan Folder</button>
        <button class="btn btn-success hidden" id="uploadBtn" onclick="startUpload()">⬆ Upload Semua</button>
      </div>
    </div>

    <div class="card hidden" id="resultCard">
      <div class="card-title">📋 Hasil</div>
      <div id="fileList"></div>
      <div class="progress-bar hidden" id="progressBar"><div class="fill" id="progressFill"></div></div>
      <div class="stats hidden" id="stats"></div>
      <div class="log" id="log"></div>
    </div>
  </div>

  <!-- ===== TAB HP ===== -->
  <div id="tab-phone" class="hidden">
    <div class="card">
      <div class="card-title">📱 Upload Video dari HP</div>
      <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
        <div class="drop-zone-icon">📹</div>
        <div class="drop-zone-text"><strong>Ketuk untuk pilih video</strong><br>Atau drag & drop file</div>
        <input type="file" id="fileInput" accept="video/*,.mp4,.avi,.mkv,.mov,.wmv,.flv,.webm,.m4v,.3gp,.mpeg" multiple>
      </div>
      <div id="phoneFileList"></div>
      <div class="row">
        <label>Kategori</label>
        <input type="text" id="phoneCategory" placeholder="(opsional)">
      </div>
      <div class="row">
        <label></label>
        <label style="min-width:auto;cursor:pointer"><input type="checkbox" id="phoneDelete" checked> Hapus dari HP setelah upload</label>
      </div>
      <button class="btn btn-success btn-block" id="phoneUploadBtn" onclick="startPhoneUpload()" disabled>⬆ Upload ke LuluStream</button>
      <div class="progress-bar hidden" id="phoneProgress"><div class="fill" id="phoneProgressFill"></div></div>
      <div class="stats hidden" id="phoneStats"></div>
      <div class="log" id="phoneLog"></div>
    </div>
  </div>

  <!-- Server info footer -->
  <div style="text-align:center;padding:16px;color:#444;font-size:11px">
    <span id="serverInfo">Local server</span> &middot; 
    <a href="#" onclick="event.preventDefault();copyServerUrl()" style="color:#666;text-decoration:none">Copy link</a>
  </div>
</div>

<script>
let scannedFiles = [];
let phoneFiles = [];
let serverUrl = window.location.origin;

function log(elId, msg, type = 'info') {
    const el = document.getElementById(elId);
    const line = document.createElement('div');
    line.className = type;
    line.textContent = '> ' + msg;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    document.getElementById('tab-pc').classList.toggle('hidden', tab !== 'pc');
    document.getElementById('tab-phone').classList.toggle('hidden', tab !== 'phone');
}

function toggleSettings() {
    document.getElementById('settingsPanel').classList.toggle('hidden');
}

// ====== TAB PC ======
async function scanFolder() {
    const folder = document.getElementById('folderPath').value.trim();
    if (!folder) { log('log', 'Masukkan folder path.', 'error'); return; }
    document.getElementById('scanBtn').disabled = true;
    document.getElementById('scanBtn').textContent = '⏳ Scanning...';
    log('log', 'Scanning: ' + folder);
    document.getElementById('resultCard').classList.remove('hidden');
    document.getElementById('uploadBtn').classList.add('hidden');
    try {
        const res = await fetch('/api/scan', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({folder}) });
        const data = await res.json();
        if (data.error) { log('log', data.error, 'error'); document.getElementById('scanBtn').disabled = false; document.getElementById('scanBtn').textContent = '🔍 Scan Folder'; return; }
        scannedFiles = data.files;
        log('log', 'Ditemukan ' + scannedFiles.length + ' file video.');
        let html = '<div class="file-grid">';
        scannedFiles.forEach((f,i) => { const s = formatBytes(f.size); html += '<div class="file-item" id="file-'+i+'"><span class="name" title="'+f.name+'">'+f.name+'</span><span class="size">'+s+'</span><span class="status pending" id="status-'+i+'"></span></div>'; });
        html += '</div>';
        document.getElementById('fileList').innerHTML = html;
        if (scannedFiles.length) document.getElementById('uploadBtn').classList.remove('hidden');
    } catch(e) { log('log', 'Error: '+e.message, 'error'); }
    document.getElementById('scanBtn').disabled = false;
    document.getElementById('scanBtn').textContent = '🔍 Scan Folder';
}

let isUploading = false;
async function startUpload() {
    if (isUploading || !scannedFiles.length) return;
    isUploading = true;
    const category = document.getElementById('category').value.trim();
    const fldId = document.getElementById('fldId').value.trim();
    const catId = document.getElementById('catId').value.trim();
    const deleteAfter = document.getElementById('deleteAfter').checked;
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('uploadBtn').textContent = '⏳ Uploading...';
    document.getElementById('progressBar').classList.remove('hidden');
    document.getElementById('stats').classList.remove('hidden');
    updateStats(0,0,0);
    let ok=0, fail=0, skip=0;
    for(let i=0;i<scannedFiles.length;i++){
        const f=scannedFiles[i];
        const se=document.getElementById('status-'+i);
        se.className='status uploading';
        log('log','['+(i+1)+'/'+scannedFiles.length+'] Uploading: '+f.name);
        try{
            const r=await fetch('/api/upload',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({file:f,category,fld_id:fldId,cat_id:catId,delete_after:deleteAfter})});
            const d=await r.json();
            if(d.success){se.className='status done';ok++;log('log','✅ '+f.name+' → '+d.file_code,'success');}
            else if(d.skipped){se.className='status done';skip++;log('log','⏭ '+f.name+' (already exists)','warn');}
            else{se.className='status failed';fail++;log('log','❌ '+f.name+': '+(d.error||'Unknown'),'error');}
        }catch(e){se.className='status failed';fail++;log('log','❌ '+f.name+': '+e.message,'error');}
        document.getElementById('progressFill').style.width=Math.round((i+1)/scannedFiles.length*100)+'%';
        updateStats(ok,fail,skip);
    }
    log('log','✅ Selesai — '+ok+' berhasil, ❌ '+fail+' gagal, ⏭ '+skip+' skip',ok?'success':'info');
    document.getElementById('uploadBtn').disabled=false;
    document.getElementById('uploadBtn').textContent='✅ Selesai';
    isUploading=false;
}
function updateStats(ok,fail,skip){
    document.getElementById('stats').innerHTML='<div class="stat"><div class="num">'+scannedFiles.length+'</div><div class="label">Total</div></div><div class="stat"><div class="num" style="color:#34d399">'+ok+'</div><div class="label">Berhasil</div></div><div class="stat"><div class="num" style="color:#f87171">'+fail+'</div><div class="label">Gagal</div></div><div class="stat"><div class="num" style="color:#fbbf24">'+skip+'</div><div class="label">Skip</div></div>';
}

// ====== TAB HP ======
const fileInput = document.getElementById('fileInput');
const dropZone = document.getElementById('dropZone');

fileInput.addEventListener('change', function(e) {
    phoneFiles = Array.from(e.target.files);
    renderPhoneFiles();
});

dropZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
dropZone.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('dragover'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        phoneFiles = Array.from(e.dataTransfer.files);
        renderPhoneFiles();
    }
});

function renderPhoneFiles() {
    const el = document.getElementById('phoneFileList');
    if (!phoneFiles.length) { el.innerHTML = ''; document.getElementById('phoneUploadBtn').disabled = true; return; }
    let html = '';
    phoneFiles.forEach((f,i) => {
        const s = formatBytes(f.size);
        html += '<div class="file-preview" id="pf-'+i+'"><span class="name">'+f.name+'</span><span class="size">'+s+'</span><span class="status pending" id="ps-'+i+'"></span></div>';
    });
    el.innerHTML = html;
    document.getElementById('phoneUploadBtn').disabled = false;
    document.getElementById('phoneUploadBtn').textContent = '⬆ Upload ' + phoneFiles.length + ' file';
}

async function startPhoneUpload() {
    if (!phoneFiles.length) return;
    document.getElementById('phoneUploadBtn').disabled = true;
    document.getElementById('phoneUploadBtn').textContent = '⏳ Uploading...';
    document.getElementById('phoneProgress').classList.remove('hidden');
    document.getElementById('phoneStats').classList.remove('hidden');
    updatePhoneStats(0,0,0);
    const cat = document.getElementById('phoneCategory').value.trim();
    let ok=0, fail=0, skip=0;
    for(let i=0;i<phoneFiles.length;i++){
        const f=phoneFiles[i];
        const se=document.getElementById('ps-'+i);
        se.className='status uploading';
        log('phoneLog','['+(i+1)+'/'+phoneFiles.length+'] Uploading: '+f.name);
        const formData = new FormData();
        formData.append('video', f);
        formData.append('category', cat);
        try{
            const r=await fetch('/api/upload-phone', {method:'POST', body:formData});
            const d=await r.json();
            if(d.success){se.className='status done';ok++;log('phoneLog','✅ '+f.name+' → '+d.file_code,'success');}
            else if(d.skipped){se.className='status done';skip++;log('phoneLog','⏭ '+f.name+' (already exists)','warn');}
            else{se.className='status failed';fail++;log('phoneLog','❌ '+f.name+': '+(d.error||'Unknown'),'error');}
        }catch(e){se.className='status failed';fail++;log('phoneLog','❌ '+f.name+': '+e.message,'error');}
        document.getElementById('phoneProgressFill').style.width=Math.round((i+1)/phoneFiles.length*100)+'%';
        updatePhoneStats(ok,fail,skip);
    }
    log('phoneLog','✅ Selesai — '+ok+' berhasil, ❌ '+fail+' gagal, ⏭ '+skip+' skip',ok?'success':'info');
    document.getElementById('phoneUploadBtn').disabled=false;
    document.getElementById('phoneUploadBtn').textContent='✅ Selesai';
}
function updatePhoneStats(ok,fail,skip){
    document.getElementById('phoneStats').innerHTML='<div class="stat"><div class="num">'+phoneFiles.length+'</div><div class="label">Total</div></div><div class="stat"><div class="num" style="color:#34d399">'+ok+'</div><div class="label">Berhasil</div></div><div class="stat"><div class="num" style="color:#f87171">'+fail+'</div><div class="label">Gagal</div></div><div class="stat"><div class="num" style="color:#fbbf24">'+skip+'</div><div class="label">Skip</div></div>';
}

function formatBytes(b){if(!b||b===0)return'0 B';const u=['B','KB','MB','GB'];let i=0,s=b;while(s>=1024&&i<3){s/=1024;i++}return s.toFixed(1)+' '+u[i];}
function copyServerUrl(){navigator.clipboard.writeText(serverUrl).then(()=>{const e=document.getElementById('serverInfo');const t=e.textContent;e.textContent='✅ Copied!';setTimeout(()=>e.textContent=t,2000);});}

// Show server IPs
fetch('/api/server-info').then(r=>r.json()).then(d=>{
    document.getElementById('serverInfo').textContent = d.ips.join(' / ');
}).catch(()=>{});

// Register service worker for PWA
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(()=>{});
}
</script>
</body>
</html>
<?php
// ====== API HANDLERS ======

function jsonResponse($data) { header('Content-Type: application/json'); echo json_encode($data); }

function getServerIps() {
    $ips = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('powershell -command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -ne \'127.0.0.1\' }).IPAddress" 2>nul');
        if ($output) {
            $ips = array_filter(array_map('trim', explode("\n", $output)));
        }
    }
    return $ips ?: ['localhost'];
}

// Serve server info for the footer
if ($uri === '/api/server-info') {
    jsonResponse(['ips' => getServerIps()]);
    exit;
}

function handleScan() {
    $input = json_decode(file_get_contents('php://input'), true);
    $folder = $input['folder'] ?? '';
    if (!is_dir($folder)) { jsonResponse(['error' => 'Folder tidak ditemukan']); return; }
    $files = [];
    $di = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($di as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), VIDEO_EXTS)) {
            $files[] = ['path' => $file->getRealPath(), 'name' => $file->getFilename(), 'size' => $file->getSize(), 'ext' => strtolower($file->getExtension())];
        }
    }
    usort($files, fn($a,$b) => strcmp($a['name'], $b['name']));
    jsonResponse(['files' => $files, 'total' => count($files)]);
}

function luluDebugLog($msg, $data = null) {
    $log = '[' . date('H:i:s') . '] ' . $msg;
    if ($data !== null) $log .= ' | ' . (is_string($data) ? $data : json_encode($data));
    file_put_contents(__DIR__ . '/upload_debug.log', $log . "\n", FILE_APPEND);
}

function handleUpload() {
    require_once __DIR__ . '/config.php';
    luluDebugLog('handleUpload started');

    $input = json_decode(file_get_contents('php://input'), true);
    $file = $input['file'] ?? null;
    $category = $input['category'] ?? '';
    $fldId = $input['fld_id'] ?? '';
    $catId = $input['cat_id'] ?? '';
    $deleteAfter = $input['delete_after'] ?? true;

    if (!$file || !file_exists($file['path'])) {
        luluDebugLog('File not found', $file['path'] ?? 'null');
        jsonResponse(['error' => 'File tidak ditemukan']);
        return;
    }

    $fileName = $file['name']; $filePath = $file['path'];
    luluDebugLog('Processing file', $fileName);

    $db = getDB();
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE title LIKE ?");
    $stmt->execute(["$baseName%"]);
    if ($stmt->fetchColumn() > 0) {
        luluDebugLog('Already in DB, skipping', $fileName);
        if ($deleteAfter) @unlink($filePath);
        jsonResponse(['skipped' => true, 'message' => 'Already exists in DB']);
        return;
    }

    luluDebugLog('Calling luluUploadFile');
    $result = luluUploadFile($filePath, $fileName, $fldId);
    luluDebugLog('luluUploadFile returned', $result);

    if (!$result || !isset($result['files'][0]['filecode']) || empty($result['files'][0]['filecode'])) {
        $errMsg = $result['msg'] ?? ($result ? json_encode($result) : 'Upload failed');
        luluDebugLog('Upload FAILED', $errMsg);
        jsonResponse(['error' => $errMsg]);
        return;
    }

    $fileCode = $result['files'][0]['filecode'];
    luluDebugLog('Upload SUCCESS, filecode: ' . $fileCode);

    $thumb = '';
    $duration = '';
    $cat = $category ?: 'Uncategorized';
    $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_code,title,thumbnail,size,duration,source,status,category,fld_id,cat_id,created_at) VALUES (?,?,?,?,?,'lulustream','active',?,?,?,?)");
    $stmt->execute([$fileCode,$fileName,$thumb,$file['size'],$duration,$cat,$fldId?:'0',$catId,date('Y-m-d H:i:s')]);
    if ($stmt->rowCount() === 0) { $db->prepare("UPDATE videos SET title=?,size=?,status='active',category=? WHERE file_code=?")->execute([$fileName,$file['size'],$cat,$fileCode]); }
    if ($deleteAfter) @unlink($filePath);
    jsonResponse(['success' => true, 'file_code' => $fileCode, 'file_name' => $fileName]);
}

function handlePhoneUpload() {
    require_once __DIR__ . '/config.php';
    if (!isset($_FILES['video'])) { jsonResponse(['error' => 'No file uploaded']); return; }
    $category = $_POST['category'] ?? '';

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);

    $uploadedFiles = $_FILES['video'];
    // Normalize single file upload (name, not array)
    if (is_array($uploadedFiles['name'])) {
        // Multiple files
        $results = [];
        $total = count($uploadedFiles['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK) { $results[] = ['success' => false, 'error' => 'Upload error code: ' . $uploadedFiles['error'][$i]]; continue; }
            $tmpPath = $uploadedFiles['tmp_name'][$i];
            $origName = $uploadedFiles['name'][$i];
            $results[] = processUploadedFile($tmpPath, $origName, $category);
        }
        jsonResponse(['batch' => true, 'results' => $results]);
    } else {
        // Single file
        if ($uploadedFiles['error'] !== UPLOAD_ERR_OK) { jsonResponse(['error' => 'Upload error code: ' . $uploadedFiles['error']]); return; }
        $result = processUploadedFile($uploadedFiles['tmp_name'], $uploadedFiles['name'], $category);
        jsonResponse($result);
    }
}

function processUploadedFile($tmpPath, $origName, $category) {
    require_once __DIR__ . '/config.php';
    luluDebugLog('processUploadedFile: ' . $origName);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, VIDEO_EXTS)) { @unlink($tmpPath); luluDebugLog('Unsupported format: ' . $ext); return ['success' => false, 'error' => 'Format tidak didukung: ' . $ext]; }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
    $destPath = UPLOAD_DIR . '/' . uniqid() . '_' . $safeName;

    if (!move_uploaded_file($tmpPath, $destPath)) { luluDebugLog('Failed to save file'); return ['success' => false, 'error' => 'Failed to save file']; }

    $db = getDB();
    $baseName = pathinfo($origName, PATHINFO_FILENAME);
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE title LIKE ?");
    $stmt->execute(["$baseName%"]);
    if ($stmt->fetchColumn() > 0) { @unlink($destPath); luluDebugLog('Already exists: ' . $origName); return ['skipped' => true, 'message' => 'Already exists in DB']; }

    $fileSize = filesize($destPath);
    luluDebugLog('Calling luluUploadFile from phone handler');
    $result = luluUploadFile($destPath, $origName, '');
    @unlink($destPath);

    if (!$result || !isset($result['files'][0]['filecode']) || empty($result['files'][0]['filecode'])) {
        $errMsg = $result['msg'] ?? ($result ? json_encode($result) : 'Upload failed');
        luluDebugLog('Phone upload FAILED: ' . $errMsg);
        return ['success' => false, 'error' => $errMsg];
    }

    $fileCode = $result['files'][0]['filecode'];
    luluDebugLog('Phone upload SUCCESS: ' . $fileCode);
    $thumb = '';
    $duration = '';
    $size = $fileSize;
    $cat = $category ?: 'Uncategorized';
    $stmt = $db->prepare("INSERT OR IGNORE INTO videos (file_code,title,thumbnail,size,duration,source,status,category,fld_id,cat_id,created_at) VALUES (?,?,?,?,?,'lulustream','active',?,'0','0',?)");
    $stmt->execute([$fileCode,$origName,$thumb,$size,$duration,$cat,date('Y-m-d H:i:s')]);
    if ($stmt->rowCount() === 0) { $db->prepare("UPDATE videos SET title=?,size=?,status='active',category=? WHERE file_code=?")->execute([$origName,$size,$cat,$fileCode]); }

    return ['success' => true, 'file_code' => $fileCode, 'file_name' => $origName];
}

function handleStatus() {
    require_once __DIR__ . '/config.php';
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM videos WHERE source = 'lulustream' AND status = 'active'");
    jsonResponse(['total_videos' => $stmt->fetchColumn()]);
}
