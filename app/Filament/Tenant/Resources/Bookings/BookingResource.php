<?php

namespace App\Filament\Tenant\Resources\Bookings;

use App\Filament\Tenant\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Tenant\Resources\Bookings\Pages\EditBooking;
use App\Filament\Tenant\Resources\Bookings\Pages\ListBookings;
use App\Filament\Tenant\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Tenant\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Models\SaasUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationLabel = '订单';

    protected static ?string $modelLabel = '订单';

    protected static ?string $pluralModelLabel = '订单';

    protected static string|UnitEnum|null $navigationGroup = '租房';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var SaasUser|null $saasUser */
        $saasUser = auth('saas')->user();

        return parent::getEloquentQuery()
            ->whereHas('listing', function (Builder $query) use ($saasUser): void {
                $query->where('tenant_id', $saasUser?->tenant_id);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
