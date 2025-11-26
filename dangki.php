<?php
session_start();
ob_start();

// --- Config ---
$DATA_DIR = __DIR__.'/data';
if(!is_dir($DATA_DIR)) mkdir($DATA_DIR,0755,true);

$USERS_FILE = $DATA_DIR.'/users.json';
if(!file_exists($USERS_FILE)) file_put_contents($USERS_FILE,'{}');
$users = json_decode(file_get_contents($USERS_FILE), true);

// --- Register ---
$error = '';
$success = '';

if(isset($_POST['action']) && $_POST['action']==='register'){
    $u = preg_replace('/[^a-zA-Z0-9_\-]/','', $_POST['username']);
    $p = $_POST['password'];
    $p_confirm = $_POST['password_confirm'];
    
    if(!$u || !$p){ 
        $error="Vui lòng nhập đầy đủ thông tin"; 
    }
    elseif(strlen($u) < 3){
        $error="Tên đăng nhập phải có ít nhất 3 ký tự";
    }
    elseif(strlen($p) < 6){
        $error="Mật khẩu phải có ít nhất 6 ký tự";
    }
    elseif($p !== $p_confirm){
        $error="Mật khẩu xác nhận không khớp";
    }
    elseif(isset($users[$u])){ 
        $error="Người dùng đã tồn tại"; 
    }
    else{
        $hash = password_hash($p,PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $users[$u] = ['pass'=>$hash,'token'=>$token];
        if(file_put_contents($USERS_FILE,json_encode($users,JSON_PRETTY_PRINT))===false){
            $error="Lỗi lưu thông tin người dùng!";
        } else {
            $success="Đăng ký thành công! Đang chuyển đến trang đăng nhập...";
            echo "<script>setTimeout(function(){ window.location.href='dangnhap.php'; }, 2000);</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng ký - FileShare</title>
<style>
body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;text-align:center;padding-top:80px;}
.container{display:inline-block;text-align:left;}
.form-card{background:#111a2c;padding:30px;border-radius:12px;width:350px;}
h2{color:#60a5fa;text-align:center;}
input[type=text], input[type=password]{padding:12px;width:100%;margin:8px 0;border-radius:6px;border:1px solid #1e3a8a;background:#0f172a;color:#fff;box-sizing:border-box;}
button{padding:12px;width:100%;margin-top:10px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:16px;}
button:hover{background:#3b82f6;}
.error{color:#f87171;background:#7f1d1d33;padding:10px;border-radius:6px;margin:10px 0;}
.success{color:#4ade80;background:#064e3b33;padding:10px;border-radius:6px;margin:10px 0;}
.back-link{text-align:center;margin-top:20px;}
.back-link a{color:#60a5fa;text-decoration:none;}
</style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2>📝 Đăng ký tài khoản</h2>
            
            <?php if($error): ?>
                <div class="error"><?=$error?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?=$success?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="register">
                
                <div style="margin-bottom:15px;">
                    <label>Tên đăng nhập:</label>
                    <input type="text" name="username" placeholder="Tên đăng nhập (ít nhất 3 ký tự)" required minlength="3">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>Mật khẩu:</label>
                    <input type="password" name="password" placeholder="Mật khẩu (ít nhất 6 ký tự)" required minlength="6">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>Xác nhận mật khẩu:</label>
                    <input type="password" name="password_confirm" placeholder="Nhập lại mật khẩu" required>
                </div>
                
                <button type="submit">🚀 Đăng ký ngay</button>
            </form>
            
            <div class="back-link">
                <a href="index.php">← Quay lại trang chủ</a> | 
                <a href="dangnhap.php">Đã có tài khoản? Đăng nhập</a>
            </div>
        </div>
    </div>
</body>
</html>
