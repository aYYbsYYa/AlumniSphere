<?php
// 数据库修复脚本 - 添加token字段
header("Content-Type: text/plain; charset=utf-8");

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

echo "开始修复数据库...\n";

// 方法1: 直接执行ALTER TABLE
$sql = "ALTER TABLE users ADD COLUMN token VARCHAR(255) NULL";
if ($conn->query($sql)) {
    echo "✅ token字段添加成功\n";
} else {
    echo "⚠️ 添加token字段可能已存在或其他错误: " . $conn->error . "\n";
}

// 方法2: 检查字段是否存在
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'token'");
if ($result && $result->num_rows > 0) {
    echo "✅ token字段已存在\n";
} else {
    echo "❌ token字段仍然不存在\n";
}

// 显示当前表结构
$result = $conn->query("DESCRIBE users");
echo "\n当前users表结构:\n";
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}: {$row['Type']} " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

$conn->close();
echo "\n修复完成！如果token字段已存在，请重新测试接口。\n";
?>