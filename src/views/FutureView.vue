<template>
	<div class="future-view-wrapper">
		<FutureHeader />
		<div class="future-content">
			<aside class="sidebar">
				<!-- <div class="sidebar-title">我的信件</div> -->
				<button class="new-letter-btn" @click="showModal = true">+ 新建一封信</button>
				<!-- 弹窗已移除，主内容区显示新建信件界面 -->
				<ul class="letter-list">
					<li
						v-for="item in letters"
						:key="item.id"
						:class="{ locked: item.locked }"
						@click="selectLetter(item)"
					>
						<div class="letter-info">
							<span class="letter-title">{{ item.title }}</span>
							<span v-if="item.locked" class="lock-icon">
								<span class="unlock-date">{{ item.unlock_date }}</span>
								<span class="unlock-label">将启封🔒</span>
							</span>
							<span v-else class="status">已启封</span>
						</div>
						<span class="letter-date">{{ item.commit_date }}</span>
					</li>
				</ul>
			</aside>
			<div class="main-content">
				<div v-if="showModal" class="letter-form-panel">
					<div class="modal-title-row">
						<div class="icon-container">
							<img
								:src="postboxIcon"
								alt="邮箱图标"
								style="width: 40px; height: 40px"
							/>
						</div>
						<div class="modal-title-text">
							<div class="main-title">新建一封信</div>
							<div class="modal-desc">写给未来的自己</div>
						</div>
					</div>
					<div class="modal-form">
						<label class="modal-label">信件标题</label>
						<input
							v-model="form.title"
							class="modal-input"
							placeholder="例如：给五年后的自己"
						/>
						<label class="modal-label">启封日期</label>
						<el-date-picker
							v-model="form.unlock_date"
							type="date"
							placeholder="请选择未来的启封日期"
							format="YYYY年MM月DD日"
							value-format="YYYY-MM-DD"
							class="modal-input el-date-picker-custom"
							popper-class="el-date-picker-popper"
						/>
						<label class="modal-label">信件内容</label>
						<textarea
							v-model="form.content"
							class="modal-textarea"
							placeholder="写下你想对未来说的话..."
						/>
					</div>
					<div class="modal-actions">
						<button class="modal-btn" @click="cancelLetter">取消</button>
						<button class="modal-btn send" @click="sendLetter">发送</button>
					</div>
					<div class="modal-tip">
						信件发送后将被安全加密存储，只有到达启封日期后才能查看。
					</div>
				</div>
				<div
					v-else-if="selectedLetter && selectedLetter.locked"
					class="letter-locked-panel"
				>
					<div class="letter-header-card">
						<div class="header-left-section">
							<div class="icon-container">
								<img
									:src="lockIcon"
									alt="信件图标"
									style="width: 40px; height: 40px"
								/>
							</div>
							<div>
								<div class="open-letter-title">{{ selectedLetter.title }}</div>
								<div class="locked-status">
									<!-- <span class="locked-icon">🔒</span> -->
									<span class="locked-desc"
										>将于 {{ selectedLetter.unlock_date }} 启封
									</span>
								</div>
								<div class="header-divider"></div>
							</div>
						</div>
						<div class="header-bottom-row">
							<div class="written-date">
								写于 {{ selectedLetter.commit_date }} [已封存]
							</div>
							<span class="locked-days-large"
								>还剩 {{ getDaysLeft(selectedLetter.unlock_date) }} 天</span
							>
						</div>
					</div>
				</div>
				<div v-else-if="selectedLetter && !selectedLetter.locked" class="letter-read-panel">
					<div class="letter-header-card">
						<div class="header-left-section">
							<div class="icon-container">
								<img
									:src="letterIcon"
									alt="信件图标"
									style="width: 40px; height: 40px"
								/>
							</div>
							<div>
								<div class="open-letter-title">{{ selectedLetter.title }}</div>
								<div class="delivery-status">
									已于 {{ selectedLetter.unlock_date }} 送达
								</div>
								<div class="header-divider"></div>
							</div>
						</div>
						<!-- 灰色线条下方内容 -->
						<div class="header-bottom-row">
							<div class="written-date">写于 {{ selectedLetter.commit_date }}</div>
						</div>
					</div>
				</div>
				<div
					v-if="!showModal && selectedLetter && !selectedLetter.locked"
					class="letter-content-area"
				>
					<div class="letter-body-box">
						<div class="letter-body">
							<p v-for="(line, idx) in selectedLetter.content.split('\n')" :key="idx">
								{{ line }}
							</p>
						</div>
					</div>
				</div>
				<div
					v-if="!showModal && selectedLetter && !selectedLetter.locked"
					style="
						margin-top: 24px;
						display: flex;
						flex-direction: column;
						align-items: flex-start;
					"
				>
					<button
						class="audio-gen-btn"
						:disabled="audioLoading"
						@click="generateAudio"
						style="
							background: #1abc9c;
							color: #fff;
							border: none;
							border-radius: 24px;
							padding: 10px 28px;
							font-size: 16px;
							font-weight: bold;
							cursor: pointer;
							transition: background 0.2s;
							margin-bottom: 18px;
						"
					>
						{{ audioLoading ? "生成中..." : "生成个性化语音" }}
					</button>
					<div v-if="audioUrl" style="width: 100%">
						<audio
							:src="audioUrl"
							controls
							style="width: 100%; margin-top: 8px"
						></audio>
					</div>
				</div>
				<!-- 欢迎界面：无信件选中时显示 -->
				<div v-if="!showModal && !selectedLetter" class="capsule-welcome-panel">
					<div class="welcome-icon-circle">
						<img :src="letterIcon" alt="信封图标" class="welcome-icon-img" />
					</div>
					<div class="welcome-title">给未来的信</div>
					<div class="welcome-subtitle">写给未来自己的一封信</div>
					<img :src="homeLetterImg" alt="信封图片" class="welcome-main-img" />
					<div class="welcome-desc">把此刻的想法、梦想和期许写下来，交给未来的自己。</div>
					<div class="welcome-desc">当约定的时间到来，你将收到来自过去的自己的问候。</div>
				</div>
				<!-- 这里可以放置时光胶囊的主内容 -->
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import postboxIcon from "@/assets/icons/postbox.png";
import lockIcon from "@/assets/icons/lock.png";
import letterIcon from "@/assets/icons/letter.png";
import homeLetterImg from "@/assets/pictures/HomeLetter.png";
function cancelLetter() {
	showModal.value = false;
	selectedLetter.value = null;
}
import { ref, onMounted } from "vue";
import FutureHeader from "@/components/Future/FutureHeader.vue";
import { ElDatePicker } from "element-plus";
import "element-plus/dist/index.css";

const showModal = ref(false);
const form = ref({
	title: "",
	unlock_date: "",
	content: "",
});
const letters = ref<Letter[]>([]);
// 获取用户token和id
function getUserInfo() {
	const currentUserDataString = localStorage.getItem("userInfo");
	const currentUserdata = currentUserDataString ? JSON.parse(currentUserDataString) : null;
	return {
		user_id: currentUserdata?.userid,
		token: currentUserdata?.token,
	};
}

// 获取所有信件
async function fetchLetters() {
	const { user_id, token } = getUserInfo();
	if (!user_id || !token) {
		alert("请先登录");
		return;
	}
	try {
		const res = await fetch(
			`http://gyip.liip.top:48040/get_future_letters.php?user_id=${user_id}&token=${token}`,
		);
		const data = await res.json();
		if (Array.isArray(data)) {
			letters.value = data;
		} else if (data.success === false) {
			alert(data.message || "获取信件失败");
		}
	} catch {
		alert("网络错误，获取信件失败");
	}
}

onMounted(() => {
	fetchLetters();
});
const selectedLetter = ref<Letter | null>(null);

// 音频相关
const audioUrl = ref<string | null>(null);
const audioLoading = ref(false);

async function generateAudio() {
	if (!selectedLetter.value) return;
	audioLoading.value = true;
	audioUrl.value = null;
	// 获取uid
	const currentUserDataString = localStorage.getItem("userInfo");
	const currentUserdata = currentUserDataString ? JSON.parse(currentUserDataString) : null;
	const currentUserId = currentUserdata?.userid;
	if (!currentUserId) {
		alert("请先登录");
		audioLoading.value = false;
		return;
	}
	try {
		const res = await fetch("http://gyip.liip.top:48050/api/vocal_clone", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({
				uid: currentUserId,
				text: selectedLetter.value.content,
			}),
		});
		const data = await res.json();
		console.log(data);
		if (data.code === 200 && data.audio) {
			// base64转audio
			audioUrl.value = `data:audio/mp3;base64,${data.audio}`;
		} else {
			alert(data.message || "音频生成失败");
		}
	} catch {
		alert("网络错误，音频生成失败");
	}
	audioLoading.value = false;
}
// function saveDraft() {
//   // 这里后续可实现保存草稿逻辑
//   showModal.value = false
// }
async function sendLetter() {
	// 校验表单
	if (!form.value.title || !form.value.unlock_date || !form.value.content) {
		alert("请填写完整信件信息");
		return;
	}
	// 获取用户信息
	const { user_id, token } = getUserInfo();
	if (!user_id || !token) {
		alert("请先登录");
		return;
	}
	try {
		const res = await fetch("http://gyip.liip.top:48040/future_letter.php", {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: new URLSearchParams({
				title: form.value.title,
				unlock_date: form.value.unlock_date,
				content: form.value.content,
				user_id: user_id.toString(),
				token: token,
			}),
		});
		const data = await res.json();
		console.log(data);
		// if (data.success) {
		// 重置表单并回到欢迎界面
		form.value.title = "";
		form.value.unlock_date = "";
		form.value.content = "";
		showModal.value = false;
		selectedLetter.value = null;
		// 重新获取信件列表
		await fetchLetters();
		alert("信件创建成功！");
		// } else {
		//   alert(data.message || '信件创建失败')
		// }
	} catch {
		alert("网络错误，信件创建失败");
	}
}

// 在侧边栏点击信件时选中
function selectLetter(letter: Letter) {
	selectedLetter.value = letter;
	showModal.value = false;
}

function getDaysLeft(unlockDate: string): number {
	// 计算距离启封日期的天数
	const today = new Date();
	const unlock = new Date(unlockDate.replace(/年|月/g, "-").replace("日", ""));
	const diff = Math.ceil((unlock.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
	return diff > 0 ? diff : 0;
}
// 信件类型声明
interface Letter {
	id: number;
	title: string;
	commit_date: string;
	unlock_date: string;
	locked: boolean;
	content: string;
}
</script>

<style scoped>
.future-view-wrapper {
	background: #f7fafd;
	min-height: 100vh;
}
.future-content {
	display: flex;
	height: calc(100vh - 80px);
}
.sidebar {
	width: 260px;
	background: #fff;
	border-right: 1px solid #e0e0e0;
	padding: 32px 0 0 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding-top: 40px;
	overflow-y: auto;
}
.sidebar-title {
	font-size: 20px;
	font-weight: bold;
	margin-bottom: 24px;
	color: #2d3a4b;
}
.new-letter-btn {
	width: 200px;
	height: 40px;
	background: #1abc9c;
	color: #fff;
	border: none;
	border-radius: 24px;
	font-size: 16px;
	font-weight: bold;
	margin-bottom: 32px;
	cursor: pointer;
	transition: background 0.2s;
}
.new-letter-btn:hover {
	background: #16a085;
}
.letter-list {
	list-style: none;
	padding: 0;
	width: 200px;
}
.letter-list li {
	position: relative;
	padding: 16px 0 28px 0;
	border-bottom: 1px solid #f0f0f0;
	font-size: 15px;
	color: #2d3a4b;
	display: block;
}
.letter-list li.locked .letter-title {
	color: #bdbdbd;
}
.letter-info {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.letter-title {
	font-weight: 500;
	flex: 1;
}
.letter-date {
	position: absolute;
	left: 0;
	bottom: 6px;
	font-size: 13px;
	color: #7b8a97;
	padding-left: 2px;
}
.lock-icon {
	margin-left: 8px;
	color: #e67e22;
	font-size: 13px;
	display: block;
	text-align: left;
}
.unlock-date,
.unlock-label {
	display: block;
	text-align: right;
}
.status {
	margin-left: 8px;
	color: #1abc9c;
	font-size: 13px;
}
.main-content {
	flex: 1;
	padding: 0;
	position: relative;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: flex-start;
	overflow-y: auto;
}
.letter-form-panel {
	background: #fff;
	border-radius: 16px;
	/* box-shadow: 0 8px 16px rgba(0,0,0,0.12); */
	box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
	width: 75%;
	min-height: 520px;
	margin-top: 48px;
	/* padding: 32px 48px 24px 48px; */
	padding: 24px;
	display: flex;
	flex-direction: column;
}
.modal-title-row {
	font-size: 22px;
	flex-direction: row;
	gap: 24px;
	font-weight: bold;
	margin-bottom: 8px;
	color: #2d3a4b;
	display: flex;
	/* flex-direction: column; */
}
.modal-desc {
	font-size: 15px;
	color: #7b8a97;
	margin-top: 2px;
}
.modal-form {
	margin: 18px 0 0 0;
	display: flex;
	flex-direction: column;
}
.modal-label {
	font-size: 15px;
	color: #2d3a4b;
	margin-bottom: 6px;
	margin-top: 18px;
}
.modal-input {
	height: 38px;
	border-radius: 8px;
	border: 1px solid #e0e0e0;
	padding: 0 12px;
	font-size: 15px;
	background: #f7fafd;
	margin-bottom: 2px;
	font-family: inherit;
	width: 100%;
	min-width: 700px;
	max-width: 860px;
	box-sizing: border-box;
}
.modal-textarea {
	min-height: 200px;
	max-width: 960px;
	border-radius: 8px;
	border: 1px solid #1abc9c;
	padding: 10px 12px;
	font-size: 15px;
	background: #f7fafd;
	margin-bottom: 2px;
	resize: vertical;
	font-family: inherit;
}
.modal-actions {
	display: flex;
	justify-content: flex-end;
	gap: 12px;
	margin-top: 18px;
}
.modal-btn.send {
	background: #16a085;
	color: #fff;
	padding: 8px 22px;
	border-radius: 24px;
	border: none;
	font-size: 15px;
	font-weight: bold;
	cursor: pointer;
	transition: background 0.2s;
}
.modal-btn.send:hover {
	background: #1abc9c;
}
.modal-tip {
	margin-top: 18px;
	font-size: 13px;
	color: #7b8a97;
	text-align: left;
}
.page-container {
	background-color: #f7f8fa;
}
.header-left-section {
	display: flex;
	align-items: center;
	gap: 16px;
}
.open-letter-title {
	font-size: 20px;
	font-weight: 600;
	color: #1f2937;
}
.delivery-status {
	font-size: 14px;
	color: #6b7280;
	margin-top: 4px;
}
.header-divider {
	width: 335%;
	/* width: 100%; */
	/* max-width: 820px; */
	height: 1px;
	background: #e5e7eb;
	margin: 18px 0 0 0;
}
.header-bottom-row {
	width: 100%;
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 12px;
}
.written-date {
	font-size: 14px;
	color: #6b7280;
	margin-left: 2px;
	display: flex;
	align-items: center;
	height: 44px;
}
.action-button {
	background-color: #009688;
	color: #ffffff;
	border: none;
	border-radius: 20px;
	padding: 10px 24px;
	font-size: 14px;
	font-weight: 500;
	cursor: pointer;
	transition: background-color 0.3s ease;
	margin-right: 2px;
	display: flex;
	align-items: center;
	height: 44px;
}
.letter-content-area {
	margin-top: 32px;
	padding: 0 8px;
	width: 80%;
	display: flex;
	justify-content: center;
}
.letter-body-box {
	background: #fff;
	border-radius: 12px;
	box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
	padding: 28px 48px;
	width: 100%;
	max-width: 960px;
	min-width: 700px;
	min-height: 200px;
	display: flex;
	flex-direction: column;
}
.letter-body p {
	font-family:
		-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans",
		sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
	font-size: 16px;
	line-height: 1.8;
	color: #374151;
	margin-bottom: 20px;
}

/* 新建信件弹窗样式 */
.modal-mask {
	position: fixed;
	z-index: 2000;
	top: 0;
	left: 0;
	width: 100vw;
	height: 100vh;
	background: rgba(0, 0, 0, 0.08);
	display: flex;
	align-items: center;
	justify-content: center;
}
.modal-container {
	background: #fff;
	border-radius: 16px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
	width: 520px;
	padding: 32px 32px 24px 32px;
	display: flex;
	flex-direction: column;
}
.modal-title {
	font-size: 22px;
	font-weight: bold;
	margin-bottom: 8px;
	color: #2d3a4b;
	display: flex;
	flex-direction: column;
}
.modal-desc {
	font-size: 15px;
	color: #7b8a97;
	margin-top: 2px;
}
.modal-form {
	margin: 18px 0 0 0;
	display: flex;
	flex-direction: column;
}
.modal-label {
	font-size: 15px;
	color: #2d3a4b;
	margin-bottom: 6px;
	margin-top: 18px;
}
.modal-input {
	height: 38px;
	border-radius: 8px;
	border: 1px solid #e0e0e0;
	padding: 0 12px;
	font-size: 15px;
	background: #f7fafd;
	margin-bottom: 2px;
}
.modal-textarea {
	min-height: 120px;
	border-radius: 8px;
	border: 1px solid #1abc9c;
	padding: 10px 12px;
	font-size: 15px;
	background: #f7fafd;
	margin-bottom: 2px;
	resize: vertical;
}
.modal-actions {
	display: flex;
	justify-content: flex-end;
	gap: 12px;
	margin-top: 18px;
}
.modal-btn {
	padding: 8px 22px;
	border-radius: 24px;
	border: none;
	font-size: 15px;
	font-weight: bold;
	background: #e0e0e0;
	color: #2d3a4b;
	cursor: pointer;
	transition: background 0.2s;
}
.modal-btn.primary {
	background: #1abc9c;
	color: #fff;
}
.modal-btn.send {
	background: #16a085;
	color: #fff;
}
.modal-btn:hover {
	background: #b2dfdb;
}
.modal-tip {
	margin-top: 18px;
	font-size: 13px;
	color: #7b8a97;
	text-align: left;
}

/* Element Plus 日期选择器美化 */
.el-date-picker-custom {
	width: 100%;
	font-size: 15px;
	font-family: inherit;
}
.el-date-picker-popper {
	font-size: 16px;
}
.letter-header-card {
	background-color: #ffffff;
	padding: 24px;
	border-radius: 12px;
	box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
	display: flex;
	flex-direction: column;
	justify-content: flex-start;
	align-items: flex-start;
	margin-top: 48px;
	position: relative;
	/* width: 100%; */
	/* max-width: 960px; */
	/* box-sizing: border-box; */
}
.letter-locked-panel {
	/* background-color: #ffffff;
  padding: 24px 48px;
  border-radius: 12px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;
  align-items: stretch;
  margin-top: 48px;
  position: relative;
  width: 100%; */
	/* margin-left: auto;
  margin-right: auto; */

	/* margin: 0 auto; */
	/* display: flex;
  flex-direction: column; */

	/* max-width: 960px;
  min-width: 780px; */

	width: 80%;
	/* max-width: 960px;
  min-width: 700px;

  margin: 0 auto;
  display: flex;
  flex-direction: column; */
}
.locked-status {
	display: flex;
	align-items: center;
	gap: 12px;
	font-size: 16px;
	color: #ff9800;
	margin-top: 8px;
}
.locked-icon {
	font-size: 22px;
	margin-right: 4px;
}
.locked-desc {
	font-size: 16px;
	color: #ff9800;
}
.locked-days {
	font-size: 15px;
	color: #009688;
	margin-left: 8px;
}
.locked-days-large {
	font-size: 20px;
	color: #009688;
	font-weight: bold;
	margin-left: auto;
	margin-right: 0;
	margin-top: 0;
	height: 44px;
	display: flex;
	align-items: center;
}
.header-divider {
	width: 100%;
	max-width: 820px;
	height: 1px;
	background: #e5e7eb;
	margin: 18px 0 0 0;
}
/* .letter-locked-panel .header-bottom-row {
  width: 100%;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-top: 12px;
} */
.written-date {
	font-size: 14px;
	color: #6b7280;
	margin-left: 2px;
	display: flex;
	align-items: center;
	height: 44px;
}
.letter-read-panel {
	width: 80%;
	/* max-width: 960px; */
	/* min-width: 700px; */

	margin: 0 auto;
	display: flex;
	flex-direction: column;
}
.capsule-welcome-panel {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: flex-start;
	margin-top: 32px;
	min-height: 600px;
}
.welcome-icon-circle {
	width: 88px;
	height: 88px;
	background: #d1fae5;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 32px;
}
.welcome-icon-img {
	width: 48px;
	height: 48px;
	display: block;
}
.welcome-title {
	font-size: 40px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 12px;
	text-align: center;
}
.welcome-subtitle {
	font-size: 22px;
	color: #8a99a8;
	font-weight: 500;
	margin-bottom: 36px;
	text-align: center;
}
.welcome-main-img {
	width: 340px;
	height: 220px;
	object-fit: cover;
	border-radius: 18px;
	margin-bottom: 48px;
	box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}
.welcome-desc {
	font-size: 18px;
	color: #7b8a97;
	text-align: center;
	max-width: 700px;
	margin: 0 auto;
	line-height: 2.1;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.sidebar::-webkit-scrollbar,
.main-content::-webkit-scrollbar {
	display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.sidebar,
.main-content {
	-ms-overflow-style: none; /* IE and Edge */
	scrollbar-width: none; /* Firefox */
}
</style>
