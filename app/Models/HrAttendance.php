<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HrAttendance extends Model {
    use HasUuids;
    protected $table = 'hr_attendance';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'employee_id', 'date', 'status', 'overtime_hours', 'notes',
    ];
    protected $casts = [
        'date' => 'date',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee() {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
