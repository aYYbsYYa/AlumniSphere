<template>
	<div class="page-container">
		<div class="chat-wrapper">
			<div class="chat-container">
				<div class="message-list">
					<div v-if="showGreeting" class="message opponent-message">
						<p>Hi，你好👏</p>
					</div>
					<div v-if="showQuestion1" class="message opponent-message">
						<p>你叫什么名字？</p>
					</div>
					<div v-if="showAnswer1" class="message my-message">
						<p>{{ name }}</p>
					</div>
					<div v-if="showQuestion2" class="message opponent-message">
						<p>请你做个自我介绍。</p>
					</div>
					<div v-if="showAnswer2" class="message my-message">
						<p>{{ introduction }}</p>
					</div>
					<div v-if="showEnding" class="message opponent-message">
						<p>感谢你的填写！</p>
					</div>
				</div>
				<div class="input-area">
					<form
						v-if="showQuestion1 && !showAnswer1"
						@submit.prevent="handleNameSubmit"
						class="message-form"
					>
						<input type="text" v-model="name" placeholder="输入你的名字" required />
						<button type="submit">发送</button>
					</form>
					<form
						v-if="showQuestion2 && !showAnswer2"
						@submit.prevent="handleIntroductionSubmit"
						class="message-form"
					>
						<textarea
							v-model="introduction"
							placeholder="输入自我介绍"
							required
						></textarea>
						<button type="submit">发送</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup name="ProfileisFirstTime">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const name = ref('')
const introduction = ref('')
const showGreeting = ref(false)
const showQuestion1 = ref(false)
const showAnswer1 = ref(false)
const showQuestion2 = ref(false)
const showAnswer2 = ref(false)
const showEnding = ref(false)
const router = useRouter()

onMounted(() => {
	setTimeout(() => {
		showGreeting.value = true
		setTimeout(() => {
			showQuestion1.value = true
		}, 1000)
	}, 500)
})

const handleNameSubmit = () => {
	showAnswer1.value = true
	setTimeout(() => {
		showQuestion2.value = true
	}, 500)
}

const handleIntroductionSubmit = () => {
	showAnswer2.value = true

	const userInfoString = localStorage.getItem('userInfo')
	if (!userInfoString) {
		//alert('用户未登录')
		// 可以在这里添加跳转到登录页的逻辑
		return
	}
	const userInfo = JSON.parse(userInfoString)
	const token = userInfo.token

	const params = new URLSearchParams()
	params.append('action', 'save')
	params.append('token', token)
	params.append('realname', name.value)
	params.append('bio', introduction.value)

	fetch('https://alumnisphereapi.liy.ink/userinfo.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: params.toString(),
	})
		.then((response) => {
			if (!response.ok) {
				////alert('提交失败')
			}
			// 假设API成功后也会返回一些信息，但我们遵循原逻辑直接跳转
			setTimeout(() => {
				showEnding.value = true
				setTimeout(() => {
					router.push({ name: 'profileEdit' })
				}, 2000)
			}, 500)
		})
		.catch((error) => {
			console.error('Error submitting user info:', error)
			////alert('提交失败')
			setTimeout(() => {
				showEnding.value = true
				setTimeout(() => {
					router.push({ name: 'profileEdit' })
				}, 2000)
			}, 500)
		})
}
</script>

<style>
html,
body {
	overflow: hidden !important;
}
</style>

<style scoped>
.page-container {
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100vh;
	/*background-color: #e5e5e5;*/
}

.chat-wrapper {
	width: 375px; /* iPhone X width */
	height: 667px; /* iPhone 8 height */
	border: 1px solid #ccc;
	border-radius: 20px;
	overflow: hidden;
	box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
	/*margin-bottom: 20; /* 底部间隔 */
}

.chat-container {
	display: flex;
	flex-direction: column;
	height: 100%;
	background-color: #f5f5f5;
}

.message-list {
	flex-grow: 1;
	padding: 20px;
	overflow-y: auto;
	display: flex;
	flex-direction: column;
}

.message {
	margin-bottom: 15px;
	padding: 10px 15px;
	border-radius: 20px;
	max-width: 70%;
	animation: fadeIn 0.5s ease-in-out;
	position: relative;
}

.opponent-message {
	background-color: #ffffff;
	align-self: flex-start;
	border-top-left-radius: 0;
}

.my-message {
	background-color: #95ec69;
	align-self: flex-end;
	text-align: left;
	border-top-right-radius: 0;
}

.input-area {
	padding: 10px;
	background-color: #ffffff;
	border-top: 1px solid #e0e0e0;
}

.message-form {
	display: flex;
	align-items: center;
}

.message-form input,
.message-form textarea {
	flex-grow: 1;
	margin-right: 10px;
	padding: 10px;
	border: 1px solid #e0e0e0;
	border-radius: 20px;
	resize: none;
}

.message-form button {
	padding: 10px 20px;
	background-color: #07c160;
	color: white;
	border: none;
	cursor: pointer;
	border-radius: 20px;
	font-weight: bold;
}

.message-form button:hover {
	background-color: #06ad56;
}

@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>
