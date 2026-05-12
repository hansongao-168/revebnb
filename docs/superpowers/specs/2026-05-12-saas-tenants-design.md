# SaaS 多租户（独立身份）— 设计说明

**日期**: 2026-05-12  
**状态**: 已定稿（待你书面确认后可进入 implementation plan）  
**范围**: 第一期（身份 + 组织 + 平台管理；业务表全量 `tenant_id` 不在本期）

---

## 1. 背景与目标

- 在现有 **Laravel + Filament `/admin`**（平台操作员 `User`、`is_admin`）之外，引入 **SaaS 客户组织** 及其 **独立后台用户**，用于 B2B 多租户形态演进。
- **硬约束**：SaaS 侧身份 **不得** 与当前平台后台 `users` 整合（不同表、不同 guard、不同登录入口；禁止共用 Filament 登录态混用两类主体）。
- **第一期范围（①）**：新建 **`tenants`**、**`saas_users`**（及必要的审计/令牌策略）；**不**在第一期对既有业务表做全量 `tenant_id` 与全局 scope（第二期按模块追加）。

---

## 2. 架构总览（推荐方案：双主体 + 双面板）

| 维度 | 平台方 | SaaS 租户方 |
|------|--------|----------------|
| 模型 | `App\Models\User`（现有） | `App\Models\Tenant`、`App\Models\SaasUser`（新建） |
| 认证 | `web` / 现有 Filament 默认 | 新 guard **`saas`** + provider 指向 `SaasUser` |
| Filament Panel | 现有 **`/admin`** | 新 Panel，路径建议 **`/tenant-admin`** |
| Sanctum / API | 现有 `/api/auth/*` 绑定 `User`（不变更语义） | 若上 App：**新前缀** `/api/saas/...`，`tokenable_type` 区分 `SaasUser`；**第一期可仅 Web 面板，无租户 App** |

```text
Platform staff (User, is_admin) ──► /admin ──► CRUD Tenant, 管理 SaasUser（受控）, 订阅占位
Tenant owner (SaasUser)        ──► /tenant-admin ──► 个人资料、改密、组织展示信息（只读或极简）
```

---

## 3. 数据模型（第一期）

### 3.1 `tenants`

- 建议字段：`id`、`name`、`slug`（或 `code`，唯一）、`status`（枚举：试用 / 正式 / 停用）、联系人字段（如 `contact_email`、`contact_name`）、`notes`（可选）。
- **订阅占位**（二选一实现时敲定，本期不展开计费引擎）：  
  - **轻量**：`plan`（nullable string）、`trial_ends_at` / `subscription_ends_at`（nullable datetime）直接挂在 `tenants`；或  
  - **空壳表**：`subscriptions` 仅 `tenant_id`、状态、周期字段，界面只读。

### 3.2 `saas_users`

- **与 `users` 零复用**；字段示例：`id`、`tenant_id`（FK）、`name`、`email`（唯一策略：**全局唯一** 或 **`tenant_id`+`email` 联合唯一**——实现前在 migration 层二选一，推荐 **联合唯一** 便于同邮箱不同租户演示）、`password`（hashed）、`email_verified_at`（可选）、`role`（第一期仅 `owner` 枚举值）、`remember_token`（若用 session）、`status`（启用/禁用）。
- **第一期仅单 owner**：创建租户时原子创建 **一条** `role=owner` 的 `SaasUser`。**多成员邀请** 明确列为 **第二期**。

### 3.3 令牌与会话失效

- 当平台将租户 **`status=停用`** 时，该租户下所有 `SaasUser` 的 API/会话应不可用。  
- **实现选项**（spec 层二选一，开发时择一落地，不在此文档写死实现细节）：  
  - **A**：`tenants` 上维护 `access_token_version`（整数），签发 Sanctum token 时在自定义校验或 middleware 中比对；停租户时递增版本使旧 token 全部失效。  
  - **B**：停租户时 **批量 revoke** 该租户所有 `personal_access_tokens`（需按 `tokenable_type`+`tokenable_id` 或自定义关联查询）。  

---

## 4. Filament 与权限

### 4.1 平台 `/admin`

- **TenantResource**：列表、创建、编辑、停用；创建租户时 **同事务** 创建 owner `SaasUser`（随机强密码 + **密码重置/设置链接** 或平台内「复制一次性密码」策略——实现时选一种并配合审计）。
- **SaasUserResource**（可选强度）：至少 **按租户筛选**、查看、**重置密码/重发邀请**；禁止把 `SaasUser` 与 `User` 合并展示为同一「用户」语义。
- **不提供**「以租户身份代登录租户面板」的一键按钮（避免两类主体混淆）；若未来需要，单独开 spec。

### 4.2 租户 `/tenant-admin`

- 新 `TenantPanelProvider`：`->path('tenant-admin')`、`->authGuard('saas')`、`->login()`。  
- **Owner 第一期页面**：个人资料、修改密码；租户信息 **只读或极简可编辑字段**（与平台约定哪些字段由谁改）。  
- **Middleware**：`EnsureSaasUserBelongsToTenant`（从 `auth('saas')->user()` 解析 `tenant_id`，禁止跨租户上下文）。所有租户 Panel 内查询 **至少** 对 `SaasUser`、`Tenant` 自身强制 `tenant_id` 约束。

---

## 5. API 约定（若第一期不做租户 App 可整节延期）

- 路由前缀：`/api/saas/...`（与现有 `/api/auth/*` 分离）。  
- 认证：`Authorization: Bearer` + Sanctum，`tokenable` 为 `SaasUser`。  
- 错误码与 JSON 结构与现有 API **对齐风格**（`success` / `message` / `data`），避免两套互不兼容的 envelope。

---

## 6. 审计（第一期最小集合）

- 记录：平台 `User` 对 `Tenant` / `SaasUser` 的创建、停用、改订阅占位、重置密码等；可选记录 `SaasUser` 改密。  
- 实现形态：**专用 `audit_logs` 表** 或 **spatie/laravel-activitylog**（二选一，开发任务中确定）。  
- 字段意图：`actor_type`、`actor_id`、`action`、`subject_type`、`subject_id`、`properties`（JSON）、`ip`、`created_at`。

---

## 7. 安全与错误提示（摘要）

- 租户停用：登录接口返回 **明确业务文案**（非泛化 401）。  
- 密码重置链接：**签名 URL 或一次性 token**，短 TTL、一次性消费。  
- 平台侧访问 `SaasUser`：**必须** `tenant_id` 过滤 + policy，防 ID 遍历。

---

## 8. 明确不在第一期（第二期及以后）

- 租户内 **多成员**、邀请链路、RBAC 细粒度角色。  
- 业务实体全表 **`tenant_id` + 全局 TenantScope**。  
- 计费引擎、发票、支付网关深度集成（仅允许占位字段）。  
- 独立子域名 / 独立部署的第三套应用（除非未来单独 spec）。

---

## 9. 自检（spec review）

| 检查项 | 结论 |
|--------|------|
| 占位符 | `tenants` 上订阅字段 vs `subscriptions` 表为 **实现时二选一**；令牌失效 **A/B 二选一**。其余需求已写死。 |
| 内部一致性 | 双面板 + 独立表与「不与 User 整合」一致；第一期不做业务 scope 与 ① 一致。 |
| 范围 | 单 repo、单进程内双 Panel；无微服务拆分。 |
| 歧义 | 「联合唯一 email」已标注为 migration 层决策；与产品确认后可改为全局唯一 email。 |

---

## 10. 验收标准（第一期）

1. 平台管理员可在 `/admin` **创建租户**并自动生成 **唯一 owner `SaasUser`**，且该用户 **仅** 能登录 `/tenant-admin`。  
2. **`users` 登录 `/admin`** 与 **`saas_users` 登录 `/tenant-admin`** 互不串用、不共享会话语义。  
3. 租户 **停用** 后，owner **无法** 再使用旧令牌或新登录进入租户面板（按第 3.3 节选定策略验证）。  
4. 关键平台操作有 **审计记录**（至少：创建租户、停租户、重置 owner 密码）。  
5. **未**对既有业务表强制加 `tenant_id`（与范围一致）。

---

## 11. 下一步（流程）

经你确认本文件无修改意见后，按项目流程进入 **implementation plan**（`writing-plans` 工作流）：拆 migration、Panel、Resource、middleware、测试与数据迁移顺序。
