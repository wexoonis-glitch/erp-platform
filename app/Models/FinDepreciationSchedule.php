<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinDepreciationSchedule extends Model {
    use HasUuids;
    protected $table = 'fin_depreciation_schedule';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'asset_id', 'period_year', 'period_month',
        'depreciation_amount', 'accumulated_after', 'status', 'journal_entry_id', 'posted_at',
    ];
    protected $casts = [
        'depreciation_amount' => 'decimal:4',
        'accumulated_after' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function asset() { return $this->belongsTo(FinFixedAsset::class, 'asset_id'); }
    public function journalEntry() { return $this->belongsTo(FinJournalEntry::class, 'journal_entry_id'); }
}
