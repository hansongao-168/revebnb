<template>
  <view class="page">
    <text class="title">revebnb</text>
    <text v-if="userStore.isLogined" class="muted">你好，{{ userStore.user?.name }}</text>
    <text v-else class="muted">未登录</text>
    <button v-if="!userStore.isLogined" class="btn" @click="goLogin">去登录</button>
    <button v-else class="btn" @click="goProfile">个人资料</button>
    <button v-if="userStore.isLogined" class="btn ghost" @click="logout">退出</button>
  </view>
</template>

<script setup lang="ts">
import { useUserStore } from '@/store/user'

const userStore = useUserStore()

const goLogin = () => {
  uni.navigateTo({ url: '/pages/login/index' })
}

const goProfile = () => {
  uni.navigateTo({ url: '/pages/user/profile' })
}

const logout = () => {
  userStore.clearUserInfo()
  uni.showToast({ title: '已退出', icon: 'none' })
}
</script>

<style scoped>
.page {
  padding: 48rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}
.title {
  font-size: 40rpx;
  font-weight: 600;
}
.muted {
  color: #666;
  font-size: 28rpx;
}
.btn {
  margin-top: 16rpx;
}
.ghost {
  background: transparent;
  color: #991b1b;
}
</style>
