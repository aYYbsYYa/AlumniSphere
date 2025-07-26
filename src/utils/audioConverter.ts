// src/utils/audioConverter.ts

/**
 * 将 MediaRecorder 生成的 Blob 对象转换为标准的 WAV 格式 Blob。
 * @param blob - 从 MediaRecorder 获取的原始 Blob 对象。
 * @returns - 返回一个包含 WAV 音频数据的新 Blob 对象。
 */
export function convertToWav(blob: Blob): Promise<Blob> {
	return new Promise((resolve, reject) => {
		const reader = new FileReader()

		reader.onload = async (event) => {
			if (!event.target?.result) {
				return reject(new Error('Failed to read blob as ArrayBuffer.'))
			}

			const arrayBuffer = event.target.result as ArrayBuffer
			const AudioContext = window.AudioContext || (window as { webkitAudioContext?: typeof window.AudioContext }).webkitAudioContext
			if (!AudioContext) {
				return reject(new Error('Web Audio API is not supported in this browser.'))
			}
			const audioContext = new AudioContext()

			try {
				const audioBuffer = await audioContext.decodeAudioData(arrayBuffer)
				const wavBlob = bufferToWav(audioBuffer)
				resolve(wavBlob)
			} catch (error) {
				console.error('Error decoding audio data:', error)
				// 如果直接解码失败，尝试作为 WAV 处理（可能是某些浏览器已经输出WAV）
				if (isWav(new DataView(arrayBuffer))) {
					console.log('Blob already appears to be in WAV format.')
					resolve(blob)
				} else {
					reject(
						new Error(
							'Failed to decode audio data. The format might be unsupported.'
						)
					)
				}
			}
		}

		reader.onerror = (error) => {
			reject(error)
		}

		reader.readAsArrayBuffer(blob)
	})
}

/**
 * 将 AudioBuffer 转换为 WAV 格式的 Blob。
 * @param buffer - 包含解码后 PCM 数据的 AudioBuffer。
 * @returns - WAV 格式的 Blob 对象。
 */
function bufferToWav(buffer: AudioBuffer): Blob {
	const numOfChan = buffer.numberOfChannels
	const length = buffer.length * numOfChan * 2 + 44
	const bufferArray = new ArrayBuffer(length)
	const view = new DataView(bufferArray)
	const channels: Float32Array[] = []
	let i, sample
	let offset = 0

	// 写入 WAV 头
	setUint32(0x46464952) // "RIFF"
	setUint32(length - 8) // file length - 8
	setUint32(0x45564157) // "WAVE"

	setUint32(0x20746d66) // "fmt " chunk
	setUint32(16) // length = 16
	setUint16(1) // PCM (uncompressed)
	setUint16(numOfChan)
	setUint32(buffer.sampleRate)
	setUint32(buffer.sampleRate * 2 * numOfChan) // "byte rate"
	setUint16(numOfChan * 2) // block align
	setUint16(16) // bits per sample

	setUint32(0x61746164) // "data" - chunk
	setUint32(length - 44) // chunk length (length of pcm data)

	// 获取声道数据
	for (i = 0; i < numOfChan; i++) {
		channels.push(buffer.getChannelData(i))
	}

	// 写入 PCM 数据
	offset = 44 // Start of data chunk
	for (let i = 0; i < buffer.length; i++) {
		for (let ch = 0; ch < numOfChan; ch++) {
			sample = channels[ch][i]
			sample = Math.max(-1, Math.min(1, sample)) // Clamp
			// a more accurate scaling to 16-bit signed integer
			const intSample = sample < 0 ? sample * 32768 : sample * 32767
			view.setInt16(offset, intSample, true)
			offset += 2
		}
	}

	return new Blob([view], { type: 'audio/wav' })

	function setUint16(data: number) {
		view.setUint16(offset, data, true)
		offset += 2
	}

	function setUint32(data: number) {
		view.setUint32(offset, data, true)
		offset += 4
	}
}

/**
 * 检查 ArrayBuffer 是否已经是 WAV 格式。
 * @param view - DataView of the ArrayBuffer.
 * @returns - True if it's a WAV file.
 */
function isWav(view: DataView): boolean {
	return view.getUint32(0, false) === 0x52494646 && view.getUint32(8, false) === 0x57415645
}
