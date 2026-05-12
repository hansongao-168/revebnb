<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Services\LandlordAccessRenewalService;
use App\Services\LandlordTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordMagicLoginController extends Controller
{
    public function __construct(
        protected LandlordTokenService $tokens,
        protected LandlordAccessRenewalService $renewal,
    ) {}

    public function __invoke(string $token, Request $request): RedirectResponse|View
    {
        $row = $this->tokens->findValidTokenRowByPlain($token);

        if ($row) {
            /** @var Landlord|null $landlord */
            $landlord = Landlord::query()->with('tenant')->find($row->landlord_id);

            if (! $landlord || $landlord->status !== Landlord::STATUS_ACTIVE || ! ($landlord->tenant?->isActive() ?? false)) {
                return view('landlord.magic-login-failed');
            }

            Auth::guard('landlord')->login($landlord, remember: false);
            $request->session()->regenerate();

            return redirect()->intended('/landlord-portal');
        }

        if ($this->renewal->tryRenewFromExpiredMagicPlain($token)) {
            return view('landlord.magic-login-renewed');
        }

        return view('landlord.magic-login-failed');
    }
}
