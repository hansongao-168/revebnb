# 前台用户认证 - 详细实现参考

## Laravel 后端实现

### 1. 安装 Sanctum (API Token 认证)

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 2. AuthController 完整实现

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 手机号 + 验证码登录
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 验证验证码
        if (!$this->verifySmsCode($request->phone, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => '验证码错误',
            ], 401);
        }

        // 查找或创建用户
        $user = User::firstOrCreate(
            ['phone' => $request->phone],
            ['name' => '用户' . substr($request->phone, -4)]
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

    /**
     * 微信登录
     */
    public function wechatLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appid' => 'required',
            'loginCode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 调用微信 API 获取 openid
        $wechatUser = $this->getWechatUser($request->loginCode);

        if (!$wechatUser) {
            return response()->json([
                'success' => false,
                'message' => '微信登录失败',
            ], 500);
        }

        // 查找或创建用户
        $user = User::firstOrCreate(
            ['wechat_openid' => $wechatUser['openid']],
            [
                'name' => $wechatUser['nickname'] ?? '微信用户',
                'avatar' => $wechatUser['headimgurl'] ?? '',
            ]
        );

        // 如果提供了手机号,更新手机号
        if ($request->telephoneCode) {
            $phone = $this->getWechatPhone($request->telephoneCode);
            if ($phone) {
                $user->update(['phone' => $phone]);
            }
        }

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

    /**
     * 获取用户信息
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * 更新用户信息
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|url',
            'gender' => 'sometimes|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $request->user()->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => '退出成功',
        ]);
    }

    /**
     * 调用微信 API 获取用户信息
     */
    private function getWechatUser(string $code): ?array
    {
        $appid = config('wechat.app_id');
        $secret = config('wechat.app_secret');

        $response = Http::get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json();

        if (isset($data['openid'])) {
            return $data;
        }

        return null;
    }

    /**
     * 验证短信验证码
     */
    private function verifySmsCode(string $phone, string $code): bool
    {
        // 实现你的验证码验证逻辑
        // 例如从 Redis 中获取验证码并比对
        return true;
    }

    /**
     * 获取微信手机号
     */
    private function getWechatPhone(string $code): ?string
    {
        // 实现获取手机号的逻辑
        return null;
    }
}
```

### 3. 路由配置

```php
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// 公开路由
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/wechat-login', [AuthController::class, 'wechatLogin']);
    Route::post('/send-sms-code', [AuthController::class, 'sendSmsCode']);
});

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/logout', [AuthController::class, 'logout']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
});
```

### 4. 更新 User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'avatar',
    'wechat_openid',
    'gender',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gender' => 'integer',
        ];
    }
}
```

## UniApp 前端实现

### 1. 登录页面完整示例

```vue
<!-- uniapp-frontend/src/pages/login/index.vue -->
<template>
  <view class="login-container">
    <view class="logo">
      <image src="/static/logo.png" mode="aspectFit" />
    </view>

    <!-- 手机号登录 -->
    <view class="login-form">
      <view class="input-group">
        <input
          v-model="form.phone"
          type="number"
          maxlength="11"
          placeholder="请输入手机号"
          class="input"
        />
      </view>

      <view class="input-group code-group">
        <input
          v-model="form.code"
          type="number"
          maxlength="4"
          placeholder="请输入验证码"
          class="input"
        />
        <button
          class="code-btn"
          :disabled="countdown > 0"
          @click="handleSendCode"
        >
          {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
        </button>
      </view>

      <button class="login-btn" @click="handleLogin">
        登录
      </button>
    </view>

    <!-- 微信登录 -->
    <view class="wechat-login">
      <button class="wechat-btn" @click="handleWechatLogin">
        <image src="/static/wechat-icon.png" class="wechat-icon" />
        微信快捷登录
      </button>
    </view>

    <!-- 用户协议 -->
    <view class="agreement">
      <checkbox-group @change="handleAgreeChange">
        <label>
          <checkbox :checked="agreed" color="#007AFF" />
          <text>我已阅读并同意</text>
          <text class="link">《用户协议》</text>
          <text>和</text>
          <text class="link">《隐私政策》</text>
        </label>
      </checkbox-group>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { loginByWechatApi } from '@/api/login'
import { useUserStore } from '@/store/user'

const userStore = useUserStore()

const form = reactive({
  phone: '',
  code: '',
})

const countdown = ref(0)
const agreed = ref(false)

let timer: number | null = null

const handleSendCode = async () => {
  if (!/^1[3-9]\d{9}$/.test(form.phone)) {
    uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
    return
  }

  // 调用发送验证码 API
  try {
    await sendSmsCodeApi(form.phone)
    uni.showToast({ title: '验证码已发送', icon: 'success' })

    // 开始倒计时
    countdown.value = 60
    timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0 && timer) {
        clearInterval(timer)
        timer = null
      }
    }, 1000)
  } catch (error) {
    uni.showToast({ title: '发送失败', icon: 'none' })
  }
}

const handleLogin = async () => {
  if (!agreed.value) {
    uni.showToast({ title: '请先同意用户协议', icon: 'none' })
    return
  }

  if (!form.phone || !form.code) {
    uni.showToast({ title: '请填写完整信息', icon: 'none' })
    return
  }

  try {
    uni.showLoading({ title: '登录中...' })
    
    const result = await loginApi(form)
    
    if (result.success) {
      userStore.setUserInfo(result.data)
      uni.showToast({ title: '登录成功', icon: 'success' })
      
      // 跳转到首页
      setTimeout(() => {
        uni.switchTab({ url: '/pages/index/index' })
      }, 1500)
    }
  } catch (error) {
    uni.showToast({ title: '登录失败', icon: 'none' })
  } finally {
    uni.hideLoading()
  }
}

const handleWechatLogin = async () => {
  if (!agreed.value) {
    uni.showToast({ title: '请先同意用户协议', icon: 'none' })
    return
  }

  try {
    uni.showLoading({ title: '登录中...' })
    
    await userStore.loginByWechat('')
    
    uni.showToast({ title: '登录成功', icon: 'success' })
    setTimeout(() => {
      uni.switchTab({ url: '/pages/index/index' })
    }, 1500)
  } catch (error) {
    uni.showToast({ title: '登录失败', icon: 'none' })
  } finally {
    uni.hideLoading()
  }
}

const handleAgreeChange = (e: any) => {
  agreed.value = e.detail.value.length > 0
}

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
})
</script>

<style scoped lang="scss">
.login-container {
  padding: 80rpx 60rpx;
  min-height: 100vh;
  background: #fff;
}

.logo {
  text-align: center;
  margin-bottom: 80rpx;
  
  image {
    width: 160rpx;
    height: 160rpx;
  }
}

.login-form {
  .input-group {
    margin-bottom: 30rpx;
    border-bottom: 1rpx solid #eee;
    
    .input {
      height: 90rpx;
      font-size: 30rpx;
    }
  }

  .code-group {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .input {
      flex: 1;
    }

    .code-btn {
      padding: 0 20rpx;
      font-size: 26rpx;
      color: #007AFF;
      background: transparent;
      
      &[disabled] {
        color: #999;
      }
    }
  }

  .login-btn {
    margin-top: 60rpx;
    height: 90rpx;
    line-height: 90rpx;
    background: #007AFF;
    color: #fff;
    border-radius: 45rpx;
    font-size: 32rpx;
  }
}

.wechat-login {
  margin-top: 60rpx;

  .wechat-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 90rpx;
    background: #07C160;
    color: #fff;
    border-radius: 45rpx;
    font-size: 32rpx;

    .wechat-icon {
      width: 40rpx;
      height: 40rpx;
      margin-right: 10rpx;
    }
  }
}

.agreement {
  margin-top: 40rpx;
  font-size: 24rpx;
  color: #666;

  .link {
    color: #007AFF;
  }
}
</style>
```

### 2. 类型定义

```typescript
// uniapp-frontend/src/pages/login/types.ts
export interface IMiniWxLoginParams {
  appid: string
  loginCode: string
  telephoneCode: string
}

export interface IMiniWxLoginRes {
  success: boolean
  data: {
    token: string
    user: IUserInfo
  }
}

export interface IUserInfo {
  id: number
  name: string
  phone?: string
  avatar?: string
  email?: string
  token: string
  wechat_openid?: string
  gender?: number
}

export interface LoginParams {
  phone: string
  code: string
}
```

### 3. 用户资料页

```vue
<!-- uniapp-frontend/src/pages/user/profile.vue -->
<template>
  <view class="profile-page">
    <view class="avatar-section">
      <image :src="userInfo.avatar || '/static/default-avatar.png'" class="avatar" />
      <button class="change-avatar" @click="handleChangeAvatar">
        更换头像
      </button>
    </view>

    <view class="info-section">
      <view class="info-item">
        <text class="label">昵称</text>
        <input
          v-model="form.name"
          placeholder="请输入昵称"
          class="input"
        />
      </view>

      <view class="info-item">
        <text class="label">手机号</text>
        <text class="value">{{ userInfo.phone || '未绑定' }}</text>
      </view>

      <view class="info-item">
        <text class="label">性别</text>
        <radio-group @change="handleGenderChange">
          <label>
            <radio value="1" :checked="form.gender === 1" />男
          </label>
          <label>
            <radio value="2" :checked="form.gender === 2" />女
          </label>
        </radio-group>
      </view>
    </view>

    <button class="save-btn" @click="handleSave">保存</button>
    <button class="logout-btn" @click="handleLogout">退出登录</button>
  </view>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import { useUserStore } from '@/store/user'
import { updateProfileApi, logoutApi } from '@/api/user'

const userStore = useUserStore()
const userInfo = computed(() => userStore.userInfo)

const form = reactive({
  name: userInfo.value.name,
  gender: userInfo.value.gender || 0,
})

const handleChangeAvatar = async () => {
  uni.chooseImage({
    count: 1,
    success: async (res) => {
      const tempFilePath = res.tempFilePaths[0]
      
      // 上传头像
      const uploadRes = await uploadAvatarApi(tempFilePath)
      
      if (uploadRes.success) {
        userStore.setUserInfo({
          ...userInfo.value,
          avatar: uploadRes.data.url,
        })
        uni.showToast({ title: '头像更新成功', icon: 'success' })
      }
    },
  })
}

const handleGenderChange = (e: any) => {
  form.gender = parseInt(e.detail.value)
}

const handleSave = async () => {
  try {
    uni.showLoading({ title: '保存中...' })
    
    const result = await updateProfileApi(form)
    
    if (result.success) {
      userStore.setUserInfo({
        ...userInfo.value,
        ...form,
      })
      uni.showToast({ title: '保存成功', icon: 'success' })
    }
  } catch (error) {
    uni.showToast({ title: '保存失败', icon: 'none' })
  } finally {
    uni.hideLoading()
  }
}

const handleLogout = async () => {
  uni.showModal({
    title: '提示',
    content: '确定要退出登录吗?',
    success: async (res) => {
      if (res.confirm) {
        await logoutApi()
        userStore.clearUserInfo()
        uni.reLaunch({ url: '/pages/index/index' })
      }
    },
  })
}
</script>

<style scoped lang="scss">
.profile-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40rpx;
}

.avatar-section {
  background: #fff;
  padding: 60rpx;
  text-align: center;
  margin-bottom: 20rpx;

  .avatar {
    width: 160rpx;
    height: 160rpx;
    border-radius: 50%;
    margin-bottom: 20rpx;
  }

  .change-avatar {
    background: transparent;
    color: #007AFF;
    font-size: 28rpx;
  }
}

.info-section {
  background: #fff;
  margin-bottom: 40rpx;

  .info-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 30rpx;
    border-bottom: 1rpx solid #eee;

    &:last-child {
      border-bottom: none;
    }

    .label {
      width: 140rpx;
      font-size: 30rpx;
      color: #333;
    }

    .input {
      flex: 1;
      text-align: right;
      font-size: 30rpx;
    }

    .value {
      flex: 1;
      text-align: right;
      font-size: 30rpx;
      color: #666;
    }
  }
}

.save-btn {
  margin: 0 40rpx 20rpx;
  height: 90rpx;
  line-height: 90rpx;
  background: #007AFF;
  color: #fff;
  border-radius: 45rpx;
}

.logout-btn {
  margin: 0 40rpx;
  height: 90rpx;
  line-height: 90rpx;
  background: #fff;
  color: #ff4444;
  border-radius: 45rpx;
}
</style>
```

## 数据库迁移

```php
// database/migrations/xxxx_xx_xx_create_users_table.php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique()->nullable();
        $table->string('phone')->unique()->nullable();
        $table->string('password')->nullable();
        $table->string('avatar')->nullable();
        $table->string('wechat_openid')->unique()->nullable();
        $table->tinyInteger('gender')->default(0)->comment('0未知 1男 2女');
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
}
```
