<template>
  <view class="page">
    <view class="field">
      <text class="label">邮箱</text>
      <input v-model="email" class="input" type="text" placeholder="请输入邮箱" />
    </view>
    <view class="field">
      <text class="label">密码</text>
      <input v-model="password" class="input" password placeholder="请输入密码" />
    </view>
    <button class="btn primary" :loading="loading" @click="handleLogin">登录</button>
    <button class="btn" :loading="loadingWx" @click="handleWechatLogin">微信登录</button>
    <navigator class="link" url="/pages/user/profile">个人资料（需登录）</navigator>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { loginApi, loginByWechatApi } from '@/api/login/index'
import { useUserStore } from '@/store/user'

const email = ref('')
const password = ref('')
const loading = ref(false)
const loadingWx = ref(false)
const userStore = useUserStore()

const handleLogin = async () => {
  if (!email.value || !password.value) {
    uni.showToast({ title: '请填写邮箱和密码', icon: 'none' })
    return
  }
  loading.value = true
  try {
    const res = await loginApi({ email: email.value, password: password.value })
    if (!res.success || !res.data) {
      uni.showToast({ title: res.message ?? '登录失败', icon: 'none' })
      return
    }
    userStore.setUserInfo({ token: res.data.token, user: res.data.user })
    uni.showToast({ title: '登录成功', icon: 'success' })
    uni.reLaunch({ url: '/pages/index/index' })
  } catch {
    uni.showToast({ title: '登录失败', icon: 'none' })
  } finally {
    loading.value = false
  }
}

const handleWechatLogin = async () => {
  loadingWx.value = true
  try {
    const loginRes = await uni.login({ provider: 'weixin' })
    const code = loginRes.code
    if (!code) {
      uni.showToast({ title: '未获取到微信 code', icon: 'none' })
      return
    }
    const res = await loginByWechatApi({
      appid: userStore.appid || undefined,
      loginCode: code,
    })
    if (!res.success || !res.data) {
      uni.showToast({ title: res.message ?? '微信登录失败', icon: 'none' })
      return
    }
    userStore.setUserInfo({ token: res.data.token, user: res.data.user })
    uni.showToast({ title: '登录成功', icon: 'success' })
    uni.reLaunch({ url: '/pages/index/index' })
  } catch {
    uni.showToast({ title: '微信登录不可用（H5 需配置）', icon: 'none' })
  } finally {
    loadingWx.value = false
  }
}
</script>

<style scoped>
.page {
  padding: 32rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}
.label {
  font-size: 28rpx;
  color: #333;
}
.input {
  border: 1px solid #ddd;
  border-radius: 8rpx;
  padding: 16rpx;
  font-size: 28rpx;
}
.btn {
  margin-top: 8rpx;
}
.primary {
  background: #f59e0b;
  color: #fff;
}
.link {
  margin-top: 24rpx;
  color: #2563eb;
  font-size: 28rpx;
}
</style>
