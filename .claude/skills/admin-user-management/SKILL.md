---
name: admin-user-management
description: Manage frontend users through Filament admin panel in Laravel. Create UserResource, user CRUD pages, filters, actions, and role-based access control. Use when building Filament admin user management, user listing, user editing, or any Filament Resource for user administration.
---

# Admin User Management (Filament)

在 Filament 后台管理面板中管理前台用户。

## 项目上下文

- **框架**: Laravel 13 + Filament 5
- **后台路径**: `/admin`
- **User Model**: `app/Models/User.php`
- **Filament Resources**: `app/Filament/Resources/`
- **当前 User 字段**: name, email, password, email_verified_at
- **数据库**: SQLite

## 快速开始

### 1. 创建 UserResource

```bash
php artisan make:filament-resource User --generate
```

这会生成:
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/UserResource/Pages/CreateUser.php`
- `app/Filament/Resources/UserResource/Pages/EditUser.php`
- `app/Filament/Resources/UserResource/Pages/ListUsers.php`

### 2. 扩展 User Model

添加前台用户所需字段:

```php
// app/Models/User.php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'status' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
}
```

### 3. 创建迁移

```bash
php artisan make:migration add_frontend_fields_to_users_table
```

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable()->after('email');
    $table->string('avatar')->nullable()->after('phone');
    $table->string('wechat_openid')->nullable()->after('avatar');
    $table->tinyInteger('gender')->default(0)->after('wechat_openid');
    $table->tinyInteger('status')->default(1)->after('gender');
    $table->boolean('is_admin')->default(false)->after('status');
});
```

## UserResource 核心配置

### 列表页 (ListUsers)

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->searchable(),
            ImageColumn::make('avatar')->circular(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    1 => 'success',
                    0 => 'danger',
                    default => 'warning',
                })
                ->formatStateUsing(fn (int $state): string => match ($state) {
                    1 => '正常',
                    0 => '禁用',
                    default => '未知',
                }),
            TextColumn::make('created_at')->dateTime('Y-m-d H:i')->sortable(),
        ])
        ->filters([
            SelectFilter::make('status')
                ->options([1 => '正常', 0 => '禁用']),
            SelectFilter::make('gender')
                ->options([0 => '未知', 1 => '男', 2 => '女']),
            TernaryFilter::make('is_admin')
                ->label('管理员'),
        ])
        ->actions([
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('disable')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['status' => 0])),
                Action::make('enable')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['status' => 1])),
            ]),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('disable')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 0])),
                BulkAction::make('enable')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 1])),
            ]),
        ]);
}
```

### 表单 (Form)

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('基本信息')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel()->maxLength(11),
                    DateTimePicker::make('email_verified_at'),
                ])->columns(2),

            Section::make('安全')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->minLength(8)
                        ->same('passwordConfirmation'),
                    TextInput::make('passwordConfirmation')
                        ->password()
                        ->required(fn (string $context): bool => $context === 'create')
                        ->minLength(8)
                        ->dehydrated(false),
                ])->columns(2),

            Section::make('个人资料')
                ->schema([
                    FileUpload::make('avatar')->image()->avatarEditor(),
                    Select::make('gender')->options([0 => '未知', 1 => '男', 2 => '女']),
                ])->columns(2),

            Section::make('管理')
                ->schema([
                    Toggle::make('is_admin')->label('管理员'),
                    Select::make('status')->options([1 => '正常', 0 => '禁用'])->default(1),
                ])->columns(2),
        ]);
}
```

## 常用模式

### 区分前后台用户

```php
// UserResource 中仅显示前台用户
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('is_admin', false);
}

// 或在 Model 中定义 scope
public function scopeFrontend(Builder $query): Builder
{
    return $query->where('is_admin', false);
}
```

### 统计 Widget

```bash
php artisan make:filament-widget UserStats --stats-overview
```

```php
protected function getStats(): array
{
    return [
        Stat::make('总用户数', User::count()),
        Stat::make('活跃用户', User::where('status', 1)->count()),
        Stat::make('今日新增', User::whereDate('created_at', today())->count()),
        Stat::make('管理员', User::where('is_admin', true)->count()),
    ];
}
```

### 用户详情页

```bash
php artisan make:filament-resource User --view
```

```php
// In UserResource schema
Infolist::make()
    ->schema([
        TextEntry::make('name'),
        TextEntry::make('email'),
        ImageEntry::make('avatar')->circular(),
        TextEntry::make('phone'),
        TextEntry::make('status')->badge()->color(...),
        TextEntry::make('created_at')->dateTime(),
    ]);
```

## 最佳实践

1. **密码处理**: 编辑时不强制要求密码,仅填写时才更新
2. **软删除**: 使用 `SoftDeletes` 而非物理删除
3. **权限控制**: 使用 `canAccessPanel()` 限制后台访问
4. **批量操作**: 提供启用/禁用批量操作
5. **搜索优化**: 对 name, email, phone 启用 searchable

## 完整代码参考

详见 [REFERENCE.md](REFERENCE.md)

## 相关文件

- User Model: [User.php](file:///var/www/laravel13x/app/Models/User.php)
- Admin Panel: [AdminPanelProvider.php](file:///var/www/laravel13x/app/Providers/Filament/AdminPanelProvider.php)
- 迁移: [create_users_table.php](file:///var/www/laravel13x/database/migrations/0001_01_01_000000_create_users_table.php)
