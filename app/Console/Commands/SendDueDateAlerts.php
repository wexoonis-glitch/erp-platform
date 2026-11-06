<?php
namespace App\Console\Commands;
use App\Models\FinApInvoice;
use App\Models\FinArInvoice;
use App\Models\Tenant;
use App\Services\Platform\NotificationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class SendDueDateAlerts extends Command {
    protected $signature = 'erp:due-date-alerts';
    protected $description = 'Vadesi geçen veya yaklaşan faturalar için uyarı gönderir.';

    public function handle() {
        $this->info('Vade uyarıları taranıyor...');
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            TenantContext::runAs($tenant->id, function () use ($tenant) {
                $svc = app(NotificationService::class);
                
                // Vadesi 3 gün içinde dolacak veya geçmiş olan satış faturaları
                $arInvoices = FinArInvoice::whereIn('status', ['posted', 'partially_paid'])
                    ->where('due_date', '<=', now()->addDays(3)->toDateString())
                    ->get();

                foreach ($arInvoices as $inv) {
                    $svc->send(
                        'fin.ar_invoice.due_soon',
                        "Vadesi Yaklaşan Fatura: {$inv->invoice_no}",
                        "Sayın {$inv->customer->name}, {$inv->invoice_no} numaralı faturanızın vadesi {$inv->due_date->format('d.m.Y')} tarihinde doluyor. Tutar: {$inv->grand_total}",
                        $inv->customer->email
                    );
                }
                $this->info("Tenant {$tenant->code} için tarama tamamlandı.");
            });
        }
        $this->info('Tüm vade uyarıları gönderildi.');
    }
}
