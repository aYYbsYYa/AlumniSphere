<template>
	<ProfileHeader />
	<br />
	<RouterView></RouterView>
</template>

<script lang="ts" setup name="ProfileView">
import { onMounted } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import ProfileHeader from '@/components/ProfileView/ProfileHeader.vue'

const router = useRouter()

onMounted(async () => {
	const userInfoStr = localStorage.getItem('userInfo')
	if (userInfoStr) {
		const userInfo = JSON.parse(userInfoStr)
		const token = userInfo.token
		try {
			const response = await fetch(
				`https://alumnisphereapi.liy.ink/userinfo.php?action=get&token=${token}`,
			)
			if (response.ok) {
				const result = await response.json()
				if (result.status === 0) {
					if (result.data.created_at === null) {
						// created_at 为空，执行后续操作，可以是在当前页面显示内容或跳转
						console.log('created_at is null, proceeding with further actions.')
						// 例如，可以导航到首次设置页面
						router.push({ name: 'profileisFirstTime' })
					} else {
						// created_at 不为空，跳转到编辑页面
						router.push({ name: 'profileEdit' })
					}
				} else {
					console.error('Failed to get user info:', result.message)
				}
			} else {
				console.error('Failed to fetch user info')
			}
		} catch (error) {
			console.error('Error fetching user info:', error)
		}
	} else {
		// 如果没有token，可以跳转到登录页面
		router.push({ name: 'login' })
	}
})
</script>

<style scoped>
/* 这里可以添加样式 */
</style>
