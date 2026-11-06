<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinAccountingPeriod extends Model {
    use HasUuids;
    protected $table = 'fin_accounting_period';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['tenant_id', 'year', 'month', 'status', 'closed_at'];
    protected $casts = ['closed_at' => 'datetime'];

    public static function isLocked(string $date): bool {
        $d = \Carbon\Carbon::parse($date);
        $period = self::where('year', $d->format('Y'))->where('month', $d->format('m'))->first();
        return $period && $period->status === 'closed';
    }
}
