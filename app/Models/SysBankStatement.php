<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysBankStatement extends Model {
    use HasUuids;
    protected $table = 'sys_bank_statement';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'bank_account_id', 'file_name', 'format', 'file_path',
        'statement_from', 'statement_to', 'total_lines', 'matched_lines', 'status',
    ];
    protected $casts = [
        'statement_from' => 'date',
        'statement_to' => 'date',
        'total_lines' => 'integer',
        'matched_lines' => 'integer',
    ];

    public function bankAccount() { return $this->belongsTo(FinBankAccount::class, 'bank_account_id'); }
    public function lines() { return $this->hasMany(SysBankStatementLine::class, 'statement_id')->orderBy('line_no'); }
}
