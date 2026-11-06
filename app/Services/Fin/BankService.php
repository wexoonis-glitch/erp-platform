<?php
namespace App\Services\Fin;
use App\Models\FinBankAccount;
use App\Models\FinBankTransaction;
use App\Models\FinAccount;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BankService {
    public function createAccount(array $data): FinBankAccount {
        $tenantId = TenantContext::id();
        return FinBankAccount::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'name' => $data['name'], 'type' => $data['type'],
            'account_no' => $data['account_no'] ?? null, 'iban' => $data['iban'] ?? null,
            'currency' => $data['currency'] ?? 'TRY', 'gl_account_id' => $data['gl_account_id'], 'status' => 'active',
        ]);
    }

    public function recordDeposit(array $data): FinBankTransaction {
        $tenantId = TenantContext::id();
        if ((float)$data['amount'] <= 0) throw ValidationException::withMessages(['amount' => 'Tutar 0 dan büyük olmalı.']);
        $bank = FinBankAccount::findOrFail($data['bank_account_id']);
        $counter = FinAccount::findOrFail($data['counter_account_id']);

        return DB::transaction(function () use ($data, $tenantId, $bank, $counter) {
            $tx = FinBankTransaction::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'bank_account_id' => $bank->id,
                'transaction_date' => $data['transaction_date'], 'type' => 'deposit',
                'amount' => $data['amount'], 'counter_account_id' => $counter->id, 'description' => $data['description'],
            ]);

            $entryLines = [
                ['account_id' => $bank->gl_account_id, 'debit' => (float)$data['amount'], 'credit' => 0, 'description' => $data['description']],
                ['account_id' => $counter->id, 'debit' => 0, 'credit' => (float)$data['amount'], 'description' => $data['description']],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create(['entry_date' => $data['transaction_date'], 'description' => "Banka Girişi: {$bank->name}"], $entryLines);
            $journalService->post($entry->id);

            $tx->update(['journal_entry_id' => $entry->id]);
            return $tx;
        });
    }

    public function recordWithdrawal(array $data): FinBankTransaction {
        $tenantId = TenantContext::id();
        if ((float)$data['amount'] <= 0) throw ValidationException::withMessages(['amount' => 'Tutar 0 dan büyük olmalı.']);
        $bank = FinBankAccount::findOrFail($data['bank_account_id']);
        $counter = FinAccount::findOrFail($data['counter_account_id']);

        return DB::transaction(function () use ($data, $tenantId, $bank, $counter) {
            $tx = FinBankTransaction::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'bank_account_id' => $bank->id,
                'transaction_date' => $data['transaction_date'], 'type' => 'withdrawal',
                'amount' => $data['amount'], 'counter_account_id' => $counter->id, 'description' => $data['description'],
            ]);

            $entryLines = [
                ['account_id' => $counter->id, 'debit' => (float)$data['amount'], 'credit' => 0, 'description' => $data['description']],
                ['account_id' => $bank->gl_account_id, 'debit' => 0, 'credit' => (float)$data['amount'], 'description' => $data['description']],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create(['entry_date' => $data['transaction_date'], 'description' => "Banka Çıkışı: {$bank->name}"], $entryLines);
            $journalService->post($entry->id);

            $tx->update(['journal_entry_id' => $entry->id]);
            return $tx;
        });
    }
}
