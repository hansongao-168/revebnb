# 前台用户认证与管理技能

## 概述

这个技能帮助您快速实现 Laravel + UniApp 全栈的前台用户认证与管理功能。

## 技能结构

```
frontend-user-auth/
├── SKILL.md           # 主要技能文件 (267 行)
├── REFERENCE.md       # 详细实现参考 (810 行)
└── scripts/
    └── generate-auth.sh  # 代码生成脚本
```

## 何时使用

当您需要进行以下开发时,此技能会自动触发:

- 实现用户登录/注册功能
- 开发微信授权登录
- 创建用户资料页面
- 实现用户状态管理
- 配置路由守卫和请求拦截器
- 开发手机号验证码登录

## 快速使用

### 方式 1: 自然语言触发

直接告诉 AI 您的需求:

```
"帮我实现微信登录功能"
"创建一个用户资料页"
"添加手机号验证码登录"
```

### 方式 2: 使用代码生成脚本

```bash
cd /var/www/laravel13x
bash .qoder/skills/frontend-user-auth/scripts/generate-auth.sh
```

脚本会提供交互式菜单,选择您需要生成的代码模块。

## 功能清单

### 后端 (Laravel)

- ✅ AuthController 完整实现
  - 手机号 + 验证码登录
  - 微信授权登录
  - 用户资料管理
  - 退出登录
  
- ✅ API 路由配置
  - 公开路由
  - 认证路由 (Sanctum)
  
- ✅ User Model 扩展
  - 微信 openid
  - 手机号
  - 头像
  - 性别

### 前端 (UniApp)

- ✅ 登录页面
  - 手机号验证码登录
  - 微信快捷登录
  - 用户协议同意
  - 表单验证
  
- ✅ 用户资料页
  - 头像上传
  - 昵称修改
  - 性别选择
  - 退出登录
  
- ✅ 状态管理 (Pinia)
  - 用户信息存储
  - 登录状态判断
  - 微信登录方法
  
- ✅ API 层
  - 登录 API
  - 用户资料 API
  - 类型定义
  
- ✅ 拦截器
  - 请求拦截 (添加 Token)
  - 响应拦截 (处理 401)
  - 路由守卫

## 技术栈

### 后端
- Laravel 11+
- Laravel Sanctum (API 认证)
- PHP 8.2+

### 前端
- UniApp
- Vue 3 (Composition API)
- TypeScript
- Pinia (状态管理)
- SCSS

## 示例代码位置

所有详细代码示例都在 [REFERENCE.md](REFERENCE.md) 中,包括:

1. 完整的 AuthController 实现
2. 登录页面完整代码 (含样式)
3. 用户资料页完整代码
4. 类型定义
5. 数据库迁移示例

## 开发流程

### 实现登录功能

```
1. 参考 SKILL.md 了解整体架构
2. 查看 REFERENCE.md 获取完整代码
3. 使用 generate-auth.sh 生成基础文件
4. 根据实际需求定制代码
```

### 实现用户资料管理

```
1. 确保登录功能已完成
2. 参考 SKILL.md 中的"用户资料管理"章节
3. 复制 REFERENCE.md 中的用户资料页代码
4. 根据需要添加字段
```

## 最佳实践

### 1. 安全性
- 使用 HTTPS
- Token 设置过期时间
- 密码加密存储
- 验证码防刷机制

### 2. 用户体验
- 登录失败明确提示
- 记住登录状态
- 提供多种登录方式
- 加载状态反馈

### 3. 代码组织
- API 层统一管理
- 类型定义清晰
- 状态集中管理
- 拦截器处理通用逻辑

## 常见问题

### Q: 如何实现短信验证码?
A: 参考 REFERENCE.md 中的 `sendSmsCode` 方法,集成第三方短信服务 (如阿里云、腾讯云)。

### Q: 微信登录如何获取手机号?
A: 需要使用微信的 `getPhoneNumber` 组件,用户授权后获取加密数据,后端解密。

### Q: 如何处理多端登录?
A: 在 User Model 中记录当前 token,新登录时使旧 token 失效。

### Q: 如何实现记住登录?
A: Pinia store 已配置 `persist: true`,会自动持久化到本地存储。

## 扩展建议

### 可以添加的功能
- 邮箱登录
- 第三方登录 (QQ、微博)
- 两步验证
- 密码登录
- 找回密码
- 账号绑定
- 登录日志
- 设备管理

## 注意事项

1. **微信登录配置**
   - 需要在 Laravel 配置文件中设置微信 app_id 和 app_secret
   - UniApp 中需要配置正确的 appid

2. ** Sanctum 配置**
   - 确保已安装并配置 Laravel Sanctum
   - 前端请求需要携带 token

3. **跨域问题**
   - 开发时配置 CORS
   - 生产环境使用相同域名或配置允许跨域

## 相关资源

- [Laravel Sanctum 文档](https://laravel.com/docs/sanctum)
- [UniApp 登录示例](https://uniapp.dcloud.net.cn/)
- [微信登录文档](https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/login.html)

## 更新日志

### v1.0.0 (2024-04-20)
- 初始版本发布
- 支持手机号验证码登录
- 支持微信授权登录
- 完整的用户资料管理
- 请求拦截和路由守卫
