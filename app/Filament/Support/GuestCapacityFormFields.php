<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class GuestCapacityFormFields
{
    /**
     * @return array<int, Grid>
     */
    public static function schema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('max_adults')
                        ->label('最多成人')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                    TextInput::make('max_children')
                        ->label('最多儿童')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('max_infants')
                        ->label('最多婴儿')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('max_pets')
                        ->label('最多宠物')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    TextInput::make('max_guests')
                        ->label('最大接待人数（合计）')
                        ->helperText('成人+儿童合计上限；留空则仅按分项容量校验')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
