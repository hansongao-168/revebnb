---
name: install-filament5
description: 在 Laravel 项目中安装 Filament v5 管理面板（Panel Builder）、执行 `filament:install`、配置 Tailwind CSS v4 与 Vite、创建管理员并验证 `/admin` 访问。用于用户提到安装 Filament 5、Filament 5.0、filament 后台、管理面板、`composer require filament/filament`、`php artisan filament:install` 或从 v4 升级至 v5 时。
---

# 安装 Filament 5 管理后台

## 适用场景

在**已有 Laravel 项目**中安装 Filament v5 的 **Panel Builder**（典型后台 `/admin`）。若项目尚未创建，先按项目内 [install-laravel13](../install-laravel13/SKILL.md) 初始化 Laravel，再回到本技能。

## 快速触发词

- 安装 Filament 5 / Filament 5.0
- filament 后台 / 管理面板
- `filament:install` / `make:filament-user`

## 前置条件（官方要求）

安装前确认：

- PHP **8.2+**
- Laravel **v11.28+**（Laravel 12/13 满足）
- Tailwind CSS **v4.1+**（面板与前端资源需要；见下文「前端资源」）

若 Composer 或 PHP 扩展缺失，可先运行：

```bash
bash .cursor/skills/install-laravel13/scripts/check_laravel13_env.sh
```

（脚本面向 Laravel 13 环境，同样覆盖 PHP/Composer 等基础项。）

## 执行流程：Panel Builder（推荐）

在**项目根目录**（存在 `artisan` 与 `composer.json`）按顺序执行。

### 1) 安装 Composer 包

```bash
composer require filament/filament:"^5.0"
```

在 **Windows PowerShell** 中 `^` 可能被忽略，请改用：

```bash
composer require filament/filament:"~5.0"
```

### 2) 安装面板脚手架

```bash
php artisan filament:install --panels
```

预期结果：

- 生成并注册 `App\Providers\Filament\AdminPanelProvider`（路径一般为 `app/Providers/Filament/AdminPanelProvider.php`）
- Laravel 11+ 使用 `bootstrap/providers.php`：若访问面板报错，检查该文件中是否已注册上述 Provider；未注册则按 [Laravel 文档](https://laravel.com/docs/providers#registering-providers) 手动加入

### 3) 前端资源（按项目类型二选一）

**A. 全新 Laravel 项目**（可接受覆盖脚手架文件时）

官方提供一键脚手架（会覆盖已修改文件，仅适合新项目）：

```bash
php artisan filament:install --scaffold
npm install
npm run dev
```

**B. 已有项目**（保留现有前端时）

按官方「Existing Laravel projects」手动集成，要点如下：

1. 安装 Filament 前端相关安装步骤：

   ```bash
   php artisan filament:install
   ```

2. 若尚未安装 Tailwind v4 与 Vite 插件：

   ```bash
   npm install tailwindcss @tailwindcss/vite --save-dev
   ```

3. 在 `resources/css/app.css` 中按需 `@import` Filament 各包下的 `vendor/filament/.../resources/css/index.css`（至少包含 `support`；按已安装的 tables/forms 等包增减，以控制体积）。官方列表见 [Installation · Configuring styles](https://filamentphp.com/docs/5.x/introduction/installation#configuring-styles)。

4. 在 `vite.config.js` 中注册 `@tailwindcss/vite` 插件，并保证 `laravel-vite-plugin` 的 `input` 包含 `resources/css/app.css`。

5. 运行 `npm run dev`（或 `npm run build`）编译资源。

6. 布局中需包含 `@filamentStyles`、`@filamentScripts` 及应用的 `@vite(...)`；若使用 Notifications 闪讯，按文档在布局中加入 `@livewire('notifications')`。

### 4) 创建管理员账号

```bash
php artisan make:filament-user
```

按提示填写邮箱、密码等。

### 5) 可选：发布配置

```bash
php artisan vendor:publish --tag=filament-config
```

便于统一调整 `config/filament.php` 中的默认值。

### 6) 验证

```bash
php artisan route:list | grep -i filament
php artisan about
```

浏览器访问 **`/admin`**，使用上一步创建的用户登录。

## 从 Filament v4 升级到 v5

不要直接当新装执行；使用官方升级包与脚本：

```bash
composer require filament/upgrade:"^5.0" -W --dev
vendor/bin/filament-v5
```

按脚本输出的命令逐项执行，完成后移除升级包：

```bash
composer remove filament/upgrade --dev
```

详细说明见官方 [Upgrade guide](https://filamentphp.com/docs/5.x/upgrade-guide)。

## 仅使用独立组件（非完整 Panel）

若不需要后台面板，只需 Tables/Forms 等 Blade 组件，使用官方「Individual components」的 `composer require` 列表（`filament/tables`、`filament/forms` 等，版本均为 `^5.0`），并同样配置 Tailwind 与 CSS `@import`。详见官方 [Installation](https://filamentphp.com/docs/5.x/introduction/installation#installing-the-individual-components)。

## 输出格式

对用户汇总时使用：

```markdown
Filament 5 安装结果：
- 环境：PHP / Laravel 版本是否满足要求
- Composer：`filament/filament` 是否已 require
- 脚手架：`filament:install --panels` 是否成功；`AdminPanelProvider` 是否注册
- 前端：新项目 scaffold 或已有项目 Tailwind/Vite/CSS 是否已按文档配置
- 管理员：`make:filament-user` 是否完成
- 验证：`/admin` 与 `route:list` 摘要
- 下一步：多面板、权限、插件或生产部署（参考官方 Deployment 文档）
```

## 常见问题

| 现象 | 处理方向 |
|------|----------|
| 访问 `/admin` 500 或空白 | 检查 `bootstrap/providers.php`；检查 Vite/CSS 是否编译；查看 `storage/logs/laravel.log` |
| 样式丢失 | 确认 `app.css` 中 Filament 的 `@import` 完整且已 `npm run dev` |
| Composer 约束解析失败 | 确认 Laravel 版本 ≥ 11.28；必要时 `composer update` 后再 require |
| PowerShell 安装版本错乱 | 使用 `~5.0` 代替 `^5.0` |

## 延伸阅读（一层链接）

- 官方安装与样式配置：[filamentphp.com/docs/5.x/introduction/installation](https://filamentphp.com/docs/5.x/introduction/installation)
- 入门与资源结构：[Getting started](https://filamentphp.com/docs/5.x/getting-started)
