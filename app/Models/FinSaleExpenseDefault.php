<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinSaleExpenseDefault extends Model {
    use HasUuids;

    protected $table = 'fin_sale_expense_default';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'name', 'default_amount', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'decimal:4',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];
}
