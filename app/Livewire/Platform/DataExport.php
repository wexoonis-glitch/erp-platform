<?php
namespace App\Livewire\Platform;

use App\Models\Tenant;
use App\Support\ImportSchemas;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DataExport extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public function mount()
    {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $isSuper = Auth::user()->isSuperadmin();
        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                true
            );
        }

        return view('livewire.platform.data-export', [
            'isSuper' => $isSuper,
            'tenants' => $tenants,
            'schemas' => ImportSchemas::all(),
        ])->layout('layouts.app', ['title' => 'Veri Dışa Aktarma']);
    }
}
