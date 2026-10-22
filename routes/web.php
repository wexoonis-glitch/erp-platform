<?php

use App\Livewire\AuditLog\Index as AuditLogIndex;
use App\Livewire\Auth\Login;
use App\Livewire\CustomFields\Index as CustomFieldsIndex;
use App\Livewire\CustomObjects\Index as CustomObjectsIndex;
use App\Livewire\CustomObjects\Records as CustomObjectsRecords;
use App\Livewire\Dashboard;
use App\Livewire\Fin\ApAging;
use App\Livewire\Fin\ApInvoices;
use App\Livewire\Fin\ApPayments;
use App\Livewire\Fin\ChartOfAccounts;
use App\Livewire\Fin\JournalEntries;
use App\Livewire\Fin\Suppliers;
use App\Livewire\Modules\Index as ModulesIndex;
use App\Livewire\Plugins\Index as PluginsIndex;
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
    Route::get('/plugins', PluginsIndex::class)->name('plugins.index');
    Route::get('/custom-fields', CustomFieldsIndex::class)->name('custom-fields.index');
    Route::get('/custom-objects', CustomObjectsIndex::class)->name('custom-objects.index');
    Route::get('/custom-objects/{objectId}/records', CustomObjectsRecords::class)->name('custom-objects.records');
    Route::get('/audit-log', AuditLogIndex::class)->name('audit-log.index');

    // FIN
    Route::get('/fin/coa', ChartOfAccounts::class)->name('fin.coa');
    Route::get('/fin/journal-entries', JournalEntries::class)->name('fin.journal-entries');
    Route::get('/fin/suppliers', Suppliers::class)->name('fin.suppliers');
    Route::get('/fin/ap-invoices', ApInvoices::class)->name('fin.ap-invoices');
    Route::get('/fin/ap-payments', ApPayments::class)->name('fin.ap-payments');
    Route::get('/fin/ap-aging', ApAging::class)->name('fin.ap-aging');
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->middleware('web.auth')->name('logout');
