<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinApPayment extends Model
{
    use HasUuids;

    protected $table = 'fin_ap_payment';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'supplier_id', 'bank_account_id', 'payment_no',
        'payment_date', 'amount', 'currency', 'method', 'reference',
        'notes', 'status', 'journal_entry_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:4',
    ];

    public function supplier()
    {
        return $this->belongsTo(FinSupplier::class, 'supplier_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(FinAccount::class, 'bank_account_id');
    }

    public function allocations()
    {
        return $this->hasMany(FinApPaymentAllocation::class, 'payment_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(FinJournalEntry::class, 'journal_entry_id');
    }
}
