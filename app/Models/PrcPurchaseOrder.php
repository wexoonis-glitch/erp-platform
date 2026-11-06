<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrcPurchaseOrder extends Model {
    use HasUuids;
    protected $table = 'prc_purchase_order';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'supplier_id', 'po_no', 'order_date', 'expected_date', 'subtotal', 'vat_total', 'grand_total', 'status', 'notes'];
    protected $casts = ['order_date' => 'date', 'expected_date' => 'date', 'subtotal' => 'decimal:4', 'vat_total' => 'decimal:4', 'grand_total' => 'decimal:4'];
    
    public function supplier() { return $this->belongsTo(FinSupplier::class, 'supplier_id'); }
    public function lines() { return $this->hasMany(PrcPurchaseOrderLine::class, 'po_id'); }
    public function receipts() { return $this->hasMany(PrcGoodsReceipt::class, 'po_id'); }
}
