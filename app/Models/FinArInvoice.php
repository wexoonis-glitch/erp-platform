<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinArInvoice extends Model {
    use HasUuids;
    protected $table = 'fin_ar_invoice';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'customer_id', 'sales_order_id', 'ettn',
        'invoice_no', 'invoice_date', 'due_date', 'currency',
        'subtotal', 'vat_total', 'grand_total', 'paid_total',
        'status', 'journal_entry_id', 'notes',
    ];
    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:4',
        'vat_total'    => 'decimal:4',
        'grand_total'  => 'decimal:4',
        'paid_total'   => 'decimal:4',
    ];

    public function customer() { return $this->belongsTo(FinCustomer::class, 'customer_id'); }
    public function salesOrder() { return $this->belongsTo(OtcSalesOrder::class, 'sales_order_id'); }
    public function lines() { return $this->hasMany(FinArInvoiceLine::class, 'invoice_id')->orderBy('line_no'); }
    public function journalEntry() { return $this->belongsTo(FinJournalEntry::class, 'journal_entry_id'); }
}
