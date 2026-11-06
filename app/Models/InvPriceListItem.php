<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvPriceListItem extends Model {
    use HasUuids;
    protected $table = 'inv_price_list_item';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'price_list_id', 'product_id', 'unit_price', 'min_quantity', 'discount_rate'];
    protected $casts = ['unit_price' => 'decimal:4', 'min_quantity' => 'decimal:4', 'discount_rate' => 'decimal:2'];

    public function priceList() { return $this->belongsTo(InvPriceList::class, 'price_list_id'); }
    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
}
