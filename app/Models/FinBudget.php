<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinBudget extends Model {
    use HasUuids;
    protected $table = 'fin_budget';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'cost_center_id', 'year', 'month', 'planned_amount', 'notes'];
    protected $casts = ['planned_amount' => 'decimal:4'];

    public function costCenter() { return $this->belongsTo(FinCostCenter::class, 'cost_center_id'); }
}
