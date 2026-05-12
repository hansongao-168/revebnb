# B2B2C 房东独立后台 + 魔法链接 Token Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在现有 Laravel + Filament 双面板基础上，实现 `Tenant` 下属 `Landlord`、72h 不透明访问 token、魔法链接兑换 `landlord` session、第三 Filament 面板 `/landlord-portal`、平台 `/admin` 托管 CRUD 与重发、定时过期轮换 + 队列邮件，并满足 spec 中的会话隔离与租户停用联动。

**Architecture:** `landlords` + `landlord_access_tokens` 表；`Landlord` 为 session guard `landlord` 的 Authenticatable；`LandlordTokenService` 负责签发/哈希/校验/吊销；公开 `GET /landlord-portal/login/{token}` 在 `routes/web.php` 注册（URL-safe token）；`LandlordPanelProvider` 与 `EnsureLandlordTenantIsActive` 中间件对齐 SaaS 租户中间件模式；`landlord:renew-expired-access-tokens` + `bootstrap/app.php` 的 `withSchedule` 每小时执行；邮件经队列发送。

**Tech Stack:** Laravel 13（项目当前）、Filament 5、PHPUnit、`Auditor` 与现有 `audit_logs`、Laravel Mail + `ShouldQueue` Mailable。

**Spec:** `docs/superpowers/specs/2026-05-12-rental-landlord-b2b2c-design.md`

---

## File map（创建 / 修改）

| 路径 | 职责 |
|------|------|
| `database/migrations/2026_05_12_200000_create_landlords_table.php` | `landlords` 表 |
| `database/migrations/2026_05_12_200001_create_landlord_access_tokens_table.php` | token 表 |
| `app/Models/Landlord.php` | 认证主体 + `tenant` 关联 + FilamentUser |
| `app/Models/LandlordAccessToken.php` | token 行模型 |
| `database/factories/LandlordFactory.php` | 测试数据 |
| `database/factories/LandlordAccessTokenFactory.php` | 可选；或测试中手工 `LandlordTokenService` 签发 |
| `config/landlord.php` | `token_ttl_hours`、`schedule_interval`、`auto_email_cooldown_hours` |
| `config/auth.php` | `landlord` guard + `landlords` provider |
| `app/Services/LandlordTokenService.php` | 签发、哈希、查找有效行、吊销、轮换 |
| `app/Http/Controllers/LandlordMagicLoginController.php` | 兑换 session |
| `app/Http/Middleware/EnsureLandlordTenantIsActive.php` | 租户非活跃则登出并重定向 |
| `app/Providers/Filament/LandlordPanelProvider.php` | 第三面板 |
| `app/Filament/Landlord/Pages/Auth/LandlordPortalLoginHint.php` | 无密码提示页（Filament `Login` 子类或 `SimplePage`） |
| `app/Filament/Resources/Landlords/*` | 平台 Resource + Form + Table + Pages + Actions |
| `app/Policies/LandlordPolicy.php` | 仅 `is_admin` |
| `app/Mail/LandlordPortalAccessMail.php` | 邮件 |
| `app/Console/Commands/RenewExpiredLandlordAccessTokensCommand.php` | 调度入口 |
| `routes/web.php` | 魔法路由 |
| `bootstrap/app.php` | `withSchedule` |
| `bootstrap/providers.php` | 注册 `LandlordPanelProvider` |
| `resources/views/landlord/magic-login-failed.blade.php` | 兑换失败极简页 |
| `tests/Feature/LandlordPortalTest.php` | 核心验收测试 |

---

### Task 1: Migrations（landlords + landlord_access_tokens）

**Files:**
- Create: `database/migrations/2026_05_12_200000_create_landlords_table.php`
- Create: `database/migrations/2026_05_12_200001_create_landlord_access_tokens_table.php`

- [ ] **Step 1: 添加 landlords migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamp('last_auto_token_email_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlords');
    }
};
```

- [ ] **Step 2: 添加 landlord_access_tokens migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlord_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('renewal_email_sent_at')->nullable();
            $table->timestamps();

            $table->index(['landlord_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_access_tokens');
    }
};
```

- [ ] **Step 3: 运行迁移**

Run: `cd /var/www/revebnb && php artisan migrate`
Expected: migrated OK

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_12_200000_create_landlords_table.php database/migrations/2026_05_12_200001_create_landlord_access_tokens_table.php
git commit -m "feat(landlord): add landlords and landlord_access_tokens tables"
```

---

### Task 2: Models + factories

**Files:**
- Create: `app/Models/Landlord.php`
- Create: `app/Models/LandlordAccessToken.php`
- Create: `database/factories/LandlordFactory.php`
- Modify: `app/Models/Tenant.php`（增加 `landlords()` `HasMany`）

- [ ] **Step 1: 编写 `Landlord` 模型**

```php
<?php

namespace App\Models;

use Database\Factories\LandlordFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Landlord extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<LandlordFactory> */
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'status',
        'password',
        'last_auto_token_email_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_auto_token_email_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<LandlordAccessToken, $this> */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(LandlordAccessToken::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->tenant?->isActive() ?? false;
    }
}
```

- [ ] **Step 2: 编写 `LandlordAccessToken` 模型**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandlordAccessToken extends Model
{
    protected $fillable = [
        'landlord_id',
        'token_hash',
        'issued_at',
        'expires_at',
        'revoked_at',
        'renewal_email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'renewal_email_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Landlord, $this> */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    public function isCurrentlyValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at->isFuture();
    }
}
```

- [ ] **Step 3: `Tenant` 增加关联**

在 `app/Models/Tenant.php` 的 class 内追加：

```php
/** @return \Illuminate\Database\Eloquent\Relations\HasMany<Landlord, $this> */
public function landlords(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Landlord::class);
}
```

- [ ] **Step 4: `LandlordFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Landlord;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Landlord>
 */
class LandlordFactory extends Factory
{
    protected $model = Landlord::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'status' => Landlord::STATUS_ACTIVE,
            'password' => Str::random(32),
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/Landlord.php app/Models/LandlordAccessToken.php app/Models/Tenant.php database/factories/LandlordFactory.php
git commit -m "feat(landlord): add Landlord models and factory"
```

---

### Task 3: `config/landlord.php` + `config/auth.php`

**Files:**
- Create: `config/landlord.php`
- Modify: `config/auth.php`

- [ ] **Step 1: 创建 `config/landlord.php`**

```php
<?php

return [
    'token_ttl_hours' => (int) env('LANDLORD_TOKEN_TTL_HOURS', 72),
    'auto_email_cooldown_hours' => (int) env('LANDLORD_AUTO_EMAIL_COOLDOWN_HOURS', 24),
];
```

- [ ] **Step 2: 在 `config/auth.php` 的 `guards` 数组追加**

```php
        'landlord' => [
            'driver' => 'session',
            'provider' => 'landlords',
        ],
```

- [ ] **Step 3: 在 `providers` 数组追加（并在文件顶部 `use App\Models\Landlord;`）**

```php
        'landlords' => [
            'driver' => 'eloquent',
            'model' => Landlord::class,
        ],
```

- [ ] **Step 4: Commit**

```bash
git add config/landlord.php config/auth.php
git commit -m "feat(landlord): add landlord guard and landlord config"
```

---

### Task 4: `LandlordTokenService`

**Files:**
- Create: `app/Services/LandlordTokenService.php`

- [ ] **Step 1: 实现服务（签发 / 哈希 / 吊销 / 当前有效 token）**

```php
<?php

namespace App\Services;

use App\Models\Landlord;
use App\Models\LandlordAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LandlordTokenService
{
    public function hashPlainToken(string $plain): string
    {
        return hash_hmac('sha256', $plain, (string) config('app.key'));
    }

    /**
     * @return array{plain: string, token: LandlordAccessToken}
     */
    public function issueNewToken(Landlord $landlord, bool $revokeOthers = true): array
    {
        $plain = Str::lower(Str::random(48));

        return DB::transaction(function () use ($landlord, $revokeOthers, $plain): array {
            if ($revokeOthers) {
                LandlordAccessToken::query()
                    ->where('landlord_id', $landlord->id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            $ttlHours = (int) config('landlord.token_ttl_hours', 72);
            $issuedAt = now();

            $token = LandlordAccessToken::query()->create([
                'landlord_id' => $landlord->id,
                'token_hash' => $this->hashPlainToken($plain),
                'issued_at' => $issuedAt,
                'expires_at' => $issuedAt->copy()->addHours($ttlHours),
                'revoked_at' => null,
                'renewal_email_sent_at' => null,
            ]);

            return ['plain' => $plain, 'token' => $token];
        });
    }

    public function findValidTokenRowByPlain(string $plain): ?LandlordAccessToken
    {
        $hash = $this->hashPlainToken($plain);

        return LandlordAccessToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function portalLoginUrl(string $plain): string
    {
        return url('/landlord-portal/login/'.$plain);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/LandlordTokenService.php
git commit -m "feat(landlord): add token issue and hash service"
```

---

### Task 5: 魔法链接控制器 + 视图 + 路由

**Files:**
- Create: `app/Http/Controllers/LandlordMagicLoginController.php`
- Create: `resources/views/landlord/magic-login-failed.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: 控制器**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Services\LandlordTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordMagicLoginController extends Controller
{
    public function __construct(
        protected LandlordTokenService $tokens,
    ) {}

    public function __invoke(string $token, Request $request): RedirectResponse|View
    {
        $row = $this->tokens->findValidTokenRowByPlain($token);

        if (! $row) {
            return view('landlord.magic-login-failed');
        }

        /** @var Landlord|null $landlord */
        $landlord = Landlord::query()->with('tenant')->find($row->landlord_id);

        if (! $landlord || $landlord->status !== Landlord::STATUS_ACTIVE || ! ($landlord->tenant?->isActive() ?? false)) {
            return view('landlord.magic-login-failed');
        }

        Auth::guard('landlord')->login($landlord, remember: false);
        $request->session()->regenerate();

        return redirect()->intended('/landlord-portal');
    }
}
```

- [ ] **Step 2: 失败视图 `resources/views/landlord/magic-login-failed.blade.php`**

```blade
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>无法登录</title>
</head>
<body>
<p>链接无效或已过期。请使用邮件中的最新链接，或联系平台管理员重新发送。</p>
</body>
</html>
```

- [ ] **Step 3: 在 `routes/web.php` 追加（保持字母数字 token，避免斜杠问题）**

```php
use App\Http\Controllers\LandlordMagicLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/landlord-portal/login/{token}', LandlordMagicLoginController::class)
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('web');
```

（若 `web` 中间件组已包裹 `routes/web.php` 内所有路由，可省略 `->middleware('web')`；以项目 `web.php` 实际为准。）

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/LandlordMagicLoginController.php resources/views/landlord/magic-login-failed.blade.php routes/web.php
git commit -m "feat(landlord): add magic login route and controller"
```

---

### Task 6: `LandlordPanelProvider` + 中间件 + 登录提示页 + 注册 Provider

**Files:**
- Create: `app/Providers/Filament/LandlordPanelProvider.php`
- Create: `app/Http/Middleware/EnsureLandlordTenantIsActive.php`
- Create: `app/Filament/Landlord/Pages/Auth/LandlordPortalLoginHint.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: 中间件 `EnsureLandlordTenantIsActive`**

```php
<?php

namespace App\Http\Middleware;

use App\Models\Landlord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandlordTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('landlord')->user();
        if ($user instanceof Landlord) {
            $user->loadMissing('tenant');
            if (! $user->tenant || ! $user->tenant->isActive()) {
                Auth::guard('landlord')->logout();

                return redirect('/landlord-portal/login')
                    ->with('error', __('saas.auth.middleware_tenant_inactive'));
            }
        }

        return $next($request);
    }
}
```

（若 Filament 默认登录路径为 `/landlord-portal/login` 且无 token 参数，与魔法路由冲突：将魔法路径改为 `/landlord-portal/magic/{token}` **或** 将 Filament `->login(false)` 并仅用提示页；**实现前在代码中验证路由表** `php artisan route:list | grep landlord`。如冲突，优先把魔法路径改为 `/landlord-portal/magic/{token}` 并同步更新 `LandlordTokenService::portalLoginUrl`、spec 邮件文案与本文 Task 5 路由。）

- [ ] **Step 2: `LandlordPortalLoginHint`** — 继承 `Filament\Auth\Pages\Login`，重写 `authenticate()` 使其 `throw ValidationException` 固定中文「请使用邮件中的入口链接」，或继承 `Filament\Pages\SimplePage` 仅展示静态说明；任选一种能通过 Filament `->login(...)` 挂载即可。

- [ ] **Step 3: `LandlordPanelProvider`** — 复制 `TenantPanelProvider` 结构：`->id('landlord')`、`->path('landlord-portal')`、`->authGuard('landlord')`、`->login(LandlordPortalLoginHint::class)`、`->pages([Dashboard::class])`、`->middleware([... , EnsureLandlordTenantIsActive::class])`。

- [ ] **Step 4: `bootstrap/providers.php` 注册** `LandlordPanelProvider::class`

- [ ] **Step 5: Commit**

```bash
git add app/Providers/Filament/LandlordPanelProvider.php app/Http/Middleware/EnsureLandlordTenantIsActive.php app/Filament/Landlord/Pages/Auth/LandlordPortalLoginHint.php bootstrap/providers.php
git commit -m "feat(landlord): add landlord Filament panel and tenant-active middleware"
```

---

### Task 7: `LandlordPolicy` + Filament 授权

**Files:**
- Create: `app/Policies/LandlordPolicy.php`

- [ ] **Step 1: Policy（与 `TenantPolicy` 相同风格）**

```php
<?php

namespace App\Policies;

use App\Models\Landlord;
use App\Models\User;

class LandlordPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, Landlord $landlord): bool
    {
        return (bool) $user->is_admin;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Policies/LandlordPolicy.php
git commit -m "feat(landlord): add LandlordPolicy for platform admin"
```

---

### Task 8: `LandlordResource`（平台 `/admin`）

**Files:**
- Create: `app/Filament/Resources/Landlords/LandlordResource.php`
- Create: `app/Filament/Resources/Landlords/Schemas/LandlordForm.php`（`Select` 关联 `Tenant`、`TextInput` name/email/phone、`Select` status）
- Create: `app/Filament/Resources/Landlords/Tables/LandlordsTable.php`（列：tenant、name、email、status；筛选 tenant）
- Create: `app/Filament/Resources/Landlords/Pages/ListLandlords.php`、`CreateLandlord.php`、`EditLandlord.php`
- **CreateLandlord / EditLandlord**：创建成功后调用 `LandlordTokenService::issueNewToken` + `Mail::queue(new LandlordPortalAccessMail(...))`；`Auditor::recordFromGuard('web', 'landlord.created', $landlord, [])`；Edit 上挂 `Action`「重发入口链接」吊销+签发+邮件+`landlord.token_resent` 审计；禁用动作：`status=disabled` + 吊销 token + `landlord.disabled`

（具体 Filament 5 Schema 写法对齐现有 `TenantResource` / `TenantForm`；此处不重复粘贴整份 Resource 生成代码，**实现时**运行 `php artisan make:filament-resource Landlord --generate` 再按 spec 改字段与 `afterCreate` Hook。）

- [ ] **Step 1: 生成骨架并改表单/表格/Policy 绑定**

Run: `cd /var/www/revebnb && php artisan make:filament-resource Landlord --generate`
Expected: 生成 Resource + Pages + Schema + Table

- [ ] **Step 2: 手工调整** `CreateLandlord` / `EditLandlord` 的 `afterCreate` / `Action` 逻辑与审计、邮件。

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Resources/Landlords
git commit -m "feat(landlord): add Filament LandlordResource for platform admin"
```

---

### Task 9: `LandlordPortalAccessMail`

**Files:**
- Create: `app/Mail/LandlordPortalAccessMail.php`
- Create: `resources/views/mail/landlord-portal-access.blade.php`

- [ ] **Step 1: Mailable（`ShouldQueue`）**

```php
<?php

namespace App\Mail;

use App\Models\Landlord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LandlordPortalAccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Landlord $landlord,
        public string $loginUrl,
        public string $expiresAtDisplay,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '房东控制台入口链接',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.landlord-portal-access',
        );
    }
}
```

- [ ] **Step 2: Markdown 视图** `resources/views/mail/landlord-portal-access.blade.php` — 包含称呼、`$loginUrl`、`$expiresAtDisplay`、勿转发提示。

- [ ] **Step 3: Commit**

```bash
git add app/Mail/LandlordPortalAccessMail.php resources/views/mail/landlord-portal-access.blade.php
git commit -m "feat(landlord): add queued portal access mailable"
```

---

### Task 10: 续发命令 + `Schedule`

**Files:**
- Create: `app/Console/Commands/RenewExpiredLandlordAccessTokensCommand.php`
- Modify: `bootstrap/app.php`

- [ ] **Step 1: 命令逻辑（核心循环）**

对每个 `Landlord::query()->where('status', Landlord::STATUS_ACTIVE)->with('tenant')`：

- 若 `! $landlord->tenant || ! $landlord->tenant->isActive()` → `continue`。
- `$latest = LandlordAccessToken::query()->where('landlord_id', $landlord->id)->whereNull('revoked_at')->orderByDesc('expires_at')->first()`。
- 若 `$latest` 为 null 或 `$latest->expires_at->isFuture()` → `continue`。
- **防刷**：若 `last_auto_token_email_at` 存在且 `< cooldown hours` → `continue`（cooldown 来自 `config('landlord.auto_email_cooldown_hours')`）。
- `issueNewToken($landlord)`；若 `$latest->renewal_email_sent_at === null`，在轮换前将 `$latest->renewal_email_sent_at = now()` 保存（或轮换后标记旧行，避免重复；**固定一种并在测试中验证**）。
- `Mail::queue(new LandlordPortalAccessMail(...))`；`$landlord->forceFill(['last_auto_token_email_at' => now()])->save()`。

- [ ] **Step 2: `bootstrap/app.php` 增加 schedule**

```php
use Illuminate\Console\Scheduling\Schedule;

// 在 Application::configure 链上追加：
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('landlord:renew-expired-access-tokens')->hourly();
})
```

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/RenewExpiredLandlordAccessTokensCommand.php bootstrap/app.php
git commit -m "feat(landlord): add token renewal command and hourly schedule"
```

---

### Task 11: Feature tests

**Files:**
- Create: `tests/Feature/LandlordPortalTest.php`

- [ ] **Step 1: 编写测试（示例骨架，实现时补全断言）**

```php
<?php

namespace Tests\Feature;

use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Services\LandlordTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandlordPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_magic_token_logs_in_landlord(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $plain = app(LandlordTokenService::class)->issueNewToken($landlord)['plain'];

        $this->get('/landlord-portal/login/'.$plain)
            ->assertRedirect();

        $this->assertAuthenticatedAs($landlord, 'landlord');
    }

    public function test_expired_magic_token_shows_failure_view(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $service = app(LandlordTokenService::class);
        $plain = 'expiredtokenplain01234567890123456789012345678901';
        $hash = $service->hashPlainToken($plain);
        $landlord->accessTokens()->create([
            'token_hash' => $hash,
            'issued_at' => now()->subDays(5),
            'expires_at' => now()->subHour(),
            'revoked_at' => null,
            'renewal_email_sent_at' => null,
        ]);

        $this->get('/landlord-portal/login/'.$plain)
            ->assertOk()
            ->assertSee('链接无效或已过期', false);
    }
}
```

- [ ] **Step 2: 补全** `test_expired_magic_token`、`test_disabled_landlord`、`test_suspended_tenant_blocked`、`test_renew_command_sends_mail_once`（`Mail::fake()` + `Artisan::call('landlord:renew-expired-access-tokens')` 两次断言发送次数）。

Run: `cd /var/www/revebnb && php artisan test --filter=LandlordPortalTest`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/LandlordPortalTest.php
git commit -m "test(landlord): add landlord portal feature tests"
```

---

### Task 12: 全量验证

- [ ] **Step 1: Pint + 全量测试**

Run: `cd /var/www/revebnb && ./vendor/bin/pint --dirty && php artisan test`
Expected: PASS

- [ ] **Step 2: Commit（如有格式化）**

```bash
git add -A && git commit -m "style: pint landlord feature" || true
```

---

## Plan self-review

| Spec 章节 | 对应 Task |
|-----------|-----------|
| §3 数据模型 | Task 1–2 |
| §4 魔法链接 | Task 4–5（含路由冲突说明） |
| §5 续发 + 邮件 | Task 9–10 |
| §6 Filament / 权限 | Task 6–8 |
| §6.3 租户停用 + 会话 | Task 1 + Task 6 中间件 |
| §7 审计 | Task 8 手工动作（自动续发可选从简） |
| §8 测试 | Task 11–12 |

**Placeholder scan:** Task 8 依赖 `make:filament-resource` 后手工合并；Task 11 已给出完整过期用例，实现时按需补充 `test_renew_command_sends_mail_once` 等。

**Gap:** E2「过期访问补发」在 spec 为可选；本计划未单列 Task — 若要做，在 Task 5 控制器分支检测过期 token 行 + 调用与 Command 共享的 `LandlordRenewalService::maybeRenew()`。

**Type consistency:** `Landlord::STATUS_*` 与 migration `status` 字符串一致；token 路由 `where` 与 `Str::random(48)` 字符集一致（仅 `[A-Za-z0-9]` 需将签发改为 `Str::random` 的字母数字子集或使用 `Str::lower(Str::random)` 仅含字母数字 — **若 `random` 含符号需改为 `Str::password(48, symbols: false)` 或 `random_bytes` base64url strip**）。

**修正（实现时必做）：** `LandlordTokenService::issueNewToken` 已使用 `Str::lower(Str::random(48))` — Laravel `Str::random` 默认可打印 ASCII，无 `/`；仍建议 `->where('token', '[A-Za-z0-9]+')` 与生成字符集对齐。

---

## Execution handoff

Plan complete and saved to `docs/superpowers/plans/2026-05-12-rental-landlord-b2b2c-implementation.md`.

**Two execution options:**

1. **Subagent-Driven（推荐）** — 每个 Task 派生子代理，任务间审查，迭代快  
2. **Inline Execution** — 本会话按 Task 执行，使用 executing-plans，带检查点批量推进  

**Which approach?**
