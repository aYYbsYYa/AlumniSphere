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

// 检查并创建users表
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 检查并添加token字段（兼容MySQL 5.7+）
try {
    // 先检查字段是否存在
    $checkSql = "SHOW COLUMNS FROM users LIKE 'token'";
    $result = $conn->query($checkSql);
    if ($result && $result->num_rows === 0) {
        // 字段不存在，添加字段
        $alterSql = "ALTER TABLE users ADD COLUMN token VARCHAR(255) NULL";
        if (!$conn->query($alterSql)) {
            error_log("添加token字段失败: " . $conn->error);
        }
    }
} catch (Exception $e) {
    error_log("检查token字段时出错: " . $e->getMessage());
}

// 获取请求参数
$action = isset($_POST['action']) ? $_POST['action'] : '';
$username = isset($_POST['username']) ? $_POST['username'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// 验证操作类型
if (empty($action)) {
    echo json_encode(["status" => 7, "message" => "请指定操作类型(action: register/login)"]);
    $conn->close();
    exit;
}

// 登录逻辑
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // 使用用户名或邮箱登录
        $loginField = $username ?: $email;
        
        // 验证参数
        if (empty($loginField) || empty($password)) {
            echo json_encode(["status" => 1, "message" => "用户名/邮箱和密码不能为空"]);
            $conn->close();
            exit;
        }

    $checkTokenField = $conn->query("SHOW COLUMNS FROM users LIKE 'token'");
    $hasTokenField = ($checkTokenField && $checkTokenField->num_rows > 0);
    
    // 判断使用邮箱还是用户名登录
    $isEmailLogin = filter_var($loginField, FILTER_VALIDATE_EMAIL);
    
    if ($hasTokenField) {
        if ($isEmailLogin) {
            $sql = "SELECT id, username, email, password, token FROM users WHERE email = ?";
        } else {
            $sql = "SELECT id, username, email, password, token FROM users WHERE username = ?";
        }
    } else {
        if ($isEmailLogin) {
            $sql = "SELECT id, username, email, password FROM users WHERE email = ?";
        } else {
            $sql = "SELECT id, username, email, password FROM users WHERE username = ?";
        }
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => 500, "message" => "数据库查询准备失败: " . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param("s", $loginField);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => 5, "message" => "用户不存在"]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        $responseData = [
                "userid" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email']
            ];
        
        // 处理token字段
        if ($hasTokenField) {
            // 如果token为空，生成新的token
            if (empty($user['token'])) {
                $newToken = bin2hex(random_bytes(32)) . time();
                $updateSql = "UPDATE users SET token = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $newToken, $user['id']);
                    if ($updateStmt->execute()) {
                        $responseData['token'] = $newToken;
                    }
                    $updateStmt->close();
                }
            } else {
                $responseData['token'] = $user['token'];
            }
        } else {
            // 兼容没有token字段的情况
            $responseData['token'] = null;
        }
        
        echo json_encode([
            "status" => 0,
            "message" => "登录成功",
            "data" => $responseData
        ]);
    } else {
        echo json_encode(["status" => 6, "message" => "密码错误"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// 注册逻辑
  if ($action === 'register') {
      $username = $_POST['username'] ?? '';
      $email = $_POST['email'] ?? '';
      $password = $_POST['password'] ?? '';

      // 验证参数
      if (empty($username) || empty($email) || empty($password)) {
          echo json_encode(["status" => 1, "message" => "用户名、邮箱和密码不能为空"]);
          $conn->close();
          exit;
      }

      // 验证邮箱格式
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          echo json_encode(["status" => 2, "message" => "邮箱格式不正确"]);
          $conn->close();
          exit;
      }

    // 检查邮箱是否已被注册
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => 500, "message" => "数据库查询准备失败: " . $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => 3, "message" => "邮箱已被注册"]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // 密码加密
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 生成唯一token
    $token = bin2hex(random_bytes(32)) . time();

    // 检查token字段是否存在
    $checkTokenField = $conn->query("SHOW COLUMNS FROM users LIKE 'token'");
    $hasTokenField = ($checkTokenField && $checkTokenField->num_rows > 0);
    
    if ($hasTokenField) {
        // 如果token字段存在，正常插入
        $sql = "INSERT INTO users (username, email, password, token) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["status" => 500, "message" => "数据库插入准备失败: " . $conn->error]);
            $conn->close();
            exit;
        }
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $token);
    } else {
        // 如果token字段不存在，兼容插入
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["status" => 500, "message" => "数据库插入准备失败: " . $conn->error]);
            $conn->close();
            exit;
        }
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
    }

    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        echo json_encode([
            "status" => 0, 
            "message" => "注册成功",
            "data" => [
                "userid" => $userId,
                "username" => $username,
                "email" => $email,
                "token" => $token
            ]
        ]);
    } else {
        echo json_encode(["status" => 4, "message" => "注册失败: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>