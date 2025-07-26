<?php
/**
 * 用户头像管理接口 - 最终稳定版本
 * 功能：头像上传、获取、缓存控制
 * 修复：带时间戳和不带时间戳的访问问题
 */

// 禁用错误显示，记录错误日志
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 引入数据库配置
$config = require_once __DIR__ . '/config/database.php';

// 创建连接
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// 设置字符集
$conn->set_charset($config['charset']);

// 检查连接
if ($conn->connect_error) {
    error_log("数据库连接失败: " . $conn->connect_error);
    http_response_code(500);
    header('Content-Type: image/png');
    echo generateErrorImage();
    exit;
}

// 创建用户头像表（如果不存在）
initDatabase($conn);

// 创建头像存储目录
$avatarDir = __DIR__ . '/userfile/avatar';
if (!is_dir($avatarDir)) {
    if (!mkdir($avatarDir, 0755, true)) {
        error_log("无法创建头像存储目录: " . $avatarDir);
    }
}

// 处理请求
processRequest($conn, $avatarDir);

$conn->close();

/**
 * 初始化数据库，创建用户头像表
 */
function initDatabase($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS user_avatars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        avatar_path VARCHAR(255) NOT NULL,
        avatar_type VARCHAR(10) NOT NULL DEFAULT 'jpg',
        file_size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id)
    )";
    
    if (!$conn->query($sql)) {
        error_log("创建用户头像表失败: " . $conn->error);
    }
}

/**
 * 处理请求路由
 */
function processRequest($conn, $avatarDir) {
    // 设置CORS头
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // 处理OPTIONS预检请求
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // 根据请求方法路由
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 检查是否为删除操作
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        if ($action === 'delete') {
            handleDeleteAvatar($conn);
        } else {
            handleAvatarUpload($conn, $avatarDir);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetAvatar($conn);
    } else {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(["status" => 405, "message" => "不支持的请求方法"]);
    }
}

/**
 * 生成错误时的图片
 */
function generateErrorImage() {
    // 检查GD库是否安装
    if (!extension_loaded('gd')) {
        // 如果GD库未安装，返回一个简单的透明PNG
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        return $pngData;
    }
    
    $size = 200;
    $image = imagecreatetruecolor($size, $size);
    
    // 红色背景表示错误
    $bgColor = imagecolorallocate($image, 255, 100, 100);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // 填充背景
    imagefill($image, 0, 0, $bgColor);
    
    // 绘制错误图标（简单的X）
    $lineColor = imagecolorallocate($image, 255, 255, 255);
    imageline($image, 50, 50, 150, 150, $lineColor);
    imageline($image, 150, 50, 50, 150, $lineColor);
    
    // 绘制"ERROR"文字
    $fontSize = 5;
    $text = "ERROR";
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = ($size - $textWidth) / 2;
    $y = $size - 30;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // 输出图片到缓冲区
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    
    // 释放内存
    imagedestroy($image);
    
    return $imageData;
}

/**
 * 处理删除头像请求
 */
function handleDeleteAvatar($conn) {
    // 验证用户ID
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if ($userId <= 0) {
        echo json_encode(["status" => 400, "message" => "用户ID无效"]);
        return;
    }

    // 验证token
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $tokenValidation = validateToken($conn, $userId, $token);
    if (!$tokenValidation['valid']) {
        echo json_encode(["status" => 401, "message" => $tokenValidation['message']]);
        return;
    }

    try {
        // 开始事务
        $conn->begin_transaction();
        
        // 获取当前头像信息
        $stmt = $conn->prepare("SELECT avatar_path FROM user_avatars WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->rollback();
            echo json_encode(["status" => 404, "message" => "用户没有头像"]);
            $stmt->close();
            return;
        }
        
        $avatar = $result->fetch_assoc();
        $avatarPath = $avatar['avatar_path'];
        $stmt->close();
        
        // 删除数据库记录
        $stmt = $conn->prepare("DELETE FROM user_avatars WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("删除数据库记录失败: " . $stmt->error);
        }
        $stmt->close();
        
        // 删除文件
        $avatarDir = __DIR__ . '/userfile/avatar';
        $filepath = $avatarDir . '/' . $avatarPath;
        if (file_exists($filepath)) {
            if (!unlink($filepath)) {
                error_log("删除头像文件失败: " . $filepath);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            "status" => 0,
            "message" => "头像删除成功"
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => 500, "message" => $e->getMessage()]);
    }
}

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

function handleAvatarUpload($conn, $avatarDir) {
    // 验证用户ID
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if ($userId <= 0) {
        echo json_encode(["status" => 400, "message" => "用户ID无效"]);
        return;
    }

    // 验证token
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $tokenValidation = validateToken($conn, $userId, $token);
    if (!$tokenValidation['valid']) {
        echo json_encode(["status" => 401, "message" => $tokenValidation['message']]);
        return;
    }

    // 验证用户是否存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => 404, "message" => "用户不存在"]);
        $stmt->close();
        return;
    }
    $stmt->close();

    // 检查是否有文件上传
    if (!isset($_FILES['avatar'])) {
        echo json_encode(["status" => 400, "message" => "没有文件被上传"]);
        return;
    }
    
    $file = $_FILES['avatar'];
    
    // 检查文件上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = '文件上传错误';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMsg = '文件大小超过服务器限制 (' . ini_get('upload_max_filesize') . ')';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = '文件大小超过表单限制';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = '文件只上传了一部分';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = '没有文件被上传';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = '服务器临时目录不可用';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = '文件写入失败，请检查权限';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMsg = '文件上传被服务器扩展阻止';
                break;
        }
        echo json_encode(["status" => 400, "message" => $errorMsg, "error_code" => $file['error']]);
        return;
    }

    $file = $_FILES['avatar'];
    
    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(["status" => 400, "message" => "只允许上传JPG、PNG、GIF、WebP格式的图片"]);
        return;
    }

    // 验证文件大小 (最大2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        echo json_encode(["status" => 400, "message" => "文件大小不能超过2MB"]);
        return;
    }

    // 获取文件扩展名
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(["status" => 400, "message" => "不支持的文件格式"]);
        return;
    }

    // 生成唯一的头像ID
    $avatarId = uniqid('avatar_' . $userId . '_', true);
    $filename = $avatarId . '.' . $fileExtension;
    $filepath = $avatarDir . '/' . $filename;

    // 检查临时文件是否存在
    if (!file_exists($file['tmp_name'])) {
        echo json_encode(["status" => 400, "message" => "临时文件不存在"]);
        return;
    }
    
    // 检查目标目录是否可写
    if (!is_writable(dirname($filepath))) {
        echo json_encode(["status" => 500, "message" => "目标目录不可写: " . dirname($filepath)]);
        return;
    }
    
    // 检查磁盘空间
    if (disk_free_space(dirname($filepath)) < $file['size']) {
        echo json_encode(["status" => 500, "message" => "磁盘空间不足"]);
        return;
    }
    
    // 移动上传的文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $error = error_get_last();
        echo json_encode([
            "status" => 500, 
            "message" => "文件移动失败", 
            "details" => $error ? $error['message'] : '未知错误',
            "source" => $file['tmp_name'],
            "destination" => $filepath,
            "dir_writable" => is_writable(dirname($filepath))
        ]);
        return;
    }
    
    // 验证文件是否成功移动
    if (!file_exists($filepath)) {
        echo json_encode(["status" => 500, "message" => "文件移动后未找到"]);
        return;
    }

    // 开始事务
    $conn->begin_transaction();
    
    try {
        // 删除用户的旧头像（如果有）
        $stmt = $conn->prepare("SELECT avatar_path FROM user_avatars WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $oldFilepath = $avatarDir . '/' . $row['avatar_path'];
            if (file_exists($oldFilepath)) {
                unlink($oldFilepath);
            }
        }
        $stmt->close();

        // 删除数据库中的旧记录
        $stmt = $conn->prepare("DELETE FROM user_avatars WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // 插入新的头像记录
        $stmt = $conn->prepare("INSERT INTO user_avatars (user_id, avatar_path, avatar_type, file_size) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $userId, $filename, $fileExtension, $file['size']);
        
        if (!$stmt->execute()) {
            throw new Exception("数据库插入失败: " . $stmt->error);
        }
        
        $avatarIdDb = $conn->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            "status" => 0,
            "message" => "头像上传成功",
            "data" => [
                "avatar_id" => $avatarIdDb,
                "avatar_path" => $filename,
                "avatar_url" => "/php/userfile/avatar/" . $filename,
                "user_id" => $userId
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // 删除已上传的文件
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        echo json_encode(["status" => 500, "message" => $e->getMessage()]);
    }
}

/**
 * 处理获取头像请求
 */
function handleGetAvatar($conn) {
    // 支持通过GET或POST获取用户ID
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : (isset($_POST['user_id']) ? intval($_POST['user_id']) : 0);
    
    if ($userId <= 0) {
        // 如果没有提供user_id，检查是否直接请求头像文件
        $path = $_SERVER['REQUEST_URI'];
        $pathParts = explode('/', $path);
        $filename = end($pathParts);
        
        if (!empty($filename) && strpos($filename, 'avatar_') === 0) {
            serveAvatarFile($filename);
            return;
        }
        
        // 设置正确的Content-Type为JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => 400, "message" => "用户ID无效"]);
        return;
    }

    // 查询用户的头像
    $stmt = $conn->prepare("SELECT avatar_path, avatar_type FROM user_avatars WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // 返回默认头像
        header('Content-Type: image/png');
        // 添加防缓存头
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // 生成默认头像（简单的圆形头像）
        $defaultAvatar = generateDefaultAvatar($userId);
        echo $defaultAvatar;
        exit;
    }
    
    $avatar = $result->fetch_assoc();
    $stmt->close();
    
    // 直接提供头像文件
    serveAvatarFile($avatar['avatar_path']);
}

/**
 * 提供头像文件
 */
function serveAvatarFile($filename) {
    $avatarDir = __DIR__ . '/userfile/avatar';
    $filepath = $avatarDir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        // 检查是否请求的是图片，如果是则返回404图片，否则返回JSON
        if (strpos($_SERVER['HTTP_ACCEPT'], 'image') !== false) {
            header('Content-Type: image/png');
            // 返回一个小的透明PNG
            $emptyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
            echo $emptyPng;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["status" => 404, "message" => "头像文件不存在"]);
        }
        exit;
    }
    
    // 获取文件类型
    $fileExtension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    $contentType = $contentTypes[$fileExtension] ?? 'application/octet-stream';
    
    // 设置响应头 - 添加防缓存控制
    $cacheBuster = isset($_GET['t']) ? $_GET['t'] : '';
    if ($cacheBuster) {
        // 如果有时间戳参数，允许缓存
        header('Cache-Control: max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    } else {
        // 无时间戳参数，禁用缓存
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    // 设置内容类型和长度
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($filepath));
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filepath)) . ' GMT');
    
    // 处理If-Modified-Since头
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $fileModified = filemtime($filepath);
        
        if ($ifModifiedSince >= $fileModified) {
            http_response_code(304);
            exit;
        }
    }
    
    // 输出文件内容
    readfile($filepath);
    exit;
}

/**
 * 生成默认头像
 */
function generateDefaultAvatar($userId) {
    // 检查GD库是否安装
    if (!extension_loaded('gd')) {
        // 如果GD库未安装，返回一个简单的PNG图片
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        return $pngData;
    }
    
    $size = 200;
    $image = imagecreatetruecolor($size, $size);
    
    // 背景色（基于用户ID生成不同颜色）
    $bgColor = imagecolorallocate($image, 
        100 + ($userId * 50) % 155, 
        100 + ($userId * 70) % 155, 
        100 + ($userId * 90) % 155
    );
    
    // 填充背景
    imagefill($image, 0, 0, $bgColor);
    
    // 绘制圆形
    $circleColor = imagecolorallocate($image, 255, 255, 255);
    imagefilledellipse($image, $size/2, $size/2, $size-20, $size-20, $circleColor);
    
    // 绘制用户ID
    $textColor = imagecolorallocate($image, 50, 50, 50);
    $username = strval($userId);
    
    // 使用内置字体，避免字体文件问题
    $fontSize = 5; // PHP内置字体
    $textWidth = imagefontwidth($fontSize) * strlen($username);
    $textHeight = imagefontheight($fontSize);
    
    // 确保文字在图片中心
    $x = max(0, ($size - $textWidth) / 2);
    $y = max(0, ($size - $textHeight) / 2);
    
    imagestring($image, $fontSize, $x, $y, $username, $textColor);
    
    // 输出图片到缓冲区
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    
    // 释放内存
    imagedestroy($image);
    
    return $imageData;
}
?>