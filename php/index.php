<?php
header("Content-Type: application/json");

// 获取GET参数
$params = $_GET;

// 构建响应数据
$response = [
    'status_code' => 200,
    'success' => true,
    'data' => $params,
    'message' => 'GET参数已成功接收'
];

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>