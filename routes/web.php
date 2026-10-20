<?php

use App\Livewire\Auth\Login;
use App\Livewire\CustomFields\Index as CustomFieldsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Modules\Index as ModulesIndex;
use App\Livewire\Tenants\Index as TenantsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('web.auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/tenants', TenantsIndex::class)->name('tenants.index');
    Route::get('/users', UsersIndex::class)->name('users.index');
    Route::get('/modules', ModulesIndex::class)->name('modules.index');
    Route::get('/custom-fields', CustomFieldsIndex::class)->name('custom-fields.index');
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->middleware('web.auth')->name('logout');
