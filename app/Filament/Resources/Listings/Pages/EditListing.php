<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Resources\Listings\ListingResource;
use App\Models\Listing;
use App\Services\RichTextSanitizerService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendarComparison')
                ->label('日历对比')
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => ListingResource::getUrl('calendar', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $sanitizer = app(RichTextSanitizerService::class);

        if (array_key_exists('description', $data)) {
            $data['description'] = $sanitizer->sanitize($data['description']);
        }

        if (array_key_exists('guest_info_html', $data)) {
            $data['guest_info_html'] = $sanitizer->sanitize($data['guest_info_html']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->normalizeCoverImages();
    }

    private function normalizeCoverImages(): void
    {
        /** @var Listing $listing */
        $listing = $this->record;

        $images = $listing->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($images->isEmpty()) {
            return;
        }

        $coverImages = $images->filter(fn ($image): bool => (bool) $image->is_cover)->values();

        if ($coverImages->isEmpty()) {
            $firstImage = $images->first();
            if ($firstImage !== null) {
                $firstImage->is_cover = true;
                $firstImage->saveQuietly();
            }

            return;
        }

        $coverToKeep = $coverImages->first();

        $coverImages
            ->skip(1)
            ->each(function ($image): void {
                $image->is_cover = false;
                $image->saveQuietly();
            });

        if (! $coverToKeep->is_cover) {
            $coverToKeep->is_cover = true;
            $coverToKeep->saveQuietly();
        }
    }
}
