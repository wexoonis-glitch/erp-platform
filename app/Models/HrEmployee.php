<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HrEmployee extends Model {
    use HasUuids;
    protected $table = 'hr_employee';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'employee_no', 'first_name', 'last_name', 'email', 'phone',
        'national_id', 'department', 'cost_center_id', 'position', 'hire_date', 'termination_date',
        'gross_salary', 'status',
    ];
    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'gross_salary' => 'decimal:4',
    ];

    public function costCenter() { return $this->belongsTo(FinCostCenter::class, 'cost_center_id'); }
    public function getFullNameAttribute(): string { return "{$this->first_name} {$this->last_name}"; }
}
