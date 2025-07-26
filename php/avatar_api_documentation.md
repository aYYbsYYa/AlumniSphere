# 用户头像系统API文档

## 📋 接口概览

### 基础信息
- **基础URL**: `http://gyip.liip.top:48040/`
- **数据格式**: JSON (响应) / multipart/form-data (上传)
- **字符编码**: UTF-8

### 接口列表
| 接口名称 | 请求方法 | URL路径 | 描述 |
|---------|----------|---------|------|
| 上传头像 | POST | `/useravatar.php` | 上传用户头像图片 |
| 获取头像 | GET | `/useravatar.php` | 获取用户头像图片 |
| 删除头像 | DELETE | `/useravatar.php` | 删除用户头像 |

---

## 📤 上传头像

### 请求信息
- **URL**: `/useravatar.php`
- **方法**: POST
- **Content-Type**: multipart/form-data

### 请求参数
| 参数名 | 类型 | 必填 | 描述 | 限制 |
|--------|------|------|------|------|
| user_id | int | 是 | 用户ID | 必须为正整数 |
| token | string | 是 | 用户登录token | 用户登录后获得的认证token |
| avatar | file | 是 | 头像图片文件 | JPG/PNG/GIF/WebP, ≤2MB |

### 成功响应
```json
{
  "status": 0,
  "message": "头像上传成功",
  "data": {
    "avatar_id": 123,
    "avatar_path": "avatar_123_abc123.jpg",
    "avatar_url": "/userfile/avatar/avatar_123_abc123.jpg",
    "user_id": 123
  }
}
```

### 错误响应
```json
{
  "status": 400,
  "message": "文件大小不能超过2MB"
}
```

### 使用示例
**cURL示例:**
```bash
curl -X POST http://gyip.liip.top:48040/useravatar.php \
  -F "user_id=123" \
  -F "token=your_login_token_here" \
  -F "avatar=@/path/to/avatar.jpg"
```

**HTML表单示例:**
```html
<form action="/useravatar.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="user_id" value="123">
  <input type="hidden" name="token" value="your_login_token_here">
  <input type="file" name="avatar" accept="image/*">
  <button type="submit">上传头像</button>
</form>
```

---

## 📥 获取头像

### 请求信息
- **URL**: `/useravatar.php`
- **方法**: GET
- **Content-Type**: image/* (返回图片)

### 请求参数
| 参数名 | 类型 | 必填 | 描述 | 示例 |
|--------|------|------|------|------|
| user_id | int | 是* | 用户ID | 123 |
| t | string | 否 | 时间戳参数，用于缓存控制 | 1234567890 |

> *注：user_id为必填参数，除非直接访问头像文件名

### 响应说明
- **成功**: 返回头像图片文件
- **用户无头像**: 返回基于用户ID生成的默认彩色圆形头像
- **错误**: 返回透明PNG图片

### 缓存策略
- **带时间戳参数(t)**: 允许缓存1小时
- **无时间戳参数**: 禁止缓存，强制刷新

### 使用示例
**获取用户头像:**
```bash
# 获取用户123的头像
curl "http://gyip.liip.top:48040/useravatar.php?user_id=123"

# 获取用户123的头像并允许缓存
curl "http://gyip.liip.top:48040/useravatar.php?user_id=123&t=1234567890"
```

**HTML图片标签:**
```html
<!-- 基本用法 -->
<img src="/useravatar.php?user_id=123" alt="用户头像">

<!-- 强制刷新 -->
<img src="/useravatar.php?user_id=123&t=1234567890" alt="用户头像">
```

---

## 🗑️ 删除头像

### 请求信息
- **URL**: `/useravatar.php`
- **方法**: DELETE (或POST带action参数)
- **Content-Type**: application/json

### 请求参数
| 参数名 | 类型 | 必填 | 描述 |
|--------|------|------|------|
| user_id | int | 是 | 用户ID |
| token | string | 是 | 用户登录token |
| action | string | 是 | 固定值: `delete` |

### 成功响应
```json
{
  "status": 0,
  "message": "头像删除成功"
}
```

### 使用示例
```bash
curl -X POST http://gyip.liip.top:48040/useravatar.php \
  -d "action=delete&user_id=123&token=your_login_token_here"
```

---

## ⚠️ 错误码说明

| 状态码 | 描述 | 常见原因 |
|--------|------|----------|
| 0 | 成功 | 操作成功完成 |
| 400 | 参数错误 | user_id无效、文件格式不支持、文件过大 |
| 401 | 未授权 | token缺失、token无效或已过期 |
| 404 | 资源不存在 | 用户不存在、头像不存在 |
| 405 | 方法不允许 | 使用了不支持的HTTP方法 |
| 500 | 服务器错误 | 数据库连接失败、文件系统错误 |

### 文件上传特定错误
| 错误码 | 描述 | 解决方案 |
|--------|------|----------|
| UPLOAD_ERR_INI_SIZE | 文件大小超过php.ini限制 | 调整php.ini中的upload_max_filesize |
| UPLOAD_ERR_FORM_SIZE | 文件大小超过表单限制 | 检查HTML表单MAX_FILE_SIZE |
| UPLOAD_ERR_PARTIAL | 文件只上传了一部分 | 检查网络连接，重新上传 |
| UPLOAD_ERR_NO_FILE | 没有文件被上传 | 确保选择了文件再提交 |
| UPLOAD_ERR_NO_TMP_DIR | 服务器临时目录不可用 | 联系服务器管理员 |
| UPLOAD_ERR_CANT_WRITE | 文件写入失败 | 检查目录权限和磁盘空间 |

---

## 🛠️ 技术规范

### 文件限制
- **格式**: JPG, JPEG, PNG, GIF, WebP
- **大小**: 最大2MB
- **尺寸**: 无限制，但建议正方形图片

### 存储结构
```
/wwwdata/php/userfile/avatar/
├── avatar_{user_id}_{unique_id}.jpg
├── avatar_{user_id}_{unique_id}.png
└── ...
```

### 数据库表结构
```sql
CREATE TABLE user_avatars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    avatar_path VARCHAR(255) NOT NULL,
    avatar_type VARCHAR(10) NOT NULL DEFAULT 'jpg',
    file_size INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

---

