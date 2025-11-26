<?php
ob_start();
session_start();

// --- Config ---
$DATA_DIR = __DIR__.'/data';
if(!is_dir($DATA_DIR)) mkdir($DATA_DIR,0755,true);

$USERS_FILE = $DATA_DIR.'/users.json';
if(!file_exists($USERS_FILE)) file_put_contents($USERS_FILE,'{}');
$users = json_decode(file_get_contents($USERS_FILE), true);

$FILES_DB = $DATA_DIR.'/files.json';
if(!file_exists($FILES_DB)) file_put_contents($FILES_DB,'[]');

// --- Auto login ---
if(!isset($_SESSION['user']) && isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_token'])){
    $u = $_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    if(isset($users[$u]['token']) && hash_equals($users[$u]['token'],$token)){
        $_SESSION['user'] = $u;
    }
}

// --- Register ---
if(isset($_POST['action']) && $_POST['action']==='register'){
    $u = preg_replace('/[^a-zA-Z0-9_\-]/','', $_POST['username']);
    $p = $_POST['password'];
    if(!$u || !$p){ $error="Vui lòng nhập đầy đủ thông tin"; }
    elseif(isset($users[$u])){ $error="Người dùng đã tồn tại"; }
    else{
        $hash = password_hash($p,PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $users[$u] = ['pass'=>$hash,'token'=>$token];
        if(file_put_contents($USERS_FILE,json_encode($users,JSON_PRETTY_PRINT))===false){
            $error="Lỗi lưu thông tin người dùng!";
        } else {
            $success="Đăng ký thành công! Bây giờ đăng nhập nhé.";
        }
    }
}

// --- Login ---
if(isset($_POST['action']) && $_POST['action']==='login'){
    $u = preg_replace('/[^a-zA-Z0-9_\-]/','', $_POST['username']);
    $p = $_POST['password'];
    if(isset($users[$u]) && password_verify($p,$users[$u]['pass'])){
        $_SESSION['user'] = $u;
        setcookie('remember_user',$u,time()+60*60*24*365*10,'/');
        setcookie('remember_token',$users[$u]['token'],time()+60*60*24*365*10,'/');
        header("Location: ".$_SERVER['PHP_SELF']); exit;
    } else { $error="Đăng nhập thất bại"; }
}

// --- Logout ---
if(isset($_GET['logout'])){
    session_destroy();
    setcookie('remember_user','',time()-3600,'/');
    setcookie('remember_token','',time()-3600,'/');
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

// --- User logged in ---
$user = $_SESSION['user'] ?? null;

// --- Load files database ---
$files_data = json_decode(file_get_contents($FILES_DB), true) ?? [];

// --- Handle File Upload ---
if($user && isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK){
    $upload = $_FILES['upload_file'];
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/','', $upload['name']);
    $file_type = $_POST['file_type'] ?? 'private';
    $file_password = $_POST['file_password'] ?? '';
    
    // Kiểm tra kích thước file (tối đa 100MB)
    $max_file_size = 100 * 1024 * 1024;
    if($upload['size'] > $max_file_size){
        $error = "File quá lớn! Kích thước tối đa là 100MB";
    } else {
        $file_id = uniqid();
        $file_path = $DATA_DIR.'/'.$file_id.'_'.$filename;
        
        if(move_uploaded_file($upload['tmp_name'], $file_path)){
            // Lưu thông tin file vào database
            $file_info = [
                'id' => $file_id,
                'filename' => $filename,
                'original_name' => $filename,
                'owner' => $user,
                'type' => $file_type,
                'password' => $file_password ? password_hash($file_password, PASSWORD_DEFAULT) : '',
                'size' => $upload['size'],
                'upload_time' => time(),
                'download_count' => 0
            ];
            
            $files_data[] = $file_info;
            file_put_contents($FILES_DB, json_encode($files_data, JSON_PRETTY_PRINT));
            
            $success = "Tải lên file thành công: $filename";
            header("Location: ".$_SERVER['PHP_SELF']); exit;
        } else {
            $error = "Lỗi khi tải lên file";
        }
    }
}

// --- Handle Download ---
if(isset($_GET['download'])){
    $file_id = $_GET['download'];
    
    // Tìm file trong database
    $file_info = null;
    foreach($files_data as $file){
        if($file['id'] === $file_id){
            $file_info = $file;
            break;
        }
    }
    
    if($file_info && file_exists($DATA_DIR.'/'.$file_info['id'].'_'.$file_info['filename'])){
        $file_path = $DATA_DIR.'/'.$file_info['id'].'_'.$file_info['filename'];
        
        // Kiểm tra quyền truy cập
        $can_download = false;
        
        if($file_info['type'] === 'public'){
            $can_download = true;
        } 
        elseif($file_info['type'] === 'private') {
            if(isset($_POST['file_password'])){
                // Kiểm tra mật khẩu
                if(password_verify($_POST['file_password'], $file_info['password'])){
                    $can_download = true;
                } else {
                    $download_error = "Mật khẩu không đúng!";
                }
            } else {
                // Hiển thị form nhập mật khẩu
                ?>
                <!DOCTYPE html>
                <html lang="vi">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Nhập mật khẩu - <?=$file_info['original_name']?></title>
                    <style>
                        body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;text-align:center;padding-top:100px;}
                        .password-form{background:#111a2c;padding:30px;border-radius:12px;display:inline-block;margin:20px;}
                        input[type=password]{padding:12px;width:300px;margin:10px 0;border-radius:6px;border:1px solid #1e3a8a;background:#0f172a;color:#fff;}
                        button{padding:12px 25px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;}
                        .error{color:#f87171;margin:10px 0;}
                    </style>
                </head>
                <body>
                    <h2>🔒 File được bảo vệ bằng mật khẩu</h2>
                    <div class="password-form">
                        <h3><?=htmlspecialchars($file_info['original_name'])?></h3>
                        <?php if(isset($download_error)) echo "<p class='error'>$download_error</p>"; ?>
                        <form method="post">
                            <input type="password" name="file_password" placeholder="Nhập mật khẩu để tải file" required>
                            <br>
                            <button type="submit">🔓 Mở khóa và Tải</button>
                        </form>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
        }
        
        if($can_download){
            // Tăng số lượt download
            foreach($files_data as &$file){
                if($file['id'] === $file_id){
                    $file['download_count']++;
                    break;
                }
            }
            file_put_contents($FILES_DB, json_encode($files_data, JSON_PRETTY_PRINT));
            
            // Thực hiện download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$file_info['original_name'].'"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            
            ob_clean();
            flush();
            readfile($file_path);
            exit;
        }
    } else {
        http_response_code(404);
        echo "File not found";
        exit;
    }
}

// --- Delete file ---
if(isset($_GET['del']) && $user){
    $file_id = $_GET['del'];
    
    // Tìm và xóa file
    foreach($files_data as $key => $file){
        if($file['id'] === $file_id && $file['owner'] === $user){
            $file_path = $DATA_DIR.'/'.$file['id'].'_'.$file['filename'];
            if(file_exists($file_path)) unlink($file_path);
            unset($files_data[$key]);
            file_put_contents($FILES_DB, json_encode(array_values($files_data), JSON_PRETTY_PRINT));
            break;
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

// --- Lấy file của user ---
$user_files = [];
$public_files = [];
foreach($files_data as $file){
    if($file['owner'] === $user){
        $user_files[] = $file;
    }
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

<?php if(!$user): ?>
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
form{margin-top:20px; display:inline-block; text-align:left; background:#111a2c; padding:25px; border-radius:12px; width:300px;}
input[type=text], input[type=password]{padding:10px; width:100%; margin:8px 0; border-radius:6px; border:1px solid #1e3a8a; background:#0f172a; color:#fff;}
p.error{color:#f87171;}
p.success{color:#4ade80;}
.features{display:flex;justify-content:center;flex-wrap:wrap;margin:40px 0;}
.feature-item{background:#111a2c;padding:20px;margin:10px;border-radius:8px;width:200px;}
.feature-icon{font-size:24px;margin-bottom:10px;}
.public-files{margin-top:40px;text-align:left;display:inline-block;}
.public-file-item{background:#111a2c;padding:15px;margin:10px;border-radius:8px;width:400px;}
</style>
</head>
<body>
<h1>📁 FileShare</h1>
<div class="tagline">Chia sẻ file công khai và riêng tư an toàn</div>

<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>

<button onclick="document.getElementById('login').style.display='block';document.getElementById('register').style.display='none'">Đăng nhập</button>
<button onclick="document.getElementById('register').style.display='block';document.getElementById('login').style.display='none'">Đăng ký</button>

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

<div id="login" style="display:none;margin-top:20px;">
<form method="post">
<input type="hidden" name="action" value="login">
Tên đăng nhập:<br><input type="text" name="username" placeholder="Nhập tên đăng nhập" required><br>
Mật khẩu:<br><input type="password" name="password" placeholder="Nhập mật khẩu" required><br><br>
<button type="submit">Đăng nhập</button>
</form>
</div>

<div id="register" style="display:none;margin-top:20px;">
<form method="post">
<input type="hidden" name="action" value="register">
Tên đăng nhập:<br><input type="text" name="username" placeholder="Tên đăng nhập mới" required><br>
Mật khẩu:<br><input type="password" name="password" placeholder="Mật khẩu mới" required><br><br>
<button type="submit">Đăng ký</button>
</form>
</div>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FileShare - <?=$user?></title>
<style>
body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;margin:0;padding:20px;}
h2{display:flex;justify-content:space-between;align-items:center;}
a.logout{color:#f87171;text-decoration:none;font-weight:bold;}
a.logout:hover{text-decoration:underline;}
input[type=file], input[type=text], input[type=password], select{width:100%;padding:10px;border-radius:6px;border:1px solid #1e3a8a;background:#111a2c;color:#fff;margin-bottom:10px;}
button{padding:8px 15px;margin-top:10px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;transition:0.2s;}
button:hover{background:#3b82f6;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{border:1px solid #1e3a8a;padding:12px;text-align:left;}
th{background:#1e3a8a;color:#fff;}
tr:hover{background:#1e3a8a33;}
a{color:#4ade80;text-decoration:none;font-weight:bold;}
a:hover{color:#60a5fa;}
.card{background:#111a2c;padding:20px;border-radius:12px;margin-top:20px;}
.upload-info{font-size:14px;color:#94a3b8;margin-top:5px;}
.file-icon{font-size:16px;margin-right:8px;}
.file-size{color:#94a3b8;font-size:12px;}
.file-date{color:#94a3b8;font-size:12px;}
.stats{display:flex;justify-content:space-around;text-align:center;margin:20px 0;}
.stat-item{background:#1e293b;padding:15px;border-radius:8px;flex:1;margin:0 10px;}
.stat-number{font-size:24px;font-weight:bold;color:#60a5fa;}
.stat-label{font-size:14px;color:#94a3b8;}
.type-public{color:#4ade80;}
.type-private{color:#fbbf24;}
.link-box{background:#1e293b;padding:8px;border-radius:4px;font-family:monospace;font-size:12px;margin:5px 0;word-break:break-all;}
@media(max-width:600px){table, th, td{font-size:14px;padding:8px;} button{width:100%;} .stats{flex-direction:column;} .stat-item{margin:5px 0;}}
</style>
</head>
<body>
<h2>📁 FileShare - <?=$user?> <a class="logout" href="?logout">Đăng xuất</a></h2>

<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>

<!-- Thống kê -->
<div class="stats">
    <div class="stat-item">
        <div class="stat-number"><?=count($user_files)?></div>
        <div class="stat-label">Tổng số file</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">
            <?php
            $total_size = 0;
            $total_downloads = 0;
            foreach($user_files as $file) {
                $total_size += $file['size'];
                $total_downloads += $file['download_count'];
            }
            echo formatFileSize($total_size);
            ?>
        </div>
        <div class="stat-label">Tổng dung lượng</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?=$total_downloads?></div>
        <div class="stat-label">Tổng lượt tải</div>
    </div>
</div>

<!-- Upload form -->
<div class="card">
<h3>📤 Tải lên file mới</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="upload_file" required>
    
    <select name="file_type" id="file_type" onchange="togglePasswordField()">
        <option value="private">🔒 Riêng tư (cần mật khẩu)</option>
        <option value="public">🌐 Công khai (ai cũng tải được)</option>
    </select>
    
    <div id="password_field">
        <input type="password" name="file_password" placeholder="Mật khẩu bảo vệ file" required>
    </div>
    
    <div class="upload-info">📝 Hỗ trợ mọi loại file, kích thước tối đa: 100MB</div>
    <button type="submit">🚀 Tải lên ngay</button>
</form>
</div>

<!-- Danh sách file -->
<div class="card">
<h3>📂 File của bạn</h3>
<?php if(count($user_files)==0){ echo "<p>Chưa có file nào được tải lên</p>"; } else { ?>
<table>
<tr>
    <th>Tên file</th>
    <th>Loại</th>
    <th>Kích thước</th>
    <th>Lượt tải</th>
    <th>Link download</th>
    <th>Xóa</th>
</tr>
<?php foreach($user_files as $file): ?>
<tr>
<td>
    <span class="file-icon">
        <?php
        $ext = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
        $icons = [
            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'bmp' => '🖼️', 'webp' => '🖼️',
            'mp4' => '🎬', 'avi' => '🎬', 'mov' => '🎬', 'mkv' => '🎬', 'webm' => '🎬',
            'mp3' => '🎵', 'wav' => '🎵', 'ogg' => '🎵', 'flac' => '🎵',
            'pdf' => '📄', 'doc' => '📄', 'docx' => '📄', 'txt' => '📄',
            'zip' => '📦', 'rar' => '📦', '7z' => '📦', 'tar' => '📦', 'gz' => '📦'
        ];
        echo $icons[$ext] ?? '📄';
        ?>
    </span>
    <?=htmlspecialchars($file['original_name'])?>
    <div class="file-date">Upload: <?=formatTime($file['upload_time'])?></div>
</td>
<td>
    <?php if($file['type'] === 'public'): ?>
        <span class="type-public">🌐 Công khai</span>
    <?php else: ?>
        <span class="type-private">🔒 Riêng tư</span>
    <?php endif; ?>
</td>
<td class="file-size"><?=formatFileSize($file['size'])?></td>
<td style="text-align:center;"><?=$file['download_count']?></td>
<td>
    <div class="link-box">
        <?=$_SERVER['HTTP_HOST']?>?download=<?=$file['id']?>
    </div>
    <a href="?download=<?=$file['id']?>" target="_blank">⬇️ Tải xuống</a>
</td>
<td><a href="?del=<?=$file['id']?>" onclick="return confirm('Bạn có chắc muốn xóa file này?')">🗑️ Xóa</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php } ?>
</div>

<script>
function togglePasswordField() {
    const fileType = document.getElementById('file_type').value;
    const passwordField = document.getElementById('password_field');
    
    if (fileType === 'public') {
        passwordField.style.display = 'none';
    } else {
        passwordField.style.display = 'block';
    }
}

// Khởi tạo trạng thái ban đầu
togglePasswordField();
</script>
</body>
</html>