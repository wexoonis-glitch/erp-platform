<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomObjectRequest;
use App\Http\Requests\StoreRecordRequest;
use App\Http\Requests\UpdateCustomObjectRequest;
use App\Http\Requests\UpdateRecordRequest;
use App\Services\Platform\CustomObjectService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CustomObjectController extends Controller
{
    public function __construct(private readonly CustomObjectService $service) {}

    // ---------- Objects ----------

    public function index(Request $request): JsonResponse
    {
        $tenantId = TenantContext::isSuperadmin() ? $request->query('tenant_id') : null;

        return response()->json([
            'data' => $this->service->listAll($tenantId),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->show($id));
    }

    public function store(CreateCustomObjectRequest $request): JsonResponse
    {
        $this->assertAdmin($request);
        $tenantId = $this->resolveTargetTenant($request, required: true);

        return response()->json(
            $this->service->create($request->validated(), $tenantId),
            201
        );
    }

    public function update(UpdateCustomObjectRequest $request, string $id): JsonResponse
    {
        $this->assertAdmin($request);

        return response()->json($this->service->update($id, $request->validated()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->assertAdmin($request);
        $this->service->delete($id);

        return response()->json(['message' => 'Custom object silindi.']);
    }

    // ---------- Records ----------

    public function listRecords(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->listRecords($id)]);
    }

    public function showRecord(string $id, string $recordId): JsonResponse
    {
        return response()->json($this->service->showRecord($id, $recordId));
    }

    public function createRecord(StoreRecordRequest $request, string $id): JsonResponse
    {
        return response()->json(
            $this->service->createRecord($id, $request->validated()['data']),
            201
        );
    }

    public function updateRecord(UpdateRecordRequest $request, string $id, string $recordId): JsonResponse
    {
        return response()->json(
            $this->service->updateRecord($id, $recordId, $request->validated()['data'])
        );
    }

    public function deleteRecord(string $id, string $recordId): JsonResponse
    {
        $this->service->deleteRecord($id, $recordId);

        return response()->json(['message' => 'Kayıt silindi.']);
    }

    // ---------- Helpers ----------

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'], true)) {
            throw new AccessDeniedHttpException('Bu işlem için admin rolü gerekli.');
        }
    }

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
