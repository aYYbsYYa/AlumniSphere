<?php
header('Content-Type: application/json; charset=utf-8');

// 数据库配置
require_once 'config/database.php';
$config = require 'config/database.php';

// Token验证函数
function validateToken($conn, $userId, $token) {
    if (empty($token)) {
        return ["valid" => false, "message" => "缺少认证token"];
    }
    
    $stmt = $conn->prepare("SELECT token FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ["valid" => false, "message" => "用户不存在"];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user['token'] !== $token) {
        return ["valid" => false, "message" => "token无效或已过期"];
    }
    
    return ["valid" => true];
}

// 创建数据库连接
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// 检查连接
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => '数据库连接失败: ' . $conn->connect_error]);
    exit;
}

// 设置字符集
$conn->set_charset($config['charset']);

// 获取用户ID和token（必需）
$user_id = $_GET['user_id'] ?? null;
$token = $_GET['token'] ?? null;

// 验证必需参数
if (empty($user_id) || empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必需参数：user_id和token']);
    $conn->close();
    exit;
}

// 验证token
$validation = validateToken($conn, $user_id, $token);
if (!$validation['valid']) {
    http_response_code(401);
    echo json_encode(['error' => $validation['message']]);
    $conn->close();
    exit;
}

// 构建查询
$stmt = $conn->prepare("SELECT id, title, content, unlock_date, commit_date FROM future_letters WHERE user_id = ? ORDER BY unlock_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$current_timestamp = time();
$letters = [];
while ($row = $result->fetch_assoc()) {
    $unlock_timestamp = strtotime($row['unlock_date']);
    $is_locked = $unlock_timestamp > $current_timestamp;
    
    $letters[] = [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'commit_date' => date('Y年m月d日', strtotime($row['commit_date'])),
        'unlock_date' => date('Y年m月d日', strtotime($row['unlock_date'])),
        'locked' => $is_locked,
        'content' => $row['content']
    ];
}

// 返回JSON响应
echo json_encode($letters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>