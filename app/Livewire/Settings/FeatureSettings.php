<?php
namespace App\Livewire\Settings;

use App\Services\Platform\FeatureFlagService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class FeatureSettings extends Component
{
    /** @var array<string, array{enabled:bool, value:mixed, config:mixed}> */
    public array $flags = [];

    public ?string $message     = null;
    public string  $messageType = 'success';

    public function mount(FeatureFlagService $svc): void
    {
        $this->loadFlags($svc);
    }

    private function loadFlags(FeatureFlagService $svc): void
    {
        $resolved = $svc->resolvedAll();
        $this->flags = [];

        foreach ($resolved as $key => $def) {
            $this->flags[$key] = [
                'enabled' => (bool) $def['enabled'],
                'value'   => $def['value'],
                'config'  => $def['value'],
            ];
        }
    }

    public function toggle(string $key, FeatureFlagService $svc): void
    {
        if (!isset($this->flags[$key])) return;

        $newState = !$this->flags[$key]['enabled'];

        try {
            $svc->set($key, $newState, $this->flags[$key]['config']);
            $this->loadFlags($svc);

            $this->message = 'Kaydedildi';
            $this->messageType = 'success';
        } catch (\Throwable $e) {
            \Log::error("Feature toggle hata [{$key}]: " . $e->getMessage());
            $this->message = 'Hata: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function setValue(string $key, mixed $value, FeatureFlagService $svc): void
    {
        if (!isset($this->flags[$key])) return;

        try {
            $svc->set($key, $this->flags[$key]['enabled'], $value);
            $this->loadFlags($svc);
            $this->message = 'Değer kaydedildi';
            $this->messageType = 'success';
        } catch (\Throwable $e) {
            $this->message = 'Hata: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    public function resetKey(string $key, FeatureFlagService $svc): void
    {
        try {
            $tenantId = \App\Support\TenantContext::id() ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                \App\Models\SysTenantFeature::where('tenant_id', $tenantId)
                    ->where('feature_key', $key)
                    ->delete();
            }
            $this->loadFlags($svc);
            $this->message = 'Varsayılana sıfırlandı';
            $this->messageType = 'info';
        } catch (\Throwable $e) {
            $this->message = 'Hata: ' . $e->getMessage();
            $this->messageType = 'error';
        }
    }

    #[Layout('layouts.app')]
    public function render(FeatureFlagService $svc)
    {
        $resolved = $svc->resolvedAll();

        // Modülleri ve detayları AYIR
        $moduleFlags = [];
        $detailGroups = [];

        foreach ($resolved as $key => $def) {
            $group = $def['group'] ?? 'general';

            if ($group === 'modules') {
                $moduleFlags[$key] = $def;
            } else {
                if (!isset($detailGroups[$group])) {
                    $detailGroups[$group] = [
                        'label' => $this->groupLabel($group),
                        'items' => [],
                    ];
                }
                $def['order'] = $def['order'] ?? 999;
                $detailGroups[$group]['items'][$key] = $def;
            }
        }

        uasort($moduleFlags, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        foreach ($detailGroups as &$group) {
            uasort($group['items'], fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        }
        unset($group);

        return view('livewire.settings.feature-settings', [
            'moduleFlags'  => $moduleFlags,
            'detailGroups' => $detailGroups,
        ])->layout('layouts.app', ['title' => 'Sistem Özellikleri']);
    }

    private function groupLabel(string $group): string
    {
        return match ($group) {
            'otc'       => '🛒 Satış İçi Özellikler',
            'ux'        => '⚡ Kullanıcı Deneyimi',
            'analytics' => '📊 Analitik',
            'security'  => '🔒 Güvenlik',
            'inv'       => '📦 Envanter Ayarları',
            'fin'       => '💰 Finans Ayarları',
            default     => ucfirst($group),
        };
    }
}
