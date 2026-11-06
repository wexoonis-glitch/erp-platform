<?php
namespace App\Services\Fin;

use App\Models\FinSaleExpenseDefault;
use App\Models\SysTenantSetting;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sərfiyyat (Satış Masraf Dağıtımı) Servisi
 *
 * Ayar yönetimi + varsayılan sərfler + satış anındaki sərf dağıtım hesabı.
 */
final class SarfiyyatService
{
    public const CONFIG_KEY = 'sarfiyyat_config';

    /** Ayar varsayılanları */
    public function getConfig(): array
    {
        $defaults = [
            'show_in_sales'            => true,
            'required_in_sale'         => false,
            'charge_customer_default'  => false,
        ];

        $stored = SysTenantSetting::query()
            ->where('key', self::CONFIG_KEY)
            ->value('value');

        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    /** Ayar kaydet */
    public function saveConfig(array $config): void
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) {
            throw new \RuntimeException('Tenant context yok.');
        }

        $normalized = [
            'show_in_sales'           => (bool) ($config['show_in_sales'] ?? true),
            'required_in_sale'        => (bool) ($config['required_in_sale'] ?? false),
            'charge_customer_default' => (bool) ($config['charge_customer_default'] ?? false),
        ];

        SysTenantSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => self::CONFIG_KEY],
            ['value' => $normalized]
        );
    }

    /** Varsayılan sərfler */
    public function defaultExpenses(): Collection
    {
        return FinSaleExpenseDefault::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Varsayılan sərf ekle */
    public function addDefault(string $name, float $amount = 0, int $order = 0): FinSaleExpenseDefault
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \RuntimeException('Tenant context yok.');

        return FinSaleExpenseDefault::create([
            'id'             => (string) Str::uuid(),
            'tenant_id'      => $tenantId,
            'name'           => trim($name),
            'default_amount' => max(0, $amount),
            'is_active'      => true,
            'sort_order'     => $order,
        ]);
    }

    /** Varsayılan sərf güncelle */
    public function updateDefault(string $id, array $data): void
    {
        $row = FinSaleExpenseDefault::findOrFail($id);

        if (isset($data['name']))           $row->name = trim($data['name']);
        if (isset($data['default_amount'])) $row->default_amount = max(0, (float) $data['default_amount']);
        if (isset($data['is_active']))      $row->is_active = (bool) $data['is_active'];
        if (isset($data['sort_order']))     $row->sort_order = (int) $data['sort_order'];

        $row->save();
    }

    /** Varsayılan sərf sil */
    public function deleteDefault(string $id): void
    {
        FinSaleExpenseDefault::where('id', $id)->delete();
    }

    /**
     * Sərf tutarını satır tutarlarına göre oransal dağıt.
     *
     * @param float $totalAmount    Toplam sərf tutarı
     * @param array $lineAmounts    [lineId => baseAmount] — örn. her ürünün net tutarı
     * @param bool  $chargeCustomer true ise müşteriye yansıtılacak (kârdan düşülmez)
     * @return array                [lineId => share]
     */
    public function distributeAmount(float $totalAmount, array $lineAmounts, bool $chargeCustomer): array
    {
        $result = [];
        if ($totalAmount <= 0 || empty($lineAmounts)) return $result;

        $sum = array_sum(array_map(fn($v) => max(0, (float) $v), $lineAmounts));
        if ($sum <= 0) return $result;

        $keys = array_keys($lineAmounts);
        $distributed = 0.0;
        $lastIdx = count($keys) - 1;

        foreach ($keys as $i => $key) {
            $base = max(0, (float) $lineAmounts[$key]);
            if ($i === $lastIdx) {
                $share = round($totalAmount - $distributed, 4);
            } else {
                $share = round(($base / $sum) * $totalAmount, 4);
                $distributed += $share;
            }
            $result[$key] = $share;
        }

        return $result;
    }

    /**
     * Satır bazlı kâr hesabı (sərf dağıtılmış hali).
     *
     * @param array $lines Her satır: ['lineId','qty','price','discount' => %, 'unitCost']
     * @param float $sarfTotal Toplam sərf
     * @param bool  $chargeCustomer Müşteriye mi?
     * @return array ['totalRevenue','totalCost','totalSarf','totalProfit','lines' => [lineId => [...]]]
     */
    public function calculateSaleProfit(array $lines, float $sarfTotal, bool $chargeCustomer): array
    {
        $baseAmounts = [];
        $lineData = [];

        foreach ($lines as $line) {
            $qty      = (float) ($line['qty'] ?? 0);
            $price    = (float) ($line['price'] ?? 0);
            $discount = (float) ($line['discount'] ?? 0);
            $unitCost = (float) ($line['unitCost'] ?? 0);

            $gross = $qty * $price;
            $net   = $gross * (1 - $discount / 100);

            $lineId = (string) $line['lineId'];
            $baseAmounts[$lineId] = $net;
            $lineData[$lineId] = [
                'qty'      => $qty,
                'net'      => $net,
                'cost'     => $qty * $unitCost,
                'sarf'     => 0.0,
            ];
        }

        $shares = $this->distributeAmount($sarfTotal, $baseAmounts, $chargeCustomer);

        $totalRevenue = 0.0;
        $totalCost    = 0.0;
        $totalSarf    = 0.0;

        foreach ($lineData as $lineId => &$d) {
            $sarf = (float) ($shares[$lineId] ?? 0);
            $d['sarf'] = $sarf;

            // Kâr = net - maliyet - (chargeCustomer değilse sərf)
            $effectiveSarf = $chargeCustomer ? 0.0 : $sarf;
            $d['profit'] = $d['net'] - $d['cost'] - $effectiveSarf;

            $totalRevenue += $d['net'];
            $totalCost    += $d['cost'];
            $totalSarf    += $sarf;
        }
        unset($d);

        $totalProfit = $totalRevenue - $totalCost - ($chargeCustomer ? 0.0 : $totalSarf);

        return [
            'totalRevenue' => round($totalRevenue, 4),
            'totalCost'    => round($totalCost, 4),
            'totalSarf'    => round($totalSarf, 4),
            'totalProfit'  => round($totalProfit, 4),
            'lines'        => $lineData,
        ];
    }
}
