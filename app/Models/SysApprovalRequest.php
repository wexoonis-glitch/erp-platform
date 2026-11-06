<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysApprovalRequest extends Model {
    use HasUuids;
    protected $table = 'sys_approval_request';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'workflow_id', 'entity_type', 'entity_id', 'entity_label',
        'payload', 'status', 'current_step_order', 'requested_by', 'requested_at', 'finished_at',
    ];
    protected $casts = [
        'payload' => 'array',
        'current_step_order' => 'integer',
        'requested_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function workflow() { return $this->belongsTo(SysApprovalWorkflow::class, 'workflow_id'); }
    public function decisions() { return $this->hasMany(SysApprovalDecision::class, 'request_id')->orderBy('step_order'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }

    public function currentStep(): ?SysApprovalStep {
        return $this->workflow->steps()->where('order_no', $this->current_step_order)->first();
    }
}
