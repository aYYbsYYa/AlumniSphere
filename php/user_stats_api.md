# 用户统计信息接口文档

## 接口地址
GET `/php/user_stats.php`

## 功能描述
获取用户的综合统计信息，包括：
1. 距离下一封未来信件送达的天数
2. 相同城市的用户数量
3. 相同班级代码的用户数量

## 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| user_id | int | 是 | 用户ID |
| token | string | 是 | 用户认证token |

## 请求示例
```
GET https://alumnisphereapi.liy.ink/user_stats.php?user_id=9&token=e368973afb3fa2b874d50a5f1c8e165f9c8f2ec55e741e35c23835da316deb531753343047
```

## 响应格式

### 成功响应示例

#### 示例1：完整数据
```json
{
    "next_letter_days": 354,
    "same_city_count": 2,
    "same_class_count": 4,
    "cityname": "沈阳市",
    "classcode": "advx2025"
}
```

#### 示例2：无未来信件
```json
{
    "next_letter_days": null,
    "same_city_count": 5,
    "same_class_count": 0,
    "cityname": "北京市",
    "classcode": ""
}
```

#### 示例3：新用户无信息
```json
{
    "next_letter_days": null,
    "same_city_count": 0,
    "same_class_count": 0,
    "cityname": null,
    "classcode": null
}
```

### 字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| next_letter_days | int/null | 距离下一封信件的天数，如果没有未送达的信件则为null |
| same_city_count | int | 相同城市的用户数量（包含自己） |
| same_class_count | int | 相同班级代码的用户数量（包含自己） |
| cityname | string/null | 用户所在城市名称 |
| classcode | string/null | 用户班级代码 |

### 实际测试数据
基于用户ID=9的测试结果：
- **距离下一封信件**：354天后
- **同城用户**：3人（沈阳市，包含当前用户）
- **同班用户**：5人（advx2025班级，包含当前用户）

### 错误响应

#### 缺少参数
```json
{
    "error": "缺少必需参数：user_id和token"
}
```

#### token无效
```json
{
    "error": "token无效或已过期"
}
```

#### 用户不存在
```json
{
    "error": "用户不存在"
}
```

#### 数据库连接失败
```json
{
    "error": "数据库连接失败: [错误信息]"
}
```

## 使用示例

### 1. 直接浏览器访问
```
https://alumnisphereapi.liy.ink/user_stats.php?user_id=9&token=e368973afb3fa2b874d50a5f1c8e165f9c8f2ec55e741e35c23835da316deb531753343047
```

### 2. JavaScript (Fetch API)
```javascript
// 基础使用
const userId = 9;
const token = 'e368973afb3fa2b874d50a5f1c8e165f9c8f2ec55e741e35c23835da316deb531753343047';

fetch(`https://alumnisphereapi.liy.ink/user_stats.php?user_id=${userId}&token=${token}`)
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('API错误:', data.error);
            return;
        }
        
        // 显示统计信息
        console.log('📮 距离下一封信件:', data.next_letter_days ? `${data.next_letter_days}天` : '暂无');
        console.log('🏙️ 同城用户:', data.same_city_count, '人');
        console.log('🏫 同班用户:', data.same_class_count, '人');
        console.log('📍 城市:', data.cityname || '未设置');
        console.log('📚 班级:', data.classcode || '未设置');
    })
    .catch(error => console.error('网络错误:', error));

// 带加载状态的完整示例
async function loadUserStats(userId, token) {
    try {
        const response = await fetch(`https://alumnisphereapi.liy.ink/user_stats.php?user_id=${userId}&token=${token}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return {
            nextLetter: data.next_letter_days,
            cityCount: data.same_city_count,
            classCount: data.same_class_count,
            cityName: data.cityname,
            classCode: data.classcode
        };
    } catch (error) {
        console.error('获取用户统计失败:', error);
        return null;
    }
}
```

### 3. Python 使用示例
```python
import requests
import json

def get_user_stats(user_id, token):
    """获取用户统计信息"""
    url = f"https://alumnisphereapi.liy.ink/user_stats.php"
    params = {
        'user_id': user_id,
        'token': token
    }
    
    try:
        response = requests.get(url, params=params)
        data = response.json()
        
        if 'error' in data:
            print(f"错误: {data['error']}")
            return None
            
        return data
    except Exception as e:
        print(f"请求失败: {e}")
        return None

# 使用示例
user_id = 9
token = "e368973afb3fa2b874d50a5f1c8e165f9c8f2ec55e741e35c23835da316deb531753343047"

stats = get_user_stats(user_id, token)
if stats:
    print("=== 用户统计信息 ===")
    print(f"下一封信件: {stats['next_letter_days']}天后" if stats['next_letter_days'] else "暂无未来信件")
    print(f"同城用户: {stats['same_city_count']}人")
    print(f"同班用户: {stats['same_class_count']}人")
    print(f"所在城市: {stats['cityname']}")
    print(f"班级代码: {stats['classcode']}")
```

### 4. PHP 使用示例
```php
<?php
// 基础使用
$userId = 9;
$token = 'e368973afb3fa2b874d50a5f1c8e165f9c8f2ec55e741e35c23835da316deb531753343047';
$url = "https://alumnisphereapi.liy.ink/user_stats.php?user_id={$userId}&token={$token}";

$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['error'])) {
    echo "错误: " . $data['error'];
} else {
    echo "📮 距离下一封信件: " . ($data['next_letter_days'] ? "{$data['next_letter_days']}天后" : "暂无") . "\n";
    echo "🏙️ 同城用户: {$data['same_city_count']}人\n";
    echo "🏫 同班用户: {$data['same_class_count']}人\n";
    echo "📍 城市: {$data['cityname']}\n";
    echo "📚 班级: {$data['classcode']}\n";
}

// 使用cURL的完整示例
function getUserStats($userId, $token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://alumnisphereapi.liy.ink/user_stats.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_GET, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'user_id' => $userId,
        'token' => $token
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>
```

### 5. React/Vue 组件示例
```javascript
// React Hook示例
import { useState, useEffect } from 'react';

function useUserStats(userId, token) {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (!userId || !token) return;

        const fetchStats = async () => {
            try {
                const response = await fetch(
                    `https://alumnisphereapi.liy.ink/user_stats.php?user_id=${userId}&token=${token}`
                );
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                setStats(data);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        fetchStats();
    }, [userId, token]);

    return { stats, loading, error };
}

// 使用示例
function UserStats() {
    const { stats, loading, error } = useUserStats(9, 'your_token');
    
    if (loading) return <div>加载中...</div>;
    if (error) return <div>错误: {error}</div>;
    
    return (
        <div>
            <h3>用户统计信息</h3>
            <p>下一封信件: {stats.next_letter_days ? `${stats.next_letter_days}天后` : '暂无'}</p>
            <p>同城用户: {stats.same_city_count}人</p>
            <p>同班用户: {stats.same_class_count}人</p>
        </div>
    );
}
```

## 响应码说明

| HTTP状态码 | 含义说明 |
|------------|----------|
| 200 | 请求成功 |
| 400 | 参数错误 |
| 401 | 认证失败 |
| 500 | 服务器内部错误 |

## 性能优化建议
1. **缓存策略**: 建议在客户端缓存统计结果，避免频繁请求
2. **分页加载**: 如果需要展示详细用户列表，建议分页获取
3. **错误重试**: 网络异常时可进行有限次数的重试

## 注意事项
1. **token有效期**: token过期后需要重新登录获取
2. **统计范围**: 统计数量包含当前用户自己
3. **数据完整性**: 如果用户未填写城市或班级信息，相应统计为0
4. **时区处理**: 日期计算基于服务器时区设置
5. **并发限制**: 建议客户端控制请求频率，避免对服务器造成压力