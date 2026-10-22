<?php

namespace App\Livewire\AuditLog;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Url(as: 'action')]
    public string $filterAction = '';

    #[Url(as: 'entity')]
    public string $filterEntity = '';

    #[Url(as: 'user')]
    public string $filterUser = '';

    #[Url(as: 'tenant')]
    public string $filterTenant = '';

    #[Url(as: 'from')]
    public string $filterFrom = '';

    #[Url(as: 'to')]
    public string $filterTo = '';

    public ?int $expandedId = null;

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function clearFilters(): void
    {
        $this->reset(['filterAction', 'filterEntity', 'filterUser', 'filterTenant', 'filterFrom', 'filterTo']);
        $this->resetPage();
    }

    public function updated($field): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();

        $query = AuditLog::query()->orderByDesc('id');

        if ($this->filterAction !== '') {
            $query->where('action', $this->filterAction);
        }
        if ($this->filterEntity !== '') {
            $query->where('entity', $this->filterEntity);
        }
        if ($this->filterUser !== '') {
            $query->where('user_email', 'like', '%' . $this->filterUser . '%');
        }
        if ($isSuper && $this->filterTenant !== '') {
            $query->where('tenant_id', $this->filterTenant);
        }
        if ($this->filterFrom !== '') {
            $query->where('created_at', '>=', $this->filterFrom . ' 00:00:00');
        }
        if ($this->filterTo !== '') {
            $query->where('created_at', '<=', $this->filterTo . ' 23:59:59');
        }

        $logs = $query->paginate(50);

        $tenants = [];
        $tenantLookup = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
            foreach ($tenants as $t) {
                $tenantLookup[$t['id']] = ['code' => $t['code'], 'name' => $t['name']];
            }
        }

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action')->all();
        $entities = AuditLog::distinct()->orderBy('entity')->pluck('entity')->all();

        return view('livewire.audit-log.index', [
            'title'        => 'Audit Log',
            'isSuper'      => $isSuper,
            'logs'         => $logs,
            'tenants'      => $tenants,
            'tenantLookup' => $tenantLookup,
            'actions'      => $actions,
            'entities'     => $entities,
        ]);
    }
}
