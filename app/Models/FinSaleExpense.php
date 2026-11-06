<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinSaleExpense extends Model
{
    use HasUuids;

    protected $table = 'fin_sale_expense';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'sale_order_id', 'name', 'amount', 'sort_order',
    ];

    protected $casts = [
        'amount'     => 'decimal:4',
        'sort_order' => 'integer',
    ];
}
