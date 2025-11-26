<?php
session_start();
ob_start();

// --- Config ---
$DATA_DIR = __DIR__.'/data';
if(!is_dir($DATA_DIR)) mkdir($DATA_DIR,0755,true);

$FILES_DB = $DATA_DIR.'/files.json';
if(!file_exists($FILES_DB)) file_put_contents($FILES_DB,'[]');

// --- Load files database ---
$files_data = json_decode(file_get_contents($FILES_DB), true) ?? [];

// --- Lấy file public ---
$public_files = [];
foreach($files_data as $file){
    if($file['type'] === 'public'){
        $public_files[] = $file;
    }
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Format time
function formatTime($timestamp) {
    return date('d/m/Y H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FileShare - Chia sẻ file an toàn</title>
<style>
body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;text-align:center;padding-top:60px;}
h1{margin-bottom:40px;font-size:36px;color:#60a5fa;}
.tagline{font-size:18px;color:#94a3b8;margin-bottom:40px;}
button{padding:15px 35px;margin:15px;font-size:18px;border:none;border-radius:8px;background:#1e3a8a;color:white;cursor:pointer;transition:0.2s;}
button:hover{background:#2563eb;}
.features{display:flex;justify-content:center;flex-wrap:wrap;margin:40px 0;}
.feature-item{background:#111a2c;padding:20px;margin:10px;border-radius:8px;width:200px;}
.feature-icon{font-size:24px;margin-bottom:10px;}
.public-files{margin-top:40px;text-align:left;display:inline-block;}
.public-file-item{background:#111a2c;padding:15px;margin:10px;border-radius:8px;width:400px;}
.nav-buttons{margin-top:30px;}
</style>
</head>
<body>
<h1>📁 FileShare</h1>
<div class="tagline">Chia sẻ file công khai và riêng tư an toàn</div>

<div class="nav-buttons">
    <button onclick="window.location.href='dangki.php'">📝 Đăng ký</button>
    <button onclick="window.location.href='dangnhap.php'">🔐 Đăng nhập</button>
</div>

<div class="features">
    <div class="feature-item">
        <div class="feature-icon">🌐</div>
        <strong>Công khai</strong>
        <p>File chia sẻ công khai cho mọi người</p>
    </div>
    <div class="feature-item">
        <div class="feature-icon">🔒</div>
        <strong>Riêng tư</strong>
        <p>File bảo vệ bằng mật khẩu</p>
    </div>
    <div class="feature-item">
        <div class="feature-icon">📊</div>
        <strong>Theo dõi</strong>
        <p>Thống kê lượt tải</p>
    </div>
</div>

<!-- Hiển thị file công khai -->
<?php if(count($public_files) > 0): ?>
<div class="public-files">
    <h3>📂 File công khai</h3>
    <?php foreach($public_files as $file): ?>
    <div class="public-file-item">
        <strong><?=htmlspecialchars($file['original_name'])?></strong>
        <div style="font-size:12px;color:#94a3b8;">
            Kích thước: <?=formatFileSize($file['size'])?> | 
            Lượt tải: <?=$file['download_count']?> |
            Upload: <?=formatTime($file['upload_time'])?>
        </div>
        <a href="?download=<?=$file['id']?>" style="color:#60a5fa;">⬇️ Tải xuống</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</body>
</html>
