<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HrPayrollItem extends Model {
    use HasUuids;
    protected $table = 'hr_payroll_item';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'payroll_run_id', 'employee_id', 'gross_salary', 'sgk_employee', 'sgk_employer', 'unemployment_employee', 'income_tax', 'stamp_tax', 'net_salary'];
    protected $casts = ['gross_salary' => 'decimal:4', 'sgk_employee' => 'decimal:4', 'sgk_employer' => 'decimal:4', 'unemployment_employee' => 'decimal:4', 'income_tax' => 'decimal:4', 'stamp_tax' => 'decimal:4', 'net_salary' => 'decimal:4'];

    public function employee() { return $this->belongsTo(HrEmployee::class, 'employee_id'); }
}
