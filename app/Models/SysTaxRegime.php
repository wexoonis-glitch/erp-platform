<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysTaxRegime extends Model {
    use HasUuids;
    protected $table = 'sys_tax_regime';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'tenant_id', 'code', 'name', 'rate', 'country', 'region',
        'is_inclusive', 'applies_to', 'is_default', 'status',
    ];
    protected $casts = [
        'rate' => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_default' => 'boolean',
    ];
}
