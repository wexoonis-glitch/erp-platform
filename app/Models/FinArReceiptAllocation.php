<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinArReceiptAllocation extends Model {
    use HasUuids;
    protected $table = 'fin_ar_receipt_allocation';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'receipt_id', 'invoice_id', 'amount'];
    protected $casts = ['amount' => 'decimal:4'];

    public function invoice() { return $this->belongsTo(FinArInvoice::class, 'invoice_id'); }
}
