<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinSupplier extends Model
{
    use HasUuids;

    protected $table = 'fin_supplier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'tax_no', 'tax_office',
        'email', 'phone', 'address', 'currency', 'status',
    ];

    public function invoices()
    {
        return $this->hasMany(FinApInvoice::class, 'supplier_id');
    }

    public function openBalance(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('grand_total') - (float) $this->invoices()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('paid_total');
    }
}
