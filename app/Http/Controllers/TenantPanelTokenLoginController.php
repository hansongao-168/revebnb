<?php

namespace App\Http\Controllers;

use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantPanelTokenLoginController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $hash = hash('sha256', $token);

        $record = SaasPanelLoginToken::query()
            ->where('token_hash', $hash)
            ->first();

        if ($record === null || $record->revoked_at !== null || $record->expires_at->isPast()) {
            return redirect()->route('filament.tenant.auth.login')
                ->with('error', '链接无效或已过期，请使用有效链接或联系平台。');
        }

        /** @var SaasUser|null $user */
        $user = $record->saasUser()->with('tenant')->first();

        if ($user === null || (int) $user->status !== 1 || $user->tenant === null || ! $user->tenant->isActive()) {
            return redirect()->route('filament.tenant.auth.login')
                ->with('error', '链接无效或已过期，请使用有效链接或联系平台。');
        }

        Auth::guard('saas')->login($user, remember: false);
        $request->session()->regenerate();

        $record->forceFill(['last_used_at' => now()])->save();

        return redirect()->intended(route('filament.tenant.pages.dashboard'));
    }
}
