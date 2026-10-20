<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithTenant
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function handle(Request $request, Closure $next): Response
    {
        // 1) Bearer token var mı?
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'error'   => 'unauthenticated',
                'message' => 'Bearer token gerekli.',
            ], 401);
        }

        // 2) Token'ı bul (personal_access_tokens tablosunda RLS yok, sorunsuz)
        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            return response()->json([
                'error'   => 'unauthenticated',
                'message' => 'Geçersiz veya iptal edilmiş token.',
            ], 401);
        }

        // 3) User'ı superadmin bağlamında çöz (users tablosu RLS'li)
        $user = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => $accessToken->tokenable,
            superadmin: true
        );

        if (!$user) {
            return response()->json([
                'error'   => 'unauthenticated',
                'message' => 'Token geçerli ama kullanıcı bulunamadı.',
            ], 401);
        }

        // 4) User'ı auth guard + request resolver'a yaz
        Auth::guard('sanctum')->setUser($user);
        $request->setUserResolver(fn() => $user);

        // 5) Doğru tenant bağlamına bind et
        if ($user->isSuperadmin()) {
            TenantContext::bind(self::NULL_TENANT, true);
        } else {
            if (!$user->tenant_id) {
                return response()->json([
                    'error'   => 'no_tenant',
                    'message' => 'Kullanıcı bir tenant\'a bağlı değil.',
                ], 403);
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
