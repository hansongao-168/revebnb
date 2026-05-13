# Tenant panel token URL entry — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add reusable-until-expiry `GET /tenant-admin/entry/{token}` session login for `SaasUser`, backed by `saas_panel_login_tokens` (hash-only storage), platform-only Filament management on `/admin`, automatic issuance on tenant owner creation, hourly expiry rotation with one email per user per run, and `PanelTokenNotifier` abstraction (mail live; SMS/WeCom stubs).

**Architecture:** Dedicated `SaasPanelLoginToken` Eloquent model + `SaasPanelLoginTokenIssuer` (create/hash/count cap) + `TenantPanelTokenLoginController` (web route, throttle, guard login + session regenerate) + `MailPanelTokenNotifier` implementing `PanelTokenNotifier` + Artisan `panel-tokens:rotate-expired` scheduled hourly + Filament `RelationManager` on `SaasUserResource` for list/revoke/issue + `CreateTenant` calls issuer and queues mail.

**Tech stack:** Laravel 13, Filament 5, PHPUnit 12, existing `saas` guard / `SaasUser` / `Tenant` / `TenantLifecycle`.

---

## File map (create / modify)

| Path | Role |
|------|------|
| `database/migrations/*_create_saas_panel_login_tokens_table.php` | Table per spec §4 |
| `app/Models/SaasPanelLoginToken.php` | Eloquent + relations |
| `app/Models/SaasUser.php` | Add `panelLoginTokens()` hasMany |
| `database/factories/SaasPanelLoginTokenFactory.php` | Test helpers (hash from plain in `afterMaking` or state) |
| `config/panel_tokens.php` | `default_ttl_days`, `max_active_per_user`, `plain_length` |
| `app/Contracts/PanelTokenNotifier.php` | Interface |
| `app/Notifications/PanelToken/NullChannelPanelTokenNotifier.php` | No-op for future SMS/WeCom (optional) |
| `app/Services/SaasPanelLoginTokenIssuer.php` | Issue, active count, supersede batch |
| `app/Http/Controllers/TenantPanelTokenLoginController.php` | GET entry |
| `app/Mail/SaasPanelLoginTokenIssuedMail.php` | Queued mailable |
| `resources/views/mail/saas-panel-login-token-issued.blade.php` | HTML + `{{ $url }}` |
| `app/Services/MailPanelTokenNotifier.php` | Implements contract |
| `app/Providers/AppServiceProvider.php` | Bind `PanelTokenNotifier`, `RateLimiter::for('panel-token-entry', ...)` |
| `routes/web.php` | Register `GET tenant-admin/entry/{token}` before Filament if needed (same `web` stack) |
| `app/Filament/Resources/SaasUsers/RelationManagers/SaasPanelLoginTokensRelationManager.php` | Table + revoke + issue modal |
| `app/Filament/Resources/SaasUsers/SaasUserResource.php` | Register relation manager |
| `app/Filament/Resources/Tenants/Pages/CreateTenant.php` | After owner `SaasUser` create: issue token + notify + queue mail |
| `app/Console/Commands/RotateExpiredSaasPanelLoginTokens.php` | `panel-tokens:rotate-expired` |
| `routes/console.php` | `Schedule::command('panel-tokens:rotate-expired')->hourly();` |
| `app/Services/TenantLifecycle.php` | Revoke all panel tokens for each `SaasUser` on suspend (mirror Sanctum delete) |
| `tests/Feature/TenantPanelTokenEntryTest.php` | HTTP + rotation + cap + suspend |
| `tests/Feature/SaasTenantPhase1Test.php` | Assert panel token row after `CreateTenant` (extend existing test) |

---

### Task 1: Migration + `SaasPanelLoginToken` model + `SaasUser` relation

**Files:**

- Create: `database/migrations/2026_05_13_120000_create_saas_panel_login_tokens_table.php`
- Create: `app/Models/SaasPanelLoginToken.php`
- Modify: `app/Models/SaasUser.php` (add `panelLoginTokens()`)

- [ ] **Step 1: Write migration**

Run: `php artisan make:migration create_saas_panel_login_tokens_table --no-interaction`

Replace class body with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_panel_login_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('saas_user_id')->constrained('saas_users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('created_reason', 32);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_panel_login_tokens');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate --no-interaction`  
Expected: migration OK.

- [ ] **Step 3: Model `SaasPanelLoginToken`**

Create `app/Models/SaasPanelLoginToken.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPanelLoginToken extends Model
{
    public const REASON_OWNER_PROVISION = 'owner_provision';

    public const REASON_MANUAL = 'manual';

    public const REASON_EXPIRY_ROTATION = 'expiry_rotation';

    protected $fillable = [
        'saas_user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'created_reason',
        'created_by_user_id',
        'note',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SaasUser, $this> */
    public function saasUser(): BelongsTo
    {
        return $this->belongsTo(SaasUser::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
```

- [ ] **Step 4: `SaasUser::panelLoginTokens()`**

In `app/Models/SaasUser.php`, add import and method:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @return HasMany<SaasPanelLoginToken, $this> */
public function panelLoginTokens(): HasMany
{
    return $this->hasMany(SaasPanelLoginToken::class);
}
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ app/Models/SaasPanelLoginToken.php app/Models/SaasUser.php
git commit -m "feat(saas): add saas_panel_login_tokens table and model"
```

Run: `vendor/bin/pint --dirty --format agent`

---

### Task 2: Config + `SaasPanelLoginTokenIssuer`

**Files:**

- Create: `config/panel_tokens.php`
- Create: `app/Services/SaasPanelLoginTokenIssuer.php`

- [ ] **Step 1: Config file**

Create `config/panel_tokens.php`:

```php
<?php

return [
    'default_ttl_days' => (int) env('PANEL_TOKEN_DEFAULT_TTL_DAYS', 90),
    'max_active_per_user' => (int) env('PANEL_TOKEN_MAX_ACTIVE', 10),
    'plain_length' => (int) env('PANEL_TOKEN_PLAIN_LENGTH', 48),
];
```

- [ ] **Step 2: Issuer service**

Create `app/Services/SaasPanelLoginTokenIssuer.php`:

```php
<?php

namespace App\Services;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SaasPanelLoginTokenIssuer
{
    public function activeCount(SaasUser $user): int
    {
        return SaasPanelLoginToken::query()
            ->where('saas_user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();
    }

    /**
     * @return array{plain: string, token: SaasPanelLoginToken}
     */
    public function issue(
        SaasUser $user,
        string $createdReason,
        ?int $ttlDays = null,
        ?User $createdBy = null,
        ?string $note = null,
    ): array {
        $ttl = $ttlDays ?? (int) config('panel_tokens.default_ttl_days', 90);
        $max = (int) config('panel_tokens.max_active_per_user', 10);

        if ($this->activeCount($user) >= $max) {
            throw new InvalidArgumentException('该 SaaS 用户的有效入口链接已达上限，请先吊销部分链接或等待过期。');
        }

        $length = (int) config('panel_tokens.plain_length', 48);
        $plain = Str::random($length);
        $hash = hash('sha256', $plain);

        $token = SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => $hash,
            'expires_at' => now()->addDays($ttl),
            'created_reason' => $createdReason,
            'created_by_user_id' => $createdBy?->id,
            'note' => $note,
        ]);

        return ['plain' => $plain, 'token' => $token];
    }

    public function entryUrl(string $plain): string
    {
        return url('/tenant-admin/entry/'.$plain);
    }

    /**
     * @param  iterable<int>  $ids
     */
    public function markSuperseded(iterable $ids): void
    {
        SaasPanelLoginToken::query()->whereIn('id', $ids)->update(['superseded_at' => now()]);
    }
}
```

(`rotateExpiredGroup` 在 **Task 7** 实现：事务内先 `issue` 再 `markSuperseded`，并返回明文供 `MailPanelTokenNotifier` 使用。)

- [ ] **Step 3: Unit test（仅覆盖 issue / activeCount / cap）**

Create `tests/Unit/SaasPanelLoginTokenIssuerTest.php` with `RefreshDatabase`：断言 `activeCount`、第 11 次 `issue` 抛 `InvalidArgumentException`。（`rotateExpiredGroup` 的断言放在 Task 7。）

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Unit/SaasPanelLoginTokenIssuerTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add config/panel_tokens.php app/Services/SaasPanelLoginTokenIssuer.php tests/Unit/SaasPanelLoginTokenIssuerTest.php
git commit -m "feat(saas): add panel token issuer service and config"
```

Run: `vendor/bin/pint --dirty --format agent`

---

### Task 3: `PanelTokenNotifier` + Mailable + `MailPanelTokenNotifier`

**Files:**

- Create: `app/Contracts/PanelTokenNotifier.php`
- Create: `app/Mail/SaasPanelLoginTokenIssuedMail.php`
- Create: `resources/views/mail/saas-panel-login-token-issued.blade.php`
- Create: `app/Services/MailPanelTokenNotifier.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Interface**

`app/Contracts/PanelTokenNotifier.php`:

```php
<?php

namespace App\Contracts;

use App\Models\SaasUser;

interface PanelTokenNotifier
{
    /**
     * @param  non-empty-string  $context  e.g. owner_provision, manual, expiry_rotation
     */
    public function sendIssued(SaasUser $user, string $url, string $context): void;
}
```

- [ ] **Step 2: Mailable**

`app/Mail/SaasPanelLoginTokenIssuedMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\SaasUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SaasPanelLoginTokenIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SaasUser $saasUser,
        public string $url,
        public string $context,
    ) {}

    public function build(): self
    {
        return $this->subject('租户后台入口链接')
            ->markdown('mail.saas-panel-login-token-issued');
    }
}
```

- [ ] **Step 3: Blade markdown**（对齐现有 `resources/views/mail/landlord-portal-access.blade.php`：`<x-mail::message>`、按钮、`<x-mail::panel>` 提示勿转发。可增加 `expires_at` 人类可读展示。）

```blade
<x-mail::message>
# 租户后台入口

您好 {{ $saasUser->name }}，

请使用以下链接进入租户管理后台（请在有效期内使用，切勿转发给无关人员）：

<x-mail::button :url="$url">
进入后台
</x-mail::button>

如非本人操作，请忽略本邮件。

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

- [ ] **Step 4: Notifier implementation**

`app/Services/MailPanelTokenNotifier.php`:

```php
<?php

namespace App\Services;

use App\Contracts\PanelTokenNotifier;
use App\Mail\SaasPanelLoginTokenIssuedMail;
use App\Models\SaasUser;
use Illuminate\Support\Facades\Mail;

class MailPanelTokenNotifier implements PanelTokenNotifier
{
    public function sendIssued(SaasUser $user, string $url, string $context): void
    {
        Mail::to($user->email)->queue(new SaasPanelLoginTokenIssuedMail($user, $url, $context));
    }
}
```

- [ ] **Step 5: Bind in `AppServiceProvider::register`**

```php
use App\Contracts\PanelTokenNotifier;
use App\Services\MailPanelTokenNotifier;

public function register(): void
{
    $this->app->singleton(PanelTokenNotifier::class, MailPanelTokenNotifier::class);
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Contracts app/Mail app/Services/MailPanelTokenNotifier.php resources/views/mail/ app/Providers/AppServiceProvider.php
git commit -m "feat(saas): mail notifier for panel entry URL"
```

---

### Task 4: HTTP entry route + controller + throttle + feature tests

**Files:**

- Create: `app/Http/Controllers/TenantPanelTokenLoginController.php`
- Modify: `routes/web.php`
- Modify: `app/Providers/AppServiceProvider.php` (rate limiter)
- Create: `tests/Feature/TenantPanelTokenEntryTest.php`

- [ ] **Step 1: Rate limiter in `AppServiceProvider::boot`**

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('panel-token-entry', function (Request $request): Limit {
    $token = (string) $request->route('token', '');

    return Limit::perMinute(20)->by($request->ip().'|'.sha1($token));
});
```

- [ ] **Step 2: Controller**

`app/Http/Controllers/TenantPanelTokenLoginController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantPanelTokenLoginController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);

        $record = SaasPanelLoginToken::query()
            ->where('token_hash', $hash)
            ->first();

        if (! $record || $record->revoked_at !== null || $record->expires_at->isPast()) {
            return redirect()->route('filament.tenant.auth.login')
                ->with('error', '链接无效或已过期，请使用有效链接或联系平台。');
        }

        /** @var SaasUser|null $user */
        $user = $record->saasUser()->with('tenant')->first();

        if (! $user || (int) $user->status !== 1 || ! $user->tenant || ! $user->tenant->isActive()) {
            return redirect()->route('filament.tenant.auth.login')
                ->with('error', '链接无效或已过期，请使用有效链接或联系平台。');
        }

        Auth::guard('saas')->login($user, remember: false);
        $request->session()->regenerate();

        $record->forceFill(['last_used_at' => now()])->save();

        return redirect()->intended(route('filament.tenant.pages.dashboard'));
    }
}
```

- [ ] **Step 3: Route in `routes/web.php`**

```php
use App\Http\Controllers\TenantPanelTokenLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/tenant-admin/entry/{token}', TenantPanelTokenLoginController::class)
    ->middleware(['web', 'throttle:panel-token-entry'])
    ->name('tenant.panel.entry');
```

Keep existing `/` route below or above as project prefers.

- [ ] **Step 4: Failing feature test first (TDD)**

`tests/Feature/TenantPanelTokenEntryTest.php` skeleton:

```php
<?php

namespace Tests\Feature;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPanelTokenEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_logs_in_and_redirects_to_dashboard(): void
    {
        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        $plain = 'test-plain-token-'.str_repeat('a', 32);
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDay(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        $response = $this->get('/tenant-admin/entry/'.$plain);

        $response->assertRedirect(route('filament.tenant.pages.dashboard'));
        $this->assertAuthenticatedAs($user, 'saas');
    }
}
```

Add tests: invalid hash → login redirect + error flash; revoked; expired; suspended tenant; disabled saas user; assert `last_used_at` set.

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact tests/Feature/TenantPanelTokenEntryTest.php`  
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TenantPanelTokenLoginController.php routes/web.php app/Providers/AppServiceProvider.php tests/Feature/TenantPanelTokenEntryTest.php
git commit -m "feat(saas): tenant panel token URL login route"
```

---

### Task 5: `CreateTenant` + notifier + extend `SaasTenantPhase1Test`

**Files:**

- Modify: `app/Filament/Resources/Tenants/Pages/CreateTenant.php`
- Modify: `tests/Feature/SaasTenantPhase1Test.php`

- [ ] **Step 1: Inject issuer + notifier in `CreateTenant`**

After successful `SaasUser::create` (inside same transaction or immediately after commit — **prefer after transaction commits** so mail sees persisted user: use `DB::afterCommit` callback or move token creation to `afterCreate` hook):

Pattern:

```php
use App\Contracts\PanelTokenNotifier;
use App\Services\SaasPanelLoginTokenIssuer;
use Illuminate\Support\Facades\DB;

// inside transaction after SaasUser::create, capture $saasUser
// After DB::transaction returns, in overridden `afterCreate` or using `->mutateFormDataBeforeCreate` — simplest: still inside transaction, call issuer + Notification for URL; queue mail (afterCommit automatic on Mail::queue inside mailable ShouldQueue)

$issuer = app(SaasPanelLoginTokenIssuer::class);
$issued = $issuer->issue($saasUser, SaasPanelLoginToken::REASON_OWNER_PROVISION);
$url = $issuer->entryUrl($issued['plain']);
app(PanelTokenNotifier::class)->sendIssued($saasUser, $url, SaasPanelLoginToken::REASON_OWNER_PROVISION);

Notification::make()
    ->title('Owner 入口链接（请立即复制）')
    ->body($url)
    ->success()
    ->persistent()
    ->send();
```

Place alongside existing password notification (two notifications or merge into one with two bodies — two is fine per spec).

- [ ] **Step 2: Extend `test_platform_admin_can_create_tenant_with_owner`**

After assertions, load `SaasUser` by email and `assertDatabaseHas('saas_panel_login_tokens', ['saas_user_id' => $user->id])`.

Use `Mail::fake()` and `Mail::assertQueued(SaasPanelLoginTokenIssuedMail::class)`.

- [ ] **Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/SaasTenantPhase1Test.php`  
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Resources/Tenants/Pages/CreateTenant.php tests/Feature/SaasTenantPhase1Test.php
git commit -m "feat(saas): issue panel token when creating tenant owner"
```

---

### Task 6: Filament `SaasPanelLoginTokensRelationManager` (list / issue / revoke)

**Files:**

- Create: `app/Filament/Resources/SaasUsers/RelationManagers/SaasPanelLoginTokensRelationManager.php` (via artisan)
- Modify: `app/Filament/Resources/SaasUsers/SaasUserResource.php`

- [ ] **Step 1: Scaffold**

Run:

```bash
php artisan make:filament-relation-manager SaasUserResource panelLoginTokens expires_at --panel=admin --generate --no-interaction
```

Adjust table: columns `expires_at`, `revoked_at`, `superseded_at`, `created_reason`, `last_used_at`, `note`.

- [ ] **Step 2: Header action on relation manager or Edit page — Issue token**

Modal fields: `ttl_days` (Select: 7,30,90,365 default 90), `note` (Textarea nullable).

On submit: `issuer->issue(...)`, `notifier->sendIssued`, `Notification::make()->title('新入口链接')->body($url)->persistent()->success()->send()`.

- [ ] **Step 3: Row action Revoke**

`->action(fn (SaasPanelLoginToken $record) => $record->update(['revoked_at' => now()]))` with confirm; visible if `revoked_at` null.

- [ ] **Step 4: Policy**

Ensure only admins reach resource (existing). No tenant access.

- [ ] **Step 5: Feature test Livewire relation manager (optional)**

Or browser test; minimum: HTTP test that admin can hit issue via Livewire is heavy — optional `Livewire::test(EditSaasUser::class, ['record' => $user])->callAction(...)` if stable.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/SaasUsers/
git commit -m "feat(admin): manage SaaS panel login tokens on SaasUser"
```

---

### Task 7: Rotate command + schedule + issuer fixups + tests

**Files:**

- Create: `app/Console/Commands/RotateExpiredSaasPanelLoginTokens.php`
- Modify: `routes/console.php`
- Modify: `app/Services/SaasPanelLoginTokenIssuer.php` (if rotation must send mail with **new** plain — see below)
- Modify: `tests/Feature/TenantPanelTokenEntryTest.php` or new `RotateExpiredSaasPanelLoginTokensTest.php`

**Rotation + mail：** 在 `SaasPanelLoginTokenIssuer` 中新增（并 `use Illuminate\Support\Facades\DB`）：

```php
/**
 * @return array{rotated: bool, plain?: string, user?: SaasUser}
 */
public function rotateExpiredGroup(SaasUser $user): array
{
    $ids = SaasPanelLoginToken::query()
        ->where('saas_user_id', $user->id)
        ->where('expires_at', '<', now())
        ->whereNull('revoked_at')
        ->whereNull('superseded_at')
        ->pluck('id')
        ->all();

    if ($ids === []) {
        return ['rotated' => false];
    }

    return DB::transaction(function () use ($user, $ids): array {
        $issued = $this->issue($user, SaasPanelLoginToken::REASON_EXPIRY_ROTATION);
        $this->markSuperseded($ids);

        return [
            'rotated' => true,
            'plain' => $issued['plain'],
            'user' => $user,
        ];
    });
}
```

**Command `handle()` 核心（只处理「有过期未处理行」的用户，避免全表 cursor）：**

```php
$saasUserIds = SaasPanelLoginToken::query()
    ->where('expires_at', '<', now())
    ->whereNull('revoked_at')
    ->whereNull('superseded_at')
    ->distinct()
    ->pluck('saas_user_id');

$issuer = app(SaasPanelLoginTokenIssuer::class);
$notifier = app(PanelTokenNotifier::class);

foreach ($saasUserIds as $saasUserId) {
    $user = SaasUser::query()->find($saasUserId);
    if ($user === null) {
        continue;
    }
    $result = $issuer->rotateExpiredGroup($user);
    if (($result['rotated'] ?? false) && isset($result['plain'], $result['user'])) {
        $url = $issuer->entryUrl($result['plain']);
        $notifier->sendIssued($result['user'], $url, SaasPanelLoginToken::REASON_EXPIRY_ROTATION);
    }
}
```

- [ ] **Step 1: Implement command `panel-tokens:rotate-expired`**

Use `php artisan make:command RotateExpiredSaasPanelLoginTokens --no-interaction` and fill `handle()`.

- [ ] **Step 2: Schedule**

`routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('panel-tokens:rotate-expired')->hourly();
```

- [ ] **Step 3: Tests**

Seed expired token rows + `Artisan::call('panel-tokens:rotate-expired')` + assert `superseded_at` set + `Mail::assertQueued` + new active token exists.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/TenantPanelTokenEntryTest.php tests/Feature/RotateExpiredSaasPanelLoginTokensTest.php`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/RotateExpiredSaasPanelLoginTokens.php routes/console.php app/Services/SaasPanelLoginTokenIssuer.php tests/
git commit -m "feat(saas): hourly rotation of expired panel login tokens"
```

---

### Task 8: `TenantLifecycle::suspend` revokes panel tokens

**Files:**

- Modify: `app/Services/TenantLifecycle.php`
- Modify: `tests/Feature/SaasTenantPhase1Test.php` (or `TenantPanelTokenEntryTest`)

- [ ] **Step 1: Add to suspend closure**

```php
use App\Models\SaasPanelLoginToken;

$tenant->saasUsers->each(function (SaasUser $user): void {
    $user->tokens()->delete();
    SaasPanelLoginToken::query()
        ->where('saas_user_id', $user->id)
        ->whereNull('revoked_at')
        ->update(['revoked_at' => now()]);
});
```

- [ ] **Step 2: Test**

Create panel token for owner, suspend tenant, assert `revoked_at` not null.

- [ ] **Step 3: Commit**

```bash
git add app/Services/TenantLifecycle.php tests/Feature/SaasTenantPhase1Test.php
git commit -m "fix(saas): revoke panel login tokens when tenant suspended"
```

---

### Task 9: Issuer cap integration test + Pint + full targeted suite

- [ ] **Step 1: Test max 10 active**

Loop `issue` 10 times OK, 11th throws.

- [ ] **Step 2: Run**

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/TenantPanelTokenEntryTest.php tests/Feature/SaasTenantPhase1Test.php tests/Unit/SaasPanelLoginTokenIssuerTest.php tests/Feature/RotateExpiredSaasPanelLoginTokensTest.php
```

- [ ] **Step 3: Commit**

```bash
git commit -am "test(saas): panel token cap and style"
```

---

## Spec coverage (self-review)

| Spec section | Plan location |
|--------------|---------------|
| §4 表字段 | Task 1 migration |
| §5 路由/校验/模糊错误 | Task 4 controller |
| §6.1 创建自动生成 | Task 5 |
| §6.2 手动签发 | Task 6 relation manager |
| §6.3 过期轮换 + superseded | Task 7 + issuer refactor |
| §6.4 手动吊销 | Task 6 row action |
| §7 通知抽象 | Task 3 interface + mail impl; Null notifier optional stub in Task 3 if desired |
| §8 安全 session regenerate | Task 4 |
| §9 测试 | Tasks 4–9 |
| §10 TenantLifecycle 对齐 | Task 8 |
| 软上限 10 | Task 2 + 9 |

**Placeholder scan:** 无故意 TBD；邮件 Markdown 须与仓库现有 `landlord-portal-access` 邮件组件一致（若组件缺失再改用纯 `view()` 邮件）。

---

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-05-13-tenant-panel-token-entry.md`. Two execution options:**

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration  
2. **Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints  

**Which approach?**
