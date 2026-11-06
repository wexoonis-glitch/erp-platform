<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SysTenantFeature extends Model
{
    use HasUuids;

    protected $table = 'sys_tenant_feature';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'feature_key', 'enabled', 'config', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config'  => 'array',
    ];
}
