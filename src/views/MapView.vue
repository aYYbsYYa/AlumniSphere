<template>
	<MapHeader />
	<div class="map-view">
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
		<div class="map-container">
			<div id="container" class="map-inner"></div>
			<!-- 校友信息弹窗组件 -->
			<AlumniInfoWindow
				:alumni="currentAlumni"
				:visible="infoWindowVisible"
				:style="infoWindowStyle"
				@close="closeInfoWindow"
			/>
		</div>

		<div class="legend-panel">
			<h3>图例</h3>
			<div class="legend-item">
				<img :src="alumniLogoSingle" alt="单个校友" class="legend-icon-img" />
				<span>单个校友</span>
			</div>
			<div class="legend-item">
				<img :src="alumniLogoMany" alt="多位校友" class="legend-icon-img" />
				<span>多位校友</span>
			</div>
		</div>

		<div class="stats-panel">
			<h3>同班校友共计</h3>
			<div class="stats-container">
				<div class="stats-item">
					<div class="stats-number">
						{{ stats.markedAlumni ? stats.markedAlumni : 1 }}
					</div>
					<!-- <div class="stats-label">已标记校友</div> -->
				</div>
				<!-- <div class="stats-item">
                    <div class="stats-number">{{ stats.markedCities }}</div>
                    <div class="stats-label">已标记城市</div>
                </div> -->
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import MapHeader from "@/components/MapView/MapHeader.vue";
import AlumniInfoWindow from "@/components/MapView/AlumniInfoWindow.vue"; // 导入校友信息弹窗组件
import alumniLogoSingle from "@/assets/alumni_logo_single.png";
import alumniLogoMany from "@/assets/alumni_logo_many.png";

// 统计数据
const stats = reactive({
	markedAlumni: 0, // 用于展示 total_count
	markedCities: 156,
	onlineNow: 89,
});

// 校友数据
const alumniData = ref([]);

// 获取校友数据
async function fetchAlumniData(userId) {
	const currentUserDataString = localStorage.getItem("userInfo");
	const currentUserdata = currentUserDataString ? JSON.parse(currentUserDataString) : null;
	const token = currentUserdata?.token;
	if (!token) {
		console.error("未登录或token不存在");
		return;
	}
	try {
		const response = await fetch(
			`https://alumnisphereapi.liy.ink/classcode_query.php?user_id=${userId}&token=${token}`,
		);
		const result = await response.json();
		if (result.status === 0 && result.data && Array.isArray(result.data.users)) {
			alumniData.value = result.data.users;
			stats.markedAlumni = result.data.total_count || result.data.users.length;
		} else {
			alumniData.value = [];
			stats.markedAlumni = 0;
			console.error("查询失败:", result.message);
		}
	} catch (err) {
		alumniData.value = [];
		stats.markedAlumni = 0;
		console.error("API请求失败:", err);
	}
}

// 弹窗相关状态
const infoWindowVisible = ref(false); // 控制弹窗的显示与隐藏
const currentAlumni = ref<any>(null); // 当前选中校友的数据
const infoWindowStyle = reactive({
	// 弹窗的定位样式
	left: "0px",
	top: "0px",
});

// 关闭弹窗
const closeInfoWindow = () => {
	infoWindowVisible.value = false;
	currentAlumni.value = null;
};

const router = useRouter();
const route = useRoute();
const current = ref(route.path === "/map" ? "map" : "alumnies");

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

onMounted(async () => {
	const currentUserDataString = localStorage.getItem("userInfo");
	const currentUserdata = currentUserDataString ? JSON.parse(currentUserDataString) : null;
	const currentUserId = currentUserdata?.userid;
	console.log("当前用户ID:", currentUserId);

	if (currentUserId) {
		// 等待数据加载完成
		await fetchAlumniData(currentUserId);
	}

	// 脚本已在 index.html 中全局加载，现在直接使用
	if (window.AMapLoader) {
		window.AMapLoader.load({
			key: "32c2a51edf15ce81daf818131917852c",
			version: "2.0",
			plugins: ["AMap.MarkerClusterer"],
		})
			.then((AMap: any) => {
				const map = new AMap.Map("container", {
					zoom: 5,
					center: [116.397428, 39.90923],
					viewmode: "2D",
					mapStyle: "amap://styles/whitesmoke",
				});

				map.on("click", closeInfoWindow);

				const alumniIconSingle = new AMap.Icon({
					size: new AMap.Size(36, 36),
					image: alumniLogoSingle,
					imageSize: new AMap.Size(36, 36),
				});

				const alumniIconMany = new AMap.Icon({
					size: new AMap.Size(36, 36),
					image: alumniLogoMany,
					imageSize: new AMap.Size(36, 36),
					imageOffset: new AMap.Pixel(-18, -36),
				});

				const points = alumniData.value
					.map((alumni) => {
						if (alumni.longitude && alumni.latitude) {
							return {
								lnglat: [parseFloat(alumni.longitude), parseFloat(alumni.latitude)],
								extData: alumni,
							};
						}
						return null;
					})
					.filter((item) => item !== null);

				const clusterOptions = {
					gridSize: 60,
					maxZoom: 18,
					renderClusterMarker: (context: any) => {
						const count = context.count;
						const div = document.createElement("div");
						const size = alumniIconMany.getSize();
						div.style.cssText = `
                        background-image: url(${alumniIconMany.getImage()});
                        background-size: contain;
                        width: ${size[0]}px;
                        height: ${size[1]}px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #000;
                        font-size: 14px;
                        font-weight: bold;
                    `;
						div.innerText = count.toString();
						context.marker.setContent(div);
						context.marker.setOffset(new AMap.Pixel(-size[0] / 2, -size[1] / 2));
					},
					renderMarker: (context: any) => {
						const marker = context.marker;
						const extData = context.data[0].extData;
						marker.setIcon(alumniIconSingle);
						marker.setOffset(new AMap.Pixel(-18, -36));

						marker.on("click", (e: any) => {
							currentAlumni.value = extData;
							const pixel = map.lngLatToContainer(e.lnglat);
							infoWindowStyle.left = `${pixel.getX()}px`;
							infoWindowStyle.top = `${pixel.getY()}px`;
							infoWindowVisible.value = true;
						});
					},
				};

				const cluster = new AMap.MarkerClusterer(map, points as any, clusterOptions);

				function updateInfoWindowPosition() {
					if (infoWindowVisible.value && currentAlumni.value) {
						const lnglat = [
							parseFloat(currentAlumni.value.longitude),
							parseFloat(currentAlumni.value.latitude),
						];
						const pixel = map.lngLatToContainer(lnglat);
						const markerHeight = 36;
						infoWindowStyle.left = `${pixel.getX()}px`;
						infoWindowStyle.top = `${pixel.getY() - markerHeight / 2}px`;
					}
				}
				map.on("moveend", updateInfoWindowPosition);
				map.on("zoomend", updateInfoWindowPosition);
			})
			.catch((e: Error) => {
				console.error("高德地图API初始化失败:", e);
			});
	} else {
		console.error("AMapLoader 未在 window 对象上找到。");
	}
});
</script>

<style scoped>
.map-view {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
}

.map-container {
	/* position: relative; 确保子元素的绝对定位基于此容器 */
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
}

.map-inner {
	width: 100%;
	height: 100%;
}

/* 左上角图例面板 */
.legend-panel {
	position: absolute;
	top: 50px;
	left: 50px;
	background: rgba(255, 255, 255, 0.9);
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	padding: 16px;
	/* min-width: 120px; */
	width: 100px;
	/* 原 min-width: 160px; 可根据实际需求调整 */
}

.legend-panel h3 {
	margin: 0 0 12px 0;
	font-size: 16px;
	color: #333;
	font-weight: 600;
	text-align: center;
}

.legend-item {
	display: flex;
	align-items: center;
	margin-bottom: 8px;
}

.legend-item:last-child {
	margin-bottom: 0;
}

/* 新增图例图片样式 */
.legend-icon-img {
	width: 20px;
	height: 20px;
	border-radius: 50%;
	margin-right: 8px;
	object-fit: cover;
	vertical-align: middle;
}

/* 右上角统计面板 */
.stats-panel {
	position: absolute;
	top: 80px;
	right: 50px;
	background: rgba(255, 255, 255, 0.9);
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	padding: 12px;
	min-width: auto;
}

.stats-panel h3 {
	margin: 0 0 8px 0;
	font-size: 14px;
	color: #333;
	font-weight: 600;
	text-align: center;
}

.stats-container {
	display: flex;
	gap: 16px;
	justify-content: center;
}

.stats-item {
	text-align: center;
}

.stats-number {
	font-size: 16px;
	font-weight: bold;
	color: #1890ff;
	line-height: 1.2;
}

.stats-label {
	font-size: 11px;
	color: #666;
	margin-top: 2px;
	white-space: nowrap;
}

/* 新增：右上角苹果风格切换按钮 */
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
