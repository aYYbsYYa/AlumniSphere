<template>
	<div class="nav" :class="{ loaded: isLoaded }">
		<div class="nav-links">
			<router-link to="/">主页</router-link>
			<template v-if="isLoggedIn">
				| <router-link to="/profile">个人中心</router-link> |
				<router-link to="/map">同窗一览</router-link>
				| <router-link to="/future">时光胶囊</router-link> |
				<a href="#" @click.prevent="logout">注销</a>
			</template>
			<template v-else>
				| <router-link to="/login">登录</router-link> |
				<router-link to="/register">注册</router-link>
			</template>
		</div>
	</div>
</template>

<script lang="ts" setup name="HomeHeader">
import { ref, onMounted } from "vue";

const isLoaded = ref(false);
const isLoggedIn = ref(false);

onMounted(() => {
	// 检查localStorage中是否存在userInfo
	if (localStorage.getItem("userInfo")) {
		isLoggedIn.value = true;
	}

	// 使用setTimeout确保元素已挂载并可见，然后触发动画
	setTimeout(() => {
		isLoaded.value = true;
	}, 100);
});

function logout() {
	localStorage.removeItem("userInfo"); // 清除用户信息
	isLoggedIn.value = false; // 更新登录状态
	window.location.href = "/"; // 跳转到主页
}
</script>

<style scoped>
.nav {
	z-index: 1000; /* 确保导航栏在最上层 */
	background-color: #333;
	padding: 10px 0; /* 调整内边距以适应动画 */
	border-radius: 20px; /* 圆角 */
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* 悬浮效果 */
	position: fixed;
	left: 50%;
	transform: translateX(-50%);
	width: 0; /* 初始宽度为0 */
	overflow: hidden; /* 隐藏溢出的内容 */
	transition:
		width 0.5s ease-in-out,
		transform 0.3s ease-in-out; /* 宽度和变换的过渡动画 */
	white-space: nowrap; /* 防止内容换行 */
}

.nav:hover {
	transform: translateX(-50%) scale(1.05); /* 悬浮时放大 */
}

.nav.loaded {
	width: 500px; /* 动画结束后的最终宽度，适配更多导航项 */
	padding: 10px;
}

.nav-links {
	display: flex;
	justify-content: space-around;
}

a {
	margin: 0 15px;
	color: white;
	text-decoration: none;
	font-weight: bold;
	display: inline-block; /* 使transform生效 */
	transition:
		color 0.3s ease,
		opacity 0.5s ease-in-out 0.3s,
		transform 0.3s ease; /* 添加transform过渡 */
	opacity: 0; /* 初始时透明 */
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
