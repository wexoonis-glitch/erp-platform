<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinBankTransaction extends Model {
    use HasUuids;
    protected $table = 'fin_bank_transaction';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'bank_account_id', 'transaction_date', 'type', 'amount', 'counter_account_id', 'description', 'journal_entry_id'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:4'];

    public function bankAccount() { return $this->belongsTo(FinBankAccount::class, 'bank_account_id'); }
    public function counterAccount() { return $this->belongsTo(FinAccount::class, 'counter_account_id'); }
}
