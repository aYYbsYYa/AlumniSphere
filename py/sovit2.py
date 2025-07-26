import requests
import base64
import urllib3
import os
import pymysql
from flask import jsonify
# --- 数据库查询功能 ---
def get_prompt_text_from_db(record_id):
    """根据记录ID从数据库获取prompt_text"""
    db_config = {
        'host': os.getenv('DB_HOST', 'localhost'),
        'port': int(os.getenv('DB_PORT', 3306)),
        'user': os.getenv('DB_USER', 'advx'),
        'password': os.getenv('DB_PASSWORD', 'LSBDmraFbyYwiZaR'),
        'database': os.getenv('DB_NAME', 'advx'),
        'charset': 'utf8mb4',
        'cursorclass': pymysql.cursors.DictCursor
    }
    
    prompt_text = None
    connection = None  # 初始化 connection
    try:
        connection = pymysql.connect(**db_config)
        with connection.cursor() as cursor:
            sql = "SELECT `text` FROM `vocals_text` WHERE `id` = %s"
            cursor.execute(sql, (record_id,))
            result = cursor.fetchone()
            if result:
                prompt_text = result['text']
                #print(f"数据库查询成功！ID为 {record_id} 的 text 是: {prompt_text}")
            else:
                prompt_text ="我有多喜欢你，可能我自己都描述不出来，就像你是月亮，我是星星，满天星河只为你。"
    except pymysql.MySQLError as e:
        print(f"数据库错误: {e}")
    finally:
        if connection and connection.open:
            connection.close()
    return prompt_text
def gen(userid,totext):
    # --- 主逻辑 ---
    userid=str(userid)
    #totext = "你好，这是一段测试音频"

    # 从数据库获取 prompt_text
    prompt_text = get_prompt_text_from_db(userid)

    # 如果从数据库获取失败，则不继续执行
    if not prompt_text:
        return jsonify({"code":404,"msg":"无法获取 prompt_text。"})

    # 禁用 InsecureRequestWarning 警告
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

    # curl 命令中的 URL
    url = "https://0380955d64a02112-23456.jp-tyo-2.gpu-instance.ppinfra.com/voice/gpt-sovits"

    # multipart/form-data 中的文本字段
    data = {
        "prompt_text": prompt_text,
        "id": "0",
        "prompt_lang": "auto",
        "preset": "default",
        "batch_size": "10000",
        "text": totext,
    }

    # 输出文件名
    output_filename = f'''{userid}_clone.wav'''
    user_vocals_dir = "user_vocals"

    try:
        # 在 user_vocals 文件夹中查找以 userid 开头的音频文件
        reference_audio_path = None
        for filename in os.listdir(user_vocals_dir):
            if filename.startswith(userid):
                reference_audio_path = os.path.join(user_vocals_dir, filename)
                break

        if not reference_audio_path:
           reference_audio_path="user_vocals/default.wav"

        #print(f"找到参考音频: {reference_audio_path}")

        # 读取参考音频文件
        with open(reference_audio_path, 'rb') as f:
            audio_content = f.read()

        # 准备要发送的文件数据
        files = {
            'reference_audio': (os.path.basename(reference_audio_path), audio_content, 'audio/wav')
        }

        #print(f"正在向 {url} 发送请求...")
        
        # 发送 POST 请求，禁用 SSL 证书验证
        response = requests.post(url, data=data, files=files, verify=False)

        # 检查请求是否成功
        response.raise_for_status()

        # 将响应内容写入输出文件
        with open(f'''user_vocals/{output_filename}''', "wb") as out_file:
            out_file.write(response.content)
        
        # 将响应内容转码为base64
        audio_base64 = base64.b64encode(response.content).decode('utf-8')
        return jsonify({"code":200,"msg":f"请求成功！音频内容已保存到 {output_filename}","audio":audio_base64,"readtext":totext})

    except FileNotFoundError:
        return jsonify({"code":500})#jsonify({"code":500,"msg":f"错误：找不到文件夹 '{user_vocals_dir}'。"})
    except requests.exceptions.RequestException as e:
        return jsonify({"code":500,"msg":f"请求失败: {e}"})