<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrcPurchaseOrderLine extends Model {
    use HasUuids;
    protected $table = 'prc_purchase_order_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'po_id', 'product_id', 'description', 'quantity', 'unit_price', 'vat_rate', 'received_quantity', 'subtotal', 'vat_amount', 'total'];
    protected $casts = ['quantity' => 'decimal:4', 'unit_price' => 'decimal:4', 'vat_rate' => 'decimal:2', 'received_quantity' => 'decimal:4', 'subtotal' => 'decimal:4', 'vat_amount' => 'decimal:4', 'total' => 'decimal:4'];
    
    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
}
