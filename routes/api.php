<?php

use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('tenant')->group(function () {
    Route::post('/tenants', [TenantController::class, 'store']);
});
