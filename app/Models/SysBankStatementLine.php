<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysBankStatementLine extends Model {
    use HasUuids;
    protected $table = 'sys_bank_statement_line';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'statement_id', 'line_no', 'value_date', 'amount',
        'description', 'reference', 'status', 'matched_transaction_id', 'matched_at',
    ];
    protected $casts = [
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'matched_at' => 'datetime',
        'line_no' => 'integer',
    ];

    public function statement() { return $this->belongsTo(SysBankStatement::class, 'statement_id'); }
    public function matchedTransaction() { return $this->belongsTo(FinBankTransaction::class, 'matched_transaction_id'); }
}
