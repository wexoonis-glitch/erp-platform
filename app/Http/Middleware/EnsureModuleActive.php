<?php
namespace App\Http\Middleware;

use App\Models\SysRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleActive
{
    public function handle(Request $request, Closure $next, string $domain): Response
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        if ($user->isSuperadmin()) return $next($request);

        $tenantId = $user->tenant_id ?? null;
        if (!$tenantId) {
            abort(403, 'Kullanıcı bir tenant\'a bağlı değil.');
        }

        // PLT her zaman açık
        if ($domain === 'PLT') return $next($request);

        // Rol bu modüle izin veriyor mu?
        $role = SysRole::where('tenant_id', $tenantId)
            ->where('code', $user->role)
            ->where('is_active', true)
            ->first();

        // Rol sys_role'de yoksa legacy fallback: erişime izin ver
        if (!$role) return $next($request);

        $allowed = $role->modules()->where('module_id', $domain)->exists();
        if (!$allowed) {
            return redirect()->route('dashboard')
                ->with('module_blocked', "Rolünüz ({$role->name}) için bu modül yetkili değil: {$domain}.");
        }

        return $next($request);
    }
}
