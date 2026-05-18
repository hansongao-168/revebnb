# Site frontend modules + navigation management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce `site_pages` module registry, database-driven Web/UniApp navigation (no CMS), modular Blade views under `site/modules/*`, and Filament `/admin` resources under navigation group `前台`.

**Architecture:** `config/site-pages.php` is the source of truth for page modules; `php artisan site:sync-pages` upserts `site_pages`. `SiteNavigationResolver` turns `site_navigation_items` / `uniapp_navigation_items` into URLs; `SiteNavigationService` caches by `placement` with Seeder fallback matching today’s hardcoded header/footer/category-strip. Blade layout receives `$siteNav` via `SiteNavigationComposer`.

**Tech Stack:** Laravel 13, Filament 5 (`Filament\Schemas\Schema`), PHPUnit 12, Livewire tests for Filament, Blade, Sanctum API route for UniApp.

**Spec:** `docs/superpowers/specs/2026-05-18-site-frontend-modules-navigation-design.md` (approved)

---

## File map

| Path | Responsibility |
|------|----------------|
| `config/site-pages.php` | Manifest of all `site_pages` keys |
| `config/site.php` | `navigation_cache_ttl`, etc. |
| `database/migrations/2026_05_18_100000_create_site_pages_table.php` | Pages registry |
| `database/migrations/2026_05_18_100001_create_site_navigation_items_table.php` | Web nav |
| `database/migrations/2026_05_18_100002_create_uniapp_navigation_items_table.php` | UniApp nav |
| `app/Site/Enums/SiteNavPlacement.php` | Web placements backed enum |
| `app/Site/Enums/SiteNavLinkType.php` | Web link types |
| `app/Site/Enums/SiteModuleGroup.php` | Module groups |
| `app/Site/Enums/UniappNavPlacement.php` | UniApp placements |
| `app/Site/Enums/UniappNavLinkType.php` | UniApp link types |
| `app/Site/Data/ResolvedNavItem.php` | DTO: title, url, target, icon, style_variant, is_active |
| `app/Site/Support/SitePageManifest.php` | Read config, validate keys |
| `app/Site/Services/SiteNavigationResolver.php` | URL + active_match resolution |
| `app/Site/Services/SiteNavigationService.php` | Query, cache, fallback |
| `app/Site/View/Composers/SiteNavigationComposer.php` | Inject `$siteNav` |
| `app/Models/SitePage.php` | Eloquent |
| `app/Models/SiteNavigationItem.php` | Eloquent |
| `app/Models/UniappNavigationItem.php` | Eloquent |
| `app/Console/Commands/SyncSitePagesCommand.php` | `site:sync-pages` |
| `database/seeders/SiteNavigationSeeder.php` | Default nav from current Blade |
| `app/Filament/Resources/SitePages/*` | Admin CRUD (restricted fields) |
| `app/Filament/Resources/SiteNavigationItems/*` | Admin CRUD + reorder |
| `app/Filament/Resources/UniappNavigationItems/*` | Admin CRUD |
| `app/Http/Controllers/Api/UniappNavigationController.php` | `GET /api/uniapp/navigation` |
| `resources/views/components/site/header.blade.php` | Dynamic nav loop |
| `resources/views/components/site/footer.blade.php` | Dynamic footer groups |
| `resources/views/components/site/category-strip.blade.php` | Dynamic categories + icon map |
| `resources/views/site/modules/**` | Moved views |
| `tests/Unit/SiteNavigationResolverTest.php` | Resolver unit tests |
| `tests/Feature/SiteNavigationTest.php` | HTTP + sync + API |
| `tests/Feature/SiteNavigationAdminTest.php` | Filament Livewire |

---

## Phase 1 — Foundation

### Task 1: Enums + config manifests

**Files:**
- Create: `app/Site/Enums/SiteNavPlacement.php`
- Create: `app/Site/Enums/SiteNavLinkType.php`
- Create: `app/Site/Enums/SiteModuleGroup.php`
- Create: `config/site.php`
- Create: `config/site-pages.php`

- [ ] **Step 1: Add enums**

```php
<?php
// app/Site/Enums/SiteNavPlacement.php
namespace App\Site\Enums;

enum SiteNavPlacement: string
{
    case Header = 'header';
    case Footer = 'footer';
    case CategoryStrip = 'category_strip';
    case UserMenu = 'user_menu';
    case Hero = 'hero';
    case BookingFlow = 'booking_flow';
    case ListingCard = 'listing_card';
}
```

```php
<?php
// app/Site/Enums/SiteNavLinkType.php
namespace App\Site\Enums;

enum SiteNavLinkType: string
{
    case SitePage = 'site_page';
    case NamedRoute = 'named_route';
    case ExternalUrl = 'external_url';
}
```

```php
<?php
// app/Site/Enums/SiteModuleGroup.php
namespace App\Site\Enums;

enum SiteModuleGroup: string
{
    case Stays = 'stays';
    case Bookings = 'bookings';
    case Account = 'account';
    case Docs = 'docs';
    case Landlord = 'landlord';
    case Uniapp = 'uniapp';
}
```

- [ ] **Step 2: Add `config/site.php`**

```php
<?php

return [
    'navigation_cache_ttl' => (int) env('SITE_NAVIGATION_CACHE_TTL', 3600),
];
```

- [ ] **Step 3: Add `config/site-pages.php`** (all keys from spec §3.2)

```php
<?php

return [
    'stays.index' => [
        'name' => '住宿列表',
        'module_group' => 'stays',
        'web_route_name' => 'site.stays.index',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => '前台住宿浏览首页',
    ],
    'stays.show' => [
        'name' => '房源详情',
        'module_group' => 'stays',
        'web_route_name' => 'site.stays.show',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => '单房源详情（动态 {listing} 由视图生成）',
    ],
    'bookings.confirmation' => [
        'name' => '预订确认',
        'module_group' => 'bookings',
        'web_route_name' => 'site.bookings.confirmation',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => null,
    ],
    'bookings.show' => [
        'name' => '订单详情',
        'module_group' => 'bookings',
        'web_route_name' => 'site.bookings.show',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => null,
    ],
    'account.bookings' => [
        'name' => '我的订单',
        'module_group' => 'account',
        'web_route_name' => 'site.me.bookings',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => null,
    ],
    'docs.stored-urls-intro' => [
        'name' => 'URL 入库说明',
        'module_group' => 'docs',
        'web_route_name' => 'docs.stored-urls-intro',
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => null,
    ],
    'landlord.portal-login' => [
        'name' => '房东门户登录',
        'module_group' => 'landlord',
        'web_route_name' => null,
        'web_route_params' => [],
        'uniapp_path' => null,
        'description' => '外链 /landlord-portal/login',
    ],
    'uniapp.index' => [
        'name' => 'UniApp 首页',
        'module_group' => 'uniapp',
        'web_route_name' => null,
        'web_route_params' => [],
        'uniapp_path' => '/pages/index/index',
        'description' => null,
    ],
    'uniapp.login' => [
        'name' => 'UniApp 登录',
        'module_group' => 'uniapp',
        'web_route_name' => null,
        'web_route_params' => [],
        'uniapp_path' => '/pages/login/index',
        'description' => null,
    ],
    'uniapp.profile' => [
        'name' => 'UniApp 个人资料',
        'module_group' => 'uniapp',
        'web_route_name' => null,
        'web_route_params' => [],
        'uniapp_path' => '/pages/user/profile',
        'description' => null,
    ],
];
```

- [ ] **Step 4: Commit**

```bash
git add app/Site/Enums config/site.php config/site-pages.php
git commit -m "feat(site): add page manifest config and navigation enums"
```

---

### Task 2: Migrations + models

**Files:**
- Create: `database/migrations/2026_05_18_100000_create_site_pages_table.php`
- Create: `database/migrations/2026_05_18_100001_create_site_navigation_items_table.php`
- Create: `database/migrations/2026_05_18_100002_create_uniapp_navigation_items_table.php`
- Create: `app/Models/SitePage.php`, `SiteNavigationItem.php`, `UniappNavigationItem.php`

- [ ] **Step 1: Write migrations** (columns per spec §2.1–2.3; `site_navigation_items.site_page_id` → `restrictOnDelete()`)

- [ ] **Step 2: Create models with casts**

```php
// app/Models/SitePage.php — fillable, casts web_route_params => array, is_system, is_active
// relations: navigationItems() hasMany SiteNavigationItem
```

```php
// app/Models/SiteNavigationItem.php
protected function casts(): array
{
    return [
        'route_params' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'link_type' => SiteNavLinkType::class,
        'placement' => SiteNavPlacement::class,
    ];
}
```

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate --no-interaction --path=database/migrations/2026_05_18_100000_create_site_pages_table.php --path=database/migrations/2026_05_18_100001_create_site_navigation_items_table.php --path=database/migrations/2026_05_18_100002_create_uniapp_navigation_items_table.php`  
Expected: `DONE`

- [ ] **Step 4: Commit**

---

### Task 3: `SitePageManifest` + `site:sync-pages` (TDD)

**Files:**
- Create: `app/Site/Support/SitePageManifest.php`
- Create: `app/Console/Commands/SyncSitePagesCommand.php`
- Create: `tests/Feature/SiteNavigationTest.php` (sync tests first)
- Modify: `routes/console.php` or rely on auto-discovery

- [ ] **Step 1: Write failing test**

```php
// tests/Feature/SiteNavigationTest.php
public function test_sync_site_pages_is_idempotent(): void
{
    $this->artisan('site:sync-pages')->assertSuccessful();
    $this->assertDatabaseCount('site_pages', count(config('site-pages')));

    $this->artisan('site:sync-pages')->assertSuccessful();
    $this->assertDatabaseCount('site_pages', count(config('site-pages')));
}

public function test_sync_updates_name_from_manifest(): void
{
    $this->artisan('site:sync-pages')->assertSuccessful();

    config(['site-pages.stays.index.name' => '住宿首页（测试）']);
    (new \App\Site\Support\SitePageManifest)->sync();

    $this->assertDatabaseHas('site_pages', [
        'key' => 'stays.index',
        'name' => '住宿首页（测试）',
    ]);
}
```

- [ ] **Step 2: Run test — expect FAIL**

Run: `php artisan test --compact --filter=test_sync_site_pages`  
Expected: FAIL (command/class missing)

- [ ] **Step 3: Implement manifest + command**

```php
<?php
// app/Site/Support/SitePageManifest.php
namespace App\Site\Support;

use App\Models\SitePage;

class SitePageManifest
{
    public function sync(): void
    {
        foreach (config('site-pages', []) as $key => $definition) {
            SitePage::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'module_group' => $definition['module_group'],
                    'web_route_name' => $definition['web_route_name'],
                    'web_route_params' => $definition['web_route_params'] ?? [],
                    'uniapp_path' => $definition['uniapp_path'],
                    'description' => $definition['description'] ?? null,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
```

```php
<?php
// app/Console/Commands/SyncSitePagesCommand.php
namespace App\Console\Commands;

use App\Site\Support\SitePageManifest;
use Illuminate\Console\Command;

class SyncSitePagesCommand extends Command
{
    protected $signature = 'site:sync-pages';
    protected $description = 'Sync site_pages from config/site-pages.php';

    public function handle(SitePageManifest $manifest): int
    {
        $manifest->sync();
        $this->info('Site pages synced.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests — expect PASS**

Run: `php artisan test --compact tests/Feature/SiteNavigationTest.php --filter=test_sync`  
Expected: PASS

- [ ] **Step 5: Commit**

---

### Task 4: `ResolvedNavItem` + `SiteNavigationResolver` (unit TDD)

**Files:**
- Create: `app/Site/Data/ResolvedNavItem.php`
- Create: `app/Site/Services/SiteNavigationResolver.php`
- Create: `tests/Unit/SiteNavigationResolverTest.php`

- [ ] **Step 1: Write failing unit tests**

```php
<?php
// tests/Unit/SiteNavigationResolverTest.php
namespace Tests\Unit;

use App\Models\SiteNavigationItem;
use App\Models\SitePage;
use App\Site\Enums\SiteNavLinkType;
use App\Site\Services\SiteNavigationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNavigationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_site_page_link(): void
    {
        $page = SitePage::factory()->create([
            'key' => 'stays.index',
            'web_route_name' => 'site.stays.index',
            'web_route_params' => ['category' => 'design'],
        ]);

        $item = SiteNavigationItem::factory()->for($page)->create([
            'link_type' => SiteNavLinkType::SitePage,
            'route_params' => null,
        ]);

        $resolved = app(SiteNavigationResolver::class)->resolve($item);

        $this->assertNotNull($resolved);
        $this->assertStringContainsString('/stays', $resolved->url);
        $this->assertStringContainsString('category=design', $resolved->url);
    }

    public function test_skips_invalid_named_route(): void
    {
        $item = SiteNavigationItem::factory()->create([
            'link_type' => SiteNavLinkType::NamedRoute,
            'route_name' => 'route.that.does.not.exist',
        ]);

        $resolved = app(SiteNavigationResolver::class)->resolve($item);

        $this->assertNull($resolved);
    }

    public function test_active_match_wildcard(): void
    {
        $resolver = app(SiteNavigationResolver::class);
        $this->assertTrue($resolver->matchesActive('site.stays.index', 'site.stays.*'));
        $this->assertFalse($resolver->matchesActive('site.me.bookings', 'site.stays.*'));
    }
}
```

- [ ] **Step 2: Add factories** (`database/factories/SitePageFactory.php`, `SiteNavigationItemFactory.php`) required by tests.

- [ ] **Step 3: Run tests — FAIL**

Run: `php artisan test --compact tests/Unit/SiteNavigationResolverTest.php`  
Expected: FAIL

- [ ] **Step 4: Implement resolver + DTO**

```php
<?php
// app/Site/Data/ResolvedNavItem.php
namespace App\Site\Data;

readonly class ResolvedNavItem
{
    public function __construct(
        public string $title,
        public string $url,
        public string $target = '_self',
        public ?string $icon = null,
        public ?string $styleVariant = null,
        public bool $isActive = false,
    ) {}
}
```

Implement `resolve(SiteNavigationItem $item): ?ResolvedNavItem` with try/catch around `route()`, `report($e)` on failure, return null.

Implement `matchesActive(?string $currentRoute, ?string $pattern): bool` — if pattern ends with `.*`, prefix match; else strict equality.

- [ ] **Step 5: Run tests — PASS**

- [ ] **Step 6: Commit**

---

### Task 5: `SiteNavigationService` + Seeder defaults

**Files:**
- Create: `app/Site/Services/SiteNavigationService.php`
- Create: `database/seeders/SiteNavigationSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` — call `SiteNavigationSeeder` after sync

- [ ] **Step 1: Feature test — header links after seed**

```php
public function test_stays_page_renders_seeded_header_links(): void
{
    $this->seed(\Database\Seeders\SiteNavigationSeeder::class);

    $this->get(route('site.stays.index'))
        ->assertOk()
        ->assertSee('住宿', false)
        ->assertSee('我的订单', false);
}

public function test_inactive_header_item_hidden(): void
{
    $this->seed(\Database\Seeders\SiteNavigationSeeder::class);

    \App\Models\SiteNavigationItem::query()
        ->where('placement', 'header')
        ->where('title', '我的订单')
        ->update(['is_active' => false]);

    app(\App\Site\Services\SiteNavigationService::class)->flushCache();

    $this->get(route('site.stays.index'))
        ->assertOk()
        ->assertDontSee('我的订单', false);
}
```

- [ ] **Step 2: Implement service**

```php
public function forPlacement(SiteNavPlacement $placement, ?string $footerGroup = null): Collection
{
    $cacheKey = 'site.nav.'.$placement->value.'.'.($footerGroup ?? 'all');

    return Cache::remember($cacheKey, config('site.navigation_cache_ttl'), function () use ($placement, $footerGroup) {
        $items = SiteNavigationItem::query()
            ->where('placement', $placement)
            ->where('is_active', true)
            ->when($footerGroup, fn ($q) => $q->where('footer_group', $footerGroup))
            ->orderBy('sort_order')
            ->with('sitePage')
            ->get();

        if ($items->isEmpty()) {
            $items = $this->defaults()->forPlacement($placement, $footerGroup);
        }

        $current = Route::currentRouteName();

        return $items
            ->map(fn ($item) => $this->resolver->resolve($item))
            ->filter()
            ->values()
            ->map(function (ResolvedNavItem $item) use ($current, $rawItem) {
                // set isActive via matchesActive on raw item's active_match
            });
    });
}

public function flushCache(): void { Cache::flush(); /* or forget by placement */ }
```

- [ ] **Step 3: Implement `SiteNavigationSeeder`** — mirror current `header.blade.php`, `footer.blade.php`, `category-strip.blade.php` entries (use `site_page` links where possible; 体验/长租 use `named_route` `site.stays.index` + `route_params` `kind`; 成为房东 `external_url` `/landlord-portal/login` with `style_variant=button` in header placement or keep as separate static CTA in header — spec: 成为房东 stays in header; seed as `external_url` item in `header` with high sort_order).

Category strip: store `icon` as keys `home`, `editor-pick`, `design`, … mapping to SVG paths in `category-strip.blade.php` via `@php $iconPaths = [...] @endphp`.

- [ ] **Step 4: Seeder calls `site:sync-pages` first**

- [ ] **Step 5: Run tests — PASS**

Run: `php artisan test --compact tests/Feature/SiteNavigationTest.php`  
Expected: PASS

- [ ] **Step 6: Commit**

---

### Task 6: View Composer + Blade (header / footer / category-strip)

**Files:**
- Create: `app/Site/View/Composers/SiteNavigationComposer.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/components/site/header.blade.php`
- Modify: `resources/views/components/site/footer.blade.php`
- Modify: `resources/views/components/site/category-strip.blade.php`
- Modify: `resources/views/components/layouts/site.blade.php` — remove `$navActive` prop dependency where replaced by resolver

- [ ] **Step 1: Register composer in `AppServiceProvider::boot`**

```php
use App\Site\View\Composers\SiteNavigationComposer;
use Illuminate\Support\Facades\View;

View::composer('components.layouts.site', SiteNavigationComposer::class);
```

- [ ] **Step 2: Composer builds `$siteNav` array**

```php
$placements = [
    SiteNavPlacement::Header,
    SiteNavPlacement::CategoryStrip,
    // footer groups loaded separately
];
$siteNav = [
    'header' => $service->forPlacement(SiteNavPlacement::Header),
    'category_strip' => $service->forPlacement(SiteNavPlacement::CategoryStrip),
    'footer' => [
        'explore' => $service->forPlacement(SiteNavPlacement::Footer, 'explore'),
        'landlord' => $service->forPlacement(SiteNavPlacement::Footer, 'landlord'),
        'support' => $service->forPlacement(SiteNavPlacement::Footer, 'support'),
    ],
];
$view->with(compact('siteNav'));
$view->with('siteNavActive', Route::currentRouteName());
```

- [ ] **Step 3: Update `header.blade.php`**

Replace hardcoded `<nav>` links with:

```blade
<nav class="hidden md:flex items-center gap-10 ...">
    @foreach ($siteNav['header'] ?? [] as $item)
        <a href="{{ $item->url }}"
           target="{{ $item->target }}"
           class="nav-link {{ $item->styleVariant === 'muted' ? 'text-ink-400' : '' }}"
           data-active="{{ $item->isActive ? 'true' : 'false' }}">{{ $item->title }}</a>
    @endforeach
</nav>
```

Keep logo + landlord CTA button area: either seed 成为房东 as nav item with `style_variant=button` rendered in `@foreach` with conditional classes, or keep static anchor if not in DB (prefer seed).

- [ ] **Step 4: Update footer + category-strip similarly**

- [ ] **Step 5: Run existing site tests**

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php`  
Expected: PASS (update assertions if copy changes)

- [ ] **Step 6: Commit**

---

### Task 7: Move views to `site/modules/*`

**Files:**
- Move: `resources/views/site/listings/index.blade.php` → `resources/views/site/modules/stays/index.blade.php`
- Move: `resources/views/site/listings/show.blade.php` → `resources/views/site/modules/stays/show.blade.php`
- Move: `resources/views/site/bookings/*` → `resources/views/site/modules/bookings/*`
- Move: `resources/views/site/me/bookings.blade.php` → `resources/views/site/modules/account/bookings.blade.php`
- Modify: `app/Http/Controllers/Site/ListingBrowseController.php`
- Modify: `app/Http/Controllers/Site/SiteGuestBookingController.php`
- Modify: `routes/web.php` — `Route::view('/me/bookings', 'site.modules.account.bookings')`

- [ ] **Step 1: Move files with `git mv`**

- [ ] **Step 2: Update controller return paths**

```php
return view('site.modules.stays.index', [...]);
return view('site.modules.stays.show', [...]);
return view('site.modules.bookings.confirmation', [...]);
return view('site.modules.bookings.show', [...]);
```

- [ ] **Step 3: Run site feature tests**

Run: `php artisan test --compact tests/Feature/SiteListingBrowseTest.php`  
Expected: PASS

- [ ] **Step 4: Commit**

---

### Task 8: Filament `SitePageResource` + `SiteNavigationItemResource`

**Files:**
- Create: `app/Filament/Resources/SitePages/*` (mirror `StoredUrls` structure)
- Create: `app/Filament/Resources/SiteNavigationItems/*`
- Create: `tests/Feature/SiteNavigationAdminTest.php`

- [ ] **Step 1: Generate resources**

Run: `php artisan make:filament-resource SitePage --no-interaction`  
Run: `php artisan make:filament-resource SiteNavigationItem --no-interaction`  
Refactor into Schemas/Tables subdirs per project convention.

- [ ] **Step 2: Configure `SitePageResource`**

- `navigationGroup = '前台'`, `navigationLabel = '页面模块'`, sort `10`
- Form: `key`, `web_route_name`, `uniapp_path` → `disabled()` on edit
- Table: key, name, module_group, is_active
- Delete disabled when `is_system`

- [ ] **Step 3: Configure `SiteNavigationItemResource`**

- Form fields per spec; `route_name` required when `link_type=named_route` with custom rule:

```php
fn (string $attribute, $value, Closure $fail) => Route::has($value) ? null : $fail('路由不存在'),
```

- `site_page_id` required when `link_type=site_page`; `Reactive`/`visible` per Filament v5 patterns
- Table: `reorderable('sort_order')`, filter by `placement`, bulk toggle `is_active`
- `afterSave` / `afterDelete` → `app(SiteNavigationService::class)->flushCache()`

- [ ] **Step 4: Filament Livewire tests**

```php
public function test_admin_cannot_save_invalid_named_route(): void
{
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(\App\Filament\Resources\SiteNavigationItems\Pages\CreateSiteNavigationItem::class)
        ->fillForm([
            'placement' => 'header',
            'title' => 'Bad',
            'link_type' => 'named_route',
            'route_name' => 'not.a.real.route',
            'is_active' => true,
            'sort_order' => 1,
            'target' => '_self',
        ])
        ->call('create')
        ->assertHasFormErrors(['route_name']);
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact tests/Feature/SiteNavigationAdminTest.php`  
Expected: PASS

- [ ] **Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

---

### Task 9: Phase 1 verification

- [ ] **Step 1: Full related test run**

Run: `php artisan test --compact tests/Unit/SiteNavigationResolverTest.php tests/Feature/SiteNavigationTest.php tests/Feature/SiteNavigationAdminTest.php tests/Feature/SiteListingBrowseTest.php`  
Expected: all PASS

- [ ] **Step 2: Manual smoke**

1. `php artisan migrate:fresh --seed` (ensure DatabaseSeeder includes SiteNavigationSeeder)
2. Visit `/stays` — header/footer/category match pre-change
3. `/admin` → 前台 → toggle one header item off → refresh `/stays`

- [ ] **Step 3: Update spec status** in `docs/superpowers/specs/2026-05-18-site-frontend-modules-navigation-design.md` → `状态: 已定稿（implementation plan: 2026-05-18-site-frontend-modules-navigation-implementation.md）`

---

## Phase 2 — Extended placements + UniApp

### Task 10: Remaining Web placements (`user_menu`, `hero`, `booking_flow`, `listing_card`)

**Files:**
- Modify: `header.blade.php` (user menu dropdown or links from `$siteNav['user_menu']`)
- Modify: `resources/views/site/modules/stays/index.blade.php` (hero CTAs from `$siteNav['hero']`)
- Modify: `resources/views/site/modules/bookings/*.blade.php` (`booking_flow`)
- Modify: `resources/views/components/site/listing-card.blade.php` (optional secondary link from `$siteNav['listing_card']` — only if seeded items exist; primary card link stays `route('site.stays.show', $listing)`)
- Extend: `SiteNavigationSeeder.php`
- Extend: `SiteNavigationComposer.php`

- [ ] **Step 1: Extend composer `$siteNav` keys**

- [ ] **Step 2: Seed defaults for new placements (minimal: 0–1 items each)**

- [ ] **Step 3: Feature test** — booking confirmation includes seeded auxiliary link text

- [ ] **Step 4: Commit**

---

### Task 11: UniApp navigation API + Filament

**Files:**
- Create: `app/Site/Enums/UniappNavPlacement.php`, `UniappNavLinkType.php`
- Create: `app/Site/Services/UniappNavigationResolver.php` (or method on shared resolver)
- Create: `app/Http/Controllers/Api/UniappNavigationController.php`
- Modify: `routes/api.php`
- Create: `app/Filament/Resources/UniappNavigationItems/*`
- Create: `database/factories/UniappNavigationItemFactory.php`
- Extend: `tests/Feature/SiteNavigationTest.php`

- [ ] **Step 1: Write failing API test**

```php
public function test_uniapp_navigation_api_returns_active_items_sorted(): void
{
    $this->seed(\Database\Seeders\SiteNavigationSeeder::class);

    \App\Models\UniappNavigationItem::factory()->create([
        'placement' => 'page_menu',
        'title' => '首页',
        'link_type' => 'site_page',
        'site_page_id' => \App\Models\SitePage::where('key', 'uniapp.index')->first()->id,
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->getJson('/api/uniapp/navigation?placement=page_menu')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['title' => '首页', 'path' => '/pages/index/index']);
}
```

- [ ] **Step 2: Implement controller + route**

```php
Route::get('/uniapp/navigation', [UniappNavigationController::class, 'index'])
    ->middleware('throttle:120,1');
```

- [ ] **Step 3: Filament `UniappNavigationItemResource`** — `navigationGroup = 前台'`; `site_page` select `whereNotNull('uniapp_path')`

- [ ] **Step 4: Run tests — PASS**

- [ ] **Step 5: Commit**

---

### Task 12: UniApp client consumption

**Files:**
- Create: `uniapp-frontend/src/api/navigation/index.ts`
- Modify: `uniapp-frontend/src/pages/index/index.vue` (fetch `page_menu` on mount)
- Modify: `uniapp-frontend/src/config.ts` (API base URL if needed)

- [ ] **Step 1: Add API helper**

```typescript
export async function fetchNavigation(placement: string) {
  const res = await uni.request({
    url: `${API_BASE}/api/uniapp/navigation`,
    data: { placement },
  });
  return res.data?.data ?? [];
}
```

- [ ] **Step 2: Render shortcuts on index page** (list of title + navigateTo path)

- [ ] **Step 3: Manual test** against `php artisan serve` (document in PR; no automated E2E required in Phase 2)

- [ ] **Step 4: Commit**

---

## Phase 3 (optional — out of scope unless requested)

- Filament preview action for resolved URL
- Draft/publish workflow
- `active_match` helper UI

---

## Spec coverage checklist (self-review)

| Spec requirement | Task |
|------------------|------|
| `site_pages` + manifest sync | Task 1–3 |
| `site_navigation_items` | Task 2, 5, 8 |
| `uniapp_navigation_items` + API | Task 11 |
| Web link types C | Task 4, 8 |
| Placements header/footer/category | Task 5–6 |
| Placements user_menu/hero/booking_flow/listing_card | Task 10 |
| No CMS | N/A (not implemented) |
| Dynamic listing/booking URLs in Blade | Task 7, 10 (listing-card note) |
| Cache + fallback | Task 5 |
| Filament 前台 group | Task 8, 11 |
| View modules migration | Task 7 |
| Tests per spec §5 | Tasks 3–5, 8, 11 |

---

## Execution handoff

Plan complete and saved to `docs/superpowers/plans/2026-05-18-site-frontend-modules-navigation-implementation.md`.

**Two execution options:**

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks  
2. **Inline Execution** — implement task-by-task in this session with checkpoints  

Which approach do you want?
