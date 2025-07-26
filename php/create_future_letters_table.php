<?php
// 创建未来信件数据库表
require_once 'config/database.php';

$config = require 'config/database.php';

// 创建连接
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 创建表的SQL语句
$sql = "CREATE TABLE IF NOT EXISTS future_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    unlock_date DATE NOT NULL,
    commit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";

if ($conn->query($sql) === TRUE) {
    echo "未来信件表创建成功！";
} else {
    echo "创建表时出错: " . $conn->error;
}

$conn->close();
?>