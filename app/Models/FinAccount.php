<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinAccount extends Model
{
    use HasUuids;

    protected $table = 'fin_account';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'parent_id',
        'is_postable', 'currency', 'status', 'description',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
    ];

    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public function parent()
    {
        return $this->belongsTo(FinAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FinAccount::class, 'parent_id');
    }

    public function lines()
    {
        return $this->hasMany(FinJournalEntryLine::class, 'account_id');
    }

    public function balance(?string $asOf = null): float
    {
        $q = $this->lines()
            ->whereHas('journalEntry', function ($w) use ($asOf) {
                $w->where('status', 'posted');
                if ($asOf) $w->where('entry_date', '<=', $asOf);
            });

        $debit = (float) (clone $q)->sum('debit');
        $credit = (float) (clone $q)->sum('credit');

        return in_array($this->type, ['asset', 'expense'], true)
            ? $debit - $credit
            : $credit - $debit;
    }
}
