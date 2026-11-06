<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvPriceList extends Model {
    use HasUuids;
    protected $table = 'inv_price_list';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'currency', 'valid_from', 'valid_to', 'priority', 'is_default', 'status'];
    protected $casts = ['valid_from' => 'date', 'valid_to' => 'date', 'is_default' => 'boolean'];

    public function items() { return $this->hasMany(InvPriceListItem::class, 'price_list_id'); }
}
