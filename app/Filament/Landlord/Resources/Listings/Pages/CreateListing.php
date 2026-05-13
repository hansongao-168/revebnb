<?php

namespace App\Filament\Landlord\Resources\Listings\Pages;

use App\Filament\Landlord\Resources\Listings\ListingResource;
use App\Models\Landlord;
use App\Models\Listing;
use App\Services\RichTextSanitizerService;
use Filament\Resources\Pages\CreateRecord;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var Landlord|null $landlord */
        $landlord = auth('landlord')->user();
        $sanitizer = app(RichTextSanitizerService::class);

        $data['tenant_id'] = $landlord?->tenant_id;
        $data['landlord_id'] = $landlord?->id;

        if (array_key_exists('description', $data)) {
            $data['description'] = $sanitizer->sanitize($data['description']);
        }

        if (array_key_exists('guest_info_html', $data)) {
            $data['guest_info_html'] = $sanitizer->sanitize($data['guest_info_html']);
        }

        return $data;
    }

    protected function afterCreate(): void
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
