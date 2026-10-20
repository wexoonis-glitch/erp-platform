<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Services\Platform\CustomFieldService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CustomFieldController extends Controller
{
    public function __construct(private readonly CustomFieldService $service) {}

    public function index(Request $request): JsonResponse
    {
        $entity = $request->query('entity');
        $tenantId = null;

        // Superadmin: opsiyonel ?tenant_id=X ile filtreleyebilir
        if (TenantContext::isSuperadmin()) {
            $tenantId = $request->query('tenant_id');
        }

        return response()->json([
            'data' => $this->service->listAll($entity ?: null, $tenantId),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->show($id));
    }

    public function store(CreateCustomFieldRequest $request): JsonResponse
    {
        $this->assertAdmin($request);
        $tenantId = $this->resolveTargetTenant($request, required: true);

        $field = $this->service->create($request->validated(), $tenantId);

        return response()->json($field, 201);
    }

    public function update(UpdateCustomFieldRequest $request, string $id): JsonResponse
    {
        $this->assertAdmin($request);

        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->assertAdmin($request);
        $this->service->delete($id);

        return response()->json(['message' => 'Custom field silindi.']);
    }

    public function schema(Request $request, string $entity): JsonResponse
    {
        $tenantId = null;
        if (TenantContext::isSuperadmin()) {
            $tenantId = $request->query('tenant_id');
        } else {
            $tenantId = TenantContext::id();
        }

        return response()->json($this->service->schemaFor($entity, $tenantId));
    }

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'], true)) {
            throw new AccessDeniedHttpException('Bu işlem için admin rolü gerekli.');
        }
    }

    /**
     * Superadmin: ?tenant_id zorunlu.
     * Tenant admin: kendi tenant'ı otomatik.
     */
    private function resolveTargetTenant(Request $request, bool $required): string
    {
        if (TenantContext::isSuperadmin()) {
            $tid = $request->query('tenant_id');
            if ($required && !$tid) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Superadmin için tenant_id query parametresi zorunludur.',
                ]);
            }
            if (!$tid) {
                throw new LogicException('tenant_id zorunlu.');
            }
            return $tid;
        }

        $id = TenantContext::id();
        if ($id === null) {
            throw new LogicException('Tenant bağlamı yok.');
        }
        return $id;
    }
}
