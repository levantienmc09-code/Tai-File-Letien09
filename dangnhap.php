<?php
session_start();
ob_start();

// --- Config ---
$DATA_DIR = __DIR__.'/data';
if(!is_dir($DATA_DIR)) mkdir($DATA_DIR,0755,true);

$USERS_FILE = $DATA_DIR.'/users.json';
if(!file_exists($USERS_FILE)) file_put_contents($USERS_FILE,'{}');
$users = json_decode(file_get_contents($USERS_FILE), true);

// --- Auto login ---
if(!isset($_SESSION['user']) && isset($_COOKIE['remember_user']) && isset($_COOKIE['remember_token'])){
    $u = $_COOKIE['remember_user'];
    $token = $_COOKIE['remember_token'];
    if(isset($users[$u]['token']) && hash_equals($users[$u]['token'],$token)){
        $_SESSION['user'] = $u;
        header("Location: quanli.php");
        exit;
    }
}

// --- Login ---
$error = '';
if(isset($_POST['action']) && $_POST['action']==='login'){
    $u = preg_replace('/[^a-zA-Z0-9_\-]/','', $_POST['username']);
    $p = $_POST['password'];
    if(isset($users[$u]) && password_verify($p,$users[$u]['pass'])){
        $_SESSION['user'] = $u;
        setcookie('remember_user',$u,time()+60*60*24*365*10,'/');
        setcookie('remember_token',$users[$u]['token'],time()+60*60*24*365*10,'/');
        header("Location: quanli.php");
        exit;
    } else { 
        $error="Đăng nhập thất bại. Kiểm tra lại tên đăng nhập và mật khẩu"; 
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập - FileShare</title>
<style>
body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;text-align:center;padding-top:80px;}
.container{display:inline-block;text-align:left;}
.form-card{background:#111a2c;padding:30px;border-radius:12px;width:350px;}
h2{color:#60a5fa;text-align:center;}
input[type=text], input[type=password]{padding:12px;width:100%;margin:8px 0;border-radius:6px;border:1px solid #1e3a8a;background:#0f172a;color:#fff;box-sizing:border-box;}
button{padding:12px;width:100%;margin-top:10px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:16px;}
button:hover{background:#3b82f6;}
.error{color:#f87171;background:#7f1d1d33;padding:10px;border-radius:6px;margin:10px 0;}
.back-link{text-align:center;margin-top:20px;}
.back-link a{color:#60a5fa;text-decoration:none;}
</style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2>🔐 Đăng nhập</h2>
            
            <?php if($error): ?>
                <div class="error"><?=$error?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="login">
                
                <div style="margin-bottom:15px;">
                    <label>Tên đăng nhập:</label>
                    <input type="text" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>Mật khẩu:</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                
                <button type="submit">🚀 Đăng nhập</button>
            </form>
            
            <div class="back-link">
                <a href="index.php">← Quay lại trang chủ</a> | 
                <a href="dangki.php">Chưa có tài khoản? Đăng ký</a>
            </div>
        </div>
    </div>
</body>
</html>
