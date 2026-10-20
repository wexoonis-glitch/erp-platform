<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function login(LoginRequest $request): JsonResponse
    {
        $user = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => User::where('email', $request->email)->first(),
            superadmin: true
        );

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kimlik bilgileri hatalı.'],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Hesap aktif değil.'],
            ]);
        }

        // Tenant aktif mi?
        if ($user->tenant_id) {
            $tenant = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::find($user->tenant_id),
                superadmin: true
            );

            if (!$tenant || $tenant->status !== 'active') {
                throw ValidationException::withMessages([
                    'email' => ['Tenant aktif değil.'],
                ]);
            }
        }

        $user->tokens()->where('name', 'api')->delete();

        $token = $user->createToken('api', ['*'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'tenant_id'  => $user->tenant_id,
                'superadmin' => $user->isSuperadmin(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();

        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'status'     => $user->status,
            'tenant_id'  => $user->tenant_id,
            'superadmin' => $user->isSuperadmin(),
            'context'    => [
                'active_tenant_id' => TenantContext::id(),
                'is_superadmin'    => TenantContext::isSuperadmin(),
            ],
        ]);
    }
}
