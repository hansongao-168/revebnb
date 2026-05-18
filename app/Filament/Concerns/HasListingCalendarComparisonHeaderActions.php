<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Facades\Filament;

trait HasListingCalendarComparisonHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('backToEdit')
                ->label('返回编辑房源')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->getRecord()])),
        ];

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            $actions[] = Action::make('airbnbIcalHelp')
                ->label('获取 Airbnb iCal 链接')
                ->icon('heroicon-o-link')
                ->url(fn (): string => route('docs.ics-external-calendar').'#airbnb-ical')
                ->openUrlInNewTab();
        }

        return $actions;
    }
}
