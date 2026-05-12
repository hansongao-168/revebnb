<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Models\Listing;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->label('城市')
                    ->maxLength(120),
                TextInput::make('address')
                    ->label('地址')
                    ->maxLength(255),
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
                FileUpload::make('cover_image')
                    ->label('封面图')
                    ->image()
                    ->disk('public')
                    ->directory('listings')
                    ->visibility('public'),
                DateTimePicker::make('published_at')
                    ->label('发布时间'),
            ]);
    }
}
