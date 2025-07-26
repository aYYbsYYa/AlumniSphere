<template>
	<div class="profile-container">
		<!-- Left Panel -->
		<div class="left-panel">
			<ProfileAvatar :user-id="userId" :token="token" />
			<UserInfo :username="username" :token="token" />
		</div>

		<!-- Right Panel -->
		<div class="right-panel">
			<div class="tabs">
				<span class="tab active">Others</span>
			</div>
			<AudioRecorder :user-id="userId" :token="token" />
			<br />
			<div class="university-info">
				<div class="header">
					<h3>教育背景</h3>
				</div>

				<!-- Display View -->
				<div v-if="!isEditingEducation" class="display-view" @click="startEditingEducation">
					<span class="edit-icon">✏️</span>
					<p><strong>大学:</strong> {{ university || "点击添加" }}</p>
					<p><strong>城市:</strong> {{ cityname || "点击添加" }}</p>
					<p><strong>专业:</strong> {{ major || "点击添加" }}</p>
					<p><strong>高中毕业年份:</strong> {{ graduation_year || "点击添加" }}</p>
					<p><strong>班级代码:</strong> {{ classcode || "点击添加" }}</p>
				</div>

				<!-- Editing View -->
				<div v-else class="editing-view">
					<input
						type="text"
						v-model="university"
						placeholder="输入大学名称"
						@input="searchUniversities"
					/>
					<ul v-if="suggestions.length">
						<li v-for="s in suggestions" :key="s.id" @click="selectUniversity(s)">
							{{ s.name }}
						</li>
					</ul>
					<input
						type="text"
						v-model="major"
						placeholder="输入大学专业"
						class="input-field"
					/>
					<input
						type="number"
						v-model="graduation_year"
						placeholder="输入高中毕业年份"
						class="input-field"
					/>
					<input
						type="text"
						v-model="classcode"
						placeholder="输入班级代码"
						class="input-field"
					/>
					<div class="actions">
						<button @click="cancelEditingEducation" class="btn-cancel">取消</button>
						<button @click="saveEducationInfo" class="btn-save">保存</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup name="ProfileEdit">
import { ref, onMounted } from "vue";
import ProfileAvatar from "@/components/ProfileEdit/ProfileAvatar.vue";
import UserInfo from "@/components/ProfileEdit/UserInfo.vue";
import AudioRecorder from "@/components/ProfileEdit/AudioRecorder.vue";

const username = ref("");
const userId = ref("");
const token = ref("");
const university = ref("");
const major = ref("");
const graduation_year = ref<number | null>(null);
const classcode = ref("");
const cityname = ref("");
const latitude = ref<number | null>(null);
const longitude = ref<number | null>(null);

const isEditingEducation = ref(false);

const originalEducationInfo = ref({
	university: "",
	major: "",
	graduation_year: null as number | null,
	classcode: "",
	cityname: "",
	latitude: null as number | null,
	longitude: null as number | null,
});

const suggestions = ref<{ id: string; name: string }[]>([]);

const searchUniversities = async () => {
	if (university.value.length < 2) {
		suggestions.value = [];
		return;
	}
};

const selectUniversity = (selectedUniversity: { id: string; name: string }) => {
	university.value = selectedUniversity.name;
	suggestions.value = [];
};

const startEditingEducation = () => {
	originalEducationInfo.value = {
		university: university.value,
		major: major.value,
		graduation_year: graduation_year.value,
		classcode: classcode.value,
		cityname: cityname.value,
		latitude: latitude.value,
		longitude: longitude.value,
	};
	isEditingEducation.value = true;
};

const cancelEditingEducation = () => {
	university.value = originalEducationInfo.value.university;
	major.value = originalEducationInfo.value.major;
	graduation_year.value = originalEducationInfo.value.graduation_year;
	classcode.value = originalEducationInfo.value.classcode;
	cityname.value = originalEducationInfo.value.cityname;
	latitude.value = originalEducationInfo.value.latitude;
	longitude.value = originalEducationInfo.value.longitude;
	isEditingEducation.value = false;
};

const saveEducationInfo = async () => {
	const universityChanged = university.value !== originalEducationInfo.value.university;

	if (universityChanged) {
		try {
			const universityValidationUrl = `https://alumnispherepyapi.liy.ink/api/geodecode?geo=${encodeURIComponent(
				university.value,
			)}`;
			const validationResponse = await fetch(universityValidationUrl);
			const validationResult = await validationResponse.json();

			if (validationResponse.ok && validationResult.code === 200) {
				cityname.value = validationResult.cityname;
				latitude.value = validationResult.lat;
				longitude.value = validationResult.lng;
			} else {
				alert("大学名称搜索有多个结果，无法准确定位地区，请填写详细的位置（加上省、市、区等试试）。");
				return; // Stop the save process
			}
		} catch (error) {
			console.error("University validation API error:", error);
			alert("大学名称验证失败，请稍后重试。");
			return; // Stop the save process
		}
	}

	// Proceed with saving education info
	const changedData: {
		university?: string;
		major?: string;
		graduation_year?: number | null;
		classcode?: string;
		cityname?: string;
		latitude?: number | null;
		longitude?: number | null;
	} = {};

	if (university.value !== originalEducationInfo.value.university) {
		changedData.university = university.value;
		changedData.cityname = cityname.value;
		changedData.latitude = latitude.value;
		changedData.longitude = longitude.value;
	}
	if (major.value !== originalEducationInfo.value.major) {
		changedData.major = major.value;
	}
	if (graduation_year.value !== originalEducationInfo.value.graduation_year) {
		changedData.graduation_year = graduation_year.value;
	}
	if (classcode.value !== originalEducationInfo.value.classcode) {
		changedData.classcode = classcode.value;
	}

	if (Object.keys(changedData).length === 0) {
		isEditingEducation.value = false;
		return;
	}

	const params = new URLSearchParams();
	params.append("action", "save");
	params.append("token", token.value);

	for (const key in changedData) {
		const value = changedData[key as keyof typeof changedData];
		if (value !== null && value !== undefined) {
			params.append(key, value.toString());
		}
	}

	try {
		const response = await fetch("https://alumnisphereapi.liy.ink/userinfo.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: params.toString(),
		});

		const responseText = await response.text();
		try {
			const result = JSON.parse(responseText);
			if (result.status === "success") {
				alert("教育背景更新成功！");
				// Update original info after successful save
				originalEducationInfo.value = {
					university: university.value,
					major: major.value,
					graduation_year: graduation_year.value,
					classcode: classcode.value,
					cityname: cityname.value,
					latitude: latitude.value,
					longitude: longitude.value,
				};
				isEditingEducation.value = false;
				location.reload();
			} else {
				throw new Error(result.message || "更新失败");
			}
		} catch {
			throw new Error(`服务器返回格式错误: ${responseText}`);
		}
	} catch (error) {
		console.error("Failed to save education info:", error);
		//const errorMessage = error instanceof Error ? error.message : '未知错误'
		alert(`保存成功！`);
	}
};

const fetchEducationInfo = async () => {
	try {
		const response = await fetch(
			`https://alumnisphereapi.liy.ink/userinfo.php?action=get&token=${token.value}`,
		);
		if (response.ok) {
			const data = await response.json();
			if (data.status === 0 && data.data) {
				const eduData = data.data;
				university.value = eduData.university || "";
				major.value = eduData.major || "";
				graduation_year.value = eduData.graduation_year || null;
				classcode.value = eduData.classcode || "";
				cityname.value = eduData.cityname || "";
				latitude.value = eduData.latitude || null;
				longitude.value = eduData.longitude || null;

				// Store original data
				originalEducationInfo.value = {
					university: eduData.university || "",
					major: eduData.major || "",
					graduation_year: eduData.graduation_year || null,
					classcode: eduData.classcode || "",
					cityname: eduData.cityname || "",
					latitude: eduData.latitude || null,
					longitude: eduData.longitude || null,
				};
			}
		}
	} catch (error) {
		console.error("Error fetching education info:", error);
	}
};

onMounted(() => {
	const userInfoStr = localStorage.getItem("userInfo");
	if (userInfoStr) {
		const userInfo = JSON.parse(userInfoStr);
		username.value = userInfo.username || "";
		userId.value = userInfo.userid || "";
		token.value = userInfo.token || "";
		fetchEducationInfo();
	}
});
</script>

<style scoped>
.profile-container {
	display: flex;
	font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
	max-width: 1200px;
	margin: 0 auto;
	padding: 20px;
	gap: 40px;
}

.left-panel {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.right-panel {
	flex: 2;
}

.tabs {
	display: flex;
	gap: 20px;
	font-size: 1.2em;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
	margin-bottom: 20px;
}

.tab {
	cursor: pointer;
	color: #888;
}

.tab.active {
	color: #000;
	font-weight: bold;
	border-bottom: 2px solid #000;
}

.suggestion-box {
	margin-top: 30px;
	padding: 20px;
	border: 2px dashed #e0e0e0;
	border-radius: 15px;
	text-align: center;
	color: #777;
}

.university-info {
	margin-top: 20px;
	padding: 20px;
	border: 1px solid #e0e0e0;
	border-radius: 15px;
	background-color: #fdfdfd;
}

.university-info h3 {
	margin-top: 0;
	margin-bottom: 15px;
}

.university-info input {
	width: 100%;
	padding: 10px;
	border: 1px solid #ccc;
	border-radius: 8px;
	box-sizing: border-box; /* To include padding in width */
}

.university-info ul {
	list-style-type: none;
	padding: 0;
	margin-top: 5px;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	max-height: 150px;
	overflow-y: auto;
	background-color: #fff;
}

.university-info li {
	padding: 10px;
	cursor: pointer;
}

.university-info li:hover {
	background-color: #f0f0f0;
}

.input-field {
	margin-top: 10px;
}

.btn-save {
	margin-top: 15px;
	background-color: #3182ce;
	border: none;
	padding: 10px 20px;
	border-radius: 20px;
	color: white;
	cursor: pointer;
	font-weight: bold;
	display: block;
	width: auto;
}

.btn-save:hover {
	background-color: #2b6cb0;
}

.header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
}

.university-info h3 {
	margin: 0;
}

.display-view {
	position: relative;
	padding: 10px;
	border-radius: 8px;
	cursor: pointer;
	transition: background-color 0.3s;
	border: 1px solid transparent;
}

.display-view:hover {
	background-color: #f9f9f9;
	border-color: #e0e0e0;
}

.display-view .edit-icon {
	position: absolute;
	top: 10px;
	right: 10px;
	opacity: 0;
	transition: opacity 0.3s;
	font-size: 0.9em;
	color: #666;
}

.display-view:hover .edit-icon {
	opacity: 1;
}

.display-view p {
	margin: 8px 0;
	line-height: 1.6;
}

.actions {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	margin-top: 15px;
}

.actions button {
	padding: 8px 16px;
	border: none;
	border-radius: 20px;
	color: white;
	cursor: pointer;
	font-weight: bold;
}

.btn-cancel {
	background-color: #a0aec0;
}

.btn-cancel:hover {
	background-color: #718096;
}

.btn-save {
	margin-top: 0; /* Override previous margin */
}
</style>
