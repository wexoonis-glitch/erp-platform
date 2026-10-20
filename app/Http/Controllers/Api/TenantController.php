<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTenantRequest;
use App\Services\Platform\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $service) {}

    /**
     * PLT-TEN-0001 — Yeni tenant oluştur.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->service->create($request->validated());

        return response()->json([
            'id'     => $tenant->id,
            'code'   => $tenant->code,
            'name'   => $tenant->name,
            'tier'   => $tenant->tier,
            'status' => $tenant->status,
        ], 201);
    }
}
