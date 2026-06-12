<template>
	<div class="login-container">
		<!-- 登录主卡片 -->
		<div class="login-card">
			<!-- 线性简约插画 核心视觉 -->
			<div class="illustration">
				<svg viewBox="0 0 200 100" fill="none" xmlns="http://www.w3.org/2000/svg">
					<!-- 线性小猫 极简勾勒 -->
					<path d="M60 70 C40 70, 30 50, 50 40 C60 35, 70 35, 80 40 C100 50, 90 70, 70 70 Z" stroke="#f89fc9" stroke-width="2" stroke-linecap="round"/>
					<circle cx="55" cy="50" r="2" fill="#f89fc9"/>
					<circle cx="75" cy="50" r="2" fill="#f89fc9"/>
					<!-- 线性云朵 简约装饰 -->
					<path d="M130 50 C120 40, 140 30, 150 40 C160 35, 175 45, 170 60 C160 70, 140 70, 130 60 Z" stroke="#f89fc9" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</div>

			<h2 class="title">V图床登录</h2>

			<!-- 登录表单 -->
			<form class="login-form" @submit.prevent="handleLogin">
				<div class="input-group">
					<input
						v-model="form.username"
						type="text"
						placeholder="请输入账号"
						required
						class="input-item"
					/>
				</div>

				<div class="input-group">
					<input
						v-model="form.password"
						type="password"
						placeholder="请输入密码"
						required
						class="input-item"
					/>
				</div>

				<button type="submit" class="login-btn" :disabled="loading">
					{{ loading ? '登录中...' : '立即登录' }}
				</button>
			</form>
		</div>
	</div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { VMessage } from '../../component/Message'

const router = useRouter()
// 表单数据
const form = ref({
	username: '',
	password: ''
})
// 加载状态
const loading = ref(false)

// 对接真实登录接口
const handleLogin = async () => {
	loading.value = true
	try {
		// 调用后端登录接口
		const res = await fetch("/api/login", {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				username: form.value.username,
				password: form.value.password
			})
		})

		// 解析后端返回的JSON数据
		const result = await res.json()

		// 判断接口返回状态（根据后端常规规范：code=200 代表成功）
		if (res.ok && result.data?.token) {
			// 存储token到本地存储
			localStorage.setItem('token', result.data.token)
			VMessage("success", '登录成功！欢迎回来🌸')
			// 跳转到首页
			router.push('/')
		} else {
			// 后端返回登录失败
			VMessage("error", result.message || '账号或密码错误，请重新输入！')
		}
	} catch (error) {
		// 网络错误/接口请求失败
		VMessage("error", '登录请求失败，请检查网络或联系管理员！')
		console.error('登录接口错误：', error)
	} finally {
		// 无论成功失败，关闭加载状态
		loading.value = false
	}
}
</script>

<style lang="scss">
@use "../../scss/login.scss";
</style>