<?php
namespace App\Services\Fin;
use App\Models\FinCurrency;
use App\Models\FinExchangeRate;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CurrencyService {
    public function createCurrency(array $data): FinCurrency {
        $tenantId = TenantContext::id();
        if (FinCurrency::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu para birimi zaten tanımlı.']);
        }

        // Eğer bu ilk para birimiyse veya base olarak işaretlendiyse, diğerlerinin base'ini kaldır
        if (($data['is_base'] ?? false) || FinCurrency::count() === 0) {
            FinCurrency::where('is_base', true)->update(['is_base' => false]);
            $data['is_base'] = true;
        }

        return FinCurrency::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'code' => strtoupper($data['code']), 'name' => $data['name'],
            'symbol' => $data['symbol'] ?? null, 'is_base' => $data['is_base'] ?? false, 'status' => 'active',
        ]);
    }

    public function addRate(array $data): FinExchangeRate {
        $tenantId = TenantContext::id();
        $currency = FinCurrency::findOrFail($data['currency_id']);
        
        if ($currency->is_base) {
            throw ValidationException::withMessages(['currency_id' => 'Ana para birimine kur eklenemez.']);
        }

        return FinExchangeRate::updateOrCreate(
            ['tenant_id' => $tenantId, 'currency_id' => $currency->id, 'rate_date' => $data['rate_date']],
            ['id' => (string) Str::uuid(), 'rate' => $data['rate']]
        );
    }
}
