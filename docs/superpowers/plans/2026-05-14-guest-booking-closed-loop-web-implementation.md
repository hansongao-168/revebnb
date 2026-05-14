# Guest booking closed loop (Blade Web) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship guest checkout on `/stays` with month calendar (Alpine + availability JSON), `POST /stays/{slug}/bookings`, confirmation page with flash token (no token on redirect query string), optional queued email, `GET /bookings/{id}?token=` public detail, `/me/bookings` localStorage shell, DB columns for `guest_email` / `guests` / hashed token + expiry, and PHPUnit coverage — **no payment integration**.

**Architecture:** Add `BookingAvailabilityService::unavailableNightKeysForListing()` (or equivalent) as the **single merge** of `otherConfirmedNightSet` + `blockNightSet` so site store and JSON API never drift. Extract token issuance/verification into `App\Services\GuestBookingAccessTokenService` (plaintext once, `hash('sha256', …)` at rest, `hash_equals` on read). Replace `BookingInquiryController` with `App\Http\Controllers\Site\SiteGuestBookingController` (store + confirmation + show) and `App\Http\Controllers\Site\ListingAvailabilityController` (`__invoke` JSON). Rename `StoreBookingInquiryRequest` → `StoreSiteBookingRequest` with `guest_email` optional. Queue `GuestBookingCreatedMail` only when email present.

**Tech Stack:** Laravel 13, Vite 8, Tailwind 4, Alpine.js 3.x, PHPUnit 12, Mailable + `ShouldQueue`.

**Spec:** `docs/superpowers/specs/2026-05-14-guest-booking-closed-loop-web-design.md`

---

## File map（创建 / 修改）

| 路径 | 职责 |
|------|------|
| `database/migrations/2026_05_15_000001_add_guest_fields_to_bookings_table.php` | `guest_email`, `guests`, `guest_access_token_hash`, `guest_access_token_expires_at` |
| `config/guest_booking.php` | `token_ttl_days` => `180` |
| `app/Services/BookingAvailabilityService.php` | 新增合并不可租夜晚方法；`store` 循环改用该方法（DRY） |
| `app/Services/GuestBookingAccessTokenService.php` | `issue(): array{plain, hash, expires_at}` / `verifyHash(string $plain, string $storedHash): bool` |
| `app/Models/Booking.php` | `fillable` / `casts` 更新 |
| `database/factories/BookingFactory.php` | 新列默认值（nullable） |
| `app/Http/Requests/Site/StoreSiteBookingRequest.php` | 由 `StoreBookingInquiryRequest` 重命名并加 `guest_email` 规则 |
| `app/Http/Controllers/Site/SiteGuestBookingController.php` | `store`, `confirmation`, `show` |
| `app/Http/Controllers/Site/ListingAvailabilityController.php` | `month=YYYY-MM` → JSON |
| `app/Mail/GuestBookingCreatedMail.php` | 队列邮件 |
| `resources/views/mail/guest-booking-created.blade.php` | HTML 邮件正文（中文） |
| `resources/views/site/bookings/confirmation.blade.php` | Flash token、复制链接、`localStorage` 脚本 |
| `resources/views/site/bookings/show.blade.php` | Token 校验后只读展示 |
| `resources/views/site/me/bookings.blade.php` | 壳页 + Alpine 读 `localStorage` |
| `routes/web.php` | 新路由、`POST` 路径改为 `/bookings`；删 `inquiries`；`throttle` |
| `resources/views/site/listings/show.blade.php` | 预订区改为 Alpine 月历 + 新表单字段 + 房费小计 |
| `resources/views/components/layouts/site.blade.php` | 若需 `@stack('scripts')` 再议；默认脚本走 `app.js` 已 `@vite` |
| `resources/views/components/site/header.blade.php` | 增加「我的订单」→ `route('site.me.bookings')` |
| `app/Http/Controllers/Site/ListingBrowseController.php` | `show` 传入 `availabilityMonth` 或仅依赖前端首月 `now()` — **传 `listing` 即可，月由 JS 默认当前月** |
| `package.json` / `package-lock.json` | `alpinejs` 依赖 |
| `resources/js/app.js` | `import Alpine from 'alpinejs'; Alpine.start();` + 可选 `import './site-booking-calendar.js'` |
| `resources/js/site-booking-calendar.js` | 导出注册 `Alpine.data('revebnbBookingCalendar', …)` 或内联 `document.addEventListener('alpine:init', …)` |
| `resources/css/app.css` | 若月历需少量工具类可省略 |
| `tests/Feature/SiteListingBrowseTest.php` | 断言重定向确认页、邮件、token 详情、路由路径 |
| `tests/Feature/SiteListingAvailabilityTest.php` | **新建** availability JSON |
| `tests/Unit/GuestBookingAccessTokenServiceTest.php` | **新建** hash/verify |
| `tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php` | **新建** 合并集合与 `store` 一致 |
| `app/Http/Controllers/Site/BookingInquiryController.php` | **删除**（逻辑迁至 `SiteGuestBookingController`） |
| `app/Http/Requests/Site/StoreBookingInquiryRequest.php` | **删除**（已由 `StoreSiteBookingRequest` 替代） |

---

### Task 1: Migration — `bookings` 访客字段

**Files:**
- Create: `database/migrations/2026_05_15_000001_add_guest_fields_to_bookings_table.php`

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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->unsignedSmallInteger('guests')->nullable()->after('guest_email');
            $table->char('guest_access_token_hash', 64)->nullable()->after('guests');
            $table->timestamp('guest_access_token_expires_at')->nullable()->after('guest_access_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'guest_email',
                'guests',
                'guest_access_token_hash',
                'guest_access_token_expires_at',
            ]);
        });
    }
};
```

- [ ] **Step 2: 迁移**

Run: `php artisan migrate --no-interaction --path=database/migrations/2026_05_15_000001_add_guest_fields_to_bookings_table.php`  
Expected: `DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_15_000001_add_guest_fields_to_bookings_table.php
git commit -m "feat(bookings): add guest email guests token hash expiry columns"
```

---

### Task 2: Config — token TTL

**Files:**
- Create: `config/guest_booking.php`

- [ ] **Step 1: 添加配置文件**

```php
<?php

return [
    'token_ttl_days' => (int) env('GUEST_BOOKING_TOKEN_TTL_DAYS', 180),
];
```

- [ ] **Step 2: Commit**

```bash
git add config/guest_booking.php
git commit -m "config: add guest booking token ttl"
```

---

### Task 3: `GuestBookingAccessTokenService`

**Files:**
- Create: `app/Services/GuestBookingAccessTokenService.php`
- Create: `tests/Unit/GuestBookingAccessTokenServiceTest.php`

- [ ] **Step 1: 写失败单测**

```php
<?php

namespace Tests\Unit;

use App\Services\GuestBookingAccessTokenService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestBookingAccessTokenServiceTest extends TestCase
{
    #[Test]
    public function issue_then_verify_succeeds(): void
    {
        $svc = new GuestBookingAccessTokenService;
        $issued = $svc->issue();
        $this->assertArrayHasKey('plain', $issued);
        $this->assertArrayHasKey('hash', $issued);
        $this->assertSame(64, strlen($issued['hash']));
        $this->assertTrue($svc->verifyPlainAgainstHash($issued['plain'], $issued['hash']));
    }

    #[Test]
    public function wrong_token_fails_verify(): void
    {
        $svc = new GuestBookingAccessTokenService;
        $issued = $svc->issue();
        $this->assertFalse($svc->verifyPlainAgainstHash('wrong-token-value', $issued['hash']));
    }
}
```

Run: `php artisan test --compact tests/Unit/GuestBookingAccessTokenServiceTest.php`  
Expected: **FAIL**（class 不存在）

- [ ] **Step 2: 实现服务**

```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

class GuestBookingAccessTokenService
{
    /**
     * @return array{plain: string, hash: string}
     */
    public function issue(): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
        ];
    }

    public function verifyPlainAgainstHash(string $plain, string $storedHash): bool
    {
        $expected = hash('sha256', $plain);

        return hash_equals($storedHash, $expected);
    }
}
```

Run: `php artisan test --compact tests/Unit/GuestBookingAccessTokenServiceTest.php`  
Expected: **PASS**

- [ ] **Step 3: Commit**

```bash
git add app/Services/GuestBookingAccessTokenService.php tests/Unit/GuestBookingAccessTokenServiceTest.php
git commit -m "feat(bookings): add guest access token hash service"
```

---

### Task 4: `BookingAvailabilityService` — 合并不可租夜晚（DRY）

**Files:**
- Modify: `app/Services/BookingAvailabilityService.php`
- Create: `tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php`

- [ ] **Step 1: 单测（断言合并键包含 confirmed 与 block）**

```php
<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use App\Models\Tenant;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingAvailabilityServiceUnavailableNightsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function merged_unavailable_includes_confirmed_booking_and_block(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create();

        Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-04',
            'status' => BookingStatus::Confirmed,
            'guest_name' => 'A',
        ]);

        ListingUnavailabilityBlock::query()->create([
            'listing_id' => $listing->id,
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-12',
            'reason' => 'x',
            'created_by_type' => 'landlord',
            'created_by_landlord_id' => $landlord->id,
        ]);

        $svc = new BookingAvailabilityService;
        $set = $svc->unavailableNightSetForSiteCalendar($listing->id);

        $this->assertArrayHasKey('2026-07-01', $set);
        $this->assertArrayHasKey('2026-07-02', $set);
        $this->assertArrayNotHasKey('2026-07-04', $set);
        $this->assertArrayHasKey('2026-07-10', $set);
        $this->assertArrayHasKey('2026-07-12', $set);
    }
}
```

Run: `php artisan test --compact tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php`  
Expected: **FAIL**（method 不存在）

- [ ] **Step 2: 在 Service 中新增方法（名称与单测一致）**

在 `BookingAvailabilityService` 类末尾 `}` 前加入：

```php
    /**
     * Nights unavailable for guest stays on the marketing site: confirmed bookings
     * (half-open nights) plus landlord/platform unavailability (inclusive nights).
     *
     * @return array<string, true>
     */
    public function unavailableNightSetForSiteCalendar(int $listingId): array
    {
        $confirmed = $this->otherConfirmedNightSet($listingId, null);
        $blocked = $this->blockNightSet($listingId);

        return $confirmed + $blocked;
    }
```

- [ ] **Step 3: 重构 `SiteGuestBookingController::store` 中的 foreach**（在 Task 7 写入该 controller 时一并替换）为：

```php
        $unavailable = $availability->unavailableNightSetForSiteCalendar($listing->id);

        foreach ($availability->bookingNightsInclusiveHalfOpen($checkIn, $checkOut) as $night) {
            if (isset($unavailable[$night])) {
                throw ValidationException::withMessages([
                    'check_in' => '所选日期已被预订，请尝试其他日期。',
                ]);
            }
        }
```

（在实现 `SiteGuestBookingController` 时从当前 `BookingInquiryController` 复制逻辑并替换循环。）

Run: `php artisan test --compact tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php`  
Expected: **PASS**

- [ ] **Step 4: Commit**

```bash
git add app/Services/BookingAvailabilityService.php tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php
git commit -m "feat(availability): add merged unavailable night set for site calendar"
```

---

### Task 5: FormRequest + Model + Factory

**Files:**
- Create: `app/Http/Requests/Site/StoreSiteBookingRequest.php`（内容见下）
- Delete: `app/Http/Requests/Site/StoreBookingInquiryRequest.php`
- Modify: `app/Models/Booking.php`
- Modify: `database/factories/BookingFactory.php`

- [ ] **Step 1: 新建 `StoreSiteBookingRequest`**

```php
<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['nullable', 'string', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'check_in' => '入住日期',
            'check_out' => '退房日期',
            'guests' => '旅客人数',
            'guest_name' => '姓名',
            'guest_email' => '邮箱',
            'notes' => '备注',
        ];
    }
}
```

- [ ] **Step 2: 更新 `Booking` model**

在 `$fillable` 中加入：`'guest_email', 'guests', 'guest_access_token_hash', 'guest_access_token_expires_at'`。

在 `casts()` 中加入：`'guest_access_token_expires_at' => 'datetime'`。

- [ ] **Step 3: 更新 `BookingFactory`**

在 `definition()` 中上述新键均保持 `null` 或省略（nullable）。

- [ ] **Step 4: Commit**

```bash
git add app/Http/Requests/Site/StoreSiteBookingRequest.php app/Models/Booking.php database/factories/BookingFactory.php
git rm app/Http/Requests/Site/StoreBookingInquiryRequest.php
git commit -m "refactor(site): rename booking inquiry request and extend booking model"
```

---

### Task 6: `ListingAvailabilityController` + 路由 + Feature 测试

**Files:**
- Create: `app/Http/Controllers/Site/ListingAvailabilityController.php`
- Create: `tests/Feature/SiteListingAvailabilityTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Feature 测试**

```php
<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteListingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function availability_returns_json_for_published_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'min_nights' => 2,
            'nightly_price' => 199.5,
        ]);

        Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'status' => BookingStatus::Confirmed,
            'guest_name' => 'X',
        ]);

        $response = $this->getJson(route('site.stays.availability', [
            'listing' => $listing->slug,
            'month' => '2026-08',
        ]));

        $response->assertOk()
            ->assertJsonPath('listing.slug', $listing->slug)
            ->assertJsonPath('month', '2026-08')
            ->assertJsonStructure(['blocked_nights', 'min_nights', 'max_guests', 'nightly_price']);
        $blocked = $response->json('blocked_nights');
        $this->assertContains('2026-08-10', $blocked);
        $this->assertContains('2026-08-11', $blocked);
        $this->assertNotContains('2026-08-13', $blocked);
    }

    #[Test]
    public function availability_returns_404_for_draft_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_DRAFT,
        ]);

        $this->getJson(route('site.stays.availability', [
            'listing' => $listing->slug,
            'month' => '2026-08',
        ]))->assertNotFound();
    }
}
```

Run: `php artisan test --compact tests/Feature/SiteListingAvailabilityTest.php`  
Expected: **FAIL**

- [ ] **Step 2: 实现 Controller**

```php
<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListingAvailabilityController extends Controller
{
    public function __invoke(Request $request, Listing $listing, BookingAvailabilityService $availability): JsonResponse
    {
        abort_unless($listing->status === Listing::STATUS_PUBLISHED, 404);

        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $unavailable = $availability->unavailableNightSetForSiteCalendar($listing->id);

        $blockedNights = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            if (isset($unavailable[$key])) {
                $blockedNights[] = $key;
            }
            $cursor->addDay();
        }

        return response()->json([
            'listing' => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
            ],
            'month' => $validated['month'],
            'blocked_nights' => $blockedNights,
            'min_nights' => $listing->min_nights,
            'max_guests' => $listing->max_guests,
            'nightly_price' => (string) $listing->nightly_price,
        ]);
    }
}
```

（若 `regex` 验证失败，Laravel 抛 `ValidationException`；测试里传合法 `month`。）

- [ ] **Step 3: 注册路由**（`routes/web.php` 在 `site.stays.show` 旁）

```php
Route::get('/stays/{listing:slug}/availability', ListingAvailabilityController::class)
    ->middleware(['throttle:120,1'])
    ->name('site.stays.availability');
```

- [ ] **Step 4: 跑测试**

Run: `php artisan test --compact tests/Feature/SiteListingAvailabilityTest.php`  
Expected: **PASS**

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Site/ListingAvailabilityController.php routes/web.php tests/Feature/SiteListingAvailabilityTest.php
git commit -m "feat(site): add listing availability json endpoint"
```

---

### Task 7: `SiteGuestBookingController` — store / confirmation / show

**Files:**
- Create: `app/Http/Controllers/Site/SiteGuestBookingController.php`
- Modify: `routes/web.php`
- Delete: `app/Http/Controllers/Site/BookingInquiryController.php`

- [ ] **Step 1: 实现 Controller（完整类骨架）**

要点：

- `store(StoreSiteBookingRequest $request, Listing $listing, BookingAvailabilityService $availability, GuestBookingAccessTokenService $tokens)`  
  - `abort_unless` published。  
  - `assertMinNightsMet` + `unavailableNightSetForSiteCalendar` 循环（同 Task 4）。  
  - `$issued = $tokens->issue()`；`$expires = now()->addDays(config('guest_booking.token_ttl_days'))`。  
  - `Booking::create([... 'status' => Pending, 'guest_email' => $data['guest_email'] ?? null, 'guests' => $data['guests'] ?? null, 'guest_access_token_hash' => $issued['hash'], 'guest_access_token_expires_at' => $expires, ...])`。  
  - `Mail::to(...)->queue(new GuestBookingCreatedMail(...))` **仅当** `filled('guest_email')`。  
  - `return redirect()->route('site.bookings.confirmation', $booking)->with('guest_booking_token', $issued['plain']);`

- `confirmation(Booking $booking)`：若无 `session('guest_booking_token')` → `abort(404)`。若有，**不要把 token 从 session 提前清掉**直到 Blade 渲染完（Laravel flash 本请求可读）；向视图传 `$booking`、`$plainToken = session('guest_booking_token')`、`$detailUrl = url()->query('/bookings/'.$booking->id, ['token' => $plainToken])` — **使用** `route('site.bookings.show', ['booking' => $booking, 'token' => $plainToken])` 若路由签名允许 query；Laravel `route()` 默认 path，query 用第二个参数：`route('site.bookings.show', $booking).'?token='.urlencode($plainToken)` 或 `URL::signedRoute` **不要**，spec 要求明文 token query。

- `show(Request $request, Booking $booking, GuestBookingAccessTokenService $tokens)`：  
  - `$request->validate(['token' => ['required', 'string']])`  
  - 若 `guest_access_token_expires_at` 已过 → `abort(404)`。  
  - 若 `! $tokens->verifyPlainAgainstHash($request->query('token'), (string) $booking->guest_access_token_hash)` → `abort(404)`。  
  - `load('listing')`；返回 `site.bookings.show`。

- [ ] **Step 2: 路由**

```php
use App\Http\Controllers\Site\SiteGuestBookingController;

Route::post('/stays/{listing:slug}/bookings', [SiteGuestBookingController::class, 'store'])
    ->middleware(['throttle:12,1'])
    ->name('site.bookings.store');

Route::get('/bookings/{booking}/confirmation', [SiteGuestBookingController::class, 'confirmation'])
    ->middleware(['throttle:30,1'])
    ->name('site.bookings.confirmation');

Route::get('/bookings/{booking}', [SiteGuestBookingController::class, 'show'])
    ->middleware(['throttle:60,1'])
    ->name('site.bookings.show');
```

删除旧的 `Route::post('.../inquiries', ...)` 行。

- [ ] **Step 3: 更新 Feature 测试 `SiteListingBrowseTest`**

把 `assertRedirect(route('site.stays.show', ...))` 改为 `assertRedirect` 到 `route('site.bookings.confirmation', $booking)`；`assertSessionHas('guest_booking_token')`；不再断言 `booking_inquiry_success`。

为邮件与 token 详情新增测试方法（见 Task 8 可合并）。

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php`  
Expected: **FAIL** 直到视图与 Mailable 完成 — 可先写 controller 再 Task 8 视图。

- [ ] **Step 4: Commit controller + routes + 测试调整**

```bash
git add app/Http/Controllers/Site/SiteGuestBookingController.php routes/web.php tests/Feature/SiteListingBrowseTest.php
git rm app/Http/Controllers/Site/BookingInquiryController.php
git commit -m "feat(site): guest booking store with token and confirmation redirect"
```

---

### Task 8: Mailable + Blade 视图（confirmation / show / mail）

**Files:**
- Create: `app/Mail/GuestBookingCreatedMail.php`
- Create: `resources/views/mail/guest-booking-created.blade.php`
- Create: `resources/views/site/bookings/confirmation.blade.php`
- Create: `resources/views/site/bookings/show.blade.php`
- Create: `resources/views/site/me/bookings.blade.php`
- Modify: `routes/web.php`（`GET /me/bookings`）
- Modify: `resources/views/components/site/header.blade.php`

- [ ] **Step 1: Mailable**

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestBookingCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $plainToken,
    ) {}

    public function build(): self
    {
        $url = route('site.bookings.show', [
            'booking' => $this->booking,
            'token' => $this->plainToken,
        ]);

        return $this->subject('您的 Revebnb 预订已提交')
            ->view('mail.guest-booking-created', [
                'booking' => $this->booking,
                'detailUrl' => $url,
            ]);
    }
}
```

（邮件内链接：使用 `route('site.bookings.show', ['booking' => $this->booking, 'token' => $this->plainToken])`；Laravel 会将额外键 `token` 生成为 **query string**。若环境行为不符，则退化为 `route('site.bookings.show', $this->booking).'?token='.rawurlencode($this->plainToken)`。）

- [ ] **Step 2: `confirmation.blade.php`** — 使用 `<x-layouts.site :navActive="'stays'">`；展示订单摘要；**复制链接**按钮用 `data-url`；底部隐私提示；内联 `@push` 若 layout 无 stack 则在 `layouts/site` 加 `@stack('scripts')` 并在确认页 `@push('scripts')` 写：

```blade
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const url = @json($detailUrl);
  try {
    const key = 'revebnb:guestBookings';
    const prev = JSON.parse(localStorage.getItem(key) || '[]');
    const row = {
      booking_id: @json($booking->id),
      listing_title: @json($booking->listing->title),
      check_in: @json($booking->check_in->toDateString()),
      check_out: @json($booking->check_out->toDateString()),
      detail_url: url,
    };
    const next = [row, ...prev.filter(r => r.booking_id !== row.booking_id)].slice(0, 50);
    localStorage.setItem(key, JSON.stringify(next));
  } catch (e) {}
});
</script>
@endpush
```

（`$booking->load('listing')` 在 controller 完成。）

- [ ] **Step 3: `show.blade.php`** — 只读：日期、人数、状态、房源标题链接回 `site.stays.show`。

- [ ] **Step 4: `me/bookings.blade.php`** — `x-layouts.site`；`x-data` Alpine 读 `localStorage` 列表并 `x-for`；空态提示去浏览 `/stays`。

- [ ] **Step 5: 路由 + 导航**

```php
Route::view('/me/bookings', 'site.me.bookings')
    ->middleware(['throttle:60,1'])
    ->name('site.me.bookings');
```

Header `<nav>` 增加：`<a href="{{ route('site.me.bookings') }}" class="nav-link">我的订单</a>`（`data-active` 可按需扩展，非必须）。

- [ ] **Step 6: 跑相关 Feature 测试**

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php tests/Feature/SiteListingAvailabilityTest.php`  
Expected: **PASS**

- [ ] **Step 7: Commit**

```bash
git add app/Mail resources/views/site/bookings resources/views/site/me resources/views/mail resources/views/components/site/header.blade.php routes/web.php resources/views/components/layouts/site.blade.php
git commit -m "feat(site): booking confirmation show mail and me bookings page"
```

---

### Task 9: Feature 测试 — 邮件、token 详情、确认页 404

**Files:**
- Modify: `tests/Feature/SiteListingBrowseTest.php`

- [ ] **Step 1: 新增测试方法**

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestBookingCreatedMail;

#[Test]
public function booking_store_sends_email_when_guest_email_provided(): void
{
    Mail::fake();
    // ... create $listing published ...
    $response = $this->post(route('site.bookings.store', $listing), [
        'check_in' => now()->addDays(5)->toDateString(),
        'check_out' => now()->addDays(8)->toDateString(),
        'guests' => 2,
        'guest_name' => 'Mia',
        'guest_email' => 'mia@example.com',
    ]);
    $response->assertRedirect();
    Mail::assertSent(GuestBookingCreatedMail::class);
}

#[Test]
public function booking_store_does_not_send_email_when_guest_email_missing(): void
{
    Mail::fake();
    // ...
    Mail::assertNothingSent();
}

#[Test]
public function booking_show_with_valid_token_returns_ok(): void
{
    // create booking with known hash: use model factory + manual set hash from hash('sha256', 'testtoken')
}

#[Test]
public function booking_confirmation_without_flash_returns_404(): void
{
    $booking = Booking::factory()->for($listing, 'listing')->create([...]);
    $this->get(route('site.bookings.confirmation', $booking))->assertNotFound();
}
```

（`booking_show_with_valid_token` 中生成 `$plain = 'testtoken-fixed-length-32bytes-minimum-recommended'` 并 `hash` 写入 DB。）

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php`  
Expected: **PASS**

- [ ] **Step 2: Commit**

```bash
git add tests/Feature/SiteListingBrowseTest.php
git commit -m "test(site): cover guest email mail and token booking show"
```

---

### Task 10: Alpine + 月历 + 详情页表单（`show.blade.php` + `app.js`）

**Files:**
- Modify: `package.json`（`alpinejs`）
- Modify: `resources/js/app.js`
- Create: `resources/js/site-booking-calendar.js`
- Modify: `resources/views/site/listings/show.blade.php`
- Modify: `resources/views/components/layouts/site.blade.php`（若需 `@stack('scripts')`）

- [ ] **Step 1: 安装依赖**

Run: `npm install alpinejs@^3.14.9 --save-dev`  
Expected: `package-lock.json` 更新

- [ ] **Step 2: `resources/js/app.js`**

```js
import Alpine from 'alpinejs';
import './site-booking-calendar.js';

window.Alpine = Alpine;
Alpine.start();
```

- [ ] **Step 3: `site-booking-calendar.js`** — 注册 `Alpine.data('revebnbStayBooking', (config) => ({ ... }))`：

  - `config`：`listingSlug`, `nightlyPrice`, `initialCheckIn`, `initialCheckOut`, `maxGuests`, `minNights`, `formAction`, `csrf`。  
  - 状态：`month`（`YYYY-MM`）、`checkIn`、`checkOut`、`blocked`（数组）、`loading`。  
  - `fetchMonth()`：`fetch(route('site.stays.availability', { listing: slug, month }))` — **注意**：Laravel `route()` 不在 JS；用 `data-route-template` 从 Blade 注入 base URL：`/stays/${slug}/availability?month=`。  
  - 月历：6 行×7 列或标准网格；`blocked` 包含的日期显示为 disabled；选择顺序：先 check-in 再 check-out，第三次点击重置。  
  - `nights`：`checkIn && checkOut` 时用 day diff；`total = nights * nightlyPrice`（`nightlyPrice` 用 float）。  
  - 隐藏字段或同步：两个 `<input type="hidden" name="check_in">` / `check_out` 由 Alpine 写入，**或**保留 `type="date"` 隐藏用 `x-ref` 同步 — 推荐 **hidden inputs** 与表单 native submit。

- [ ] **Step 4: Blade 根元素**

在 `price-card` 的 `<form>` 外包一层：

```blade
<div x-data="revebnbStayBooking({ ... })">
```

`listing->slug` 等从 PHP 传入。

- [ ] **Step 5: 构建与手动 smoke**

Run: `npm run build`  
Expected: `public/build` 产物更新

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php`  
Expected: **PASS**

- [ ] **Step 6: Commit**

```bash
git add package.json package-lock.json resources/js resources/views/site/listings/show.blade.php resources/views/components/layouts/site.blade.php
git commit -m "feat(site): alpine month picker and booking form on listing show"
```

---

### Task 11: Pint + 全量相关测试

**Files:**（仅格式化）

- [ ] **Step 1: Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 2: 测试**

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php tests/Feature/SiteListingAvailabilityTest.php tests/Unit/GuestBookingAccessTokenServiceTest.php tests/Unit/BookingAvailabilityServiceUnavailableNightsTest.php`

Expected: 全部 **PASS**

- [ ] **Step 3: Commit**

```bash
git commit --allow-empty -m "chore: pint guest booking closed loop" || true
```

---

## Self-review（对照 spec）

| Spec 章节 | 覆盖任务 |
|-----------|----------|
| 访客、无支付、仅房费 | Task 10 UI 小计 + 文案；无支付代码 |
| 邮箱选填 + 邮件 | Task 7–9 |
| Flash 确认页、无首跳 query token | Task 7 `with('guest_booking_token')`；Task 8 视图 |
| Token 详情 `?token=` | Task 7 `show` |
| `localStorage` key | Task 8 |
| `/me/bookings` | Task 8 |
| Availability `month=` | Task 6 |
| 路由 `POST .../bookings` | Task 7 |
| Throttle | 各路由 middleware |
| 测试矩阵 §11 | Task 3–4、6、9 |

**占位符扫描：** 无 TBD；`route()` 与 query token 已在 Task 8 说明用拼接或 `http_build_query`。

**类型一致：** `guest_access_token_hash` 长度 64 与 `hash('sha256')` 一致。

---

## Execution handoff

**Plan complete and saved to** `docs/superpowers/plans/2026-05-14-guest-booking-closed-loop-web-implementation.md`.

**Two execution options:**

1. **Subagent-Driven（推荐）** — 每个 Task 派生子代理，任务间 review，迭代快。  
2. **Inline Execution** — 本会话内按 Task 执行，使用 executing-plans 的批次与检查点。

**Which approach?**

若你在本会话直接说「按计划在主会话实现」，则视为 **Inline Execution**，从 **Task 1** 开始依次勾选完成即可。
