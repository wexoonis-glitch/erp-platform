<?php
namespace App\Console\Commands;
use App\Models\SysBankStatement;
use App\Models\SysScheduleRun;
use App\Models\Tenant;
use App\Services\Fin\BankStatementService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ErpBankAutoReconcile extends Command {
    protected $signature = 'erp:bank-auto-reconcile {--tenant=}';
    protected $description = 'Kısmen eşleşmiş/import edilmiş banka ekstreleri için otomatik mutabakat çalıştırır.';

    public function handle(): int {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('status', 'active')->get();

        $this->info('Banka otomatik mutabakatı başlatıldı.');

        foreach ($tenants as $tenant) {
            $run = SysScheduleRun::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'task_code' => 'bank_auto_reconcile',
                'task_name' => 'Banka Otomatik Mutabakat',
                'started_at' => now(),
                'status' => 'running',
            ]);

            try {
                $result = TenantContext::runAs($tenant->id, function () {
                    $statements = SysBankStatement::whereIn('status', ['imported', 'partially_reconciled'])->get();
                    $svc = app(BankStatementService::class);
                    $totalMatched = 0; $totalUnmatched = 0; $statementCount = 0;

                    foreach ($statements as $st) {
                        try {
                            $r = $svc->autoMatch($st->id);
                            $totalMatched += $r['matched'];
                            $totalUnmatched += $r['unmatched'];
                            $statementCount++;
                        } catch (\Throwable) {
                            // tek bir ekstre hata verse de diğerleri devam etsin
                        }
                    }

                    return [
                        'statements' => $statementCount,
                        'matched' => $totalMatched,
                        'unmatched' => $totalUnmatched,
                    ];
                });

                $run->update([
                    'status' => 'success',
                    'finished_at' => now(),
                    'output' => "{$result['statements']} ekstre, {$result['matched']} eşleşti, {$result['unmatched']} kaldı",
                ]);
                $this->info("  [{$tenant->code}] {$result['statements']} ekstre / {$result['matched']} eşleşme.");
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
