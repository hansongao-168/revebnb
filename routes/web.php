<?php

use App\Http\Controllers\LandlordMagicLoginController;
use App\Http\Controllers\Site\BookingInquiryController;
use App\Http\Controllers\Site\ListingAvailabilityController;
use App\Http\Controllers\Site\ListingBrowseController;
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

Route::post('/stays/{listing:slug}/inquiries', [BookingInquiryController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('site.bookings.store');
