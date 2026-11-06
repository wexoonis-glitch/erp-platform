<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysApprovalDecision extends Model {
    use HasUuids;
    protected $table = 'sys_approval_decision';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'request_id', 'step_id', 'step_order', 'decision', 'decided_by', 'notes', 'decided_at'];
    protected $casts = [
        'step_order' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function request() { return $this->belongsTo(SysApprovalRequest::class, 'request_id'); }
    public function step() { return $this->belongsTo(SysApprovalStep::class, 'step_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
}
