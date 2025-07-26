<template>
	<LoginHeader />
	<div class="login-container">
		<video class="background-video" autoplay muted loop playsinline>
			<source src="https://y.liy.ink/img/bg1.mp4" type="video/mp4" />
		</video>
		<div class="login-form-container">
			<div class="brand">AluMniSphere</div>
			<div class="login-box">
				<h2>AluMniSphere - Login</h2>
				<form @submit.prevent="login">
					<div class="input-group">
						<input v-model="email" type="text" id="email" placeholder="邮箱" required />
					</div>
					<div class="input-group">
						<input
							v-model="password"
							type="password"
							id="password"
							placeholder="密码"
							required
						/>
					</div>
					<button type="submit">登录</button>
				</form>
				<!--<div class="links">
					<router-link to="/register">注册账号</router-link>
					<a href="#">无法登录?</a>
				</div>-->
			</div>
		</div>
	</div>
</template>

<script setup lang="ts" name="LoginView">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoginHeader from '@/components/LoginView/LoginHeader.vue'

const email = ref('')
const password = ref('')
const router = useRouter()

interface UserInfo {
	userid: number
	username: string
	email: string
	token: string
}
function saveUserInfoToLocal(data: UserInfo) {
	localStorage.setItem('userInfo', JSON.stringify(data))
}

const login = async () => {
	if (!email.value || !password.value) {
		alert('请输入用户名和密码')
		return
	}

	// 判断输入的是邮箱还是昵称
	const isEmail = email.value.includes('@')
	const payload: Record<string, string> = {
		action: 'login',
		password: password.value,
	}
	if (isEmail) {
		payload.email = email.value
	} else {
		payload.username = email.value
	}

	try {
		const response = await fetch('https://alumnisphereapi.liy.ink/user.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams(payload),
		})
		const result = await response.json()
		if (result.status === 0) {
			saveUserInfoToLocal(result.data)
			alert('登录成功！')
			// 跳转到主页或个人中心
			router.push('/')
		} else {
			console.log(result)
			alert(result.message || '登录失败，请重试')
		}
	} catch (error) {
		console.error('登录请求异常:', error)
		alert('登录请求异常，请检查网络')
	}
}
</script>

<style scoped>
.login-container {
	display: flex;
	height: 100vh;
	width: 100vw;
	position: fixed;
	top: 0;
	left: 0;
	overflow: hidden;
}

.background-video {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	z-index: -1;
}

.login-form-container {
	flex-shrink: 0;
	padding: 40px 60px;
	background-color: rgba(255, 255, 255, 0.15); /* 保持背景颜色的透明度 */
	backdrop-filter: blur(25px);
	-webkit-backdrop-filter: blur(25px);
	color: white;
	display: flex;
	flex-direction: column;
	justify-content: center;
	z-index: 1;
	border: 1px solid rgba(255, 255, 255, 0.2);
	border-radius: 10px; /* 圆角处理增加美感 */
	box-shadow: 0 0 30px rgba(0, 0, 0, 0.2); /* 阴影处理增加视觉效果 */
}

.brand {
	font-size: 24px;
	font-weight: bold;
	position: absolute;
	top: 40px;
	left: 60px;
	color: #fff;
}

.login-box {
	width: 100%;
}

h2 {
	text-align: left;
	margin-bottom: 2.5rem;
	font-size: 28px;
	font-weight: 300;
}

.input-group {
	margin-bottom: 2rem;
	position: relative;
}

input {
	width: 100%;
	padding: 12px 15px;
	background: rgba(255, 255, 255, 0.5);
	border: 1px solid rgba(255, 255, 255, 0.2);
	border-radius: 8px;
	color: #fff;
	font-size: 16px;
	transition: all 0.3s ease;
	box-sizing: border-box;
}

input::placeholder {
	color: #aaa;
	transition: all 0.3s ease;
}

input:focus::placeholder {
	color: transparent;
}

input:focus {
	outline: none;
	border-color: #ff7eb3;
	background: rgba(255, 255, 255, 0.2);
}

button[type='submit'] {
	width: 100%;
	padding: 12px;
	background-color: #ff7eb3;
	border: none;
	border-radius: 4px;
	color: white;
	font-size: 16px;
	font-weight: bold;
	cursor: pointer;
	margin-top: 1rem;
	transition: background-color 0.3s ease;
}

button[type='submit']:hover {
	background-color: #e66a9f;
}

.links {
	display: flex;
	justify-content: space-between;
	margin-top: 1.5rem;
	font-size: 14px;
}

.links a {
	color: #ccc;
	text-decoration: none;
	transition: color 0.3s ease;
}

.links a:hover {
	color: #fff;
}
</style>
