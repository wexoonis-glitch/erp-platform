<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysApprovalStep extends Model {
    use HasUuids;
    protected $table = 'sys_approval_step';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'workflow_id', 'order_no', 'name', 'approver_role', 'min_amount', 'max_amount'];
    protected $casts = [
        'order_no' => 'integer',
        'min_amount' => 'decimal:4',
        'max_amount' => 'decimal:4',
    ];

    public function workflow() { return $this->belongsTo(SysApprovalWorkflow::class, 'workflow_id'); }
}
