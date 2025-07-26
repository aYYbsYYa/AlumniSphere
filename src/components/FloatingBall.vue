<template>
	<div
		class="floating-ball"
		@mousedown="startDrag"
		@touchstart="startDrag"
		:style="{ top: top + 'px', left: left + 'px' }"
		@click="handleClick"
	>
		🤖
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const top = ref(window.innerHeight - 80)
const left = ref(window.innerWidth - 80)
const dragging = ref(false)
const clickFlag = ref(true)

let startX = 0
let startY = 0
let startTop = 0
let startLeft = 0

const startDrag = (e: MouseEvent | TouchEvent) => {
	dragging.value = true
	clickFlag.value = true
	const touch = e.type === 'touchstart' ? (e as TouchEvent).touches[0] : null
	const clientX = touch ? touch.clientX : (e as MouseEvent).clientX
	const clientY = touch ? touch.clientY : (e as MouseEvent).clientY
	startX = clientX
	startY = clientY
	startTop = top.value
	startLeft = left.value

	document.addEventListener('mousemove', onDrag)
	document.addEventListener('touchmove', onDrag)
	document.addEventListener('mouseup', stopDrag)
	document.addEventListener('touchend', stopDrag)
}

const onDrag = (e: MouseEvent | TouchEvent) => {
	if (dragging.value) {
		clickFlag.value = false
		const touch = e.type === 'touchmove' ? (e as TouchEvent).touches[0] : null
		const clientX = touch ? touch.clientX : (e as MouseEvent).clientX
		const clientY = touch ? touch.clientY : (e as MouseEvent).clientY
		const dx = clientX - startX
		const dy = clientY - startY
		top.value = startTop + dy
		left.value = startLeft + dx
	}
}

const stopDrag = () => {
	dragging.value = false
	document.removeEventListener('mousemove', onDrag)
	document.removeEventListener('touchmove', onDrag)
	document.removeEventListener('mouseup', stopDrag)
	document.removeEventListener('touchend', stopDrag)
}

const handleClick = () => {
	if (clickFlag.value) {
		if (router.currentRoute.value.path === '/smartq') {
			router.back()
		} else {
			router.push('/smartq')
		}
	}
}
</script>

<style scoped>
.floating-ball {
	position: fixed;
	width: 60px;
	height: 60px;
	background-color: lightgreen;
	border-radius: 50%;
	display: flex;
	justify-content: center;
	align-items: center;
	cursor: grab;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
	z-index: 1000;
	font-size: 30px;
	user-select: none;
}
</style>
