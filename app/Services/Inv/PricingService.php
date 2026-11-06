<?php
namespace App\Services\Inv;

use App\Models\InvProduct;

final class PricingService
{
    /**
     * Önerilen satış fiyatı = (replacement_cost + overhead_per_unit) × (1 + target_margin%).
     */
    public function suggestedPrice(InvProduct $p): float
    {
        $base = $this->costBase($p);
        $margin = ((float) ($p->target_margin_pct ?? 30)) / 100;
        return round($base * (1 + $margin), 4);
    }

    /**
     * Minimum satış fiyatı = (replacement_cost + overhead_per_unit) × (1 + min_margin%).
     * Bu fiyatın altında satış → uyarı (engellenmez).
     */
    public function minimumPrice(InvProduct $p): float
    {
        $base = $this->costBase($p);
        $margin = ((float) ($p->min_margin_pct ?? 10)) / 100;
        return round($base * (1 + $margin), 4);
    }

    /**
     * Maliyet tabanı: replacement_cost > 0 ise onu kullan, yoksa purchase_price.
     * + birim başına genel gider.
     */
    private function costBase(InvProduct $p): float
    {
        $replacement = (float) ($p->replacement_cost ?? 0);
        $fallback = (float) ($p->purchase_price ?? 0);
        $cost = $replacement > 0 ? $replacement : $fallback;
        return $cost + (float) ($p->overhead_per_unit ?? 0);
    }

    /**
     * Bir satış fiyatı için analiz. Hem muhasebe (FIFO) hem nakit (replacement) bakışı.
     */
    public function analyze(InvProduct $p, float $unitPrice): array
    {
        $base = $this->costBase($p);
        $fifoCost = (float) $p->purchase_price;
        $minPrice = $this->minimumPrice($p);
        $suggested = $this->suggestedPrice($p);

        $accountingProfit = $unitPrice - $fifoCost;   // FIFO COGS bazlı
        $cashProfit = $unitPrice - $base;             // yenileme maliyeti bazlı
        $cashMarginPct = $base > 0 ? ($cashProfit / $base) * 100 : 0;

        return [
            'unit_price'       => round($unitPrice, 4),
            'fifo_cost'        => round($fifoCost, 4),
            'replacement_cost' => round($base, 4),
            'minimum_price'    => $minPrice,
            'suggested_price'  => $suggested,
            'accounting_profit'=> round($accountingProfit, 4),
            'cash_profit'      => round($cashProfit, 4),
            'cash_margin_pct'  => round($cashMarginPct, 2),
            'below_min'        => $unitPrice < $minPrice - 0.0001,
            'below_cost'       => $cashProfit < -0.0001,
        ];
    }

    /**
     * Mal kabul / alış faturası sonrası "son alış fiyatı"nı günceller.
     */
    public function updateReplacementCost(string $productId, float $unitCost): void
    {
        if ($unitCost > 0) {
            InvProduct::where('id', $productId)->update(['replacement_cost' => $unitCost]);
        }
    }
}
