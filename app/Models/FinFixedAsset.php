<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinFixedAsset extends Model {
    use HasUuids;
    protected $table = 'fin_fixed_asset';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'code', 'name', 'category', 'acquisition_date',
        'acquisition_cost', 'useful_life_months', 'depreciation_method',
        'salvage_value', 'accumulated_depreciation', 'book_value',
        'asset_account_id', 'accumulated_dep_account_id', 'depreciation_expense_account_id',
        'cost_center_id', 'status', 'disposal_date', 'disposal_amount', 'disposal_notes', 'eam_asset_id',
    ];
    protected $casts = [
        'acquisition_date' => 'date',
        'disposal_date' => 'date',
        'acquisition_cost' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'book_value' => 'decimal:4',
        'disposal_amount' => 'decimal:4',
    ];

    public function assetAccount() { return $this->belongsTo(FinAccount::class, 'asset_account_id'); }
    public function accumulatedDepAccount() { return $this->belongsTo(FinAccount::class, 'accumulated_dep_account_id'); }
    public function depreciationExpenseAccount() { return $this->belongsTo(FinAccount::class, 'depreciation_expense_account_id'); }
    public function costCenter() { return $this->belongsTo(FinCostCenter::class, 'cost_center_id'); }
    public function schedule() { return $this->hasMany(FinDepreciationSchedule::class, 'asset_id')->orderBy('period_year')->orderBy('period_month'); }
}
