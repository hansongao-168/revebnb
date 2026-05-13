<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Models\Listing;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('关联租户')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('URL 标识')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->alphaDash(),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        Listing::STATUS_DRAFT => '草稿',
                        Listing::STATUS_PUBLISHED => '已发布',
                        Listing::STATUS_ARCHIVED => '已下架',
                    ])
                    ->required()
                    ->default(Listing::STATUS_DRAFT)
                    ->native(false),
                Select::make('landlord_id')
                    ->label('房东')
                    ->relationship(
                        'landlord',
                        'name',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query->when(
                            $get('tenant_id'),
                            fn (Builder $tenantQuery) => $tenantQuery->where('tenant_id', $get('tenant_id')),
                        ),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('min_nights')
                    ->label('最低预定晚数')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1),
                TextInput::make('max_guests')
                    ->label('最大接待人数')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
                RichEditor::make('description')
                    ->label('描述')
                    ->columnSpanFull(),
                RichEditor::make('guest_info_html')
                    ->label('客人展示说明')
                    ->columnSpanFull()
                    ->nullable(),
                TextInput::make('city')
                    ->label('城市')
                    ->maxLength(120),
                TextInput::make('address')
                    ->label('地址')
                    ->maxLength(255),
                Repeater::make('images')
                    ->label('图片')
                    ->relationship('images')
                    ->schema([
                        FileUpload::make('path')
                            ->label('图片')
                            ->image()
                            ->disk('public')
                            ->directory('listings')
                            ->visibility('public')
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->label('排序'),
                        Toggle::make('is_cover')
                            ->label('封面')
                            ->inline(false),
                    ])
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ->collapsible(),
                TextInput::make('nightly_price')
                    ->label('每晚价格')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0)
                    ->suffix(fn ($get) => $get('currency') ?: 'CNY'),
                TextInput::make('currency')
                    ->label('货币')
                    ->length(3)
                    ->default('CNY')
                    ->maxLength(3),
                DateTimePicker::make('published_at')
                    ->label('发布时间'),
            ]);
    }
}
