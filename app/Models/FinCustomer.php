<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinCustomer extends Model {
    use HasUuids;
    protected $table = 'fin_customer';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'code', 'name', 'tax_no', 'tax_office',
        'email', 'phone', 'address', 'currency', 'status', 'price_list_id',
        'custom_fields',
    ];
    protected $casts = ['custom_fields' => 'array'];

    public function invoices() { return $this->hasMany(FinArInvoice::class, 'customer_id'); }
    public function priceList() { return $this->belongsTo(InvPriceList::class, 'price_list_id'); }
}
