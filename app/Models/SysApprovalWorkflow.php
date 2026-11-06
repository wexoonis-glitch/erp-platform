<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysApprovalWorkflow extends Model {
    use HasUuids;
    protected $table = 'sys_approval_workflow';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'entity_type', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function steps() { return $this->hasMany(SysApprovalStep::class, 'workflow_id')->orderBy('order_no'); }
    public function requests() { return $this->hasMany(SysApprovalRequest::class, 'workflow_id'); }
}
