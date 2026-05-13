<?php

namespace App\Providers;

use App\Contracts\PanelTokenNotifier;
use App\Services\MailPanelTokenNotifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PanelTokenNotifier::class, MailPanelTokenNotifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('panel-token-entry', function (Request $request): Limit {
            $token = (string) $request->route('token', '');

            return Limit::perMinute(20)->by($request->ip().'|'.sha1($token));
        });
    }
}
