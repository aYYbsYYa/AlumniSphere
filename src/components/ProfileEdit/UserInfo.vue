<template>
  <div class="user-info-container">
    <h1 class="username">{{ username }}</h1>
    <div v-if="!isEditingBio" class="info-display-container">
      <p class="realname">{{ editableRealname || '未设置真实姓名' }}</p>
    </div>
    <div v-else>
      <input type="text" v-model="editableRealname" class="realname-input" placeholder="输入真实姓名" />
    </div>
    <div class="bio-container">
      <div v-if="!isEditingBio" @click="startEditingBio" class="bio-display-container">
        <p class="bio" v-html="formattedBio || '点击添加简介...'"></p>
        <span class="edit-icon">✏️</span>
      </div>
      <div v-else class="bio-edit-container">
        <textarea v-model="editableBio" class="bio-textarea" rows="5" placeholder="输入你的简介..."></textarea>
        <div class="bio-actions">
          <button @click="cancelEditingBio" class="btn-cancel-bio">取消</button>
          <button @click="saveBio" class="btn-save-bio">保存</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  username: String,
})

const editableRealname = ref('')
const editableBio = ref('')
const formattedBio = ref('加载中...')
const isEditingBio = ref(false)
const originalBio = ref('')
const originalRealname = ref('')

const fetchUserInfo = async (userToken: string) => {
  try {
    const response = await fetch(
      `https://alumnisphereapi.liy.ink/userinfo.php?action=get&token=${userToken}`,
    )
    if (!response.ok) {
      throw new Error('Network response was not ok')
    }
    const responseData = await response.json()
    if (responseData.status === 0 && responseData.data) {
      editableBio.value = responseData.data.bio || ''
      formattedBio.value = editableBio.value
        ? editableBio.value.replace(/\n/g, '<br>')
        : '暂无简介信息。'
      editableRealname.value = responseData.data.realname || '未设置真实姓名'
    } else {
      editableBio.value = ''
      formattedBio.value = '暂无简介信息。'
    }
  } catch (error) {
    console.error('Failed to fetch user info:', error)
    editableBio.value = ''
    formattedBio.value = '无法加载简介信息。'
  }
}

const startEditingBio = () => {
  originalBio.value = editableBio.value
  originalRealname.value = editableRealname.value
  isEditingBio.value = true
}

const cancelEditingBio = () => {
  editableBio.value = originalBio.value
  editableRealname.value = originalRealname.value
  isEditingBio.value = false
}

const saveBio = async () => {
  if (editableBio.value === originalBio.value && editableRealname.value === originalRealname.value) {
    isEditingBio.value = false
    return // No changes made
  }

  try {
    const params = new URLSearchParams()
    params.append('action', 'save')
    params.append('token', props.token)
    params.append('realname', editableRealname.value)
    params.append('bio', editableBio.value)

    const response = await fetch('https://alumnisphereapi.liy.ink/userinfo.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: params.toString(),
    })

    const responseText = await response.text()
    try {
      const result = JSON.parse(responseText)
      if (result.status === 'success') {
        formattedBio.value = editableBio.value.replace(/\n/g, '<br>')
        isEditingBio.value = false
        //alert('简介更新成功！')
      } else {
        throw new Error(result.message || '更新失败')
      }
    } catch {
      throw new Error(`服务器返回格式错误: ${responseText}`)
    }
  } catch (error) {
    console.error('Failed to save bio:', error)
    //const errorMessage = error instanceof Error ? error.message : '未知错误'
    //alert(`保存失败: ${errorMessage}`)
    editableBio.value = originalBio.value
    editableRealname.value = originalRealname.value
    isEditingBio.value = false
  }
}

watch(() => props.token, (newToken) => {
    if (newToken) {
        fetchUserInfo(newToken);
    }
}, { immediate: true });

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

.realname-input {
  width: 60%;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-family: inherit;
  font-size: 1em;
  margin-top: 5px;
  text-align: center;
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
  cursor: pointer;
  transition: background-color 0.3s;
  border: 1px solid transparent;
  width: 100%;
}

.bio-display-container:hover {
  background-color: #f9f9f9;
  border-color: #e0e0e0;
}

.bio-display-container .edit-icon {
  position: absolute;
  top: 10px;
  right: 10px;
  opacity: 0;
  transition: opacity 0.3s;
  font-size: 0.9em;
  color: #666;
}

.bio-display-container:hover .edit-icon {
  opacity: 1;
}

.bio {
  padding: 0;
  line-height: 1.8;
  margin: 0; /* remove default margin */
  min-height: 24px; /* Ensure container has height even if bio is empty */
}

.bio-edit-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.bio-textarea {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-family: inherit;
  font-size: 1em;
  line-height: 1.6;
  resize: vertical;
}

.bio-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.bio-actions button {
  align-self: flex-end;
  padding: 8px 16px;
  border: none;
  border-radius: 20px;
  color: white;
  cursor: pointer;
  font-weight: bold;
}

.btn-save-bio {
  background-color: #3182ce;
}

.btn-save-bio:hover {
  background-color: #2b6cb0;
}

.btn-cancel-bio {
  background-color: #a0aec0;
}

.btn-cancel-bio:hover {
  background-color: #718096;
}
</style>
