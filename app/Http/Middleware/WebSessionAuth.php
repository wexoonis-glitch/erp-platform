<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WebSessionAuth
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function handle(Request $request, Closure $next): Response
    {
        $guard      = Auth::guard('web');
        $sessionKey = $guard->getName();
        $userId     = session()->get($sessionKey);

        // Kullanıcı yoksa
        if (!$userId) {
            // Livewire update route üzerinden login/guest component çalışıyor olabilir
            // Bu durumda devam et — component kendisi Auth kontrolü yapar
            if ($request->is('livewire/update')) {
                return $next($request);
            }
            return redirect()->route('login');
        }

        // Kullanıcıyı superadmin bağlamında çöz
        $user = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => User::find($userId),
            superadmin: true
        );

        if (!$user || !$user->isActive()) {
            session()->forget($sessionKey);
            if ($request->is('livewire/update')) {
                return response()->json(['message' => 'Session expired'], 401);
            }
            return redirect()->route('login');
        }

        $guard->setUser($user);

        // Doğru tenant bağlamına bind et
        if ($user->isSuperadmin()) {
            TenantContext::bind(self::NULL_TENANT, true);
        } else {
            if (!$user->tenant_id) {
                abort(403, 'Kullanıcı bir tenant\'a bağlı değil.');
            }
            TenantContext::bind($user->tenant_id, false);
        }

        try {
            return $next($request);
        } finally {
            TenantContext::unbind();
        }
    }
}
