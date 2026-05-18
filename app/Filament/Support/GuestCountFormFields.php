<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class GuestCountFormFields
{
    /**
     * @return array<int, Grid>
     */
    public static function schema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('guest_adults')
                        ->label('成人')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    TextInput::make('guest_children')
                        ->label('儿童')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('guest_infants')
                        ->label('婴儿')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('guest_pets')
                        ->label('宠物')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('guests')
                        ->label('接待人数合计')
                        ->helperText('成人+儿童，保存订单时可自动计算')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
