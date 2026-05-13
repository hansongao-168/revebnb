<?php

namespace App\Filament\Landlord\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Models\Landlord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();

        return $schema
            ->components([
                Select::make('listing_id')
                    ->label('房源')
                    ->relationship(
                        'listing',
                        'title',
                        modifyQueryUsing: fn (Builder $query) => $query->where('landlord_id', $landlord?->id),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('check_in')
                    ->label('入住日期')
                    ->required(),
                DatePicker::make('check_out')
                    ->label('离店日期')
                    ->required(),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        BookingStatus::Draft->value => '草稿',
                        BookingStatus::Pending->value => '待确认',
                        BookingStatus::Confirmed->value => '已确认',
                        BookingStatus::Cancelled->value => '已取消',
                    ])
                    ->required()
                    ->default(BookingStatus::Draft->value)
                    ->native(false),
                TextInput::make('guest_name')
                    ->label('入住人')
                    ->maxLength(255)
                    ->nullable(),
                Textarea::make('notes')
                    ->label('备注')
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
