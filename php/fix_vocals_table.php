<?php
// 创建vocals_text表（如果不存在）

// 引入数据库配置
$config = require_once __DIR__ . '/config/database.php';

// 创建连接
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// 设置字符集
$conn->set_charset($config['charset']);

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 创建vocals_text表
$sql = "CREATE TABLE IF NOT EXISTS vocals_text (
    id INT PRIMARY KEY,
    text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "vocals_text表创建成功或已存在\n";
} else {
    echo "创建表失败: " . $conn->error . "\n";
}

$conn->close();
?>