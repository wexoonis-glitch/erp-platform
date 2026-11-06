<?php
namespace App\Services\Hrm;

use App\Models\HrExpenseClaim;
use App\Models\FinAccount;
use App\Models\FinBankAccount;
use App\Models\SysApprovalRequest;
use App\Services\Fin\JournalEntryService;
use App\Services\Platform\NotificationService;
use App\Services\Platform\WorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ExpenseService {

    public function create(array $data): HrExpenseClaim {
        $tenantId = TenantContext::id();
        return HrExpenseClaim::create([
            'id'           => (string) Str::uuid(),
            'tenant_id'    => $tenantId,
            'employee_id'  => $data['employee_id'],
            'expense_date' => $data['expense_date'],
            'category'     => $data['category'],
            'amount'       => $data['amount'],
            'description'  => $data['description'],
            'status'       => 'draft',
        ]);
    }

    public function submit(string $id): void {
        $claim = HrExpenseClaim::findOrFail($id);
        if ($claim->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Sadece taslaklar onaya sunulabilir.']);
        }
        $claim->update(['status' => 'submitted']);

        // Eğer 'expense' için aktif bir onay akışı varsa workflow başlat
        try {
            $started = app(WorkflowService::class)->startRequest(
                'expense',
                $claim->id,
                "Masraf: " . ($claim->description ?: $claim->id),
                (float) $claim->amount,
                [
                    'amount' => (float) $claim->amount,
                    'description' => $claim->description,
                    'employee' => $claim->employee?->first_name . ' ' . $claim->employee?->last_name,
                    'category' => $claim->category,
                ]
            );
        } catch (ValidationException $e) {
            $started = null; // bekleyen var; aşağıda direkt bildirime düşmesin
        }

        // Workflow başlamadıysa, mevcut davranış: yöneticiye bildirim
        if (!$started && !SysApprovalRequest::where('entity_type', 'expense')->where('entity_id', $claim->id)->where('status', 'pending')->exists()) {
            app(NotificationService::class)->notify(
                'info',
                'Yeni Masraf Onayı',
                "{$claim->employee->first_name} {$claim->employee->last_name} tarafından {$claim->amount} ₺ tutarında bir masraf onaya sunuldu.",
                '/hrm/expenses'
            );
        }
    }

    public function approve(string $id): void {
        $claim = HrExpenseClaim::findOrFail($id);
        if ($claim->status !== 'submitted') {
            throw ValidationException::withMessages(['status' => 'Sadece onaya sunulmuş masraflar onaylanabilir.']);
        }

        // Bekleyen bir workflow varsa, doğrudan onaylamayı engelle
        $pending = SysApprovalRequest::where('entity_type', 'expense')
            ->where('entity_id', $claim->id)
            ->where('status', 'pending')
            ->exists();
        if ($pending) {
            throw ValidationException::withMessages(['status' => 'Bu masraf onay sürecinde. Lütfen "Onaylar > Onay Kutusu" menüsünden işlem yapın.']);
        }

        $claim->update(['status' => 'approved']);

        app(NotificationService::class)->notify(
            'success',
            'Masrafınız Onaylandı',
            "{$claim->description} açıklamalı {$claim->amount} ₺ tutarındaki masrafınız onaylandı. Ödeme aşamasına geçebilir.",
            '/hrm/expenses',
            $claim->employee->user_id ?? null
        );
    }

    public function pay(string $id, string $bankAccountId): HrExpenseClaim {
        $claim = HrExpenseClaim::findOrFail($id);
        if ($claim->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Sadece onaylanmış masraflar ödenebilir.']);
        }

        $bank = FinBankAccount::findOrFail($bankAccountId);
        $expenseAccount = FinAccount::where('code', '770')->first();
        if (!$expenseAccount) {
            throw ValidationException::withMessages(['account' => '770 hesabı bulunamadı.']);
        }

        return DB::transaction(function () use ($claim, $bank, $expenseAccount) {
            $entryLines = [
                [
                    'account_id'  => $expenseAccount->id,
                    'debit'       => (float) $claim->amount,
                    'credit'      => 0,
                    'description' => "Masraf: {$claim->description}",
                ],
                [
                    'account_id'  => $bank->gl_account_id,
                    'debit'       => 0,
                    'credit'      => (float) $claim->amount,
                    'description' => "Masraf Ödemesi - {$claim->employee->first_name}",
                ],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date'  => now()->toDateString(),
                'description' => "Personel Masraf Ödemesi - {$claim->employee->first_name} {$claim->employee->last_name}",
            ], $entryLines);
            $journalService->post($entry->id);

            $claim->update([
                'status'             => 'paid',
                'payment_account_id' => $bank->id,
                'journal_entry_id'   => $entry->id,
            ]);

            return $claim->fresh();
        });
    }
}
