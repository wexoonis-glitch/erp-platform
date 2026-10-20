<?php

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// --- GEÇİCİ TEST ROUTE'U (canlıya çıkmadan silinecek) ---
Route::middleware('tenant')->get('/test/whoami', function () {
    return response()->json([
        'tenant_id'       => TenantContext::id(),
        'superadmin'      => TenantContext::isSuperadmin(),
        'tenants_visible' => Tenant::orderBy('code')->pluck('code')->all(),
        'db_user'         => \DB::selectOne('SELECT current_user AS u')->u,
    ]);
});
