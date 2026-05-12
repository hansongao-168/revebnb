<template>
  <view class="page">
    <image v-if="form.avatar" :src="form.avatar" class="avatar" mode="aspectFill" />
    <view class="field">
      <text class="label">昵称</text>
      <input v-model="form.name" class="input" placeholder="昵称" />
    </view>
    <view class="field">
      <text class="label">手机</text>
      <input v-model="form.phone" class="input" type="number" maxlength="20" placeholder="手机号" />
    </view>
    <button class="btn" :loading="loadingAvatar" @click="pickAvatar">更换头像</button>
    <button class="btn primary" :loading="loading" @click="handleUpdate">保存</button>
  </view>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { fetchProfileApi, updateProfileApi } from '@/api/login/index'
import { uploadAvatarApi } from '@/api/user/index'
import { useUserStore } from '@/store/user'

const userStore = useUserStore()
const loading = ref(false)
const loadingAvatar = ref(false)

const form = reactive({
  name: '',
  phone: '',
  avatar: '' as string | null,
})

onMounted(async () => {
  if (!userStore.isLogined) {
    uni.navigateTo({ url: '/pages/login/index' })
    return
  }
  try {
    const res = await fetchProfileApi()
    if (res.success && res.data) {
      form.name = res.data.name
      form.phone = res.data.phone ?? ''
      form.avatar = res.data.avatar
    }
  } catch {
    uni.showToast({ title: '加载失败', icon: 'none' })
  }
})

const handleUpdate = async () => {
  loading.value = true
  try {
    const res = await updateProfileApi({ name: form.name, phone: form.phone ? form.phone : null })
    if (res.success && res.data) {
      userStore.patchUser(res.data)
      uni.showToast({ title: '更新成功', icon: 'success' })
    }
  } catch {
    uni.showToast({ title: '更新失败', icon: 'none' })
  } finally {
    loading.value = false
  }
}

const pickAvatar = () => {
  uni.chooseImage({
    count: 1,
    success: async (r) => {
      const path = r.tempFilePaths[0]
      if (!path) {
        return
      }
      loadingAvatar.value = true
      try {
        const res = await uploadAvatarApi(path)
        if (res.success && res.data) {
          form.avatar = res.data.avatar
          userStore.patchUser(res.data)
          uni.showToast({ title: '头像已更新', icon: 'success' })
        }
      } catch {
        uni.showToast({ title: '上传失败', icon: 'none' })
      } finally {
        loadingAvatar.value = false
      }
    },
  })
}
</script>

<style scoped>
.page {
  padding: 32rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}
.avatar {
  width: 160rpx;
  height: 160rpx;
  border-radius: 80rpx;
  align-self: center;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}
.label {
  font-size: 28rpx;
}
.input {
  border: 1px solid #ddd;
  border-radius: 8rpx;
  padding: 16rpx;
}
.btn.primary {
  background: #f59e0b;
  color: #fff;
}
</style>
