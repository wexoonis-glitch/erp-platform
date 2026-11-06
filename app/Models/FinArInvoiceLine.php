<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinArInvoiceLine extends Model {
    use HasUuids;
    protected $table = 'fin_ar_invoice_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'invoice_id', 'revenue_account_id', 'product_id', 'tax_regime_id',
        'description', 'quantity', 'unit_price', 'vat_rate',
        'subtotal', 'vat_amount', 'total', 'line_no',
    ];
    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_price' => 'decimal:4',
        'vat_rate'   => 'decimal:2',
        'subtotal'   => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'total'      => 'decimal:4',
    ];

    public function invoice() { return $this->belongsTo(FinArInvoice::class, 'invoice_id'); }
    public function revenueAccount() { return $this->belongsTo(FinAccount::class, 'revenue_account_id'); }
    public function product() { return $this->belongsTo(InvProduct::class, 'product_id'); }
    public function taxRegime() { return $this->belongsTo(SysTaxRegime::class, 'tax_regime_id'); }
}
