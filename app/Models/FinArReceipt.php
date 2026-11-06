<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinArReceipt extends Model {
    use HasUuids;
    protected $table = 'fin_ar_receipt';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'customer_id', 'bank_account_id', 'receipt_no',
        'receipt_date', 'amount', 'currency', 'method', 'reference',
        'notes', 'status', 'journal_entry_id',
    ];
    protected $casts = ['receipt_date' => 'date', 'amount' => 'decimal:4'];

    public function customer() { return $this->belongsTo(FinCustomer::class, 'customer_id'); }
    public function bankAccount() { return $this->belongsTo(FinAccount::class, 'bank_account_id'); }
    public function allocations() { return $this->hasMany(FinArReceiptAllocation::class, 'receipt_id'); }
}
