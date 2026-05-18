# 前台模块结构 + 导航管理 — 设计说明

**日期**: 2026-05-18  
**状态**: 已定稿（implementation plan: `docs/superpowers/plans/2026-05-18-site-frontend-modules-navigation-implementation.md`）  
**范围**: 消费者 Blade 站点 + UniApp；平台 `/admin` Filament

---

## 1. 背景与目标

### 1.1 背景

当前消费者前台（Laravel Blade + Vite）中，顶栏、页脚、分类条等导航均为视图硬编码；页面视图分散在 `resources/views/site/*`，无统一的「模块」边界。仓库内虽有 `frontend-navbar-management` skill（Webpack + `navbars` 表），与本项目 **服务端渲染 Blade** 架构不一致，且尚未落地。

运营需要：在 **不改 HTML 布局、不做 CMS** 的前提下，管理各区域导航的文案、链接、排序与显隐；开发需要：按现有前台页面 **结构性拆分代码模块**，并与后台登记对齐。

### 1.2 目标

1. 建立清晰的 **前台代码模块结构**（按业务域组织 Controller 引用视图、View、共享 Component）。
2. 在 `/admin` 增加 Filament **「前台」** 分组：**页面模块登记** + **Web / UniApp 分表导航管理**。
3. 运营可配置：**标题、链接、排序、显隐、打开方式**；不可配置：Blade 结构、业务逻辑、富文本块。

### 1.3 已排除

| 项 | 说明 |
|----|------|
| 完整 CMS | 用户明确放弃；不实现块拼装、任意 URL 落地页 |
| 多租户消费者分站 | 消费者站视为 **平台单站**，`site_*` 表无 `tenant_id` |
| UniApp `pages.json` 自动生成 | 新页面仍由开发注册；后台仅管理导航数据 |
| 租户/房东面板导航 | 本期仅消费者 Web + UniApp |

### 1.4 需求决策摘要

| 主题 | 决策 |
|------|------|
| 总体方案 | **方案 2**：`site_pages` 模块登记 + 导航表引用 |
| 后台与代码 | **双轨（B）**：manifest 登记模块 + Filament 管导航 |
| 导航区域 | **顶栏 + 页脚 + 分类条 + 用户菜单 + Hero CTA + 预订流 + 房源卡片 + UniApp**（用户选 D，全部纳入） |
| UniApp 数据 | **分表（B）**：`site_navigation_items` / `uniapp_navigation_items` |
| 链接配置 | **混合（C）**：Web 命名路由校验；UniApp 从 `site_pages` 下拉或手填 path |

---

## 2. 数据模型

### 2.1 `site_pages`（页面模块登记）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | bigint PK | |
| `key` | string, unique | 稳定标识，如 `stays.index` |
| `name` | string | 后台显示名 |
| `module_group` | string | `stays` / `bookings` / `account` / `docs` / `landlord` / `uniapp` |
| `web_route_name` | string nullable | Laravel 命名路由；无 Web 能力则为 null |
| `web_route_params` | json, default `{}` | 默认 route/query 参数 |
| `uniapp_path` | string nullable | 如 `/pages/index/index` |
| `description` | text nullable | 后台说明 |
| `is_system` | boolean, default true | 系统页禁止删除 |
| `is_active` | boolean, default true | 禁用后，新导航项不可引用（已有项 Filament 校验） |
| `timestamps` | | |

**真相来源**：`config/site-pages.php`，由 `php artisan site:sync-pages` 与 `SitePageSeeder` 同步。开发新增模块流程：manifest → sync →（可选）Seeder 导航默认值。

### 2.2 `site_navigation_items`（Web 导航）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | bigint PK | |
| `placement` | string | 见 §2.4 |
| `footer_group` | string nullable | `explore` / `landlord` / `support`；仅 `placement=footer` |
| `title` | string | 展示文案 |
| `link_type` | string | `site_page` \| `named_route` \| `external_url` |
| `site_page_id` | FK → `site_pages`, nullable | `link_type=site_page` 时必填 |
| `route_name` | string nullable | `named_route` 时必填 |
| `route_params` | json nullable | |
| `external_url` | string nullable | `external_url` 时必填 |
| `icon` | string nullable | Heroicon 名或内部分类条 SVG key |
| `sort_order` | unsigned int, default 0 | Filament `reorderable` |
| `is_active` | boolean, default true | |
| `target` | string, default `_self` | `_self` / `_blank` |
| `style_variant` | string nullable | `default` / `muted` / `button` |
| `active_match` | string nullable | 路由名模式，如 `site.stays.*` |
| `timestamps` | | |

**索引建议**：`(placement, is_active, sort_order)`；`site_page_id` FK `nullOnDelete` 改为 **restrict**（有引用禁止删 page）。

### 2.3 `uniapp_navigation_items`（UniApp 导航）

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | bigint PK | |
| `placement` | string | `tab_bar` / `page_menu` / `index_shortcut` 等 |
| `title` | string | |
| `link_type` | string | `site_page` \| `path` \| `external` |
| `site_page_id` | FK nullable | 引用 `site_pages` 且 `uniapp_path` 非空 |
| `path` | string nullable | `link_type=path` |
| `external_url` | string nullable | |
| `icon` | string nullable | UniApp 侧映射资源名 |
| `sort_order` | unsigned int | |
| `is_active` | boolean | |
| `timestamps` | | |

### 2.4 `placement` 枚举（首期）

| placement | 渲染位置 |
|-----------|----------|
| `header` | `x-site.header` 主链 |
| `footer` | `x-site.footer` 列链接 |
| `category_strip` | `x-site.category-strip` |
| `user_menu` | 头像区菜单项 |
| `hero` | 住宿列表 Hero 区次要 CTA（不含搜索表单） |
| `booking_flow` | 预订确认/详情页内辅助链 |
| `listing_card` | 房源卡片可运营文案/次要链（见 §4.2） |

UniApp `placement`：`tab_bar`、`page_menu`、`index_shortcut`（首期 Tab 未在 `pages.json` 配置时，先用 `page_menu` / `index_shortcut`）。

### 2.5 链接解析规则

**Web**（`SiteNavigationResolver`）：

1. `site_page` → `route($page->web_route_name, merge($page->web_route_params, $item->route_params))`；`web_route_name` 为空则回退 `external_url` 或报错日志 + 跳过该项。
2. `named_route` → 保存时校验 `Route::has()`；运行期 `route($name, $params)`。
3. `external_url` → 原样输出。

**UniApp**（API Resource）：

1. `site_page` → 返回 `path` = `site_pages.uniapp_path`。
2. `path` → 直接返回。
3. `external` → 返回 `external_url`。

### 2.6 缓存与 Fallback

- `SiteNavigationService::forPlacement(string $placement, ?string $footerGroup = null)`  
  - 缓存键：`site.nav.{placement}.{footerGroup?}`，TTL 默认 3600s（`config/site.php` 可配置）。
  - Filament 保存/删除导航后，按 placement 清除相关键。
- **Fallback**：某 placement 无 active 记录时，使用 `SiteNavigationDefaults`（Seeder 自当前硬编码导入），保证与现网一致。
- **禁止**：删除仍被 `site_navigation_items` 引用的 `site_pages`（FK restrict + Filament 删除前检查）。

---

## 3. 代码结构

### 3.1 目录

```
app/Site/
  Enums/SiteNavPlacement.php
  Enums/SiteNavLinkType.php
  Enums/SiteModuleGroup.php
  Support/SitePageManifest.php
  Services/SiteNavigationResolver.php
  Services/SiteNavigationService.php
  View/Composers/SiteNavigationComposer.php

config/site-pages.php
config/site.php                    # cache ttl 等

app/Http/Controllers/Site/         # 保留命名空间；视图路径更新
app/Filament/Resources/SitePages/
app/Filament/Resources/SiteNavigationItems/
app/Filament/Resources/UniappNavigationItems/

resources/views/site/modules/
  stays/index.blade.php
  stays/show.blade.php
  bookings/confirmation.blade.php
  bookings/show.blade.php
  account/bookings.blade.php

resources/views/site/components/   # 原 components/site/* 可保留或 alias
```

### 3.2 首期 `site_pages` 登记

| key | module_group | web_route_name | uniapp_path |
|-----|--------------|----------------|-------------|
| `stays.index` | stays | `site.stays.index` | — |
| `stays.show` | stays | `site.stays.show` | — |
| `bookings.confirmation` | bookings | `site.bookings.confirmation` | — |
| `bookings.show` | bookings | `site.bookings.show` | — |
| `account.bookings` | account | `site.me.bookings` | — |
| `docs.stored-urls-intro` | docs | `docs.stored-urls-intro` | — |
| `landlord.portal-login` | landlord | — | — |
| `uniapp.index` | uniapp | — | `/pages/index/index` |
| `uniapp.login` | uniapp | — | `/pages/login/index` |
| `uniapp.profile` | uniapp | — | `/pages/user/profile` |

`landlord.portal-login` 无命名路由时，导航项使用 `link_type=external_url` 指向 `/landlord-portal/login`。

### 3.3 视图与 Composer

- `SiteNavigationComposer` 绑定 `components.layouts.site`（及需要导航的 guest 布局）。
- 注入变量：
  - `$siteNav`：`array<placement, Collection<ResolvedNavItem>>`
  - `$siteNavActive`：当前路由名（`Route::currentRouteName()`）
- 各 Blade 组件从 `$siteNav` 读取；`active_match` 支持 `*` 后缀通配。

### 3.4 Filament（`/admin`，navigationGroup = `前台`）

| Resource | 行为 |
|----------|------|
| SitePageResource | 列表/编辑；`key`、`web_route_name`、`uniapp_path` 仅 sync 可写（表单 disabled）；`is_system` 不可删 |
| SiteNavigationItemResource | CRUD + placement 筛选 + reorder + 批量显隐 |
| UniappNavigationItemResource | 同上；`site_page` 下拉仅 `uniapp_path IS NOT NULL` |

### 3.5 API

```
GET /api/uniapp/navigation?placement=tab_bar
```

- 无认证（公开导航）；`throttle:120,1`。
- 响应：`{ "success": true, "data": [ { "title", "path", "icon", "sort_order" } ] }`。

### 3.6 动态路由与组件内链接

含路由参数的动态链接（`site.stays.show` + `$listing`、`site.bookings.show` + `$booking`）**仍在 Blade/Controller 内** 使用 `route()` 生成；后台 `listing_card` / `booking_flow` placement 仅管理：

- 可统一的 **静态辅助链接**（如「查看全部订单」→ `site.me.bookings`）；
- **显隐 / 文案**（若产品需要）。

卡片主链（进入详情）保持 `route('site.stays.show', $listing)`，不通过 DB 拼参数。

---

## 4. 错误处理与边界

| 场景 | 行为 |
|------|------|
| `named_route` 不存在 | Filament 保存失败，字段级错误 |
| `site_page` 已禁用 | Filament 禁止关联；已有关联项列表标警告 |
| 解析失败（运行时） | `report()` + 跳过该项；Composer 不抛 500 |
| 缓存不可用 | 降级直查 DB |
| 外链 | `external_url` 须 `http://` 或 `https://` 或站内 path `/...` |
| UniApp path | 须以 `/pages/` 开头（校验规则） |
| 删除 site_page | 有导航引用 → 403 / 验证错误 |

---

## 5. 测试

| 类型 | 用例 |
|------|------|
| Unit | `SiteNavigationResolver`：三种 link_type、params merge、无效 route |
| Feature | Seeder 后首页含 header 链接；改 `is_active=false` 后不可见 |
| Feature | Filament：创建 `named_route` 非法名失败 |
| Feature | `GET /api/uniapp/navigation` 返回排序、过滤 inactive |
| Feature | `site:sync-pages` 幂等；新增 manifest key 出现在 DB |
| Browser（可选） | 顶栏链接 200（后续 webapp-testing） |

---

## 6. 分期交付

### Phase 1 — 基础（必须先合）

- 迁移三表 + Model + manifest + Seeder + `site:sync-pages`
- `SiteNavigationService` + Resolver + Composer
- 改造 `header`、`footer`、`category-strip`
- Filament：SitePage、SiteNavigationItem
- 视图迁至 `site/modules/*`（路由名不变）
- PHPUnit（Resolver + 首页导航）

### Phase 2 — 扩展区域 + UniApp

- `user_menu`、`hero`、`booking_flow`、`listing_card` placements + 组件接入
- `uniapp_navigation_items` + API + Filament Resource
- UniApp 客户端拉取 navigation（`page_menu` / 未来 `tab_bar`）

### Phase 3 — 运营增强（可选）

- 导航项预览 URL（Filament Action）
- 版本/草稿（若需要再开 spec）
- 全站 `active_match` 可视化配置助手

---

## 7. 依赖与参考

- Filament v5 Resource 模式：对齐 `StoredUrlResource`（Schemas/Tables 子目录）。
- 现有路由：`routes/web.php` 中 `site.*`、`docs.*` 不变。
- 相关 spec：`2026-05-13-listings-availability-bookings-design.md`（房源/订单业务不受本 spec 修改行为）。

---

## 8. 开放问题（实现前可默认）

| 问题 | 默认 |
|------|------|
| `user_menu` 未登录显示什么 | Seeder：「登录」→ `uniapp.login` 或 Web 登录页（若后续有）；「成为房东」保留 header `style_variant=button` |
| 分类条 icon | Seeder 存 SVG path key，Blade 映射表与现 `category-strip` 一致 |
| Filament 仅 admin `User` | 沿用 `/admin` 现有认证，不新增 policy（与 StoredUrl 同级） |
