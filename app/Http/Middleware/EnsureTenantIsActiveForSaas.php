<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActiveForSaas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('saas')->user();
        if ($user && $user->tenant && ! $user->tenant->isActive()) {
            Auth::guard('saas')->logout();

            return redirect('/tenant-admin/login')
                ->with('error', '组织已停用，请联系平台。');
        }

        return $next($request);
    }
}
