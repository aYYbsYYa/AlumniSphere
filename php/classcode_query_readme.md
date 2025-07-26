# ClassCode查询功能文档

## 功能描述

classcode_query.php 提供了一个新的API接口，用于查询与指定用户具有相同classcode的其他用户信息。

## API接口

### 请求地址
```
POST/GET /php/classcode_query.php
```

### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| user_id | int | 是 | 目标用户ID |
| token | string | 是 | 已登录用户的token |

### 返回格式
JSON格式

### 返回状态码说明

| 状态码 | 说明 |
|--------|------|
| 0 | 查询成功 |
| 1 | 用户ID不能为空且必须为正整数 |
| 2 | 该用户尚未设置classcode |
| 3 | 该用户的classcode为空 |
| 5 | 用户不存在 |
| 8 | 请先登录或token无效 |
| 500 | 数据库连接失败 |

### 成功返回示例

```json
{
    "status": 0,
    "message": "查询成功",
    "data": {
        "target_user_id": 123,
        "classcode": "CS2024",
        "total_count": 3,
        "target_index": 1,
        "users": [
            {
                "user_id": 456,
                "username": "user1",
                "realname": "张三",
                "university": "清华大学",
                "classcode": "CS2024",
                "cityname": "北京",
                "latitude": 39.9042,
                "longitude": 116.4074
            },
            {
                "user_id": 123,
                "username": "current_user",
                "realname": "王五",
                "university": "清华大学",
                "classcode": "CS2024",
                "cityname": "北京",
                "latitude": 39.9042,
                "longitude": 116.4074
            },
            {
                "user_id": 789,
                "username": "user2",
                "realname": "李四",
                "university": "清华大学",
                "classcode": "CS2024",
                "cityname": "北京",
                "latitude": 39.9042,
                "longitude": 116.4074
            }
        ]
    }
}
```

### 错误返回示例

```json
{
    "status": 2,
    "message": "该用户尚未设置classcode"
}
```

## 使用示例

### GET请求示例
```
GET /php/classcode_query.php?user_id=123&token=your_token_here
```

### POST请求示例
```
POST /php/classcode_query.php
Content-Type: application/x-www-form-urlencoded

user_id=123&token=your_token_here
```

### JavaScript调用示例
```javascript
fetch('/php/classcode_query.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'user_id=123&token=your_token_here'
})
.then(response => response.json())
.then(data => {
    if (data.status === 0) {
        console.log('查询成功:', data.data);
    } else {
        console.error('查询失败:', data.message);
    }
});
```

## 注意事项

1. 查询结果不包含目标用户本身
2. 返回的用户按user_id升序排列
3. 如果没有任何相同classcode的用户，返回的users数组为空
4. 支持GET和POST两种请求方式
5. 所有返回数据均为UTF-8编码