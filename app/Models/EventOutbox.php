<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOutbox extends Model
{
    protected $table = 'event_outbox';
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'event_key',
        'payload',
        'status',
        'attempts',
        'available_at',
        'processed_at',
        'last_error',
    ];

    protected $casts = [
        'payload'      => 'array',
        'attempts'     => 'integer',
        'available_at' => 'datetime',
        'created_at'   => 'datetime',
        'processed_at' => 'datetime',
    ];
}
