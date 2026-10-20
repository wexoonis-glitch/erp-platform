<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Services\Platform\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $service) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->service->listAll()]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->show($id));
    }

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

    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->service->update($id, $request->validated());

        return response()->json([
            'id'     => $tenant->id,
            'code'   => $tenant->code,
            'name'   => $tenant->name,
            'tier'   => $tenant->tier,
            'status' => $tenant->status,
            'config' => $tenant->config,
        ]);
    }

    public function suspend(string $id): JsonResponse
    {
        $tenant = $this->service->suspend($id);

        return response()->json([
            'id'     => $tenant->id,
            'status' => $tenant->status,
        ]);
    }

    public function activate(string $id): JsonResponse
    {
        $tenant = $this->service->activate($id);

        return response()->json([
            'id'     => $tenant->id,
            'status' => $tenant->status,
        ]);
    }
}
