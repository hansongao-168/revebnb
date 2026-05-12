---
name: frontend-user-auth
description: Implement frontend user authentication and management features including login, registration, profile management, and user state handling using Laravel backend and UniApp frontend. Use when building user authentication, login pages, registration forms, user profile features, or managing user state in UniApp.
---

# Frontend User Authentication & Management

实现前台用户认证与管理功能,包括登录、注册、用户资料管理等。

## 项目结构

### 后端 (Laravel)
- User Model: `app/Models/User.php`
- 路由: `routes/web.php` 或 `routes/api.php`
- 控制器: `app/Http/Controllers/Api/`

### 前端 (UniApp)
- 登录页: `uniapp-frontend/src/pages/login/index.vue`
- API 层: `uniapp-frontend/src/api/login/`
- 状态管理: `uniapp-frontend/src/store/user.ts`
- 类型定义: `uniapp-frontend/src/pages/login/types.ts`

## 快速开始

### 1. 实现登录功能

**后端 API:**
```php
// routes/api.php
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/wechat-login', [AuthController::class, 'wechatLogin']);
```

**前端调用:**
```typescript
// uniapp-frontend/src/api/login/index.ts
export const loginApi = (data: LoginParams) => {
  return request.post('/api/auth/login', data)
}
```

### 2. 用户状态管理

参考 `uniapp-frontend/src/store/user.ts`:
- `userInfo` - 用户信息
- `isLogined` - 登录状态
- `loginByWechat()` - 微信登录
- `clearUserInfo()` - 清除登录状态

### 3. 路由守卫

在 `uniapp-frontend/src/interceptors/` 中实现路由拦截:
```typescript
// 检查登录状态
if (!userStore.isLogined) {
  uni.navigateTo({ url: '/pages/login/index' })
}
```

## 核心功能实现

### 微信登录流程

```
Task Progress:
- [ ] Step 1: 调用 uni.login() 获取 code
- [ ] Step 2: 发送 code 到后端
- [ ] Step 3: 后端调用微信 API 换取 openid
- [ ] Step 4: 生成 token 返回前端
- [ ] Step 5: 前端存储 token 和用户信息
```

**前端实现:**
```typescript
const handleWechatLogin = async () => {
  const loginRes = await uni.login({ provider: 'weixin' })
  const { code } = loginRes
  
  const result = await loginByWechatApi({
    appid: userStore.appid,
    loginCode: code,
    telephoneCode: phoneCode.value,
  })
  
  userStore.setUserInfo(result.data)
  uni.switchTab({ url: '/pages/index/index' })
}
```

**后端实现:**
```php
public function wechatLogin(Request $request)
{
    $code = $request->loginCode;
    
    // 调用微信 API 换取 openid
    $wechatUser = $this->getWechatUser($code);
    
    // 查找或创建用户
    $user = User::firstOrCreate(
        ['openid' => $wechatUser['openid']],
        ['name' => $wechatUser['nickname']]
    );
    
    // 生成 token
    $token = $user->createToken('auth_token')->plainTextToken;
    
    return response()->json([
        'success' => true,
        'data' => [
            'token' => $token,
            'user' => $user,
        ],
    ]);
}
```

### 用户资料管理

**API 路由:**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'updateAvatar']);
});
```

**前端页面:**
```vue
<!-- uniapp-frontend/src/pages/user/profile.vue -->
<template>
  <view class="profile-page">
    <image :src="userInfo.avatar" class="avatar" />
    <input v-model="form.name" placeholder="昵称" />
    <button @click="handleUpdate">保存</button>
  </view>
</template>

<script setup lang="ts">
const userStore = useUserStore()
const form = reactive({
  name: userStore.userInfo.name,
  avatar: userStore.userInfo.avatar,
})

const handleUpdate = async () => {
  await updateProfileApi(form)
  userStore.setUserInfo({ ...userStore.userInfo, ...form })
  uni.showToast({ title: '更新成功' })
}
</script>
```

## 最佳实践

### 1. Token 管理
- 使用 Sanctum 或 Passport 管理 API token
- Token 存储在 `localStorage` 或 `pinia` (带持久化)
- 设置合理的过期时间

### 2. 请求拦截器
```typescript
// uniapp-frontend/src/interceptors/request.ts
request.interceptors.request.use((config) => {
  const token = userStore.userInfo.token
  if (token) {
    config.header.Authorization = `Bearer ${token}`
  }
  return config
})
```

### 3. 响应拦截器
```typescript
// 处理 401 未授权
request.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.status === 401) {
      userStore.clearUserInfo()
      uni.redirectTo({ url: '/pages/login/index' })
    }
    return Promise.reject(error)
  }
)
```

### 4. 路由守卫
```typescript
// uniapp-frontend/src/interceptors/router.ts
const whiteList = ['/pages/login/index', '/pages/index/index']

uni.addInterceptor('navigateTo', {
  invoke(e) {
    if (!whiteList.includes(e.url) && !userStore.isLogined) {
      uni.navigateTo({ url: '/pages/login/index' })
      return false
    }
    return true
  },
})
```

## 常见功能模板

### 手机号登录
```vue
<template>
  <view class="login-page">
    <input 
      v-model="phone" 
      type="number" 
      maxlength="11"
      placeholder="请输入手机号" 
    />
    <button 
      @click="sendCode" 
      :disabled="countdown > 0"
    >
      {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
    </button>
    <input v-model="code" placeholder="验证码" />
    <button @click="handleLogin">登录</button>
  </view>
</template>
```

### 用户信息展示
```vue
<template>
  <view class="user-card">
    <image :src="userInfo.avatar" class="avatar" />
    <text class="name">{{ userInfo.name }}</text>
    <text class="phone">{{ userInfo.phone }}</text>
  </view>
</template>

<script setup lang="ts">
const userStore = useUserStore()
const userInfo = computed(() => userStore.userInfo)
</script>
```

## 注意事项

1. **安全性**
   - 密码必须加密存储 (Laravel 自动处理)
   - 使用 HTTPS 传输敏感数据
   - Token 设置合理过期时间

2. **用户体验**
   - 登录失败给出明确提示
   - 记住登录状态
   - 提供快捷登录方式 (微信/手机号)

3. **状态同步**
   - 用户信息变更后更新 store
   - 多端登录处理策略
   - 退出登录时清除所有状态

## 相关文件

- User Model: [User.php](file:///var/www/laravel13x/app/Models/User.php)
- 登录页: [index.vue](file:///var/www/laravel13x/uniapp-frontend/src/pages/login/index.vue)
- 用户 Store: [user.ts](file:///var/www/laravel13x/uniapp-frontend/src/store/user.ts)
- 登录 API: [index.ts](file:///var/www/laravel13x/uniapp-frontend/src/api/login/index.ts)
