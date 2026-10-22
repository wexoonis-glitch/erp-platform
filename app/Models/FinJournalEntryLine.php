<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinJournalEntryLine extends Model
{
    use HasUuids;

    protected $table = 'fin_journal_entry_line';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'journal_entry_id', 'account_id',
        'debit', 'credit', 'description', 'line_no',
    ];

    protected $casts = [
        'debit'  => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(FinJournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(FinAccount::class, 'account_id');
    }
}
