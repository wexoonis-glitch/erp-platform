<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvStockMovement extends Model {
    use HasUuids;
    protected $table = 'inv_stock_movement';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'product_id', 'warehouse_id', 'movement_date', 'type',
        'quantity', 'remaining_quantity', 'unit_cost',
        'reference_type', 'reference_id', 'description',
    ];
    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];
    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
    public function warehouse() { return $this->belongsTo(InvWarehouse::class, 'warehouse_id'); }
}
