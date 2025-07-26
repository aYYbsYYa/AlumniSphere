<template>
	<div class="audio-recorder-container">
		<h3>参考音频</h3>
		<p>录制一段你的声音，让你的校友更好地认识你。</p>
		<div class="audio-controls">
			<button
				@click="toggleRecording"
				:class="['btn-record', { 'is-recording': isRecording }]"
			>
				{{ isRecording ? '停止录音' : '开始录音' }}
			</button>
			<button @click="uploadAudio" class="btn-upload" :disabled="!audioBlob">上传音频</button>
		</div>
		<div v-if="statusMessage" class="status-message">{{ statusMessage }}</div>
		<div v-if="audioPlayerUrl" class="audio-preview">
			<h4>录音预览:</h4>
			<audio :src="audioPlayerUrl" controls></audio>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { convertToWav } from '../../utils/audioConverter'

const props = defineProps({
	userId: {
		type: String,
		required: true,
	},
	token: {
		type: String,
		required: true,
	},
})

const isRecording = ref(false)
const mediaRecorder = ref<MediaRecorder | null>(null)
const audioChunks = ref<Blob[]>([])
const audioBlob = ref<Blob | null>(null)
const audioBase64 = ref('')
const audioPlayerUrl = ref('')
const statusMessage = ref('')
const recordingTimeoutId = ref<number | null>(null)
const recordingStartTime = ref(0)

const toggleRecording = () => {
	if (isRecording.value) {
		stopRecording()
	} else {
		startRecording()
	}
}

const startRecording = async () => {
	try {
		const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
		mediaRecorder.value = new MediaRecorder(stream)
		audioChunks.value = []
		audioBlob.value = null
		audioPlayerUrl.value = ''
		statusMessage.value = '正在录音... (最长9秒)'
		recordingStartTime.value = Date.now()

		mediaRecorder.value.ondataavailable = (event) => {
			audioChunks.value.push(event.data)
		}

		mediaRecorder.value.onstop = async () => {
			if (recordingTimeoutId.value) clearTimeout(recordingTimeoutId.value)

			const duration = (Date.now() - recordingStartTime.value) / 1000
			if (duration < 3) {
				isRecording.value = false
				statusMessage.value = `录音时间太短 (仅 ${duration.toFixed(1)} 秒), 请至少录制3秒。`
				audioBlob.value = null
				audioBase64.value = ''
				audioPlayerUrl.value = ''
				stream.getTracks().forEach((track) => track.stop())
				return
			}

			const rawBlob = new Blob(audioChunks.value, { type: 'audio/webm' }) // 原始格式通常是 webm

			try {
				statusMessage.value = '正在转换音频格式...'
				const wavBlob = await convertToWav(rawBlob)
				audioBlob.value = wavBlob
				audioPlayerUrl.value = URL.createObjectURL(audioBlob.value)

				const reader = new FileReader()
				reader.readAsDataURL(audioBlob.value)
				reader.onloadend = () => {
					audioBase64.value = reader.result as string
					statusMessage.value = `录音完成 (时长: ${duration.toFixed(
						1
					)} 秒), 格式转换成功，可以预览或上传。`
				}
			} catch (error) {
				console.error('音频转换失败:', error)
				statusMessage.value = '音频格式转换失败，请重试。'
				audioBlob.value = null
				audioBase64.value = ''
				audioPlayerUrl.value = ''
			} finally {
				isRecording.value = false
				stream.getTracks().forEach((track) => track.stop())
			}
		}

		mediaRecorder.value.start()
		isRecording.value = true

		recordingTimeoutId.value = setTimeout(() => {
			if (isRecording.value) {
				stopRecording()
			}
		}, 9000)
	} catch (error) {
		console.error('无法获取麦克风权限:', error)
		statusMessage.value = '错误：无法访问麦克风。请检查浏览器权限。'
		//alert('无法访问麦克风。请在浏览器设置中允许访问麦克风。')
	}
}

const stopRecording = () => {
	if (mediaRecorder.value && isRecording.value) {
		mediaRecorder.value.stop()
		if (recordingTimeoutId.value) {
			clearTimeout(recordingTimeoutId.value)
			recordingTimeoutId.value = null
		}
	}
}

const uploadAudio = async () => {
	if (!audioBase64.value) {
		//alert('没有可上传的录音或录音正在转换中。')
		return
	}

	statusMessage.value = '正在上传...'
	const payload = {
		user_id: props.userId,
		token: props.token,
		audio_base64: audioBase64.value,
	}

	try {
		const response = await fetch('https://alumnisphereapi.liy.ink/useraudio.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify(payload),
		})

		const result = await response.json()
		if (response.ok && result.status === 'success') {
			statusMessage.value = '上传成功！'
			//alert('音频上传成功！')
		} else {
			throw new Error(result.message || '上传失败')
		}
	} catch (error) {
		console.error('音频上传失败:', error)
		const errorMessage = error instanceof Error ? error.message : '未知错误'
		statusMessage.value = `上传失败: ${errorMessage}`
		//alert(`上传失败: ${errorMessage}`)
	}
}
</script>

<style scoped>
.audio-recorder-container {
	margin-top: 20px;
	padding: 20px;
	border: 1px solid #e0e0e0;
	border-radius: 15px;
	background-color: #fdfdfd;
}

.audio-recorder-container h3 {
	margin-top: 0;
}

.audio-controls {
	display: flex;
	align-items: center;
	gap: 15px;
	margin-top: 10px;
}

.btn-record {
	background-color: #48bb78;
	border: none;
	padding: 10px 20px;
	border-radius: 20px;
	color: white;
	cursor: pointer;
	font-weight: bold;
}
.btn-record.is-recording {
	background-color: #e53e3e;
}
.btn-record:hover {
	opacity: 0.9;
}

.btn-upload {
	background-color: #3182ce;
	border: none;
	padding: 10px 20px;
	border-radius: 20px;
	color: white;
	cursor: pointer;
	font-weight: bold;
}
.btn-upload:hover {
	background-color: #2b6cb0;
}
.btn-upload:disabled {
	background-color: #a0aec0;
	cursor: not-allowed;
}

.status-message {
	margin-top: 15px;
	color: #4a5568;
	font-style: italic;
}

.audio-preview {
	margin-top: 15px;
}

.audio-preview audio {
	width: 100%;
	border-radius: 25px;
}
</style>
