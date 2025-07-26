<template>
	<div class="user-info-container">
		<h1 class="username">{{ username }}</h1>
		<div class="info-display-container">
			<p class="realname">{{ realname || '未设置真实姓名' }}</p>
		</div>
		<div class="bio-container">
			<div class="bio-display-container">
				<p class="bio" v-html="formattedBio || '暂无简介信息。'"></p>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
	token: {
		type: String,
		required: true,
	},
	userId: {
		type: String,
		required: true,
	},
})

const realname = ref('')
const username = ref('')
const bio = ref('')

const formattedBio = computed(() => {
	return bio.value ? bio.value.replace(/\n/g, '<br>') : '暂无简介信息。'
})

const fetchUserInfo = async (userToken: string, userId: string) => {
	if (!userToken || !userId) return
	try {
		const response = await fetch(
			`http://gyip.liip.top:48040/userinfo.php?action=get&token=${userToken}&user_id=${userId}`,
		)
		if (!response.ok) {
			throw new Error('Network response was not ok')
		}
		const responseData = await response.json()
		if (responseData.data) {
			bio.value = responseData.data.bio || ''
			realname.value = responseData.data.realname || '未设置真实姓名'
			username.value = responseData.data.username || '未设置用户名'
		} else {
			bio.value = ''
			realname.value = '未设置真实姓名'
		}
	} catch (error) {
		console.error('Failed to fetch user info:', error)
		bio.value = ''
		realname.value = '无法加载信息'
	}
}

watch(
	() => [props.token, props.userId],
	([newToken, newUserId]) => {
		if (newToken && newUserId) {
			fetchUserInfo(newToken, newUserId)
		}
	},
	{ immediate: true },
)
</script>

<style scoped>
.user-info-container {
	width: 100%;
	text-align: center;
}
.username {
	font-size: 2.5em;
	margin: 0;
}

.realname {
	color: #666;
	margin-top: 5px;
	font-size: 1.1em;
}

.info-display-container {
	min-height: 38px; /* Match height of input */
}

.bio-container {
	width: 100%;
	margin-top: 20px;
	text-align: left;
}

.bio-display-container {
	position: relative;
	padding: 10px;
	border-radius: 8px;
	width: 100%;
}

.bio {
	list-style: none;
	padding: 0;
	line-height: 1.8;
	margin: 0; /* remove default margin */
	min-height: 24px; /* Ensure container has height even if bio is empty */
}
</style>
