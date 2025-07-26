<template>
	<div class="nav" :class="{ loaded: isLoaded }">
		<div class="nav-links">
			<!-- Text removed as per request -->
		</div>
	</div>
</template>

<script lang="ts" setup name="SmartqHeader">
import { ref, onMounted } from 'vue'

const isLoaded = ref(false)

onMounted(() => {
	// 使用setTimeout确保元素已挂载并可见，然后触发动画
	setTimeout(() => {
		isLoaded.value = true
	}, 100)
})
</script>

<style scoped>
.nav {
	z-index: 1000; /* 确保导航栏在最上层 */
	background-color: #ffffff; /* Changed to white */
	padding: 0; /* Adjusted padding */
	height: 8%; /* Set height */
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* 悬浮效果 */
	position: fixed;
	left: 0; /* Changed for full width */
	top: 0;
	transform: none; /* Removed transform */
	width: 0; /* 初始宽度为0 */
	overflow: hidden; /* 隐藏溢出的内容 */
	transition:
		width 0.5s ease-in-out,
		transform 0.3s ease-in-out; /* 宽度和变换的过渡动画 */
	white-space: nowrap; /* 防止内容换行 */
}

.nav:hover {
	/* transform: translateX(-50%) scale(1.05); */ /* Removed hover effect */
}

.nav.loaded {
	width: 100%; /* Changed to full width */
	padding: 0;
	border-radius: 0; /* Removed border-radius for a full-width bar */
}

.nav-links {
	display: flex;
	justify-content: space-around;
}

a {
	display: none; /* Hide text links */
}

.nav.loaded a {
	opacity: 1; /* 加载后完全显示 */
}

a:hover {
	color: #ffcc00; /* 悬浮时颜色变化 */
	transform: translateY(-5px); /* 悬浮时向上跳动 */
}

.router-link-active {
	color: lightgreen;
}
</style>
