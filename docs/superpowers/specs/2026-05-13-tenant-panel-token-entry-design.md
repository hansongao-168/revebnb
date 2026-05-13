# 租户面板（`/tenant-admin`）— Token URL 入口与轮换

**日期**: 2026-05-13  
**状态**: 已定稿（经需求方口头确认；合并前请再通读本文）  
**依赖**: `docs/superpowers/specs/2026-05-12-saas-tenants-design.md`（`Tenant`、`SaasUser`、双面板、`saas` guard）

---

## 1. 背景与目标

- 当前租户后台 **仅** 支持 Filament 默认 **邮箱 + 密码** 登录（`TenantPanelProvider` + `->login()` + `saas` guard）。
- **目标**：在保留密码登录的前提下，增加 **带随机 token 的 HTTPS URL**，访问后建立与密码登录等价的 **`saas` Web 会话**，便于运营分发「长期书签式」入口（在有效期内可反复使用）。
- **平台能力**：创建 `SaasUser`（含创建租户时的 owner）时 **自动生成** 首条入口 token；平台 `/admin` 可 **手动签发**、**列表查看**、**手动吊销**（立即失效，即使未到期）。
- **过期策略**：每条 token 有 **`expires_at`**；到期后由系统 **轮换**（生成新 token + **邮件**发送新 URL）。**短信 / 企业微信**：第一期仅 **接口与配置占位**（可实现为 no-op 或写日志），第二期接具体供应商。
- **权限边界**：**仅平台 `/admin`** 可管理（签发/吊销）此类 token；**租户 `/tenant-admin` 不提供** token 吊销或列表 UI。

---

## 2. 已锁定产品决策（摘要）

| 维度 | 结论 |
|------|------|
| 密码登录 | **保留**，与 token 入口并存 |
| 链接行为 | 在 **`expires_at` 之前** 可 **反复** 用于建立会话；吊销后 **立即** 不可用 |
| 吊销 | **仅** 平台后台手动 `revoked_at` |
| 过期后通知 | 一期 **邮件** 真发；短信/企微 **二期**（一期保留 `PanelTokenNotifier` 类级扩展点） |
| 实现形态 | **专用表** 存每条 token（见 §4），**不**与 Sanctum API `personal_access_tokens` 混用 |

---

## 3. 架构总览

```text
Platform (User) /admin
    └── SaasUserResource（或关联页）管理 saas_panel_login_tokens：签发、吊销、只读列表
    └── CreateTenant / SaasUser 创建流程内：自动插入首条 token + 邮件/通知

Tenant (SaasUser) 浏览器
    └── GET /tenant-admin/entry/{plainToken}  → 校验 → saas session → 重定向面板首页
    └── 原有 GET /tenant-admin/login + 密码 → 不变
```

- **会话**：token 路由成功后在 **`saas`** guard 上 `login($saasUser)`，并 **`session()->regenerate()`**，后续走现有 Filament `Authenticate` 与 `EnsureTenantIsActiveForSaas` 等中间件链。
- **与 API token 分离**：`SaasUser` 上现有 Sanctum **不改变语义**；面板入口 token **仅** 用于 Web 会话引导，不当作 `Authorization: Bearer` 使用。

---

## 4. 数据模型

**新表名（建议）**：`saas_panel_login_tokens`（实现时若改名，全仓一致即可）。

| 字段 | 说明 |
|------|------|
| `id` | 主键 |
| `saas_user_id` | FK → `saas_users`，级联删除或随用户清理策略与现有外键约定一致 |
| `token_hash` | **仅存** `hash('sha256', $plainToken)`（或应用层统一使用的等价安全哈希）；**禁止**存明文 |
| `expires_at` | 到期时间；到期后由轮换任务处理 |
| `revoked_at` | 可空；非空表示平台手动吊销，**立即**拒绝该 token |
| `last_used_at` | 可空；每次成功换会话时更新（可选，用于审计） |
| `created_reason` | 枚举或短字符串：`owner_provision`（创建用户/租户 owner）、`manual`（平台签发）、`expiry_rotation`（过期轮换）等 |
| `created_by_user_id` | 可空，FK → `users.id`；系统自动行为可为 null |
| `note` | 可空；平台签发时可选填，便于运营备注 |
| `superseded_at` | 可空；由 **过期轮换任务** 在「已为该用户合并发放新 token」后标记，防止同一批过期行重复触发邮件 |
| `timestamps` | `created_at` / `updated_at` |

**并发条数**：同一 `SaasUser` 允许 **多条未吊销且未过期** token 并存，便于「旧书签仍有效直到各自过期」与「手动补发新链」共存。默认 **软上限 10 条**（未吊销且未过期计数）；达到上限时 **拒绝新建** 并提示平台先吊销或等待过期（**不**自动静默删除最旧记录，避免误伤仍在使用的链接）。若产品后续要改为「单活跃 token」，另开变更 spec。

**明文 token**：仅于 **创建/轮换当次** 通过（1）Filament 通知展示给平台操作员、（2）邮件正文中的完整 URL 出现；持久层仅存 hash。

---

## 5. 路由与校验

- **建议路径**：`GET /tenant-admin/entry/{token}`（`{token}` 为 URL 安全随机串，长度建议 ≥ 40 字符，与 `Str::random` / `random_bytes` 编码方案一致；实现前与 Filament 内置路由 `route:list` 核对避免冲突）。
- **校验顺序**（短路）：解析 `token` → 计算 hash 查表 → 存在且 `revoked_at` 为空 → `expires_at` > now() → `SaasUser` 启用且 `Tenant` 满足 `EnsureTenantIsActiveForSaas` 同类规则 → `login` + `session()->regenerate()` → 重定向租户 Dashboard（`intended`）。
- **失败响应**：**统一模糊文案**（避免泄露「用户存在但 token 错/过期」）；HTTP **429** 对 IP +（可选）token 前缀限流。
- **日志**：禁止记录完整明文 token；可记录 `token` 行 id 或 hash 前 8 位用于排障。

---

## 6. 生命周期

### 6.1 创建时自动生成

- **`CreateTenant`**（及未来任意创建 `SaasUser` 的单一入口，若有）：在 **同一事务或紧随其后的可靠步骤** 中插入首条 `saas_panel_login_tokens`，`created_reason = owner_provision`，`expires_at = now() + default_ttl`（**默认 TTL 建议 90 天**，来自 `config` 可覆盖）。
- **展示**：与现有「Owner 初始密码」通知并列或合并为一条通知：**含可复制完整入口 URL**（或分拆两条通知，实现时择一，须保证运营可见）。
- **邮件**：队列异步发送「租户面板入口」邮件（`Mail::queue`），失败重试策略与项目现有邮件一致。

### 6.2 平台手动签发

- `/admin` 内对指定 `SaasUser`：**签发新 token**（选 TTL、可选 `note`），生成明文一次 → Filament 通知 + 邮件（与 6.1 同模板族）。
- 若已触达 **软上限 10**，返回校验错误，不插入。

### 6.3 过期轮换

- **定时任务**（建议 `hourly`）：针对「`expires_at` < now() 且 `revoked_at` 为空 且 `superseded_at` 为空」的记录，**按 `saas_user_id` 分组**。
- **合并规则（默认）**：同一 `saas_user_id` 在一轮调度中，若存在 **一条或多条** 上述记录，则执行 **一次** 业务动作：插入 **一条** 新 token（`created_reason = expiry_rotation`）、向该用户 **email 发送一封** 含新 URL 的邮件；并将本组内所有参与轮换的旧行统一写入 **`superseded_at = now()`**（**不**写入 `revoked_at`，以区分「平台手动吊销」与「过期被轮换消化」）。
- **已手动吊销的行**（`revoked_at` 非空）：不参与轮换。
- **校验侧**：`GET /tenant-admin/entry/{token}` 仍仅以 `expires_at` / `revoked_at` / hash 为准；`superseded_at` 不影响拒绝逻辑（已过期本身即拒绝）。

### 6.4 手动吊销

- 平台操作：`revoked_at = now()`。**不**自动发送新 token（除非未来产品另行规定）。

---

## 7. 通知抽象（一期 / 二期）

- 定义接口例如 **`PanelTokenNotifier`**，方法：`sendIssued(SaasUser $user, string $url, string $context)`（签名可微调）。
- **一期实现**：`MailPanelTokenNotifier`（真实发信）。
- **二期实现**：`SmsPanelTokenNotifier`、`WeComPanelTokenNotifier` 等；一期可注册 **空实现** 或 **MultiNotifier** 仅委托 Mail。
- **配置**：`config/panel-tokens.php`（或并入现有 `config`）中保留 `sms_enabled`、`wecom_enabled` 等布尔占位，默认 `false`。

---

## 8. 安全要点

- **全站 HTTPS**；邮件与文档中禁止出现 `http://` 示例链向生产。
- **CSRF**：GET 入口 **不** 要求 CSRF token；依赖 **高熵 token + HTTPS + 限流 + 短审计窗口**。
- **Referer 泄露**：不在第三方页面嵌入该 URL；文档提示运营勿把链接贴到不可信站点。
- **会话固定**：成功登录后必须 **`session()->regenerate()`**。

---

## 9. 测试要求（验收）

- 合法 token → `saas` 已认证，可访问租户面板受保护路由。
- 过期 / 吊销 / 不存在 token → 未认证，错误文案模糊一致。
- 租户停用 / `SaasUser` 禁用 → 拒绝登录。
- 限流：超限返回 429。
- 创建租户（或 SaasUser）后：存在对应 `saas_panel_login_tokens` 行；`Mail::fake()` 断言邮件/通知行为（与项目测试风格一致）。
- 软上限：第 11 条活跃 token 创建失败。

---

## 10. 与既有 SaaS 规格的关系

- 不修改「`users` 与 `saas_users` 分 guard、分面板」的硬约束。
- **不提供**「以平台身份一键登录租户面板」；本 spec 的 token 由平台 **生成并交付给租户**，租户仍自行持有链接。
- 若未来 `SaasUser` 支持多成员，本 token 模型 **按人绑定**（每条行属于单一 `saas_user_id`），无需变更总架构。

---

## 11. 实现阶段建议

| 阶段 | 内容 |
|------|------|
| Phase A | Migration + 模型 + 签发/校验服务 + 路由 + 限流 + 特征测试 |
| Phase B | `/admin` Filament：列表/签发/吊销 + CreateTenant 集成 + 邮件模板 |
| Phase C | 调度轮换任务 + 邮件合并策略 + 配置项 default_ttl / max_active |
| Phase D（二期） | `PanelTokenNotifier` 非邮件通道 |

---

## 12. 自检（spec 质量）

- **占位**：默认 TTL、软上限、路由字面量已在正文给默认值，实现计划可引用。
- **一致性**：与「仅平台吊销」「邮件一期」「多 token」无矛盾。
- **范围**：本 spec **不**包含租户侧自助吊销、不包含短信/企微真实发送。
- **歧义消解**：吊销与过期语义分离；过期轮换用 `superseded_at` 标记已合并处理旧行；多行过期默认「每用户每调度周期一封轮换邮件」见 §6.3。
