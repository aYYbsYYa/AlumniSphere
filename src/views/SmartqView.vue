<template>
	<SmartqHide />
	<SmartqHeader />
	<div class="iframe-container">
		<iframe
			ref="iframeRef"
			src="https://lke.cloud.tencent.com/webim/#/chat/rIVVLX"
			frameborder="0"
			class="iframe-content"
		></iframe>
	</div>
</template>

<script lang="ts" setup name="SmartqView">
import { ref, onMounted, onUnmounted } from "vue";
import SmartqHide from "@/components/Smartq/SmartqHide.vue";
import SmartqHeader from "@/components/Smartq/SmartqHeader.vue";

const iframeRef = ref<HTMLIFrameElement | null>(null);
let isInitialLoad = true;

const handleRedirect = () => {
	alert("会话已过期，请重新登录腾讯云智能体平台");
	window.open("/", "_self");
	window.open("https://lke.cloud.tencent.com/lke/#/trialProduct", "_self");
};

const handleIframeLoad = () => {
	// The first load is the initial one, we ignore it.
	if (isInitialLoad) {
		isInitialLoad = false;
		return;
	}
	// Any subsequent load event is considered a redirection (e.g., to a login page).
	handleRedirect();
};

const handleVisibilityChange = () => {
	// When the tab becomes visible again, reload the page to ensure a fresh state.
	if (document.visibilityState === "visible") {
		location.reload();
	}
};

onMounted(() => {
	const iframe = iframeRef.value;
	if (iframe) {
		iframe.addEventListener("load", handleIframeLoad);
	}
	document.addEventListener("visibilitychange", handleVisibilityChange);
});

onUnmounted(() => {
	const iframe = iframeRef.value;
	if (iframe) {
		iframe.removeEventListener("load", handleIframeLoad);
	}
	document.removeEventListener("visibilitychange", handleVisibilityChange);
});
</script>

<style scoped>
.iframe-container {
	z-index: 1;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	overflow: hidden;
}

.iframe-content {
	width: 100%;
	height: 100%;
	border: none;
}
</style>
