# 租房系统（第一期）：B2B2C 房东独立后台 + 魔法链接 Token — 设计说明

**日期**: 2026-05-12  
**状态**: 已定稿（请你通读本文件并书面确认后，再进入 implementation plan）  
**依赖**: 现有 SaaS 基建见 `docs/superpowers/specs/2026-05-12-saas-tenants-design.md`（`Tenant`、`SaasUser`、双面板、`/admin` 与 `/tenant-admin`）

---

## 1. 背景与目标

- 在 **平台 `/admin`** 与 **租户 `/tenant-admin`** 之外，引入 **`Landlord`（房东）** 及其 **第三套独立后台**，用于 B2B2C：**平台 → 组织（`Tenant`）→ 房东 →（后续）房源/租约**。
- **第一期范围（本 spec）**：仅实现 **挂在 `Tenant` 下的房东**、**入口魔法链接（3 天有效）**、**过期后自动轮换 token 并邮件通知房东**、**房东 Filament 面板骨架**、**平台管理员托管 CRUD 与应急重发**。  
- **明确不在第一期**：`Tenant` 外的「平台直挂个人房东」分支；租客端、支付、电子合同、完整房源/库存/日历价；`SaasUser` 自助创建房东（第二期下放权限时需单独 spec + RBAC）。

---

## 2. 架构总览（三主体 + 三面板）

| 维度 | 平台方 | 租户组织方 | 房东方 |
|------|--------|------------|--------|
| 模型 | `User`（`is_admin`） | `Tenant`、`SaasUser` | `Landlord`（`tenant_id` 必填） |
| 认证 | `web` | `saas` | **`landlord`**（session；由魔法链接兑换建立） |
| Filament Panel | `/admin` | `/tenant-admin` | **`/landlord-portal`**（路径名可微调，须全局唯一） |

```text
Platform admin (User)     ──► /admin          ──► 创建/禁用房东、吊销/重发 token（应急）
Tenant owner (SaasUser)   ──► /tenant-admin ──► 本期不创建房东（二期）
Landlord                  ──► /landlord-portal ──► 仅能通过有效 token 兑换进入；与 admin/tenant-admin 会话隔离
```

**硬约束**

- `Landlord` **不得** 与 `users`、`saas_users` 混表或混 guard。  
- 房东 **不得** 登录 `/admin` 与 `/tenant-admin`。  
- 所有房东侧数据查询 **必须** 带 `tenant_id` 约束（与后续租房域表一致）。

---

## 3. 数据模型（第一期）

### 3.1 `landlords`

| 字段 | 说明 |
|------|------|
| `id` | PK |
| `tenant_id` | FK → `tenants.id`，**必填** |
| `name` | 展示名 |
| `email` | 接收入口链接邮件 |
| `phone` | 可选 |
| `status` | `active` / `disabled`；`disabled` 禁止兑换与访问 |
| `last_auto_token_email_at` | datetime nullable；**防刷**：记录最后一次「系统自动发 token 邮件」时间，用于 24h 内次数上限 |
| `timestamps` | |

**约束**

- `unique(tenant_id, email)`：同一组织内邮箱唯一；跨租户可重复。

### 3.2 `landlord_access_tokens`

| 字段 | 说明 |
|------|------|
| `id` | PK |
| `landlord_id` | FK |
| `token_hash` | **仅存哈希**；全局或租户维度 unique（推荐全局 unique） |
| `issued_at` | 签发时间 |
| `expires_at` | `issued_at + 72 hours` |
| `revoked_at` | nullable；吊销/轮换时写入 |
| `renewal_email_sent_at` | nullable；**幂等**：标记「针对该 token 生命周期结束的自动续发邮件已发送」（与调度逻辑配合，避免重复） |
| `timestamps` | |

**轮换规则**

- 同一 `landlord_id` **同时最多 1 条**「未吊销且未过期」记录：签发新 token 前，将旧记录 `revoked_at = now()`（在事务内完成）。  
- 明文 token **仅**出现在邮件与一次性 URL 中；服务端校验比对 `hash(plain)`。

### 3.3 与租户停用的关系

- 当 `Tenant` 为**非活跃**（与现有 `Tenant::isActive()` 语义一致：非 trial/active）时：**禁止**魔法链接兑换；房东已有 session 应被中间件拒绝（与 `EnsureTenantIsActiveForSaas` 同模式：`LandlordPanel` 增加 `EnsureLandlordTenantIsActive` 或等价中间件）。  
- **自动续发邮件**：租户非活跃时 **停止** 为该租户下房东生成新 token（避免骚扰）。

---

## 4. 魔法链接与登录流程

### 4.1 URL 与兑换

- 公开路由示例：`GET /landlord-portal/login/{plainToken}`。  
- 校验：`token_hash` 匹配、未吊销、`expires_at > now()`、`Landlord.status=active`、`Tenant` 活跃。  
- 成功：`landlord` guard **login** → `session()->regenerate()` → 302 至面板首页。  
- 失败：统一错误页/文案，**不泄露** token 是否曾存在。

### 4.2 使用次数

- **默认**：有效期内链接 **可多次打开**（同一浏览器可重建 session）。若产品改为一次性消费，需另开修订 spec。

---

## 5. 过期自动续发与邮件策略

### 5.1 主路径：定时任务（E1）

- `schedule`：**每小时**（可配置，默认 60 分钟）执行命令，例如 `landlord:renew-expired-access-tokens`。  
- 扫描对象：`Landlord.status=active` 且所属 `Tenant` 活跃，且其**当前有效 token**（未吊销且未过期—若不存在则取最近一条逻辑由实现定义，**推荐**「取未吊销中 `expires_at` 最大者」）满足 `expires_at <= now()`。  
- 动作：在事务内 **吊销旧 token** → **插入新 token**（新 `expires_at`）→ **入队发送邮件**。  
- **幂等**：依赖 `renewal_email_sent_at` 与/或「仅当 `last_auto_token_email_at` 距上次自动发信 ≥ 24h」二者组合，确保同一过期边界 **不重复轰炸**（实现择一或组合，须在代码注释与测试中写清）。

### 5.2 补充：访问触发（E2，可选）

- 当房东访问 **已过期** 链接时：若判定「本次过期尚未自动续发」，可 **同步触发一次补发**（与 E1 共享幂等键，避免双发）。

### 5.3 邮件内容

- 必含：称呼、**完整新 URL**、到期时间（使用应用时区并在邮件中写明）、安全提示（勿转发、怀疑泄露联系平台管理员）。  
- 通道：Mailable + **Queue**；失败重试遵循队列配置。

### 5.4 人工重发（平台 `/admin`）

- `LandlordResource`（或等价 UI）提供 **「重发入口链接」**：吊销旧 token、生成新 token、入队邮件；**不计入**自动续发防刷计数或单独计数（实现二选一成文即可，推荐：**人工重发不受 24h 自动上限限制**，但仍审计）。

---

## 6. Filament 与权限（第一期）

### 6.1 平台 `/admin`

- **仅** `User.is_admin === true` 可 CRUD `Landlord`、重发链接、禁用房东、吊销 token。  
- **推荐**：独立 `LandlordResource`（列表支持按 `tenant` 筛选）；创建时必选 `tenant`。

### 6.2 租户 `/tenant-admin`

- **本期不做**房东创建/管理 UI（避免与仅有 `owner` 角色的权限模型并行分叉）。

### 6.3 房东 `/landlord-portal`

- 新 `LandlordPanelProvider`：`authGuard('landlord')`、`->path('landlord-portal')`；**不提供**自助注册。  
- 登录页：可选极简说明页；主入口为魔法链接路由。  
- 首期页面：**Dashboard 占位** +（可选）只读展示所属 `Tenant.name` 与房东基础信息。

---

## 7. 审计（建议最小集合）

- 记录平台 `User`：`landlord.created`、`landlord.disabled`、`landlord.token_revoked`、`landlord.token_resent`（subject 指向 `Landlord`）。  
- 系统自动续发：可选 `landlord.token_auto_renewed`（actor 标记为 `system` 或省略 actor，二选一成文）；**第一期允许从简**，但人工动作建议必须有审计。

---

## 8. 测试与验收标准（第一期）

### 8.1 自动化测试（最低集）

- 有效 token 兑换 → `landlord` 已认证，可访问 dashboard。  
- 过期 / 吊销 / disabled / 租户非活跃 → 兑换失败。  
- 轮换：新 token 生效后旧 token 校验失败。  
- 调度：`Mail::fake` / 队列断言 + 幂等（同一过期边界不重复发）。  
- Policy：非 admin 不可管理 `Landlord`。

### 8.2 验收标准（完成定义）

1. 平台管理员在 `/admin` 可为指定 `Tenant` **创建房东**，并收到含 **72h 有效** 入口链接的邮件（队列成功）。  
2. 房东仅能通过该链接体系进入 `/landlord-portal`，**无法**进入 `/admin` 与 `/tenant-admin`。  
3. token 过期后，在调度周期内 **自动轮换并邮件发送新链接**，且满足 **幂等与防刷**（见 §5.1）。  
4. 禁用房东后，旧 token **立即不可用**，无法建立新 session。  
5. `Tenant` 非活跃后，房东 **无法继续访问**（兑换与会话均被拒绝）。

---

## 9. 第二期挂起项（不在本期实现）

- 平台直挂个人房东（无 `tenant_id` 分支）。  
- `SaasUser`（含未来 `staff` 角色）自助管理旗下房东。  
- 房源、租约、租客、支付、合同与复杂定价。

---

## 10. Spec 自检（占位 / 一致性 / 范围 / 歧义）

| 检查项 | 结论 |
|--------|------|
| 占位符 | 默认调度 **每小时**、防刷 **24h**、路径 **`/landlord-portal`** 已写死；若需改配置，通过 `config` + env 覆盖并在实现计划中列明。 |
| 与 SaaS spec 一致性 | 三面板与 guard 隔离与既有双面板 spec 不冲突；房东域新增表不修改 `users`/`saas_users` 语义。 |
| 范围 | 本期不含房源/租约；不含 `SaasUser` 侧房东管理。 |
| 歧义 | 「幂等键」允许 `renewal_email_sent_at` 与 `last_auto_token_email_at` 组合实现；实现须在单测中固定一种策略并文档化。 |

---

## 11. 下一步

请你 **通读本文件** 并回复是否需要修订。确认无修改意见后，进入 **writing-plans**：输出 `docs/superpowers/plans/2026-05-12-rental-landlord-b2b2c-implementation.md`（或同日期的计划文件名），再按任务拆分开发与测试。
