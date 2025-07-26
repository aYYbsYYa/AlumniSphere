import os
import base64
import requests
import random
import string
import re
from flask import Flask, jsonify, request
from flask_cors import CORS
import pymysql  # 新增导入
import sovit2 as vocal

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False
CORS(app)

@app.route('/', methods=['GET', 'POST'])
def hello():
    if request.method == 'POST':
        data = request.get_json() or {}
        return jsonify({
            'code': 200,
            'message': '请求已接收',
            'received_data': data
        })
    return 'Hello, World!'

@app.route('/api/ucount', methods=['GET'])
def get_user_count():
    try:
        # 连接MySQL数据库
        conn = pymysql.connect(
            host=os.getenv('DB_HOST', '127.0.0.1'),
            port=int(os.getenv('DB_PORT', 3306)),
            user=os.getenv('DB_USER', 'advx'),
            password=os.getenv('DB_PASSWORD', 'LSBDmraFbyYwiZaR'),
            database=os.getenv('DB_NAME', 'advx'),
            charset='utf8mb4'
        )
        with conn.cursor() as cursor:
            cursor.execute("SELECT COUNT(*) FROM users")
            count = cursor.fetchone()[0]
        return jsonify({'code': 200, 'count': count})
    except pymysql.MySQLError as e:
        return jsonify({'code': 500, 'error': f'数据库错误: {str(e)}'})
    except Exception as e:
        return jsonify({'code': 500, 'error': f'服务器错误: {str(e)}'})
    finally:
        if 'conn' in locals() and conn:
            conn.close()
@app.route('/api/geodecode', methods=['GET'])
def geodecode():
    city = request.args.get('geo')
    if not city:
        return jsonify({'code': 400, 'error': '缺少geo参数'})
    
    # 调用高德地图v5地点搜索API
    try:
        url = f"https://restapi.amap.com/v5/place/text?keywords={city}&key=82c77d9dce8a69b0bd135b342a03cf62"
        response = requests.get(url)
        data = response.json()
        
        if data['status'] == '1' and len(data['pois']) > 0:
            # 获取第一个POI结果
            first_poi = data['pois'][0]
            location = first_poi['location'].split(',')
            cityname = first_poi['cityname']
            return jsonify({
                'code': 200,
                'cityname': cityname,
                'lat': float(location[1]),
                'lng': float(location[0])
            })
        else:
            return jsonify({'code': 404, 'error': '未找到该地点'})
    except Exception as e:
        return jsonify({'code': 500, 'error': f'地理编码服务错误: {str(e)}'})

@app.route('/api/alumnisrc', methods=['GET'])
def get_alumni_locations():
    try:
        conn = pymysql.connect(
            host=os.getenv('DB_HOST', '127.0.0.1'),
            port=int(os.getenv('DB_PORT', 3306)),
            user=os.getenv('DB_USER', 'advx'),
            password=os.getenv('DB_PASSWORD', 'LSBDmraFbyYwiZaR'),
            database=os.getenv('DB_NAME', 'advx'),
            charset='utf8mb4'
        )
        with conn.cursor() as cursor:
            cursor.execute("""
                SELECT user_id, realname, latitude, longitude 
                FROM userinfo 
                WHERE show_on_map = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
            """)
            alumni = []
            for row in cursor.fetchall():
                alumni.append({
                    'user_id': row[0],
                    'realname': row[1],
                    'lat': float(row[2]),
                    'lng': float(row[3])
                })
            return jsonify(alumni)
    except pymysql.MySQLError as e:
        return jsonify({'code': 500, 'error': f'数据库错误: {str(e)}'})
    except Exception as e:
        return jsonify({'code': 500, 'error': f'服务器错误: {str(e)}'})
    finally:
        if 'conn' in locals() and conn:
            conn.close()

@app.route('/api/uploadvoice', methods=['POST'])
def upload_voice():
    try:
        # 检查Content-Type
        if not request.is_json:
            return jsonify({'code': 415, 'error': 'Unsupported Media Type: Content-Type must be application/json'})
            
        # 获取参数
        data = request.get_json()
        user_id = data.get('userid')
        token = data.get('token')
        audio_b64 = data.get('rec')
        
        if not all([user_id, token, audio_b64]):
            return jsonify({'code': 400, 'error': '缺少必要参数'})
        
        # 验证用户token
        conn = pymysql.connect(
            host=os.getenv('DB_HOST', '127.0.0.1'),
            port=int(os.getenv('DB_PORT', 3306)),
            user=os.getenv('DB_USER', 'advx'),
            password=os.getenv('DB_PASSWORD', 'LSBDmraFbyYwiZaR'),
            database=os.getenv('DB_NAME', 'advx'),
            charset='utf8mb4'
        )
        with conn.cursor() as cursor:
            cursor.execute("SELECT id FROM users WHERE id = %s AND token = %s", (user_id, token))
            if not cursor.fetchone():
                return jsonify({'code': 403, 'error': '无效的用户凭证'})
        
        # 创建存储目录
        os.makedirs('user_vocals', exist_ok=True)
        
        # 解码并保存音频
        if ',' not in audio_b64:
            return jsonify({'code': 400, 'error': '无效的音频格式: 缺少base64前缀'})
        parts = audio_b64.split(',')
        if len(parts) < 2:
            return jsonify({'code': 400, 'error': '无效的音频格式'})
        try:
            audio_data = base64.b64decode(parts[1])
            filename = f"user_vocals/{user_id}_vocals.wav"
            with open(filename, 'wb') as f:
                f.write(audio_data)
        except:
            return jsonify({'code': 400, 'error': '无效的base64音频数据'})
           # 调用语音转文字API
        try:
            url = "https://api.siliconflow.cn/v1/audio/transcriptions"
            headers = {
                "Authorization": "Bearer sk-ablsmhrbzfyhhnamfxrutqggjchogpgmljlgkeqwnkrrlqnh"
            }
            with open(filename, 'rb') as f:
                files = {
                    'file': f,
                    'model': (None, 'FunAudioLLM/SenseVoiceSmall')
                }
                response = requests.post(url, headers=headers, files=files)
            response.raise_for_status()
            import re
            text_result = re.sub(r'[^\w\s,.?!，。？！]', '', response.json().get('text', ''))
            
            # 存储到数据库
            with conn.cursor() as cursor:
                cursor.execute("SELECT id FROM vocals_text WHERE id = %s", (user_id,))
                if cursor.fetchone():
                    cursor.execute("UPDATE vocals_text SET text = %s WHERE id = %s", (text_result, user_id))
                else:
                    cursor.execute("INSERT INTO vocals_text (id, text) VALUES (%s, %s)", (user_id, text_result))
                conn.commit()
            
            return jsonify({
                'code': 200, 
                'message': '音频上传并转文字成功',
                'filename': filename,
                'text': text_result
            })
        except Exception as e:
            return jsonify({
                'code': 500, 
                'error': f'语音转文字失败: {str(e)}',
                'filename': filename
            })
        
    except pymysql.MySQLError as e:
        return jsonify({'code': 500, 'error': f'数据库错误: {str(e)}'})
    except Exception as e:
        return jsonify({'code': 500, 'error': f'服务器错误: {str(e)}'})
    finally:
        if 'conn' in locals() and conn:
            conn.close()

@app.route('/api/vocal_clone', methods=['GET','POST'])
def vocal_clone():
    if request.method == 'POST':
        # 从POST请求中获取uid
        data = request.get_json()
        uid = str(data.get('uid'))
        text = str(data.get('text'))
        if not uid:
            return jsonify({'code': 400, 'error': '缺少uid参数'})
        status = vocal.gen(uid,text)
        return status
    else:
        # GET请求方式，从URL参数获取uid
        uid = str(request.args.get('uid'))
        text = str(request.args.get('text'))
        if not uid:
            return jsonify({'code': 400, 'error': '缺少uid参数'})
        status = vocal.gen(uid,text)
        return status
if __name__ == '__main__':
    app.run(host="0.0.0.0", port=48050, debug=True)