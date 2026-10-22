<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinApInvoice extends Model
{
    use HasUuids;

    protected $table = 'fin_ap_invoice';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'supplier_id', 'invoice_no', 'invoice_date', 'due_date',
        'currency', 'subtotal', 'vat_total', 'grand_total', 'paid_total',
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

    public function supplier()
    {
        return $this->belongsTo(FinSupplier::class, 'supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(FinApInvoiceLine::class, 'invoice_id')->orderBy('line_no');
    }

    public function journalEntry()
    {
        return $this->belongsTo(FinJournalEntry::class, 'journal_entry_id');
    }

    public function openAmount(): float
    {
        return (float) $this->grand_total - (float) $this->paid_total;
    }
}
