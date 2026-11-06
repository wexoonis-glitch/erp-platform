<?php
namespace App\Livewire\Settings;

use App\Models\SysScheduleRun;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ScheduledTasks extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $message = '';
    public string $messageType = 'success';

    public array $taskCatalog = [
        'depreciation_monthly' => [
            'code' => 'depreciation_monthly',
            'name' => 'Aylık Amortisman',
            'command' => 'erp:depreciation-run',
            'args' => [],
            'schedule' => 'Her ayın 1\'i 03:00',
        ],
        'ar_reminders' => [
            'code' => 'ar_reminders',
            'name' => 'Fatura Hatırlatmaları',
            'command' => 'erp:ar-reminders',
            'args' => ['--days' => 3],
            'schedule' => 'Her gün 09:00',
        ],
        'bank_auto_reconcile' => [
            'code' => 'bank_auto_reconcile',
            'name' => 'Banka Otomatik Mutabakat',
            'command' => 'erp:bank-auto-reconcile',
            'args' => [],
            'schedule' => 'Her gün 10:00',
        ],
    ];

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

    public function runNow(string $taskCode) {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) {
            $this->messageType = 'error';
            $this->message = 'Tenant seçin.';
            return;
        }

        if (!isset($this->taskCatalog[$taskCode])) {
            $this->messageType = 'error';
            $this->message = 'Bilinmeyen görev.';
            return;
        }

        $task = $this->taskCatalog[$taskCode];
        $commandName = $task['command'];
        $args = $task['args'] ?? [];

        // --tenant argümanını ekle
        $args['--tenant'] = $tenantId;

        try {
            $exitCode = Artisan::call($commandName, $args);
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                $this->messageType = 'error';
                $this->message = "Görev çalıştı ama hata döndü (exit={$exitCode}): " . (strlen($output) > 200 ? substr($output, 0, 200) . '...' : $output);
                return;
            }

            $this->messageType = 'success';
            $this->message = "Görev çalıştırıldı: {$task['name']} — " . (strlen($output) > 200 ? substr($output, 0, 200) . '...' : $output);
        } catch (\Throwable $e) {
            $this->messageType = 'error';
            $this->message = 'Hata: ' . $e->getMessage();
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $runs = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$runs) {
                $runs = SysScheduleRun::orderByDesc('started_at')->limit(50)->get()->map(fn($r) => [
                    'id' => $r->id,
                    'task_code' => $r->task_code,
                    'task_name' => $r->task_name,
                    'started_at' => $r->started_at?->toDateTimeString() ?? '—',
                    'finished_at' => $r->finished_at?->toDateTimeString() ?? '—',
                    'status' => $r->status,
                    'output' => $r->output,
                    'error' => $r->error,
                ])->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.settings.scheduled-tasks', compact('runs', 'tenants', 'isSuper'))->layout('layouts.app', ['title' => 'Zamanlanmış Görevler']);
    }
}
