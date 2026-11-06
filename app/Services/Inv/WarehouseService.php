<?php
namespace App\Services\Inv;
use App\Models\InvWarehouse;
use App\Models\InvStockTransfer;
use App\Models\InvStockTransferLine;
use App\Models\InvProduct;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class WarehouseService {
    public function createWarehouse(array $data): InvWarehouse {
        $tenantId = TenantContext::id();
        if (InvWarehouse::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu depo kodu zaten kullanımda.']);
        }
        return InvWarehouse::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'code' => $data['code'], 'name' => $data['name'], 'status' => 'active',
        ]);
    }

    public function transfer(array $data): InvStockTransfer {
        $tenantId = TenantContext::id();
        
        if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Kaynak ve hedef depo aynı olamaz.']);
        }

        $product = InvProduct::findOrFail($data['product_id']);
        $qty = (float) $data['quantity'];

        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Miktar 0 dan büyük olmalı.']);
        }

        $stockService = app(StockService::class);
        $sourceBalance = $stockService->getBalance($product->id, $data['from_warehouse_id']);

        if ($sourceBalance < $qty) {
            throw ValidationException::withMessages(['quantity' => "Kaynak depoda yetersiz stok. Mevcut: {$sourceBalance}, İstenen: {$qty}"]);
        }

        return DB::transaction(function () use ($data, $tenantId, $product, $qty, $stockService) {
            $transfer = InvStockTransfer::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'transfer_no' => $this->nextTransferNo($tenantId),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_date' => $data['transfer_date'],
                'status' => 'completed', 'notes' => $data['notes'] ?? null,
            ]);

            InvStockTransferLine::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'transfer_id' => $transfer->id, 'product_id' => $product->id, 'quantity' => $qty,
            ]);

            // Stok Çıkışı (Kaynak Depo)
            $stockService->recordOut(
                $product->id, $data['from_warehouse_id'], $data['transfer_date'], $qty, 
                (float)$product->purchase_price, 'TRANSFER_OUT', $transfer->id, "Transfer: {$transfer->transfer_no}"
            );

            // Stok Girişi (Hedef Depo)
            $stockService->recordIn(
                $product->id, $data['to_warehouse_id'], $data['transfer_date'], $qty, 
                (float)$product->purchase_price, 'TRANSFER_IN', $transfer->id, "Transfer: {$transfer->transfer_no}"
            );

            return $transfer->fresh();
        });
    }

    private function nextTransferNo(string $tenantId): string {
        $year = date('Y'); $prefix = "TRF-{$year}-";
        $last = InvStockTransfer::where('tenant_id', $tenantId)->where('transfer_no', 'like', $prefix . '%')->orderByDesc('transfer_no')->value('transfer_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
