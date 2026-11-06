<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinExchangeRate extends Model {
    use HasUuids;
    protected $table = 'fin_exchange_rate';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'currency_id', 'rate_date', 'rate'];
    protected $casts = ['rate_date' => 'date', 'rate' => 'decimal:6'];

    public function currency() { return $this->belongsTo(FinCurrency::class, 'currency_id'); }
}
