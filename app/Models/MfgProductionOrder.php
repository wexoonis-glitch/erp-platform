<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MfgProductionOrder extends Model {
    use HasUuids;
    protected $table = 'mfg_production_order';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'product_id', 'bom_id', 'order_no', 'order_date', 'quantity', 'status', 'total_cost'];
    protected $casts = ['order_date' => 'date', 'quantity' => 'decimal:4', 'total_cost' => 'decimal:4'];

    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
    public function bom() { return $this->belongsTo(MfgBom::class, 'bom_id'); }
}
