<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HrExpenseClaim extends Model {
    use HasUuids;
    protected $table = 'hr_expense_claim';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'employee_id', 'expense_date', 'category', 'amount', 
        'description', 'status', 'payment_account_id', 'journal_entry_id'
    ];
    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:4'];

    public function employee() { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
    public function paymentAccount() { return $this->belongsTo(FinBankAccount::class, 'payment_account_id'); }
}
