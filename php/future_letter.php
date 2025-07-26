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

// 获取POST参数
$title = $_POST['title'] ?? '';
$unlock_date = $_POST['unlock_date'] ?? '';
$content = $_POST['content'] ?? '';
$user_id = $_POST['user_id'] ?? null;
$token = $_POST['token'] ?? null;

// 验证必需参数
if (empty($title) || empty($unlock_date) || empty($content) || empty($user_id) || empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要参数：title, unlock_date, content, user_id, token']);
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

// 验证日期格式
if (!strtotime($unlock_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'unlock_date格式无效']);
    exit;
}

// 插入数据到数据库
$stmt = $conn->prepare("INSERT INTO future_letters (title, content, unlock_date, user_id) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => '数据库准备失败: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sssi", $title, $content, $unlock_date, $user_id);

if ($stmt->execute()) {
    $new_letter_id = $conn->insert_id;
    
    // 获取当前用户的所有信件，按启封时间由远到近排序
    $query = "SELECT id, title, content, unlock_date, commit_date FROM future_letters WHERE user_id = ? ORDER BY unlock_date ASC";
    $stmt = $conn->prepare($query);
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
    
    echo json_encode($letters, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['error' => '数据插入失败: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>