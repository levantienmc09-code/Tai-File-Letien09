<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành công - FileShare</title>
    <style>
        body{font-family:Arial,sans-serif;background:#0b1220;color:#eee;text-align:center;padding-top:100px;}
        .success-card{background:#111a2c;padding:40px;border-radius:12px;display:inline-block;margin:20px;}
        .success-icon{font-size:60px;color:#4ade80;margin-bottom:20px;}
        button{padding:15px 30px;margin:15px;font-size:16px;border:none;border-radius:8px;background:#2563eb;color:white;cursor:pointer;transition:0.2s;}
        button:hover{background:#3b82f6;}
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✅</div>
        <h1>Đăng ký thành công!</h1>
        <p>Tài khoản <strong><?=htmlspecialchars($_SESSION['registered_username'] ?? '')?></strong> đã được tạo thành công.</p>
        <p>Bây giờ bạn có thể đăng nhập để bắt đầu sử dụng FileShare.</p>
        <br>
        <button onclick="window.location.href='index.php'">🎉 Đăng nhập ngay</button>
    </div>

    <script>
        // Tự động chuyển hướng sau 5 giây
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 5000);
    </script>
</body>
</html>
<?php
// Xóa session sau khi hiển thị
unset($_SESSION['register_success']);
unset($_SESSION['registered_username']);
?>
