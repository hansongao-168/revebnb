<?php

namespace App\Filament\Landlord\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class LandlordPortalLoginHint extends Login
{
    public function mount(): void
    {
        if ($message = session()->pull('error')) {
            Notification::make()
                ->title($message)
                ->danger()
                ->persistent()
                ->send();
        }

        parent::mount();
    }

    public function authenticate(): ?LoginResponse
    {
        throw ValidationException::withMessages([
            'data.email' => '请使用邮件中的入口链接登录。',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return '房东控制台';
    }

    public function getHeading(): string|Htmlable|null
    {
        return '登录';
    }
}
