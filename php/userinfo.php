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

// 检查并创建userinfo表
$sql = "CREATE TABLE IF NOT EXISTS userinfo (
    user_id INT PRIMARY KEY,
    nickname VARCHAR(50),
    realname VARCHAR(50),
    bio TEXT,
    university VARCHAR(100),
    classcode VARCHAR(20),
    major VARCHAR(100),
    graduation_year YEAR,
    cityname VARCHAR(50),
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// 检查并创建userlinks表
$sql = "CREATE TABLE IF NOT EXISTS userlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    linkicon VARCHAR(255),
    linkname VARCHAR(100) NOT NULL,
    linkurl VARCHAR(255) NOT NULL,
    tag VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($sql);

// 验证token函数
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

// 获取请求参数（优先GET，兼容POST）
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

$realname = isset($_GET['realname']) ? $_GET['realname'] : (isset($_POST['realname']) ? $_POST['realname'] : '');
$bio = isset($_GET['bio']) ? $_GET['bio'] : (isset($_POST['bio']) ? $_POST['bio'] : '');
$university = isset($_GET['university']) ? $_GET['university'] : (isset($_POST['university']) ? $_POST['university'] : '');
$classcode = isset($_GET['classcode']) ? $_GET['classcode'] : (isset($_POST['classcode']) ? $_POST['classcode'] : '');
$major = isset($_GET['major']) ? $_GET['major'] : (isset($_POST['major']) ? $_POST['major'] : '');
$graduation_year = isset($_GET['graduation_year']) ? intval($_GET['graduation_year']) : (isset($_POST['graduation_year']) ? intval($_POST['graduation_year']) : null);
$cityname = isset($_GET['cityname']) ? $_GET['cityname'] : (isset($_POST['cityname']) ? $_POST['cityname'] : '');
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : (isset($_POST['latitude']) ? floatval($_POST['latitude']) : null);
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : (isset($_POST['longitude']) ? floatval($_POST['longitude']) : null);
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);

// 链接相关参数
$link_id = isset($_GET['link_id']) ? intval($_GET['link_id']) : (isset($_POST['link_id']) ? intval($_POST['link_id']) : 0);
$linkicon = isset($_GET['linkicon']) ? $_GET['linkicon'] : (isset($_POST['linkicon']) ? $_POST['linkicon'] : '');
$linkname = isset($_GET['linkname']) ? $_GET['linkname'] : (isset($_POST['linkname']) ? $_POST['linkname'] : '');
$linkurl = isset($_GET['linkurl']) ? $_GET['linkurl'] : (isset($_POST['linkurl']) ? $_POST['linkurl'] : '');
$tag = isset($_GET['tag']) ? $_GET['tag'] : (isset($_POST['tag']) ? $_POST['tag'] : '');

// 验证操作类型
if (empty($action)) {
    echo json_encode(["status" => 7, "message" => "请指定操作类型(action: save/get)"]);
    $conn->close();
    exit;
}

// 验证用户登录状态
$user = validateToken($conn, $token);

// 保存用户信息
if ($action === 'save') {
    // 验证登录状态
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 检查是否尝试修改他人信息（通过user_id参数判断）
    if ($user_id > 0 && $user_id != $user['id']) {
        echo json_encode(["status" => 9, "message" => "只能修改自己的个人信息"]);
        $conn->close();
        exit;
    }

    // 使用当前登录用户的ID
    $current_user_id = $user['id'];

    // 构建动态更新字段
    $updateFields = [];
    $params = [];
    $types = '';

    // 只处理非空字段
    if (isset($_POST['realname']) || isset($_GET['realname'])) {
        $realname = empty($realname) ? null : $realname;
        $updateFields[] = "realname = ?";
        $params[] = $realname;
        $types .= 's';
    }
    
    if (isset($_POST['bio']) || isset($_GET['bio'])) {
        $bio = empty($bio) ? null : $bio;
        $updateFields[] = "bio = ?";
        $params[] = $bio;
        $types .= 's';
    }
    
    if (isset($_POST['university']) || isset($_GET['university'])) {
        $university = empty($university) ? null : $university;
        $updateFields[] = "university = ?";
        $params[] = $university;
        $types .= 's';
    }
    
    if (isset($_POST['classcode']) || isset($_GET['classcode'])) {
        $classcode = empty($classcode) ? null : $classcode;
        $updateFields[] = "classcode = ?";
        $params[] = $classcode;
        $types .= 's';
    }
    
    if (isset($_POST['major']) || isset($_GET['major'])) {
        $major = empty($major) ? null : $major;
        $updateFields[] = "major = ?";
        $params[] = $major;
        $types .= 's';
    }
    
    if (isset($_POST['graduation_year']) || isset($_GET['graduation_year'])) {
        $graduation_year = empty($graduation_year) ? null : $graduation_year;
        $updateFields[] = "graduation_year = ?";
        $params[] = $graduation_year;
        $types .= 'i';
    }
    
    if (isset($_POST['cityname']) || isset($_GET['cityname'])) {
        $cityname = empty($cityname) ? null : $cityname;
        $updateFields[] = "cityname = ?";
        $params[] = $cityname;
        $types .= 's';
    }
    
    if (isset($_POST['latitude']) || isset($_GET['latitude'])) {
        $latitude = empty($latitude) ? null : $latitude;
        $updateFields[] = "latitude = ?";
        $params[] = $latitude;
        $types .= 'd';
    }
    
    if (isset($_POST['longitude']) || isset($_GET['longitude'])) {
        $longitude = empty($longitude) ? null : $longitude;
        $updateFields[] = "longitude = ?";
        $params[] = $longitude;
        $types .= 'd';
    }

    // 检查用户是否已存在个人信息
    $checkSql = "SELECT user_id FROM userinfo WHERE user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $current_user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // 更新现有信息 - 只更新指定字段
        if (!empty($updateFields)) {
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $updateSql = "UPDATE userinfo SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
            $params[] = $current_user_id;
            $types .= 'i';
            
            $updateStmt = $conn->prepare($updateSql);
            if (!$updateStmt) {
                echo json_encode(["status" => 500, "message" => "SQL准备失败: " . $conn->error]);
                $conn->close();
                exit;
            }
            
            $updateStmt->bind_param($types, ...$params);
            
            if ($updateStmt->execute()) {
                echo json_encode([
                    "status" => 0,
                    "message" => "用户信息更新成功"
                ]);
            } else {
                echo json_encode(["status" => 500, "message" => "更新失败: " . $conn->error]);
            }
            $updateStmt->close();
        } else {
            echo json_encode([
                "status" => 0,
                "message" => "没有需要更新的字段"
            ]);
        }
    } else {
        // 插入新信息 - 使用所有字段
        $realname = empty($realname) ? null : $realname;
        $bio = empty($bio) ? null : $bio;
        $university = empty($university) ? null : $university;
        $classcode = empty($classcode) ? null : $classcode;
        $major = empty($major) ? null : $major;
        $graduation_year = empty($graduation_year) ? null : $graduation_year;
        $cityname = empty($cityname) ? null : $cityname;
        $latitude = empty($latitude) ? null : $latitude;
        $longitude = empty($longitude) ? null : $longitude;
        
        $insertSql = "INSERT INTO userinfo (user_id, realname, bio, university, classcode, major, graduation_year, cityname, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        if (!$insertStmt) {
            echo json_encode(["status" => 500, "message" => "SQL准备失败: " . $conn->error]);
            $conn->close();
            exit;
        }
        $insertStmt->bind_param("issssisssd", $current_user_id, $realname, $bio, $university, $classcode, $major, $graduation_year, $cityname, $latitude, $longitude);
        
        if ($insertStmt->execute()) {
            echo json_encode([
                "status" => 0,
                "message" => "用户信息保存成功"
            ]);
        } else {
            echo json_encode(["status" => 500, "message" => "保存失败: " . $conn->error]);
        }
        $insertStmt->close();
    }
    
    $checkStmt->close();
    $conn->close();
    exit;
}

// 获取用户信息
if ($action === 'get') {
    // 验证登录状态 - 查看信息需要登录
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 如果提供了user_id参数，获取指定用户信息，否则获取当前登录用户的信息
    $target_user_id = $user_id > 0 ? $user_id : $user['id'];
    
    if ($target_user_id <= 0) {
        echo json_encode(["status" => 1, "message" => "用户ID不能为空"]);
        $conn->close();
        exit;
    }

    // 获取用户基本信息
    $userSql = "SELECT id, username, email FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $target_user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        echo json_encode(["status" => 5, "message" => "用户不存在"]);
        $userStmt->close();
        $conn->close();
        exit;
    }

    $userData = $userResult->fetch_assoc();
    $userStmt->close();

    // 获取用户详细信息
    $infoSql = "SELECT realname, bio, university, classcode, major, graduation_year, cityname, latitude, longitude, created_at, updated_at FROM userinfo WHERE user_id = ?";
    $infoStmt = $conn->prepare($infoSql);
    $infoStmt->bind_param("i", $target_user_id);
    $infoStmt->execute();
    $infoResult = $infoStmt->get_result();

    if ($infoResult->num_rows === 0) {
        // 获取用户链接信息（即使没有详细信息）
        $linksSql = "SELECT linkicon, linkname, linkurl, tag FROM userlinks WHERE user_id = ? ORDER BY created_at ASC";
        $linksStmt = $conn->prepare($linksSql);
        $linksStmt->bind_param("i", $target_user_id);
        $linksStmt->execute();
        $linksResult = $linksStmt->get_result();
        
        $links = [];
        $index = 0;
        while ($link = $linksResult->fetch_assoc()) {
            $links[$index] = $link;
            $index++;
        }
        
        // 用户存在但没有详细信息，返回基本信息
        echo json_encode([
            "status" => 0,
            "message" => "获取成功",
            "data" => [
                "user_id" => $userData['id'],
                "username" => $userData['username'],
                "email" => $userData['email'],

                "realname" => null,
                "bio" => null,
                "university" => null,
                "classcode" => null,
                "major" => null,
                "graduation_year" => null,
                "cityname" => null,
                "latitude" => null,
                "longitude" => null,
                "created_at" => null,
                "updated_at" => null
            ],
            "links" => $links
        ]);
        
        $linksStmt->close();
    } else {
        $userinfo = $infoResult->fetch_assoc();
        
        // 获取用户链接信息
        $linksSql = "SELECT linkicon, linkname, linkurl, tag FROM userlinks WHERE user_id = ? ORDER BY created_at ASC";
        $linksStmt = $conn->prepare($linksSql);
        $linksStmt->bind_param("i", $target_user_id);
        $linksStmt->execute();
        $linksResult = $linksStmt->get_result();
        
        $links = [];
        $index = 0;
        while ($link = $linksResult->fetch_assoc()) {
            $links[$index] = $link;
            $index++;
        }
        
        echo json_encode([
            "status" => 0,
            "message" => "获取成功",
            "data" => array_merge([
                'user_id' => $userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email']
            ], $userinfo),
            "links" => $links
        ]);
        
        $linksStmt->close();
    }

    $infoStmt->close();
    $conn->close();
    exit;
}

// 保存用户链接
if ($action === 'save_link') {
    // 验证登录状态
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 验证必填字段
    if (empty($linkname) || empty($linkurl)) {
        echo json_encode(["status" => 1, "message" => "链接名称和链接地址不能为空"]);
        $conn->close();
        exit;
    }

    // 使用当前登录用户的ID
    $current_user_id = $user['id'];

    // 处理空字符串为null
    $linkicon = empty($linkicon) ? null : $linkicon;
    $tag = empty($tag) ? null : $tag;

    // 插入新链接
    $insertSql = "INSERT INTO userlinks (user_id, linkicon, linkname, linkurl, tag) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        echo json_encode(["status" => 500, "message" => "SQL准备失败: " . $conn->error]);
        $conn->close();
        exit;
    }
    $insertStmt->bind_param("issss", $current_user_id, $linkicon, $linkname, $linkurl, $tag);
    
    if ($insertStmt->execute()) {
        echo json_encode([
            "status" => 0,
            "message" => "链接添加成功",
            "link_id" => $conn->insert_id
        ]);
    } else {
        echo json_encode(["status" => 500, "message" => "添加失败: " . $conn->error]);
    }
    $insertStmt->close();
    $conn->close();
    exit;
}

// 更新用户链接
if ($action === 'update_link') {
    // 验证登录状态
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 验证必填字段
    if (empty($link_id) || empty($linkname) || empty($linkurl)) {
        echo json_encode(["status" => 1, "message" => "链接ID、名称和地址不能为空"]);
        $conn->close();
        exit;
    }

    // 使用当前登录用户的ID
    $current_user_id = $user['id'];

    // 处理空字符串为null
    $linkicon = empty($linkicon) ? null : $linkicon;
    $tag = empty($tag) ? null : $tag;

    // 更新链接（只能更新自己的链接）
    $updateSql = "UPDATE userlinks SET linkicon = ?, linkname = ?, linkurl = ?, tag = ? WHERE id = ? AND user_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        echo json_encode(["status" => 500, "message" => "SQL准备失败: " . $conn->error]);
        $conn->close();
        exit;
    }
    $updateStmt->bind_param("ssssii", $linkicon, $linkname, $linkurl, $tag, $link_id, $current_user_id);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            echo json_encode([
                "status" => 0,
                "message" => "链接更新成功"
            ]);
        } else {
            echo json_encode(["status" => 404, "message" => "链接不存在或无权限"]);
        }
    } else {
        echo json_encode(["status" => 500, "message" => "更新失败: " . $conn->error]);
    }
    $updateStmt->close();
    $conn->close();
    exit;
}

// 批量保存用户链接
if ($action === 'save_links') {
    // 验证登录状态
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 获取链接数组参数
    $links_data = isset($_GET['links']) ? $_GET['links'] : (isset($_POST['links']) ? $_POST['links'] : '');
    
    if (empty($links_data)) {
        echo json_encode(["status" => 1, "message" => "链接数据不能为空"]);
        $conn->close();
        exit;
    }

    // 解析链接数据（支持JSON字符串或数组格式）
    if (is_string($links_data)) {
        $links_array = json_decode($links_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["status" => 1, "message" => "链接数据格式错误"]);
            $conn->close();
            exit;
        }
    } else {
        $links_array = $links_data;
    }

    if (!is_array($links_array)) {
        echo json_encode(["status" => 1, "message" => "链接数据必须是数组格式"]);
        $conn->close();
        exit;
    }

    // 使用当前登录用户的ID
    $current_user_id = $user['id'];
    $inserted_count = 0;
    $error_messages = [];

    // 开始事务
    $conn->begin_transaction();
    
    try {
        // 先删除该用户的所有旧链接
        $deleteSql = "DELETE FROM userlinks WHERE user_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        if (!$deleteStmt) {
            throw new Exception("SQL删除准备失败: " . $conn->error);
        }
        $deleteStmt->bind_param("i", $current_user_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        $insertSql = "INSERT INTO userlinks (user_id, linkicon, linkname, linkurl, tag) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        
        if (!$insertStmt) {
            throw new Exception("SQL准备失败: " . $conn->error);
        }

        foreach ($links_array as $index => $link) {
            // 验证每个链接的必要字段
            if (!isset($link['linkname']) || !isset($link['linkurl'])) {
                $error_messages[] = "第{$index}个链接缺少必要字段";
                continue;
            }

            $linkicon = isset($link['linkicon']) && !empty($link['linkicon']) ? $link['linkicon'] : null;
            $linkname = $link['linkname'];
            $linkurl = $link['linkurl'];
            $tag = isset($link['tag']) && !empty($link['tag']) ? $link['tag'] : null;

            $insertStmt->bind_param("issss", $current_user_id, $linkicon, $linkname, $linkurl, $tag);
            
            if ($insertStmt->execute()) {
                $inserted_count++;
            } else {
                $error_messages[] = "第{$index}个链接添加失败: " . $insertStmt->error;
            }
        }

        $insertStmt->close();
        
        // 提交事务
        $conn->commit();
        
        if ($inserted_count > 0) {
            echo json_encode([
                "status" => 0,
                "message" => "成功更新 {$inserted_count} 个链接",
                "inserted_count" => $inserted_count,
                "errors" => $error_messages
            ]);
        } else {
            echo json_encode([
                "status" => 1,
                "message" => "链接列表已清空",
                "errors" => $error_messages
            ]);
        }
        
    } catch (Exception $e) {
        // 回滚事务
        $conn->rollback();
        echo json_encode(["status" => 500, "message" => $e->getMessage()]);
    }
    
    $conn->close();
    exit;
}

// 删除用户链接
if ($action === 'delete_link') {
    // 验证登录状态
    if (!$user) {
        echo json_encode(["status" => 8, "message" => "请先登录"]);
        $conn->close();
        exit;
    }

    // 验证必填字段
    if (empty($link_id)) {
        echo json_encode(["status" => 1, "message" => "链接ID不能为空"]);
        $conn->close();
        exit;
    }

    // 使用当前登录用户的ID
    $current_user_id = $user['id'];

    // 删除链接（只能删除自己的链接）
    $deleteSql = "DELETE FROM userlinks WHERE id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    if (!$deleteStmt) {
        echo json_encode(["status" => 500, "message" => "SQL准备失败: " . $conn->error]);
        $conn->close();
        exit;
    }
    $deleteStmt->bind_param("ii", $link_id, $current_user_id);
    
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode([
                "status" => 0,
                "message" => "链接删除成功"
            ]);
        } else {
            echo json_encode(["status" => 404, "message" => "链接不存在或无权限"]);
        }
    } else {
        echo json_encode(["status" => 500, "message" => "删除失败: " . $conn->error]);
    }
    $deleteStmt->close();
    $conn->close();
    exit;
}

// 未知操作
echo json_encode(["status" => 6, "message" => "不支持的操作类型"]);
$conn->close();
?>