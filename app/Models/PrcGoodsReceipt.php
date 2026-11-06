<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PrcGoodsReceipt extends Model {
    use HasUuids;
    protected $table = 'prc_goods_receipt';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'po_id', 'gr_no', 'receipt_date', 'status'];
    protected $casts = ['receipt_date' => 'date'];

    public function purchaseOrder() { return $this->belongsTo(PrcPurchaseOrder::class, 'po_id'); }
    public function lines() { return $this->hasMany(PrcGoodsReceiptLine::class, 'gr_id'); }
}
