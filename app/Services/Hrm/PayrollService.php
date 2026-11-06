<?php
namespace App\Services\Hrm;
use App\Models\HrPayrollRun;
use App\Models\HrPayrollItem;
use App\Models\HrEmployee;
use App\Models\FinAccount;
use App\Services\Fin\JournalEntryService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PayrollService {
    public function generatePayroll(string $period, string $paymentDate): HrPayrollRun {
        $tenantId = TenantContext::id();
        
        if (HrPayrollRun::where('period', $period)->exists()) {
            throw ValidationException::withMessages(['period' => 'Bu dönem için zaten bordro oluşturulmuş.']);
        }

        $employees = HrEmployee::where('status', 'active')->get();
        if ($employees->isEmpty()) {
            throw ValidationException::withMessages(['employees' => 'Aktif personel bulunamadı.']);
        }

        return DB::transaction(function () use ($period, $paymentDate, $tenantId, $employees) {
            $run = HrPayrollRun::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'period' => $period, 'payment_date' => $paymentDate, 'status' => 'draft',
            ]);

            $totalGross = 0; $totalDeductions = 0; $totalNet = 0;

            foreach ($employees as $emp) {
                $gross = (float) $emp->gross_salary;
                if ($gross <= 0) continue;

                // Basit Türkiye Bordro Hesaplaması (Örnek Oranlar)
                $sgkEmp = round($gross * 0.14, 4);
                $unemploymentEmp = round($gross * 0.01, 4);
                $incomeTaxBase = $gross - $sgkEmp - $unemploymentEmp;
                $incomeTax = round($incomeTaxBase * 0.15, 4); // Basit %15 dilim
                $stampTax = round($gross * 0.00759, 4);
                
                $totalDeduction = $sgkEmp + $unemploymentEmp + $incomeTax + $stampTax;
                $net = $gross - $totalDeduction;
                $sgkEmployer = round($gross * 0.205, 4);

                HrPayrollItem::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                    'payroll_run_id' => $run->id, 'employee_id' => $emp->id,
                    'gross_salary' => $gross, 'sgk_employee' => $sgkEmp, 'sgk_employer' => $sgkEmployer,
                    'unemployment_employee' => $unemploymentEmp, 'income_tax' => $incomeTax,
                    'stamp_tax' => $stampTax, 'net_salary' => $net,
                ]);

                $totalGross += $gross;
                $totalDeductions += $totalDeduction;
                $totalNet += $net;
            }

            $run->update(['total_gross' => $totalGross, 'total_deductions' => $totalDeductions, 'total_net' => $totalNet]);
            return $run->fresh();
        });
    }

    public function postPayroll(string $id): HrPayrollRun {
        $run = HrPayrollRun::findOrFail($id);
        if ($run->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft bordro post edilebilir.']);

        $salaryExpenseAcc = FinAccount::where('code', '770')->first(); // Genel Yönetim Giderleri
        $bankAcc = FinAccount::where('code', '102')->first(); // Bankalar
        $taxAcc = FinAccount::where('code', '360')->first(); // Ödenecek Vergi ve Fonlar (Yoksa 391 kullanabiliriz veya oluştururuz)
        $sgkAcc = FinAccount::where('code', '361')->first(); // Ödenecek SGK (Yoksa 320 kullanabiliriz)

        if (!$salaryExpenseAcc || !$bankAcc) throw ValidationException::withMessages(['accounts' => 'Gerekli muhasebe hesapları (770, 102) eksik.']);

        // Eğer 360 ve 361 yoksa geçici olarak 320 (Satıcılar) kullanıyoruz (Seed'de olmadığı için)
        $taxAccId = $taxAcc?->id ?? FinAccount::where('code', '320')->first()?->id;
        $sgkAccId = $sgkAcc?->id ?? FinAccount::where('code', '320')->first()?->id;

        return DB::transaction(function () use ($run, $salaryExpenseAcc, $bankAcc, $taxAccId, $sgkAccId) {
            // Toplam İşveren Maliyeti = Brüt + SGK İşveren
            $totalEmployerSgk = $run->items->sum('sgk_employer');
            $totalCost = (float)$run->total_gross + $totalEmployerSgk;
            
            // Toplam Kesintiler (Vergi + SGK İşçi + İşsizlik + Damga)
            $totalTaxes = $run->items->sum('income_tax') + $run->items->sum('stamp_tax');
            $totalSgkEmployee = $run->items->sum('sgk_employee') + $run->items->sum('unemployment_employee');

            $entryLines = [
                // Borç: Maaş Gideri (Brüt + SGK İşveren)
                ['account_id' => $salaryExpenseAcc->id, 'debit' => $totalCost, 'credit' => 0, 'description' => "Bordro Gideri {$run->period}"],
                // Alacak: Ödenecek Vergiler
                ['account_id' => $taxAccId, 'debit' => 0, 'credit' => $totalTaxes, 'description' => "Gelir Vergisi ve Damga"],
                // Alacak: Ödenecek SGK (İşçi + İşveren)
                ['account_id' => $sgkAccId, 'debit' => 0, 'credit' => $totalSgkEmployee + $totalEmployerSgk, 'description' => "SGK Kesintileri"],
                // Alacak: Personele Net Ödenecek (Banka)
                ['account_id' => $bankAcc->id, 'debit' => 0, 'credit' => (float)$run->total_net, 'description' => "Net Maaş Ödemesi"],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date' => $run->payment_date->toDateString(),
                'description' => "Bordro Ödemesi {$run->period}",
            ], $entryLines);
            $journalService->post($entry->id);

            $run->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);
            return $run->fresh();
        });
    }
}
