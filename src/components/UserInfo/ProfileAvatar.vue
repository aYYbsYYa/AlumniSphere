<template>
  <div class="avatar-container">
    <img :src="avatarUrl" class="avatar" alt="User Avatar" />
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

const avatarTimestamp = ref(new Date().getTime())

const avatarUrl = computed(() => {
  return `https://alumnisphereapi.liy.ink/useravatar.php?user_id=${props.userId}&t=${avatarTimestamp.value}`
})
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
  transition: all 0.3s ease;
}

.avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
