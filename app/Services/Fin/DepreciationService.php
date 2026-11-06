<?php
namespace App\Services\Fin;
use App\Models\FinDepreciationSchedule;
use App\Models\FinFixedAsset;
use App\Models\FinAccountingPeriod;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DepreciationService {
    /**
     * Belirli bir dönem için tüm bekleyen amortismanları post eder.
     */
    public function runPeriod(string $year, string $month): int {
        $tenantId = TenantContext::id();

        // Dönem kilitli mi?
        $periodDate = "{$year}-{$month}-01";
        if (FinAccountingPeriod::isLocked($periodDate)) {
            throw ValidationException::withMessages(['period' => 'Bu dönem kilitli. Amortisman post edilemez.']);
        }

        $schedules = FinDepreciationSchedule::where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'pending')
            ->with('asset')
            ->get();

        if ($schedules->isEmpty()) return 0;

        $journalService = app(JournalEntryService::class);
        $postedCount = 0;

        foreach ($schedules as $schedule) {
            $asset = $schedule->asset;
            if (!$asset || $asset->status === 'disposed') continue;

            DB::transaction(function () use ($schedule, $asset, $journalService, $year, $month, &$postedCount) {
                // Yevmiye: Borç 770/730 (Amortisman Gideri) / Alacak 257 (Birikmiş Amortisman)
                $entryLines = [
                    [
                        'account_id' => $asset->depreciation_expense_account_id,
                        'debit' => (float) $schedule->depreciation_amount,
                        'credit' => 0,
                        'description' => "Amortisman - {$asset->code} {$asset->name}",
                    ],
                    [
                        'account_id' => $asset->accumulated_dep_account_id,
                        'debit' => 0,
                        'credit' => (float) $schedule->depreciation_amount,
                        'description' => "Birikmiş amortisman - {$asset->code}",
                    ],
                ];

                $entry = $journalService->create([
                    'entry_date' => "{$year}-{$month}-28", // Ay sonu
                    'description' => "Aylık Amortisman {$month}/{$year} - {$asset->name}",
                ], $entryLines);
                $journalService->post($entry->id);

                $schedule->update([
                    'status' => 'posted',
                    'journal_entry_id' => $entry->id,
                    'posted_at' => now(),
                ]);

                $newAccumulated = (float) $asset->accumulated_depreciation + (float) $schedule->depreciation_amount;
                $newBookValue = (float) $asset->acquisition_cost - $newAccumulated;

                $asset->update([
                    'accumulated_depreciation' => $newAccumulated,
                    'book_value' => max(0, $newBookValue),
                    'status' => $newBookValue <= (float) $asset->salvage_value + 0.0001 ? 'fully_depreciated' : $asset->status,
                ]);

                $postedCount++;
            });
        }

        return $postedCount;
    }
}
