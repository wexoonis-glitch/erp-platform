<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\ModuleService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LogicException;

class ModuleController extends Controller
{
    public function __construct(private readonly ModuleService $service) {}

    /**
     * GET /api/v1/modules — tenant'ın kurduğu modüller
     * Superadmin: ?tenant_id=xxx zorunlu.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTargetTenant($request, required: true);

        return response()->json([
            'data' => $this->service->installed($tenantId),
        ]);
    }

    /**
     * GET /api/v1/modules/catalog — tüm katalog (global).
     */
    public function catalog(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->catalog(),
        ]);
    }

    /**
     * GET /api/v1/modules/{id}/functions — modülün fonksiyonları.
     */
    public function functions(string $id): JsonResponse
    {
        return response()->json([
            'module_id' => $id,
            'data'      => $this->service->moduleFunctions($id),
        ]);
    }

    /**
     * POST /api/v1/modules/{id}/install — yalnızca superadmin.
     * ?tenant_id=xxx zorunlu.
     */
    public function install(Request $request, string $id): JsonResponse
    {
        $this->assertSuperadmin();
        $tenantId = $this->resolveTargetTenant($request, required: true);

        $tm = $this->service->install($tenantId, $id);

        return response()->json([
            'module_id'    => $tm->module_id,
            'version'      => $tm->version,
            'status'       => $tm->status,
            'installed_at' => $tm->installed_at,
        ], 201);
    }

    /**
     * POST /api/v1/modules/{id}/uninstall — yalnızca superadmin.
     */
    public function uninstall(Request $request, string $id): JsonResponse
    {
        $this->assertSuperadmin();
        $tenantId = $this->resolveTargetTenant($request, required: true);

        $this->service->uninstall($tenantId, $id);

        return response()->json(['message' => "Modül kaldırıldı: {$id}"]);
    }

    private function assertSuperadmin(): void
    {
        if (!TenantContext::isSuperadmin()) {
            throw new LogicException('Bu işlem yalnızca superadmin tarafından yapılabilir.');
        }
    }

    private function resolveTargetTenant(Request $request, bool $required): ?string
    {
        $query = $request->query('tenant_id');

        if (TenantContext::isSuperadmin()) {
            if ($required && !$query) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Superadmin için tenant_id query parametresi zorunludur.',
                ]);
            }
            return $query;
        }

        return TenantContext::id();
    }
}
