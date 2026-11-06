<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinCurrency extends Model {
    use HasUuids;
    protected $table = 'fin_currency';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'code', 'name', 'symbol', 'is_base', 'status'];
    protected $casts = ['is_base' => 'boolean'];

    public function exchangeRates() { return $this->hasMany(FinExchangeRate::class, 'currency_id'); }
}
