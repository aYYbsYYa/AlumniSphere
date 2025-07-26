<template>
	<AllAlumniesHeader />
	<div class="all-alumnies-view">
		<div class="switch-btn-wrapper">
			<div class="ios-switch-btn">
				<button :class="['switch-btn', { active: current === 'map' }]" @click="goMap">
					地图
				</button>
				<button
					:class="['switch-btn', { active: current === 'alumnies' }]"
					@click="goAlumnies"
				>
					同学录
				</button>
			</div>
		</div>
		<!-- <div class="title">同学录</div> -->
		<div class="alumnies-list">
			<div v-for="alumni in alumnies" :key="alumni.user_id" class="alumni-card">
				<img
					class="avatar"
					:src="getAvatarUrl(alumni.user_id)"
					:alt="alumni.realname"
					loading="lazy"
				/>
				<div class="alumni-info">
					<div class="alumni-name">{{ alumni.realname }}</div>
					<div class="alumni-row">
						<span class="icon university"></span>
						<span>{{ alumni.university }}</span>
					</div>
					<div class="alumni-row">
						<span class="icon major"></span>
						<span>{{ alumni.major }}</span>
					</div>
					<div class="alumni-row">
						<span class="icon class"></span>
						<span>{{
							alumni.graduate_year
								? alumni.graduate_year + "届" + alumni.classcode + "班"
								: ""
						}}</span>
					</div>
					<!-- <div class="alumni-row">
			<span class="icon class"></span>
			<span>{{ alumni.classname}}</span>
		  </div> -->
					<div class="alumni-row">
						<span class="icon city"></span>
						<span>{{ alumni.cityname }}</span>
					</div>
				</div>
				<button class="detail-btn" @click="goToDetail(alumni.user_id)">查看详情</button>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AllAlumniesHeader from "@/components/AllAlumnies/AllAlumniesHeader.vue";

interface Alumni {
	user_id: number;
	realname: string;
	university: string;
	classcode: string;
	cityname: string;
	latitude?: number;
	longitude?: number;
	major?: string;
	graduate_year?: string;
	classname?: string;
}

const alumnies = ref<Alumni[]>([]);
const router = useRouter();
const route = useRoute();
const current = ref(route.path === "/map" ? "map" : "alumnies");
function getAvatarUrl(userId: number) {
	return `https://alumnisphereapi.liy.ink/useravatar.php?user_id=${userId}`
}

function goToDetail(userId: number) {
	router.push(`/userinfo/${userId}`);
}

function goMap() {
	if (current.value !== "map") {
		current.value = "map";
		router.push("/map");
	}
}
function goAlumnies() {
	if (current.value !== "alumnies") {
		current.value = "alumnies";
		router.push("/AllAlumnies");
	}
}

async function fetchAlumnies() {
	const userInfoStr = localStorage.getItem("userInfo");
	const userInfo = userInfoStr ? JSON.parse(userInfoStr) : null;
	const token = userInfo?.token;
	const user_id = userInfo?.userid;
	if (!token || !user_id) return;
	try {
		const response = await fetch(
			`https://alumnisphereapi.liy.ink/classcode_query.php?user_id=10&token=${token}`,
		)
		const result = await response.json()
		if (result.status === 0 && result.data && Array.isArray(result.data.users)) {
			alumnies.value = result.data.users;
		} else {
			alumnies.value = [];
			console.error("查询失败:", result.message);
		}
	} catch (err) {
		alumnies.value = [];
		console.error("API请求失败:", err);
	}
}

onMounted(() => {
	fetchAlumnies();
});
</script>

<style scoped>
.all-alumnies-view {
	min-height: 100vh;
	background: #fff;
	padding-bottom: 40px;
	padding-top: 80px; /* 新增：顶部留白，数值可根据Header高度调整 */
}
.title {
	text-align: center;
	font-size: 32px;
	font-weight: bold;
	margin: 32px 0 24px 0;
	color: #111;
	letter-spacing: 2px;
}
.alumnies-list {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
	gap: 36px 32px;
	justify-items: center;
	align-items: stretch;
	padding: 0 48px;
	max-width: 1200px;
	margin: 0 auto;
}
.alumni-card {
	background: #fff;
	border-radius: 18px;
	box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
	width: 260px;
	min-height: 320px;
	padding: 28px 18px 18px 18px;
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 8px;
	box-sizing: border-box;
}
.avatar {
	width: 64px;
	height: 64px;
	border-radius: 50%;
	object-fit: cover;
	margin-bottom: 12px;
	background: #f2f2f2;
}
.alumni-info {
	width: 100%;
	margin-bottom: 12px;
}
.alumni-name {
	font-size: 18px;
	font-weight: 600;
	color: #111;
	text-align: center;
	margin-bottom: 8px;
}
.alumni-row {
	display: flex;
	align-items: center;
	font-size: 14px;
	color: #222;
	margin-bottom: 4px;
	gap: 4px;
}
.icon {
	width: 16px;
	height: 16px;
	display: inline-block;
	background-size: contain;
	background-repeat: no-repeat;
}
.icon.university {
	background-image: url('data:image/svg+xml;utf8,<svg fill="%231abc9c" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L2 7l10 5 10-5-10-5zm0 7.236L4.618 7 12 3.764 19.382 7 12 9.236zM2 17v2h20v-2H2zm2-2v-4.764l8 4 8-4V15H4z"/></svg>');
}
.icon.major {
	background-image: url('data:image/svg+xml;utf8,<svg fill="%231abc9c" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>');
}
.icon.class {
	background-image: url('data:image/svg+xml;utf8,<svg fill="%231abc9c" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10"/><text x="12" y="16" text-anchor="middle" font-size="10" fill="white">班</text></svg>');
}
.icon.city {
	background-image: url('data:image/svg+xml;utf8,<svg fill="%231abc9c" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>');
}
.detail-btn {
	width: 100%;
	background: #1abc9c;
	color: #fff;
	border: none;
	border-radius: 8px;
	padding: 8px 0;
	font-size: 15px;
	font-weight: 500;
	cursor: pointer;
	margin-top: 8px;
	transition: background 0.2s;
}
.detail-btn:hover {
	background: #16a085;
}
.switch-btn-wrapper {
	position: absolute;
	top: 18px;
	right: 38px;
	z-index: 20;
}
.ios-switch-btn {
	display: flex;
	background: #f2f2f2;
	border-radius: 22px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	padding: 4px 6px;
}
.switch-btn {
	min-width: 64px;
	height: 32px;
	border: none;
	background: transparent;
	color: #222;
	font-size: 15px;
	font-weight: 500;
	border-radius: 18px;
	margin: 0 2px;
	cursor: pointer;
	transition:
		background 0.2s,
		color 0.2s;
}
.switch-btn.active {
	background: #3a7ca5;
	color: #fff;
	box-shadow: 0 2px 8px rgba(58, 124, 165, 0.08);
}
</style>
