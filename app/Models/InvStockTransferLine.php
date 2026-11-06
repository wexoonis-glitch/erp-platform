<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvStockTransferLine extends Model {
    use HasUuids;
    protected $table = 'inv_stock_transfer_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'transfer_id', 'product_id', 'quantity'];
    protected $casts = ['quantity' => 'decimal:4'];

    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
}
