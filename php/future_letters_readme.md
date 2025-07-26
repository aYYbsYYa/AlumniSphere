# 未来信件功能使用说明

## 功能概述
实现了完整的未来信件系统，支持将信件存入数据库并根据启封时间自动判断锁定状态。

## 文件结构

### 1. 数据库相关文件
- `create_future_letters_table.php` - 创建数据库表
- `config/database.php` - 数据库配置

### 2. 功能接口文件
- `future_letter.php` - 创建新的未来信件
- `get_future_letters.php` - 获取所有信件列表
- `update_letter_status.php` - 更新信件锁定状态（定时任务）

## 使用方法

### 1. 初始化数据库
```bash
php http://gyip.liip.top:48040/create_future_letters_table.php
```

### 2. 创建未来信件
**接口地址**: `POST http://gyip.liip.top:48040/php/future_letter.php`

**必需参数**:
- `title`: 信件标题
- `unlock_date`: 启封日期，格式：YYYY-MM-DD
- `content`: 信件内容
- `user_id`: 用户ID
- `token`: 用户认证token（存储在users表中）

**示例请求**:
```bash
curl -X POST http://gyip.liip.top:48040/php/future_letter.php \
  -d "title=给三年后的自己&unlock_date=2027-12-31&content=希望你一切都好&user_id=1&token=ad49686687f1aab5b7fc1395ac65b5ef53be05ccb080cdf289b72c0800a22d171753325775"
```

**成功返回示例**:
```json
{
    "success": true,
    "message": "信件创建成功",
    "data": {
        "id": 5,
        "title": "给三年后的自己",
        "content": "希望你一切都好",
        "submit_date": "2024-12-27",
        "unlock_date": "2027-12-31",
        "locked": true
    },
    "letters": [
        {
            "id": 1,
            "title": "测试token验证",
            "content": "这是测试token验证的内容",
            "submit_date": "2024-12-27",
            "unlock_date": "2025-12-31",
            "locked": true
        },
        {
            "id": 5,
            "title": "给三年后的自己",
            "content": "希望你一切都好",
            "submit_date": "2024-12-27",
            "unlock_date": "2027-12-31",
            "locked": true
        }
    ]
}
```

**错误响应**:
```json
{"success": false, "message": "token无效或已过期"}
```

### 3. 获取所有信件
**接口地址**: `GET http://gyip.liip.top:48040/php/get_future_letters.php`

**必需参数**:
- `user_id`: 用户ID
- `token`: 用户认证token（存储在users表中）

**示例请求**:
```bash
# 获取特定用户的信件
curl "http://gyip.liip.top:48040/php/get_future_letters.php?user_id=1&token=ad49686687f1aab5b7fc1395ac65b5ef53be05ccb080cdf289b72c0800a22d171753325775"
```

**成功返回示例**:
```json
[
    {
        "id": 1,
        "title": "测试token验证",
        "content": "这是测试token验证的内容",
        "submit_date": "2024-12-27",
        "unlock_date": "2025-12-31",
        "locked": true
    },
    {
        "id": 2,
        "title": "给三年后的自己",
        "content": "希望你一切都好",
        "submit_date": "2024-12-27",
        "unlock_date": "2027-12-31",
        "locked": true
    }
]
```

**空列表返回示例**:
```json
[]
```

**错误返回示例**:
```json
{"success": false, "message": "token无效或已过期"}
```

**参数错误返回示例**:
```json
{"error": "缺少必需参数：user_id和token"}
```

## 数据库表结构

```sql
CREATE TABLE future_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    unlock_date DATE NOT NULL,
    commit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT 1,
    status ENUM('locked', 'unlocked') DEFAULT 'locked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 锁定状态逻辑
- **locked**: 启封时间未到，信件处于锁定状态
- **unlocked**: 启封时间已到，信件可以查看

锁定状态会根据当前日期与启封日期自动判断和更新。

## 使用注意事项
1. **Token验证**: 所有接口都需要有效的用户token，请先确保用户已注册并获取token
2. **锁定状态判断**: 系统基于当前机器时间与启封时间的实时比较，无需手动更新状态
3. **时间精度**: 锁定状态精确到秒级，确保准确性
4. **日期格式**: 所有日期使用YYYY-MM-DD格式，系统会自动转换为本地时间显示
5. **数据安全**: 建议定期备份数据库，防止数据丢失

## 完整使用流程

### 1. 用户注册
```bash
curl -X POST http://gyip.liip.top:48040/user.php \
  -d "action=register&username=新用户&email=newuser@example.com&password=123456"
```

**返回示例**:
```json
{
  "status": 0,
  "message": "注册成功",
  "data": {
    "userid": 4,
    "username": "新用户",
    "email": "newuser@example.com",
    "token": "新生成的token字符串"
  }
}
```

### 2. 用户登录
```bash
curl -X POST http://gyip.liip.top:48040/user.php \
  -d "action=login&username=新用户&password=123456"
```

### 3. 创建数据表（首次使用）
```bash
curl http://gyip.liip.top:48040/create_future_letters_table.php
```

### 4. 创建未来信件
```bash
curl -X POST http://gyip.liip.top:48040/future_letter.php \
  -d "title=新年愿望&content=希望新的一年身体健康，工作顺利！&unlock_date=2026-01-01&user_id=3&token=ad49686687f1aab5b7fc1395ac65b5ef53be05ccb080cdf289b72c0800a22d171753325775"
```

### 5. 查询用户信件
```bash
curl "http://gyip.liip.top:48040/get_future_letters.php?user_id=3&token=ad49686687f1aab5b7fc1395ac65b5ef53be05ccb080cdf289b72c0800a22d171753325775"
```