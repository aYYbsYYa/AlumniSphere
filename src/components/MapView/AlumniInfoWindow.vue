<template>
	<Transition name="fade">
		<div v-if="visible && alumni" class="alumni-info-window">
			<div class="info-header">
				<img :src="avatarUrl" alt="用户头像" class="avatar" />
				<div class="user-details">
					<div class="user-name-row">
						<h3 class="user-name">
							{{ alumni.realname || alumni.nickname || '未知校友' }}
						</h3>
						<div
							v-if="alumni.graduation_year || alumni.classcode"
							class="user-year-badge"
						>
							{{ alumni.graduation_year ? alumni.graduation_year + '届' : '' }}
							<template v-if="alumni.classcode">{{ alumni.classcode }}班</template>
						</div>
					</div>
					<div class="user-info-list">
						<div v-if="alumni.university" class="user-info-item">
							{{ alumni.university }}
						</div>
						<div v-if="alumni.major" class="user-info-item">{{ alumni.major }}</div>
						<div v-if="alumni.cityname" class="user-info-item">
							现居：{{ alumni.cityname }}
						</div>
					</div>
				</div>
				<button class="close-btn" @click="handleClose" aria-label="关闭弹窗"></button>
			</div>
			<div class="info-actions">
				<button class="detail-button small" @click="viewDetails">查看详情</button>
			</div>
		</div>
	</Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

// 定义组件接收的 props
const props = defineProps<{
	alumni: {
		user_id: number
		nickname: string | null
		realname: string | null
		classcode: string | null
		university?: string | null
		major?: string | null
		graduation_year?: string | null
		cityname?: string | null
		// 其他字段可继续补充
	}
	visible: boolean // 控制弹窗显示/隐藏
}>()

// 计算属性，根据 user_id 生成头像 URL
const avatarUrl = computed(() => {
	// 基础头像接口 URL
	const baseUrl = 'https://alumnisphereapi.liy.ink/useravatar.php'
	// 拼接 user_id 参数，并添加时间戳防止缓存，确保每次都能获取最新头像或默认头像
	return props.alumni ? `${baseUrl}?user_id=${props.alumni.user_id}&t=${Date.now()}` : ''
})

// “查看详情”按钮点击事件处理函数
const router = useRouter()
const viewDetails = () => {
	if (props.alumni) {
		router.push(`/userinfo/${props.alumni.user_id}`)
	}
}

// 关闭弹窗按钮逻辑
const emit = defineEmits(['close'])
const handleClose = () => {
	emit('close')
}
</script>

<style scoped>
.user-year-badge {
	display: inline-block;
	background: #fff6d1;
	color: #bfa14a;
	font-size: 12px;
	font-weight: 500;
	border-radius: 10px;
	padding: 1px 10px;
	margin-left: 6px;
	white-space: nowrap;
	box-shadow: 0 1px 4px rgba(191, 161, 74, 0.08);
	border: none;
	vertical-align: middle;
	max-width: 80px;
	overflow: hidden;
	text-overflow: ellipsis;
}

.close-btn {
	position: absolute;
	top: 8px;
	right: 8px;
	width: 24px;
	height: 24px;
	background: none;
	border: none;
	cursor: pointer;
	z-index: 10;
	/* padding-right: 40px; */
}
.close-btn::before {
	content: '';
	display: block;
	width: 100%;
	height: 100%;
	background-image: url('data:image/svg+xml;utf8,<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5.63604" y="7.05029" width="2" height="12" rx="1" transform="rotate(-45 5.63604 7.05029)" fill="%2333c1b7"/><rect x="7.05029" y="18.364" width="2" height="12" rx="1" transform="rotate(-135 7.05029 18.364)" fill="%2333c1b7"/></svg>');
	background-size: contain;
	background-repeat: no-repeat;
}
/* 弹窗容器样式 */
.alumni-info-window {
	position: absolute;
	background: #fff;
	border-radius: 12px;
	box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
	padding: 20px;
	width: 280px; /* 固定宽度 */
	box-sizing: border-box;
	z-index: 1000; /* 确保在地图上方 */
	/* 初始位置，将在 MapView 中通过 style 动态设置 */
	transform: translate(-50%, -100%); /* 向上和左移动50%，使箭头指向marker */
	transform-origin: bottom center; /* 动画基点在底部中心 */
	opacity: 1; /* 默认可见 */
	pointer-events: auto; /* 允许鼠标事件 */
	transition:
		left 0.3s cubic-bezier(0.4, 0, 0.2, 1),
		top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 头部区域样式 */
.info-header {
	display: flex;
	align-items: center;
	margin-bottom: 15px;
	padding-bottom: 15px;
	border-bottom: 1px solid #eee;
}

/* 头像样式 */
.avatar {
	width: 60px;
	height: 60px;
	border-radius: 50%;
	object-fit: cover;
	margin-right: 15px;
	border: 2px solid #f0f0f0;
	flex-shrink: 0; /* 防止头像被压缩 */
}

/* 用户详情文本区域 */
.user-details {
	flex-grow: 1;
	min-width: 0; /* 允许内容在需要时收缩 */
}

/* 用户姓名样式 */
/* 姓名和届数一行显示 */
.user-name-row {
	display: flex;
	align-items: center;
	gap: 8px;
}
.user-name {
	margin: 0;
	font-size: 18px;
	font-weight: bold;
	color: #333;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.user-year {
	font-size: 14px;
	color: #409eff;
	background: #f0f7ff;
	border-radius: 6px;
	padding: 2px 8px;
	margin-left: 4px;
	font-weight: 500;
	white-space: nowrap;
}

/* 学校、专业、城市信息列表 */
.user-info-list {
	margin: 6px 0 0 0;
	display: flex;
	flex-direction: column;
	gap: 2px;
}
.user-info-item {
	font-size: 13px;
	color: #555;
	line-height: 1.3;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* 用户简介样式 */
.user-bio {
	margin: 5px 0 0;
	font-size: 13px;
	color: #666;
	line-height: 1.4;
	display: -webkit-box; /* 限制行数 */
	-webkit-line-clamp: 2; /* 最多显示2行 */
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* 底部操作区域样式 */
.info-actions {
	text-align: center;
}

/* 查看详情按钮样式 */
/* 详情按钮小号样式 */
.detail-button {
	background-color: #409eff;
	color: white;
	border: none;
	border-radius: 8px;
	padding: 10px 20px;
	font-size: 15px;
	cursor: pointer;
	transition:
		background-color 0.3s ease,
		transform 0.2s ease;
	width: 100%;
}
.detail-button.small {
	padding: 6px 12px;
	font-size: 13px;
	width: auto;
	min-width: 80px;
	max-width: 120px;
}
.detail-button:hover {
	background-color: #66b1ff;
	transform: translateY(-2px);
}
.detail-button:active {
	background-color: #3a8ee6;
	transform: translateY(0);
}

/* 动画效果 */
/* 进入动画 */
.fade-enter-active {
	transition: all 0.3s ease-out;
}

/* 离开动画 */
.fade-leave-active {
	transition: all 0.2s ease-in;
}

/* 进入前和离开后状态 */
.fade-enter-from,
.fade-leave-to {
	opacity: 0;
	transform: translate(-50%, -80%) scale(0.9); /* 稍微缩小并向上移动 */
}

/* 进入后和离开前状态 */
.fade-enter-to,
.fade-leave-from {
	opacity: 1;
	transform: translate(-50%, -100%) scale(1);
}
</style>
