<?php

use App\Http\Controllers\LandlordMagicLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/landlord-portal/magic/{token}', LandlordMagicLoginController::class)
    ->where('token', '[a-f0-9]{48}');
