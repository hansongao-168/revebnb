# Listings, availability blocks, and bookings (three panels) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend `listings` with landlord ownership, min nights, max guests, rich HTML descriptions, guest info HTML, and a gallery; add `listing_unavailability_blocks` (platform + landlord only) and `bookings` with shared conflict rules; expose CRUD on `/admin`, `/tenant-admin`, and `/landlord-portal` per the spec matrix, using one domain service for night math and validation.

**Architecture:** New tables `listing_images`, `listing_unavailability_blocks`, `bookings`; remove `cover_image` after backfill to `listing_images`; `App\Services\BookingAvailabilityService` centralizes half-open booking nights vs inclusive block nights and throws `ValidationException` with Chinese messages; `App\Services\RichTextSanitizerService` wraps `Symfony\Component\HtmlSanitizer\HtmlSanitizer` with `HtmlSanitizerConfig::allowSafeElements()` (already present in `vendor/` — no new Composer dependency required). Filament: extend platform `ListingForm` / pages; add `App\Filament\Tenant\Resources\*` and `App\Filament\Landlord\Resources\*` mirroring patterns from `app/Filament/Resources/Listings/`; add `->discoverResources(...)` to `LandlordPanelProvider` (tenant panel already discovers). Policies follow `ListingPolicy` (`User::is_admin` on admin; tenant/landlord use `viewAny`/`view`/`update` scoped by `tenant_id` / `landlord_id`).

**Tech Stack:** Laravel 13, Filament 5 (`Filament\Schemas\Schema`), PHPUnit 12, Livewire tests, Symfony HtmlSanitizer, Carbon.

**Spec:** `docs/superpowers/specs/2026-05-13-listings-availability-bookings-design.md`

---

## File map（创建 / 修改）

| 路径 | 职责 |
|------|------|
| `database/migrations/2026_05_14_100000_add_listing_extended_fields.php` | `landlord_id` nullable FK, `min_nights`, `max_guests`, `guest_info_html`; widen `description` 若仍为 `text` 可保持 |
| `database/migrations/2026_05_14_100001_create_listing_images_table.php` | 图集表 |
| `database/migrations/2026_05_14_100002_migrate_cover_images_to_listing_images.php` | 将 `cover_image` 路径写入 `listing_images` 后 `update listings set cover_image=null` |
| `database/migrations/2026_05_14_100003_drop_cover_image_from_listings.php` | `dropColumn('cover_image')` |
| `database/migrations/2026_05_14_100004_create_listing_unavailability_blocks_table.php` | 手动不可租 |
| `database/migrations/2026_05_14_100005_create_bookings_table.php` | 订单 |
| `app/Enums/BookingStatus.php` | `Draft`/`Pending`/`Confirmed`/`Cancelled` backed string |
| `app/Enums/UnavailabilityBlockCreator.php` | `Platform` / `Landlord` |
| `app/Models/ListingImage.php` | `belongsTo` Listing |
| `app/Models/ListingUnavailabilityBlock.php` | `belongsTo` Listing, Landlord (nullable) |
| `app/Models/Booking.php` | `belongsTo` Listing；casts dates + status |
| `app/Models/Listing.php` | `fillable`/relations/`landlord()`/`images()` |
| `app/Models/Landlord.php` | `listings()` hasMany |
| `database/factories/ListingImageFactory.php` | 可选；或测试中手工创建 |
| `database/factories/ListingUnavailabilityBlockFactory.php` | 块工厂 |
| `database/factories/BookingFactory.php` | 订单工厂 |
| `database/factories/ListingFactory.php` | 增加 `landlord_id`、`min_nights`、`max_guests`；移除 `cover_image` |
| `app/Services/RichTextSanitizerService.php` | `sanitize(?string $html): ?string` |
| `app/Services/BookingAvailabilityService.php` | 夜次展开 + 校验 API |
| `app/Policies/BookingPolicy.php` | admin / tenant / landlord 作用域 |
| `app/Policies/ListingUnavailabilityBlockPolicy.php` | 仅 admin + 对应房东 |
| `app/Policies/ListingPolicy.php` | 扩展 `view`/`update`/`create` 对租户与房东（若平台全量保持 `is_admin`） |
| `app/Filament/Resources/Listings/Schemas/ListingForm.php` | Repeater 图集、RichEditor、`landlord_id` Select、`min_nights`、`max_guests` |
| `app/Filament/Resources/Listings/Tables/ListingsTable.php` | 列：房东、min nights、封面缩略图来自首图 |
| `app/Filament/Resources/Listings/Pages/CreateListing.php` / `EditListing.php` | `mutateFormDataUsing` / `afterSave` 调 sanitizer + availability（订单在别 Resource） |
| `app/Filament/Resources/ListingUnavailabilityBlocks/*` | 平台 Resource（全量） |
| `app/Filament/Resources/Bookings/*` | 平台 Resource |
| `app/Filament/Tenant/Resources/Listings/*` | 租户房源 |
| `app/Filament/Tenant/Resources/Bookings/*` | 租户订单 |
| `app/Filament/Landlord/Resources/Listings/*` | 房东房源 |
| `app/Filament/Landlord/Resources/Bookings/*` | 房东订单 |
| `app/Filament/Landlord/Resources/ListingUnavailabilityBlocks/*` | 房东不可租 |
| `app/Providers/Filament/LandlordPanelProvider.php` | `discoverResources` + `discoverPages` |
| `tests/Unit/BookingAvailabilityServiceTest.php` | 夜次与交集单测 |
| `tests/Feature/ListingAdminTest.php` | 更新为带 Landlord + 图集 + 富文本 |
| `tests/Feature/BookingConflictTest.php` | 订单 vs 订单、订单 vs 块、块 vs 订单 |
| `tests/Feature/TenantListingBookingTest.php` | SaasUser 作用域 + 无块入口 |
| `tests/Feature/LandlordListingBookingTest.php` | Landlord 仅本人 + 块 CRUD |
| `.cursor/skills/rental-listings-system/SKILL.md` | 更新 Current scope 以反映新表与三面板（文档维护） |

---

### Task 1: Migration — `listings` 扩展列

**Files:**
- Create: `database/migrations/2026_05_14_100000_add_listing_extended_fields.php`

- [ ] **Step 1: 编写 migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('landlord_id')->nullable()->after('tenant_id')->constrained('landlords')->nullOnDelete();
            $table->unsignedSmallInteger('min_nights')->default(1)->after('currency');
            $table->unsignedSmallInteger('max_guests')->nullable()->after('min_nights');
            $table->longText('guest_info_html')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
            $table->dropColumn(['min_nights', 'max_guests', 'guest_info_html']);
        });
    }
};
```

- [ ] **Step 2: 运行迁移**

Run: `php artisan migrate --no-interaction --path=database/migrations/2026_05_14_100000_add_listing_extended_fields.php`  
Expected: `DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_14_100000_add_listing_extended_fields.php
git commit -m "feat(listings): add landlord_id min_nights max_guests guest_info_html"
```

---

### Task 2: Migration — `listing_images`

**Files:**
- Create: `database/migrations/2026_05_14_100001_create_listing_images_table.php`

- [ ] **Step 1: 编写 migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['listing_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_images');
    }
};
```

- [ ] **Step 2: 迁移并提交**

Run: `php artisan migrate --no-interaction --path=database/migrations/2026_05_14_100001_create_listing_images_table.php`  
Then: `git add database/migrations/2026_05_14_100001_create_listing_images_table.php && git commit -m "feat(listings): create listing_images table"`

---

### Task 3: Migration — 回填 `cover_image` 到图集

**Files:**
- Create: `database/migrations/2026_05_14_100002_migrate_cover_images_to_listing_images.php`

- [ ] **Step 1: 编写 data migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('listings')->whereNotNull('cover_image')->get(['id', 'cover_image']);
        foreach ($rows as $row) {
            DB::table('listing_images')->insert([
                'listing_id' => $row->id,
                'path' => $row->cover_image,
                'sort_order' => 0,
                'is_cover' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('listing_images')->truncate();
    }
};
```

- [ ] **Step 2: 迁移**

Run: `php artisan migrate --no-interaction --path=database/migrations/2026_05_14_100002_migrate_cover_images_to_listing_images.php`

- [ ] **Step 3: Commit** `git commit -am "data(listings): backfill cover_image into listing_images"`

---

### Task 4: Migration — 删除 `cover_image`

**Files:**
- Create: `database/migrations/2026_05_14_100003_drop_cover_image_from_listings.php`

- [ ] **Step 1: migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('cover_image')->nullable();
        });
    }
};
```

- [ ] **Step 2: migrate + commit** `feat(listings): drop cover_image column`

---

### Task 5: Migration — `listing_unavailability_blocks`

**Files:**
- Create: `database/migrations/2026_05_14_100004_create_listing_unavailability_blocks_table.php`

- [ ] **Step 1: migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_unavailability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason', 500)->nullable();
            $table->string('created_by_type', 32);
            $table->foreignId('created_by_landlord_id')->nullable()->constrained('landlords')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_unavailability_blocks');
    }
};
```

- [ ] **Step 2: migrate + commit**

---

### Task 6: Migration — `bookings`

**Files:**
- Create: `database/migrations/2026_05_14_100005_create_bookings_table.php`

- [ ] **Step 1: migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->string('status', 32)->default('draft');
            $table->string('guest_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
```

- [ ] **Step 2: migrate + commit**

---

### Task 7: Enums + Models + `Listing` / `Landlord` 关联

**Files:**
- Create: `app/Enums/BookingStatus.php`
- Create: `app/Enums/UnavailabilityBlockCreator.php`
- Create: `app/Models/ListingImage.php`
- Create: `app/Models/ListingUnavailabilityBlock.php`
- Create: `app/Models/Booking.php`
- Modify: `app/Models/Listing.php`
- Modify: `app/Models/Landlord.php`

- [ ] **Step 1: `BookingStatus` enum**

```php
<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

- [ ] **Step 2: `UnavailabilityBlockCreator` enum**

```php
<?php

namespace App\Enums;

enum UnavailabilityBlockCreator: string
{
    case Platform = 'platform';
    case Landlord = 'landlord';
}
```

- [ ] **Step 3: `ListingImage` model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingImage extends Model
{
    protected $fillable = ['listing_id', 'path', 'sort_order', 'is_cover'];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
```

- [ ] **Step 4: `Booking` model** — `protected $casts = ['check_in' => 'date', 'check_out' => 'date', 'status' => BookingStatus::class];` 与 `listing()` belongsTo。

- [ ] **Step 5: `ListingUnavailabilityBlock` model** — casts `starts_on`/`ends_on` 为 `date`；`created_by_type` 为 `UnavailabilityBlockCreator::class`；`listing()`、`creatorLandlord()` optional belongsTo。

- [ ] **Step 6: 更新 `Listing`** — `images()` hasMany、`bookings()` hasMany、`unavailabilityBlocks()` hasMany、`landlord()` belongsTo；`$fillable` 加入新字段并移除 `cover_image`。

- [ ] **Step 7: `Landlord` 增加 `listings()` hasMany**

- [ ] **Step 8: 运行 `php artisan test --compact --filter=ListingAdminTest`（预期失败直至表单更新）**

- [ ] **Step 9: Commit** `feat(domain): add booking and listing image models`

---

### Task 8: `RichTextSanitizerService`

**Files:**
- Create: `app/Services/RichTextSanitizerService.php`

- [ ] **Step 1: 实现服务**

```php
<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichTextSanitizerService
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)->allowSafeElements();
        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return $this->sanitizer->sanitize($html);
    }
}
```

- [ ] **Step 2: 单测 `tests/Unit/RichTextSanitizerServiceTest.php`** — 断言 `<script>` 被移除、`<p>` 保留。

- [ ] **Step 3:** `php artisan test --compact tests/Unit/RichTextSanitizerServiceTest.php` 然后 commit。

---

### Task 9: `BookingAvailabilityService` + 单元测试

**Files:**
- Create: `app/Services/BookingAvailabilityService.php`
- Create: `tests/Unit/BookingAvailabilityServiceTest.php`

- [ ] **Step 1: 实现核心方法（完整类）**

```php
<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    /** @return list<string> */
    public function bookingNightsInclusiveHalfOpen(Carbon $checkIn, Carbon $checkOut): array
    {
        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            return [];
        }
        $nights = [];
        $cursor = $checkIn->copy()->startOfDay();
        $endExclusive = $checkOut->copy()->startOfDay();
        while ($cursor->lt($endExclusive)) {
            $nights[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $nights;
    }

    /** @return list<string> */
    public function blockNightsInclusiveClosed(Carbon $startsOn, Carbon $endsOn): array
    {
        $nights = [];
        $cursor = $startsOn->copy()->startOfDay();
        $last = $endsOn->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $nights[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $nights;
    }

    /** @return array<string, true> */
    public function otherConfirmedNightSet(int $listingId, ?int $ignoreBookingId): array
    {
        $q = Booking::query()
            ->where('listing_id', $listingId)
            ->where('status', BookingStatus::Confirmed);
        if ($ignoreBookingId !== null) {
            $q->where('id', '!=', $ignoreBookingId);
        }
        $set = [];
        foreach ($q->cursor() as $booking) {
            foreach ($this->bookingNightsInclusiveHalfOpen(
                Carbon::parse($booking->check_in),
                Carbon::parse($booking->check_out),
            ) as $d) {
                $set[$d] = true;
            }
        }

        return $set;
    }

    /** @return array<string, true> */
    public function blockNightSet(int $listingId): array
    {
        $set = [];
        foreach (ListingUnavailabilityBlock::query()->where('listing_id', $listingId)->cursor() as $block) {
            foreach ($this->blockNightsInclusiveClosed(
                Carbon::parse($block->starts_on),
                Carbon::parse($block->ends_on),
            ) as $d) {
                $set[$d] = true;
            }
        }

        return $set;
    }

    public function assertMinNightsMet(Listing $listing, Carbon $checkIn, Carbon $checkOut): void
    {
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < $listing->min_nights) {
            throw ValidationException::withMessages([
                'check_out' => "入住天数至少为 {$listing->min_nights} 晚。",
            ]);
        }
    }

    public function assertBookingAllowed(Booking $booking, ?int $ignoreBookingId = null): void
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            return;
        }

        $listing = $booking->listing;
        if (! $listing instanceof Listing) {
            $listing = Listing::query()->findOrFail($booking->listing_id);
        }

        $this->assertMinNightsMet($listing, Carbon::parse($booking->check_in), Carbon::parse($booking->check_out));

        $candidate = $this->bookingNightsInclusiveHalfOpen(
            Carbon::parse($booking->check_in),
            Carbon::parse($booking->check_out),
        );
        $ignoreId = $ignoreBookingId ?? ($booking->exists ? $booking->id : null);
        $other = $this->otherConfirmedNightSet($booking->listing_id, $ignoreId);
        $blocks = $this->blockNightSet($booking->listing_id);

        foreach ($candidate as $d) {
            if (isset($other[$d])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期与已有已确认订单冲突。',
                ]);
            }
            if (isset($blocks[$d])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期落在手动不可租区间内。',
                ]);
            }
        }
    }

    public function assertUnavailabilityBlockAllowed(ListingUnavailabilityBlock $block, ?int $ignoreBlockId = null): void
    {
        $nights = $this->blockNightsInclusiveClosed(
            Carbon::parse($block->starts_on),
            Carbon::parse($block->ends_on),
        );
        $confirmed = $this->otherConfirmedNightSet($block->listing_id, null);
        foreach ($nights as $d) {
            if (isset($confirmed[$d])) {
                throw ValidationException::withMessages([
                    'starts_on' => '不可租区间与已确认订单冲突。',
                ]);
            }
        }
    }
}
```

- [ ] **Step 2: 单元测试** — 用纯 Carbon 断言 `bookingNightsInclusiveHalfOpen(1日,4日)` 为 `[1,2,3]`；`blockNightsInclusiveClosed` 同日为单天；交集逻辑用内存模型或 SQLite in-memory `Booking` / `Block` 行（`RefreshDatabase` 在 Unit 中可选；Feature 更全）。

- [ ] **Step 3:** `php artisan test --compact tests/Unit/BookingAvailabilityServiceTest.php`

- [ ] **Step 4: Commit**

---

### Task 10: Policies

**Files:**
- Modify: `app/Policies/ListingPolicy.php`
- Create: `app/Policies/BookingPolicy.php`
- Create: `app/Policies/ListingUnavailabilityBlockPolicy.php`

- [ ] **Step 1: `ListingPolicy`** — `User`：`is_admin` 全 true。对 `SaasUser`：在 `view`/`update`/`delete` 中 **不可** 用 `User` type-hint；Laravel 默认 Policy 仅接收 `User`。因此 **拆分为** `ListingPolicy` 仅平台 `User`，另建 `TenantListingPolicy` **或** 在 Resource 层 `->authorize()` / `static::canViewAny()` 使用 `Gate::forUser($saasUser)`。实现选：**Resource 基类方法** `isListingScoped($record)` 配合 Filament `->visible(fn () => ...)` 与 `getEloquentQuery()` 过滤；Policy 保持仅 `User` 时，租户与房东用 **Filament Resource 的 `getEloquentQuery` + `mutateFormDataBeforeCreate`** 强制租户/房东，不扩展 Policy 到多 guard（避免 Laravel 不调用）。**更简单且符合现有仓库：** 为 `SaasUser` 注册 `Gate::before` 不推荐；使用 **`Filament\Models\Contracts\Authorizable` 不在此**。推荐做法：**`app/Policies/ListingPolicy.php` 继续只服务 `User`**；租户与房东 Resource 内 `public static function canAccess(): bool` 与 `getEloquentQuery()` 限制，**并** 增加 Feature 测试断言 query 范围。若团队坚持 Policy：新增 `app/Policies\Concerns\AssertTenantOwnsListing` 并在 `AuthServiceProvider` 用 `Gate::policy` 无法绑定多 model。**定案：平台用 Policy；租户/房东用 Resource `getEloquentQuery` + Form `landlord_id` disabled/hidden for landlord create。** 在计划中写明并在 `ListingPolicy` PHPDoc 注释「仅 web User」。

- [ ] **Step 2: `BookingPolicy`** — 同上，仅 `User::is_admin`；租户/房东用 query scope。

- [ ] **Step 3: `ListingUnavailabilityBlockPolicy`** — `view/create/update/delete`：`User::is_admin` **或**（`$user instanceof Landlord` 且 `$block->listing->landlord_id === $user->id`）。需在 `AuthServiceProvider` 注册 `Landlord` 的 gate 回调 **或** 在 Block Resource 使用 `Landlord` 自定义 authorization。Filament v5 Landlord guard 下 `authorize()` 传入 `Landlord`：**使用 `php artisan make:policy ListingUnavailabilityBlockPolicy --model=ListingUnavailabilityBlock` 并手动在 policy 方法里 `mixed $user`**：

```php
public function create($user): bool
{
    return $user instanceof \App\Models\User && $user->is_admin
        || $user instanceof \App\Models\Landlord;
}
```

（平台与房东可建；租户 Resource 不注册 Block，故租户进不来。）

- [ ] **Step 4: Commit** `feat(auth): add booking and unavailability policies`

---

### Task 11: 平台 `ListingForm` + Pages（富文本、图集、房东）

**Files:**
- Modify: `app/Filament/Resources/Listings/Schemas/ListingForm.php`
- Modify: `app/Filament/Resources/Listings/Pages/CreateListing.php`
- Modify: `app/Filament/Resources/Listings/Pages/EditListing.php`
- Modify: `database/factories/ListingFactory.php`

- [ ] **Step 1: `ListingForm`** — 移除 `FileUpload::make('cover_image')`；添加 `Select::make('landlord_id')->label('房东')->relationship('landlord', 'name', modifyQueryUsing: fn ($q, Get $get) => $q->where('tenant_id', $get('tenant_id')))->searchable()->preload()->required()`（当 `tenant_id` null 时 landlord query 空，平台可先选租户再选房东，或 landlord 不 required 若 tenant null — 与 spec「新建须 landlord」：若 `tenant_id` 为空则 `landlord_id` 也应空，表单规则 `required_with:tenant_id`）；`TextInput::make('min_nights')->integer()->minValue(1)->default(1)`；`TextInput::make('max_guests')->integer()->minValue(1)->nullable()`；`RichEditor::make('description')`、`RichEditor::make('guest_info_html')`；`Repeater::make('images')->relationship()->schema([FileUpload::make('path')->disk('public')->directory('listings')->visibility('public'), TextInput::make('sort_order')->numeric()->default(0), Toggle::make('is_cover')])`（Repeater relationship 名与模型 `images()` 对齐）。

- [ ] **Step 2: `CreateListing` / `EditListing`** — `mutateFormDataBeforeSave` 或 `afterStateUpdated` 对 `description`/`guest_info_html` 调 `app(RichTextSanitizerService::class)->sanitize`；保存后 **规范化封面**：至多一条 `is_cover=true`，否则取最小 `sort_order`。

- [ ] **Step 3: 更新 `ListingFactory`** — `forLandlord(Landlord $l)` state 设置 `tenant_id`/`landlord_id`/`min_nights`。

- [ ] **Step 4: 更新 `tests/Feature/ListingAdminTest.php`** — 创建 `Tenant`+`Landlord`，`actingAs($admin)`，`fillForm` 含 `landlord_id`、`min_nights`、`images` repeater 可空数组。

- [ ] **Step 5:** `php artisan test --compact tests/Feature/ListingAdminTest.php`

- [ ] **Step 6: Pint** `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

---

### Task 12: 平台 `ListingUnavailabilityBlockResource`

**Files:**
- Create under `app/Filament/Resources/ListingUnavailabilityBlocks/`：`ListingUnavailabilityBlockResource.php`、`Schemas/ListingUnavailabilityBlockForm.php`、`Tables/ListingUnavailabilityBlocksTable.php`、`Pages/*`

- [ ] **Step 1:** `getEloquentQuery()` 无租户过滤（全量）。`CreateRecord::mutateFormDataBeforeCreate` 设置 `created_by_type = platform`，`created_by_landlord_id = null`。

- [ ] **Step 2:** `beforeCreate` / `beforeSave` 调 `BookingAvailabilityService::assertUnavailabilityBlockAllowed`。

- [ ] **Step 3:** 导航组放 `租房`。

- [ ] **Step 4: Feature test** 管理员创建块成功；与已确认订单冲突时失败。

- [ ] **Step 5: Commit**

---

### Task 13: 平台 `BookingResource`

**Files:**
- Create `app/Filament/Resources/Bookings/*`

- [ ] **Step 1:** Form：`Select::listing` relationship scoped 全量；`DatePicker check_in/check_out`；`Select status` enum；保存前若 `confirmed` 调 `assertBookingAllowed`。

- [ ] **Step 2: Feature test** 两条 confirmed 重叠失败。

- [ ] **Step 3: Commit**

---

### Task 14: 租户面板 `ListingResource` + `BookingResource`

**Files:**
- Create: `app/Filament/Tenant/Resources/Listings/*`
- Create: `app/Filament/Tenant/Resources/Bookings/*`

- [ ] **Step 1:** `getEloquentQuery`：`where('tenant_id', auth('saas')->user()->tenant_id)`；`ListingForm` 复用：可抽 `App\Filament\Support\TenantListingForm::configure(Schema $schema): Schema` 从平台拷贝并删「手动不可租」；房东选择：租户可见本租户全部 `Landlord` select。

- [ ] **Step 2:** **不** 注册 `ListingUnavailabilityBlockResource`。

- [ ] **Step 3:** `tests/Feature/TenantListingBookingTest.php` — `SaasUser::factory()` + `Livewire::test(Tenant\Resources\Listings\Pages\CreateListing::class)`（路径以实际 `make:filament-resource` 为准）。

- [ ] **Step 4: Commit**

---

### Task 15: 房东面板 discovery + `ListingResource` + `BookingResource` + `ListingUnavailabilityBlockResource`

**Files:**
- Modify: `app/Providers/Filament/LandlordPanelProvider.php` — 追加：

```php
->discoverResources(in: app_path('Filament/Landlord/Resources'), for: 'App\\Filament\\Landlord\\Resources')
->discoverPages(in: app_path('Filament/Landlord/Pages'), for: 'App\\Filament\\Landlord\\Pages')
```

- [ ] **Step 1:** 三个 Resource；query：`where('landlord_id', auth('landlord')->id())` 于 Listing；Booking `whereHas('listing', fn ($q) => $q->where('landlord_id', ...))`；Block 同理。

- [ ] **Step 2:** `CreateListing` 房东：`mutateFormDataBeforeCreate` 强制 `landlord_id = auth()->id()`、`tenant_id = auth()->user()->tenant_id`。

- [ ] **Step 3:** Block create：`created_by_type = landlord`，`created_by_landlord_id = auth()->id()`。

- [ ] **Step 4:** `tests/Feature/LandlordListingBookingTest.php`

- [ ] **Step 5: Commit**

---

### Task 16: `ListingsTable` 与跨面板导航文案

**Files:**
- Modify: `app/Filament/Resources/Listings/Tables/ListingsTable.php`
- 各新 Resource 的 `$navigationGroup` / `$navigationLabel` 中文一致

- [ ] **Step 1:** 列展示首图：`Image::make('images.path')` 需 `query()->with('images')` 或自定义 `stateUsing` 取第一张。

- [ ] **Step 2: Commit**

---

### Task 17: 技能文件与文档同步

**Files:**
- Modify: `.cursor/skills/rental-listings-system/SKILL.md`

- [ ] **Step 1:** 更新「Current scope」表：新表、三面板路径、手动不可租维护矩阵、`cover_image` 已移除。

- [ ] **Step 2: Commit** `docs(skills): refresh rental-listings-system scope`

---

## Self-review（对照 spec）

| Spec § | 计划覆盖 |
|--------|----------|
| 3.1 字段与封面策略 | Tasks 1–4, 7, 11, 16 |
| 3.2 listing_images | Tasks 2, 7, 11 |
| 3.3 blocks | Tasks 5, 7, 9, 12, 15 |
| 3.4 bookings | Tasks 6, 7, 9, 13–15 |
| 4 三面板矩阵 | Tasks 12–15 + LandlordProvider |
| 5 日期语义与冲突 | Task 9 + 各 Resource hooks |
| 6 Filament UX | Task 11 RichEditor + Repeater |
| 7 迁移兼容 landlord_id nullable | Task 1 + Factory/Test 补 Landlord |
| 8 测试 | Tasks 9, 11–15 Feature 清单 |
| 9 非目标 API | 未计划新增路由 |

**Placeholder scan:** 无 TBD。Policy 多 guard 处已给出定案（平台 Policy + 租户/房东 query scope）。

---

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-05-13-listings-availability-bookings-implementation.md`. Two execution options:**

1. **Subagent-Driven (recommended)** — 每个 Task 派生子代理并在 Task 间复核，迭代快  
2. **Inline Execution** — 本会话用 executing-plans 按检查点批量执行  

**Which approach?**
