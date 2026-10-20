<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId  = $request->header('X-Tenant-Id');
        $superadmin = $request->header('X-Superadmin') === '1';

        // Ne tenant ne superadmin varsa: bağlam açmadan geçir.
        // (sağlık kontrolü, login, public sayfalar vs.)
        if (!$tenantId && !$superadmin) {
            return $next($request);
        }

        // UUID formatı bozuksa erken dön
        if ($tenantId && !Str::isUuid($tenantId)) {
            return response()->json([
                'error'   => 'invalid_tenant_id',
                'message' => 'X-Tenant-Id geçerli bir UUID olmalıdır.',
            ], 400);
        }

        try {
            if ($superadmin) {
                // Superadmin: belirli bir tenant bağlanmadıysa "null UUID" ile
                // bağla; RLS is_superadmin=true sayesinde tüm satırları görür.
                TenantContext::bind(
                    $tenantId ?: '00000000-0000-0000-0000-000000000000',
                    true
                );
            } else {
                // Normal tenant bağlamı
                TenantContext::bind($tenantId, false);

                // Tenant gerçekten var mı ve aktif mi?
                $tenant = Tenant::find($tenantId);

                if (!$tenant) {
                    return response()->json([
                        'error'   => 'tenant_not_found',
                        'message' => "Tenant bulunamadı: {$tenantId}",
                    ], 404);
                }

                if ($tenant->status !== 'active') {
                    return response()->json([
                        'error'   => 'tenant_inactive',
                        'message' => "Tenant aktif değil: {$tenant->status}",
                    ], 403);
                }
            }

            return $next($request);
        } finally {
            // Her durumda bağlamı temizle
            TenantContext::unbind();
        }
    }
}
