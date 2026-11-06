<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvProduct extends Model {
    use HasUuids;
    protected $table = 'inv_product';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'code', 'name', 'unit', 'track_stock', 'sales_price',
        'purchase_price', 'replacement_cost', 'target_margin_pct', 'min_margin_pct', 'overhead_per_unit',
        'vat_rate', 'tax_regime_id', 'cost_method',
        'revenue_account_id', 'inventory_account_id', 'cogs_account_id', 'status',
        'custom_fields',
    ];
    protected $casts = [
        'track_stock' => 'boolean',
        'sales_price' => 'decimal:4',
        'purchase_price' => 'decimal:4',
        'replacement_cost' => 'decimal:4',
        'target_margin_pct' => 'decimal:4',
        'min_margin_pct' => 'decimal:4',
        'overhead_per_unit' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    public function revenueAccount() { return $this->belongsTo(FinAccount::class, 'revenue_account_id'); }
    public function inventoryAccount() { return $this->belongsTo(FinAccount::class, 'inventory_account_id'); }
    public function cogsAccount() { return $this->belongsTo(FinAccount::class, 'cogs_account_id'); }
    public function taxRegime() { return $this->belongsTo(SysTaxRegime::class, 'tax_regime_id'); }
    public function bom() { return $this->hasOne(MfgBom::class, 'product_id'); }
}
