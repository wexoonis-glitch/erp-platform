<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';
    public $timestamps = false;
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_email',
        'function_id',
        'entity',
        'entity_id',
        'action',
        'before_state',
        'after_state',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
        'created_at'   => 'datetime',
    ];
}
