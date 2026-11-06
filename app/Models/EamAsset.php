<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EamAsset extends Model {
    use HasUuids;
    protected $table = 'eam_asset';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'category', 'serial_no', 'purchase_date', 'purchase_cost', 'status', 'notes'];
    protected $casts = ['purchase_date' => 'date', 'purchase_cost' => 'decimal:4'];

    public function maintenanceOrders() { return $this->hasMany(EamMaintenanceOrder::class, 'asset_id'); }
}
