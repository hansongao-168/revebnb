<?php

namespace App\Filament\Resources\SiteNavigationItems\Schemas;

use App\Models\SitePage;
use App\Site\Enums\SiteNavLinkType;
use App\Site\Enums\SiteNavPlacement;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;

class SiteNavigationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('placement')
                    ->label('展示区域')
                    ->options(collect(SiteNavPlacement::cases())->mapWithKeys(
                        fn (SiteNavPlacement $case) => [$case->value => $case->value],
                    ))
                    ->required()
                    ->native(false),
                Select::make('footer_group')
                    ->label('页脚分组')
                    ->options([
                        'explore' => '探索',
                        'landlord' => '房东',
                        'support' => '支持',
                    ])
                    ->visible(fn (Get $get): bool => $get('placement') === SiteNavPlacement::Footer->value)
                    ->nullable()
                    ->native(false),
                TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(100),
                Select::make('link_type')
                    ->label('链接类型')
                    ->options(collect(SiteNavLinkType::cases())->mapWithKeys(
                        fn (SiteNavLinkType $case) => [$case->value => $case->value],
                    ))
                    ->required()
                    ->live()
                    ->native(false),
                Select::make('site_page_id')
                    ->label('页面模块')
                    ->options(fn (): array => SitePage::query()->where('is_active', true)->orderBy('key')->pluck('name', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::SitePage->value)
                    ->required(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::SitePage->value),
                TextInput::make('route_name')
                    ->label('命名路由')
                    ->visible(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::NamedRoute->value)
                    ->required(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::NamedRoute->value)
                    ->rule(fn (): \Closure => function (string $attribute, ?string $value, \Closure $fail): void {
                        if ($value !== null && $value !== '' && ! Route::has($value)) {
                            $fail('路由不存在。');
                        }
                    }),
                TextInput::make('route_params')
                    ->label('路由参数 JSON')
                    ->helperText('例如 {"category":"design"}')
                    ->visible(fn (Get $get): bool => in_array($get('link_type'), [SiteNavLinkType::SitePage->value, SiteNavLinkType::NamedRoute->value], true))
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : $state)
                    ->dehydrateStateUsing(fn ($state) => $state ? json_decode((string) $state, true) : null),
                TextInput::make('external_url')
                    ->label('外链或站内路径')
                    ->visible(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::ExternalUrl->value)
                    ->required(fn (Get $get): bool => $get('link_type') === SiteNavLinkType::ExternalUrl->value)
                    ->maxLength(2048),
                TextInput::make('icon')
                    ->label('图标（分类条 SVG path）')
                    ->visible(fn (Get $get): bool => $get('placement') === SiteNavPlacement::CategoryStrip->value)
                    ->maxLength(500),
                TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('target')
                    ->label('打开方式')
                    ->options(['_self' => '当前窗口', '_blank' => '新窗口'])
                    ->default('_self')
                    ->required()
                    ->native(false),
                TextInput::make('style_variant')
                    ->label('样式')
                    ->placeholder('default / muted / button'),
                TextInput::make('active_match')
                    ->label('高亮匹配')
                    ->placeholder('site.stays.*'),
                Toggle::make('is_active')
                    ->label('显示')
                    ->default(true),
            ]);
    }
}
