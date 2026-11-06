<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysScheduleRun extends Model {
    use HasUuids;
    protected $table = 'sys_schedule_run';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'task_code', 'task_name', 'started_at', 'finished_at',
        'status', 'output', 'error',
    ];
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
