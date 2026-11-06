<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrcGoodsReceiptLine extends Model {
    use HasUuids;
    protected $table = 'prc_goods_receipt_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'gr_id', 'po_line_id', 'product_id', 'quantity', 'unit_cost'];
    protected $casts = ['quantity' => 'decimal:4', 'unit_cost' => 'decimal:4'];
    
    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
}
