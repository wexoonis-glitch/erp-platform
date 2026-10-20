<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunctionRegistry extends Model
{
    protected $table = 'function_registry';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'domain', 'subprocess', 'name', 'type',
        'description', 'version', 'status', 'owner', 'sla_ms', 'spec',
    ];

    protected $casts = [
        'spec'   => 'array',
        'sla_ms' => 'integer',
    ];
}
