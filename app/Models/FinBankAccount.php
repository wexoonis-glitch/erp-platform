<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinBankAccount extends Model {
    use HasUuids;
    protected $table = 'fin_bank_account';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'name', 'type', 'account_no', 'iban', 'currency', 'gl_account_id', 'status'];

    public function glAccount() { return $this->belongsTo(FinAccount::class, 'gl_account_id'); }
    public function transactions() { return $this->hasMany(FinBankTransaction::class, 'bank_account_id'); }

    public function balance(): float {
        $in = (float) $this->transactions()->whereIn('type', ['deposit', 'transfer_in'])->sum('amount');
        $out = (float) $this->transactions()->whereIn('type', ['withdrawal', 'transfer_out'])->sum('amount');
        return $in - $out;
    }
}
