<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QmInspectionPlan extends Model {
    use HasUuids;
    protected $table = 'qm_inspection_plan';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'product_id', 'code', 'name', 'description', 'status'];

    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
    public function criteria() { return $this->hasMany(QmInspectionCriteria::class, 'plan_id'); }
}
