<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EamMaintenanceOrder extends Model {
    use HasUuids;
    protected $table = 'eam_maintenance_order';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'asset_id', 'order_no', 'type', 'scheduled_date', 'completed_date', 'status', 'description', 'cost'];
    protected $casts = ['scheduled_date' => 'date', 'completed_date' => 'date', 'cost' => 'decimal:4'];

    public function asset() { return $this->belongsTo(EamAsset::class, 'asset_id'); }
}
