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

// 获取用户基本信息
$user_info = null;
$stmt = $conn->prepare("SELECT cityname, classcode FROM userinfo WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
}
$stmt->close();

// 如果没有用户信息，返回默认值
if (!$user_info) {
    echo json_encode([
        'next_letter_days' => null,
        'same_city_count' => 0,
        'same_class_count' => 0,
        'cityname' => null,
        'classcode' => null
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// 1. 查询距离下一封信件送达的天数
$next_letter_days = null;
$stmt = $conn->prepare("SELECT unlock_date FROM future_letters WHERE user_id = ? AND unlock_date > NOW() ORDER BY unlock_date ASC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $unlock_date = new DateTime($row['unlock_date']);
    $current_date = new DateTime();
    $interval = $current_date->diff($unlock_date);
    $next_letter_days = max(0, $interval->days);
}
$stmt->close();

// 2. 查询相同cityname的数量（包含当前用户）
$same_city_count = 0;
if (!empty($user_info['cityname'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM userinfo WHERE cityname = ?");
    $stmt->bind_param("s", $user_info['cityname']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $same_city_count = $row['count'];
    $stmt->close();
}

// 3. 查询相同classcode的数量（包含当前用户）
$same_class_count = 0;
if (!empty($user_info['classcode'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM userinfo WHERE classcode = ?");
    $stmt->bind_param("s", $user_info['classcode']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $same_class_count = $row['count'];
    $stmt->close();
}

// 返回统计信息
$response = [
    'next_letter_days' => $next_letter_days,
    'same_city_count' => $same_city_count,
    'same_class_count' => $same_class_count,
    'cityname' => $user_info['cityname'],
    'classcode' => $user_info['classcode']
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

$conn->close();
?>