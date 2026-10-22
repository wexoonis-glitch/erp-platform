<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    use HasUuids;

    protected $table = 'plugin';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'plugin_id',
        'name',
        'module_id',
        'path',
        'version',
        'manifest',
        'vendor',
        'checksum',
        'status',
        'installed_at',
        'enabled_at',
        'created_at',
    ];

    protected $casts = [
        'manifest'     => 'array',
        'installed_at' => 'datetime',
        'enabled_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
