# 平台后台 ICS 外部日历同步与对比 — 设计说明

**日期**: 2026-05-18  
**状态**: 已定稿（请通读本文件并确认后进入 implementation plan）  
**依赖**:

- `docs/superpowers/specs/2026-05-13-listings-availability-bookings-design.md`（`listings`、`bookings`、`listing_unavailability_blocks`、`BookingAvailabilityService`）
- 平台 Filament `ListingResource`（`/admin`）

---

## 1. 背景与目标

运营需要在 **平台 `/admin`** 为每条房源配置 **一条或多条** 外部 iCal 订阅（例如 Airbnb `…/calendar/ical/{id}.ics?t=…`），**定时或手动** 拉取 ICS，将 `VEVENT` 持久化为 **独立外部日历事件**，并在后台与 **本地已确认订单、手动不可租** 做 **对比展示**。

**第一期明确不做**：

- 将外部事件合并进 `BookingAvailabilityService::unavailableNightSetForSiteCalendar()` 或前台 `/stays` 可订 API
- 租户 `/tenant-admin`、房东 `/landlord-portal` 的 ICS 配置入口
- 向外导出 ICS、双向同步

**二期预留**（本 spec 仅数据与 night 展开方式对齐，不实现开关）：按房源或按 feed 将外部占用夜并入前台不可订计算。

---

## 2. 需求决策摘要（已确认）

| 主题 | 决策 |
|------|------|
| 数据策略 | 独立表 `external_calendar_events` + `listing_calendar_feeds`；后台对比展示 |
| 后台范围 | **仅** 平台 `/admin` |
| 同步方式 | **手动**（单 feed / 房源全部 feeds）+ **定时**（可配置间隔，全局默认 **6** 小时） |
| 第一期可订 | **不** 改 `BookingAvailabilityService`、不改 `ListingAvailabilityController` |
| 每房源订阅 | **多条** ICS（`label` + `source` 区分来源） |
| 空 ICS | 同步成功且解析结果无事件时，**删除**该 feed 下全部已有事件（可配置，默认 `true`） |

---

## 3. 数据模型

### 3.1 `listing_calendar_feeds`

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | PK | |
| `listing_id` | FK → `listings`, `cascadeOnDelete` | |
| `label` | string, max 120 | 后台显示名，如「Airbnb 主日历」 |
| `source` | string, max 64, nullable | 机器可读来源，如 `airbnb`、`booking_com` |
| `ical_url` | text | **加密**（Laravel `encrypted` cast）；含私有 token |
| `is_enabled` | boolean, default `true` | 关闭后不参与定时同步 |
| `sync_interval_hours` | unsigned smallint, nullable | 空则用 `config('calendar_feeds.default_sync_interval_hours')`（**6**） |
| `last_synced_at` | timestamp nullable | 最近一次同步尝试结束时间 |
| `last_successful_sync_at` | timestamp nullable | 最近一次 **成功** 同步 |
| `last_sync_status` | string, max 32 | `pending` / `success` / `failed` |
| `last_sync_error` | text nullable | 截断保存（如 2000 字符），不含完整 URL |
| `timestamps` | | |

**索引**: `(listing_id)`, `(is_enabled, last_synced_at)`（定时任务扫描）。

### 3.2 `external_calendar_events`

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | PK | |
| `listing_calendar_feed_id` | FK → `listing_calendar_feeds`, `cascadeOnDelete` | |
| `ical_uid` | string, max 255 | VEVENT `UID` |
| `summary` | string, max 500, nullable | |
| `starts_at` | datetime | 解析后应用时区 |
| `ends_at` | datetime | |
| `all_day` | boolean, default `false` | |
| `blocked_nights` | json | `string[]`，`Y-m-d`，按 **半开区间** `[starts_on, ends_on)` 展开（与 `BookingAvailabilityService::bookingNightsInclusiveHalfOpen` 一致） |
| `timestamps` | | |

**唯一约束**: `unique(listing_calendar_feed_id, ical_uid)`。

**说明**: `blocked_nights` 在 sync 时由服务层计算并存储，对比页与二期合并逻辑直接读 JSON，避免重复解析。

### 3.3 与现有表关系

```
Listing 1 ──* ListingCalendarFeed 1 ──* ExternalCalendarEvent
Listing 1 ──* Booking (confirmed nights)
Listing 1 ──* ListingUnavailabilityBlock (inclusive nights)
```

---

## 4. ICS 拉取与解析

### 4.1 HTTP

- `Illuminate\Support\Facades\Http` GET `ical_url`
- 超时 **30s**；仅允许 `https` scheme（`config('calendar_feeds.allow_http', false)` 供本地测试）
- User-Agent 建议 `Revebnb-CalendarSync/1.0`
- 响应 `Content-Type` 非强制校验； body 须能解析为 iCalendar

### 4.2 解析库

- 新增 Composer 依赖 **`sabre/vobject`**（实现前需 `composer require`；无合适内置替代）
- 封装 `App\Services\Ics\IcsCalendarParser`：输入 ICS 字符串 → `Collection<NormalizedIcsEvent>`（uid, summary, starts_at, ends_at, all_day）

### 4.3 日期语义

| 类型 | 处理 |
|------|------|
| `VALUE=DATE` 全天 | `starts_at` 当日 00:00，`ends_at` 为 ICS 的 DTEND（Airbnb 离店日常为 **exclusive** end）；展开 nights 用半开区间 |
| `DATE-TIME` | 转应用 `config('app.timezone')` 再取 date 边界；跨午夜按日切分后展开 |
| 缺失 UID | 跳过并记入 sync 警告计数（不 upsert） |

### 4.4 同步算法（`ExternalCalendarSyncService`）

对单个 `ListingCalendarFeed`：

1. 标记 `last_sync_status = pending`（可选，或仅 Job 内处理）
2. HTTP 拉取 → 解析事件列表
3. `DB::transaction`:
   - 对每个有效事件：`updateOrCreate` by `(feed_id, ical_uid)`，写入字段 + 重算 `blocked_nights`
   - 删除该 feed 下 **UID 不在本次解析结果中** 的行（全量对账）
   - 若解析结果为空且 `config('calendar_feeds.empty_ics_clears_events', true)`：删除该 feed 下全部事件
4. 成功：`last_sync_status=success`，`last_successful_sync_at=now()`，清空 `last_sync_error`
5. 失败：不写步骤 3 的删除（**保留上次成功快照**），`last_sync_status=failed`，写入截断错误信息

**并发**: Job 使用 `ShouldBeUnique` / `WithoutOverlapping` key `calendar-feed:{id}`。

---

## 5. 定时与队列

| 组件 | 职责 |
|------|------|
| `SyncListingCalendarFeedJob` | 单 feed 同步；`ShouldQueue`，`tries=3`，`timeout=120` |
| `calendar-feeds:sync-due` | Artisan：查询 `is_enabled=1` 且 `last_synced_at` 为空或 `<= now()->subHours(interval)` 的 feeds，`dispatch` Job |
| `routes/console.php` | `Schedule::command('calendar-feeds:sync-due')->hourly()` |

**间隔计算**: `effective_interval = feed.sync_interval_hours ?? config('calendar_feeds.default_sync_interval_hours', 6)`。

**手动同步**:

- Feed Relation Manager 行操作「立即同步」
- 编辑页 Header Action「同步全部外部日历」→ 对该房源所有 `is_enabled` feeds 依次 dispatch Job

---

## 6. 平台后台 UI（Filament）

### 6.1 Relation Manager — `ListingCalendarFeedsRelationManager`

挂载于 `App\Filament\Resources\Listings\ListingResource::getRelations()`。

- 列：`label`, `source`, `is_enabled`, `sync_interval_hours`, `last_sync_status`, `last_successful_sync_at`
- 表单：`label`（必填）, `source`, `ical_url`（Password 输入，**编辑时 placeholder「留空则不修改」**）, `is_enabled`, `sync_interval_hours`
- 行操作：立即同步
- 创建/更新后 **不** 自动同步（避免误触外网）；由运营手动或等待定时

### 6.2 自定义页 — `ViewListingCalendarComparison`

- 路由：`Listings/{record}/calendar`（`ListingResource` 注册 `calendar` page）
- 权限：与 `Listing` 的 `view` 一致（平台 admin）
- 内容：
  - 月份选择器（`Y-m`）
  - **月历网格**（只读）：图例 — 外部（按 feed 分色）/ 已确认订单 / 手动不可租
  - **事件表**：当月外部 `external_calendar_events`；可选筛选 feed
  - **差异摘要**（只读计算，不落库）：仅外部 / 仅本地 / 日期重叠（外部 night ∩ 本地 confirmed night 或 block night）

本地数据读取：

- Confirmed bookings → `BookingAvailabilityService::otherConfirmedNightSet`
- Blocks → `BookingAvailabilityService::blockNightSet`
- 外部 → 合并该 listing 下所有 feeds 的 `blocked_nights` JSON（按 feed 保留来源用于着色）

导航：房源编辑页 Header 链接「日历对比」。

---

## 7. 安全与合规

- `ical_url` **encrypted**；Filament 列表/详情 **永不** 展示完整 URL
- 日志与 `last_sync_error` **禁止** 记录完整 URL 或 query `t=` token
- Policy：`ListingCalendarFeedPolicy` — 仅 `User::is_admin`（与平台 Listing 一致）
- 可选：保存 URL 时校验 host 白名单（`config('calendar_feeds.allowed_hosts')` 默认 `null` 表示不限制；生产可设 `airbnb.com`, `airbnb.fr` 等）

---

## 8. 配置 `config/calendar_feeds.php`

```php
return [
    'default_sync_interval_hours' => 6,
    'empty_ics_clears_events' => true,
    'allow_http' => env('CALENDAR_FEED_ALLOW_HTTP', false),
    'allowed_hosts' => null, // 或 ['airbnb.com', 'airbnb.fr', ...]
    'http_timeout_seconds' => 30,
];
```

---

## 9. 错误处理

| 场景 | 行为 |
|------|------|
| HTTP 4xx/5xx | failed，保留旧事件 |
| 连接超时 | failed，保留旧事件 |
| 解析异常 | failed，保留旧事件 |
| 单条 VEVENT 无效 | 跳过，成功同步其余；可选在 `last_sync_error` 附 `skipped=N` |
| 同一 feed 并发 Job | 第二个 Job 丢弃或重试（unique lock） |

Filament：手动同步失败时 `Notification::danger()` 展示 `last_sync_error` 摘要。

---

## 10. 测试策略

| 层级 | 内容 |
|------|------|
| Unit | `IcsCalendarParser` fixture（全天、DATE-TIME、跨月、多 UID） |
| Unit | `ExternalCalendarSyncService`：upsert、删除陈旧 UID、空 ICS 清空 |
| Feature | 加密 URL 往返；`calendar-feeds:sync-due` 仅挑选到期 feed |
| Feature | HTTP::fake Airbnb 样例 ICS → events 行数与 `blocked_nights` |
| Feature | Filament admin：创建 feed、触发同步（fake HTTP） |

Fixture 文件：`tests/fixtures/ics/airbnb-sample.ics`（从真实结构脱敏，不含有效 token）。

---

## 11. 文件清单（实现期参考）

| 路径 | 职责 |
|------|------|
| `config/calendar_feeds.php` | 默认间隔、空 ICS 行为 |
| `database/migrations/*_create_listing_calendar_feeds_table.php` | |
| `database/migrations/*_create_external_calendar_events_table.php` | |
| `app/Models/ListingCalendarFeed.php` | encrypted `ical_url` |
| `app/Models/ExternalCalendarEvent.php` | |
| `app/Enums/CalendarFeedSyncStatus.php` | backed string enum |
| `app/Services/Ics/IcsCalendarParser.php` | 解析 |
| `app/Services/Ics/NormalizedIcsEvent.php` | DTO |
| `app/Services/ExternalCalendarSyncService.php` | 对账同步 |
| `app/Jobs/SyncListingCalendarFeedJob.php` | 队列 |
| `app/Console/Commands/SyncDueCalendarFeedsCommand.php` | 定时入口 |
| `app/Policies/ListingCalendarFeedPolicy.php` | admin only |
| `app/Filament/Resources/Listings/RelationManagers/ListingCalendarFeedsRelationManager.php` | |
| `app/Filament/Resources/Listings/Pages/ViewListingCalendarComparison.php` | 对比页 |
| `tests/...` | 见 §10 |

---

## 12. 二期方向（不在本 spec 实现）

- `listings.merge_external_calendar_into_availability` 或 per-feed 开关
- `BookingAvailabilityService` 增加 `externalNightSet(int $listingId)` 并在 `unavailableNightSetForSiteCalendar` 合并
- 租户/房东只读对比视图

---

## 13. 验收标准（第一期）

1. 平台 admin 可为房源添加 **≥2** 条 ICS feed（不同 `label`/`source`）。
2. 手动同步后，`external_calendar_events` 与 ICS 内容一致（UID 对账、删除已消失事件）。
3. 定时任务每小时运行，仅同步 **到期** 的 enabled feeds。
4. 「日历对比」页能按月看到外部 vs 本地三类占用，并显示差异摘要。
5. 前台 `/stays/{slug}/availability` 行为与同步前 **完全一致**（回归测试通过）。
6. ICS URL 在数据库中为密文；界面与日志不泄露 token。
