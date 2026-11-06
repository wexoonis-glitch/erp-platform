<?php
namespace App\Console\Commands;
use App\Models\SysScheduleRun;
use App\Models\Tenant;
use App\Services\Fin\DepreciationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ErpDepreciationRun extends Command {
    protected $signature = 'erp:depreciation-run {--tenant=} {--year=} {--month=}';
    protected $description = 'Aylık amortisman koşusunu çalıştırır (geçen ay için varsayılan).';

    public function handle(): int {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('status', 'active')->get();

        if ($tenants->isEmpty()) {
            $this->warn('Tenant bulunamadı.');
            return self::SUCCESS;
        }

        $year = $this->option('year') ?: now()->subMonth()->format('Y');
        $month = $this->option('month') ?: now()->subMonth()->format('m');

        $this->info("Amortisman koşusu: {$year}-{$month}");

        foreach ($tenants as $tenant) {
            $run = SysScheduleRun::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'task_code' => 'depreciation_monthly',
                'task_name' => 'Aylık Amortisman',
                'started_at' => now(),
                'status' => 'running',
            ]);

            try {
                $count = TenantContext::runAs($tenant->id, fn() => app(DepreciationService::class)->runPeriod($year, $month));
                $run->update([
                    'status' => 'success',
                    'finished_at' => now(),
                    'output' => "{$count} amortisman post edildi ({$year}-{$month})",
                ]);
                $this->info("  [{$tenant->code}] {$count} amortisman post edildi.");
            } catch (\Throwable $e) {
                $run->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error' => $e->getMessage(),
                ]);
                $this->error("  [{$tenant->code}] Hata: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
