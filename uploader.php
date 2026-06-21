<?php
/**
 * XVIDSUP Uploader — CLI Tool
 * 
 * Scan folder → Upload video ke LuluStream → Update DB → Hapus file lokal
 * 
 * Usage:
 *   php uploader.php <folder_path> [category]
 * 
 * Examples:
 *   php uploader.php "D:\Videos"
 *   php uploader.php "D:\Videos" "Film Barat"
 *   php uploader.php "D:\Videos" "Film Barat" --folder-id=123
 */

require_once __DIR__ . '/config.php';

// ========== CONFIG ==========
$VIDEO_EXTS = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', 'm4v', '3gp', 'mpeg'];

// ========== CLI PARSING ==========
$args = array_slice($argv, 1);
if (empty($args) || in_array($args[0], ['-h', '--help', '/?'])) {
    echo "XVIDSUP Uploader v1.0\n";
    echo "======================\n\n";
    echo "Usage:\n";
    echo "  php uploader.php <folder> [category] [options]\n\n";
    echo "Examples:\n";
    echo '  php uploader.php "D:\Videos"' . "\n";
    echo '  php uploader.php "D:\Videos" "Film Barat"' . "\n";
    echo '  php uploader.php "D:\Videos" --folder-id=123' . "\n";
    echo '  php uploader.php "D:\Videos" --no-delete' . "\n\n";
    echo "Options:\n";
    echo "  --folder-id=N   Upload ke folder LuluStream dengan ID tertentu\n";
    echo "  --no-delete      Jangan hapus file lokal setelah upload\n";
    echo "  --cat-id=N       Set category ID LuluStream\n";
    exit;
}

$folderPath = rtrim($args[0], '\\/');
$category = '';
$fldId = '';
$catId = '';
$deleteAfter = true;

for ($i = 1; $i < count($args); $i++) {
    $arg = $args[$i];
    if (str_starts_with($arg, '--folder-id=')) {
        $fldId = substr($arg, strlen('--folder-id='));
    } elseif (str_starts_with($arg, '--cat-id=')) {
        $catId = substr($arg, strlen('--cat-id='));
    } elseif ($arg === '--no-delete') {
        $deleteAfter = false;
    } elseif (!str_starts_with($arg, '--')) {
        $category = $arg;
    }
}

// ========== VALIDASI FOLDER ==========
if (!is_dir($folderPath)) {
    die("[ERROR] Folder tidak ditemukan: $folderPath\n");
}

// ========== SCAN VIDEO ==========
echo "Scanning folder: $folderPath\n";
$files = [];
$di = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($di as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (in_array($ext, $VIDEO_EXTS, true)) {
            $files[] = $file->getRealPath();
        }
    }
}

if (empty($files)) {
    die("[ERROR] Tidak ada file video ditemukan di folder.\n");
}

echo "Ditemukan " . count($files) . " file video.\n\n";

// ========== PROSES ==========
$db = getDB();
$success = 0;
$failed = 0;
$skipped = 0;

foreach ($files as $filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    $fileSizeHuman = formatFileSize($fileSize);

    echo "[" . ($success + $failed + $skipped + 1) . "/" . count($files) . "] ";
    echo "Processing: $fileName ($fileSizeHuman)\n";

    // Cek apakah file sudah pernah diupload (cek berdasarkan nama mirip di DB)
    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE title LIKE ?");
    $stmt->execute(["$baseName%"]);
    if ($stmt->fetchColumn() > 0) {
        echo "  ⏭️  Skipped (already in DB): $fileName\n";
        $skipped++;
        continue;
    }

    // Upload ke LuluStream
    echo "  ⬆️  Uploading to LuluStream...\n";
    $result = luluUploadFile($filePath, $fileName, $fldId);

    if (!$result || !isset($result['files'][0]['filecode'])) {
        $msg = $result['msg'] ?? json_encode($result) ?? 'Unknown error';
        echo "  ❌  Upload FAILED: $msg\n";
        $failed++;
        continue;
    }

    $fileCode = $result['files'][0]['filecode'];
    $thumb = '';
    $duration = '';
    echo "  ✅  Uploaded! File Code: $fileCode\n";

    // Simpan ke database
    $cat = $category ?: ($catId ? 'LuluStream' : 'Uncategorized');
    $stmt = $db->prepare("INSERT OR IGNORE INTO videos 
        (file_code, title, thumbnail, size, duration, source, status, category, fld_id, cat_id, created_at) 
        VALUES (?, ?, ?, ?, ?, 'lulustream', 'active', ?, ?, ?, ?)");
    $stmt->execute([
        $fileCode,
        $fileName,
        $thumb,
        $fileSize,
        $duration,
        $cat,
        $fldId ?: '0',
        $catId,
        date('Y-m-d H:i:s')
    ]);

    if ($stmt->rowCount() > 0) {
        echo "  💾  Saved to database.\n";
    } else {
        echo "  ⚠️  File code already exists in database (updated).\n";
        $stmt2 = $db->prepare("UPDATE videos SET title=?, size=?, status='active', category=? WHERE file_code=?");
        $stmt2->execute([$fileName, $fileSize, $cat, $fileCode]);
    }

    // Hapus file lokal
    if ($deleteAfter) {
        if (@unlink($filePath)) {
            echo "  🗑️  Local file deleted.\n";
        } else {
            echo "  ⚠️  Failed to delete local file.\n";
        }
    }

    $success++;
    echo "\n";
}

// ========== SUMMARY ==========
echo "====================\n";
echo "Upload Complete!\n";
echo "  ✅ Success: $success\n";
echo "  ❌ Failed: $failed\n";
echo "  ⏭️  Skipped: $skipped\n";
echo "  📁 Folder: $folderPath\n";
if ($category) echo "  🏷️  Category: $category\n";
echo "====================\n";
