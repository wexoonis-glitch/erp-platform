<?php
namespace App\Services\Fin;
use App\Models\FinFixedAsset;
use App\Models\FinDepreciationSchedule;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FixedAssetService {
    /**
     * Yeni duran varlık oluşturur ve amortisman planını otomatik üretir.
     */
    public function create(array $data): FinFixedAsset {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        if (FinFixedAsset::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu demirbaş kodu zaten kullanımda.']);
        }

        $cost = (float) $data['acquisition_cost'];
        $salvage = (float) ($data['salvage_value'] ?? 0);
        $lifeMonths = (int) $data['useful_life_months'];

        if ($cost <= $salvage) {
            throw ValidationException::withMessages(['acquisition_cost' => 'Maliyet, hurda değerinden büyük olmalı.']);
        }
        if ($lifeMonths <= 0) {
            throw ValidationException::withMessages(['useful_life_months' => 'Faydalı ömür > 0 olmalı.']);
        }

        return DB::transaction(function () use ($data, $tenantId, $cost, $salvage, $lifeMonths) {
            $asset = FinFixedAsset::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'code' => $data['code'], 'name' => $data['name'], 'category' => $data['category'],
                'acquisition_date' => $data['acquisition_date'],
                'acquisition_cost' => $cost,
                'useful_life_months' => $lifeMonths,
                'depreciation_method' => $data['depreciation_method'] ?? 'straight_line',
                'salvage_value' => $salvage,
                'accumulated_depreciation' => 0,
                'book_value' => $cost,
                'asset_account_id' => $data['asset_account_id'],
                'accumulated_dep_account_id' => $data['accumulated_dep_account_id'],
                'depreciation_expense_account_id' => $data['depreciation_expense_account_id'],
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'status' => 'active',
                'eam_asset_id' => $data['eam_asset_id'] ?? null,
            ]);

            $this->generateSchedule($asset);

            return $asset->fresh();
        });
    }

    /**
     * Amortisman planını oluşturur (düz hat yöntemi).
     */
    private function generateSchedule(FinFixedAsset $asset): void {
        $depreciable = (float) $asset->acquisition_cost - (float) $asset->salvage_value;
        $monthly = round($depreciable / $asset->useful_life_months, 4);

        $start = $asset->acquisition_date->copy()->startOfMonth();
        $accumulated = 0;

        for ($i = 0; $i < $asset->useful_life_months; $i++) {
            $period = $start->copy()->addMonths($i);
            $accumulated += $monthly;

            // Son taksit: yuvarlama farkını düzelt
            if ($i === $asset->useful_life_months - 1) {
                $monthly = round($depreciable - ($monthly * ($asset->useful_life_months - 1)), 4);
                $accumulated = $depreciable;
            }

            FinDepreciationSchedule::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'tenant_id' => $asset->tenant_id,
                'asset_id' => $asset->id,
                'period_year' => $period->format('Y'),
                'period_month' => $period->format('m'),
                'depreciation_amount' => $monthly,
                'accumulated_after' => $accumulated,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Varlığı elden çıkarır (satış/hurda). Kâr/zarar yevmiyesi oluşturur.
     */
    public function dispose(string $id, float $proceeds, string $date, ?string $notes = null): FinFixedAsset {
        $asset = FinFixedAsset::findOrFail($id);
        if ($asset->status === 'disposed') {
            throw ValidationException::withMessages(['status' => 'Bu varlık zaten elden çıkarılmış.']);
        }

        return DB::transaction(function () use ($asset, $proceeds, $date, $notes) {
            $bookValue = (float) $asset->book_value;
            $gainLoss = $proceeds - $bookValue;

            // Yevmiye: 
            // Borç: 257 Birikmiş Amortisman (accumulated_depreciation)
            // Borç: Kasa/Banka (proceeds)
            // Borç: 659 Diğer Olağan Gider ve Zararlar (if loss) VEYA Alacak: 679 Diğer Olağandışı Gelirler (if gain)
            // Alacak: 253/254/255 Varlık hesabı (acquisition_cost)

            $entryLines = [];

            // Birikmiş amortismanı kapat (Borç)
            if ((float) $asset->accumulated_depreciation > 0) {
                $entryLines[] = [
                    'account_id' => $asset->accumulated_dep_account_id,
                    'debit' => (float) $asset->accumulated_depreciation,
                    'credit' => 0,
                    'description' => "Birikmiş amortisman kapatma - {$asset->code}",
                ];
            }

            // Satış tutarı (Borç - Kasa/Banka)
            if ($proceeds > 0) {
                $cashAccount = \App\Models\FinAccount::where('code', '100')->first();
                if ($cashAccount) {
                    $entryLines[] = [
                        'account_id' => $cashAccount->id,
                        'debit' => $proceeds,
                        'credit' => 0,
                        'description' => "Elden çıkarma bedeli - {$asset->code}",
                    ];
                }
            }

            // Kâr/zarar hesabı
            if (abs($gainLoss) > 0.0001) {
                if ($gainLoss < 0) {
                    // Zarar - 659
                    $lossAccount = \App\Models\FinAccount::where('code', '659')->first();
                    if ($lossAccount) {
                        $entryLines[] = [
                            'account_id' => $lossAccount->id,
                            'debit' => abs($gainLoss),
                            'credit' => 0,
                            'description' => "Elden çıkarma zararı - {$asset->code}",
                        ];
                    }
                } else {
                    // Kâr - 679
                    $gainAccount = \App\Models\FinAccount::where('code', '679')->first();
                    if ($gainAccount) {
                        $entryLines[] = [
                            'account_id' => $gainAccount->id,
                            'debit' => 0,
                            'credit' => $gainLoss,
                            'description' => "Elden çıkarma kârı - {$asset->code}",
                        ];
                    }
                }
            }

            // Varlık hesabını kapat (Alacak)
            $entryLines[] = [
                'account_id' => $asset->asset_account_id,
                'debit' => 0,
                'credit' => (float) $asset->acquisition_cost,
                'description' => "Varlık çıkışı - {$asset->code}",
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date' => $date,
                'description' => "Duran Varlık Elden Çıkarma - {$asset->name}",
            ], $entryLines);
            $journalService->post($entry->id);

            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $date,
                'disposal_amount' => $proceeds,
                'disposal_notes' => $notes,
                'book_value' => 0,
            ]);

            return $asset->fresh();
        });
    }
}
