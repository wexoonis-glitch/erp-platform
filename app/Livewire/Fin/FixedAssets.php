<?php
namespace App\Livewire\Fin;
use App\Models\FinFixedAsset;
use App\Models\FinAccount;
use App\Models\FinCostCenter;
use App\Models\Tenant;
use App\Services\Fin\FixedAssetService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class FixedAssets extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public bool $showDispose = false;
    public ?string $disposingId = null;

    // Form
    public string $code = '', $name = '', $category = 'machinery', $acquisition_date = '';
    public string $acquisition_cost = '0', $useful_life_months = '60', $salvage_value = '0';
    public string $asset_account_id = '', $accumulated_dep_account_id = '', $depreciation_expense_account_id = '', $cost_center_id = '';

    // Dispose
    public string $disposal_amount = '0', $disposal_date = '', $disposal_notes = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->acquisition_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate([
            'code' => 'required', 'name' => 'required',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0.01',
            'useful_life_months' => 'required|integer|min:1',
            'asset_account_id' => 'required',
            'accumulated_dep_account_id' => 'required',
            'depreciation_expense_account_id' => 'required',
        ]);
        try {
            TenantContext::runAs($tid, fn() => app(FixedAssetService::class)->create([
                'code' => $this->code, 'name' => $this->name, 'category' => $this->category,
                'acquisition_date' => $this->acquisition_date,
                'acquisition_cost' => (float) $this->acquisition_cost,
                'useful_life_months' => (int) $this->useful_life_months,
                'salvage_value' => (float) $this->salvage_value,
                'asset_account_id' => $this->asset_account_id,
                'accumulated_dep_account_id' => $this->accumulated_dep_account_id,
                'depreciation_expense_account_id' => $this->depreciation_expense_account_id,
                'cost_center_id' => $this->cost_center_id ?: null,
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function openDispose(string $id) {
        $this->disposingId = $id;
        $this->disposal_date = now()->toDateString();
        $this->disposal_amount = '0';
        $this->disposal_notes = '';
        $this->showDispose = true;
    }
    public function cancelDispose() { $this->showDispose = false; $this->disposingId = null; $this->resetErrorBag(); }

    public function dispose() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->disposingId) return;
        $this->validate(['disposal_date' => 'required|date', 'disposal_amount' => 'required|numeric|min:0']);
        try {
            TenantContext::runAs($tid, fn() => app(FixedAssetService::class)->dispose(
                $this->disposingId, (float) $this->disposal_amount, $this->disposal_date, $this->disposal_notes ?: null
            ));
            $this->cancelDispose();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['code', 'name', 'asset_account_id', 'accumulated_dep_account_id', 'depreciation_expense_account_id', 'cost_center_id']);
        $this->category = 'machinery'; $this->acquisition_cost = '0'; $this->useful_life_months = '60'; $this->salvage_value = '0';
        $this->acquisition_date = now()->toDateString();
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $assets = []; $assetAccounts = []; $accumulatedAccounts = []; $expenseAccounts = []; $costCenters = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$assets, &$assetAccounts, &$accumulatedAccounts, &$expenseAccounts, &$costCenters) {
                $assets = FinFixedAsset::with('costCenter')->orderBy('code')->get()->toArray();
                $assetAccounts = FinAccount::whereIn('code', ['253', '254', '255'])->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $accumulatedAccounts = FinAccount::whereIn('code', ['257'])->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $expenseAccounts = FinAccount::whereIn('code', ['730', '770'])->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $costCenters = FinCostCenter::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.fixed-assets', compact('assets', 'assetAccounts', 'accumulatedAccounts', 'expenseAccounts', 'costCenters', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Duran Varlıklar']);
    }
}
