<template>
	<div class="profile-container">
		<!-- Left Panel -->
		<div class="left-panel">
			<ProfileAvatar :user-id="userId" :token="token" />
			<UserInfo :token="token" :user-id="userId" />
		</div>

		<!-- Right Panel -->
		<div class="right-panel">
			<div class="tabs">
				<span class="tab active">Others</span>
			</div>
			<div class="audio-controls">
				<button @click="playAudio" class="btn-play-audio">听听TA的声音</button>
				<button @click="chatWithUser" class="btn-chat">与TA聊聊</button>
			</div>
			<audio ref="audioPlayer" style="display: none"></audio>
			<div class="university-info">
				<div class="header">
					<h3>教育背景</h3>
				</div>
				<div class="display-view">
					<p><strong>大学:</strong> {{ university || '未填写' }}</p>
					<p><strong>专业:</strong> {{ major || '未填写' }}</p>
					<p><strong>高中毕业年份:</strong> {{ graduation_year || '未填写' }}</p>
					<p><strong>班级代码:</strong> {{ classcode || '未填写' }}</p>
				</div>
			</div>
			<div class="suggestion-box">
				<p>这些你可能会感兴趣👋</p>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup name="UserInfo">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProfileAvatar from '@/components/UserInfo/ProfileAvatar.vue'
import UserInfo from '@/components/UserInfo/UserInfo.vue'

const route = useRoute()
const router = useRouter()
const username = ref('')
const userId = ref('')
const loggedInUserId = ref('')
const token = ref('')
const audioPlayer = ref<HTMLAudioElement | null>(null)

const university = ref('')
const major = ref('')
const graduation_year = ref<number | null>(null)
const classcode = ref('')

const playAudio = async () => {
	try {
		const response = await fetch('https://alumnispherepyapi.liy.ink/api/vocal_clone', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				uid: userId.value,
				token: token.value,
				text: '你好，很高兴认识你',
			}),
		})

		if (!response.ok) {
			throw new Error('Network response was not ok')
		}

		const result = await response.json()
		if (result.audio) {
			const audioUrl = `data:audio/wav;base64,${result.audio}`
			if (audioPlayer.value) {
				audioPlayer.value.src = audioUrl
				audioPlayer.value.play()
			}
		} else {
			throw new Error('Audio data not found in response')
		}
	} catch (error) {
		console.error('Error fetching or playing audio:', error)
		////alert('播放声音失败，请稍后再试。')
	}
}

const chatWithUser = () => {
	router.push({ path: '/callta', query: { id: userId.value } })
}

const fetchEducationInfo = async () => {
	try {
		// Note: The API endpoint needs to support fetching other users' info by user_id
		const response = await fetch(
			`https://alumnisphereapi.liy.ink/userinfo.php?action=get&token=${token.value}&user_id=${userId.value}`,
		)
		if (response.ok) {
			const data = await response.json()
			if (data.status === 0 && data.data) {
				const eduData = data.data
				university.value = eduData.university || ''
				major.value = eduData.major || ''
				graduation_year.value = eduData.graduation_year || null
				classcode.value = eduData.classcode || ''
			}
		}
	} catch (error) {
		console.error('Error fetching education info:', error)
	}
}

onMounted(() => {
	userId.value = route.params.id as string

	const userInfoStr = localStorage.getItem('userInfo')
	if (userInfoStr) {
		const userInfo = JSON.parse(userInfoStr)
		username.value = userInfo.username || ''
		token.value = userInfo.token || ''
		loggedInUserId.value = userInfo.id || ''
	}
	fetchEducationInfo()
})
</script>

<style scoped>
.audio-controls {
	display: flex;
	gap: 15px;
	margin-bottom: 20px;
}

.btn-play-audio,
.btn-chat {
	border: none;
	padding: 10px 20px;
	border-radius: 20px;
	color: white;
	cursor: pointer;
	font-weight: bold;
	transition: background-color 0.3s;
}

.btn-play-audio {
	background-color: #48bb78; /* Green */
}

.btn-play-audio:hover {
	background-color: #38a169;
}

.btn-chat {
	background-color: #3182ce; /* Blue */
}

.btn-chat:hover {
	background-color: #2b6cb0;
}

.profile-container {
	display: flex;
	font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
	max-width: 1200px;
	margin: 0 auto;
	padding: 20px;
	gap: 40px;
}

.left-panel {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.right-panel {
	flex: 2;
}

.tabs {
	display: flex;
	gap: 20px;
	font-size: 1.2em;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
	margin-bottom: 20px;
}

.tab {
	cursor: pointer;
	color: #888;
}

.tab.active {
	color: #000;
	font-weight: bold;
	border-bottom: 2px solid #000;
}

.suggestion-box {
	margin-top: 30px;
	padding: 20px;
	border: 2px dashed #e0e0e0;
	border-radius: 15px;
	text-align: center;
	color: #777;
}

.university-info {
	margin-top: 20px;
	padding: 20px;
	border: 1px solid #e0e0e0;
	border-radius: 15px;
	background-color: #fdfdfd;
}

.university-info .header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
}

.university-info h3 {
	margin: 0;
}

.display-view {
	padding: 10px;
	border-radius: 8px;
}

.display-view p {
	margin: 8px 0;
	line-height: 1.6;
}
</style>
