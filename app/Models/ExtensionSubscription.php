<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExtensionSubscription extends Model
{
    use HasUuids;

    protected $table = 'extension_subscription';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'plugin_id',
        'event_key',
        'handler_class',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
