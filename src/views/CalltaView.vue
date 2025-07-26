<template>
	<CalltaHeader />
	<div class="call-container">
		<h1>与TA聊聊</h1>
		<p class="status">状态: {{ status }}</p>
		<button @click="toggleListening" :disabled="isProcessing">
			{{ isListening ? '说话完毕' : '点击说话' }}
		</button>

		<div v-if="showModal" class="modal-overlay">
			<div class="modal-content">
				<h3>识别到以下内容:</h3>
				<p>{{ recognizedText }}</p>
				<div class="modal-buttons">
					<button @click="cancelSend">取消</button>
					<button @click="confirmSend">发送</button>
				</div>
			</div>
		</div>

		<div class="conversation-area">
			<div
				v-for="message in conversation"
				:key="message.id"
				:class="['message', message.role]"
			>
				<p>
					<strong>{{ message.role === 'user' ? '你' : 'AI' }}:</strong>
					{{ message.content }}
				</p>
				<audio v-if="message.audioUrl" :src="message.audioUrl" controls autoplay></audio>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import CalltaHeader from '@/components/callta/CalltaHeader.vue'

interface Message {
	id: number
	role: 'user' | 'assistant'
	content: string
	audioUrl?: string
}

const isListening = ref(false)
const isProcessing = ref(false)
const status = ref('准备就绪')
const recognizedText = ref('')
const showModal = ref(false)
const conversation = ref<Message[]>([])
const userId = ref('')
const token = ref('')
const targetUserInfo = ref(null)

let recognition: SpeechRecognition | null = null

const initializeRecognition = () => {
	const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
	if (!SpeechRecognition) {
		status.value = '您的浏览器不支持语音识别'
		return
	}

	recognition = new SpeechRecognition()
	recognition.continuous = false
	recognition.lang = 'zh-CN'
	recognition.interimResults = false

	recognition.onstart = () => {
		status.value = '正在聆听...'
	}

	recognition.onresult = (event: SpeechRecognitionEvent) => {
		const text = event.results[0][0].transcript
		recognizedText.value = text
		status.value = `识别完成: ${text}`
		showModal.value = true
		isListening.value = false
	}

	recognition.onend = () => {
		isListening.value = false
		// No need to update status here, as it's handled by other events.
	}

	recognition.onerror = (event: SpeechRecognitionErrorEvent) => {
		status.value = `语音识别错误: ${event.error}`
		isListening.value = false
	}
}

onMounted(() => {
	const route = useRoute()
	userId.value = (route.query.id as string) || ''

	const userInfoStr = localStorage.getItem('userInfo')
	if (userInfoStr) {
		const userInfo = JSON.parse(userInfoStr)
		token.value = userInfo.token || ''
	}

	fetchUserInfo()
	initializeRecognition()
})

onUnmounted(() => {
	if (recognition) {
		recognition.stop()
	}
})

const toggleListening = () => {
	if (isListening.value) {
		if (recognition) {
			recognition.stop()
		}
		isListening.value = false
		status.value = '监听已停止'
	} else {
		initializeRecognition() // Re-initialize for every new listening session
		if (recognition) {
			try {
				recognition.start()
				isListening.value = true
			} catch (e) {
				console.error('语音识别启动失败', e)
				status.value = '无法启动监听'
			}
		}
	}
}

const cancelSend = () => {
	showModal.value = false
	recognizedText.value = ''
	status.value = '已取消'
}

const confirmSend = async () => {
	showModal.value = false
	isProcessing.value = true
	status.value = '正在处理...'

	const userMessageContent = recognizedText.value
	conversation.value.push({ id: Date.now(), role: 'user', content: userMessageContent })
	recognizedText.value = ''

	try {
		status.value = 'AI 正在思考...'
		const aiMessage: Message = { id: Date.now() + 1, role: 'assistant', content: '' }
		conversation.value.push(aiMessage)

		const stream = await openAICall(userMessageContent)
		const reader = stream.getReader()
		const decoder = new TextDecoder('utf-8')
		let done = false

		while (!done) {
			const { value, done: readerDone } = await reader.read()
			if (value) {
				const chunk = decoder.decode(value, { stream: true })
				const lines = chunk.split('\n\n')
				for (const line of lines) {
					if (line.startsWith('data: ')) {
						const dataStr = line.substring(6)
						if (line.includes('[DONE]')) {
							done = true
							break
						}
						try {
							const data = JSON.parse(dataStr)
							if (data.choices && data.choices[0].delta.content) {
								aiMessage.content += data.choices[0].delta.content
							}
						} catch (e) {
							console.error('Error parsing stream data:', e, 'data:', dataStr)
						}
					}
				}
			}
			if (readerDone) {
				break
			}
		}

		status.value = '正在生成语音...'
		const audioUrl = await vocalCloneCall(aiMessage.content)
		aiMessage.audioUrl = audioUrl
		status.value = '处理完成'
	} catch (error) {
		console.error('处理请求时出错:', error)
		status.value = '处理失败'
		const lastMessage = conversation.value[conversation.value.length - 1]
		if (lastMessage && lastMessage.role === 'assistant' && !lastMessage.content) {
			conversation.value.pop()
		}
	} finally {
		isProcessing.value = false
	}
}

const fetchUserInfo = async () => {
	if (!userId.value || !token.value) return
	try {
		const response = await fetch(
			`https://alumnisphereapi.liy.ink/userinfo.php?action=get&token=${token.value}&user_id=${userId.value}`,
		)
		if (response.ok) {
			const data = await response.json()
			if (data.status === 0 && data.data) {
				targetUserInfo.value = data.data
				status.value = `已加载用户 ${data.data.username || ''} 的信息，可以开始对话。`
			} else {
				status.value = '获取用户信息失败。'
			}
		}
	} catch (error) {
		console.error('Error fetching user info:', error)
		status.value = '获取用户信息时出错。'
	}
}

async function openAICall(prompt: string): Promise<ReadableStream<Uint8Array>> {
	let systemPrompt = `I will give you detailed information about the user. Please role-play as them and reply in the first person. Keep your responses concise, around 50 words. Match the tone to the user's personality as described.`
	if (targetUserInfo.value) {
		systemPrompt += `
The following is the detailed information about the user.:
${JSON.stringify(targetUserInfo.value, null, 2)}`
	}

	const response = await fetch('https://advxaiapi.liy.ink/v1/chat/completions', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			Authorization: `Bearer sk-eaeWXvM6ldKwSKph82w6etvI1HfnlqAkYKzV23AQ0I55aZe4`,
		},
		body: JSON.stringify({
			model: 'kimi-k2-0711-preview',
			max_tokens: 4096,
			temperature: 0.7,
			messages: [
				{ role: 'system', content: systemPrompt },
				{ role: 'user', content: prompt },
			],
			stream: true,
		}),
	})

	if (!response.ok) {
		const errorText = await response.text()
		throw new Error(`API request failed with status ${response.status}: ${errorText}`)
	}

	if (!response.body) {
		throw new Error('Response body is null')
	}

	return response.body
}

async function vocalCloneCall(text: string): Promise<string> {
	const response = await fetch('https://alumnispherepyapi.liy.ink/api/vocal_clone', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({
			uid: userId.value,
			token: token.value,
			text: text,
		}),
	})

	if (!response.ok) {
		const errorText = await response.text()
		throw new Error(
			`Vocal clone API request failed with status ${response.status}: ${errorText}`,
		)
	}

	const data = await response.json()
	if (data.audio) {
		const audioBase64 = data.audio
		// Assuming the audio is mp3. If not, this might need adjustment.
		return `data:audio/mpeg;base64,${audioBase64}`
	} else {
		throw new Error('Vocal clone API response does not contain audio data.')
	}
}
</script>

<style scoped>
.call-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 20px;
	font-family: sans-serif;
	max-width: 800px;
	margin: auto;
}

.status {
	margin: 15px 0;
	color: #666;
	min-height: 20px;
	font-size: 1.1em;
}

button {
	padding: 12px 25px;
	font-size: 16px;
	cursor: pointer;
	border: none;
	border-radius: 25px;
	background-color: #007bff;
	color: white;
	margin: 5px;
	transition:
		background-color 0.3s,
		transform 0.2s;
}

button:hover:not(:disabled) {
	background-color: #0056b3;
	transform: translateY(-2px);
}

button:disabled {
	cursor: not-allowed;
	background-color: #cccccc;
}

.modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.6);
	display: flex;
	justify-content: center;
	align-items: center;
	z-index: 1000;
}

.modal-content {
	background-color: white;
	padding: 30px;
	border-radius: 12px;
	text-align: center;
	width: 90%;
	max-width: 450px;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
	margin-top: 0;
}

.modal-buttons {
	margin-top: 25px;
	display: flex;
	justify-content: space-around;
}

.conversation-area {
	margin-top: 25px;
	width: 100%;
	border: 1px solid #e0e0e0;
	border-radius: 12px;
	padding: 20px;
	height: 50vh;
	overflow-y: auto;
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.message {
	padding: 12px 18px;
	border-radius: 18px;
	max-width: 85%;
	word-wrap: break-word;
}

.message.user {
	background-color: #dcf8c6;
	align-self: flex-end;
	border-bottom-right-radius: 4px;
}

.message.assistant {
	background-color: #f1f0f0;
	align-self: flex-start;
	border-bottom-left-radius: 4px;
}

.message p {
	margin: 0;
	padding-bottom: 8px;
}

audio {
	width: 100%;
	margin-top: 8px;
}
</style>
