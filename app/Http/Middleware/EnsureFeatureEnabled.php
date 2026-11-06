<?php
namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (Features::enabled($feature)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'Bu özellik devre dışı.',
                'feature' => $feature,
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('feature_disabled', $feature);
    }
}
