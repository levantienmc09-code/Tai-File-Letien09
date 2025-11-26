<?php
function getDatabase() {
    $db = new PDO('sqlite:data/fileshare.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tạo bảng users nếu chưa có
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE,
        password TEXT,
        token TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Tạo bảng files nếu chưa có
    $db->exec("CREATE TABLE IF NOT EXISTS files (
        id TEXT PRIMARY KEY,
        filename TEXT,
        original_name TEXT,
        owner TEXT,
        type TEXT,
        password TEXT,
        size INTEGER,
        upload_time INTEGER,
        download_count INTEGER DEFAULT 0
    )");
    
    return $db;
}
?>
