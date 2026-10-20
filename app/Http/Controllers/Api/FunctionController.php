<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FunctionRegistry;
use App\Registry\FunctionRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FunctionController extends Controller
{
    public function __construct(private readonly FunctionRegistryService $registry) {}

    public function index(Request $request): JsonResponse
    {
        $query = FunctionRegistry::where('status', 'active');

        if ($module = $request->query('module')) {
            $query->where('domain', $module);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $data = $query->orderBy('id')
            ->get(['id', 'domain', 'subprocess', 'name', 'type', 'version', 'status'])
            ->toArray();

        return response()->json(['data' => $data]);
    }

    public function show(string $id): JsonResponse
    {
        $spec = $this->registry->get($id);

        return response()->json($spec->toArray());
    }
}
