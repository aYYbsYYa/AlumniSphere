<template>
	<RegisterHeader />
	<div class="register-container">
		<video
			class="background-video"
			autoplay
			muted
			loop
			playsinline
		>
			<source src="https://y.liy.ink/img/bg1.mp4" type="video/mp4">
		</video>
		<div class="register-form-container">
			<div class="brand">AluMniSphere</div>
			<div class="register-box">
				<h2>AluMniSphere - Register</h2>
				<form @submit.prevent="register">
					<div class="input-group">
						<input
							v-model="nickname"
							type="text"
							id="nickname"
							placeholder="昵称"
							required
						/>
					</div>
					<div class="input-group">
						<input
							v-model="email"
							type="email"
							id="email"
							placeholder="邮箱"
							required
						/>
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
					<div class="input-group">
						<input
							v-model="confirmPassword"
							type="password"
							id="confirmPassword"
							placeholder="确认密码"
							required
						/>
					</div>
					<div v-if="passwordError" class="error-message">
						{{ passwordError }}
					</div>
					<button type="submit">注册</button>
				</form>
				<!--<div class="links">
					<router-link to="/login">已有账号？去登录</router-link>
				</div>-->
			</div>
		</div>
	</div>
</template>

<script setup lang="ts" name="RegisterView">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import RegisterHeader from '@/components/RegisterView/RegisterHeader.vue'

const router = useRouter()

const nickname = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const passwordError = ref('')

/**
 * 保存用户信息到 localStorage
 * @param data 后端返回的 data 对象
 */
// function saveUserInfoToLocal(data: any) {
// 	localStorage.setItem('userInfo', JSON.stringify(data))
// }

const register = async () => {
	if (password.value !== confirmPassword.value) {
		passwordError.value = '两次输入的密码不一致，请重新输入'
		return
	}
	passwordError.value = ''

	try {
		const response = await fetch('https://alumnisphereapi.liy.ink/user.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'register',
				username: nickname.value,
				email: email.value,
				password: password.value,
			}),
		})

		const result = await response.json()
		if (result.status === 0) {
			// 保存data到localStorage
			// saveUserInfoToLocal(result.data)
			//alert('注册成功，请前往登录！')
			// 可选：跳转到登录页
			router.push('/login')
		} else {
			console.log(result)
			//alert(result.message || '注册失败，请重试')
		}
	} catch (error) {
		console.error('注册请求异常:', error)
		//alert('注册请求异常，请检查网络')
	}
}
</script>

<style scoped>
.register-container {
	display: flex;
	height: 100vh;
	width: 100vw;
	position: fixed;
	top: 0;
	left: 0;
	overflow: hidden;
	justify-content: flex-end; /* 将表单容器推到右侧 */
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

.register-form-container {
	flex-shrink: 0;
	padding: 40px 60px;
	background-color: rgba(255, 255, 255, 0.15);
	backdrop-filter: blur(25px);
	-webkit-backdrop-filter: blur(25px);
	color: white;
	display: flex;
	flex-direction: column;
	justify-content: center;
	z-index: 1;
	border: 1px solid rgba(255, 255, 255, 0.2);
	border-radius: 10px;
	box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
}

.brand {
	font-size: 24px;
	font-weight: bold;
	position: absolute;
	top: 40px;
	left: 60px;
	color: #fff;
}

.register-box {
	width: 100%;
}

h2 {
	text-align: left;
	margin-bottom: 2.5rem;
	font-size: 28px;
	font-weight: 300;
}

.input-group {
	margin-bottom: 2rem; /* 与登录页保持一致的间距 */
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
	justify-content: center;
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

.error-message {
	color: #ff7eb3; /* 更改错误信息颜色以匹配主题 */
	margin-bottom: 1rem;
	text-align: center;
}
</style>
