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
        'tenant_id', 'module_id', 'version', 'manifest',
        'vendor', 'checksum', 'status', 'created_at',
    ];

    protected $casts = [
        'manifest'   => 'array',
        'created_at' => 'datetime',
    ];
}
