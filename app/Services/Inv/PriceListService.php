<?php
namespace App\Services\Inv;
use App\Models\FinCustomer;
use App\Models\InvPriceList;
use App\Models\InvPriceListItem;
use App\Models\InvProduct;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PriceListService {
    /**
     * Fiyat çözümleme sırası:
     * 1) Müşteriye atanmış fiyat listesi (varsa)
     * 2) Varsayılan fiyat listesi (is_default)
     * 3) Ürünün kendi sales_price'ı
     *
     * Her adımda quantity break (min_quantity <= qty) ve tarih uygunluğu kontrol edilir.
     */
    public function resolve(string $productId, ?string $customerId, float $qty, ?string $date = null): float {
        $date = $date ?? now()->toDateString();
        $qty = max(1, $qty);

        if ($customerId) {
            $customer = FinCustomer::find($customerId);
            if ($customer && $customer->price_list_id) {
                $price = $this->findPrice($customer->price_list_id, $productId, $qty, $date);
                if ($price !== null) return $price;
            }
        }

        $default = InvPriceList::where('is_default', true)
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date))
            ->where(fn($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $date))
            ->first();

        if ($default) {
            $price = $this->findPrice($default->id, $productId, $qty, $date);
            if ($price !== null) return $price;
        }

        $product = InvProduct::find($productId);
        return (float) ($product->sales_price ?? 0);
    }

    private function findPrice(string $priceListId, string $productId, float $qty, string $date): ?float {
        $list = InvPriceList::find($priceListId);
        if (!$list || $list->status !== 'active') return null;
        if ($list->valid_from && $list->valid_from->toDateString() > $date) return null;
        if ($list->valid_to && $list->valid_to->toDateString() < $date) return null;

        $item = InvPriceListItem::where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->where('min_quantity', '<=', $qty)
            ->orderByDesc('min_quantity')
            ->first();

        if (!$item) return null;

        $discount = (float) $item->discount_rate / 100;
        return round((float) $item->unit_price * (1 - $discount), 4);
    }

    public function createList(array $data, array $items): InvPriceList {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        if (InvPriceList::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu fiyat listesi kodu zaten kullanımda.']);
        }

        if (!empty($data['is_default'])) {
            InvPriceList::where('is_default', true)->update(['is_default' => false]);
        }

        $list = InvPriceList::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'code' => $data['code'], 'name' => $data['name'],
            'currency' => $data['currency'] ?? 'TRY',
            'valid_from' => $data['valid_from'] ?: null,
            'valid_to' => $data['valid_to'] ?: null,
            'priority' => (int) ($data['priority'] ?? 100),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'status' => 'active',
        ]);

        foreach ($items as $i => $item) {
            if (empty($item['product_id'])) continue;
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            if ($unitPrice < 0) {
                throw ValidationException::withMessages(["items.{$i}.unit_price" => 'Fiyat negatif olamaz.']);
            }

            InvPriceListItem::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'price_list_id' => $list->id, 'product_id' => $item['product_id'],
                'unit_price' => $unitPrice,
                'min_quantity' => (float) ($item['min_quantity'] ?? 1),
                'discount_rate' => (float) ($item['discount_rate'] ?? 0),
            ]);
        }

        return $list->fresh();
    }

    public function delete(string $id): void {
        $list = InvPriceList::findOrFail($id);
        $list->delete();
    }
}
