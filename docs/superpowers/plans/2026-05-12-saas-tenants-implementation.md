# SaaS Tenants & SaasUser (Phase 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `tenants` and `saas_users` (owner-only), a second Filament panel at `/tenant-admin` authenticated by a new `saas` guard, platform management under existing `/admin`, audit logs for critical actions, and session/API token invalidation when a tenant is disabled—without touching existing business tables or merging with `App\Models\User`.

**Architecture:** Two Filament panels share one Laravel app: `AdminPanelProvider` remains default for platform `User`; new `TenantPanelProvider` registers a non-default panel with `->path('tenant-admin')->authGuard('saas')`. `Tenant` and `SaasUser` are new Eloquent models; `SaasUser` implements `FilamentUser` and `Authenticatable`. Subscription is **lightweight columns on `tenants`** (`plan`, `trial_ends_at`, `subscription_ends_at`). Token invalidation uses **revoking all Sanctum `personal_access_tokens` rows** for every `SaasUser` under the tenant when status becomes inactive (spec option B). Audit uses a **dedicated `audit_logs` table** and a small `Auditor` helper (no Spatie package in phase 1). **No `/api/saas` routes** in this plan (spec §5 deferred).

**Tech Stack:** Laravel 13, Filament 5, Sanctum (already installed), SQLite/MySQL per env, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-05-12-saas-tenants-design.md`

---

### File map (create / modify)

| Path | Responsibility |
|------|----------------|
| `database/migrations/*_create_tenants_table.php` | `tenants` schema |
| `database/migrations/*_create_saas_users_table.php` | `saas_users` + unique (`tenant_id`,`email`) |
| `database/migrations/*_create_audit_logs_table.php` | audit rows |
| `app/Models/Tenant.php` | status constants, relations, casts |
| `app/Models/SaasUser.php` | auth + FilamentUser + HasApiTokens + belongsTo Tenant |
| `app/Models/AuditLog.php` | mass-assignment safe fill |
| `app/Support/Auditor.php` | `record(User\|SaasUser\|null $actor, string $action, ?Model $subject, array $props = [])` |
| `config/auth.php` | `guards.saas`, `providers.saas_users`, optional `passwords` if you add forgot-password later |
| `app/Providers/Filament/TenantPanelProvider.php` | second panel |
| `bootstrap/providers.php` | register `TenantPanelProvider` |
| `app/Http/Middleware/EnsureTenantIsActiveForSaas.php` | block inactive tenant for `saas` session |
| `app/Filament/Resources/Tenants/TenantResource.php` (+ Pages, Schemas, Tables) | platform CRUD |
| `app/Filament/Resources/SaasUsers/SaasUserResource.php` | list/filter by tenant, view, reset password action |
| `app/Filament/Tenant/Pages/*.php` | minimal tenant dashboard + profile |
| `database/factories/TenantFactory.php`, `SaasUserFactory.php` | tests |
| `tests/Feature/SaasTenantPhase1Test.php` | end-to-end checks |

---

### Task 1: Migrations — `tenants`, `saas_users`, `audit_logs`

**Files:**
- Create: `database/migrations/2026_05_12_150000_create_tenants_table.php`
- Create: `database/migrations/2026_05_12_150001_create_saas_users_table.php`
- Create: `database/migrations/2026_05_12_150002_create_audit_logs_table.php`

- [ ] **Step 1: Add `tenants` migration**

Create file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('trial'); // trial|active|suspended
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->string('plan')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

- [ ] **Step 2: Add `saas_users` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 32)->default('owner');
            $table->unsignedTinyInteger('status')->default(1); // 1 active, 0 disabled
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_users');
    }
};
```

- [ ] **Step 3: Add `audit_logs` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

- [ ] **Step 4: Run migrations**

```bash
cd /var/www/revebnb && php artisan migrate --no-interaction --force
```

Expected: `DONE` for all three.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_05_12_150000_create_tenants_table.php database/migrations/2026_05_12_150001_create_saas_users_table.php database/migrations/2026_05_12_150002_create_audit_logs_table.php
git commit -m "feat(saas): add tenants, saas_users, and audit_logs tables"
```

---

### Task 2: Models `Tenant`, `SaasUser`, `AuditLog` + `Auditor` + factories

**Files:**
- Create: `app/Models/Tenant.php`
- Create: `app/Models/SaasUser.php`
- Create: `app/Models/AuditLog.php`
- Create: `app/Support/Auditor.php`
- Create: `database/factories/TenantFactory.php`
- Create: `database/factories/SaasUserFactory.php`

- [ ] **Step 1: Write failing feature test skeleton** (models load, relations work)

Create `tests/Feature/SaasTenantPhase1Test.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasTenantPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_has_owner_saas_user(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = SaasUser::factory()->for($tenant)->create(['role' => 'owner']);

        $this->assertTrue($tenant->saasUsers()->whereKey($owner->id)->exists());
        $this->assertSame($tenant->id, $owner->tenant_id);
    }
}
```

Run:

```bash
cd /var/www/revebnb && php artisan test tests/Feature/SaasTenantPhase1Test.php
```

Expected: **FAIL** (classes/factories missing).

- [ ] **Step 2: Implement `Tenant` model**

```php
<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name', 'slug', 'status', 'contact_name', 'contact_email', 'notes',
        'plan', 'trial_ends_at', 'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /** @return HasMany<SaasUser, $this> */
    public function saasUsers(): HasMany
    {
        return $this->hasMany(SaasUser::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->status === self::STATUS_TRIAL;
    }
}
```

- [ ] **Step 3: Implement `SaasUser` model** (Authenticatable + Filament + Sanctum)

```php
<?php

namespace App\Models;

use Database\Factories\SaasUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SaasUser extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<SaasUserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'email_verified_at',
        'role', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'integer',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ((int) $this->status !== 1) {
            return false;
        }

        return $this->tenant?->isActive() ?? false;
    }
}
```

- [ ] **Step 4: Implement `AuditLog` + `Auditor`**

`app/Models/AuditLog.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_type', 'actor_id', 'action', 'subject_type', 'subject_id',
        'properties', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }
}
```

`app/Support/Auditor.php`:

```php
<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Auditor
{
    public static function record(?string $actorType, ?int $actorId, string $action, ?Model $subject = null, array $properties = []): void
    {
        AuditLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }

    public static function recordFromGuard(string $guard, string $action, ?Model $subject = null, array $properties = []): void
    {
        $user = Auth::guard($guard)->user();
        if ($user instanceof Model) {
            self::record($user::class, (int) $user->getKey(), $action, $subject, $properties);

            return;
        }

        self::record(null, null, $action, $subject, $properties);
    }
}
```

- [ ] **Step 5: Factories**

`database/factories/TenantFactory.php` — `slug` unique via `Str::slug(fake()->unique()->company())`, `status` default `trial`.

`database/factories/SaasUserFactory.php` — `password` => `Hash::make('password')`, `role` => `owner`, `status` => 1, `tenant_id` required (use `for($tenant)` in tests).

- [ ] **Step 6: Run test**

```bash
php artisan test tests/Feature/SaasTenantPhase1Test.php
```

Expected: **PASS**.

- [ ] **Step 7: Commit**

```bash
git add app/Models/Tenant.php app/Models/SaasUser.php app/Models/AuditLog.php app/Support/Auditor.php database/factories/TenantFactory.php database/factories/SaasUserFactory.php tests/Feature/SaasTenantPhase1Test.php
git commit -m "feat(saas): add Tenant, SaasUser, AuditLog models and factories"
```

---

### Task 3: `config/auth.php` — `saas` guard + provider

**Files:**
- Modify: `config/auth.php`

- [ ] **Step 1: Extend guards and providers**

Inside `'guards'` add:

```php
        'saas' => [
            'driver' => 'session',
            'provider' => 'saas_users',
        ],
```

Inside `'providers'` add:

```php
        'saas_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\SaasUser::class,
        ],
```

Do **not** change `'defaults' => ['guard' => ...]` — keep `web` for the rest of the app.

- [ ] **Step 2: Smoke test config**

```bash
php artisan config:clear && php artisan about
```

Expected: no exceptions.

- [ ] **Step 3: Commit**

```bash
git add config/auth.php && git commit -m "feat(saas): add saas session guard and saas_users provider"
```

---

### Task 4: `TenantPanelProvider` + middleware + register provider

**Files:**
- Create: `app/Providers/Filament/TenantPanelProvider.php`
- Create: `app/Http/Middleware/EnsureTenantIsActiveForSaas.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Middleware** — if tenant not trial/active, logout `saas` guard and redirect to tenant login with flash.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActiveForSaas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('saas')->user();
        if ($user && $user->tenant && ! $user->tenant->isActive()) {
            Auth::guard('saas')->logout();

            return redirect('/tenant-admin/login')
                ->with('error', '组织已停用，请联系平台。');
        }

        return $next($request);
    }
}
```

Register this class **only** in `TenantPanelProvider::middleware()` (do not register globally in `bootstrap/app.php` unless you intend to affect non-Filament routes).

- [ ] **Step 2: Add `TenantPanelProvider`**

Create `app/Providers/Filament/TenantPanelProvider.php` with the following full implementation (panel middleware stack matches `AdminPanelProvider`; `EnsureTenantIsActiveForSaas` is appended at the end so session is started before the check runs on every tenant-panel request including post-login pages):

```php
<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureTenantIsActiveForSaas;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant-admin')
            ->login()
            ->authGuard('saas')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureTenantIsActiveForSaas::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

Do **not** call `->default()` on this panel (the admin panel remains the default).

- [ ] **Step 3: Register provider**

`bootstrap/providers.php` add:

```php
use App\Providers\Filament\TenantPanelProvider;

// in array:
TenantPanelProvider::class,
```

- [ ] **Step 4: Create empty dirs** so discovery does not error:

```bash
mkdir -p app/Filament/Tenant/Resources app/Filament/Tenant/Pages
```

Add a placeholder `app/Filament/Tenant/Pages/.gitkeep` if needed.

- [ ] **Step 5: Hit route smoke**

```bash
php artisan serve --no-reload &
sleep 1
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/tenant-admin/login
kill %1
```

Expected HTTP `200`.

- [ ] **Step 6: Commit**

```bash
git add app/Providers/Filament/TenantPanelProvider.php app/Http/Middleware/EnsureTenantIsActiveForSaas.php bootstrap/providers.php app/Filament/Tenant
git commit -m "feat(saas): add tenant Filament panel and active-tenant middleware"
```

---

### Task 5: Platform `TenantResource` — CRUD + create owner in transaction + audit + one-time password notification

**Files:**
- Create: `app/Filament/Resources/Tenants/TenantResource.php` (+ Pages/Schemas/Tables as per Filament 5 generator pattern, mirroring `Users/UserResource.php` structure)
- Modify: none to existing `UserResource`

Implementation rules:

1. **Create tenant form** collects: `name`, `slug` (auto from name optional), `status`, contacts, `plan`, dates; plus **owner** fields: `owner_name`, `owner_email`, `owner_password` auto-generated in `CreateTenant` page class using `Str::password(20)` unless you add manual override field (YAGNI: auto-generate only).
2. In `CreateRecord` hook `afterCreate` **or** override `handleRecordCreation` on a custom `CreateTenant` page: wrap in `DB::transaction`: create `Tenant`, then `SaasUser::create([... 'role' => 'owner', 'tenant_id' => ...])`.
3. Call `Auditor::recordFromGuard('web', 'tenant.created', $tenant, ['owner_email' => $owner->email])` — do **not** log plaintext password.
4. Show **Filament Notification** to the acting `User` with title “Owner password (copy now)” and body the generated password once.

5. **Suspend tenant** action on Edit page: set `status` to `suspended`, then run:

```php
$tenant->saasUsers()->get()->each(function (SaasUser $u): void {
    $u->tokens()->delete();
});
Auditor::recordFromGuard('web', 'tenant.suspended', $tenant, []);
```

- [ ] **Step 1: Scaffold Filament resource (creates `CreateTenant` class file)**

```bash
cd /var/www/revebnb
php artisan make:filament-resource Tenant --generate --no-interaction
```

Move generated classes from `app/Filament/Resources/Tenants/` if the generator placed them under `Resources/Tenant/` — the **target namespace** must be `App\Filament\Resources\Tenants\` with pages `CreateTenant`, `EditTenant`, `ListTenants` mirroring `app/Filament/Resources/Users/`.

- [ ] **Step 2: Extend `TenantForm` schema** with `owner_name` and `owner_email` fields (strings, required on create). Keep tenant fields aligned with `$fillable` on `Tenant` model.

- [ ] **Step 3: Add Livewire feature test**

Append to `tests/Feature/SaasTenantPhase1Test.php`:

```php
use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Models\User;
use Livewire\Livewire;

public function test_platform_admin_can_create_tenant_with_owner(): void
{
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $slug = 'acme-'.uniqid();

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => 'Acme Corp',
            'slug' => $slug,
            'status' => Tenant::STATUS_TRIAL,
            'owner_name' => 'Owner User',
            'owner_email' => 'owner@acme.test',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tenants', [
        'slug' => $slug,
        'name' => 'Acme Corp',
    ]);

    $tenantId = Tenant::query()->where('slug', $slug)->value('id');

    $this->assertDatabaseHas('saas_users', [
        'tenant_id' => $tenantId,
        'email' => 'owner@acme.test',
        'role' => 'owner',
    ]);
}
```

Run:

```bash
php artisan test tests/Feature/SaasTenantPhase1Test.php --filter=test_platform_admin_can_create_tenant_with_owner
```

Expected: **FAIL** until Step 4 implements provisioning (`saas_users` row not created yet).

If `fillForm` keys do not match the schema component names, adjust keys to match `TenantForm::configure` output (Filament 5 uses flat keys when using `Schema::components([...])`).

- [ ] **Step 4: Implement provisioning on `CreateTenant`**

Override `handleRecordCreation` (or `mutateFormDataBeforeCreate` + `afterCreate` per Filament 5 `CreateRecord` hooks) so that:

1. `DB::transaction` wraps creation.
2. Insert `Tenant` from form data excluding `owner_name` / `owner_email`.
3. `$plain = \Illuminate\Support\Str::password(20);` then `SaasUser::create(['tenant_id' => $tenant->id, 'name' => $ownerName, 'email' => $ownerEmail, 'password' => $plain, 'role' => 'owner', 'status' => 1]);` — rely on `hashed` cast on `SaasUser` by assigning plain string **or** use `Hash::make` consistently with your model casts.
4. `Auditor::recordFromGuard('web', 'tenant.created', $tenant, ['owner_email' => $ownerEmail]);`
5. `\Filament\Notifications\Notification::make()->title('Owner password (copy now)')->body($plain)->success()->send();`

Return the `Tenant` model instance from the transaction as the created record.

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Feature/SaasTenantPhase1Test.php --filter=test_platform_admin_can_create_tenant_with_owner
php artisan test
```

Expected: targeted test **PASS**, full suite **PASS**.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/Tenants tests/Feature/SaasTenantPhase1Test.php
git commit -m "feat(saas): platform TenantResource with owner provisioning"
```

---

### Task 6: Platform `SaasUserResource` — filter by tenant, reset password, audit

**Files:**
- Create: `app/Filament/Resources/SaasUsers/SaasUserResource.php` (+ pages/schemas/tables)

- [ ] **Step 1: Table** — columns: tenant name (relationship), email, role, status, created_at.

- [ ] **Step 2: Header filter** — `SelectFilter::make('tenant_id')->relationship('tenant', 'name')`.

- [ ] **Step 3: Action `resetPassword`** — generates new `Str::password(20)`, `Hash::make` saved, `Auditor::recordFromGuard('web', 'saas_user.password_reset', $record)`, Filament Notification with one-time password to the admin.

- [ ] **Step 4: Policy** — `viewAny`/`view`/`update` only for `User` where `$user->is_admin` (reuse existing convention).

- [ ] **Step 5: Test** — acting as admin, `Livewire::test(ListSaasUsers::class)->assertSuccessful()`.

- [ ] **Step 6: Commit** — `feat(saas): add SaasUserResource for platform operators`

---

### Task 7: Tenant panel UX — dashboard + profile (name, email read-only, password change)

**Files:**
- Create: `app/Filament/Tenant/Pages/EditSaasProfile.php` (extends `Filament\Pages\Page` or use Filament v5 profile pattern)
- Register in `TenantPanelProvider::pages([...])`

Minimum:

- Form: `name` (editable), `email` (disabled), `password` + `passwordConfirmation` (dehydrated false, only updates when filled) mirroring `UserForm` pattern from `app/Filament/Resources/Users/Schemas/UserForm.php`.

- [ ] **Step 1: Feature test** — create `SaasUser`, `actingAs($saasUser, 'saas')`, `Livewire::test(EditSaasProfile::class)->set('data.name', 'New')->call('save')->assertHasNoErrors(); assert DB.

- [ ] **Step 2: Commit** — `feat(saas): tenant panel profile page`

---

### Task 8: Tenant suspend — integration test for token revoke + login blocked

**Files:**
- Modify: `tests/Feature/SaasTenantPhase1Test.php`

- [ ] **Step 1: Test `tokens` deleted**

```php
use Laravel\Sanctum\Sanctum;

public function test_suspending_tenant_revokes_saas_tokens(): void
{
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    $owner = SaasUser::factory()->for($tenant)->create();
    $token = $owner->createToken('t')->plainTextToken;

    $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);
    // call same code path as Filament action — extract to TenantService::suspend($tenant) and call here
    app(\App\Services\TenantLifecycle::class)->suspend($tenant);

    $this->assertSame(0, $owner->tokens()->count());
}
```

- [ ] **Step 2: Implement `App\Services\TenantLifecycle::suspend(Tenant $tenant): void`** containing status update + token deletion + audit — call this from Filament action to keep single source of truth.

- [ ] **Step 3: Commit** — `feat(saas): suspend tenant revokes Sanctum tokens and audits`

---

### Task 9: SaasUser login messaging + Filament login customization

**Files:**
- Create: `app/Filament/Tenant/Pages/Auth/Login.php` extending Filament's Login page **or** use `->login()` customization callbacks documented in Filament 5 if available.

Behavior: when credentials valid but `!$user->canAccessPanel()`, return validation error **“组织已停用或账号不可用”** instead of generic failed login.

- [ ] **Step 1: Feature test** with `post` to Livewire login component.

- [ ] **Step 2: Commit** — `feat(saas): clarify tenant login errors when tenant inactive`

---

### Plan self-review (against spec)

| Spec item | Covered by task |
|-----------|-----------------|
| §2 dual panel + `saas` guard | Task 3–4 |
| §3.1 tenants + lightweight plan fields | Task 1 |
| §3.2 saas_users composite email, owner only | Task 1–2, 5 |
| §3.3 token invalidation option B | Task 8 |
| §4.1 TenantResource + owner | Task 5 |
| §4.1 SaasUserResource | Task 6 |
| §4.2 tenant panel + middleware | Task 4, 7 |
| §6 audit_logs | Task 1–2, 5–8 |
| §7 security copy | Task 6 policy, Task 4 middleware |
| §10 acceptance | Tasks 5–9 tests |
| §5 `/api/saas` | **Explicitly out of scope** in this plan header |

**Placeholder scan:** none remaining in tasks above.

**Type consistency:** `Tenant::STATUS_*` strings used consistently; `SaasUser` `status` int `1|0` matches factories.

---

## Execution handoff

**Plan complete and saved to** `docs/superpowers/plans/2026-05-12-saas-tenants-implementation.md`.

**Two execution options:**

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration (**skill:** `superpowers:subagent-driven-development`).

2. **Inline Execution** — run tasks sequentially in this workspace with checkpoints (**skill:** `superpowers:executing-plans`).

Which approach do you want?
