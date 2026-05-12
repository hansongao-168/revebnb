---
name: laravel-filament-feature-v1
description: |
  Add new features to Laravel Filament admin panels. Use this skill whenever you need to:
  - Create a new Filament page (custom pages, not model-based resources)
  - Create a new Filament resource (CRUD for a model)
  - Add a custom widget to the dashboard or a resource
  - Create reusable Filament actions
  - Add navigation items, modify the sidebar structure
  - Work with Filament 5.x patterns, Page classes, Resource classes, Widgets, Actions, and form/table schemas

  This skill is for building new Filament functionality, not for fixing bugs in existing code. Trigger when user mentions Filament admin, admin panel, admin pages, dashboard widgets, or wants to add any CRUD/resource to the admin.
---

# Laravel Filament Feature Development

This skill guides you through building features for Filament 5.x admin panels.

## Determine Feature Type

First, understand what type of feature the user needs:

| Feature Type | When to Use | File Location |
|--------------|-------------|---------------|
| **Custom Page** | System tools, settings pages, reports | `app/Filament/Pages/` |
| **Resource** | CRUD operations for a model | `app/Filament/Resources/<Model>/` |
| **Widget** | Dashboard stats, charts, metrics | `app/Filament/Widgets/` |
| **Action** | Reusable buttons (export, import, custom) | `app/Filament/Actions/` |

## Key Conventions

### Filament 5.x Property Types

```php
// Static properties - use proper union types
protected static ?string $title = '...';
protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-...';
protected static string|UnitEnum|null $navigationGroup = '分组名称';
protected static ?int $navigationSort = 100;

// Non-static properties
protected ?string $heading = null;
```

### Page Discovery

The `AdminPanelProvider` auto-discovers pages from `app_path('Filament/Pages')`:
```php
->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
```

**No manual registration needed** — just create the file and it appears in the navigation.

### Resource Auto-Discovery

Resources are auto-discovered similarly:
```php
->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
```

### Navigation Groups

Use `getNavigationGroup()` method or static property:
```php
public static function getNavigationGroup(): string
{
    return '系统工具'; // Or use UnitEnum for existing groups
}
```

### Blade View Path

For custom pages, return the view path without `.blade.php`:
```php
public function getView(): string
{
    return 'filament.pages.cache-management';
}
```

Place blade files in `resources/views/filament/pages/`.

## Building a Custom Page (Standalone)

Use when you need a page that doesn't manage an Eloquent model (e.g., settings, cache management, reports).

### File Structure
```
app/Filament/Pages/<PageName>Page.php
resources/views/filament/pages/<page-name>.blade.php
```

### Page Class Pattern
```php
<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class <PageName>Page extends Page
{
    protected static ?string $title = '页面标题';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationLabel = '导航标签';

    protected static ?int $navigationSort = 100;

    protected static string|UnitEnum|null $navigationGroup = '导航分组';

    // Optional: hide from navigation
    // public static function shouldShowInNavigation(): bool { return false; }

    protected CacheManagementService $myService;

    public function mount(): void
    {
        $this->myService = app(MyService::class);
    }

    public function getView(): string
    {
        return 'filament.pages.page-name';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('actionName')
                ->label('操作名称')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('确认操作')
                ->modalDescription('此操作不可撤销')
                ->action(function () {
                    // Perform action
                    Notification::make()
                        ->title('成功')
                        ->body('操作完成')
                        ->success()
                        ->send();
                }),
        ];
    }
}
```

## Building a Service for Filament

Services encapsulate business logic and are injected into pages/actions.

### Service Pattern
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class MyService
{
    public function doSomething(): array
    {
        try {
            // Business logic
            return ['success' => true, 'message' => 'Done'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
```

## Blade View Pattern for Pages

```blade
<x-filament-panels::page>
    {{-- Use Sections for layout --}}
    <x-filament::section>
        <x-slot name="heading">
            标题
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Content --}}
        </div>
    </x-filament::section>

    {{-- Another section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            另一个标题
        </x-slot>

        @foreach($items as $item)
            <div class="p-4 bg-white border rounded-lg mb-2">
                {{ $item->name }}
            </div>
        @endforeach
    </x-filament::section>
</x-filament-panels::page>
```

## Adding Actions to Resources

### Reusable Action Pattern
```php
<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;

class MyAction
{
    public static function make(string $param): Action
    {
        return Action::make('myAction')
            ->label('操作名称')
            ->icon('heroicon-o-pencil')
            ->color('info')
            ->form([/* form components */])
            ->action(function (array $data, $livewire) {
                // Handle action
            })
            ->modalHeading('确认')
            ->modalSubmitActionLabel('确认');
    }
}
```

Use in resource:
```php
protected function getHeaderActions(): array
{
    return [
        MyAction::make($someParam),
    ];
}
```

## Common Patterns

### Notification Types
```php
Notification::make()
    ->title('成功')
    ->body('message')
    ->success()    // green
    ->warning()    // yellow
    ->danger()     // red
    ->info()       // blue
    ->send();
```

### Artisan Commands in Service
```php
Artisan::call('cache:clear');
Artisan::call('config:clear');
Artisan::call('route:clear');
Artisan::call('view:clear');
Artisan::call('optimize:clear');
```

## Testing the Feature

1. Clear caches: `php artisan optimize:clear`
2. Start server: `php artisan serve --port=5173`
3. Navigate to the page at `/admin/<page-url>`
4. Test all actions and verify notifications appear

## Common Issues

### Property Type Errors
If you get "Type of X must be Y", check the exact union type required:
- `$navigationIcon` needs `string|BackedEnum|null`
- `$navigationGroup` needs `string|UnitEnum|null`

### Static vs Non-Static
- Most properties are `static`
- `$heading`, `$subheading` are **non-static**

### View Not Found
Ensure blade file exists at `resources/views/filament/pages/<name>.blade.php` and `getView()` returns `filament.pages.<name>` without `.blade.php`.
