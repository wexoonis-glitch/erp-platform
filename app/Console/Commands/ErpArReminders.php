<?php
namespace App\Console\Commands;
use App\Models\FinArInvoice;
use App\Models\SysScheduleRun;
use App\Models\Tenant;
use App\Services\Platform\NotificationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ErpArReminders extends Command {
    protected $signature = 'erp:ar-reminders {--tenant=} {--days=3}';
    protected $description = 'Vadesi yaklaşan ve geçmiş müşteri faturaları için bildirim gönderir.';

    public function handle(): int {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::where('status', 'active')->get();

        $days = (int) $this->option('days');
        $this->info("AR hatırlatma: {$days} gün içinde vadesi dolan + geçmiş");

        foreach ($tenants as $tenant) {
            $run = SysScheduleRun::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'task_code' => 'ar_reminders',
                'task_name' => 'Müşteri Fatura Hatırlatmaları',
                'started_at' => now(),
                'status' => 'running',
            ]);

            try {
                $sent = TenantContext::runAs($tenant->id, function () use ($days) {
                    $threshold = now()->addDays($days)->toDateString();

                    // Vadesi yaklaşan veya geçmiş, henüz ödenmemiş faturalar
                    $invoices = FinArInvoice::with('customer')
                        ->whereIn('status', ['posted', 'partially_paid'])
                        ->whereDate('due_date', '<=', $threshold)
                        ->whereRaw('grand_total::numeric > paid_total::numeric')
                        ->limit(200)
                        ->get();

                    $count = 0;
                    $notifier = app(NotificationService::class);

                    foreach ($invoices as $inv) {
                        $remaining = (float) $inv->grand_total - (float) $inv->paid_total;
                        $daysLeft = now()->diffInDays($inv->due_date, false);

                        $title = $daysLeft < 0
                            ? "Gecikmiş Fatura: {$inv->invoice_no}"
                            : "Vadesi Yaklaşan Fatura: {$inv->invoice_no}";

                        $msg = $daysLeft < 0
                            ? "{$inv->customer?->name} — {$inv->invoice_no} faturası " . abs((int) $daysLeft) . " gün gecikti. Kalan: " . number_format($remaining, 2) . " {$inv->currency}"
                            : "{$inv->customer?->name} — {$inv->invoice_no} faturasının vadesine " . (int) $daysLeft . " gün kaldı. Kalan: " . number_format($remaining, 2) . " {$inv->currency}";

                        $notifier->notify('warning', $title, $msg, '/fin/ar-invoices');

                        // Müşteriye e-posta (opsiyonel — NotificationService içinde log'a yazar)
                        if ($inv->customer?->email) {
                            $notifier->send('ar_invoice_reminder', $title, $msg, $inv->customer->email);
                        }

                        $count++;
                    }
                    return $count;
                });

                $run->update([
                    'status' => 'success',
                    'finished_at' => now(),
                    'output' => "{$sent} fatura için hatırlatma gönderildi",
                ]);
                $this->info("  [{$tenant->code}] {$sent} hatırlatma.");
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
