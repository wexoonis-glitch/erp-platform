<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModule extends Model
{
    protected $table = 'tenant_module';

    // Composite PK (tenant_id, module_id) — id kolonu yok
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'module_id',
        'version',
        'status',
        'config',
        'installed_at',
    ];

    protected $casts = [
        'config'       => 'array',
        'installed_at' => 'datetime',
    ];
}
