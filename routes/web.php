<?php
use App\Http\Controllers\CompanyLogoController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\InvoiceXmlController;
use App\Http\Controllers\NavigationController;
use App\Livewire\Approvals\Inbox as ApprovalsInbox;
use App\Livewire\Approvals\Workflows as ApprovalsWorkflows;
use App\Livewire\AuditLog\Index as AuditLogIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Crm\Leads;
use App\Livewire\Crm\Opportunities;
use App\Livewire\CustomFields\Index as CustomFieldsIndex;
use App\Livewire\CustomObjects\Index as CustomObjectsIndex;
use App\Livewire\CustomObjects\Records as CustomObjectsRecords;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Eam\Assets;
use App\Livewire\Eam\MaintenanceOrders;
use App\Livewire\Fin\ApAging;
use App\Livewire\Fin\ApInvoices;
use App\Livewire\Fin\ApPayments;
use App\Livewire\Fin\ArInvoices;
use App\Livewire\Fin\ArReceipts;
use App\Livewire\Fin\BankAccounts;
use App\Livewire\Fin\BankStatements;
use App\Livewire\Fin\BankTransactions;
use App\Livewire\Fin\BudgetVariance;
use App\Livewire\Fin\Budgets;
use App\Livewire\Fin\ChartOfAccounts;
use App\Livewire\Fin\CostCenters;
use App\Livewire\Fin\Currencies;
use App\Livewire\Fin\Customers;
use App\Livewire\Fin\Depreciation;
use App\Livewire\Fin\ExchangeRates;
use App\Livewire\Fin\FixedAssets;
use App\Livewire\Fin\JournalEntries;
use App\Livewire\Fin\Periods;
use App\Livewire\Fin\Reconciliation;
use App\Livewire\Fin\Reports;
use App\Livewire\Fin\Statement;
use App\Livewire\Fin\Suppliers;
use App\Livewire\Hrm\Attendances;
use App\Livewire\Hrm\Employees;
use App\Livewire\Hrm\Expenses;
use App\Livewire\Hrm\Payrolls;
use App\Livewire\Inv\PriceLists;
use App\Livewire\Inv\Products;
use App\Livewire\Inv\StockMovements;
use App\Livewire\Inv\StockTransfers;
use App\Livewire\Inv\Warehouses;
use App\Livewire\Log\Carriers;
use App\Livewire\Log\Shipments;
use App\Livewire\Mfg\Boms;
use App\Livewire\Mfg\Mrp;
use App\Livewire\Mfg\ProductionOrders;
use App\Livewire\Modules\Index as ModulesIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Otc\ConvertToInvoice;
use App\Livewire\Otc\SalesOrders;
use App\Livewire\Platform\DataExport;
use App\Livewire\Platform\DataImport;
use App\Livewire\Plugins\Index as PluginsIndex;
use App\Livewire\Ppm\Projects;
use App\Livewire\Prc\GoodsReceipts;
use App\Livewire\Prc\PurchaseOrders;
use App\Livewire\Prc\ThreeWayMatch;
use App\Livewire\Qm\InspectionPlans;
use App\Livewire\Qm\Ncrs;
use App\Livewire\SaaS\Plans;
use App\Livewire\SaaS\Subscriptions;
use App\Livewire\Settings\CompanyProfile;
use App\Livewire\Settings\CountrySettings;
use App\Livewire\Settings\FeatureSettings;
use App\Livewire\Settings\Notifications as SettingsNotifications;
use App\Livewire\Settings\Roles as SettingsRoles;
use App\Livewire\Settings\ScheduledTasks;
use App\Livewire\Tenants\Index as TenantsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));
Route::middleware('guest')->group(fn() => Route::get('/login', Login::class)->name('login'));

Route::middleware('web.auth')->group(function () {

    Route::post('/nav/favorite/toggle', [NavigationController::class, 'toggleFavorite'])
        ->name('nav.favorite.toggle');

    Route::middleware('module.active:PLT')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/tenants', TenantsIndex::class)->name('tenants.index');
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/modules', ModulesIndex::class)->name('modules.index');
        Route::get('/plugins', PluginsIndex::class)->name('plugins.index');
        Route::get('/custom-fields', CustomFieldsIndex::class)->name('custom-fields.index');
        Route::get('/custom-objects', CustomObjectsIndex::class)->name('custom-objects.index');
        Route::get('/custom-objects/{objectId}/records', CustomObjectsRecords::class)->name('custom-objects.records');
        Route::get('/audit-log', AuditLogIndex::class)->name('audit-log.index');
        Route::get('/notifications', NotificationsIndex::class)->name('notifications.index');
        Route::get('/approvals/inbox', ApprovalsInbox::class)->name('approvals.inbox');
        Route::get('/approvals/workflows', ApprovalsWorkflows::class)->name('approvals.workflows');
        Route::get('/documents', DocumentsIndex::class)->name('documents.index');
        Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('/data/import', DataImport::class)->name('data.import');
        Route::get('/data/export', DataExport::class)->name('data.export');
        Route::get('/data/export/{entity}', [ImportExportController::class, 'export'])->name('data.export.download');
        Route::get('/data/template/{entity}', [ImportExportController::class, 'template'])->name('data.template');
        Route::get('/settings/company', CompanyProfile::class)->name('settings.company');
        Route::get('/settings/company/logo', [CompanyLogoController::class, 'show'])->name('company.logo');
        Route::get('/settings/country', CountrySettings::class)->name('settings.country');
        Route::get('/settings/features', FeatureSettings::class)->name('settings.features');
        Route::get('/settings/notifications', SettingsNotifications::class)->name('settings.notifications');
        Route::get('/settings/scheduled-tasks', ScheduledTasks::class)->name('settings.scheduled-tasks');
        Route::get('/settings/roles', SettingsRoles::class)->name('settings.roles');
        Route::get('/saas/plans', Plans::class)->name('saas.plans');
        Route::get('/saas/subscriptions', Subscriptions::class)->name('saas.subscriptions');
    });

    Route::middleware(['module.active:FIN', 'feature:fin.enabled'])->group(function () {
        Route::get('/fin/coa', ChartOfAccounts::class)->name('fin.coa');
        Route::get('/fin/journal-entries', JournalEntries::class)->name('fin.journal-entries');
        Route::get('/fin/bank-accounts', BankAccounts::class)->name('fin.bank-accounts');
        Route::get('/fin/bank-transactions', BankTransactions::class)->name('fin.bank-transactions');
        Route::get('/fin/bank-statements', BankStatements::class)->name('fin.bank-statements');
        Route::get('/fin/reconciliation', Reconciliation::class)->name('fin.reconciliation');
        Route::get('/fin/currencies', Currencies::class)->name('fin.currencies');
        Route::get('/fin/exchange-rates', ExchangeRates::class)->name('fin.exchange-rates');
        Route::get('/fin/cost-centers', CostCenters::class)->name('fin.cost-centers');
        Route::get('/fin/periods', Periods::class)->name('fin.periods');
        Route::get('/fin/fixed-assets', FixedAssets::class)->name('fin.fixed-assets');
        Route::get('/fin/depreciation', Depreciation::class)->name('fin.depreciation');
        Route::get('/fin/budgets', Budgets::class)->name('fin.budgets');
        Route::get('/fin/budget-variance', BudgetVariance::class)->name('fin.budget-variance');
        Route::get('/fin/suppliers', Suppliers::class)->name('fin.suppliers');
        Route::get('/fin/ap-invoices', ApInvoices::class)->name('fin.ap-invoices');
        Route::get('/fin/ap-payments', ApPayments::class)->name('fin.ap-payments');
        Route::get('/fin/ap-aging', ApAging::class)->name('fin.ap-aging');
        Route::get('/fin/customers', Customers::class)->name('fin.customers');
        Route::get('/fin/ar-invoices', ArInvoices::class)->name('fin.ar-invoices');
        Route::get('/fin/ar-invoices/{id}/pdf', [InvoicePdfController::class, 'show'])->name('fin.ar-invoices.pdf');
        Route::get('/fin/ar-invoices/{id}/xml', [InvoiceXmlController::class, 'show'])->name('fin.ar-invoices.xml');
        Route::get('/fin/ar-receipts', ArReceipts::class)->name('fin.ar-receipts');
        Route::get('/fin/reports', Reports::class)->name('fin.reports');
        Route::get('/fin/statement', Statement::class)->name('fin.statement');
    });

    Route::middleware(['module.active:INV', 'feature:inv.enabled'])->group(function () {
        Route::get('/inv/products', Products::class)->name('inv.products');
        Route::get('/inv/price-lists', PriceLists::class)->name('inv.price-lists');
        Route::get('/inv/stock', StockMovements::class)->name('inv.stock');
        Route::get('/inv/warehouses', Warehouses::class)->name('inv.warehouses');
        Route::get('/inv/stock-transfers', StockTransfers::class)->name('inv.stock-transfers');
    });

    Route::middleware(['module.active:PRC', 'feature:prc.enabled'])->group(function () {
        Route::get('/prc/purchase-orders', PurchaseOrders::class)->name('prc.purchase-orders');
        Route::get('/prc/goods-receipts', GoodsReceipts::class)->name('prc.goods-receipts');
        Route::get('/prc/three-way-match', ThreeWayMatch::class)->name('prc.three-way-match');
    });

    Route::middleware(['module.active:OTC', 'feature:otc.enabled'])->group(function () {
        Route::get('/otc/sales-orders', SalesOrders::class)->name('otc.sales-orders');
        Route::get('/otc/convert-to-invoice', ConvertToInvoice::class)->name('otc.convert-to-invoice');
    });

    Route::middleware(['module.active:HRM', 'feature:hrm.enabled'])->group(function () {
        Route::get('/hrm/employees', Employees::class)->name('hrm.employees');
        Route::get('/hrm/attendances', Attendances::class)->name('hrm.attendances');
        Route::get('/hrm/payrolls', Payrolls::class)->name('hrm.payrolls');
        Route::get('/hrm/expenses', Expenses::class)->name('hrm.expenses');
    });

    Route::middleware(['module.active:MFG', 'feature:mfg.enabled'])->group(function () {
        Route::get('/mfg/boms', Boms::class)->name('mfg.boms');
        Route::get('/mfg/production-orders', ProductionOrders::class)->name('mfg.production-orders');
        Route::get('/mfg/mrp', Mrp::class)->name('mfg.mrp');
    });

    Route::middleware(['module.active:EAM', 'feature:eam.enabled'])->group(function () {
        Route::get('/eam/assets', Assets::class)->name('eam.assets');
        Route::get('/eam/maintenance-orders', MaintenanceOrders::class)->name('eam.maintenance-orders');
    });

    Route::middleware(['module.active:CRM', 'feature:crm.enabled'])->group(function () {
        Route::get('/crm/leads', Leads::class)->name('crm.leads');
        Route::get('/crm/opportunities', Opportunities::class)->name('crm.opportunities');
    });

    Route::middleware(['module.active:PPM', 'feature:ppm.enabled'])->group(function () {
        Route::get('/ppm/projects', Projects::class)->name('ppm.projects');
    });

    Route::middleware(['module.active:QM', 'feature:qm.enabled'])->group(function () {
        Route::get('/qm/inspection-plans', InspectionPlans::class)->name('qm.inspection-plans');
        Route::get('/qm/ncrs', Ncrs::class)->name('qm.ncrs');
    });

    Route::middleware(['module.active:LOG', 'feature:log.enabled'])->group(function () {
        Route::get('/log/carriers', Carriers::class)->name('log.carriers');
        Route::get('/log/shipments', Shipments::class)->name('log.shipments');
    });
});

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->middleware('web.auth')->name('logout');
