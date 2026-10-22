<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinApPaymentAllocation extends Model
{
    use HasUuids;

    protected $table = 'fin_ap_payment_allocation';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tenant_id', 'payment_id', 'invoice_id', 'amount'];

    protected $casts = ['amount' => 'decimal:4'];

    public function payment()
    {
        return $this->belongsTo(FinApPayment::class, 'payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(FinApInvoice::class, 'invoice_id');
    }
}
