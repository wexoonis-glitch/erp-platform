<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinJournalEntry extends Model
{
    use HasUuids;

    protected $table = 'fin_journal_entry';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'entry_no', 'entry_date', 'description', 'status',
        'posted_at', 'posted_by', 'source_type', 'source_id', 'reverses_entry_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at'  => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(FinJournalEntryLine::class, 'journal_entry_id')->orderBy('line_no');
    }

    public function reverses()
    {
        return $this->belongsTo(FinJournalEntry::class, 'reverses_entry_id');
    }

    public function totalDebit(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->totalDebit() - $this->totalCredit()) < 0.0001;
    }
}
