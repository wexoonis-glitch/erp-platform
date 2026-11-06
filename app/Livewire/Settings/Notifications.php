<?php
namespace App\Livewire\Settings;
use App\Models\SysNotificationSetting;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Notifications extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function toggleSetting(string $eventKey, string $channel) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($tid, $eventKey, $channel) {
            $setting = SysNotificationSetting::where('event_key', $eventKey)->where('channel', $channel)->first();
            if ($setting) {
                $setting->update(['is_active' => !$setting->is_active]);
            } else {
                SysNotificationSetting::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tid, 'event_key' => $eventKey, 'channel' => $channel, 'is_active' => true,
                ]);
            }
        });
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $settings = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$settings) {
                $settings = SysNotificationSetting::pluck('is_active', 'event_key')->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.settings.notifications', compact('settings', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Bildirim Ayarları']);
    }
}
