<?php
namespace App\Services\Platform;

use App\Models\FinArInvoice;
use App\Models\FinApInvoice;
use App\Models\FinAccount;
use App\Models\InvProduct;
use App\Models\InvStockMovement;
use App\Models\HrEmployee;
use App\Models\OtcSalesOrder;
use App\Models\PrcPurchaseOrder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function getKpis(): array
    {
        // 1. Aylık Ciro (Bu ay kesilen AR Faturaları)
        $monthlyRevenue = FinArInvoice::where('status', '!=', 'cancelled')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('grand_total');

        // 2. Tahsilatlar (Bu ay yapılan tahsilatlar - Basitlik için AR Receipt tablosundan çekeceğiz)
        $monthlyCollections = DB::table('fin_ar_receipt')
            ->where('status', 'posted')
            ->whereMonth('receipt_date', now()->month)
            ->whereYear('receipt_date', now()->year)
            ->sum('amount');

        // 3. Açık Alacaklar (Müşterilerin bize borcu)
        $openReceivables = FinArInvoice::whereIn('status', ['posted', 'partially_paid'])
            ->sum(DB::raw('grand_total - paid_total'));

        // 4. Açık Borçlar (Bizim tedarikçilere borcumuz)
        $openPayables = FinApInvoice::whereIn('status', ['posted', 'partially_paid'])
            ->sum(DB::raw('grand_total - paid_total'));

        // 5. Toplam Stok Değeri (Basit bir hesaplama: Toplam Giriş - Çıkış * Maliyet)
        // Gerçekte FIFO/Ortalama maliyet gerekir, burada yaklaşık bir değer buluyoruz.
        $stockValue = InvStockMovement::selectRaw("
            SUM(CASE WHEN type = 'in' THEN quantity * unit_cost ELSE 0 END) - 
            SUM(CASE WHEN type = 'out' THEN quantity * unit_cost ELSE 0 END) as val
        ")->value('val') ?? 0;

        // 6. Aktif Personel
        $activeEmployees = HrEmployee::where('status', 'active')->count();

        return [
            'monthly_revenue'   => (float) $monthlyRevenue,
            'monthly_collections'=> (float) $monthlyCollections,
            'open_receivables'  => (float) $openReceivables,
            'open_payables'     => (float) $openPayables,
            'stock_value'       => (float) $stockValue,
            'active_employees'  => $activeEmployees,
        ];
    }

    public function getSalesChartData(): array
    {
        // Son 6 ayın satış verilerini çek
        $data = FinArInvoice::where('status', '!=', 'cancelled')
            ->where('invoice_date', '>=', now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(invoice_date, 'YYYY-MM') as month"),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $values = [];

        // Eksik ayları 0 ile doldur
        for ($i = 5; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->translatedFormat('M Y');
            
            $labels[] = $monthLabel;
            $found = $data->firstWhere('month', $monthKey);
            $values[] = $found ? (float) $found->total : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getRecentActivity(): array
    {
        // Son 5 satış siparişi
        return OtcSalesOrder::with('customer')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'type' => 'Satış Siparişi',
                'desc' => "{$o->so_no} - {$o->customer->name}",
                'amount' => $o->grand_total,
                'status' => $o->status,
                'date' => $o->created_at->diffForHumans(),
            ])->toArray();
    }
}
