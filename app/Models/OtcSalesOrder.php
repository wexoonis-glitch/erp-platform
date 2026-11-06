<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OtcSalesOrder extends Model {
    use HasUuids;
    protected $table = 'otc_sales_order';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'customer_id', 'so_no', 'order_date', 'subtotal', 'vat_total', 'grand_total', 'status', 'ar_invoice_id', 'notes'];
    protected $casts = ['order_date' => 'date', 'subtotal' => 'decimal:4', 'vat_total' => 'decimal:4', 'grand_total' => 'decimal:4'];
    
    public function customer() { return $this->belongsTo(FinCustomer::class, 'customer_id'); }
    public function lines() { return $this->hasMany(OtcSalesOrderLine::class, 'so_id'); }
    public function invoice() { return $this->belongsTo(FinArInvoice::class, 'ar_invoice_id'); }
}
