<?php
namespace App\Http\Controllers;

use App\Services\Platform\NavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NavigationController extends Controller
{
    public function toggleFavorite(Request $request, NavigationService $nav)
    {
        $data = $request->validate([
            'route' => 'required|string|max:120',
            'back'  => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'unauth'], 401);
            }
            abort(401);
        }

        $tenantId = \App\Support\TenantContext::id()
            ?? $user->tenant_id
            ?? null;

        if (!$tenantId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Tenant context yok.'], 422);
            }
            return back()->with('nav_error', 'Tenant context yok.');
        }

        try {
            $added = $nav->toggleFavorite((int) $user->id, $data['route'], $tenantId);

            if ($request->expectsJson()) {
                return response()->json(['favorited' => $added]);
            }

            return redirect($data['back'] ?? url()->previous())
                ->with('nav_fav', $added ? 'added' : 'removed');

        } catch (\Throwable $e) {
            \Log::warning('Nav toggleFavorite failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->with('nav_error', $e->getMessage());
        }
    }
}
