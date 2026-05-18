<?php

namespace App\Site\View\Composers;

use App\Site\Enums\SiteNavPlacement;
use App\Site\Services\SiteNavigationService;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SiteNavigationComposer
{
    public function __construct(
        private readonly SiteNavigationService $navigation,
    ) {}

    public function compose(View $view): void
    {
        $view->with('siteNav', [
            'header' => $this->navigation->forPlacement(SiteNavPlacement::Header),
            'category_strip' => $this->navigation->forPlacement(SiteNavPlacement::CategoryStrip),
            'footer' => [
                'explore' => $this->navigation->forPlacement(SiteNavPlacement::Footer, 'explore'),
                'landlord' => $this->navigation->forPlacement(SiteNavPlacement::Footer, 'landlord'),
                'support' => $this->navigation->forPlacement(SiteNavPlacement::Footer, 'support'),
            ],
        ]);

        $view->with('siteNavActive', Route::currentRouteName());
    }
}
