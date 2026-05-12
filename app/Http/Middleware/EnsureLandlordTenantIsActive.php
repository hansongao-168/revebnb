<?php

namespace App\Http\Middleware;

use App\Models\Landlord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandlordTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('landlord')->user();
        if ($user instanceof Landlord) {
            $user->loadMissing('tenant');
            if (! $user->tenant || ! $user->tenant->isActive()) {
                Auth::guard('landlord')->logout();

                return redirect('/landlord-portal/login')
                    ->with('error', __('saas.auth.middleware_tenant_inactive'));
            }
        }

        return $next($request);
    }
}
