# Admin User Management - 详细实现参考

## 完整 UserResource

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    protected static ?string $navigationGroup = '用户中心';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->description('用户的基本账户信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('昵称')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('phone')
                            ->label('手机号')
                            ->tel()
                            ->maxLength(11)
                            ->rules(['regex:/^1[3-9]\d{9}$/'])
                            ->validationMessages([
                                'regex' => '请输入正确的手机号',
                            ])
                            ->columnSpan(1),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('邮箱验证时间')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('安全设置')
                    ->description('密码和账户安全')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->same('passwordConfirmation')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->label('确认密码')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('个人资料')
                    ->description('用户的个人资料信息')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('头像')
                            ->image()
                            ->avatarEditor()
                            ->imageEditor()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->columnSpan(1),
                        Forms\Components\Select::make('gender')
                            ->label('性别')
                            ->options([
                                0 => '未知',
                                1 => '男',
                                2 => '女',
                            ])
                            ->default(0)
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('管理设置')
                    ->description('账户状态和管理权限')
                    ->schema([
                        Forms\Components\Toggle::make('is_admin')
                            ->label('管理员')
                            ->default(false)
                            ->helperText('管理员可以访问后台面板')
                            ->columnSpan(1),
                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options([
                                1 => '正常',
                                0 => '禁用',
                            ])
                            ->default(1)
                            ->required()
                            ->columnSpan(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular()
                    ->defaultImageUrl(fn () => asset('images/default-avatar.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label('昵称')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('邮箱已复制'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('手机号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('性别')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'info',
                        2 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => '男',
                        2 => '女',
                        default => '未知',
                    }),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('已验证')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark'),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        0 => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => '正常',
                        0 => '禁用',
                        default => '未知',
                    }),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('管理员')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        1 => '正常',
                        0 => '禁用',
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('性别')
                    ->options([
                        0 => '未知',
                        1 => '男',
                        2 => '女',
                    ]),
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('管理员'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('邮箱验证')
                    ->nullable()
                    ->placeholder('全部')
                    ->trueLabel('已验证')
                    ->falseLabel('未验证'),
                Tables\Filters\Filter::make('created_at')
                    ->label('注册时间')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('开始日期'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('结束日期'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('disable')
                        ->label('禁用')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('禁用用户')
                        ->modalDescription('确定要禁用此用户吗？禁用后用户将无法登录。')
                        ->visible(fn (User $record): bool => $record->status === 1)
                        ->action(function (User $record) {
                            $record->update(['status' => 0]);
                            Notification::make()
                                ->title('用户已禁用')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('enable')
                        ->label('启用')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('启用用户')
                        ->modalDescription('确定要启用此用户吗？')
                        ->visible(fn (User $record): bool => $record->status === 0)
                        ->action(function (User $record) {
                            $record->update(['status' => 1]);
                            Notification::make()
                                ->title('用户已启用')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('verify_email')
                        ->label('验证邮箱')
                        ->icon('heroicon-o-check-badge')
                        ->color('info')
                        ->visible(fn (User $record): bool => $record->email_verified_at === null)
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $record->update(['email_verified_at' => now()]);
                            Notification::make()
                                ->title('邮箱已验证')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('disable')
                        ->label('批量禁用')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['status' => 0]);
                            Notification::make()
                                ->title("已禁用 {$records->count()} 个用户")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('enable')
                        ->label('批量启用')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['status' => 1]);
                            Notification::make()
                                ->title("已启用 {$records->count()} 个用户")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', false);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
```

## 完整 User Model

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'avatar',
    'wechat_openid',
    'gender',
    'status',
    'is_admin',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'status' => 'integer',
            'gender' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->status === 1;
    }

    public function scopeFrontend($query)
    {
        return $query->where('is_admin', false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
```

## 数据库迁移

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('wechat_openid')->nullable()->unique()->after('avatar');
            $table->tinyInteger('gender')->default(0)->comment('0未知 1男 2女')->after('wechat_openid');
            $table->tinyInteger('status')->default(1)->comment('1正常 0禁用')->after('gender');
            $table->boolean('is_admin')->default(false)->after('status');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'wechat_openid',
                'gender', 'status', 'is_admin', 'deleted_at',
            ]);
        });
    }
};
```

## 用户统计 Widget

```php
<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('总用户数', User::frontend()->count())
                ->description('所有注册用户')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart(
                    User::frontend()
                        ->where('created_at', '>=', now()->subDays(7))
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->pluck('count')
                        ->toArray()
                ),
            Stat::make('活跃用户', User::frontend()->active()->count())
                ->description('状态正常的用户')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('今日新增', User::frontend()
                ->whereDate('created_at', today())
                ->count())
                ->description('今天注册的用户')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('info'),
            Stat::make('禁用用户', User::frontend()->where('status', 0)->count())
                ->description('被禁用的用户')
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color('danger'),
        ];
    }
}
```

## 创建管理员 Seeder

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'is_admin' => true,
                'status' => 1,
            ]
        );
    }
}
```

运行:
```bash
php artisan db:seed --class=AdminUserSeeder
```

## Filament Resource Pages

### ListUsers

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

### ViewUser

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
```

## 常见问题

### Q: 如何只显示前台用户?
A: 在 UserResource 中重写 `getEloquentQuery()` 过滤 `is_admin = false`。

### Q: 如何防止删除管理员?
A: 使用 `->visible(fn (User $record) => !$record->is_admin)` 限制删除按钮。

### Q: 如何添加用户导出?
A: 安装 `filament/actions` 后添加 `ExportAction::make()`。

### Q: 如何实现角色权限?
A: 使用 `filament/spatie-laravel-permission-plugin` 或自定义 Policy。
