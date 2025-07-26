<?php
header("Content-Type: application/json");

// 引入数据库配置
$config = require_once __DIR__ . '/config/database.php';

// 创建连接
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// 设置字符集
$conn->set_charset($config['charset']);

// 检查连接
if ($conn->connect_error) {
    die(json_encode(["status" => 500, "message" => "数据库连接失败: " . $conn->connect_error]));
}

// 验证token的函数
function validateToken($conn, $token) {
    if (empty($token)) {
        return null;
    }
    
    $sql = "SELECT id, username, email FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return null;
}

// 获取请求参数（支持GET和POST）
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

// 验证token
$user = validateToken($conn, $token);
if (!$user) {
    echo json_encode(["status" => 8, "message" => "请先登录或token无效"]);
    $conn->close();
    exit;
}

// 验证用户ID
if ($user_id <= 0) {
    echo json_encode(["status" => 1, "message" => "用户ID不能为空且必须为正整数"]);
    $conn->close();
    exit;
}

// 验证用户是否存在
$userCheckSql = "SELECT id FROM users WHERE id = ?";
$userCheckStmt = $conn->prepare($userCheckSql);
$userCheckStmt->bind_param("i", $user_id);
$userCheckStmt->execute();
$userCheckResult = $userCheckStmt->get_result();

if ($userCheckResult->num_rows === 0) {
    echo json_encode(["status" => 5, "message" => "用户不存在"]);
    $userCheckStmt->close();
    $conn->close();
    exit;
}
$userCheckStmt->close();

// 查询用户的classcode
$classcodeSql = "SELECT classcode FROM userinfo WHERE user_id = ?";
$classcodeStmt = $conn->prepare($classcodeSql);
$classcodeStmt->bind_param("i", $user_id);
$classcodeStmt->execute();
$classcodeResult = $classcodeStmt->get_result();

if ($classcodeResult->num_rows === 0) {
    echo json_encode(["status" => 2, "message" => "该用户尚未设置classcode"]);
    $classcodeStmt->close();
    $conn->close();
    exit;
}

$classcodeData = $classcodeResult->fetch_assoc();
$classcode = $classcodeData['classcode'];
$classcodeStmt->close();

// 验证classcode是否为空
if (empty($classcode)) {
    echo json_encode(["status" => 3, "message" => "该用户的classcode为空"]);
    $conn->close();
    exit;
}

// 查询具有相同classcode的所有用户完整信息（包含查询用户本身，不含bio）
$querySql = "SELECT ui.user_id, u.username, ui.realname, ui.university, ui.classcode, ui.cityname, ui.latitude, ui.longitude, ui.major, ui.graduation_year 
             FROM userinfo ui 
             JOIN users u ON ui.user_id = u.id 
             WHERE ui.classcode = ? 
             ORDER BY ui.user_id ASC";
$queryStmt = $conn->prepare($querySql);
$queryStmt->bind_param("s", $classcode);
$queryStmt->execute();
$queryResult = $queryStmt->get_result();

$users = [];
$targetIndex = null;
$index = 0;
while ($row = $queryResult->fetch_assoc()) {
    $currentUserId = intval($row['user_id']);
    if ($currentUserId === $user_id) {
        $targetIndex = $index;
    }
    $users[] = [
        "user_id" => $currentUserId,
        "username" => $row['username'],
        "realname" => $row['realname'],
        "university" => $row['university'],
        "classcode" => $row['classcode'],
        "cityname" => $row['cityname'],
        "latitude" => $row['latitude'] ? floatval($row['latitude']) : null,
        "longitude" => $row['longitude'] ? floatval($row['longitude']) : null,
        "major" => $row['major'],
        "graduation_year" => $row['graduation_year']
    ];
    $index++;
}

$queryStmt->close();

// 返回结果
$response = [
    "status" => 0,
    "message" => "查询成功",
    "data" => [
        "target_user_id" => $user_id,
        "classcode" => $classcode,
        "total_count" => count($users),
        "target_index" => $targetIndex,
        "users" => $users
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

$conn->close();
?>