# 房源扩展、可订性、订单 — 跨三面板设计说明

**日期**: 2026-05-13  
**状态**: 已定稿（请你通读本文件并书面确认后，再进入 implementation plan）  
**依赖**:

- `docs/superpowers/specs/2026-05-12-saas-tenants-design.md`（`Tenant`、`SaasUser`、`/tenant-admin`）
- `docs/superpowers/specs/2026-05-12-rental-landlord-b2b2c-design.md`（`Landlord`、`/landlord-portal`）
- 现有 `listings` / `ListingResource`（平台 `/admin`）

---

## 1. 背景与目标

在现有 **房源**（`listings`、`nightly_price`、单封面、纯文本描述）基础上，扩展 **经营与展示字段**、**多图**、**富文本描述**、**可订性（含手动不可租）** 与 **订单（入住/离店）**，并在 **三个 Filament 面板** 中落地：

| 面板 | 路径 | 认证主体 |
|------|------|----------|
| 平台 | `/admin` | `User`（`is_admin`） |
| 租户 | `/tenant-admin` | `SaasUser`（`tenant_id`） |
| 房东 | `/landlord-portal` | `Landlord`（`tenant_id`） |

**本期不包含**：对外预订 API / UniApp 下单（需求澄清为 **Filament 内维护订单 + 冲突校验**，不强制本期交付公共 API；若后续需要，须另开 spec 并复用同一领域服务）。

---

## 2. 需求决策摘要（已确认）

| 主题 | 决策 |
|------|------|
| 单价/晚 | 沿用 `nightly_price` + `currency`；新增 **最低预定天数**、**最大接待人数**。 |
| 描述 | **富文本（HTML）** 存储；需白名单净化。 |
| 图片 | **批量多图**（独立图集表 + 排序；见 §3.2）。 |
| A 可订性 | 除 **已确认订单占房** 外，需要 **手动不可租**（维修/自住/关房等）。 |
| B 订单 | 三端可维护订单；**仅 `confirmed` 订单参与夜次占房**；新建或改为 `confirmed`、或修改已确认订单的日期时，做 **与「其他已确认订单」的冲突检测**；并与 **手动不可租** 一并校验（见 §5）。 |
| C 展示 | 在房源上增加 **面向客人的补充展示字段**（与主 `description` 区分：见 §3.1 `guest_info_html`）。 |
| 房东与房源 | **每个房源归属一个房东**：`listings.landlord_id` 必填（新建）；**房东仅能操作本人房源**；**`landlord.tenant_id` 必须等于 `listing.tenant_id`**。 |
| 手动不可租谁可改 | **仅平台 `/admin` 与房东 `/landlord-portal`**；**租户 `/tenant-admin` 无维护入口**（可选：只读展示日历，默认 **本期不做** 以降低范围）。 |
| 租户面板 | 租户员工 **照常** 维护 **房源资料 + 订单**（含冲突校验）；**不**维护手动不可租。 |

---

## 3. 数据模型

### 3.1 `listings` 扩展

| 字段 | 类型 | 说明 |
|------|------|------|
| `landlord_id` | FK → `landlords`，nullable 仅允许 **迁移过渡期**；新数据业务上视为必填 | 房东归属 |
| `min_nights` | unsigned smallint，默认 `1` | 最低预定天数（展示与下单前置校验可共用；Filament 表单校验 ≥1） |
| `max_guests` | unsigned smallint nullable | 最大接待人数；`null` 表示未限制（或产品改为必填则在实现前收紧） |
| `description` | `longText` | **HTML**（富文本编辑器输出） |
| `guest_info_html` | `longText` nullable | **C 类**：入住须知、展示用补充说明等；同样走 HTML 净化 |

**不变字段**（沿用）：`tenant_id`、`title`、`slug`、`city`、`address`、`nightly_price`、`currency`、`status`、`published_at`、`timestamps`。

**封面图策略（本期定案）**

- 引入图集表后，**以 `listing_images` 为唯一真相**；删除独立 `cover_image` 列 **或** 保留 `cover_image` 但在保存图集时同步「封面」路径（二选一）。**推荐：删除 `cover_image`，以 `sort_order` 最小且 `is_cover=true` 的一张为封面；若未设 `is_cover`，则取 `sort_order` 最小。** 若迁移成本过高可暂留 `cover_image` 只读同步，第二期再删列。

### 3.2 `listing_images`

| 字段 | 说明 |
|------|------|
| `id` | PK |
| `listing_id` | FK，`cascadeOnDelete` |
| `path` | 相对 `public` disk，目录建议 `listings/{listing_id}/` 或沿用 `listings/` + 唯一文件名 |
| `sort_order` | unsigned int，默认 0 |
| `is_cover` | bool，默认 false；同一 `listing_id` 至多一条 `true`（应用层或 DB 部分约束） |
| `timestamps` | |

### 3.3 `listing_unavailability_blocks`（手动不可租）

| 字段 | 说明 |
|------|------|
| `id` | PK |
| `listing_id` | FK |
| `starts_on` | `date` |
| `ends_on` | `date`，**与 `starts_on` 同日表示单日块** |
| `reason` | string nullable，最长建议 500 |
| `created_by_type` | enum：`platform` \| `landlord`（字符串存表即可） |
| `created_by_landlord_id` | nullable FK；`created_by_type=landlord` 时必填且须等于房源的 `landlord_id` |
| `timestamps` | |

**区间语义**：`starts_on`～`ends_on` 为 **闭区间**（两端日期当晚均不可租），与 §5 夜次换算一致。

**与已确认订单**：新建或更新块时，若与 **任意已确认订单** 所占夜次 **有交集**，**拒绝保存**（全员一致，避免「块盖住已出租夜」的语义冲突；紧急关房须先处理订单状态或日期）。

### 3.4 `bookings`

| 字段 | 说明 |
|------|------|
| `id` | PK |
| `listing_id` | FK |
| `check_in` | `date` |
| `check_out` | `date`，**离店日**；占房区间为 **半开区间** `[check_in, check_out)`（见 §5） |
| `status` | `draft` / `pending` / `confirmed` / `cancelled`（string 或 PHP enum，与现有 Listing status 风格一致即可） |
| `guest_name` | string nullable | 最小客人信息；其余字段（电话、渠道、金额）**本期 YAGNI**，后续 spec 扩展 |
| `notes` | text nullable |
| `timestamps` | |

**作用域**：不强制冗余 `tenant_id`；所有查询通过 `listing.tenant_id` / `listing.landlord_id` 做授权。若列表性能需要可再加生成的 `tenant_id` 并维护一致性（可选）。

---

## 4. 三面板职责与权限

### 4.1 矩阵

| 能力 | `/admin` | `/tenant-admin` | `/landlord-portal` |
|------|----------|-----------------|---------------------|
| 房源 CRUD | 全部租户 | `where tenant_id = auth tenant` | `where landlord_id = auth id` |
| 订单 CRUD | 全部 | 本租户房源 | 本人房源 |
| 手动不可租 | 全部房源 | **无** create/update/delete | 本人房源 |

### 4.2 Policy 要点

- **平台**：`User::is_admin`，沿用 `ListingPolicy` 模式扩展至新资源。
- **租户**：`SaasUser`；任意 `listing.tenant_id === auth->tenant_id`；**不得** 访问其他租户房源或订单。
- **房东**：`Landlord`；`listing.landlord_id === auth->id` 且租户活跃中间件已存在。
- **手动不可租**：仅 `User::is_admin` 与 **房源对应房东**；创建时写入 `created_by_*`。

### 4.3 Filament 实现组织

- **新资源**：`Tenant` 面板下 `app/Filament/Tenant/Resources/...`；`Landlord` 面板下 `app/Filament/Landlord/Resources/...`（与现有 `LandlordPanelProvider`、`TenantPanelProvider` 发现规则一致）。
- **共享**：表单 Schema 片段、订单确认校验、日期冲突与块交集逻辑放入 **服务类**（如 `BookingAvailabilityService`），三处 Resource 在 `CreateRecord` / `EditRecord` 钩子或 Form 提交中调用，**禁止复制粘贴三份不同算法**。

---

## 5. 日期与冲突规则（规范性）

### 5.1 订单占夜

- 对每条订单，**占用的宿夜日期集合**为：从 `check_in` 起，到 **`check_out` 的前一天**（含），即半开区间 **`[check_in, check_out)`** 映射到具体 `date` 的每个 night。
- **示例**：`check_in=2026-06-01`，`check_out=2026-06-04` → 占用 `06-01、06-02、06-03` 三晚；**不包含** `06-04` 当晚。

### 5.2 手动不可租块占夜

- 闭区间 `[starts_on, ends_on]` 内 **每一晚** 均不可租（含端点）。

### 5.3 冲突检测（设为 `confirmed` 或调整日期/状态）

在以下时机调用统一服务：

1. 订单状态变为 `confirmed`；
2. 已是 `confirmed` 的订单修改 `check_in` / `check_out`；
3. （可选）从 `confirmed` 降级为其他状态时释放库存，不触发与他人的冲突。

**规则**：

- **订单 vs 订单**：同一 `listing_id` 下，任意两条 `status=confirmed` 的占用夜集合 **不得相交**。
- **订单 vs 块**：`confirmed` 订单的占用夜 **不得落在任一手动不可租块的占用夜内**。
- **块 vs 订单**：保存块时，若与任意 `confirmed` 订单占用夜相交 → **验证失败**。

`draft` / `pending` / `cancelled` **不占房**（不与新 `confirmed` 冲突；产品若日后要求 `pending` 也锁房，须修订 spec）。

### 5.4 `min_nights`

- 在订单层：`check_out - check_in`（按日差）**必须 ≥ `listing.min_nights`**（在确认时校验；草稿可放宽或同样校验，实现时选 **确认时强校验**）。

---

## 6. Filament UX 要点

- **富文本**：`RichEditor`；保存前 **HTML 白名单净化**（例如 `stechstudio/filament-richtext` 自带或 `mews/purifier` / 等价方案），列表列用纯文本摘要。
- **多图**：`Repeater` + `FileUpload`（`multiple()` 或逐条 repeater），排序用 `sort_order`；封面 `Toggle` 或单选逻辑。
- **手动不可租**：独立 Resource 或 Listing 的 `RelationManager`；**租户 Panel 不注册**该 RelationManager。
- **订单**：独立 `BookingResource`（或挂在 Listing 下 RelationManager — 若记录量大推荐独立 Resource + 筛选）。

---

## 7. 数据迁移与兼容

- 为现有 `listings` 行补充 `landlord_id`：需 **数据修复策略**（例如：平台脚本为每租户指定默认房东、或仅允许在 `/admin` 批量补全后才在房东端可见）。**本期 spec 要求**：迁移加 nullable FK + 后台「未分配房东」筛选；**房东面板**仅展示 `landlord_id = 当前房东` 的房源；**新建房源**三端均须选/带 `landlord_id`（平台与租户可选任意本租户房东；房东自建时自动填自己）。

---

## 8. 测试要求（验收）

- **订单冲突**：同一房源两条 `confirmed` 重叠日期 → 第二条保存失败。
- **订单 vs 块**：存在覆盖某夜的块时，该夜不能确认订单。
- **块 vs 订单**：块与已确认订单相交 → 块保存失败。
- **越权**：租户 A 不能编辑租户 B 的 listing/booking；房东不能编辑非本人 `landlord_id` 的 listing。
- **`min_nights`**：不满足时确认失败。
- **Filament Livewire**：三面板各至少一条关键路径 smoke（随实现文件拆分）。

---

## 9. 非目标（本期不做）

- 公共 REST/UniApp **预订** API（需求为 2，非 3）。
- 租户员工维护手动不可租（含只读日历，除非后续小修订开启）。
- 动态日历定价、清洁费、押金、支付、退款。
- `pending` 锁房。

---

## 10. 修订记录

| 日期 | 说明 |
|------|------|
| 2026-05-13 | 初版：汇总头脑风暴澄清结论与推荐架构（关系型表 + 共享领域服务）。 |
