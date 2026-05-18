<?php

use App\Http\Controllers\LandlordMagicLoginController;
use App\Http\Controllers\Site\ListingAvailabilityController;
use App\Http\Controllers\Site\ListingBrowseController;
use App\Http\Controllers\Site\SiteGuestBookingController;
use App\Http\Controllers\TenantPanelTokenLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/tenant-admin/entry/{token}', TenantPanelTokenLoginController::class)
    ->middleware(['web', 'throttle:panel-token-entry'])
    ->name('tenant.panel.entry');

Route::get('/landlord-portal/magic/{token}', LandlordMagicLoginController::class)
    ->middleware('web')
    ->name('landlord.portal.magic');

Route::redirect('/', '/stays');

Route::get('/stays', [ListingBrowseController::class, 'index'])->name('site.stays.index');

Route::get('/stays/{listing:slug}/availability', ListingAvailabilityController::class)
    ->middleware(['throttle:120,1'])
    ->name('site.stays.availability');

Route::get('/stays/{listing:slug}', [ListingBrowseController::class, 'show'])
    ->name('site.stays.show');

Route::post('/stays/{listing:slug}/bookings', [SiteGuestBookingController::class, 'store'])
    ->middleware(['throttle:12,1'])
    ->name('site.bookings.store');

Route::get('/bookings/{booking}/confirmation', [SiteGuestBookingController::class, 'confirmation'])
    ->middleware(['throttle:30,1'])
    ->name('site.bookings.confirmation');

Route::get('/bookings/{booking}', [SiteGuestBookingController::class, 'show'])
    ->middleware(['throttle:60,1'])
    ->name('site.bookings.show');

Route::view('/me/bookings', 'site.modules.account.bookings')
    ->middleware(['throttle:60,1'])
    ->name('site.me.bookings');

Route::view('/docs/stored-urls-intro', 'docs.stored-urls-intro')
    ->middleware(['throttle:30,1'])
    ->name('docs.stored-urls-intro');

Route::get('/docs/stored-urls-intro.pdf', function () {
    $path = public_path('docs/stored-urls-intro.pdf');

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
    ]);
})->middleware(['throttle:30,1'])->name('docs.stored-urls-intro-pdf');
