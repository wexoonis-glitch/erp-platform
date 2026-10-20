<?php

namespace App\Livewire;

use App\Models\CustomField;
use App\Models\CustomObject;
use App\Models\FunctionRegistry;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Layout('layouts.app')]
    public function render()
    {
        $user    = Auth::user();
        $isSuper = $user->isSuperadmin();

        $stats = $this->buildStats($isSuper, $user);
        $recent = $this->recentActivity($isSuper);

        return view('livewire.dashboard', [
            'title'  => 'Dashboard',
            'isSuper' => $isSuper,
            'stats'  => $stats,
            'recent' => $recent,
        ]);
    }

    private function buildStats(bool $isSuper, $user): array
    {
        // Tenants
        if ($isSuper) {
            $tenants = TenantContext::runAs(self::NULL_TENANT, function () {
                return [
                    'total'  => Tenant::count(),
                    'active' => Tenant::where('status', 'active')->count(),
                ];
            }, superadmin: true);
        } else {
            $tenants = TenantContext::runAs(self::NULL_TENANT, function () use ($user) {
                $t = Tenant::find($user->tenant_id);
                return [
                    'total'  => 1,
                    'active' => $t && $t->status === 'active' ? 1 : 0,
                ];
            }, superadmin: true);
        }

        // Users (RLS-aware)
        $users = User::count();

        // Modules
        if ($isSuper) {
            $modules = FunctionRegistry::select('domain')->distinct()->count('domain');
            $modulesLabel = 'Katalog Modülü';
        } else {
            $modules = TenantModule::where('status', 'active')->count();
            $modulesLabel = 'Kurulu Modül';
        }

        // Custom fields + objects
        $customFields  = CustomField::count();
        $customObjects = CustomObject::count();

        // Functions
        $functions = FunctionRegistry::where('status', 'active')->count();

        return [
            ['label' => 'Tenant',         'value' => $tenants['total'],   'sub' => "{$tenants['active']} aktif", 'icon' => '🏢', 'color' => 'indigo'],
            ['label' => 'Kullanıcı',      'value' => $users,              'sub' => 'toplam',                     'icon' => '👥', 'color' => 'emerald'],
            ['label' => $modulesLabel,    'value' => $modules,            'sub' => 'aktif',                      'icon' => '🧩', 'color' => 'amber'],
            ['label' => 'Fonksiyon',      'value' => $functions,          'sub' => 'katalogda',                  'icon' => '⚡', 'color' => 'sky'],
            ['label' => 'Custom Field',   'value' => $customFields,       'sub' => 'tanımlı',                    'icon' => '⚙️', 'color' => 'violet'],
            ['label' => 'Custom Object',  'value' => $customObjects,      'sub' => 'tanımlı',                    'icon' => '📦', 'color' => 'rose'],
        ];
    }

    private function recentActivity(bool $isSuper): array
    {
        return \App\Models\EventOutbox::query()
            ->orderByDesc('id')
            ->limit(10)
            ->get(['event_key', 'status', 'created_at'])
            ->map(fn($e) => [
                'key'    => $e->event_key,
                'status' => $e->status,
                'time'   => $e->created_at?->diffForHumans(),
            ])
            ->toArray();
    }
}
