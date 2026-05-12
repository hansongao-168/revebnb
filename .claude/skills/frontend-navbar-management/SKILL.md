---
name: frontend-navbar-management
description: 为Webpack前台页面创建可管理的导航栏系统。在Filament后台实现导航栏的增删改查，前端动态加载。适用于需要管理前台导航菜单、添加删除导航项、修改导航顺序的场景。
---

# 前台导航栏管理系统

## 功能概述

创建一套完整的导航栏管理功能：
- 数据库存储导航栏数据
- Filament后台管理（CRUD）
- Webpack前端动态加载

## 项目文件

| 文件 | 路径 |
|------|------|
| 数据库迁移 | `database/migrations/2026_04_23_024555_create_navbars_table.php` |
| Model | `app/Models/Navbar.php` |
| Filament Resource | `app/Filament/Resources/NavbarResource.php` |
| Resource Pages | `app/Filament/Resources/NavbarResource/Pages/*.php` |
| API Controller | `app/Http/Controllers/NavbarController.php` |
| 路由 | `routes/web.php` |
| 前端HTML | `webpack-frontend/src/index.html` |
| 前端JS | `webpack-frontend/src/js/main.js` |
| Seeder | `database/seeders/NavbarSeeder.php` |

## 数据库表结构

```sql
navbars (
    id - 主键
    title - 导航标题
    url - 链接地址
    icon - 图标类名（可选）
    sort_order - 排序（100以上显示为按钮样式）
    is_active - 是否显示
    target - 打开方式（_self/_blank）
    created_at, updated_at
)
```

## 测试验证

### 1. API测试
```bash
curl http://localhost:8000/api/navbars
```

### 2. 访问Filament后台
```
http://localhost:8000/admin/navbars
```

### 3. 运行Seeder
```bash
php artisan db:seed --class=NavbarSeeder
```

## 特性说明

1. **排序100以上**的导航项会显示为按钮样式（紫色背景）
2. 支持拖拽排序（reorderable）
3. 支持批量切换显示状态
4. 支持筛选显示/隐藏状态

## 实施流程

### 步骤1：创建数据库迁移和Model

**1.1 创建导航栏表迁移**

```bash
php artisan make:migration create_navbars_table
```

**1.2 编辑迁移文件**
```php
// database/migrations/xxxx_create_navbars_table.php
public function up(): void
{
    Schema::create('navbars', function (Blueprint $table) {
        $table->id();
        $table->string('title');           // 导航标题
        $table->string('url');             // 链接地址
        $table->string('icon')->nullable(); // 图标（可选）
        $table->integer('sort_order')->default(0);  // 排序
        $table->boolean('is_active')->default(true); // 是否显示
        $table->string('target')->default('_self'); // 链接目标
        $table->timestamps();
    });
}
```

**1.3 创建Navbar Model**

```bash
php artisan make:model Navbar
```

```php
// app/Models/Navbar.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Navbar extends Model
{
    protected $fillable = [
        'title',
        'url',
        'icon',
        'sort_order',
        'is_active',
        'target',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // 获取已启用的导航，按排序
    public static function getActiveNavbars()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
```

**1.4 执行迁移**
```bash
php artisan migrate
```

---

### 步骤2：创建Filament Resource

```bash
php artisan make:filament-resource NavbarResource
```

**2.1 编辑 NavbarResource.php**

```php
// app/Filament/Resources/NavbarResource.php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NavbarResource\Pages;
use App\Models\Navbar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NavbarResource extends Resource
{
    protected static ?string $model = Navbar::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $navigationLabel = '导航栏管理';
    protected static ?string $modelLabel = '导航';
    protected static ?string $pluralModelLabel = '导航列表';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('导航标题')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('url')
                            ->label('链接地址')
                            ->required()
                            ->url()
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('设置')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('图标类名')
                            ->placeholder('如: heroicon-o-home')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('排序')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Select::make('target')
                            ->label('打开方式')
                            ->options([
                                '_self' => '当前窗口',
                                '_blank' => '新窗口',
                            ])
                            ->default('_self'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('是否显示')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable()
                    ->width(80),
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('链接')
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('显示')
                    ->boolean(),
                Tables\Columns\TextColumn::make('target')
                    ->label('打开方式')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === '_blank' ? '新窗口' : '当前窗口'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('显示状态'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('切换显示状态')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => !$record->is_active]))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNavbars::route('/'),
            'create' => Pages\CreateNavbar::route('/create'),
            'edit' => Pages\EditNavbar::route('/{record}/edit'),
        ];
    }
}
```

---

### 步骤3：创建API接口

**3.1 添加路由**

```php
// routes/web.php
use App\Http\Controllers\NavbarController;

Route::get('/api/navbars', [NavbarController::class, 'index'])->name('api.navbars');
```

**3.2 创建Controller**

```bash
php artisan make:controller NavbarController
```

```php
// app/Http/Controllers/NavbarController.php
<?php

namespace App\Http\Controllers;

use App\Models\Navbar;
use Illuminate\Http\JsonResponse;

class NavbarController extends Controller
{
    public function index(): JsonResponse
    {
        $navbars = Navbar::getActiveNavbars();
        
        return response()->json([
            'success' => true,
            'data' => $navbars,
        ]);
    }
}
```

---

### 步骤4：修改Webpack前端

**4.1 修改 main.js 动态加载导航栏**

```javascript
// webpack-frontend/src/js/main.js
import '../css/main.css';

async function loadNavbars() {
    try {
        const response = await fetch('/api/navbars');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            const navContainer = document.querySelector('.nav-links-container');
            if (navContainer) {
                navContainer.innerHTML = result.data.map(item => `
                    <a href="${item.url}" 
                       target="${item.target}" 
                       class="text-gray-600 hover:text-indigo-600">
                        ${item.title}
                    </a>
                `).join('');
            }
        }
    } catch (error) {
        console.error('加载导航栏失败:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadNavbars();
});
```

**4.2 修改 index.html 添加容器**

```html
<!-- 在导航栏链接容器添加 class="nav-links-container" -->
<div class="hidden md:flex items-center space-x-8 nav-links-container">
    <!-- 原有链接或留空让JS动态加载 -->
</div>
```

---

## 验证测试

### 1. 启动开发服务器

```bash
# Laravel后端
php artisan serve

# Webpack前端 (另一个终端)
cd webpack-frontend && npm run dev
```

### 2. 测试步骤

1. **访问Filament后台**：http://localhost:8000/admin/navbars
2. **添加导航项**：点击创建，填写标题、URL、排序
3. **访问前台**：http://localhost:3000
4. **验证**：确认导航栏动态加载显示

### 3. 预期结果

- Filament后台可以增删改查导航项
- 前台页面从数据库动态加载导航栏
- 排序功能正常工作
- 显示/隐藏开关有效

---

## 相关文件

- `app/Models/Navbar.php`
- `app/Filament/Resources/NavbarResource.php`
- `app/Http/Controllers/NavbarController.php`
- `routes/web.php`
- `webpack-frontend/src/index.html`
- `webpack-frontend/src/js/main.js`

## 注意事项

1. **CORS问题**：如果前后端分离部署，需要配置CORS
2. **缓存**：生产环境考虑添加缓存
3. **SEO**：动态加载可能影响SEO，可考虑SSR方案
