<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\CustomObjectController;
use App\Http\Controllers\Api\FunctionController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth.tenant')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Tenant lifecycle
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::get('/tenants/{id}', [TenantController::class, 'show']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::patch('/tenants/{id}', [TenantController::class, 'update']);
        Route::post('/tenants/{id}/suspend', [TenantController::class, 'suspend']);
        Route::post('/tenants/{id}/activate', [TenantController::class, 'activate']);

        // User CRUD
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Function catalog
        Route::get('/functions', [FunctionController::class, 'index']);
        Route::get('/functions/{id}', [FunctionController::class, 'show']);

        // Modules
        Route::get('/modules', [ModuleController::class, 'index']);
        Route::get('/modules/catalog', [ModuleController::class, 'catalog']);
        Route::get('/modules/{id}/functions', [ModuleController::class, 'functions']);
        Route::post('/modules/{id}/install', [ModuleController::class, 'install']);
        Route::post('/modules/{id}/uninstall', [ModuleController::class, 'uninstall']);

        // Custom fields
        Route::get('/custom-fields', [CustomFieldController::class, 'index']);
        Route::get('/custom-fields/{id}', [CustomFieldController::class, 'show']);
        Route::post('/custom-fields', [CustomFieldController::class, 'store']);
        Route::patch('/custom-fields/{id}', [CustomFieldController::class, 'update']);
        Route::delete('/custom-fields/{id}', [CustomFieldController::class, 'destroy']);

        // Entity schema (built-in + custom fields)
        Route::get('/entities/{entity}/schema', [CustomFieldController::class, 'schema']);

        // Custom objects
        Route::get('/custom-objects', [CustomObjectController::class, 'index']);
        Route::get('/custom-objects/{id}', [CustomObjectController::class, 'show']);
        Route::post('/custom-objects', [CustomObjectController::class, 'store']);
        Route::patch('/custom-objects/{id}', [CustomObjectController::class, 'update']);
        Route::delete('/custom-objects/{id}', [CustomObjectController::class, 'destroy']);

        // Custom object records
        Route::get('/custom-objects/{id}/records', [CustomObjectController::class, 'listRecords']);
        Route::post('/custom-objects/{id}/records', [CustomObjectController::class, 'createRecord']);
        Route::get('/custom-objects/{id}/records/{recordId}', [CustomObjectController::class, 'showRecord']);
        Route::patch('/custom-objects/{id}/records/{recordId}', [CustomObjectController::class, 'updateRecord']);
        Route::delete('/custom-objects/{id}/records/{recordId}', [CustomObjectController::class, 'deleteRecord']);
    });
});
