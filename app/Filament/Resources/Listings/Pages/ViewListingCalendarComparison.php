<?php

namespace App\Filament\Resources\Listings\Pages;

use App\Filament\Concerns\HasListingCalendarComparisonHeaderActions;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\Listing;
use App\Services\ListingCalendarComparisonService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewListingCalendarComparison extends Page
{
    use HasListingCalendarComparisonHeaderActions;
    use InteractsWithRecord;

    protected static string $resource = ListingResource::class;

    protected static ?string $title = '日历对比';

    protected string $view = 'filament.resources.listings.pages.view-listing-calendar-comparison';

    public string $month;

    /** @var array<string, mixed> */
    public array $comparison = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $requested = request()->query('month');
        $this->month = is_string($requested) && preg_match('/^\d{4}-\d{2}$/', $requested)
            ? $requested
            : now()->format('Y-m');

        $this->loadComparison();
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Listing $listing */
        $listing = $this->getRecord();

        return '日历对比 · '.$listing->title;
    }

    private function loadComparison(): void
    {
        /** @var Listing $listing */
        $listing = $this->getRecord();

        $this->comparison = app(ListingCalendarComparisonService::class)
            ->build($listing, $this->month);
    }
}
