<?php

use App\Http\Controllers\LandlordMagicLoginController;
use App\Http\Controllers\TenantPanelTokenLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/tenant-admin/entry/{token}', TenantPanelTokenLoginController::class)
    ->middleware(['web', 'throttle:panel-token-entry'])
    ->name('tenant.panel.entry');

Route::get('/landlord-portal/magic/{token}', LandlordMagicLoginController::class)
    ->middleware('web')
    ->name('landlord.portal.magic');

Route::get('/', function () {
    return view('welcome');
});
