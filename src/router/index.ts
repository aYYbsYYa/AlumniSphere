import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '@/views/HomeView.vue'
import MapView from '@/views/MapView.vue'
import AllAlumniesView from '@/views/AllAlumniesView.vue'
import FutureView from '@/views/FutureView.vue'

const router = createRouter({
	history: createWebHistory(import.meta.env.BASE_URL),
	routes: [
		{
			//主页
			path: '/',
			name: 'homeview',
			component: HomeView,
		},
		{
			//登录
			path: '/login',
			name: 'login',
			component: () => import('@/views/LoginView.vue'),
		},
		{
			//注册
			path: '/register',
			name: 'register',
			component: () => import('@/views/RegisterView.vue'),
		},
		{
			// 地图
			path: '/map',
			name: 'map',
			component: MapView,
		},
		{
			//个人信息编辑
			path: '/profile',
			name: 'profile',
			component: () => import('@/views/Profile/ProfileView.vue'),
			children: [
				{
					path: 'isFirstTime',
					name: 'profileisFirstTime',
					component: () => import('@/views/Profile/ProfileisFirstTime.vue'),
				},
				{
					path: 'edit',
					name: 'profileEdit',
					component: () => import('@/views/Profile/ProfileEdit.vue'),
				},
			],
		},
		{
			//所有校友
			path: '/AllAlumnies',
			name: 'AllAlumnies',
			component: AllAlumniesView,
		},
		{
			// 时光胶囊
			path: '/future',
			name: 'future',
			component: FutureView,
		},
		{
			// 个人信息
			path: '/userinfo/:id',
			name: 'userinfo',
			component: () => import('@/views/UserInfoView.vue'),
		},
		{
			// 与TA对聊
			path: '/callta',
			name: 'callta',
			component: () => import('@/views/CalltaView.vue'),
		},
		{
			// 语音合成
			path: '/smartq',
			name: 'smartq',
			component: () => import('@/views/SmartqView.vue'),
		},
	],
})

export default router
