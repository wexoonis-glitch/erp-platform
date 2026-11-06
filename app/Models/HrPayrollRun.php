<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HrPayrollRun extends Model {
    use HasUuids;
    protected $table = 'hr_payroll_run';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'period', 'payment_date', 'total_gross', 'total_deductions', 'total_net', 'status', 'journal_entry_id'];
    protected $casts = ['payment_date' => 'date', 'total_gross' => 'decimal:4', 'total_deductions' => 'decimal:4', 'total_net' => 'decimal:4'];

    public function items() { return $this->hasMany(HrPayrollItem::class, 'payroll_run_id'); }
}
