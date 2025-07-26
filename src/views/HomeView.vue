<template>
	<div class="home-root">
		<HeaderComponent />
		<br />
		<div class="home-title-section">
			<h1>同窗集</h1>
			<p>下一代同学录，何止是同学录？</p>
			<div class="info-section">
				<div class="info-item">
					<span class="info-label">用户数:</span>
					<span class="info-value" id="userCount"></span>
				</div>
			</div>
		</div>
		<div v-if="isLoggedIn" class="card-grid">
			<div class="card-grid-row">
				<div class="card card-mailbox">
					<div class="card-icon">
						<img :src="postbox" alt="时光胶囊" />
					</div>
					<div class="card-title">时光胶囊</div>
					<br />
					<div v-if="nextLetterDays != '-'" class="card-content">
						距离下一封信件送达还有<br /><span class="mailbox-days"
							>{{ nextLetterDays }} 天</span
						>
					</div>
					<div v-else class="card-content">暂无信件，快去给未来的自己发一封信吧~</div>
				</div>
				<div class="card card-welcome">
					<div class="card-icon">
						<img :src="student" alt="欢迎回来" />
					</div>
					<div class="card-title">欢迎回来</div>
					<div class="card-content">
						<!-- <div class="welcome-user">欢迎回来，张三！</div> -->
						<br />
						<div class="welcome-desc">{{ welcomeDesc }}</div>
					</div>
				</div>
			</div>
			<div class="card-grid-row">
				<div class="card card-footprint">
					<div class="card-icon">
						<img :src="mapPicture" alt="校友足迹" />
					</div>
					<div class="card-title">校友足迹</div>
					<br />
					<div class="card-content">
						<div class="footprint-info">
							已标记同窗达到
							<span class="highlight">{{ sameClassCount }}</span>
							人<br />我们的足迹已遍布
							<span class="highlight">{{ sameCityCount }}</span> 个城市
						</div>
					</div>
				</div>
				<div class="card card-today">
					<div class="card-icon">
						<img :src="calendar" alt="那年今日" />
					</div>
					<div class="card-title">那年今日</div>
					<div class="card-content today-content">
						<div class="today-event">{{ todayEvent.text }}</div>
						<!-- <div class="today-years-label">
							<span class="today-years">{{ todayYearsAgo }}</span>
							<span class="today-label">年前</span>
						</div> -->
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from "vue";
import HeaderComponent from "@/components/HomeView/HomeHeader.vue";
import student from "@/assets/pictures/student.png";
import mapPicture from "@/assets/pictures/map.png";
import calendar from "@/assets/pictures/calendar.png";
import postbox from "@/assets/icons/postbox.png";

const welcomeMessages = [
	"欢迎回到同窗集，记录属于我们的青春回忆！",
	"又见老同学，时光不老，我们不散。",
	"同学录新篇，期待你的故事。",
	"每一次归来，都是友情的延续。",
	"让我们一起见证彼此的成长。",
	"同窗情谊，历久弥新，欢迎回来！",
	"在这里，遇见曾经的你和我。",
	"欢迎加入同学录，留下你的足迹。",
	"青春不散场，友谊常相伴。",
	"同学录等你分享新故事！",
];
const welcomeDesc = ref("");
const nextLetterDays = ref("-");
const sameClassCount = ref("-");
const sameCityCount = ref("-");
// 高中大事记
const todayEvents = [
	{ year: 2017, text: "高一军训，大家第一次相识，留下了汗水与欢笑。" },
	{ year: 2018, text: "校园艺术节，班级合唱荣获一等奖。" },
	{ year: 2019, text: "高考百日誓师大会，全体同学激情宣誓。" },
	{ year: 2017, text: "第一次班级春游，大家一起登山野餐。" },
	{ year: 2018, text: "高二运动会接力赛，我们团结协作，勇夺团体总分第一。" },
	{ year: 2019, text: "毕业典礼，我们互相交换签名，合影留念。" },
	{ year: 2018, text: "校科技节，物理小组作品获创新奖。" },
	{ year: 2017, text: "高一元旦晚会，班级大合唱《国旗之下》。" },
	{ year: 2019, text: "高三冲刺，教室灯火通明，奋斗不息。" },
	{ year: 2019, text: "生物考完，高考结束，晚上大家一起聚餐，畅聊高中三年经历。" },
];
const todayEvent = ref({ year: 0, text: "" });
const todayYearsAgo = ref(0);
const isLoggedIn = localStorage.getItem("userInfo") ? true : false;

onMounted(() => {
	const idx = Math.floor(Math.random() * welcomeMessages.length);
	welcomeDesc.value = welcomeMessages[idx];
	// 随机大事记
	const eventIdx = Math.floor(Math.random() * todayEvents.length);
	todayEvent.value = todayEvents[eventIdx];
	const nowYear = new Date().getFullYear();
	todayYearsAgo.value = nowYear - todayEvent.value.year;

	// 获取用户信息
	const currentUserDataString = localStorage.getItem("userInfo");
	const currentUserdata = currentUserDataString ? JSON.parse(currentUserDataString) : null;
	const currentUserId = currentUserdata?.userid;
	const token = currentUserdata?.token;
	if (!currentUserId || !token) return;

	// 获取用户统计数据
	fetch(`https://alumnisphereapi.liy.ink/user_stats.php?user_id=${currentUserId}&token=${token}`)
		.then((res) => res.json())
		.then((data) => {
			console.log(data);
			console.log(currentUserId);
			nextLetterDays.value = data.next_letter_days ?? "-";
			sameClassCount.value = data.same_class_count ?? "-";
			sameCityCount.value = data.same_city_count ?? "-";
		})
		.catch(() => {
			nextLetterDays.value = "-";
			sameClassCount.value = "-";
			sameCityCount.value = "-";
		});
});

onMounted(async () => {
	try {
		const response = await fetch("https://alumnispherepyapi.liy.ink/api/ucount");
		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}
		const data = await response.json();
		if (data.code === 200) {
			slotMachineAnimation("userCount", data.count);
		} else {
			throw new Error(`API returned error code: ${data.code}`);
		}
	} catch (error) {
		console.error("Failed to fetch user count:", error);
		// Fallback to a default value if the API call fails
		slotMachineAnimation("userCount", 1000);
	}
});

function slotMachineAnimation(elementId: string, targetNumber: number) {
	const element = document.getElementById(elementId);
	if (!element) return;

	const targetStr = targetNumber.toString();
	const numDigits = targetStr.length;
	const intervals: number[] = [];

	element.innerHTML = ""; // Clear previous content

	// Create spans for each digit
	for (let i = 0; i < numDigits; i++) {
		const span = document.createElement("span");
		span.className = "digit";
		span.textContent = "0";
		element.appendChild(span);
	}

	const digitSpans = element.querySelectorAll(".digit");

	// Start animation for each digit
	digitSpans.forEach((span) => {
		const interval = window.setInterval(() => {
			span.textContent = Math.floor(Math.random() * 10).toString();
		}, 80); // Animation speed for each digit
		intervals.push(interval);
	});

	// Stop each digit at the correct number with a delay
	for (let i = 0; i < numDigits; i++) {
		setTimeout(
			() => {
				clearInterval(intervals[i]);
				digitSpans[i].textContent = targetStr[i];
			},
			500 + i * 300,
		); // Stagger the stop time for each digit
	}
}
</script>

<style scoped>
.home-root {
	min-height: 100vh;
	background: #f8fafc;
	padding-bottom: 40px;
}
.home-title-section {
	text-align: center;
	margin-top: 32px;
	margin-bottom: 32px;
}
h1 {
	font-size: 2.8em;
	text-align: center;
	margin-top: 20px;
	margin-bottom: 0.1em;
	font-weight: 600;
}
p {
	font-size: 1.2em;
	color: #666;
	margin-bottom: 0.5em;
}
.info-section {
	margin-top: 12px;
	text-align: center;
}
.info-item {
	font-size: 1.5em;
	margin: 10px 0;
}
.info-label {
	font-weight: bold;
}
.info-value {
	color: #007bff;
	font-weight: bold;
	display: inline-block; /* Ensure the container can hold the digits */
}
.digit {
	display: inline-block;
	width: 0.6em; /* Fixed width to prevent shaking */
	text-align: center;
}
.user-count {
	font-size: 1.1em;
	color: #222;
	margin-top: 0.5em;
}
.user-count-number {
	color: #1abc9c;
	font-size: 1.5em;
	font-weight: bold;
	margin-left: 0.2em;
}
.card-grid {
	display: flex;
	flex-direction: column;
	gap: 32px;
	max-width: 900px;
	margin: 0 auto;
}
.card-grid-row {
	display: flex;
	flex-direction: row;
	gap: 32px;
}
.card {
	background: #fff;
	border-radius: 18px;
	box-shadow: 0 2px 16px 0 rgba(0, 0, 0, 0.07);
	padding: 32px 28px;
	display: flex;
	flex-direction: column;
	align-items: center;
	min-width: 320px;
	min-height: 180px;
	flex: 1;
}
.card {
	background: #fff;
	border-radius: 18px;
	box-shadow: 0 2px 16px 0 rgba(0, 0, 0, 0.07);
	padding: 24px 28px;
	display: flex;
	flex-direction: column;
	align-items: center;
	min-width: 260px;
	/* min-height: 180px; */
}
.card-icon {
	margin-bottom: 12px;
}
.card-icon img {
	width: 38px;
	height: 38px;
}
.card-title {
	font-size: 1.3em;
	font-weight: 600;
	/* margin-bottom: 10px; */
	color: #1abc9c;
}
.card-content {
	font-size: 1.1em;
	color: #444;
	text-align: center;
}
.mailbox-days {
	color: #1abc9c;
	font-size: 1.3em;
	font-weight: bold;
}
.mailbox-btn {
	margin-top: 18px;
	background: #1abc9c;
	color: #fff;
	border: none;
	border-radius: 20px;
	padding: 8px 28px;
	font-size: 1em;
	cursor: pointer;
	transition: background 0.2s;
}
.mailbox-btn:hover {
	background: #16a085;
}
.card-footprint .footprint-map {
	width: 160px;
	height: 100px;
	background: #eaf6f3;
	border-radius: 10px;
	margin: 0 auto 10px auto;
}
.card-footprint .footprint-info {
	font-size: 1em;
	color: #1abc9c;
}
.highlight {
	color: #007bff;
	font-weight: bold;
}
.card-welcome .welcome-user {
	font-size: 1.3em;
	font-weight: 600;
	margin-bottom: 6px;
}
.card-welcome .welcome-desc {
	color: #888;
	font-size: 1em;
}
.card-today .today-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	margin-top: 10px;
}
.card-today .today-event {
	font-size: 1.05em;
	color: #444;
	margin-bottom: 16px;
	text-align: center;
	min-height: 48px;
}
.card-today .today-years-label {
	display: flex;
	align-items: baseline;
	gap: 4px;
}
.card-today .today-years {
	font-size: 2em;
	color: #1abc9c;
	font-weight: bold;
	line-height: 1;
}
.card-today .today-label {
	font-size: 1.1em;
	color: #888;
	margin-top: 4px;
}
@media (max-width: 900px) {
	.card-grid {
		gap: 18px;
	}
	.card-grid-row {
		flex-direction: column;
		gap: 18px;
	}
	.card {
		min-width: 180px;
		padding: 18px 8px;
		width: 100%;
	}
}
</style>
