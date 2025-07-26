<template>
  <div class="avatar-container" @click="triggerFileUpload">
    <img :src="avatarUrl" class="avatar" alt="User Avatar" />
    <div class="upload-overlay">
      <span class="upload-icon">⬆️</span>
      <span class="upload-text">更换头像</span>
    </div>
    <input type="file" ref="fileInputRef" accept="image/*" @change="onFileChange" hidden />
  </div>
</template>

<script lang="ts" setup name="ProfileAvatar">
import { ref, computed } from 'vue'

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

const fileInputRef = ref<HTMLInputElement | null>(null)
const avatarTimestamp = ref(new Date().getTime())

const avatarUrl = computed(() => {
  return `http://gyip.liip.top:48040/useravatar.php?user_id=${props.userId}&t=${avatarTimestamp.value}`
})

const triggerFileUpload = () => {
  fileInputRef.value?.click()
}

const onFileChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) {
    return
  }

  const formData = new FormData()
  formData.append('user_id', props.userId)
  formData.append('token', props.token)
  formData.append('avatar', file)

  try {
    await fetch('http://gyip.liip.top:48040/useravatar.php', {
      method: 'POST',
      body: formData,
      mode: 'no-cors',
    })
    //alert('头像上传请求已发送！请稍后刷新查看。')
    // Update timestamp to refresh image
    avatarTimestamp.value = new Date().getTime()
  } catch (error) {
    console.error('Upload failed:', error)
    //alert('上传过程中发生网络错误。')
  }
}
</script>

<style scoped>
.avatar-container {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #f0f0f0;
  cursor: pointer;
  transition: all 0.3s ease;
}

.avatar-container:hover .avatar {
  filter: blur(2px) brightness(0.7);
}

.upload-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 50%;
  text-align: center;
}

.avatar-container:hover .upload-overlay {
  opacity: 1;
}

.upload-icon {
  font-size: 2em;
}

.upload-text {
  margin-top: 5px;
  font-size: 0.9em;
}

.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
